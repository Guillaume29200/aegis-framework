<?php
$docPage = 'modules/forum/doc-forum-community.php';
$seo = [
    'title' => 'Forum — Communauté & engagement · Documentation Aegis Framework',
    'desc'  => "Réputation, XP et niveaux, récompenses et badges, profils enrichis, livre d'or, shoutbox, notifications et liste des membres du forum Aegis Framework.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-forum-community.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Communauté &amp; engagement</h1>
    <p class="doc-lead">Au-delà des discussions, le forum embarque une couche de <strong>gamification</strong> et de vie sociale : réputation, badges, profils riches, shoutbox et notifications — de quoi fidéliser vos membres.</p>
    <div class="doc-meta"><span class="doc-pill">XP &amp; niveaux</span><span class="doc-pill">Badges</span><span class="doc-pill">Shoutbox</span><span class="doc-pill">Notifications</span></div>

    <h2 id="fco-reputation">Réputation, XP &amp; niveaux</h2>
    <p>Les membres gagnent de l'<strong>XP</strong> par leurs actions, ce qui les fait monter en <strong>niveau</strong> (chaque niveau a un libellé et une couleur). Une barre de progression montre l'avancement vers le niveau suivant.</p>
    <table class="doc-table">
      <tr><th>Action</th><th>Effet</th></tr>
      <tr><td>Créer son 1<sup>er</sup> sujet / 1<sup>er</sup> message</td><td>Bonus de bienvenue</td></tr>
      <tr><td>Créer un sujet / répondre</td><td>XP standard</td></tr>
      <tr><td>Recevoir un vote ↑ / une réaction</td><td>XP pour l'auteur</td></tr>
      <tr><td>Recevoir un vote ↓</td><td>Malus (karma)</td></tr>
    </table>
    <div class="callout"><span class="i">⚙️</span><div>XP, votes et karma sont <strong>activables</strong> et leurs valeurs <strong>réglables</strong> dans <code>/admin/forum/settings</code>. Vous pouvez désactiver complètement la gamification si elle ne convient pas à votre communauté.</div></div>

    <h2 id="fco-rewards">Récompenses &amp; badges</h2>
    <p>Les <strong>récompenses</strong> (badges avec icône, nom, couleur, description) sont décernées <strong>automatiquement</strong> à l'atteinte de paliers, ou <strong>manuellement</strong> par un admin/modérateur depuis la fiche d'un membre. Elles s'affichent sur le profil et sous les messages de l'auteur.</p>

    <h2 id="fco-profiles">Profils membres</h2>
    <p>Chaque membre dispose d'un profil public (<code>/forum/u/pseudo</code>) : avatar, bannière, <strong>signature</strong> (affichée sous ses messages), <strong>liens réseaux sociaux</strong>, niveau, XP, récompenses, messages récents et favoris. Avatar &amp; bannière s'uploadent depuis le profil (images validées, voir sécurité).</p>
    <div class="callout"><span class="i">🔐</span><div>Signature (500 car. max) et liens sociaux sont <strong>filtrés et échappés</strong> ; les URL sociales doivent être en <code>http(s)://</code>, les protocoles dangereux sont rejetés.</div></div>

    <h2 id="fco-guestbook">Livre d'or</h2>
    <p>Sur chaque profil, les autres membres peuvent laisser un message dans le <strong>livre d'or</strong> (5 à 1000 caractères, sans HTML). L'auteur d'un message peut l'éditer ; le propriétaire du profil (ou un modérateur) peut le supprimer ou le masquer. Un <strong>anti-spam</strong> impose 1 minute entre deux entrées.</p>

    <h2 id="fco-shoutbox">Shoutbox</h2>
    <p>Un mini-chat en direct, disponible en <strong>widget</strong> (sidebar/footer) et en <strong>page dédiée</strong> (<code>/forum/shoutbox</code>). Entièrement configurable depuis <code>/admin/forum/shoutbox</code> :</p>
    <table class="doc-table">
      <tr><th>Réglage</th><th>Rôle</th></tr>
      <tr><td>Invités autorisés</td><td>Laisser ou non les non-connectés écrire</td></tr>
      <tr><td>Caractères max / message</td><td>Limite de longueur</td></tr>
      <tr><td>Rétention &amp; nb de messages</td><td>Durée de conservation, profondeur d'historique</td></tr>
      <tr><td>Anti-spam (délai, max/heure)</td><td>Empêche le flood (par IP &amp; par compte)</td></tr>
      <tr><td>Mots interdits</td><td>Filtre de contenu</td></tr>
    </table>
    <div class="callout"><span class="i">🛡️</span><div>Les messages sont <strong>échappés</strong> à l'affichage, l'anti-flood s'appuie sur un <strong>hash d'IP</strong>, et seuls les modérateurs peuvent supprimer un message.</div></div>

    <h2 id="fco-notifications">Notifications</h2>
    <p>Un membre est notifié quand on <strong>répond à son sujet</strong> ou qu'on le <strong>mentionne</strong> (<code>@pseudo</code>). Un compteur de non-lues s'affiche dans l'en-tête ; la page <code>/forum/notifications</code> les liste et les marque comme lues.</p>

    <h2 id="fco-members">Liste des membres</h2>
    <p>Si activée (<code>/admin/forum/settings</code>), la page <code>/forum/members</code> présente l'annuaire, triable par <strong>inscription</strong>, <strong>activité</strong> ou <strong>réputation</strong>, avec recherche par pseudo.</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
