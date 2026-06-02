<?php
/**
 * Shell Admin — Aegis Framework V4 (nouvelle UX/UI, sans dépendance externe)
 *
 * Emplacement unique du thème d'administration. Les pages ne l'incluent jamais
 * directement : elles passent par les helpers admin_header() / admin_footer().
 *
 * - Disposition commutable sidebar / topbar (panneau de contrôle)
 * - Thème clair / sombre / auto · plein écran · accent personnalisable
 * - Menu 100% modulaire (AdminMenuService agrège les module.json actifs)
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');

$basePath = BASE_URL; // compat anciennes vues

// ── Menu modulaire (déclaré dans chaque module.json) ──────────────────────────
$menuService = new \Framework\Services\AdminMenuService($GLOBALS['moduleManager'] ?? null);
$adminMenu   = $menuService->build();

// ── Chemin courant (pour l'état actif) ────────────────────────────────────────
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (BASE_URL !== '' && str_starts_with($currentPath, BASE_URL)) {
    $currentPath = substr($currentPath, strlen(BASE_URL)) ?: '/';
}

// ── Utilisateur courant ───────────────────────────────────────────────────────
$admUsername = $_SESSION['username'] ?? ($currentUser['username'] ?? 'Admin');
$admRole     = $_SESSION['role'] ?? ($currentUser['role'] ?? 'admin');
$admInitial  = strtoupper(mb_substr($admUsername, 0, 1));

// ── Helpers de rendu du menu (guardés contre redéclaration) ───────────────────
if (!function_exists('adm_render_sidebar_item')) {
    function adm_render_sidebar_item(array $item, string $path): void
    {
        $active   = \Framework\Services\AdminMenuService::isActive($item, $path);
        $hasChild = !empty($item['children']);
        $icon     = htmlspecialchars($item['icon']);
        $label    = htmlspecialchars($item['label']);

        if ($hasChild) {
            echo '<li class="adm-nav-item' . ($active ? ' active open' : '') . '">';
            echo '<a class="adm-nav-link" data-toggle-submenu href="javascript:;">';
            echo '<span class="adm-nav-icon">' . $icon . '</span>';
            echo '<span class="adm-nav-label">' . $label . '</span>';
            echo '<span class="adm-nav-caret">▾</span></a>';
            echo '<ul class="adm-submenu">';
            foreach ($item['children'] as $child) {
                if (!empty($child['children'])) {
                    // Sous-groupe : en-tête de section + ses liens
                    echo '<li class="adm-nav-section">' . htmlspecialchars($child['icon']) . ' ' . htmlspecialchars($child['label']) . '</li>';
                    foreach ($child['children'] as $gc) {
                        $gActive = \Framework\Services\AdminMenuService::isActive($gc, $path);
                        echo '<li class="adm-nav-item' . ($gActive ? ' active' : '') . '">';
                        echo '<a class="adm-nav-link" href="' . u($gc['url'] ?? '#') . '">';
                        echo '<span class="adm-nav-icon">' . htmlspecialchars($gc['icon']) . '</span>';
                        echo '<span class="adm-nav-label">' . htmlspecialchars($gc['label']) . '</span></a></li>';
                    }
                    continue;
                }
                $cActive = \Framework\Services\AdminMenuService::isActive($child, $path);
                echo '<li class="adm-nav-item' . ($cActive ? ' active' : '') . '">';
                echo '<a class="adm-nav-link" href="' . u($child['url'] ?? '#') . '">';
                echo '<span class="adm-nav-icon">' . htmlspecialchars($child['icon']) . '</span>';
                echo '<span class="adm-nav-label">' . htmlspecialchars($child['label']) . '</span></a></li>';
            }
            echo '</ul></li>';
        } else {
            echo '<li class="adm-nav-item' . ($active ? ' active' : '') . '">';
            echo '<a class="adm-nav-link" href="' . u($item['url'] ?? '#') . '">';
            echo '<span class="adm-nav-icon">' . $icon . '</span>';
            echo '<span class="adm-nav-label">' . $label . '</span>';
            if (!empty($item['badge'])) {
                echo '<span class="adm-nav-badge">' . htmlspecialchars((string)$item['badge']) . '</span>';
            }
            echo '</a></li>';
        }
    }
}
if (!function_exists('adm_render_topnav_item')) {
    function adm_render_topnav_item(array $item, string $path): void
    {
        $active   = \Framework\Services\AdminMenuService::isActive($item, $path);
        $hasChild = !empty($item['children']);
        $icon     = htmlspecialchars($item['icon']);
        $label    = htmlspecialchars($item['label']);

        echo '<div class="adm-topnav-item' . ($active ? ' active' : '') . '">';
        if ($hasChild) {
            // Mega-menu opt-in : uniquement si le module le demande explicitement
            // ("mega": true dans son module.json). Pas de déclenchement automatique.
            $mega = !empty($item['mega']) ? ' mega' : '';
            echo '<span class="adm-topnav-link">' . $icon . ' ' . $label . ' <small>▾</small></span>';
            echo '<ul class="adm-topnav-sub' . $mega . '">';
            foreach ($item['children'] as $child) {
                if (!empty($child['children'])) {
                    // Sous-groupe : colonne de section dans le mega-menu
                    echo '<li class="adm-mega-col"><span class="adm-mega-head">'
                        . htmlspecialchars($child['icon']) . ' ' . htmlspecialchars($child['label']) . '</span>';
                    echo '<ul class="adm-mega-list">';
                    foreach ($child['children'] as $gc) {
                        echo '<li><a href="' . u($gc['url'] ?? '#') . '">'
                            . htmlspecialchars($gc['icon']) . ' ' . htmlspecialchars($gc['label']) . '</a></li>';
                    }
                    echo '</ul></li>';
                } else {
                    echo '<li><a href="' . u($child['url'] ?? '#') . '">'
                        . htmlspecialchars($child['icon']) . ' ' . htmlspecialchars($child['label']) . '</a></li>';
                }
            }
            echo '</ul>';
        } else {
            echo '<a class="adm-topnav-link" href="' . u($item['url'] ?? '#') . '">' . $icon . ' ' . $label . '</a>';
        }
        echo '</div>';
    }
}
?>
<!doctype html>
<html lang="fr" data-theme="light" data-layout="sidebar" data-sidebar="expanded">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Administration') ?></title>

    <!-- Pré-application des préférences (anti-FOUC) -->
    <script>
    (function () {
        var r = document.documentElement, g = function (k, d) { try { return localStorage.getItem(k) || d; } catch (e) { return d; } };
        var theme = g('adm.theme', 'light'), layout = g('adm.layout', 'sidebar'),
            accent = g('adm.accent', 'indigo'), sidebar = g('adm.sidebar', 'expanded');
        var eff = theme === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme;
        r.setAttribute('data-theme', eff); r.setAttribute('data-theme-mode', theme);
        r.setAttribute('data-layout', layout); r.setAttribute('data-sidebar', sidebar);
        var A = { indigo:['#6366f1','#4f46e5','99, 102, 241'], violet:['#8b5cf6','#7c3aed','139, 92, 246'],
            blue:['#3b82f6','#2563eb','59, 130, 246'], emerald:['#10b981','#059669','16, 185, 129'],
            rose:['#f43f5e','#e11d48','244, 63, 94'], amber:['#f59e0b','#d97706','245, 158, 11'],
            cyan:['#06b6d4','#0891b2','6, 182, 212'] };
        var c = A[accent]; if (!c) { var h = accent; c = [h, h, '99,102,241']; }
        r.style.setProperty('--accent', c[0]); r.style.setProperty('--accent-hover', c[1]);
        r.style.setProperty('--accent-rgb', c[2]); r.style.setProperty('--accent-soft', 'rgba(' + c[2] + ', .12)');
    })();
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- UI maison (aucune dépendance externe) -->
    <link href="<?= u('/framework/assets/css/admin/ui.css') ?>" rel="stylesheet">
    <link href="<?= u('/framework/assets/css/admin/compat.css') ?>" rel="stylesheet">
</head>
<body>
<?php if (is_dir(ROOT_PATH . '/install')): ?>
<div class="adm-install-warn" id="adm-install-warn">
    <span>⚠️ Le dossier <code>/install</code> est toujours présent — supprimez-le pour sécuriser votre site.</span>
    <button class="adm-install-x" data-action="dismiss-install" title="Masquer pour cette session">✕</button>
</div>
<?php endif; ?>
<?php
// Bandeau d'information : mode maintenance actif (lecture directe du réglage).
$admMaintenanceOn = false;
try {
    $admDb = $GLOBALS['db'] ?? null;
    if ($admDb instanceof \Framework\Services\Database) {
        $admRow = $admDb->queryOne("SELECT param_value FROM settings WHERE param_key = 'maintenance_mode'");
        $admMaintenanceOn = $admRow && (string)$admRow['param_value'] === '1';
    }
} catch (\Throwable $e) { /* silencieux */ }
if ($admMaintenanceOn): ?>
<div class="adm-maint-warn" id="adm-maint-warn">
    <span>🚧 Le <strong>mode maintenance</strong> est activé — le site est inaccessible au public. <a href="<?= u('/admin/configuration') ?>">Désactiver</a></span>
