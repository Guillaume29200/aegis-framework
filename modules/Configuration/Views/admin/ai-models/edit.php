<?php
/**
 * Édition d'un modèle IA — UI maison
 * Variables : $model, $csrfToken
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$pageTitle = 'Éditer — ' . ($model['display_name'] ?? '');
admin_header($pageTitle);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$provider = $model['provider'] ?? '';
$capabilities = $model['capabilities'] ?? [];
if (is_string($capabilities)) { $capabilities = json_decode($capabilities, true) ?: []; }

$caps = [
    ['name' => 'cap_text', 'key' => 'text', 'emoji' => '📝', 'label' => 'Texte'],
    ['name' => 'cap_vision', 'key' => 'vision', 'emoji' => '👁️', 'label' => 'Vision'],
    ['name' => 'cap_audio', 'key' => 'audio', 'emoji' => '🔊', 'label' => 'Audio'],
    ['name' => 'cap_video', 'key' => 'video', 'emoji' => '🎥', 'label' => 'Vidéo'],
    ['name' => 'cap_code', 'key' => 'code', 'emoji' => '💻', 'label' => 'Code'],
    ['name' => 'cap_function_calling', 'key' => 'function_calling', 'emoji' => '⚙️', 'label' => 'Functions'],
    ['name' => 'cap_long_context', 'key' => 'long_context', 'emoji' => '📚', 'label' => 'Long contexte'],
    ['name' => 'cap_multilingual', 'key' => 'multilingual', 'emoji' => '🌍', 'label' => 'Multilingue'],
];
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration/ai-models') ?>">Modèles IA</a><span>/</span><span><?= $h($model['display_name'] ?? '') ?></span></div>
    <h1>✏️ <?= $h($model['display_name'] ?? 'Modèle') ?></h1>
    <p>Modifiez le modèle et ses capacités.</p>
</div>

<form method="POST" action="<?= u('/admin/configuration/ai-models/' . (int)($model['id'] ?? 0) . '/update') ?>">
    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">

    <div class="ui-grid cols-2" style="align-items:start">
        <div class="ui-card">
            <div class="ui-card-head">🪪 Informations</div>
            <div class="ui-card-body">
                <div style="margin-bottom:16px">
                    <label class="form-label">Provider *</label>
                    <select name="provider" class="form-select" required>
                        <option value="openai"  <?= $provider === 'openai' ? 'selected' : '' ?>>🤖 OpenAI</option>
                        <option value="claude"  <?= $provider === 'claude' ? 'selected' : '' ?>>🧠 Claude</option>
                        <option value="mistral" <?= $provider === 'mistral' ? 'selected' : '' ?>>🌬️ Mistral</option>
                        <option value="google"  <?= $provider === 'google' ? 'selected' : '' ?>>🔴 Google</option>
                    </select>
                </div>
                <div style="margin-bottom:16px">
                    <label class="form-label">Nom affiché *</label>
                    <input type="text" name="display_name" class="form-control" value="<?= $h($model['display_name'] ?? '') ?>" required>
                </div>
                <div style="margin-bottom:16px">
                    <label class="form-label">Nom technique *</label>
                    <input type="text" name="model_name" class="form-control" value="<?= $h($model['model_name'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= $h($model['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="ui-card">
            <div class="ui-card-head">✨ Capacités</div>
            <div class="ui-card-body">
                <div class="ui-grid cols-2" style="gap:8px">
                    <?php foreach ($caps as $c): $on = !empty($capabilities[$c['key']]); ?>
                        <label class="form-check" style="border:1px solid var(--border);border-radius:10px;padding:10px 12px">
                            <input type="checkbox" class="form-check-input" name="<?= $c['name'] ?>" <?= $on ? 'checked' : '' ?>>
                            <span><?= $c['emoji'] ?> <?= $c['label'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <hr style="border:0;border-top:1px solid var(--border);margin:18px 0">
                <label class="form-check" style="margin-bottom:10px"><input type="checkbox" class="form-check-input" name="is_active" <?= !empty($model['is_active']) ? 'checked' : '' ?>> <span>✅ Modèle actif</span></label>
                <label class="form-check"><input type="checkbox" class="form-check-input" name="is_default" <?= !empty($model['is_default']) ? 'checked' : '' ?>> <span>⭐ Modèle par défaut</span></label>
            </div>
        </div>
    </div>

    <div class="u-flex" style="justify-content:flex-end;gap:10px;margin-top:18px">
        <a class="ui-btn" href="<?= u('/admin/configuration/ai-models') ?>">Annuler</a>
        <button type="submit" class="ui-btn primary">💾 Enregistrer</button>
    </div>
</form>

<?php admin_footer(); ?>
