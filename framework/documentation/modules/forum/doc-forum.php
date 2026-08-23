<?php
$docPage = 'modules/forum/doc-forum.php';
$seo = [
    'title' => 'Forum — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'  => "Le module Forum d'Aegis Framework : catégories, sujets, Markdown sur-mesure, réputation, récompenses, sondages, shoutbox, thèmes et SEO. Architecture, fonctionnalités et installation.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-forum.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Forum — Vue d'ensemble</h1>
    <p class="doc-lead">Le module <strong>Forum</strong> transforme votre site en une vraie communauté : catégories et sous-catégories, sujets &amp; réponses en <strong>Markdown sur-mesure</strong>, <strong>réputation / XP</strong>, <strong>récompenses</strong>, sondages, réactions, shoutbox, profils, thèmes importables et <strong>SEO complet</strong>. Le tout intégré au shell d'administration d'Aegis.</p>
    <div class="doc-meta"><span class="doc-pill">Communautaire</span><span class="doc-pill">Markdown + BBCode</span><span class="doc-pill">Réputation &amp; badges</span><span class="doc-pill">SEO natif</span></div>

    <h2 id="forum-intro">Qu'est-ce que le module Forum ?</h2>
    <p>C'est un forum de discussion complet, livré comme <strong>module officiel</strong> activable/désactivable depuis <code>Administration → Modules</code>. Une fois actif, il expose une partie <strong>publique</strong> (<code>/forum</code>) et une partie <strong>administration</strong> (<code>/admin/forum</code>) greffée dans le menu Aegis.</p>
    <div class="callout ok"><span class="i">🎯</span><div>En bref : vos visiteurs lisent &amp; écrivent sur <code>/forum</code> ; vous pilotez tout (catégories, permissions, modération, apparence, SEO) depuis <code>/admin/forum</code>.</div></div>

    <h2 id="forum-features">Fonctionnalités</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Ce que ça couvre</th></tr>
      <tr><td><strong>Structure</strong></td><td>Catégories &amp; sous-catégories, permissions par niveau (voir / créer / répondre), sujets épinglés, verrouillés, en vedette</td></tr>
      <tr><td><strong>Rédaction</strong></td><td>Markdown sur-mesure + extensions BBCode (spoiler, quote, YouTube, couleur, taille), mentions <code>@pseudo</code>, emojis</td></tr>
      <tr><td><strong>Engagement</strong></td><td>Réputation / XP / niveaux, récompenses &amp; badges, réactions, votes ↑/↓, sondages, favoris</td></tr>
      <tr><td><strong>Communauté</strong></td><td>Profils enrichis (avatar, bannière, signature, réseaux), livre d'or, shoutbox, notifications, liste des membres</td></tr>
      <tr><td><strong>Apparence</strong></td><td>Thèmes importables (ZIP), widgets sidebar/footer, liens de navigation, pages statiques</td></tr>
      <tr><td><strong>Visibilité</strong></td><td>SEO complet (meta, Open Graph, Twitter Cards, JSON-LD), sitemap.xml + robots.txt</td></tr>
      <tr><td><strong>Modération</strong></td><td>Signalements, permissions modérateur granulaires, anti-spam, gestion des rôles</td></tr>
    </table>

    <h2 id="forum-archi">Architecture</h2>
    <p>Le module suit la convention Aegis « 1 contrôleur + 1 service par responsabilité » :</p>
    <table class="doc-table">
      <tr><th>Composant</th><th>Rôle</th></tr>
      <tr><td><code>ForumController</code></td><td>Pages publiques (accueil, catégorie, sujet, recherche, profil, membres…)</td></tr>
      <tr><td><code>ApiController</code></td><td>Endpoints AJAX (réactions, votes, sondages, shoutbox, livre d'or, uploads…)</td></tr>
      <tr><td><code>AdminController</code></td><td>Toute l'administration (catégories, modération, réglages, thèmes, widgets…)</td></tr>
      <tr><td><code>ForumService</code></td><td>Accès données (requêtes paramétrées) &amp; logique métier</td></tr>
      <tr><td><code>ForumMarkdownService</code></td><td>Rendu Markdown/BBCode <strong>sécurisé</strong> (échappement + filtrage d'URL)</td></tr>
      <tr><td><code>ReputationService</code> / <code>RewardService</code></td><td>XP / niveaux / votes &amp; récompenses</td></tr>
      <tr><td><code>ForumThemeService</code> / <code>ForumUploadService</code></td><td>CSS dynamique du thème &amp; uploads d'images validés</td></tr>
    </table>

    <h2 id="forum-install">Installation &amp; activation</h2>
    <ol>
      <li>Le module est présent dans <code>/modules/Forum</code>.</li>
      <li><code>Administration → Modules</code> → bouton <strong>Activer</strong> : le schéma SQL (<code>database/install.sql</code>) crée les tables <code>forum_*</code> et insère les données par défaut (catégories, réglages, widgets, liens).</li>
      <li>Le menu <strong>Forum</strong> apparaît dans l'administration ; la partie publique est servie sur <code>/forum</code>.</li>
    </ol>
    <div class="callout"><span class="i">ℹ️</span><div>À la <strong>désactivation</strong>, <code>database/uninstall.sql</code> supprime les tables <code>forum_*</code>. Sauvegardez avant si vous voulez conserver les données.</div></div>

    <h2 id="forum-secu">Sécurité en bref</h2>
    <ul>
      <li><strong>CSRF</strong> validé sur toutes les actions (public + admin).</li>
      <li><strong>SQL</strong> 100 % en requêtes préparées.</li>
      <li><strong>Markdown</strong> échappé à l'entrée + filtrage des URL (<code>javascript:</code>, <code>data:</code>… bloqués).</li>
      <li><strong>Uploads</strong> validés (type MIME + <code>getimagesize</code>, extension forcée, dossier durci).</li>
      <li><strong>Anti-spam</strong> : délais entre publications, anti-flood shoutbox, signalements.</li>
    </ul>
    <p>Détails par thème dans les pages suivantes.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