</div>
<?php endif; ?>
<div class="adm">

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="adm-sidebar">
        <div class="adm-brand">
            <span class="adm-brand-logo">⚡</span>
            <span class="adm-brand-text">Aegis Framework<small>Administration</small></span>
        </div>
        <nav class="adm-nav">
            <ul style="list-style:none;margin:0;padding:0">
                <?php foreach ($adminMenu as $item) adm_render_sidebar_item($item, $currentPath); ?>
            </ul>
        </nav>
    </aside>

    <!-- ═══════════════ HEADER ═══════════════ -->
    <header class="adm-header">
        <button class="adm-icon-btn adm-burger" data-action="mobnav-open" title="Menu">☰</button>
        <button class="adm-icon-btn" data-action="sidebar-toggle" title="Replier le menu">⇆</button>

        <!-- Nav horizontale (mode topbar) -->
        <nav class="adm-topnav">
            <?php foreach ($adminMenu as $item) adm_render_topnav_item($item, $currentPath); ?>
        </nav>

        <span class="adm-header-title"><?= htmlspecialchars($pageTitle ?? 'Administration') ?></span>
        <span class="adm-header-spacer"></span>

        <div class="adm-header-actions">
            <button class="adm-icon-btn" data-action="theme-toggle" title="Thème clair/sombre">🌓</button>
            <button class="adm-icon-btn" data-action="fullscreen" title="Plein écran"><span class="adm-fs-icon">⛶</span></button>

            <div class="adm-user">
                <button class="adm-user-trigger" data-action="user-toggle">
                    <span class="adm-avatar"><?= htmlspecialchars($admInitial) ?></span>
                    <span class="adm-user-meta">
                        <b><?= htmlspecialchars($admUsername) ?></b>
                        <span><?= htmlspecialchars(ucfirst((string)$admRole)) ?></span>
                    </span>
                </button>
                <div class="adm-user-menu">
                    <a href="<?= u('/member/dashboard') ?>">👤 Espace membre</a>
                    <a href="<?= u('/admin/configuration') ?>">⚙️ Réglages</a>
                    <a href="javascript:;" data-action="panel-open">🎨 Apparence</a>
                    <div class="adm-menu-divider"></div>
                    <a href="<?= u('/auth/logout') ?>" data-no-turbonav>🚪 Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════════ MAIN ═══════════════ -->
    <main class="adm-main">
        <div class="adm-content" id="admin-content">
<?php // ↑ Le contenu de la page suit, fermé par admin_footer() ?>
