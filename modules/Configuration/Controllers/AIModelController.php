<?php
/**
 * AIModelController - Gestion des modèles IA
 */

namespace Configuration\Controllers;

use Configuration\Services\AIModelService;
use Framework\Services\Database;
use Framework\Security\CSRFProtection;

class AIModelController
{
    private AIModelService $modelService;
    private CSRFProtection $csrf;
    
    public function __construct(Database $db, CSRFProtection $csrf)
    {
        $this->modelService = new AIModelService($db);
        $this->csrf = $csrf;
    }
    
    /**
     * PAGE PRINCIPALE - Liste des modèles
     */
    public function index(): void
    {
        $models = $this->modelService->getAll(false); // Tous les modèles
        $csrfToken = $this->csrf->generateToken();
        
        // Grouper par provider
        $modelsByProvider = [];
        foreach ($models as $model) {
            $provider = $model['provider'];
            if (!isset($modelsByProvider[$provider])) {
                $modelsByProvider[$provider] = [];
            }
            $modelsByProvider[$provider][] = $model;
        }
        
        require __DIR__ . '/../Views/admin/ai-models/index.php';
    }
    
    /**
     * PAGE AJOUT
     */
    public function create(): void
    {
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/ai-models/create.php';
    }
    
    /**
     * PAGE ÉDITION
     */
    public function edit(int $id): void
    {
        $model = $this->modelService->getById($id);
        
        if (!$model) {
            $_SESSION['error'] = 'Modèle introuvable';
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        $csrfToken = $this->csrf->generateToken();
        require __DIR__ . '/../Views/admin/ai-models/edit.php';
    }
    
    /**
     * TRAITER AJOUT
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalide';
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        $capabilities = $this->parseCapabilities($_POST);
        
        $data = [
            'provider' => $_POST['provider'],
            'model_name' => $_POST['model_name'],
            'display_name' => $_POST['display_name'],
            'capabilities' => $capabilities,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'notes' => $_POST['notes'] ?? null
        ];
        
        $id = $this->modelService->create($data);
        
        if ($id) {
            $_SESSION['success'] = 'Modèle créé avec succès !';
        } else {
            $_SESSION['error'] = 'Erreur lors de la création';
        }
        
        header('Location: /admin/configuration/ai-models');
        exit;
    }
    
    /**
     * TRAITER MODIFICATION
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalide';
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        $capabilities = $this->parseCapabilities($_POST);
        
        $data = [
            'provider' => $_POST['provider'],
            'model_name' => $_POST['model_name'],
            'display_name' => $_POST['display_name'],
            'capabilities' => $capabilities,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'notes' => $_POST['notes'] ?? null
        ];
        
        if ($this->modelService->update($id, $data)) {
            $_SESSION['success'] = 'Modèle mis à jour avec succès !';
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour';
        }
        
        header('Location: /admin/configuration/ai-models');
        exit;
    }
    
    /**
     * SUPPRIMER
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalide';
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if ($this->modelService->delete($id)) {
            $_SESSION['success'] = 'Modèle supprimé avec succès !';
        } else {
            $_SESSION['error'] = 'Erreur lors de la suppression';
        }
        
        header('Location: /admin/configuration/ai-models');
        exit;
    }
    
    /**
     * TOGGLE ACTIF
     */
    public function toggle(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalide';
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if ($this->modelService->toggleActive($id)) {
            $_SESSION['success'] = 'Statut modifié avec succès !';
        } else {
            $_SESSION['error'] = 'Erreur lors de la modification';
        }
        
        header('Location: /admin/configuration/ai-models');
        exit;
    }
    
    /**
     * DÉFINIR PAR DÉFAUT
     */
    public function setDefault(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalide';
            header('Location: /admin/configuration/ai-models');
            exit;
        }
        
        if ($this->modelService->setDefault($id)) {
            $_SESSION['success'] = 'Modèle défini par défaut !';
        } else {
            $_SESSION['error'] = 'Erreur lors de la modification';
        }
        
        header('Location: /admin/configuration/ai-models');
        exit;
    }
    
    /**
     * Parser capabilities depuis formulaire
     */
    private function parseCapabilities(array $data): array
    {
        return [
            'text' => isset($data['cap_text']),
            'vision' => isset($data['cap_vision']),
            'audio' => isset($data['cap_audio']),
            'video' => isset($data['cap_video']),
            'code' => isset($data['cap_code']),
            'function_calling' => isset($data['cap_function_calling']),
            'long_context' => isset($data['cap_long_context']),
            'multilingual' => isset($data['cap_multilingual'])
        ];
    }
}
