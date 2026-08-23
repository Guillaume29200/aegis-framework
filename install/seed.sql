-- ============================================================
-- Aegis Framework V4 — Données par défaut (seed)
-- Exécuté par l'installeur après le schéma.
-- INSERT IGNORE : ré-exécutable sans écraser des données existantes.
-- ============================================================

-- Modèles IA par défaut (OpenAI, Claude, Mistral, Gemini, Groq, Ollama)
-- NOTE: identifiants à jour au mieux des connaissances au moment de la rédaction ;
-- vérifier sur la console de chaque provider si un modèle ne répond plus (nom retiré/renommé côté provider).
-- Ollama (local) ne nécessite pas de clé API mais un serveur Ollama accessible (voir Configuration → IA).
INSERT IGNORE INTO `ai_models`
  (`provider`, `model_name`, `display_name`, `is_active`, `is_default`, `notes`)
VALUES
('openai', 'gpt-5', 'GPT-5', 1, 0, 'Modèle phare OpenAI. Identifiant à vérifier sur platform.openai.com si l''appel échoue.'),
('openai', 'gpt-5-mini', 'GPT-5 Mini', 1, 0, 'Version économique de GPT-5.'),
('openai', 'gpt-4o', 'GPT-4o (Omni)', 1, 0, 'Modèle multimodal (texte/vision/audio).'),
('openai', 'gpt-4o-mini', 'GPT-4o Mini', 1, 0, 'Version économique de GPT-4o.'),
('claude', 'claude-sonnet-5', 'Claude Sonnet 5', 1, 0, 'Modèle Claude le plus récent, équilibre performance/coût.'),
('claude', 'claude-opus-4-8', 'Claude Opus 4.8', 1, 0, 'Le plus intelligent de la gamme Claude, pour tâches complexes.'),
('claude', 'claude-haiku-4-5-20251001', 'Claude Haiku 4.5', 1, 0, 'Le plus rapide et économique de la gamme Claude.'),
('mistral', 'mistral-large-latest', 'Mistral Large', 1, 0, 'Le plus performant, excellent en code.'),
('mistral', 'mistral-medium-3-latest', 'Mistral Medium', 1, 0, 'Équilibre performance/coût.'),
('mistral', 'mistral-small-3.1-latest', 'Mistral Small', 1, 0, 'Rapide et économique.'),
('mistral', 'codestral-25.01-latest', 'Codestral', 1, 0, 'Spécialisé pour le code.'),
('gemini', 'gemini-2.5-pro', 'Gemini 2.5 Pro', 1, 0, 'Le plus performant de la gamme Gemini.'),
('gemini', 'gemini-2.5-flash', 'Gemini 2.5 Flash', 1, 0, 'Équilibre performance/coût, rapide.'),
('gemini', 'gemini-2.0-flash', 'Gemini 2.0 Flash', 1, 0, 'Version précédente, toujours disponible.'),
('gemini', 'gemini-1.5-pro', 'Gemini 1.5 Pro (legacy)', 0, 0, 'Modèle legacy, désactivé par défaut.'),
('groq', 'llama-3.3-70b-versatile', 'Llama 3.3 70B Versatile', 1, 0, 'Modèle généraliste hébergé par Groq (inférence très rapide).'),
('groq', 'llama-3.1-8b-instant', 'Llama 3.1 8B Instant', 1, 0, 'Version légère et très rapide.'),
('groq', 'gemma2-9b-it', 'Gemma 2 9B IT', 1, 0, 'Modèle Google hébergé par Groq.'),
('groq', 'mixtral-8x7b-32768', 'Mixtral 8x7B (legacy)', 0, 0, 'Modèle legacy, désactivé par défaut.'),
('ollama', 'llama3.2', 'Llama 3.2 (local)', 1, 0, 'Nécessite Ollama installé localement — ollama pull llama3.2'),
('ollama', 'llama3.1', 'Llama 3.1 (local)', 1, 0, 'Nécessite Ollama installé localement — ollama pull llama3.1'),
('ollama', 'mistral', 'Mistral 7B (local)', 1, 0, 'Nécessite Ollama installé localement — ollama pull mistral'),
('ollama', 'gemma2', 'Gemma 2 (local)', 1, 0, 'Nécessite Ollama installé localement — ollama pull gemma2');

-- ============================================================
-- Réglages de session (Configuration → Sessions). Valeurs par défaut.
-- Optionnel : le framework retombe sur les défauts de security.php si absent,
-- mais on les seede pour une config explicite dès l'installation.
-- ============================================================
INSERT IGNORE INTO `settings` (`param_key`, `param_value`, `param_type`) VALUES
('session_idle_logout',        '1',      'bool'),
('session_idle_minutes',       '120',    'int'),
('session_warn_seconds',       '60',     'int'),
('session_ip_binding',         'subnet', 'string'),
('session_regenerate_minutes', '5',      'int'),
('session_remember_enabled',   '1',      'bool'),
('session_remember_days',      '30',     'int');
