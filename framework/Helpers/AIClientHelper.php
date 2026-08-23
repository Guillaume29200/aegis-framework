<?php
/**
 * AIClientHelper - Fonctions globales pour appeler l'IA (Claude/OpenAI/Mistral)
 *
 * Complète AIModelsHelper.php (catalogue en lecture seule) avec le client
 * d'appel réseau unifié : historique multi-tours, tool use, structured output.
 */

use Framework\Services\AIClientService;

/**
 * Appel multi-tours à l'IA.
 *
 * @param array $messages [['role' => 'system'|'user'|'assistant', 'content' => string], ...]
 * @param array $options  provider, model, system, tools, tool_choice, response_format,
 *                        max_tokens, temperature, timeout, max_attempts
 * @return array ['success','content','tool_calls','tokens_input','tokens_output','provider','model','error']
 */
function ai_chat(array $messages, array $options = []): array
{
    global $GLOBALS;

    if (!isset($GLOBALS['ai_client_service'])) {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) {
            return ['success' => false, 'content' => null, 'tool_calls' => [], 'tokens_input' => 0, 'tokens_output' => 0, 'provider' => '', 'model' => '', 'error' => 'Base de données indisponible'];
        }
        $GLOBALS['ai_client_service'] = new AIClientService($db);
    }

    return $GLOBALS['ai_client_service']->chat($messages, $options);
}

/**
 * Sucre pour un appel simple à un tour.
 *
 * @param string $prompt
 * @param array  $options Voir ai_chat()
 * @return array Voir ai_chat()
 */
function ai_complete(string $prompt, array $options = []): array
{
    return ai_chat([['role' => 'user', 'content' => $prompt]], $options);
}
