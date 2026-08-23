<?php
$docPage = 'modules/gnp/doc-vega.php';
$seo = ['title' => 'VEGA AI LogGuard (technique) — Documentation · GameNodePanel', 'desc' => "Fonctionnement technique de VEGA : collecte SSH des logs, analyse IA multi-provider, 63 patterns de détection, actions automatiques (AutoBan), cloisonnement par serveur.", 'canonical' => 'https://gamenodepanel.com/documentation/doc-vega.php'];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>VEGA AI LogGuard <span style="font-weight:500;color:var(--tx3);font-size:1rem">· technique</span></h1>
    <p class="doc-lead">VEGA est le module d'analyse IA des logs de serveurs de jeu. Cette page détaille son fonctionnement interne. Pour la présentation, voir <a href="../pages/vega.php">la page VEGA</a> du site.</p>

    <h2 id="vega-archi">Architecture</h2>
    <p>VEGA suit le pipeline : <strong>collecte → analyse IA → détection → action</strong>, le tout cloisonné par serveur (chaque serveur a ses logs, incidents et rapports). Les clés API des providers IA sont chiffrées (AES-256).</p>

    <h2 id="vega-collect">Collecte des logs</h2>
    <p>Les logs de chaque serveur sont récupérés <strong>via SSH</strong> (service de collecte dédié), en continu — sans agent lourd ni port à ouvrir. Seules les lignes pertinentes sont transmises à l'IA, pour maîtriser le volume.</p>

    <h2 id="vega-patterns">Patterns de détection</h2>
    <p>VEGA s'appuie sur une bibliothèque de <strong>63 patterns</strong> stockés en base, <strong>extensibles</strong> : triche, exploits, crashs répétés, spam, comportements suspects… Vous pouvez ajouter vos propres règles. La détection est contextuelle (l'IA comprend la ligne), pas un simple « mot-clé ».</p>

    <h2 id="vega-providers">Providers IA (multi-provider)</h2>
    <p>VEGA est compatible avec plusieurs fournisseurs et bascule automatiquement en cas d'indisponibilité ou de quota :</p>
    <table class="doc-table">
      <tr><th>Provider</th><th>Note</th></tr>
      <tr><td>Claude (Anthropic)</td><td>Recommandé</td></tr>
      <tr><td>GPT-4o (OpenAI)</td><td>—</td></tr>
      <tr><td>Mistral AI</td><td>—</td></tr>
      <tr><td>Llama 3</td><td>—</td></tr>
      <tr><td>Gemini</td><td>—</td></tr>
      <tr><td>Provider custom</td><td>Configurable</td></tr>
    </table>
    <div class="callout"><span class="i">🔄</span><div>Le <strong>fallback automatique</strong> garantit la continuité de l'analyse même si un provider tombe.</div></div>

    <h2 id="vega-actions">Actions automatiques</h2>
    <p>Sur détection, VEGA peut déclencher des actions <strong>configurables par jeu et par seuil de gravité</strong> :</p>
    <ul>
      <li>Kick / ban temporaire (<strong>AutoBan</strong> / AutoMod).</li>
      <li>Alerte administrateur.</li>
      <li>Rapport structuré : chronologie, logs bruts, analyse IA, actions effectuées, recommandations.</li>
    </ul>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
