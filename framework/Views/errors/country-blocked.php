<?php
/**
 * Page de refus géographique — rédigée en anglais.
 *
 * Le visiteur vient d'un pays qui n'est pas desservi : rien ne dit qu'il lise
 * le français, et c'est la seule page du site qu'il verra. L'anglais est le
 * plus petit dénominateur commun.
 *
 * Autonome : aucun thème, aucune requête, aucune ressource extérieure. Cette
 * page doit s'afficher même quand tout le reste est indisponible, et surtout
 * ne rien coûter — elle est servie précisément aux requêtes qu'on refuse, donc
 * potentiellement en très grand nombre.
 *
 * On ne nomme ni le mode de filtrage, ni la liste : dire « votre pays est sur
 * notre liste noire » renseigne un attaquant sur la règle à contourner.
 *
 * Variables attendues : $countryCode, $countryName.
 */
$code = isset($countryCode) ? strtoupper(substr((string) $countryCode, 0, 2)) : '';
$e    = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Le drapeau est composé de deux « indicateurs régionaux » Unicode : deux
// lettres transformées en points de code, sans aucune image à charger.
$drapeau = '';
if ($code !== '' && ctype_alpha($code)) {
    $drapeau = mb_chr(0x1F1E6 + ord($code[0]) - 65, 'UTF-8')
             . mb_chr(0x1F1E6 + ord($code[1]) - 65, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Access denied</title>
<style>
  :root { color-scheme: light dark; }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #0b1120; color: #e6ebf5;
    -webkit-font-smoothing: antialiased;
  }
  .card {
    width: 100%; max-width: 520px; text-align: center;
    background: #151c2c; border: 1px solid #263149;
    border-radius: 16px; padding: 40px 32px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, .45);
  }
  .flag { font-size: 52px; line-height: 1; margin-bottom: 18px; }
  h1 { margin: 0 0 14px; font-size: 22px; font-weight: 700; letter-spacing: -.01em; }
  p { margin: 0 0 14px; font-size: 15px; line-height: 1.65; color: #aab6cd; }
  p:last-child { margin-bottom: 0; }
  .country {
    display: inline-block; margin: 6px 0 18px;
    padding: 7px 14px; border-radius: 999px;
    background: #1e2740; border: 1px solid #2e3a55;
    font-size: 13.5px; font-weight: 600; color: #e6ebf5;
  }
  .note { font-size: 13px; color: #7f8ba4; }
  @media (prefers-color-scheme: light) {
    body { background: #eef1f7; color: #1e293b; }
    .card { background: #fff; border-color: #dfe4ee; box-shadow: 0 18px 44px rgba(15, 23, 42, .1); }
    p { color: #55617a; }
    .country { background: #f1f4fa; border-color: #dfe4ee; color: #1e293b; }
    .note { color: #8993a8; }
  }
</style>
</head>
<body>
  <main class="card">
    <?php if ($drapeau !== ''): ?><div class="flag"><?= $drapeau ?></div><?php else: ?><div class="flag">🌍</div><?php endif; ?>

    <h1>This site is not available in your country</h1>

    <?php if (!empty($countryName)): ?>
      <div class="country"><?= $e($countryName) ?><?= $code !== '' ? ' · ' . $e($code) : '' ?></div>
    <?php endif; ?>

    <p>Access to this website has been restricted for your region as a security measure. This is an automatic decision based on your network address — it is not personal.</p>

    <p class="note">If you believe this is a mistake, for example because you are using a VPN or a proxy, please contact the site administrator.</p>
  </main>
</body>
</html>
