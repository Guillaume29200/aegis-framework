<?php
$docPage = 'modules/forum/doc-forum-admin.php';
$seo = [
    'title' => 'Forum — Administration · Documentation Aegis Framework',
    'desc'  => "Administrer le forum Aegis : catégories & sous-catégories, permissions voir/créer/répondre, modération & signalements, rôles & modérateurs, réglages généraux.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-forum-admin.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Administration du forum</h1>
    <p class="doc-lead">Tout se pilote depuis <code>/admin/forum</code> : structure des catégories, <strong>permissions fines</strong>, modération, rôles et réglages. Réservé aux <strong>admins, superadmins et modérateurs</strong> (les modérateurs avec des droits configurables).</p>
    <div class="doc-meta"><span class="doc-pill">/admin/forum</span><span class="doc-pill">Permissions</span><span class="doc-pill">Modération</span></div>

    <h2 id="fa-categories">Catégories &amp; sous-catégories</h2>
    <p>La structure du forum est une arborescence sur <strong>2 niveaux</strong> : des catégories racines, et des sous-catégories (champ <em>parent</em>). Chaque catégorie a un nom, un slug, une description, une icône, une couleur et un ordre.</p>
    <ul>
      <li><strong>Catégorie conteneur</strong> : décochez <em>« Autoriser la création de sujets »</em> (<code>allow_topics</code>) → elle ne sert qu'à regrouper des sous-catégories.</li>
      <li><strong>Visibilité</strong> (<code>is_visible</code>) : masque une catégorie du forum public sans la supprimer.</li>
      <li>Réorganisation par glisser-déposer (l'ordre est enregistré).</li>
    </ul>

    <h2 id="fa-permissions">Permissions : voir / créer / répondre</h2>
    <p>Chaque catégorie a <strong>trois</strong> permissions indépendantes, par niveau croissant <em>Invité → Membre → Modérateur → Admin</em> :</p>
    <table class="doc-table">
      <tr><th>Permission</th><th>Contrôle</th></tr>
      <tr><td><strong>Voir</strong> (<code>view_permission</code>)</td><td>Qui peut accéder à la catégorie et lire ses sujets</td></tr>
      <tr><td><strong>Créer un sujet</strong> (<code>post_permission</code>)</td><td>Qui peut ouvrir de nouveaux sujets</td></tr>
      <tr><td><strong>Répondre</strong> (<code>reply_permission</code>)</td><td>Qui peut répondre aux sujets existants</td></tr>
    </table>
    <p>« Créer » et « Répondre » sont <strong>distincts</strong> : la réponse peut être <strong>plus permissive</strong> que la création.</p>
    <div class="callout ok"><span class="i">💬</span><div><strong>Cas typique — une catégorie « Annonces » :</strong> <em>Voir = Tout le monde</em>, <em>Créer un sujet = Modérateurs+</em>, <em>Répondre = Membres</em>. Résultat : seuls les modérateurs publient des annonces, mais les membres peuvent réagir en dessous.</div></div>
    <div class="callout warn"><span class="i">⚠️</span><div>« Créer » et « Répondre » doivent rester <strong>≥ à la permission de voir</strong> (on ne peut pas écrire dans une catégorie qu'on ne voit pas) — l'interface ajuste automatiquement si besoin. Ces contrôles sont aussi <strong>vérifiés côté serveur</strong>, pas seulement dans l'UI.</div></div>

    <h2 id="fa-moderation">Modération &amp; signalements</h2>
    <p>Les membres signalent un message (spam, contenu inapproprié, harcèlement, autre). Les signalements arrivent dans <code>/admin/forum/reports</code> où vous pouvez les traiter ou les ignorer. Sur les sujets : <strong>épingler</strong>, <strong>verrouiller</strong>, <strong>mettre en vedette</strong>, <strong>déplacer</strong> vers une autre catégorie, ou <strong>supprimer</strong>. Les messages peuvent être supprimés individuellement.</p>

    <h2 id="fa-users">Utilisateurs, rôles &amp; modérateurs</h2>
    <p><code>/admin/forum/users</code> liste les membres avec leur activité. Vous pouvez changer le <strong>rôle</strong> (membre / modérateur / admin), le <strong>statut</strong> (actif / banni), et décerner ou retirer des récompenses.</p>
    <h3 id="fa-modperms">Permissions modérateur granulaires</h3>
    <p>Un modérateur n'a pas tous les droits d'un admin : <code>/admin/forum/moderator-permissions</code> active précisément ce qu'il peut faire :</p>
    <table class="doc-table">
      <tr><th>Groupe</th><th>Droits configurables</th></tr>
      <tr><td>Sujets</td><td>Épingler, verrouiller, mettre en vedette, déplacer, supprimer</td></tr>
      <tr><td>Messages</td><td>Supprimer des messages</td></tr>
      <tr><td>Signalements</td><td>Voir &amp; traiter les signalements</td></tr>
      <tr><td>Membres</td><td>Voir la liste / la fiche d'un membre, décerner des récompenses</td></tr>
    </table>

    <h2 id="fa-settings">Réglages généraux</h2>
    <p><code>/admin/forum/settings</code> regroupe : titre &amp; description du forum, couleurs du thème, pagination (sujets/messages par page), accès invités (voir / créer / rechercher), activation des votes / XP / karma et leurs valeurs, affichage de la liste des membres, réseaux sociaux affichés, CSS personnalisé, et tout le bloc <strong>SEO</strong>. Vous y gérez aussi le logo, la bannière et le fond du forum.</p>
    <h3 id="fa-tools">Outils &amp; maintenance</h3>
    <p>Un bouton <strong>« Recalculer les compteurs »</strong> resynchronise le nombre de sujets/messages des catégories et profils si jamais ils dérivent (imports, suppressions massives).</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
