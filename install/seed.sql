-- ============================================================
-- eSport-CMS V4 — Données par défaut (seed)
-- Exécuté par l'installeur après le schéma.
-- INSERT IGNORE : ré-exécutable sans écraser des données existantes.
-- ============================================================

-- Modèles IA par défaut
INSERT IGNORE INTO `ai_models`
  (`provider`, `model_name`, `display_name`, `capabilities`, `is_active`, `is_default`, `notes`)
VALUES
('openai', 'gpt-4-turbo-2024-04-09', 'GPT-4 Turbo (128k)', '{"code": true, "text": true, "vision": true, "function_calling": true}', 1, 0, 'Modèle le plus performant avec vision'),
('openai', 'gpt-4o', 'GPT-4o (Omni)', '{"code": true, "text": true, "audio": true, "vision": true}', 1, 0, 'Modèle multimodal avec audio'),
('openai', 'gpt-4o-mini', 'GPT-4o Mini', '{"code": true, "text": true, "vision": true}', 1, 0, 'Version économique de GPT-4o'),
('claude', 'claude-3-opus-20240229', 'Claude 3 Opus', '{"code": true, "text": true, "vision": true, "long_context": true}', 1, 0, 'Le plus intelligent, excellent pour tâches complexes'),
('claude', 'claude-3-5-sonnet-20241022', 'Claude 3.5 Sonnet', '{"code": true, "text": true, "vision": true, "long_context": true}', 1, 0, 'Équilibre performance/coût optimal'),
('claude', 'claude-3-haiku-20240307', 'Claude 3 Haiku', '{"code": true, "text": true, "vision": true}', 1, 0, 'Le plus rapide et économique'),
('claude', 'claude-3-sonnet-20240229', 'Claude 3 Sonnet', '{"code": true, "text": true, "vision": true}', 1, 0, 'Version stable de Sonnet'),
('mistral', 'mistral-large-latest', 'Mistral Large', '{"text":true,"code":true,"multilingual":true}', 1, 0, 'Le plus performant, excellent en code'),
('mistral', 'mistral-medium-3-latest', 'Mistral Medium', '{"text":true,"code":true}', 1, 0, 'Équilibre performance/coût'),
('mistral', 'mistral-small-3.1-latest', 'Mistral Small', '{"text":true,"code":true}', 1, 0, 'Rapide et économique'),
('mistral', 'open-mistral-7b', 'Open Mistral 7B', '{"text": true}', 1, 0, 'Modèle open source léger'),
('mistral', 'open-mixtral-8x7b', 'Open Mixtral 8x7B', '{"code": true, "text": true}', 1, 0, 'Modèle open source MoE'),
('mistral', 'codestral-25.01-latest', 'Codestral', '{"text":true,"code":true}', 1, 0, 'Spécialisé pour le code');
