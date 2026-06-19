<?php
/**
 * En-tête + styles partagés des pages d'authentification (autonome, sans Bootstrap).
 * Attend : $siteName, $logoImageUrl (optionnel), $authTitle (optionnel).
 */
if (!defined('AEGIS_FRAMEWORK')) die('Access denied');
$authTitle = $authTitle ?? 'Connexion';
$siteName = $siteName ?? 'Aegis Framework';
$faviconUrl = trim((string)($settings['favicon_url'] ?? ''));
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($authTitle . ' - ' . $siteName, ENT_QUOTES, 'UTF-8') ?></title>
    <?php if ($faviconUrl !== ''): ?><link rel="icon" href="<?= htmlspecialchars(u($faviconUrl), ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --au-accent:#6366f1; --au-accent-h:#4f46e5; --au-ink:#1e293b; --au-muted:#64748b; --au-line:#e4e9f2; --au-soft:#f4f6fb; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:"Inter",system-ui,-apple-system,"Segoe UI",sans-serif; background:var(--au-soft); color:var(--au-ink); }
        .auth-shell { min-height:100vh; display:grid; grid-template-columns:minmax(0,1.1fr) minmax(420px,.9fr); }
        .auth-visual { position:relative; min-height:100vh; background-size:cover; background-position:center; display:flex; align-items:flex-end; padding:clamp(28px,5vw,64px); }
        .auth-visual-content { position:relative; z-index:1; max-width:620px; color:#fff; }
        .auth-kicker { display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border:1px solid rgba(255,255,255,.22); border-radius:999px; background:rgba(255,255,255,.12); backdrop-filter:blur(10px); font-size:13px; font-weight:500; margin-bottom:18px; }
        .auth-visual-title { font-size:clamp(34px,4.5vw,58px); line-height:1.04; font-weight:700; margin:0 0 14px; }
        .auth-visual-text { max-width:540px; margin:0; color:rgba(255,255,255,.82); font-size:16px; line-height:1.65; }
        .auth-panel { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:clamp(22px,4vw,56px); background:#fff; }
        .auth-card { width:min(100%,440px); }
        .auth-logo { width:68px; height:68px; border-radius:18px; border:1px solid var(--au-line); background:#fff; display:flex; align-items:center; justify-content:center; margin-bottom:22px; box-shadow:0 14px 34px rgba(15,23,42,.08); overflow:hidden; }
        .auth-logo img { max-width:52px; max-height:52px; object-fit:contain; }
        .auth-heading { margin-bottom:24px; }
        .auth-heading h1 { font-size:27px; line-height:1.2; margin:0 0 6px; font-weight:700; }
        .auth-heading p { margin:0; color:var(--au-muted); }
        .auth-form { display:flex; flex-direction:column; gap:16px; }
        .f-field label { display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#27324a; }
        .f-field input { width:100%; min-height:46px; padding:0 14px; border:1.5px solid var(--au-line); border-radius:10px; font-size:14.5px; font-family:inherit; color:var(--au-ink); outline:none; transition:border-color .15s, box-shadow .15s; }
        .f-field input:focus { border-color:var(--au-accent); box-shadow:0 0 0 4px rgba(99,102,241,.12); }
        .f-field input.error { border-color:#ef4444; }
        .f-field .error-message { color:#b91c1c; font-size:12px; margin-top:5px; }
        .f-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media (max-width:520px){ .f-grid2 { grid-template-columns:1fr; } }
        .f-row { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .f-switch { display:inline-flex; align-items:center; gap:9px; cursor:pointer; font-size:13.5px; color:var(--au-muted); user-select:none; }
        .f-switch input { display:none; }
        .f-switch i { width:40px; height:23px; border-radius:30px; background:var(--au-line); position:relative; transition:.2s; flex-shrink:0; }
        .f-switch i::before { content:""; position:absolute; width:17px; height:17px; border-radius:50%; background:#fff; top:3px; left:3px; transition:.2s; box-shadow:0 1px 2px rgba(0,0,0,.3); }
        .f-switch input:checked + i { background:var(--au-accent); }
        .f-switch input:checked + i::before { transform:translateX(17px); }
        .f-btn { min-height:48px; border:0; border-radius:10px; background:var(--au-accent); color:#fff; font-weight:700; font-size:15px; cursor:pointer; font-family:inherit; transition:background .15s; }
        .f-btn:hover { background:var(--au-accent-h); }
        .f-btn:disabled { opacity:.7; cursor:default; }
        .f-btn .btn-loading { display:none; } .f-btn.loading .btn-text { display:none; } .f-btn.loading .btn-loading { display:inline; }
        .auth-alert { border-radius:10px; padding:12px 14px; margin-bottom:14px; font-size:13.5px; font-weight:500; }
        .auth-alert.err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .auth-alert.ok { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .auth-link { color:var(--au-accent); font-weight:600; text-decoration:none; font-size:13.5px; }
        .auth-link:hover { text-decoration:underline; }
        .auth-foot { text-align:center; margin:6px 0 0; color:var(--au-muted); font-size:13.5px; }
        .auth-help { font-size:12.5px; color:var(--au-muted); margin:4px 0 0; }
        @media (max-width:991.98px) {
            .auth-shell { grid-template-columns:1fr; }
            .auth-visual { min-height:220px; padding:24px; }
            .auth-visual-text { display:none; }
            .auth-panel { min-height:auto; padding:32px 20px 48px; }
        }
    </style>
</head>
