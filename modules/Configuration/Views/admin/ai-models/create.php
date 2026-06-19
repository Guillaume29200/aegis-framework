<?php
/**
 * Création d'un modèle IA — UI maison
 * Variables : $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = 'Nouveau modèle IA';
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$caps = [
    ['name' => 'cap_text', 'emoji' => '📝', 'label' => 'Texte'],
    ['name' => 'cap_vision', 'emoji' => '👁️', 'label' => 'Vision'],
    ['name' => 'cap_audio', 'emoji' => '🔊', 'label' => 'Audio'],
    ['name' => 'cap_video', 'emoji' => '🎥', 'label' => 'Vidéo'],
    ['name' => 'cap_code', 'emoji' => '💻', 'label' => 'Code'],
    ['name' => 'cap_function_calling', 'emoji' => '⚙️', 'label' => 'Functions'],
    ['name' => 'cap_long_context', 'emoji' => '📚', 'label' => 'Long contexte'],
    ['name' => 'cap_multilingual', 'emoji' => '🌍', 'label' => 'Multilingue'],
];
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
                        <option value="google">🔴 Google</option>
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
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Note interne (optionnel)…"></textarea>
                </div>
            </div>
        </div>

        <div class="ui-card">
            <div class="ui-card-head">✨ Capacités</div>
            <div class="ui-card-body">
                <div class="ui-grid cols-2" style="gap:8px">
                    <?php foreach ($caps as $c): ?>
                        <label class="form-check" style="border:1px solid var(--border);border-radius:10px;padding:10px 12px">
                            <input type="checkbox" class="form-check-input" name="<?= $c['name'] ?>" <?= $c['name'] === 'cap_text' ? 'checked' : '' ?>>
                            <span><?= $c['emoji'] ?> <?= $c['label'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <hr style="border:0;border-top:1px solid var(--border);margin:18px 0">
                <label class="form-check" style="margin-bottom:10px"><input type="checkbox" class="form-check-input" name="is_active" checked> <span>✅ Modèle actif</span></label>
                <label class="form-check"><input type="checkbox" class="form-check-input" name="is_default"> <span>⭐ Définir par défaut</span></label>
            </div>
        </div>
    </div>

    <div class="u-flex" style="justify-content:flex-end;gap:10px;margin-top:18px">
        <a class="ui-btn" href="<?= u('/admin/configuration/ai-models') ?>">Annuler</a>
        <button type="submit" class="ui-btn primary">💾 Créer le modèle</button>
    </div>
</form>

<?php admin_footer(); ?>
