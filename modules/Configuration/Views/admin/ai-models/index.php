<?php
/**
 * Gestion des modèles IA — liste (UI maison)
 * Variables : $models[], $modelsByProvider[], $csrfToken
 */
if (!defined('ESPORT_CMS')) die('Access denied');

$pageTitle = $pageTitle ?? 'Modèles IA';
admin_header($pageTitle);

$models = $models ?? [];
$modelsByProvider = $modelsByProvider ?? [];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$flashOk  = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$flashErr = $_SESSION['error'] ?? null;   unset($_SESSION['error']);

$providerEmoji = ['openai' => '🟢', 'claude' => '🟣', 'anthropic' => '🟣', 'mistral' => '🟠', 'google' => '🔴', 'gemini' => '🔴'];
$activeCount  = count(array_filter($models, fn($m) => !empty($m['is_active'])));
?>

<div class="adm-page-head u-between" style="flex-wrap:wrap;gap:12px">
    <div>
        <div class="adm-breadcrumb"><a href="<?= u('/admin/dashboard') ?>">Admin</a><span>/</span><a href="<?= u('/admin/configuration') ?>">Configuration</a><span>/</span><span>Modèles IA</span></div>
        <h1>🤖 Modèles IA</h1>
        <p>Connectez et gérez les modèles d'intelligence artificielle de votre CMS.</p>
    </div>
    <a class="ui-btn primary" href="<?= u('/admin/configuration/ai-models/create') ?>">➕ Nouveau modèle</a>
</div>

<?php if ($flashOk): ?><div class="ui-card" style="border-color:var(--green-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--green)">✅ <?= $h($flashOk) ?></div></div><?php endif; ?>
<?php if ($flashErr): ?><div class="ui-card" style="border-color:var(--red-soft);margin-bottom:16px"><div class="ui-card-body" style="color:var(--red)">❌ <?= $h($flashErr) ?></div></div><?php endif; ?>

<div class="ui-grid cols-3" style="margin-bottom:18px">
    <div class="ui-card tone-accent"><div class="ui-kpi"><div class="ui-kpi-icon">🤖</div><div><p class="ui-kpi-label">Modèles</p><div class="ui-kpi-value"><?= count($models) ?></div></div></div></div>
    <div class="ui-card tone-green"><div class="ui-kpi"><div class="ui-kpi-icon">✅</div><div><p class="ui-kpi-label">Actifs</p><div class="ui-kpi-value"><?= $activeCount ?></div></div></div></div>
    <div class="ui-card tone-blue"><div class="ui-kpi"><div class="ui-kpi-icon">🏢</div><div><p class="ui-kpi-label">Providers</p><div class="ui-kpi-value"><?= count($modelsByProvider) ?></div></div></div></div>
</div>

<?php if (empty($models)): ?>
    <div class="ui-card"><div class="ui-empty"><div class="ui-empty-icon">🤖</div>Aucun modèle IA. Ajoutez votre premier modèle pour connecter le CMS à OpenAI, Claude, Mistral, Google…</div></div>
<?php else: ?>
    <?php foreach ($modelsByProvider as $provider => $list): ?>
        <div class="ui-card u-mt">
            <div class="ui-card-head"><?= $providerEmoji[strtolower($provider)] ?? '🔌' ?> <?= $h(ucfirst($provider)) ?> <span class="ui-card-actions ui-badge"><?= count($list) ?> modèle(s)</span></div>
            <div class="ui-card-body" style="padding:0">
                <div style="overflow-x:auto">
                    <table class="ui-table">
                        <thead><tr><th>Modèle</th><th>Capacités</th><th>Statut</th><th class="u-right">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($list as $model):
                            $caps = $model['capabilities'] ?? [];
                            if (is_string($caps)) { $caps = json_decode($caps, true) ?: []; }
                            $capList = [];
                            foreach ((array)$caps as $k => $v) { if ($v === true || $v === 1 || $v === '1') $capList[] = $k; }
                            $id = (int)$model['id']; ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= $h($model['display_name'] ?? '') ?>
                                        <?php if (!empty($model['is_default'])): ?><span class="ui-badge accent">⭐ Défaut</span><?php endif; ?>
                                    </div>
                                    <div class="u-muted" style="font-size:12px;font-family:monospace"><?= $h($model['model_name'] ?? '') ?></div>
                                </td>
                                <td>
                                    <?php if ($capList): foreach (array_slice($capList, 0, 4) as $c): ?><span class="ui-badge" style="margin:1px"><?= $h($c) ?></span><?php endforeach; else: ?><span class="u-muted">—</span><?php endif; ?>
                                </td>
                                <td><span class="ui-badge <?= !empty($model['is_active']) ? 'green' : 'amber' ?>"><?= !empty($model['is_active']) ? '🟢 Actif' : '⏸️ Inactif' ?></span></td>
                                <td class="u-right u-nowrap">
                                    <a class="ui-btn sm" href="<?= u('/admin/configuration/ai-models/' . $id . '/edit') ?>">✏️</a>
                                    <?php if (empty($model['is_default'])): ?>
                                    <form method="post" action="<?= u('/admin/configuration/ai-models/' . $id . '/default') ?>" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                                        <button class="ui-btn sm" type="submit" title="Définir par défaut">⭐</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= u('/admin/configuration/ai-models/' . $id . '/toggle') ?>" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                                        <button class="ui-btn sm" type="submit" title="Activer/Désactiver"><?= !empty($model['is_active']) ? '⏸️' : '▶️' ?></button>
                                    </form>
                                    <form method="post" action="<?= u('/admin/configuration/ai-models/' . $id . '/delete') ?>" style="display:inline" onsubmit="return confirm('Supprimer ce modèle ?')">
                                        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken ?? '') ?>">
                                        <button class="ui-btn sm danger" type="submit" title="Supprimer">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php admin_footer(); ?>
