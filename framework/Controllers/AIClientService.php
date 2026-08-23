<?php
declare(strict_types=1);

namespace Framework\Services;

use Configuration\Services\AIModelService;
use Configuration\Services\SettingsService;

/**
 * Client IA unifié multi-provider (Claude/OpenAI/Mistral).
 *
 * Remplace les 3 implémentations dupliquées de GameNodePanel (Marketplace AI,
 * V.E.G.A, O.D.I.N) par un point d'entrée unique : historique multi-tours,
 * prompt système normalisé, tool use, structured output (natif OpenAI/Mistral),
 * retry/backoff, extraction systématique des tokens.
 *
 * Pattern match($provider) => sendXxx() privé, comme GeolocService/CloudService :
 * pas d'interface, jamais d'exception propagée pour une erreur provider/réseau
 * (toujours ['success' => false, 'error' => ...]).
 */
class AIClientService
{
    private AIModelService $modelService;
    private SettingsService $settingsService;

    public function __construct(Database $db)
    {
        $this->modelService    = new AIModelService($db);
        $this->settingsService = new SettingsService($db);
    }

    // ─── API publique ───────────────────────────────────────────────────────

    /** Sucre pour un appel simple à un tour : complete("...", [...]) */
    public function complete(string $prompt, array $options = []): array
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $options);
    }

    /**
     * @param array $messages [['role' => 'system'|'user'|'assistant', 'content' => string], ...]
     * @param array $options  provider, model, system, tools, tool_choice, response_format,
     *                        max_tokens, temperature, timeout, max_attempts
     */
    public function chat(array $messages, array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? $this->settingsService->get('default_ai_provider', 'openai'));
        $model    = $this->resolveModel($provider, $options['model'] ?? null);

        if ($model === null) {
            return $this->fail($provider, (string) ($options['model'] ?? ''), "Aucun modèle actif trouvé pour le provider: {$provider}");
        }

        // Ollama tourne en local sans authentification : pas de clé API à vérifier,
        // juste une URL de serveur (avec valeur par défaut si non configurée).
        if ($provider === 'ollama') {
            $options['max_attempts'] = (int) ($options['max_attempts'] ?? 3);
            $options['timeout']      = (int) ($options['timeout'] ?? 120); // inférence locale souvent plus lente

            try {
                $result = $this->sendOllama($messages, $model['model_name'], $this->resolveOllamaBaseUrl(), $options);
            } catch (\Throwable $e) {
                $result = $this->fail('ollama', $model['model_name'], $e->getMessage());
            }
            $result['model_id'] = (int) $model['id'];

            return $result;
        }

        $apiKey = $this->resolveApiKey($provider);
        if (empty($apiKey)) {
            $result = $this->fail($provider, $model['model_name'], "Clé API manquante pour {$provider}");
            $result['model_id'] = (int) $model['id'];

            return $result;
        }

        $options['max_attempts'] = (int) ($options['max_attempts'] ?? 3);
        $options['timeout']      = (int) ($options['timeout'] ?? 60);

        try {
            $result = match ($provider) {
                'openai'  => $this->sendOpenAI($messages, $model['model_name'], $apiKey, $options),
                'claude'  => $this->sendClaude($messages, $model['model_name'], $apiKey, $options),
                'mistral' => $this->sendMistral($messages, $model['model_name'], $apiKey, $options),
                'gemini'  => $this->sendGemini($messages, $model['model_name'], $apiKey, $options),
                'groq'    => $this->sendGroq($messages, $model['model_name'], $apiKey, $options),
                default   => $this->fail($provider, $model['model_name'], "Provider non supporté: {$provider}"),
            };
        } catch (\Throwable $e) {
            $result = $this->fail($provider, $model['model_name'], $e->getMessage());
        }

        $result['model_id'] = (int) $model['id'];

        return $result;
    }

    /** Nettoyage markdown/backticks centralisé, pour les appelants qui attendent du JSON. */
    public function stripJsonFences(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```$/m', '', $raw) ?? $raw;

        return trim($raw);
    }

    // ─── Résolution modèle / clé API ────────────────────────────────────────

    private function resolveModel(string $provider, ?string $modelName): ?array
    {
        if ($modelName !== null) {
            foreach ($this->modelService->getByProvider($provider, true) as $m) {
                if ($m['model_name'] === $modelName) {
                    return $m;
                }
            }
        }

        $default = $this->modelService->getDefaultForProvider($provider);
        if ($default) {
            return $default;
        }

        $models = $this->modelService->getByProvider($provider, true);

        return $models[0] ?? null;
    }

    private function resolveApiKey(string $provider): ?string
    {
        $stored = (string) $this->settingsService->get("{$provider}_api_key", '');
        if ($stored === '') {
            return null;
        }

        // Déchiffre si chiffré via SecretBox ; sinon, valeur legacy en clair (migration transparente).
        return SecretBox::decrypt($stored) ?? $stored;
    }

    /** URL du serveur Ollama local — pas un secret, jamais chiffrée, valeur par défaut si non configurée. */
    private function resolveOllamaBaseUrl(): string
    {
        $url = trim((string) $this->settingsService->get('ollama_base_url', ''));

        return $url !== '' ? rtrim($url, '/') : 'http://localhost:11434';
    }

    private function fail(string $provider, string $model, string $error): array
    {
        return [
            'success'       => false,
            'content'       => null,
            'tool_calls'    => [],
            'tokens_input'  => 0,
            'tokens_output' => 0,
            'provider'      => $provider,
            'model'         => $model,
            'model_id'      => 0,
            'error'         => $error,
        ];
    }

    // ─── Providers ───────────────────────────────────────────────────────────

    private function sendOpenAI(array $messages, string $model, string $apiKey, array $options): array
    {
        $body = [
            'model'       => $model,
            'messages'    => $this->buildFlatMessages($messages, $options['system'] ?? null),
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens'  => $options['max_tokens'] ?? 2000,
        ];
        $this->applyOpenAiToolOptions($body, $options);

        $res = $this->curlWithRetry(
            'https://api.openai.com/v1/chat/completions',
            ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"],
            $body,
            $options
        );

        if (!$res['ok']) {
            return $this->fail('openai', $model, $res['error'] ?? 'Erreur inconnue');
        }

        $data = json_decode((string) $res['body'], true);
        if (!is_array($data) || !isset($data['choices'][0])) {
            return $this->fail('openai', $model, 'Réponse OpenAI invalide: ' . substr((string) $res['body'], 0, 300));
        }

        $msg = $data['choices'][0]['message'] ?? [];

        return [
            'success'       => true,
            'content'       => $msg['content'] ?? null,
            'tool_calls'    => $this->parseOpenAiToolCalls($msg['tool_calls'] ?? []),
            'tokens_input'  => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'tokens_output' => (int) ($data['usage']['completion_tokens'] ?? 0),
            'provider'      => 'openai',
            'model'         => $model,
            'error'         => null,
        ];
    }

    private function sendMistral(array $messages, string $model, string $apiKey, array $options): array
    {
        $body = [
            'model'       => $model,
            'messages'    => $this->buildFlatMessages($messages, $options['system'] ?? null),
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens'  => $options['max_tokens'] ?? 2000,
        ];
        $this->applyOpenAiToolOptions($body, $options);

        $res = $this->curlWithRetry(
            'https://api.mistral.ai/v1/chat/completions',
            ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"],
            $body,
            $options
        );

        if (!$res['ok']) {
            return $this->fail('mistral', $model, $res['error'] ?? 'Erreur inconnue');
        }

        $data = json_decode((string) $res['body'], true);
        if (!is_array($data) || !isset($data['choices'][0])) {
            return $this->fail('mistral', $model, 'Réponse Mistral invalide: ' . substr((string) $res['body'], 0, 300));
        }

        $msg = $data['choices'][0]['message'] ?? [];

        return [
            'success'       => true,
            'content'       => $msg['content'] ?? null,
            'tool_calls'    => $this->parseOpenAiToolCalls($msg['tool_calls'] ?? []),
            'tokens_input'  => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'tokens_output' => (int) ($data['usage']['completion_tokens'] ?? 0),
            'provider'      => 'mistral',
            'model'         => $model,
            'error'         => null,
        ];
    }

    /** Groq — endpoint compatible OpenAI (mêmes formats requête/réponse que Mistral/OpenAI). */
    private function sendGroq(array $messages, string $model, string $apiKey, array $options): array
    {
        $body = [
            'model'       => $model,
            'messages'    => $this->buildFlatMessages($messages, $options['system'] ?? null),
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens'  => $options['max_tokens'] ?? 2000,
        ];
        $this->applyOpenAiToolOptions($body, $options);

        $res = $this->curlWithRetry(
            'https://api.groq.com/openai/v1/chat/completions',
            ['Content-Type: application/json', "Authorization: Bearer {$apiKey}"],
            $body,
            $options
        );

        if (!$res['ok']) {
            return $this->fail('groq', $model, $res['error'] ?? 'Erreur inconnue');
        }

        $data = json_decode((string) $res['body'], true);
        if (!is_array($data) || !isset($data['choices'][0])) {
            return $this->fail('groq', $model, 'Réponse Groq invalide: ' . substr((string) $res['body'], 0, 300));
        }

        $msg = $data['choices'][0]['message'] ?? [];

        return [
            'success'       => true,
            'content'       => $msg['content'] ?? null,
            'tool_calls'    => $this->parseOpenAiToolCalls($msg['tool_calls'] ?? []),
            'tokens_input'  => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'tokens_output' => (int) ($data['usage']['completion_tokens'] ?? 0),
            'provider'      => 'groq',
            'model'         => $model,
            'error'         => null,
        ];
    }

    /**
     * Ollama (serveur local, https://ollama.com) — endpoint compatible OpenAI,
     * aucune authentification. `$baseUrl` pointe vers le serveur (par défaut
     * http://localhost:11434), résolu par resolveOllamaBaseUrl().
     */
    private function sendOllama(array $messages, string $model, string $baseUrl, array $options): array
    {
        $body = [
            'model'       => $model,
            'messages'    => $this->buildFlatMessages($messages, $options['system'] ?? null),
            'stream'      => false,
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens'  => $options['max_tokens'] ?? 2000,
        ];

        $res = $this->curlWithRetry(
            $baseUrl . '/v1/chat/completions',
            ['Content-Type: application/json'],
            $body,
            $options
        );

        if (!$res['ok']) {
            $error = $res['error'] ?? 'Erreur inconnue';
            // Le serveur répond mais ne connaît pas ce modèle (pas encore téléchargé en local)
            // — hint différent d'un serveur injoignable, sinon le message induit en erreur.
            $hint = stripos($error, 'not found') !== false
                ? " — le modèle « {$model} » n'est pas installé sur ce serveur Ollama. Téléchargez-le avec : ollama pull {$model}"
                : " — le serveur Ollama est-il démarré et accessible à {$baseUrl} ?";
            return $this->fail('ollama', $model, $error . $hint);
        }

        $data = json_decode((string) $res['body'], true);
        if (!is_array($data) || !isset($data['choices'][0])) {
            return $this->fail('ollama', $model, 'Réponse Ollama invalide: ' . substr((string) $res['body'], 0, 300));
        }

        $msg = $data['choices'][0]['message'] ?? [];

        return [
            'success'       => true,
            'content'       => $msg['content'] ?? null,
            'tool_calls'    => [],
            'tokens_input'  => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'tokens_output' => (int) ($data['usage']['completion_tokens'] ?? 0),
            'provider'      => 'ollama',
            'model'         => $model,
            'error'         => null,
        ];
    }

    private function sendClaude(array $messages, string $model, string $apiKey, array $options): array
    {
        $body = [
            'model'      => $model,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'messages'   => $this->buildClaudeMessages($messages),
        ];
        if (!empty($options['system'])) {
            $body['system'] = $options['system'];
        }
        if (isset($options['temperature'])) {
            $body['temperature'] = $options['temperature'];
        }
        if (!empty($options['tools'])) {
            $body['tools'] = $this->toClaudeTools($options['tools']);
            $tc = $options['tool_choice'] ?? null;
            if (is_array($tc) && isset($tc['name'])) {
                $body['tool_choice'] = ['type' => 'tool', 'name' => $tc['name']];
            }
        }

        $res = $this->curlWithRetry(
            'https://api.anthropic.com/v1/messages',
            ['Content-Type: application/json', "x-api-key: {$apiKey}", 'anthropic-version: 2023-06-01'],
            $body,
            $options
        );

        if (!$res['ok']) {
            return $this->fail('claude', $model, $res['error'] ?? 'Erreur inconnue');
        }

        $data = json_decode((string) $res['body'], true);
        if (!is_array($data) || !isset($data['content'])) {
            return $this->fail('claude', $model, 'Réponse Claude invalide: ' . substr((string) $res['body'], 0, 300));
        }

        $text      = '';
        $toolCalls = [];
        foreach ($data['content'] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            } elseif (($block['type'] ?? '') === 'tool_use') {
                $toolCalls[] = [
                    'id'        => $block['id'] ?? '',
                    'name'      => $block['name'] ?? '',
                    'arguments' => $block['input'] ?? [],
                ];
            }
        }

        return [
            'success'       => true,
            'content'       => $text !== '' ? $text : null,
            'tool_calls'    => $toolCalls,
            'tokens_input'  => (int) ($data['usage']['input_tokens'] ?? 0),
            'tokens_output' => (int) ($data['usage']['output_tokens'] ?? 0),
            'provider'      => 'claude',
            'model'         => $model,
            'error'         => null,
        ];
    }

    /**
     * Google Gemini — la clé API se passe en query string (pas de header
     * d'authentification), et le system prompt est un champ `systemInstruction`
     * séparé, jamais mêlé aux `contents`.
     */
    private function sendGemini(array $messages, string $model, string $apiKey, array $options): array
    {
        $body = [
            'contents'         => $this->buildGeminiContents($messages),
            'generationConfig' => [
                'temperature'     => $options['temperature'] ?? 0.3,
                'maxOutputTokens' => $options['max_tokens'] ?? 2000,
            ],
        ];
        if (!empty($options['system'])) {
            $body['systemInstruction'] = ['parts' => [['text' => $options['system']]]];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model)
            . ':generateContent?key=' . urlencode($apiKey);

        $res = $this->curlWithRetry($url, ['Content-Type: application/json'], $body, $options);

        if (!$res['ok']) {
            return $this->fail('gemini', $model, $res['error'] ?? 'Erreur inconnue');
        }

        $data = json_decode((string) $res['body'], true);
        if (!is_array($data) || !isset($data['candidates'][0])) {
            return $this->fail('gemini', $model, 'Réponse Gemini invalide: ' . substr((string) $res['body'], 0, 300));
        }

        $text = '';
        foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
            $text .= $part['text'] ?? '';
        }

        return [
            'success'       => true,
            'content'       => $text !== '' ? $text : null,
            'tool_calls'    => [],
            'tokens_input'  => (int) ($data['usageMetadata']['promptTokenCount'] ?? 0),
            'tokens_output' => (int) ($data['usageMetadata']['candidatesTokenCount'] ?? 0),
            'provider'      => 'gemini',
            'model'         => $model,
            'error'         => null,
        ];
    }

    // ─── Normalisation messages / tools ─────────────────────────────────────

    /** Format OpenAI/Mistral : system en tête de `messages` s'il est fourni. */
    private function buildFlatMessages(array $messages, ?string $system): array
    {
        $out = [];
        if (!empty($system)) {
            $out[] = ['role' => 'system', 'content' => $system];
        }
        foreach ($messages as $m) {
            $out[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        return $out;
    }

    /** Format Claude : le system prompt est un champ à part, jamais dans `messages`. */
    private function buildClaudeMessages(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? '') === 'system') {
                continue;
            }
            $out[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        return $out;
    }

    /**
     * Format Gemini : `contents` avec rôles 'user'/'model' ('assistant' → 'model'),
     * le rôle 'system' est exclu (géré via `systemInstruction`, voir sendGemini()).
     */
    private function buildGeminiContents(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? '') === 'system') {
                continue;
            }
            $role = ($m['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $out[] = ['role' => $role, 'parts' => [['text' => $m['content']]]];
        }

        return $out;
    }

    private function applyOpenAiToolOptions(array &$body, array $options): void
    {
        if (!empty($options['tools'])) {
            $body['tools']       = $this->toOpenAiTools($options['tools']);
            $body['tool_choice'] = $this->toOpenAiToolChoice($options['tool_choice'] ?? null);
        }
        if (!empty($options['response_format'])) {
            $body['response_format'] = $options['response_format'];
        }
    }

    private function toOpenAiTools(array $tools): array
    {
        $out = [];
        foreach ($tools as $t) {
            $out[] = [
                'type'     => 'function',
                'function' => [
                    'name'        => $t['name'],
                    'description' => $t['description'] ?? '',
                    'parameters'  => $t['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ];
        }

        return $out;
    }

    private function toOpenAiToolChoice($choice)
    {
        if ($choice === 'none' || $choice === 'auto') {
            return $choice;
        }
        if (is_array($choice) && isset($choice['name'])) {
            return ['type' => 'function', 'function' => ['name' => $choice['name']]];
        }

        return 'auto';
    }

    private function toClaudeTools(array $tools): array
    {
        $out = [];
        foreach ($tools as $t) {
            $out[] = [
                'name'         => $t['name'],
                'description'  => $t['description'] ?? '',
                'input_schema' => $t['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass()],
            ];
        }

        return $out;
    }

    private function parseOpenAiToolCalls(array $raw): array
    {
        $out = [];
        foreach ($raw as $tc) {
            $args = json_decode($tc['function']['arguments'] ?? '{}', true);
            $out[] = [
                'id'        => $tc['id'] ?? '',
                'name'      => $tc['function']['name'] ?? '',
                'arguments' => is_array($args) ? $args : [],
            ];
        }

        return $out;
    }

    // ─── Réseau ──────────────────────────────────────────────────────────────

    /**
     * POST JSON avec retry/backoff exponentiel sur erreur réseau, 429 ou 5xx.
     * Échec immédiat (pas de retry) sur les erreurs 4xx (ex: clé invalide).
     */
    private function curlWithRetry(string $url, array $headers, array $body, array $options): array
    {
        $maxAttempts = max(1, (int) ($options['max_attempts'] ?? 3));
        $timeout     = (int) ($options['timeout'] ?? 60);
        $lastError   = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr      = curl_error($ch);
            unset($ch); // curl_close() deprecated en PHP 8.5

            if ($curlErr === '' && $httpCode >= 200 && $httpCode < 300) {
                return ['ok' => true, 'body' => $responseBody, 'error' => null];
            }

            $lastError = $curlErr !== ''
                ? "cURL: {$curlErr}"
                : "HTTP {$httpCode}: " . substr((string) $responseBody, 0, 300);

            $retryable = $curlErr !== '' || $httpCode === 429 || $httpCode >= 500;
            if (!$retryable || $attempt === $maxAttempts) {
                break;
            }

            usleep((int) (300_000 * (2 ** ($attempt - 1))));
        }

        return ['ok' => false, 'body' => null, 'error' => $lastError];
    }
}
