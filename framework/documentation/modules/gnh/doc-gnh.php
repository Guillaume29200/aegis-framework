<?php
/**
 * documentation/modules/gnh/doc-gnh.php — GameNodeHosting, vue d'ensemble.
 */
$docPage = 'modules/gnh/doc-gnh.php';
$seo = [
    'title'     => 'GameNodeHosting — Vue d\'ensemble · Documentation Aegis Framework',
    'desc'      => "GameNodeHosting transforme GameNodePanel en hébergeur : boutique publique, tarification au slot, commande, paiement PayPal, livraison automatique des serveurs, espace client et support intégré. Requiert impérativement GameNodePanel.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh.php',
];
require __DIR__ . '/../../inc/head.php';

$code_requires = <<<'JSON'
"requires": {
    "cms_version": ">=4.0.0",
    "php_version": ">=8.1.0",
    "modules": ["Auth", "GameNodePanel"]
}
JSON;

$code_tree = <<<'TXT'
GameNodeHosting/
├── module.json              manifeste : menu, capacités, préfixe public, requires
├── routes.php               140 routes — administration, site public, espace client
├── Controllers/             21 contrôleurs
│   └── GamePanel/           le panel de gestion d'un serveur, côté client
├── Services/                38 services
│   ├── GamePanel/           lecture du panel : config, extensions, V.E.G.A
│   └── Gateways/            passerelles de paiement (contrat + PayPal)
├── Viewer/                  moteur de protocoles embarqué (vitrine publique)
├── Views/
│   ├── admin/               les écrans d'administration
│   └── client/              les thèmes, par surface : site / panel / gamepanel
└── database/
    ├── install.sql          20 tables gnh_
    ├── uninstall.sql
    └── migrations/          évolutions de schéma versionnées
