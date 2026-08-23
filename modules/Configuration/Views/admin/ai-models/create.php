<?php
/**
 * Création d'un modèle IA — UI maison
 * Variables : $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = 'Nouveau modèle IA';
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration/ai-models') ?>">Modèles IA</a><span>/</span><span>Nouveau</span></div>
    <h1>➕ Nouveau modèle IA</h1>
    <p>Connectez un modèle d'un provider (OpenAI, Claude, Mistral…).</p>
</div>

<form method="POST" action="<?= u('/admin/configuration/ai-models') ?>">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">

    <div class="ui-grid cols-2" style="align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">🪪 Informations</div>
            <div class="ui-card-body">
                <div style="margin-bottom:16px">
                    <label class="form-label">Provider *</label>
                    <select name="provider" class="form-select" required>
                        <option value="">Sélectionner…</option>
                        <option value="openai">🤖 OpenAI</option>
                        <option value="claude">🧠 Claude</option>
                        <option value="mistral">🌬️ Mistral</option>
                        <option value="gemini">💎 Gemini</option>
                        <option value="groq">⚡ Groq</option>
                        <option value="ollama">🦙 Ollama (local)</option>
                    </select>
                </div>
                <div style="margin-bottom:16px">
                    <label class="form-label">Nom affiché *</label>
                    <input type="text" name="display_name" class="form-control" placeholder="GPT-4o" required>
                </div>
                <div style="margin-bottom:16px">
                    <label class="form-label">Nom technique *</label>
                    <input type="text" name="model_name" class="form-control" placeholder="gpt-4o" required>
                    <p class="form-text">Identifiant exact du modèle chez le provider.</p>
                </div>
                <div style="margin-bottom:16px">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Note interne (optionnel)…"></textarea>
                </div>
                <hr style="border:0;border-top:1px solid var(--border);margin:18px 0">
                <label class="form-check" style="margin-bottom:10px"><input type="checkbox" class="form-check-input" name="is_active" checked> <span>✅ Modèle actif</span></label>
                <label class="form-check"><input type="checkbox" class="form-check-input" name="is_default"> <span>⭐ Définir par défaut</span></label>
            </div>
        </div>

        <div class="ui-card">
            <div class="ui-card-head">💡 Repères</div>
            <div class="ui-card-body">
                <p class="u-muted" style="font-size:13px;margin-top:0">Où trouver l'identifiant technique exact d'un modèle :</p>
                <ul style="font-size:13px;padding-left:18px;margin:0 0 16px">
                    <li>🤖 OpenAI — <a href="https://platform.openai.com/docs/models" target="_blank" rel="noopener">platform.openai.com/docs/models</a></li>
                    <li>🧠 Claude — <a href="https://docs.claude.com/en/docs/about-claude/models" target="_blank" rel="noopener">docs.claude.com — modèles</a></li>
                    <li>🌬️ Mistral — <a href="https://docs.mistral.ai/getting-started/models/" target="_blank" rel="noopener">docs.mistral.ai — modèles</a></li>
                    <li>💎 Gemini — <a href="https://ai.google.dev/gemini-api/docs/models" target="_blank" rel="noopener">ai.google.dev — modèles</a></li>
                    <li>⚡ Groq — <a href="https://console.groq.com/docs/models" target="_blank" rel="noopener">console.groq.com — modèles</a></li>
                </ul>
                <hr style="border:0;border-top:1px solid var(--border);margin:16px 0">
                <p class="u-muted" style="font-size:13px;margin:0">⭐ <strong>Par défaut</strong> est propre à chaque provider : cocher cette case ici ne change rien au défaut des autres fournisseurs (OpenAI/Claude/Mistral ont chacun le leur).</p>
            </div>
        </div>
    </div>

    <div class="u-flex" style="justify-content:flex-end;gap:10px;margin-top:18px">
        <a class="ui-btn" href="<?= u('/admin/configuration/ai-models') ?>">Annuler</a>
        <button type="submit" class="ui-btn primary">💾 Créer le modèle</button>
    </div>
</form>

<?php admin_footer(); ?>
