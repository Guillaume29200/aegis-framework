<?php
/**
 * AIModelsHelper - Fonctions globales pour accès aux modèles IA
 * 
 */

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
        $db = $GLOBALS['container']->db ?? null;
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
        $db = $GLOBALS['container']->db ?? null;
        if (!$db) {
            return [];
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getByProvider($provider, $activeOnly);
}

/**
 * Récupérer modèles par capacité
 * 
 * @param string $capability 'text', 'vision', 'audio', 'code', etc.
 * @param bool $activeOnly Seulement les actifs
 * @return array
 */
function ai_get_models_by_capability(string $capability, bool $activeOnly = true): array
{
    global $GLOBALS;
    
    if (!isset($GLOBALS['ai_model_service'])) {
        $db = $GLOBALS['container']->db ?? null;
        if (!$db) {
            return [];
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getByCapability($capability, $activeOnly);
}

/**
 * Récupérer le modèle par défaut
 * 
 * @return array|null
 */
function ai_get_default_model(): ?array
{
    global $GLOBALS;
    
    if (!isset($GLOBALS['ai_model_service'])) {
        $db = $GLOBALS['container']->db ?? null;
        if (!$db) {
            return null;
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getDefault();
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
        $db = $GLOBALS['container']->db ?? null;
        if (!$db) {
            return null;
        }
        $GLOBALS['ai_model_service'] = new AIModelService($db);
    }
    
    return $GLOBALS['ai_model_service']->getById($id);
}

/**
 * Vérifier si un modèle supporte une capacité
 * 
 * @param array $model Le modèle
 * @param string $capability La capacité à vérifier
 * @return bool
 */
function ai_model_has_capability(array $model, string $capability): bool
{
    return !empty($model['capabilities'][$capability]);
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
        'openai' => '🟢',
        'claude' => '🟣',
        // 'mistral' => '🔵'
    ];
    
    return $icons[$provider] ?? '🤖';
}

/**
 * Obtenir l'icône d'une capacité
 * 
 * @param string $capability
 * @return string Emoji
 */
function ai_capability_icon(string $capability): string
{
    $icons = [
        'text' => '📝',
        'vision' => '👁️',
        'audio' => '🔊',
        'video' => '🎥',
        'code' => '💻',
        'function_calling' => '⚙️',
        'long_context' => '📚',
        'multilingual' => '🌍'
    ];
    
    return $icons[$capability] ?? '✨';
}
