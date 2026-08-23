<?php
/**
 * documentation/modules/gnh/doc-gnh-admin.php — Administration et réglages.
 */
$docPage = 'modules/gnh/doc-gnh-admin.php';
$seo = [
    'title'     => 'GameNodeHosting — Administration & réglages · Documentation Aegis Framework',
    'desc'      => "Piloter GameNodeHosting : tableau de bord, clients, commandes, locations, promotions, centre anti-fraude et les dix onglets de réglages.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh-admin.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Administration &amp; réglages</h1>
    <p class="doc-lead">Treize écrans pour piloter l'hébergement au quotidien : ce que vous vendez, à qui, ce qui tourne, ce qui rapporte — et ce qui sent mauvais.</p>
    <div class="doc-meta">
      <span class="doc-pill">13 écrans</span>
      <span class="doc-pill">10 onglets de réglages</span>
      <span class="doc-pill">Anti-fraude</span>
    </div>

    <h2 id="gnha-dashboard">Tableau de bord</h2>
    <p>Les chiffres du moment : commandes, locations actives, revenus, tickets ouverts.</p>
    <div class="callout"><span class="i">🩺</span><div>Chaque requête du tableau de bord est <strong>isolée dans son propre try/catch</strong>. C'est la première page consultée après activation, alors que certaines tables peuvent encore manquer : elle doit informer, <strong>jamais tomber en erreur</strong>.</div></div>

    <h2 id="gnha-clients">Clients</h2>
    <p>La liste des clients, puis la fiche de chacun : ses commandes, ses locations, ses tickets. Deux gestes commerciaux sont possibles directement depuis la fiche :</p>
    <table class="doc-table">
      <tr><th>Action</th><th>Effet</th><th>Usage</th></tr>
      <tr><td><strong>Offrir des slots</strong></td><td>Ajoute des slots à une location en cours</td><td>Geste commercial, compensation d'incident</td></tr>
      <tr><td><strong>Offrir des mois</strong></td><td>Repousse l'échéance d'une location</td><td>Dédommagement, fidélisation</td></tr>
    </table>

    <h2 id="gnha-orders">Commandes</h2>
    <p>Toutes les commandes, leur statut, leur détail ligne à ligne. Le statut se change à la main (remboursement, annulation), et une commande peut être supprimée.</p>
    <p>Statuts : <code>pending</code>, <code>paid</code>, <code>failed</code>, <code>cancelled</code>, <code>refunded</code>.</p>

    <h2 id="gnha-rentals">Locations</h2>
    <p>C'est l'écran d'exploitation : ce qui tourne réellement. Quatre actions par location :</p>
    <table class="doc-table">
      <tr><th>Action</th><th>Ce qu'elle fait</th></tr>
      <tr><td>Changer le statut</td><td><code>pending</code> · <code>provisioning</code> · <code>active</code> · <code>expired</code> · <code>suspended</code> · <code>cancelled</code></td></tr>
      <tr><td>Prolonger</td><td>Repousse l'échéance</td></tr>
      <tr><td>Rattacher un serveur</td><td>Associe la location à un serveur GameNodePanel existant — utile après une livraison manuelle ou une reprise de parc</td></tr>
      <tr><td>Supprimer</td><td>Retire la location</td></tr>
    </table>

    <h2 id="gnha-promotions">Promotions</h2>
    <p>Deux outils dans un seul écran.</p>
    <p><strong>Codes promo</strong> — trois types : pourcentage, montant fixe, gratuité. Chaque usage est tracé, ce qui permet de plafonner un code globalement ou par client, et de mesurer ce qu'il a réellement rapporté.</p>
    <p><strong>Liens partenaires</strong> — une adresse <code>/hosting/promo/{slug}</code> qui dépose le code puis renvoie sur le site. Chaque clic est enregistré, et un écran de statistiques montre clics et conversions par partenaire. Vous savez donc qui vous amène des clients, pas seulement qui vous amène du trafic.</p>

    <h2 id="gnha-fraud">Centre anti-fraude</h2>
    <p>Un dispositif de <strong>signalement</strong>, pas de blocage aveugle. Le principe : on enregistre un signalement dès qu'un signal existe, <strong>même sous le seuil</strong>, pour garder l'historique exploitable. Seul un score au-dessus du seuil bloque réellement.</p>
    <table class="doc-table">
      <tr><th>Signal</th><th>Points</th></tr>
      <tr><td>Compte créé il y a moins de quelques heures</td><td>20</td></tr>
      <tr><td>Compte créé le jour même de la commande</td><td>8</td></tr>
      <tr><td>Même IP utilisée par d'autres comptes dans la fenêtre de surveillance</td><td>35</td></tr>
      <tr><td>E-mail déjà impliqué dans une fraude confirmée</td><td>élevé</td></tr>
      <tr><td>Écart entre l'e-mail du compte et celui du payeur</td><td>variable</td></tr>
      <tr><td>Première commande du compte, montant élevé</td><td>variable</td></tr>
    </table>
    <p>Le seuil se règle (défaut 50, borné entre 1 et 100). Chaque signalement se consulte, s'approuve ou se rejette ; un compte se bloque et se débloque. Statuts : <code>pending</code>, <code>approved</code>, <code>rejected</code>.</p>
    <div class="callout"><span class="i">🌍</span><div>La géolocalisation passe par le service du framework : <strong>GameNodeHosting ne dépend pas du module Marketplace</strong>, dont les signaux ont pourtant inspiré ce dispositif.</div></div>

    <h2 id="gnha-settings">Les dix onglets de réglages</h2>
    <p>Chaque section a sa propre route de sauvegarde et répond en JSON — enregistrer un onglet ne recharge pas les neuf autres.</p>
    <table class="doc-table">
      <tr><th>Onglet</th><th>Contenu</th></tr>
      <tr><td>Général</td><td>Identité du site, devise, logo</td></tr>
      <tr><td>Social</td><td>Liens vers vos réseaux</td></tr>
      <tr><td>Locations</td><td>Règles de durée, renouvellement, échéances</td></tr>
      <tr><td>GamePanel</td><td><strong>Les autorisations du client</strong> : V.E.G.A, Marketplace, réinstallation</td></tr>
      <tr><td>Paramètres</td><td>Réglages techniques du module</td></tr>
      <tr><td>Marketing</td><td>Suppléments facturables — et l'avertissement sur leur absence d'effet technique</td></tr>
      <tr><td>Paiements</td><td>Passerelles découvertes, activation et clés</td></tr>
      <tr><td>SEO</td><td>Titre, description, image de partage</td></tr>
      <tr><td>E-mails</td><td>Les trois gabarits transactionnels, avec aperçu</td></tr>
      <tr><td>Anti-fraude</td><td>Seuil de blocage, comptes bloqués</td></tr>
    </table>
    <p>Deux écrans complètent les onglets : <strong>Thèmes</strong> (activation, téléversement, suppression, par surface) et <strong>Options du thème</strong>, dont le formulaire est construit d'après ce que le thème déclare.</p>

    <h2 id="gnha-perms">Permissions</h2>
    <p>Le module déclare trois permissions : <code>gamenodehosting.manage</code> (administration), <code>gamenodehosting.order</code> (commander) et <code>gamenodehosting.rent</code> (louer).</p>

    <div class="doc-foot">
      <span>GameNodeHosting · administration</span>
      <span><a href="modules/gnh/doc-gnh-tickets.php">Support intégré →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