TXT;
?>

    <h1>GameNodeHosting</h1>
    <p class="doc-lead">GameNodeHosting transforme GameNodePanel en <strong>véritable hébergeur</strong> : une boutique publique, une tarification au slot et à la durée, un tunnel de commande avec paiement, la <strong>livraison automatique</strong> des serveurs, un espace client complet et un support intégré.</p>
    <div class="doc-meta">
      <span class="doc-pill">v1.13.1</span>
      <span class="doc-pill">20 tables gnh_</span>
      <span class="doc-pill">140 routes</span>
      <span class="doc-pill">Requiert GameNodePanel</span>
    </div>

    <div class="callout warn"><span class="i">🛑</span><div>
      <strong>GameNodePanel est indispensable.</strong> GameNodeHosting n'est pas un module autonome : <strong>sans GameNodePanel, il est inutilisable</strong>. Il n'a ni catalogue de jeux, ni serveurs, ni machines — il <em>vend</em> ce que GameNodePanel <em>exploite</em>. Installez et configurez GameNodePanel d'abord ; GameNodeHosting vient ensuite.
    </div></div>

    <h2 id="gnh-requis">Pourquoi GameNodePanel est obligatoire</h2>
    <p>Ce n'est pas une préférence d'installation, c'est une dépendance de fond. GameNodeHosting ne possède <strong>aucune</strong> des données qui font un hébergement :</p>
    <table class="doc-table">
      <tr><th>Ce dont l'hébergeur a besoin</th><th>Qui le détient</th></tr>
      <tr><td>Le catalogue des jeux proposables</td><td>GameNodePanel — <code>gnp_games</code></td></tr>
      <tr><td>Les machines qui hébergent, et leur capacité</td><td>GameNodePanel — <code>gnp_dedicated_servers</code></td></tr>
      <tr><td>Les serveurs de jeu eux-mêmes</td><td>GameNodePanel — <code>gnp_game_servers</code></td></tr>
      <tr><td>Le catalogue d'extensions installables</td><td>GameNodePanel — <code>gnp_marketplace_*</code></td></tr>
      <tr><td>Démarrer, arrêter, installer, réinstaller un serveur</td><td>GameNodePanel</td></tr>
      <tr><td>L'analyse de santé V.E.G.A</td><td>GameNodePanel</td></tr>
    </table>
    <p>GameNodeHosting apporte le reste : le prix, la commande, le paiement, la facture, le client, et les écrans qu'un client a le droit de voir. Activer le module sans GameNodePanel donne une boutique <strong>sans rien à vendre</strong>.</p>
    <p>La dépendance est déclarée dans le manifeste :</p>
    <pre><code><?= $h($code_requires) ?></code></pre>
    <div class="callout"><span class="i">🤔</span><div>
      <strong>Mais le module reste activable seul, et c'est voulu.</strong> <code>getDependencies()</code> n'est <em>délibérément pas</em> surchargée : si elle l'était, le gestionnaire de modules refuserait l'activation avec un message technique, et l'alerte du tableau de bord ne pourrait <strong>jamais</strong> s'afficher. Le module préfère s'activer, puis vous dire clairement ce qui manque et où l'obtenir. La déclaration <code>requires</code> reste donc informative.
    </div></div>
    <p>Concrètement : sans GameNodePanel, le tableau de bord affiche une alerte au lieu de tomber en erreur, et la boutique n'a aucun jeu à proposer. Rien ne casse — mais rien ne se vend.</p>

    <h2 id="gnh-frontiere">La frontière entre les deux modules</h2>
    <p>Chaque module d'Aegis porte son <strong>propre préfixe de tables</strong> — c'est ce qui permet à deux modules de cohabiter sans jamais se mélanger. GameNodeHosting possède ses <strong>20 tables <code>gnh_</code></strong> et n'en crée aucune ailleurs.</p>
    <p>Vis-à-vis de GameNodePanel, la règle tient en une phrase : <strong>on lit, on n'écrit pas</strong> — à une exception près, revendiquée.</p>
    <table class="doc-table">
      <tr><th>Sens</th><th>Ce qui se passe</th><th>Où</th></tr>
      <tr><td>Lecture</td><td>Jeux, machines, serveurs, extensions, comptes FTP, versions… GameNodeHosting consulte le schéma de GameNodePanel pour afficher et pour calculer.</td><td><code>GameCatalogService</code>, <code>CapacityService</code>, <code>GamePanel/*</code></td></tr>
      <tr><td>Écriture</td><td><strong>Une seule</strong> : l'insertion d'une ligne dans <code>gnp_game_servers</code> au moment de livrer un serveur payé. Aucune colonne existante n'est modifiée, aucune table n'est altérée.</td><td><code>ProvisioningService</code></td></tr>
      <tr><td>Actions</td><td>Démarrer, arrêter, installer, envoyer une commande : <strong>jamais</strong> GameNodeHosting. Ces routes restent celles de GameNodePanel, qui vérifie la propriété du serveur.</td><td>—</td></tr>
    </table>
    <div class="callout ok"><span class="i">🧭</span><div>Cette discipline a une conséquence pratique : <strong>désactiver GameNodeHosting ne casse rien</strong> chez GameNodePanel. Les serveurs livrés continuent d'exister et de tourner — ils ont simplement été créés par la boutique plutôt qu'à la main.</div></div>

    <h2 id="gnh-intro">Ce que le module apporte</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Fonctions</th><th>Détail</th></tr>
      <tr><td>💶 Catalogue &amp; prix</td><td>Offres, tarifs par jeu, durées à coefficient, suppléments, contrôle de capacité</td><td><a href="modules/gnh/doc-gnh-catalogue.php">Offres, tarifs &amp; durées</a></td></tr>
      <tr><td>🧾 Vente</td><td>Tunnel de commande, codes promo, paiement, facture PDF, livraison automatique, e-mails</td><td><a href="modules/gnh/doc-gnh-commande.php">Commande, paiement &amp; livraison</a></td></tr>
      <tr><td>👤 Client</td><td>Espace client, panel de jeu, configuration, extensions, V.E.G.A, fichiers, factures, sessions</td><td><a href="modules/gnh/doc-gnh-client.php">Espace client &amp; panel de jeu</a></td></tr>
      <tr><td>🎨 Vitrine</td><td>Trois surfaces de thèmes, pages, navigation, FAQ, vitrine des serveurs, SEO</td><td><a href="modules/gnh/doc-gnh-site.php">Site public &amp; thèmes</a></td></tr>
      <tr><td>⚙️ Pilotage</td><td>Tableau de bord, clients, commandes, locations, promotions, anti-fraude, réglages</td><td><a href="modules/gnh/doc-gnh-admin.php">Administration &amp; réglages</a></td></tr>
      <tr><td>🎫 Support</td><td>Tickets liés au serveur, côté client et côté staff</td><td><a href="modules/gnh/doc-gnh-tickets.php">Support intégré</a></td></tr>
    </table>

    <h2 id="gnh-archi">Architecture</h2>
    <div class="tree"><?= $h($code_tree) ?></div>
    <p>Le module suit la règle Aegis : <strong>un contrôleur par écran, un service par domaine</strong>, aucune requête dans une vue. Deux services méritent d'être signalés parce qu'ils sont volontairement <em>sans</em> base de données :</p>
    <ul>
      <li><code>PricingService</code> — la règle métier centrale. Tout prix affiché, commandé ou facturé en sort. On lui passe les données déjà lues, il ne fait que calculer : la formule reste vérifiable sans monter une base de test.</li>
      <li><code>MailTemplateService</code> — compose les e-mails, ne les envoie pas. On peut donc en afficher un aperçu en administration sans risquer d'en partir un.</li>
    </ul>

    <h2 id="gnh-install">Installation</h2>
    <ol class="steps">
      <li><strong>Installez et configurez GameNodePanel</strong> : au moins un serveur dédié déclaré, et les jeux que vous voulez proposer. Sans cela, la boutique n'a rien à vendre.</li>
      <li>Déposez le dossier <code>GameNodeHosting/</code> dans <code>modules/</code>, ou importez le ZIP depuis <em>Administration → Modules</em>.</li>
      <li>Activez le module : les 20 tables <code>gnh_</code> sont créées, et les pages système du site (accueil, offres, serveurs, FAQ) sont semées.</li>
      <li>Renseignez <em>GameNode Hosting → Réglages</em> : identité, devise, paiement, et les autorisations du panel de jeu.</li>
      <li>Créez vos <strong>durées</strong>, puis vos <strong>offres</strong>, et fixez les prix par jeu. La boutique est en ligne.</li>
    </ol>
    <div class="callout"><span class="i">🔗</span><div>Le site visiteur répond sous le préfixe <code>/hosting</code>, <strong>renommable</strong> depuis <em>Administration → Modules</em> sans toucher au code : les chemins sont écrits en canonique et traduits à l'entrée comme à la sortie.</div></div>

    <h2 id="gnh-menu">Le menu d'administration</h2>
    <p>Le module s'installe en <strong>méga-menu</strong> sous « 🗄️ GameNode Hosting », avec treize entrées : Tableau de bord, Gérer les offres, Durées de location, Pages, FAQ, Navigation, Clients, Commandes, Locations, Promotions, Anti-fraude, Support et Réglages.</p>

    <h2 id="gnh-tables">Les 20 tables</h2>
    <table class="doc-table">
      <tr><th>Domaine</th><th>Tables</th></tr>
      <tr><td>Réglages</td><td><code>gnh_settings</code></td></tr>
      <tr><td>Catalogue &amp; prix</td><td><code>gnh_durations</code>, <code>gnh_game_pricing</code>, <code>gnh_offers</code>, <code>gnh_offer_games</code></td></tr>
      <tr><td>Vente</td><td><code>gnh_orders</code>, <code>gnh_order_items</code>, <code>gnh_rentals</code>, <code>gnh_payment_methods</code></td></tr>
      <tr><td>Site</td><td><code>gnh_pages</code>, <code>gnh_menu_items</code>, <code>gnh_faq</code></td></tr>
      <tr><td>Marketing</td><td><code>gnh_coupons</code>, <code>gnh_coupon_usages</code>, <code>gnh_promo_links</code>, <code>gnh_promo_clicks</code></td></tr>
      <tr><td>Sécurité</td><td><code>gnh_fraud_flags</code>, <code>gnh_blocked_users</code></td></tr>
      <tr><td>Support</td><td><code>gnh_tickets</code>, <code>gnh_ticket_messages</code></td></tr>
    </table>

    <h2 id="gnh-capacites">Capacités déclarées</h2>
    <p>Le module s'appuie sur six briques transverses du framework, plutôt que de les réimplémenter : <strong>Markdown</strong>, <strong>Cache</strong>, <strong>SEO</strong>, <strong>RGPD/Cookies</strong>, <strong>Analytics</strong> et <strong>reCAPTCHA</strong>. Chacune se règle dans <em>Configuration</em>, jamais dans le module — voir <a href="framework/doc-capabilities.php">Fonctionnalités &amp; capacités</a>.</p>

    <div class="doc-foot">
      <span>GameNodeHosting v1.13.1 · requiert GameNodePanel</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
