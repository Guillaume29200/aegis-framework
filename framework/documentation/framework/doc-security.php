<?php
$docPage = 'framework/doc-security.php';
$seo = ['title' => 'Sécurité dans un module — Documentation · GameNodePanel', 'desc' => "Écrire un module sûr sur Aegis : protection CSRF, échappement des sorties, requêtes préparées, contrôle d'accès et validation des entrées.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-security.php'];
require __DIR__ . '/../inc/head.php';
$c_csrf_form = <<<'HTML'
<form method="POST" action="<?= u('/admin/monmodule/store') ?>">
  <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
  <!-- … champs … -->
</form>
HTML;
$c_csrf_ctrl = <<<'PHP'
public function store(): void
{
    if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
        // requête rejetée
        http_response_code(419);
        return;
    }
    // … traitement …
}
PHP;
$c_escape = <<<'PHP'
<!-- ✅ Toujours échapper à l'affichage -->
<td><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></td>

<!-- ❌ Jamais ceci -->
<td><?= $item['title'] ?></td>
PHP;
$c_sql = <<<'PHP'
// ✅ Requête préparée (paramètre lié)
$row = $this->db->queryOne(
    "SELECT * FROM monmodule_items WHERE id = ?",
    [$id]
);

// ❌ Jamais de concaténation
// "SELECT * FROM items WHERE id = " . $_GET['id']   ← injection SQL
PHP;
$c_access = <<<'PHP'
private function requireAdmin(): void
{
    if (empty($_SESSION['logged_in']) ||
        !in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'], true)) {
        redirect('/auth/login');
    }
}
PHP;
$c_valid = <<<'PHP'
$id    = (int) ($_POST['id'] ?? 0);                 // cast strict
$title = trim((string) ($_POST['title'] ?? ''));    // normalisation
if ($title === '' || mb_strlen($title) > 190) {
    // rejet : valeur invalide
}
// Identifiants techniques : liste blanche par regex
if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) { /* refus */ }
PHP;
?>
    <h1>Sécurité dans un module</h1>
    <p class="doc-lead">La sécurité d'Aegis est intégrée au socle — mais votre module doit respecter quelques règles d'or pour rester sûr. Les voici, avec les bons réflexes.</p>

    <h2 id="s-csrf">CSRF</h2>
    <p>Toute action mutante (POST/PUT/DELETE) doit porter un jeton CSRF. Insérez-le dans vos formulaires et validez-le côté contrôleur.</p>
    <pre><code><?= $h($c_csrf_form) ?></code></pre>
    <pre><code><?= $h($c_csrf_ctrl) ?></code></pre>
    <div class="callout"><span class="i">🛡️</span><div>Un garde CSRF global existe au niveau du routeur ; la validation explicite dans le contrôleur reste la bonne pratique pour les actions sensibles.</div></div>

    <h2 id="s-escape">Échappement des sorties</h2>
    <p>Toute donnée affichée dans une vue doit être échappée avec <code>htmlspecialchars</code> — sans exception. C'est la première barrière contre le XSS.</p>
    <pre><code><?= $h($c_escape) ?></code></pre>

    <h2 id="s-sql">Requêtes préparées</h2>
    <p>Aucune valeur ne doit être concaténée dans une requête SQL. Utilisez toujours des paramètres liés via le service <code>Database</code>.</p>
    <pre><code><?= $h($c_sql) ?></code></pre>

    <h2 id="s-access">Contrôle d'accès</h2>
    <p>Vérifiez les droits au début de chaque action d'administration. Ne vous reposez pas uniquement sur le masquage du menu.</p>
    <pre><code><?= $h($c_access) ?></code></pre>

    <h2 id="s-validation">Validation des entrées</h2>
    <p>Castez et bornez systématiquement les entrées. Pour les identifiants techniques (noms de table, hôtes…), préférez une <strong>liste blanche par expression régulière</strong>.</p>
    <pre><code><?= $h($c_valid) ?></code></pre>
    <div class="callout ok"><span class="i">✅</span><div>Réflexe Aegis : <strong>échapper en sortie, préparer en SQL, valider en entrée, CSRF sur chaque action</strong>. Le reste (rate limiting, firewall, Security Center) est fourni par le framework.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
