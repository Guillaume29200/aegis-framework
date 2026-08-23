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
$siblingModels = $siblingModels ?? [];
$providerLabels = ['openai' => '🤖 OpenAI', 'claude' => '🧠 Claude', 'mistral' => '🌬️ Mistral', 'gemini' => '💎 Gemini', 'groq' => '⚡ Groq', 'ollama' => '🦙 Ollama (local)'];
$docsLinks = [
    'openai'  => 'https://platform.openai.com/docs/models',
    'claude'  => 'https://docs.claude.com/en/docs/about-claude/models',
    'mistral' => 'https://docs.mistral.ai/getting-started/models/',
    'gemini'  => 'https://ai.google.dev/gemini-api/docs/models',
    'groq'    => 'https://console.groq.com/docs/models',
    'ollama'  => 'https://ollama.com/library',
];
?>

<div class="adm-page-head">
    <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration/ai-models') ?>">Modèles IA</a><span>/</span><span><?= $h($model['display_name'] ?? '') ?></span></div>
    <h1>✏️ <?= $h($model['display_name'] ?? 'Modèle') ?></h1>
    <p>Modifiez les informations de ce modèle.</p>
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
                        <option value="gemini"  <?= $provider === 'gemini' ? 'selected' : '' ?>>💎 Gemini</option>
                        <option value="groq"    <?= $provider === 'groq' ? 'selected' : '' ?>>⚡ Groq</option>
                        <option value="ollama"  <?= $provider === 'ollama' ? 'selected' : '' ?>>🦙 Ollama (local)</option>
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
                <div style="margin-bottom:16px">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= $h($model['notes'] ?? '') ?></textarea>
                </div>
                <hr style="border:0;border-top:1px solid var(--border);margin:18px 0">
                <label class="form-check" style="margin-bottom:10px"><input type="checkbox" class="form-check-input" name="is_active" <?= !empty($model['is_active']) ? 'checked' : '' ?>> <span>✅ Modèle actif</span></label>
                <label class="form-check"><input type="checkbox" class="form-check-input" name="is_default" <?= !empty($model['is_default']) ? 'checked' : '' ?>> <span>⭐ Modèle par défaut</span></label>
            </div>
        </div>

        <div class="ui-card">
            <div class="ui-card-head"><?= $providerLabels[$provider] ?? '🔌 ' . $h(ucfirst($provider)) ?> — autres modèles</div>
            <div class="ui-card-body">
                <?php if (count($siblingModels) <= 1): ?>
                    <p class="u-muted" style="font-size:13px;margin:0">Aucun autre modèle enregistré pour ce provider.</p>
                <?php else: ?>
                    <ul style="list-style:none;padding:0;margin:0 0 16px">
                        <?php foreach ($siblingModels as $sm): ?>
                            <li style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
                                <span>
                                    <?php if ((int)$sm['id'] === (int)($model['id'] ?? 0)): ?><strong><?= $h($sm['display_name']) ?></strong> <span class="u-muted">(celui-ci)</span>
                                    <?php else: ?><a href="<?= u('/admin/configuration/ai-models/' . (int)$sm['id'] . '/edit') ?>"><?= $h($sm['display_name']) ?></a><?php endif; ?>
                                </span>
                                <span>
                                    <?php if (!empty($sm['is_default'])): ?><span class="ui-badge accent">⭐</span><?php endif; ?>
                                    <span class="ui-badge <?= !empty($sm['is_active']) ? 'green' : 'amber' ?>" style="margin-left:4px"><?= !empty($sm['is_active']) ? 'actif' : 'inactif' ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <hr style="border:0;border-top:1px solid var(--border);margin:16px 0">
                <p class="u-muted" style="font-size:13px;margin:0 0 8px">⭐ Un seul défaut possible par provider — le cocher ici retire automatiquement le défaut des autres modèles <?= $h($providerLabels[$provider] ?? $provider) ?> ci-dessus, sans toucher aux autres fournisseurs.</p>
                <?php if (isset($docsLinks[$provider])): ?>
                    <p class="u-muted" style="font-size:13px;margin:0"><a href="<?= $h($docsLinks[$provider]) ?>" target="_blank" rel="noopener">Identifiants exacts sur la doc <?= $h($providerLabels[$provider] ?? $provider) ?> →</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="u-flex" style="justify-content:flex-end;gap:10px;margin-top:18px">
        <a class="ui-btn" href="<?= u('/admin/configuration/ai-models') ?>">Annuler</a>
        <button type="submit" class="ui-btn primary">💾 Enregistrer</button>
    </div>
</form>

<?php admin_footer(); ?>
