<?php
/**
 * documentation/modules/gne/doc-gne-boutique.php — Boutique et espace membre.
 */
$docPage = 'modules/gne/doc-gne-boutique.php';
$seo = [
    'title'     => 'GameNodeEsport — Boutique & espace membre · Documentation Aegis Framework',
    'desc'      => "La boutique de GameNodeEsport : catalogue et déclinaisons, panier, commande, paiement PayPal, transporteurs et suivi, statistiques — et l'espace membre avec profil et configuration PC.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gne/doc-gne-boutique.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Boutique &amp; espace membre</h1>
    <p class="doc-lead">Vendre les maillots de la team et laisser chaque membre tenir son profil : deux fonctions qui transforment un site vitrine en site vivant.</p>
    <div class="doc-meta">
      <span class="doc-pill">Déclinaisons de taille</span>
      <span class="doc-pill">PayPal</span>
      <span class="doc-pill">Suivi transporteur</span>
    </div>

    <h2 id="gneb-interrupteur">La boutique s'allume et s'éteint</h2>
    <div class="callout ok"><span class="i">🔌</span><div>
      Un <strong>seul interrupteur</strong> coupe toute la boutique. Désactivée, elle disparaît entièrement côté visiteur — menu, panier, pages — et ses routes publiques répondent <strong>404</strong>. Une équipe qui ne vend rien n'a pas à voir un panier vide traîner sur son site.
    </div></div>

    <h2 id="gneb-catalogue">Le catalogue</h2>
    <p>Des <strong>catégories</strong> et des <strong>produits</strong>, chaque produit pouvant porter des <strong>déclinaisons de taille</strong> avec leur propre stock. C'est le minimum vital pour du textile — un maillot en S, M, L et XL n'est pas quatre produits.</p>
    <p>Deux pages publiques : la boutique et la fiche produit.</p>

    <h2 id="gneb-panier">Le panier</h2>
    <p>Stocké en session. Il ne retient que des <strong>identifiants de déclinaison et des quantités</strong>.</p>
    <div class="callout"><span class="i">💶</span><div>Les prix et les noms sont <strong>relus en base à chaque affichage</strong>. Un panier oublié pendant deux semaines ne facturera donc jamais un ancien prix — le client paie le tarif du jour, pas celui du jour où il a cliqué.</div></div>

    <h2 id="gneb-commande">La commande</h2>
    <p>Le tunnel mène du panier au paiement, puis à une page de confirmation adressée par la <strong>référence</strong> de commande. Les retours et annulations de paiement ont chacun leur route.</p>
    <p>L'administration suit chaque commande, change son statut, et renseigne le <strong>numéro de suivi</strong>.</p>

    <h3 id="gneb-livraison">Les transporteurs</h3>
    <p>Chaque mode de livraison porte le <strong>gabarit de son URL de suivi</strong>. Le module transforme donc un numéro en lien cliquable <strong>sans coder la liste des transporteurs en dur</strong> : ajouter Mondial Relay se fait dans un formulaire, pas dans le code.</p>

    <h3 id="gneb-paypal">Le paiement</h3>
    <p>PayPal, partagé avec le module de dons.</p>
    <div class="callout"><span class="i">🔗</span><div>Les identifiants PayPal de la boutique <strong>retombent sur ceux des dons</strong> quand ils ne sont pas renseignés. Un site avec un seul compte PayPal n'a rien à configurer deux fois.</div></div>

    <h2 id="gneb-stats">Les statistiques</h2>
    <p>Un tableau de bord dédié à la boutique : ventes, produits, commandes.</p>
    <div class="callout warn"><span class="i">🧮</span><div>
      Une règle traverse tout le calcul : le chiffre d'affaires <strong>ignore les commandes annulées et remboursées</strong>. Compter de l'argent rendu gonflerait les chiffres et fausserait toutes les décisions qu'ils servent à prendre.
    </div></div>

    <h2 id="gneb-membre">L'espace membre</h2>
    <p>Quatre écrans pour la personne connectée :</p>
    <table class="doc-table">
      <tr><th>Écran</th><th>Contenu</th></tr>
      <tr><td><code>/mon-compte</code></td><td>Le profil communautaire</td></tr>
      <tr><td><code>/mon-compte/config-pc</code></td><td>Sa configuration matérielle</td></tr>
      <tr><td><code>/mon-compte/commandes</code></td><td>L'historique des commandes</td></tr>
      <tr><td><code>/mon-compte/commandes/{référence}</code></td><td>Le détail d'une commande, avec son suivi</td></tr>
    </table>

    <h3 id="gneb-profil">Un seul modèle de profil</h3>
    <div class="callout ok"><span class="i">🧩</span><div>
      Le profil d'un membre est stocké dans <code>gne_players</code>, avec <code>team_id</code> à <code>NULL</code> tant que la personne n'a pas rejoint un effectif. <strong>C'est exactement la même fiche que celle d'un joueur.</strong> Intégrer quelqu'un à une équipe ne recrée donc rien : on remplit un champ. Et il n'y a jamais deux modèles de profil à maintenir en parallèle.
    </div></div>

    <div class="doc-foot">
      <span>GameNodeEsport · boutique</span>
      <span><a href="modules/gne/doc-gne-themes.php">Les thèmes →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
