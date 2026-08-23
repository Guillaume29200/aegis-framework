<?php
/**
 * AIModelsHelper - Fonctions globales pour accès aux modèles IA
 *
 */

require_once __DIR__ . '/AIClientHelper.php';

use Configuration\Services\AIModelService;

/**
 * Récupérer tous les modèles IA
 * 
 * @param bool $activeOnly Seulement les actifs
 * @return array
 */
function ai_get_models(bool $activeOnly = true): array
{
    global $GLOBALS;
    
    if (!isset($GLOBALS['ai_model_service'])) {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) {
            return [];
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getAll($activeOnly);
}

/**
 * Récupérer modèles par provider
 * 
 * @param string $provider 'openai', 'claude', 'mistral'
 * @param bool $activeOnly Seulement les actifs
 * @return array
 */
function ai_get_models_by_provider(string $provider, bool $activeOnly = true): array
{
    global $GLOBALS;
    
    if (!isset($GLOBALS['ai_model_service'])) {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) {
            return [];
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getByProvider($provider, $activeOnly);
}

/**
 * Récupérer le modèle par défaut — global, ou celui d'un provider si précisé
 * (le défaut est scopé par provider : un OpenAI, un Claude, un Mistral, etc.)
 *
 * @param string|null $provider 'openai', 'claude', 'mistral'… ou null pour le défaut global
 * @return array|null
 */
function ai_get_default_model(?string $provider = null): ?array
{
    global $GLOBALS;

    if (!isset($GLOBALS['ai_model_service'])) {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) {
            return null;
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }

    return $provider !== null
        ? $GLOBALS['ai_model_service']->getDefaultForProvider($provider)
        : $GLOBALS['ai_model_service']->getDefault();
}

/**
 * Récupérer un modèle par ID
 * 
 * @param int $id
 * @return array|null
 */
function ai_get_model(int $id): ?array
{
    global $GLOBALS;
    
    if (!isset($GLOBALS['ai_model_service'])) {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) {
            return null;
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getById($id);
}

/**
 * Formater le nom d'un modèle pour affichage
 * 
 * @param array $model
 * @return string
 */
function ai_model_display_name(array $model): string
{
    $name = $model['display_name'] ?? $model['model_name'] ?? 'Unknown';
    $provider = strtoupper($model['provider'] ?? '');
    
    return "{$name} ({$provider})";
}

/**
 * Obtenir l'icône d'un provider
 * 
 * @param string $provider
 * @return string Emoji
 */
function ai_provider_icon(string $provider): string
{
    $icons = [
        'openai'  => '🟢',
        'claude'  => '🟣',
        'mistral' => '🌬️',
    ];

    return $icons[$provider] ?? '🤖';
}
