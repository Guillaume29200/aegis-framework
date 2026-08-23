<?php
/**
 * documentation/doc-prerequis.php — Prérequis techniques (hébergement web + VPS de jeux).
 * Repris de l'ancienne doc GSH et porté au design de la doc actuelle.
 */
$docPage = 'modules/gnp/doc-prerequis.php';
$seo = [
    'title'     => 'Prérequis & hébergement — Documentation GameNodePanel',
    'desc'      => "Tout ce qu'il faut avant d'installer GameNodePanel : hébergement web PHP 8.5+ pour le panel, VPS/dédié Debian ou Ubuntu pour les serveurs de jeux, architecture en deux parties, OS supportés et extensions PHP.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-prerequis.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Prérequis &amp; hébergement</h1>
    <p class="doc-lead">Tout ce dont vous avez besoin avant de commencer. Spoiler&nbsp;: c'est moins que vous ne le pensez.</p>
    <div class="doc-meta">
      <span class="doc-pill">Étape 1 / 5</span>
      <span class="doc-pill">PHP ≥ 8.5</span>
      <span class="doc-pill">VPS Debian / Ubuntu</span>
      <span class="doc-pill">Accessible à tous</span>
    </div>

    <div class="callout">
      <span class="i">💡</span>
      <div><strong>Aucune connaissance Linux requise.</strong> GameNodePanel gère tout de manière automatique&nbsp;: installation des dépendances, configuration FTP, déploiement des serveurs de jeux via SSH. Vous n'avez jamais besoin d'ouvrir un terminal pour l'utiliser au quotidien.</div>
    </div>

    <h2 id="hebergement">Hébergement web — pour GNP</h2>
    <p>GNP (et Aegis Framework) tourne sur un <strong>hébergement web classique</strong>. Pas besoin d'un VPS pour le panel lui-même&nbsp;: un mutualisé standard avec PHP 8.5 et MySQL fait parfaitement l'affaire.</p>
    <table class="doc-table">
      <tr><th>Élément</th><th>Exigence</th><th>Détail</th></tr>
      <tr><td>🐘 Langage</td><td><strong>PHP ≥ 8.5</strong></td><td>Version minimum obligatoire (testé sur 8.5.6). Les versions antérieures ne sont pas supportées (typage strict introduit en 8.5).</td></tr>
      <tr><td>🗃️ Base de données</td><td><strong>≈ 110 Mo</strong></td><td>Tables GNP : serveurs, jeux, logs VEGA, métriques O.D.I.N, Marketplace, historiques. MySQL 8.0+ ou MariaDB 10.6+.</td></tr>
      <tr><td>💾 Espace disque</td><td><strong>≈ 45 Mo</strong></td><td>Fichiers PHP, assets et configs côté hébergement web. Les fichiers des serveurs de jeux vivent sur le VPS, pas ici.</td></tr>
      <tr><td>🌐 Type d'hébergement</td><td>Standard</td><td>Mutualisé, VPS web ou dédié web — peu importe, du moment que PHP 8.5 et MySQL sont disponibles (o2switch, LWS, OVH…).</td></tr>
    </table>
    <div class="callout ok">
      <span class="i">✅</span>
      <div><strong>GNP ne nécessite PAS de VPS pour le panel.</strong> Un hébergement mutualisé standard avec PHP 8.5 et une base MySQL suffit pour faire tourner GNP et administrer tous vos serveurs de jeux.</div>
    </div>

    <h2 id="vps">Serveur de jeux — VPS ou dédié</h2>
    <p>Pour <em>héberger</em> vos serveurs de jeux, il vous faut un <strong>VPS ou serveur dédié Linux</strong>. C'est sur cette machine que tourneront CS2, Minecraft, ARK… — pas sur votre hébergement web.</p>
    <table class="doc-table">
      <tr><th>Élément</th><th>Exigence</th><th>Détail</th></tr>
      <tr><td>🐧 Système</td><td><strong>Debian 12+</strong></td><td>Debian 12 (Bookworm) minimum, Debian 13 (Trixie) disponible. Voir la section OS supportés.</td></tr>
      <tr><td>🔐 Accès</td><td><strong>SSH actif</strong></td><td>GNP se connecte via SSH pour installer et gérer les serveurs (port 22 par défaut, mot de passe ou clé).</td></tr>
      <tr><td>⚡ Config minimale</td><td><strong>2 vCPU · 4 Go RAM</strong></td><td>Pour 1–2 serveurs légers (CS 1.6, TF2…). Pour ARK / Rust / Minecraft moddé : 4+ vCPU et 8–16 Go recommandés.</td></tr>
      <tr><td>🌐 Réseau</td><td>Ports par jeu</td><td>Chaque serveur utilise un port UDP/TCP. GNP indique exactement quels ports ouvrir sur le pare-feu.</td></tr>
    </table>
    <div class="callout">
      <span class="i">💡</span>
      <div><strong>Plusieurs VPS possibles.</strong> GNP pilote autant de serveurs dédiés que voulu depuis un seul panel. Les joueurs se connectent directement aux VPS via leur IP — pas par votre hébergement web.</div>
    </div>

    <h2 id="architecture">Architecture en deux parties</h2>
    <p>GNP sépare clairement le <strong>panel d'administration</strong> (hébergement web) des <strong>serveurs de jeux</strong> (VPS Debian / Ubuntu). Les deux communiquent uniquement via <strong>SSH</strong>.</p>
    <table class="doc-table">
      <tr><th>🌐 Hébergement web</th><th>🖥️ VPS / Dédié</th></tr>
      <tr>
        <td>
          Aegis Framework + GameNodePanel<br>
          PHP 8.5 + MySQL<br>
          Dashboards VEGA AI &amp; O.D.I.N<br>
          Marketplace &amp; Modrinth<br>
          <span class="doc-pill" style="margin-top:8px;display:inline-block">Mutualisé OK</span>
        </td>
        <td>
          Debian 11+ / Ubuntu 22.04+<br>
          Serveurs de jeux (CS2, MC…)<br>
          SteamCMD + ProFTPD (auto-installés)<br>
          Agent O.D.I.N Python · ports jeux ouverts<br>
          <span class="doc-pill" style="margin-top:8px;display:inline-block">VPS ou dédié requis</span>
        </td>
      </tr>
    </table>
    <div class="callout warn">
      <span class="i">⚠️</span>
      <div><strong>En résumé :</strong> le panel = hébergement web classique ; les serveurs de jeux = VPS séparé sous Debian / Ubuntu. Deux machines qui communiquent via SSH. Tout mettre sur un seul VPS est possible, mais les performances des jeux en pâtiront.</div>
    </div>

    <h2 id="os">OS supportés pour les VPS de jeux</h2>
    <p>GNP vise une <strong>compatibilité multi-OS</strong>. Le support repose sur une base commune pour les distributions <strong>APT</strong> (Debian/Ubuntu), avec un profil dédié par version. La liste ci-dessous correspond aux OS <strong>réellement reconnus &amp; testés</strong> par le module.</p>
    <table class="doc-table">
      <tr><th>OS</th><th>Statut</th><th>Détail</th></tr>
      <tr><td>🟢 Debian 11 (Bullseye)</td><td style="color:var(--ok);font-weight:700">✔ Supporté</td><td>Base APT · profil dédié</td></tr>
      <tr><td>🟢 Debian 12 (Bookworm)</td><td style="color:var(--ok);font-weight:700">✔ Supporté (recommandé)</td><td>Base APT · le plus éprouvé</td></tr>
      <tr><td>🟢 Debian 13 (Trixie)</td><td style="color:var(--ok);font-weight:700">✔ Supporté</td><td>Base APT · profil dédié</td></tr>
      <tr><td>🟢 Ubuntu 22.04 LTS</td><td style="color:var(--ok);font-weight:700">✔ Supporté</td><td>Base APT · profil dédié</td></tr>
      <tr><td>🟢 Ubuntu 24.04 LTS</td><td style="color:var(--ok);font-weight:700">✔ Supporté</td><td>Base APT · profil dédié</td></tr>
      <tr><td>🟢 Ubuntu 26.04</td><td style="color:var(--ok);font-weight:700">✔ Supporté</td><td>Base APT · profil dédié</td></tr>
      <tr><td>🪟 Windows Server (2022 / 2025)</td><td style="color:var(--warn);font-weight:700">🧪 À l'étude</td><td>Travaux exploratoires en cours — proposé <strong>selon la demande</strong> (voir ci-dessous)</td></tr>
      <tr><td>⚪ Distributions non-APT (CentOS / RHEL, Arch…)</td><td style="color:var(--tx3);font-weight:700">✗ Non pris en charge</td><td>Le provisioning automatisé cible les distributions APT</td></tr>
    </table>
    <div class="callout"><span class="i">🔄</span><div><strong>La liste s'agrandit régulièrement.</strong> Chaque nouvel OS « reconnu » (nouvelle version de Debian/Ubuntu, etc.) est <strong>testé en amont</strong> avant d'être proposé dans une mise à jour — l'objectif est d'étendre la compatibilité tout en garantissant que l'installation des serveurs de jeu fonctionne de bout en bout.</div></div>
    <div class="callout"><span class="i">🪟</span><div><strong>Windows : en réflexion.</strong> Une compatibilité Windows Server est <strong>à l'étude</strong> et dépendra de la <strong>demande générale</strong>. Elle n'est pas encore finalisée : si ce support vous intéresse, faites-le savoir — cela aide à prioriser.</div></div>

    <h2 id="extensions">Extensions PHP requises</h2>
    <p>Ces extensions s'appliquent à votre hébergement web. La plupart sont activées par défaut chez les hébergeurs standard.</p>
    <table class="doc-table">
      <tr><th>Extension</th><th>Statut</th><th>Utilisation dans GNP</th></tr>
      <tr><td><code>PDO</code> + <code>pdo_mysql</code></td><td>✅ Obligatoire</td><td>Toutes les requêtes BDD — requêtes préparées anti-injection SQL</td></tr>
      <tr><td><code>openssl</code></td><td>✅ Obligatoire</td><td>Chiffrement AES-256 des mots de passe SSH stockés en BDD</td></tr>
      <tr><td><code>curl</code></td><td>✅ Obligatoire</td><td>API Modrinth, providers IA VEGA (Claude/GPT/Mistral), géolocalisation O.D.I.N</td></tr>
      <tr><td><code>exec</code></td><td>✅ Obligatoire</td><td>Exécution de commandes SSH distantes — ne doit pas être désactivé chez l'hébergeur</td></tr>
      <tr><td><code>zip</code> (ZipArchive)</td><td>⚠️ Recommandé</td><td>Analyse des archives .zip du Marketplace (mode IA)</td></tr>
      <tr><td><code>json</code></td><td>✅ Inclus PHP 8+</td><td>Manifests Marketplace, configs VEGA JSON — activé par défaut</td></tr>
    </table>
    <div class="callout">
      <span class="i">💡</span>
      <div>Vérifiez vos extensions via <code>php -m</code> ou un <code>phpinfo()</code>. Chez o2switch, LWS et OVH Web Hosting, elles sont activées par défaut.</div>
    </div>

    <h2 id="checklist">Checklist avant de commencer</h2>
    <ol class="steps">
      <li><strong>Hébergement web PHP 8.5+ avec MySQL</strong> — BDD ≈ 110 Mo · ≈ 45 Mo d'espace · <code>pdo_mysql</code> + <code>curl</code> + <code>openssl</code> + <code>exec</code> activés. Un mutualisé standard suffit.</li>
      <li><strong>Aegis Framework installé et fonctionnel</strong> — GNP est un module d'Aegis : la plateforme de base doit être installée avant d'activer GNP.</li>
      <li><strong>Au moins un VPS Debian / Ubuntu pour les jeux</strong> — SSH accessible depuis l'extérieur. GNP installera automatiquement SteamCMD, ProFTPD et le reste.</li>
      <li><strong>Vous êtes prêt à démarrer</strong> — aucune connaissance Linux requise pour la suite. GNP prend en charge tout ce qui se passe sur le VPS.</li>
    </ol>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
