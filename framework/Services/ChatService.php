<?php
declare(strict_types=1);

namespace Framework\Services;

use Configuration\Services\SettingsService;
use Configuration\Services\AIModelService;

/**
 * Assistant IA conversationnel pré-intégré au core (icône 🧠 du header,
 * disponible sur toutes les pages admin, indépendant de tout module tiers —
 * fonctionne dès qu'un provider IA est configuré dans Configuration → IA).
 * Conversations et messages persistés par utilisateur — chacun a son propre
 * historique, comme sur claude.ai/ChatGPT.
 */
class ChatService
{
    /** Nombre max de messages de l'historique renvoyés en contexte à chaque appel. */
    private const HISTORY_CONTEXT_LIMIT = 30;

    public const PRIMER_PATH = ROOT_PATH . '/framework/data/aegis_framework_primer.md';

    private SettingsService  $settings;
    private AIModelService   $models;
    private AIClientService  $client;

    public function __construct(private Database $db)
    {
        $this->settings = new SettingsService($db);
        $this->models   = new AIModelService($db);
        $this->client   = new AIClientService($db);
    }

    /**
     * Providers sélectionnables côté UI : les providers cloud nécessitent une
     * clé API configurée ET au moins un modèle actif ; Ollama (local, sans
     * authentification) nécessite seulement un modèle actif.
     */
    public function availableProviders(): array
    {
        $providers = ['openai' => 'OpenAI', 'claude' => 'Claude', 'mistral' => 'Mistral', 'gemini' => 'Gemini', 'groq' => 'Groq', 'ollama' => 'Ollama (local)'];
        $out = [];
        foreach ($providers as $key => $label) {
            if ($key !== 'ollama' && (string)$this->settings->get("{$key}_api_key", '') === '') {
                continue;
            }
            $activeModels = $this->models->getByProvider($key, true);
            if (empty($activeModels)) {
                continue;
            }
            $out[] = ['key' => $key, 'label' => $label, 'models' => $activeModels];
        }
        return $out;
    }

    public function getConversations(int $userId): array
    {
        return $this->db->query(
            'SELECT * FROM cms_chat_conversations WHERE user_id = ? ORDER BY updated_at DESC',
            [$userId]
        );
    }

