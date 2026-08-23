<?php
$docPage = 'framework/doc-security-center.php';
$seo = ['title' => 'Centre de sécurité & firewall — Documentation · Aegis Framework', 'desc' => "La sécurité d'Aegis Framework de bout en bout : garde CSRF globale, rate limiting, firewall applicatif, Security Center avec 27 détecteurs, scoring de menace, blocage automatique, listes blanche/noire et en-têtes durcis.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-security-center.php'];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Centre de sécurité &amp; firewall</h1>
    <p class="doc-lead">Aegis applique une <strong>défense en profondeur</strong> à chaque requête : CSRF, rate limiting, firewall applicatif, puis le <strong>Security Center</strong> qui score chaque IP et bloque automatiquement les comportements malveillants.</p>
    <div class="doc-meta"><span class="doc-pill">27 détecteurs</span><span class="doc-pill">Scoring par IP</span><span class="doc-pill">Blocage auto</span><span class="doc-pill">Argon2id</span></div>

    <h2 id="sc-intro">Vue d'ensemble</h2>
    <p>La sécurité n'est pas un module optionnel : elle est tissée dans le cœur. Chaque requête traverse un pipeline de gardes avant d'atteindre votre contrôleur.</p>
    <table class="doc-table">
      <tr><th>Couche</th><th>Implémentation</th></tr>
      <tr><td>CSRF</td><td><code>CSRFProtection</code> + garde globale du Router</td></tr>
      <tr><td>Rate limiting</td><td><code>RateLimiter</code> (par compte &amp; par IP)</td></tr>
      <tr><td>Firewall applicatif</td><td><code>SecurityFirewall</code> + <code>SecurityFirewallService</code></td></tr>
      <tr><td>Détection avancée</td><td><code>SecurityCenterService</code> + middleware <code>SecurityCenterDetector</code></td></tr>
      <tr><td>En-têtes</td><td><code>SecurityHeaders</code> + <code>.htaccess</code></td></tr>
      <tr><td>Sessions</td><td><code>SessionManager</code> (HttpOnly/Secure/SameSite)</td></tr>
      <tr><td>Mots de passe</td><td>Argon2id</td></tr>
    </table>
    <p>Configuration centralisée : <code>framework/config/security.php</code>.</p>

    <h2 id="sc-csrf">CSRF</h2>
    <p>Toutes les requêtes mutantes (POST/PUT/DELETE) sont validées par une <strong>garde globale</strong> au niveau du routeur. Les jetons sont gérés par pool et comparés avec <code>hash_equals</code> (anti timing-attack).</p>
    <pre><code>// Dans une vue
&lt;input type="hidden" name="csrf_token" value="&lt;?= $csrf-&gt;getToken() ?&gt;"&gt;

// Dans un contrôleur
if (!$this-&gt;csrf-&gt;validateToken($_POST['csrf_token'] ?? '')) {
    // 419 / rejet
}</code></pre>

    <h2 id="sc-ratelimit">Rate limiting</h2>
    <p><code>RateLimiter</code> protège les points sensibles (le login en premier) contre le brute-force. Le comptage se fait <strong>à la fois par compte et par IP</strong>, avec persistance en base (<code>rate_limits</code>, <code>rate_limit_blocks</code>).</p>

    <h2 id="sc-firewall">Firewall applicatif</h2>
    <p><code>SecurityFirewall</code> (middleware) s'appuie sur <code>SecurityFirewallService</code> pour : détecter le <strong>flood</strong>, repérer les <strong>chemins / User-Agents suspects</strong>, respecter une liste d'<strong>IP de confiance</strong> et écrire les blocages. C'est la première ligne, exécutée avant la détection avancée.</p>

    <h2 id="sc-center">Security Center</h2>
    <p>Le <code>SecurityCenterService</code> est une couche d'<strong>analyse au-dessus du firewall</strong>. Sa méthode pivot, <code>recordEvent($ip, $ruleKey, $details, $meta)</code> :</p>
    <ul>
      <li>journalise l'événement (<code>security_events</code>),</li>
      <li>cumule le <strong>score de menace</strong> de l'IP (<code>security_threat_scores</code>),</li>
      <li>déclenche le <strong>blocage automatique</strong> via le firewall si les seuils sont franchis.</li>
    </ul>
    <p>Le schéma est auto-créé (idempotent) via <code>ensureSchema()</code>.</p>

    <h2 id="sc-detectors">Les 27 détecteurs</h2>
    <p>Le catalogue (<code>security_rules</code>, 27 règles seedées) est <strong>éditable en admin</strong> : chaque règle a une <code>category</code>, un <code>label</code>, une <code>severity</code>, un <code>score</code> et un état <code>enabled</code>.</p>
    <table class="doc-table">
      <tr><th>Catégorie</th><th>Exemples de détecteurs</th></tr>
      <tr><td><code>web</code></td><td>XSS, injection SQL, path traversal, LFI/RFI</td></tr>
      <tr><td><code>scan</code></td><td>Sondes git/env/backup, fingerprinting CMS, scanners, panels tiers</td></tr>
      <tr><td><code>auth</code></td><td>Attaque CSRF, brute-force, flood d'authentification</td></tr>
      <tr><td><code>upload</code></td><td>Webshells, doubles extensions, extensions exécutables</td></tr>
      <tr><td><code>abuse</code></td><td>Flood, comportements abusifs (alimenté par le rate-limit)</td></tr>
      <tr><td><code>admin</code></td><td>Détournement de session, accès admin anormaux</td></tr>
    </table>
    <p><strong>Le pipeline :</strong> le middleware <code>SecurityCenterDetector</code> (après le firewall) appelle <code>inspectHttpRequest()</code> qui scanne la <strong>surface URL</strong> (chemin + query + User-Agent — <em>pas</em> le corps POST). Les détecteurs d'authentification sont signalés depuis <code>AuthController</code> ; les uploads sont inspectés sur <code>$_FILES</code> (<code>inspectUploadedFiles()</code>) ; le détournement de session est posé par <code>SessionManager</code>.</p>
    <div class="callout"><span class="i">ℹ️</span><div><strong>24 des 27 détecteurs</strong> sont actifs par défaut. Trois sont conservés au catalogue mais non déclenchés (<code>clickjacking</code> — couvert par l'en-tête <code>X-Frame-Options</code>, <code>account_enumeration</code>, <code>invalid_session</code>), et restent administrables.</div></div>

    <h2 id="sc-scoring">Scoring &amp; blocage</h2>
    <p>Chaque IP accumule un score. Le niveau en découle (<code>levelFromScore()</code>), et des seuils déclenchent le blocage :</p>
    <table class="doc-table">
      <tr><th>Score</th><th>Niveau</th></tr>
      <tr><td>0 – 25</td><td>Faible</td></tr>
      <tr><td>26 – 50</td><td>Moyen</td></tr>
      <tr><td>51 – 75</td><td>Élevé</td></tr>
      <tr><td>76 +</td><td>Critique</td></tr>
    </table>
    <table class="doc-table">
      <tr><th>Seuil (<code>security_settings</code>)</th><th>Effet (défaut)</th></tr>
      <tr><td><code>block_threshold</code></td><td>100 → blocage temporaire (<code>block_duration_hours</code>, déf. 24 h)</td></tr>
      <tr><td><code>ban_threshold</code></td><td>300 → blocage permanent</td></tr>
      <tr><td><code>auto_block</code> / <code>enabled</code></td><td>Activent le blocage automatique / le système</td></tr>
      <tr><td><code>log_retention_days</code></td><td>Rétention des événements</td></tr>
    </table>

    <h2 id="sc-lists">Listes blanche / noire</h2>
    <ul>
      <li><strong>Liste blanche</strong> (<code>security_ip_whitelist</code>) : IP jamais bloquées. Ajouter une IP la débloque automatiquement.</li>
      <li><strong>Liste noire</strong> : blocages permanents (<code>security_ip_blocks</code>).</li>
    </ul>

    <h2 id="sc-headers">En-têtes &amp; sessions</h2>
    <ul>
      <li><strong>En-têtes durcis</strong> (<code>SecurityHeaders</code> + <code>.htaccess</code>) : CSP, <code>X-Frame-Options</code>, <code>X-Content-Type-Options: nosniff</code>, <code>Referrer-Policy</code>.</li>
      <li><strong>Sessions</strong> (<code>SessionManager</code>) : cookies <code>HttpOnly</code>, <code>Secure</code>, <code>SameSite</code>, régénération de l'ID, détection de détournement.</li>
      <li><strong>Uploads</strong> : validation MIME + extension, dossier protégé (<code>.htaccess</code> : exécution PHP désactivée).</li>
    </ul>

    <h2 id="sc-admin">Page d'administration</h2>
    <p>Tout se pilote depuis <code>/admin/security</code> : activer/désactiver des détecteurs, ajuster scores et seuils, consulter les événements et scores par IP, gérer les listes blanche/noire. Le <strong>mode debug</strong> (Configuration → Système) force l'affichage des erreurs et de la Debug Bar quel que soit <code>APP_ENV</code>.</p>
    <div class="callout"><span class="i">🛡️</span><div>Pour écrire du code de module sécurisé (CSRF, échappement, requêtes préparées, contrôle d'accès), voir <a href="framework/doc-security.php">Module → Sécurité dans un module</a>.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