    public function getConversation(int $id, int $userId): ?array
    {
        $rows = $this->db->query(
            'SELECT * FROM cms_chat_conversations WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        );
        return $rows[0] ?? null;
    }

    public function getMessages(int $conversationId): array
    {
        return $this->db->query(
            'SELECT * FROM cms_chat_messages WHERE conversation_id = ? ORDER BY id ASC',
            [$conversationId]
        );
    }

    public function renameConversation(int $id, int $userId, string $title): bool
    {
        $title = trim(mb_substr($title, 0, 200));
        if ($title === '') {
            return false;
        }
        return $this->db->execute(
            'UPDATE cms_chat_conversations SET title = ? WHERE id = ? AND user_id = ?',
            [$title, $id, $userId]
        ) > 0;
    }

    public function deleteConversation(int $id, int $userId): bool
    {
        return $this->db->execute(
            'DELETE FROM cms_chat_conversations WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    /**
     * Envoie un message utilisateur (crée la conversation à la volée si besoin)
     * et renvoie la réponse de l'assistant.
     * @return array{ok:bool, conversation_id:?int, message:?array, error:?string}
     */
    public function sendMessage(int $userId, ?int $conversationId, string $userMessage, string $provider, string $model): array
    {
        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            return ['ok' => false, 'conversation_id' => $conversationId, 'message' => null, 'error' => 'Message vide'];
        }

        $allowedKeys = array_column($this->availableProviders(), 'key');
        if (!in_array($provider, $allowedKeys, true)) {
            return ['ok' => false, 'conversation_id' => $conversationId, 'message' => null, 'error' => "Provider indisponible : « {$provider} » (aucune clé API configurée ou aucun modèle actif)."];
        }

        if ($conversationId) {
            if (!$this->getConversation($conversationId, $userId)) {
                return ['ok' => false, 'conversation_id' => null, 'message' => null, 'error' => 'Conversation introuvable'];
            }
        } else {
            $title = trim((string)preg_replace('/\s+/', ' ', $userMessage));
            $title = mb_substr($title, 0, 80);
            $this->db->execute(
                'INSERT INTO cms_chat_conversations (user_id, title, provider, model_name) VALUES (?, ?, ?, ?)',
                [$userId, $title, $provider, $model]
            );
            $conversationId = (int)$this->db->getPDO()->lastInsertId();
        }

        // Historique existant → format chat(), plafonné pour ne pas gonfler indéfiniment le contexte envoyé.
        $history = array_slice($this->getMessages($conversationId), -self::HISTORY_CONTEXT_LIMIT);
        $messages = [];
        foreach ($history as $m) {
            if ($m['role'] === 'assistant' && $m['content'] === '') {
                continue; // message d'erreur précédent (pas de contenu réel à renvoyer en contexte)
            }
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // Enregistre le message utilisateur avant l'appel (reste visible même si l'IA échoue).
        $this->db->execute(
            'INSERT INTO cms_chat_messages (conversation_id, role, content) VALUES (?, ?, ?)',
            [$conversationId, 'user', $userMessage]
        );

        $result = $this->client->chat($messages, [
            'provider'    => $provider,
            'model'       => $model,
            'system'      => $this->systemPrompt(),
            'max_tokens'  => 2000,
            'temperature' => 0.5,
        ]);

        if (!$result['success']) {
            $this->db->execute(
                'INSERT INTO cms_chat_messages (conversation_id, role, content, provider, model_name, error_message) VALUES (?, ?, ?, ?, ?, ?)',
                [$conversationId, 'assistant', '', $provider, $model, mb_substr((string)$result['error'], 0, 490)]
            );
            $this->touchConversation($conversationId, 0, 0);
            return ['ok' => false, 'conversation_id' => $conversationId, 'message' => null, 'error' => $result['error'] ?? 'Échec de l\'appel IA'];
        }

        $tokensIn  = (int)$result['tokens_input'];
        $tokensOut = (int)$result['tokens_output'];

        $this->db->execute(
            'INSERT INTO cms_chat_messages (conversation_id, role, content, tokens_input, tokens_output, provider, model_name) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$conversationId, 'assistant', (string)$result['content'], $tokensIn, $tokensOut, $provider, $model]
        );
        $this->touchConversation($conversationId, $tokensIn, $tokensOut);

        return [
            'ok'              => true,
            'conversation_id' => $conversationId,
            'message'         => [
                'content'       => (string)$result['content'],
                'tokens_input'  => $tokensIn,
                'tokens_output' => $tokensOut,
                'provider'      => $provider,
                'model'         => $model,
            ],
            'error' => null,
        ];
    }

    /** Date de dernière mise à jour de la base de connaissances (mtime du primer), affichée dans l'UI. */
    public static function primerLastUpdated(): ?string
    {
        if (!is_file(self::PRIMER_PATH)) {
            return null;
        }
        $mtime = @filemtime(self::PRIMER_PATH);
        return $mtime ? date('d/m/Y', $mtime) : null;
    }

    /**
     * Prompt système : instructions générales + primer Aegis Framework (chargé
     * depuis un fichier Markdown maintenu à part — voir
     * framework/data/aegis_framework_primer.md, inclut les liens vers la
     * documentation officielle).
     */
    private function systemPrompt(): string
    {
        $base = "Tu es l'assistant IA intégré à Aegis Framework. Tu aides l'administrateur de la plateforme sur toutes ses questions : configuration, modules, code, dépannage, bonnes pratiques, ou toute autre demande liée à la gestion du site. Beaucoup d'administrateurs sont débutants — sois clair, pédagogue et concret, avec des exemples si utile. Réponds en français sauf si on te parle dans une autre langue.";

        $primer = @file_get_contents(self::PRIMER_PATH);
        if ($primer === false || trim($primer) === '') {
            return $base;
        }

        return $base . "\n\n---\n\nDocumentation de référence sur Aegis Framework (architecture, modules, conventions, liens vers la documentation officielle) :\n\n" . $primer;
    }

    private function touchConversation(int $id, int $tokensIn, int $tokensOut): void
    {
        $this->db->execute(
            'UPDATE cms_chat_conversations
                SET tokens_input_total = tokens_input_total + ?,
                    tokens_output_total = tokens_output_total + ?,
                    updated_at = NOW()
             WHERE id = ?',
            [$tokensIn, $tokensOut, $id]
        );
    }
}
