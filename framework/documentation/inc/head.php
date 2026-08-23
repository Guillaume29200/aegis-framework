<?php
/**
 * documentation/inc/head.php — Layout + sidebar catégorisée (multi-pages) partagés.
 *
 * Chaque page définit AVANT l'include :
 *   $docPage = 'documentation.php';   // nom de fichier de la page active
 *   $seo = ['title'=>…, 'desc'=>…, 'canonical'=>…];
 */
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$github  = 'https://github.com/Guillaume29200/aegis-framework';
$docPage = $docPage ?? 'documentation.php';
$seo = array_merge(['title' => 'Documentation · GameNodePanel', 'desc' => '', 'canonical' => ''], $seo ?? []);

/* Navigation : catégories → pages → sections (ancres internes de la page). */
$docNav = [
    'Aegis Framework' => ['icon' => '📦', 'pages' => [
        'framework/doc-aegis.php'   => ['Présentation', [
            ['intro','Présentation'],['aegis','GNP &amp; Aegis'],['prerequis','Prérequis'],['installation','Installation'],
            ['architecture','Architecture'],['lifecycle','Cycle d\'une requête'],['routing','Routing'],['modules','Système de modules'],
            ['menus','Menus (JSON)'],['services','Services'],['securite','Sécurité'],['security-center','Security Center'],
            ['helpers','Helpers globaux'],['conventions','Conventions'],
        ]],
        'framework/doc-quickstart.php'  => ['Démarrage rapide', [['qs-intro','En 5 minutes'],['qs-get','Récupérer'],['qs-install','Installer'],['qs-login','Premier login'],['qs-next','Et ensuite ?']]],
        'framework/doc-config.php'      => ['Configuration .env', [['env-intro','Le fichier .env'],['env-vars','Variables'],['env-security','Sécurité'],['env-prod','Production']]],
        'framework/doc-router.php'      => ['Router &amp; Helpers', [['rt-routes','Définir des routes'],['rt-params','Paramètres'],['rt-groups','Groupes'],['rt-di','Injection'],['rt-helpers','Helpers']]],
        'framework/doc-services.php'    => ['Services &amp; helpers', [['srv-intro','Le conteneur'],['srv-database','Database'],['srv-cache','Cache'],['srv-logger','Logger'],['srv-debugbar','Debug Bar'],['srv-geoloc','Géolocalisation'],['srv-image','ImageOptimizer'],['srv-uploader','ImageUploader'],['srv-device','DeviceDetector'],['srv-recaptcha','reCAPTCHA'],['srv-helpers','Helpers globaux']]],
        'framework/doc-security-center.php' => ['Centre de sécurité &amp; firewall', [['sc-intro','Vue d\'ensemble'],['sc-csrf','CSRF'],['sc-ratelimit','Rate limiting'],['sc-firewall','Firewall applicatif'],['sc-center','Security Center'],['sc-detectors','Les 27 détecteurs'],['sc-scoring','Scoring &amp; blocage'],['sc-lists','Listes blanche / noire'],['sc-headers','En-têtes &amp; sessions'],['sc-admin','Page d\'administration']]],
        'framework/doc-migrations.php'  => ['Migrations versionnées', [['mig-intro','Principe'],['mig-create','Créer une migration'],['mig-run','Exécution'],['mig-best','Bonnes pratiques']]],
        'framework/doc-turbonav.php'    => ['TurboNav (navigation SPA)', [['tn-intro','Présentation'],['tn-how','Fonctionnement'],['tn-req','Prérequis HTML'],['tn-config','Activation &amp; config'],['tn-events','Événements'],['tn-api','API JavaScript'],['tn-prefetch','Préchargement'],['tn-forms','Formulaires GET'],['tn-optout','Exclure un lien'],['tn-headers','En-têtes &amp; PHP'],['tn-js','Écrire du JS compatible'],['tn-changelog','Historique'],['tn-tshoot','Dépannage']]],
    ]],
    'Module' => ['icon' => '🧩', 'pages' => [
        'framework/doc-module.php'   => ['Créer un module', [
            ['mod-intro','Créer un module'],['mod-generator','Le générateur'],['mod-gen-public','Moteur de templates'],
            ['mod-structure','Structure'],['mod-manifest','module.json'],['mod-manifest-public','Préfixe public'],
            ['mod-public-home','Interface publique'],
            ['mod-class','Classe du module'],['mod-routes','Routes'],['mod-controller','Contrôleur'],['mod-service','Service'],
            ['mod-views','Vues admin'],['mod-sql','SQL install / uninstall'],['mod-changelog','Changelog'],['mod-install','Installer'],
        ]],
        'framework/doc-templating.php' => ['Moteur de templates &amp; thèmes', [
            ['tpl-why','Pourquoi du HTML'],['tpl-structure','Structure d\'un thème'],['tpl-meta','meta.json &amp; options'],['tpl-links','Le type links'],
            ['tpl-syntax','La syntaxe'],['tpl-filters','Les filtres'],['tpl-traps','Les pièges'],
            ['tpl-manager','ThemeManager'],['tpl-fallback','Le repli par gabarit'],['tpl-zip','Téléverser un thème'],['tpl-assets','Servir les assets'],
        ]],
        'framework/doc-capabilities.php' => ['Fonctionnalités &amp; capacités', [
            ['cap-intro','Le principe'],['cap-list','Les huit capacités'],['cap-declare','Déclarer une capacité'],
            ['cap-output','Poser sur une page'],['cap-turbonav','Le cas TurboNav'],['cap-cache','Ne jamais supposer'],['cap-example','Le module Exemple'],
        ]],
        'framework/doc-views.php'    => ['Anatomie d\'une vue admin', [['v-intro','Principe'],['v-layout','En-tête de page'],['v-card','Cartes'],['v-kpi','KPIs'],['v-table','Tableaux'],['v-btn','Boutons'],['v-badge','Badges'],['v-grid','Grilles'],['v-form','Formulaires']]],
        'framework/doc-theme.php'    => ['Thème admin &amp; TurboNav', [['th-intro','Le thème admin'],['th-helpers','admin_header / footer'],['th-prefs','Préférences &amp; dispositions'],['th-menus','Menus JSON'],['th-graft','Greffer dans un autre menu'],['th-mega','Méga-menu'],['th-turbonav','TurboNav (SPA)'],['th-turbonav-js','Écrire du JS compatible'],['th-assets','CSS &amp; JS du thème']]],
        'framework/doc-security.php' => ['Sécurité dans un module', [['s-csrf','CSRF'],['s-escape','Échappement'],['s-sql','Requêtes préparées'],['s-access','Contrôle d\'accès'],['s-validation','Validation']]],
        'framework/doc-package.php'  => ['Empaqueter &amp; distribuer', [['pk-structure','Préparer'],['pk-zip','Créer le ZIP'],['pk-install','Installer'],['pk-update','Mettre à jour']]],
    ]],
    'Analytics' => ['icon' => '📈', 'pages' => [
        'modules/analytics/doc-analytics.php'     => ['Vue d\'ensemble', [['an-intro','Qu\'est-ce que Analytics ?'],['an-why','Sans cookie &amp; RGPD'],['an-collect','La collecte'],['an-archi','Architecture'],['an-install','Installation &amp; activation'],['an-secu','Sécurité &amp; confidentialité']]],
        'modules/analytics/doc-analytics-use.php' => ['Tableau de bord &amp; objectifs', [['anu-dashboard','Tableau de bord &amp; rapports'],['anu-realtime','Temps réel &amp; en ligne'],['anu-goals','Objectifs &amp; conversions'],['anu-settings','Réglages &amp; confidentialité'],['anu-retention','Rétention &amp; purge (cron)'],['anu-reset','Réinitialisation &amp; export']]],
    ]],
    'GameNodeEsport' => ['icon' => '🏆', 'pages' => [
        'modules/gne/doc-gne.php'            => ['Vue d\'ensemble', [['gne-pour-qui','Pour qui'],['gne-fonctions','Ce que couvre le module'],['gne-archi','Architecture'],['gne-ecosysteme','L\'écosystème'],['gne-install','Installation'],['gne-menu','Les écrans'],['gne-tables','Les 26 tables']]],
        'modules/gne/doc-gne-equipes.php'    => ['Équipes, joueurs &amp; matchs', [['gnee-jeux','Les jeux'],['gnee-equipes','Les équipes'],['gnee-joueurs','Les joueurs'],['gnee-annuaire','Annuaire des membres'],['gnee-config','Configurations PC'],['gnee-marques','Marques matériel'],['gnee-matchs','Les matchs'],['gnee-libelles','Libellés d\'agenda'],['gnee-stats','Statistiques'],['gnee-zero','Le piège du zéro']]],
        'modules/gne/doc-gne-contenu.php'    => ['Contenus &amp; communauté', [['gnec-news','Actualités'],['gnec-pages','Pages'],['gnec-menu','Le menu'],['gnec-sliders','Sliders'],['gnec-widgets','Les widgets'],['gnec-photos','Galerie photo'],['gnec-videos','Vidéos'],['gnec-twitch','Streamers Twitch'],['gnec-sondages','Sondages'],['gnec-recrutement','Recrutement'],['gnec-dons','Dons &amp; cagnottes'],['gnec-partenaires','Partenaires'],['gnec-contact','Contact']]],
        'modules/gne/doc-gne-boutique.php'   => ['Boutique &amp; espace membre', [['gneb-interrupteur','Allumer la boutique'],['gneb-catalogue','Le catalogue'],['gneb-panier','Le panier'],['gneb-commande','La commande'],['gneb-livraison','Transporteurs'],['gneb-paypal','Le paiement'],['gneb-stats','Statistiques'],['gneb-membre','Espace membre'],['gneb-profil','Un seul profil']]],
        'modules/gne/doc-gne-themes.php'     => ['Les thèmes', [['gnet-modele','Le modèle'],['gnet-livres','Thèmes livrés'],['gnet-premium','Thèmes premium'],['gnet-html','HTML, jamais PHP'],['gnet-options','Les options'],['gnet-repli','Le repli par gabarit'],['gnet-coque','Le piège de la coque'],['gnet-installer','Installer un thème'],['gnet-structure','Structure']]],
        'modules/gne/doc-gne-comparatif.php' => ['Face à NeoFrag &amp; Contentify', [['cmp-qui','Les trois projets'],['cmp-tech','Technologies'],['cmp-maintenance','Maintenance'],['cmp-secu','« Plus maintenu » : conséquences'],['cmp-fonctions','Fonctionnalités'],['cmp-themes','Les thèmes'],['cmp-prix','Modèle économique'],['cmp-ecosysteme','L\'écosystème'],['cmp-choisir','Comment choisir'],['cmp-sources','Sources']]],
    ]],
    'GameNodeHosting' => ['icon' => '🗄️', 'pages' => [
        'modules/gnh/doc-gnh.php'           => ['Vue d\'ensemble', [['gnh-requis','GameNodePanel obligatoire'],['gnh-frontiere','La frontière entre les deux'],['gnh-intro','Ce que le module apporte'],['gnh-archi','Architecture'],['gnh-install','Installation'],['gnh-menu','Le menu'],['gnh-tables','Les 20 tables'],['gnh-capacites','Capacités']]],
        'modules/gnh/doc-gnh-catalogue.php' => ['Offres, tarifs &amp; durées', [['gnhc-modele','Le modèle'],['gnhc-durations','Les durées'],['gnhc-pricing','Tarifs par jeu'],['gnhc-offers','Les offres'],['gnhc-formula','La formule'],['gnhc-vat','TVA'],['gnhc-extras','Les suppléments'],['gnhc-capacity','Contrôle de capacité']]],
        'modules/gnh/doc-gnh-commande.php'  => ['Commande, paiement &amp; livraison', [['gnho-tunnel','Le tunnel'],['gnho-coupon','Codes promo'],['gnho-payment','Le paiement'],['gnho-gateways','Ajouter une passerelle'],['gnho-order','De la config à la commande'],['gnho-provisioning','Livraison automatique'],['gnho-invoice','Facture PDF'],['gnho-mails','E-mails']]],
        'modules/gnh/doc-gnh-client.php'    => ['Espace client &amp; panel de jeu', [['gnhp-espace','Les écrans'],['gnhp-principe','Afficher, ne pas agir'],['gnhp-permissions','Autorisations'],['gnhp-config','Configuration du jeu'],['gnhp-extensions','Extensions'],['gnhp-vega','V.E.G.A'],['gnhp-fichiers','Fichiers'],['gnhp-reinstall','Réinstallation'],['gnhp-factures','Factures'],['gnhp-sessions','Sessions'],['gnhp-profil','Profil']]],
        'modules/gnh/doc-gnh-site.php'      => ['Site public &amp; thèmes', [['gnhs-surfaces','Les trois surfaces'],['gnhs-themes','Installer un thème'],['gnhs-options','Options de thème'],['gnhs-pages','Les pages'],['gnhs-menu','La navigation'],['gnhs-faq','La FAQ'],['gnhs-vitrine','Vitrine des serveurs'],['gnhs-seo','SEO'],['gnhs-404','La page 404']]],
        'modules/gnh/doc-gnh-admin.php'     => ['Administration &amp; réglages', [['gnha-dashboard','Tableau de bord'],['gnha-clients','Clients'],['gnha-orders','Commandes'],['gnha-rentals','Locations'],['gnha-promotions','Promotions'],['gnha-fraud','Anti-fraude'],['gnha-settings','Les réglages'],['gnha-perms','Permissions']]],
        'modules/gnh/doc-gnh-tickets.php'   => ['Support intégré', [['tk-intro','Ce que c\'est'],['tk-included','Plus rien à installer'],['tk-client','Côté client'],['tk-types','Types de problème'],['tk-admin','Côté staff'],['tk-statuses','Statuts'],['tk-antispam','Anti-spam'],['tk-secu','Sécurité']]],
    ]],
    'GameNodePanel' => ['icon' => '🎮', 'pages' => [
        'modules/gnp/doc-gnp.php'      => ['Vue d\'ensemble', [['gnp-intro','Qu\'est-ce que GNP ?'],['gnp-archi','Architecture hybride'],['gnp-panels','Les panels'],['gnp-modules','Les modules GNP'],['gnp-vega','VEGA'],['gnp-odin','O.D.I.N'],['gnp-db','Database Manager'],['gnp-securite','Sécurité GNP']]],
        'modules/gnp/doc-prerequis.php' => ['Prérequis &amp; hébergement', [['hebergement','Hébergement web'],['vps','Serveur de jeux (VPS)'],['architecture','Architecture en 2 parties'],['os','OS supportés'],['extensions','Extensions PHP'],['checklist','Checklist']]],
        'modules/gnp/doc-host.php'     => ['Prérequis serveur hôte', [['host-intro','Le serveur hôte'],['host-ssh','SSH'],['host-steamcmd','SteamCMD'],['host-docker','Docker'],['host-mysql','MySQL'],['host-ips','IP failover &amp; additionnelles']]],
        'modules/gnp/doc-games.php'    => ['Ajouter un jeu', [['games-intro','Deux méthodes'],['games-form','Via le formulaire'],['games-versions','Versions &amp; configs'],['games-json','Via JSON (import)'],['games-json-struct','Structure du JSON'],['games-import','Importer'],['games-export','Exporter'],['games-images','Images']]],
        'modules/gnp/doc-install.php'  => ['Installer un serveur', [['inst-intro','Vue d\'ensemble'],['inst-flow','Le cycle d\'installation'],['inst-steam','SteamCMD'],['inst-steamguard','Steam Guard'],['inst-docker','Docker'],['inst-java','Java / Minecraft'],['inst-git','Git'],['inst-manual','Manuel'],['inst-vu','Venice Unleashed'],['inst-live','Console live (SSE)'],['inst-reinstall','Réinstaller']]],
        'modules/gnp/doc-panel.php'    => ['Gérer un serveur de jeu', [['panel-intro','Le panel'],['panel-dispatch','Un panel par type de jeu'],['panel-power','Démarrer / Arrêter'],['panel-console','Console &amp; commandes'],['panel-files','Fichiers &amp; FTP'],['panel-backups','Sauvegardes'],['panel-config','Configuration &amp; éditeurs'],['panel-specialized','Panels spécialisés'],['panel-mc','Minecraft'],['panel-voice','Vocal / TeamSpeak'],['panel-cfx','FiveM / CfX'],['panel-vega','Santé VEGA']]],
        'modules/gnp/doc-marketplace.php' => ['Marketplace &amp; plugins', [['mk-intro','Le Marketplace'],['mk-catalog','Alimenter le catalogue'],['mk-ai','Ajout par IA'],['mk-manifest','Le manifeste'],['mk-systems','Systèmes reconnus'],['mk-install','Installation SSH'],['mk-admins','Gestion des admins'],['mk-browse','Côté utilisateur']]],
        'modules/gnp/doc-gnpq.php'     => ['GNPQ — le viewer', [['gq-intro','Qu\'est-ce que GNPQ ?'],['gq-vs','GNPQ vs LGSL'],['gq-protocols','Protocoles supportés'],['gq-matrice','La matrice de champs'],['gq-flow','Cycle d\'une requête'],['gq-libs','Les briques (Libs)'],['gq-extend','Ajouter un jeu'],['gq-widget','Le widget']]],
        'modules/gnp/doc-vega.php'     => ['VEGA (technique)', [['vega-archi','Architecture'],['vega-collect','Collecte'],['vega-patterns','Patterns'],['vega-providers','Providers IA'],['vega-actions','Actions']]],
        'modules/gnp/doc-odin.php'     => ['O.D.I.N (technique)', [['odin-agent','Agent Python'],['odin-metrics','Métriques'],['odin-geoloc','Géolocalisation'],['odin-anomaly','Anomalies']]],
        'modules/gnp/doc-database.php' => ['Database Manager (technique)', [['db-provision','Provisioning'],['db-dblite','Console DbLite'],['db-quota','Quotas'],['db-backup','Sauvegardes'],['db-users','Utilisateurs RO/RW']]],
        'modules/gnp/doc-cron.php'     => ['Cron &amp; tâches planifiées', [['cron-intro','Pourquoi des crons'],['cron-manager','Le gestionnaire de cron'],['cron-gnp','Crons GameNodePanel'],['cron-odin','Crons O.D.I.N'],['cron-crontab','Exemple crontab'],['cron-logs','Logs &amp; exécution manuelle']]],
    ]],
    'GameNodeViewer' => ['icon' => '🧩', 'pages' => [
        'modules/gnv/doc-gnv.php'           => ['Vue d\'ensemble', [['gnv-intro','Qu\'est-ce que GameNodeViewer ?'],['gnv-vs','Vs GameQ / LGSL'],['gnv-archi','Architecture'],['gnv-flow','Cycle d\'une requête'],['gnv-protocols','Protocoles supportés'],['gnv-matrix','Matrice d\'affichage'],['gnv-cache','Cache &amp; historique'],['gnv-secu','Sécurité']]],
        'modules/gnv/doc-gnv-protocol.php'  => ['Créer un protocole', [['p-intro','Le principe'],['p-interface','L\'interface'],['p-registry','Auto-découverte'],['p-serverinfo','ServerInfo'],['p-net','La couche réseau'],['p-example','Exemple complet'],['p-register','Brancher le jeu'],['p-test','Tester']]],
        'modules/gnv/doc-gnv-usage.php'     => ['Admin &amp; viewer public', [['u-servers','Serveurs'],['u-games','Jeux &amp; matrice'],['u-maps','Maps'],['u-settings','Réglages'],['u-history','Historique &amp; cron'],['u-widget','Widget embeddable'],['u-api','API JSON'],['u-seo','SEO &amp; sitemap'],['u-themes','Thèmes']]],
    ]],
    'Forum' => ['icon' => '🗨️', 'pages' => [
        'modules/forum/doc-forum.php'           => ['Vue d\'ensemble', [['forum-intro','Qu\'est-ce que le Forum ?'],['forum-features','Fonctionnalités'],['forum-archi','Architecture'],['forum-install','Installation &amp; activation'],['forum-secu','Sécurité en bref']]],
        'modules/forum/doc-forum-admin.php'     => ['Administration', [['fa-categories','Catégories &amp; sous-catégories'],['fa-permissions','Permissions voir/créer/répondre'],['fa-moderation','Modération &amp; signalements'],['fa-users','Utilisateurs &amp; rôles'],['fa-modperms','Permissions modérateur'],['fa-settings','Réglages généraux'],['fa-tools','Outils &amp; maintenance']]],
        'modules/forum/doc-forum-content.php'   => ['Contenu &amp; Markdown', [['fc-topics','Sujets'],['fc-replies','Réponses &amp; édition'],['fc-markdown','Markdown sur-mesure'],['fc-bbcode','Extensions BBCode'],['fc-mentions','Mentions &amp; emojis'],['fc-reactions','Réactions &amp; votes'],['fc-polls','Sondages'],['fc-tags','Tags &amp; recherche']]],
        'modules/forum/doc-forum-community.php' => ['Communauté &amp; engagement', [['fco-reputation','Réputation, XP &amp; niveaux'],['fco-rewards','Récompenses &amp; badges'],['fco-profiles','Profils membres'],['fco-guestbook','Livre d\'or'],['fco-shoutbox','Shoutbox'],['fco-notifications','Notifications'],['fco-members','Liste des membres']]],
        'modules/forum/doc-forum-customize.php' => ['Personnalisation &amp; SEO', [['fcz-themes','Thèmes &amp; import ZIP'],['fcz-widgets','Widgets sidebar &amp; footer'],['fcz-pollwidgets','Sondages widgets'],['fcz-navlinks','Liens de navigation'],['fcz-pages','Pages statiques'],['fcz-seo','SEO']]],
    ]],
    'Blog' => ['icon' => '✍️', 'pages' => [
        'modules/blog/doc-blog.php'           => ['Vue d\'ensemble', [['bl-intro','Qu\'est-ce que Blogging ?'],['bl-features','Fonctionnalités'],['bl-archi','Architecture'],['bl-install','Installation &amp; activation'],['bl-secu','Sécurité en bref']]],
        'modules/blog/doc-blog-content.php'   => ['Articles &amp; contenu', [['blc-post','Créer un article'],['blc-publish','Publication &amp; programmation'],['blc-categories','Catégories &amp; tags'],['blc-comments','Commentaires &amp; modération'],['blc-likes','Likes']]],
        'modules/blog/doc-blog-customize.php' => ['Apparence &amp; diffusion', [['blz-themes','Thèmes &amp; import ZIP'],['blz-settings','Réglages'],['blz-seo','SEO'],['blz-feed','Flux RSS &amp; sitemap']]],
    ]],
    'Marketplace' => ['icon' => '🛒', 'pages' => [
        'modules/marketplace/doc-mp.php'           => ['Vue d\'ensemble', [['mp-intro','Qu\'est-ce que la Marketplace ?'],['mp-features','Fonctionnalités'],['mp-archi','Architecture'],['mp-install','Installation &amp; activation'],['mp-secu','Sécurité en bref']]],
        'modules/marketplace/doc-mp-products.php'  => ['Catalogue &amp; produits', [['mpp-products','Créer un produit'],['mpp-versions','Versions &amp; changelog'],['mpp-files','Fichiers &amp; stockage'],['mpp-categories','Catégories'],['mpp-tags','Tags'],['mpp-images','Galerie d\'images']]],
        'modules/marketplace/doc-mp-sales.php'     => ['Ventes &amp; clients', [['mps-payment','Paiement'],['mps-orders','Commandes &amp; remboursements'],['mps-download','Téléchargement sécurisé'],['mps-invoice','Factures PDF'],['mps-reviews','Avis &amp; notes'],['mps-promos','Promotions &amp; coupons']]],
        'modules/marketplace/doc-mp-customize.php' => ['Vitrine &amp; réglages', [['mpc-themes','Thèmes &amp; import ZIP'],['mpc-sliders','Sliders d\'accueil'],['mpc-pages','Pages &amp; mentions légales'],['mpc-nav','Navigation'],['mpc-seo','SEO &amp; médias'],['mpc-settings','Réglages']]],
    ]],
    'AegisHost' => ['icon' => '🌐', 'pages' => [
        'modules/aegishost/doc-aegishost.php' => ['Présentation', [
            ['ah-intro','Présentation'],['ah-agent','L\'agent privilégié'],
            ['ah-features','Ce qu\'il sait faire'],['ah-principe','Le principe du module'],
            ['ah-requis','Prérequis'],
        ]],
        'modules/aegishost/doc-aegishost-install.php' => ['Partie 1 : installation', [
            ['ai-requis','Avant de commencer'],['ai-upload','1. Déposer l\'archive'],
            ['ai-extract','2. Extraire l\'archive'],['ai-rights','3. Droits d\'exécution'],
            ['ai-run','4. Lancer l\'assistant'],
            ['ai-wizard','5. Répondre aux questions'],['ai-after','6. Après l\'installation'],
            ['ai-options','Installation non interactive'],['ai-agent','Reposer l\'agent seul'],
            ['ai-tshoot','Dépannage'],
        ]],
        'modules/aegishost/doc-aegishost-panel.php' => ['Partie 2 : le panneau', [
            ['a2-deploy','1. Déposer le framework'],['a2-module','2. Installer le module'],
            ['a2-first','3. Première visite'],['a2-tour','4. Écran par écran'],
            ['a2-site','5. Créer un site'],['a2-adopt','6. Sites déjà en place'],
            ['a2-modes','7. Simple &amp; Avancé'],['a2-securite','8. Les garde-fous'],
        ]],
        'modules/aegishost/doc-aegishost-docker.php' => ['Partie 3 : Docker', [
            ['a3-why','Pourquoi passer par le panneau'],['a3-apps','Les applications'],
            ['a3-create','Installer une application'],['a3-free','L\'image libre'],
            ['a3-manage','Suivre un conteneur'],['a3-publish','Publier sur un domaine'],
            ['a3-res','Volumes &amp; réseaux'],
        ]],
        'modules/aegishost/doc-aegishost-market.php' => ['Partie 4 : la Logithèque', [
            ['a4-how','Comment ça marche'],['a4-web','Serveurs web'],['a4-php','PHP'],
            ['a4-db','Bases de données'],['a4-tools','Services'],
            ['a4-config','Régler un composant'],['a4-remove','Retirer un composant'],
        ]],
        'modules/aegishost/doc-aegishost-backup.php' => ['Partie 5 : les sauvegardes', [
            ['a5-intro','Ce qui est sauvegardé'],['a5-form','La forme d\'une sauvegarde'],
            ['a5-run','Lancer une sauvegarde'],['a5-states','Les trois états'],
            ['a5-restore','Restaurer'],['a5-auto','Sauvegardes automatiques'],
            ['a5-remote','La copie hors-site'],['a5-advice','En pratique'],
        ]],
        'modules/aegishost/doc-aegishost-update.php' => ['Partie 6 : mise à jour', [
            ['a6-pair','Pourquoi deux versions'],['a6-module','Mettre à jour le module'],
            ['a6-agent','Mettre à jour l\'agent'],['a6-order','Dans quel ordre'],
            ['a6-help','Aide et réparation'],['a6-log','Le journal de l\'agent'],
            ['a6-tshoot','Quand le panneau ne répond plus'],
        ]],
        'modules/aegishost/doc-aegishost-security.php' => ['Partie 7 : la sécurité', [
            ['a7-audit','L\'audit du serveur'],['a7-ssh','Durcir SSH'],
            ['a7-firewall','Le pare-feu'],['a7-fail2ban','Fail2ban'],
            ['a7-clamav','L\'antivirus'],['a7-rest','Ce qui protège tout seul'],
            ['a7-advice','Quelques conseils'],
        ]],
        'modules/aegishost/doc-aegishost-mail.php' => ['Partie 8 : la messagerie', [
            ['a8-install','Installer la messagerie'],['a8-dns','Le DNS d\'abord'],
            ['a8-domain','Ajouter un domaine'],['a8-box','Créer une boîte'],
            ['a8-alias','Les alias'],['a8-client','Relever son courrier'],
            ['a8-check','Vérifier que ça marche'],['a8-honest','Avant de se lancer'],
        ]],
        'modules/aegishost/doc-aegishost-comparatif.php' => ['Face à aaPanel &amp; CyberPanel', [
            ['cp-position','Trois intentions'],['cp-ram','L\'empreinte mémoire'],
            ['cp-surface','La surface d\'attaque'],['cp-feat','Les fonctionnalités'],
            ['cp-price','Le modèle tarifaire'],['cp-choose','Lequel choisir ?'],
            ['cp-sources','Sources'],
        ]],
    ]],
    'Sauvegarde' => ['icon' => '💾', 'pages' => [
        'modules/sauvegarde/doc-sauvegarde.php' => ['Sauvegarde (Aegis Backup)', [['sv-intro','Présentation'],['sv-storage','Stockage protégé'],['sv-formats','Formats &amp; chiffrement'],['sv-scope','Périmètre (fichiers)'],['sv-db','Sauvegarde BDD sélective'],['sv-manual','Sauvegarde manuelle'],['sv-cron','Plannings automatiques'],['sv-cloud','Synchronisation cloud (S3)'],['sv-restore','Restauration'],['sv-secu','Sécurité']]],
    ]],
    'Tutoriels & Articles' => ['icon' => '🎓', 'pages' => [
        'tutoriels/tuto-ssh-root.php' => ['Activer le login root SSH', [['why','Pourquoi root ?'],['debian','Debian 12 / 13'],['ubuntu','Ubuntu'],['verif','Vérifier la connexion'],['secu','Sécuriser (clé SSH)'],['tshoot','Dépannage']]],
        'tutoriels/doc-ressources.php' => ['Ressources &amp; limites par hôte', [['res-tldr','L\'essentiel en 30 s'],['res-intro','Dédié vs mutualisé'],['res-reserves','Réserves OS &amp; disponible'],['res-dedie','Allouer CPU / RAM dédié'],['res-slots','Slots maximum par hôte'],['res-dimensionner','Dimensionner les slots'],['res-exemples','Exemples concrets'],['res-methode','Méthode recommandée']]],
        'tutoriels/doc-realtime.php' => ['Script realtime.sh (priorité CPU)', [['rt-what','À quoi ça sert ?'],['rt-fps','Impact sur les FPS'],['rt-install','Installation &amp; utilisation'],['rt-clocksource','Source d\'horloge système'],['rt-gnp','Avec GameNodePanel']]],
        'tutoriels/doc-kernel-1000hz.php' => ['Noyau Linux 1000Hz + Realtime', [['k-goal','Objectif'],['k-why','Pourquoi 1000Hz + RT'],['k-prereq','Prérequis'],['k-download','Télécharger les sources'],['k-patch','Extraction &amp; patch RT'],['k-config','Configuration du noyau'],['k-build','Compilation &amp; installation'],['k-reboot','Redémarrage'],['k-tips','Astuces'],['k-refs','Références']]],
        'tutoriels/doc-idler.php' => ['Astuce « idler » (cœur CPU)', [['id-goal','Objectif'],['id-steps','Étapes de mise en place'],['id-multicore','Astuce multi-cœur'],['id-result','Résultat attendu'],['id-warning','À ne pas oublier'],['id-conclusion','En conclusion']]],
    ]],
    'FAQ' => ['icon' => '❓', 'pages' => [
        'faq/doc-faq.php' => ['FAQ — Aegis Framework', [
            ['faq-demarrage','Démarrage & connexion'],['faq-general','Paramètres généraux'],['faq-apparence','Apparence & TurboNav'],
            ['faq-sessions','Sessions'],['faq-securite','Sécurité'],['faq-rgpd','RGPD / cookies'],['faq-seo','SEO & médias'],
            ['faq-ia','Modèles IA'],['faq-users','Utilisateurs & rôles'],['faq-modules','Modules'],
            ['faq-monitoring','Monitoring & diagnostic'],['faq-versions','Versions & changelog'],['faq-licences','Licences'],
        ]],
        'faq/doc-faq-gnp.php' => ['FAQ — GameNodePanel', [
            ['gnp-archi','Présentation & architecture'],['gnp-hotes','Serveurs hôtes (VPS)'],['gnp-serveurs','Serveurs de jeu'],
            ['gnp-install','Installer un jeu'],['gnp-panel','Gérer un serveur'],['gnp-jeux','Panels par jeu'],
            ['gnp-catalogue','Catalogue de jeux'],['gnp-plugins','Plugins & mods'],['gnp-vega','VEGA (IA)'],
            ['gnp-odin','O.D.I.N'],['gnp-db','Database Manager'],['gnp-ftp','FTP'],['gnp-membre','Espace membre'],['gnp-licence','Licence'],
        ]],
        'faq/doc-faq-forum.php' => ['FAQ — Forum', [
            ['fq-intro','Présentation'],['fq-categories','Catégories'],['fq-sujets','Sujets & messages'],
            ['fq-moderation','Modération & signalements'],['fq-recompenses','Réputation & récompenses'],
            ['fq-sondages','Sondages'],['fq-reactions','Réactions & votes'],['fq-themes','Thèmes & apparence'],
            ['fq-widgets','Widgets & navigation'],['fq-shoutbox','Shoutbox'],['fq-profils','Profils membres'],
            ['fq-reglages','Réglages'],['fq-secu','Sécurité & maintenance'],
        ]],
        'faq/doc-faq-blog.php' => ['FAQ — Blog', [
            ['fb-intro','Présentation'],['fb-articles','Articles &amp; rédaction'],['fb-publish','Publication &amp; programmation'],
            ['fb-organize','Catégories &amp; tags'],['fb-comments','Commentaires &amp; likes'],['fb-themes','Thèmes &amp; apparence'],
            ['fb-seo','SEO, RSS &amp; sitemap'],['fb-secu','Sécurité'],
        ]],
        'faq/doc-faq-marketplace.php' => ['FAQ — Marketplace', [
            ['mk-intro','Présentation'],['mk-produits','Produits'],['mk-catalog','Catégories & tags'],
            ['mk-paiement','Paiement'],['mk-commandes','Commandes & téléchargement'],['mk-factures','Factures PDF'],
            ['mk-promos','Promotions & coupons'],['mk-avis','Avis & notes'],['mk-apparence','Apparence de la boutique'],
            ['mk-secu','Sécurité'],
        ]],
        'faq/doc-faq-tickets.php' => ['FAQ — Tickets', [
            ['tk-intro','Présentation & dépendance GNP'],['tk-membre','Côté client'],['tk-admin','Côté administration'],
            ['tk-antispam','Anti-spam'],['tk-secu','Sécurité'],['tk-maint','Maintenance & données'],
        ]],
        'faq/doc-faq-aegishost.php' => ['FAQ — AegisHost', [
            ['ah-general','Généralités'],['ah-agent','L\'agent privilégié'],
            ['ah-install','Installation &amp; mise à jour'],['ah-sites','Sites, PHP &amp; HTTPS'],
            ['ah-docker','Docker'],['ah-backup','Sauvegardes'],
            ['ah-secu','Sécurité'],['ah-mail','Messagerie'],
        ]],
        'faq/doc-faq-sauvegarde.php' => ['FAQ — Sauvegarde', [
            ['sf-intro','Présentation'],['sf-formats','Formats &amp; chiffrement'],['sf-scope','Périmètre &amp; BDD'],
            ['sf-manual','Sauvegardes manuelles'],['sf-cron','Plannings automatiques'],['sf-cloud','Cloud (S3)'],
            ['sf-restore','Restauration'],['sf-secu','Sécurité &amp; stockage'],
        ]],
    ]],
];

/* Fil d'Ariane : retrouver catégorie + label de la page active. */
$crumbCat = ''; $crumbPage = '';
foreach ($docNav as $catLabel => $cat) {
    if (isset($cat['pages'][$docPage])) { $crumbCat = $catLabel; $crumbPage = $cat['pages'][$docPage][0]; break; }
}

/*
 * Navigation Précédent / Suivant AUTOMATIQUE.
 * Source unique = l'ordre du menu $docNav (aplati en ordre de lecture).
 * Ajouter / réordonner une page = modifier UNIQUEMENT $docNav : les liens
 * « Précédent / Suivant » de toutes les pages se recalculent tout seuls.
 */
$docFlat = [];
foreach ($docNav as $catLabel => $cat) {
    foreach ($cat['pages'] as $path => $meta) {
        $docFlat[] = ['path' => $path, 'label' => $meta[0], 'cat' => $catLabel, 'icon' => $cat['icon']];
    }
}
$docPrev = $docNext = null;
foreach ($docFlat as $i => $p) {
    if ($p['path'] === $docPage) {
        $docPrev = $docFlat[$i - 1] ?? ['path' => 'documentation.php', 'label' => 'Documentation', 'icon' => '📚']; // 1re page → retour à l'accueil
        $docNext = $docFlat[$i + 1] ?? null;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
// Base d'URL = racine de la doc (…/documentation/). Permet de ranger les pages
// en sous-dossiers tout en gardant des liens relatifs résolus depuis la racine,
// quelle que soit la profondeur de la page courante.
$__reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__mark = '/documentation/';
$__p = strpos($__reqPath, $__mark);
$__docBase = $__p !== false ? substr($__reqPath, 0, $__p + strlen($__mark)) : '/';
?>
<base href="<?= htmlspecialchars($__docBase, ENT_QUOTES) ?>">
<title><?= $h($seo['title']) ?></title>
<meta name="description" content="<?= $h($seo['desc']) ?>">
<?php if ($seo['canonical']): ?><link rel="canonical" href="<?= $h($seo['canonical']) ?>"><?php endif; ?>
<meta property="og:title" content="<?= $h($seo['title']) ?>">
<meta property="og:description" content="<?= $h($seo['desc']) ?>">
<meta name="theme-color" content="#4f46e5">
<script>try{var t=localStorage.getItem('gnp-theme');if(t)document.documentElement.setAttribute('data-theme',t);else if(matchMedia('(prefers-color-scheme: dark)').matches)document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
<style>
:root{
  --bg:#ffffff; --bg2:#f7f8fa; --bg3:#eef0f4; --bd:#e3e6ec; --bd2:#d4d8e0;
  --tx:#1f2330; --tx2:#5a6072; --tx3:#8b91a3;
  --ac:#4f46e5; --ac2:#6366f1; --ac-bg:#4f46e510;
  --ok:#16a34a; --warn:#d97706; --code-bg:#f4f5f8;
  --pre-bg:#0e1018; --pre-tx:#e6e8ef;
  --mono:ui-monospace,"SF Mono",Menlo,Consolas,"Liberation Mono",monospace;
  --sans:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
[data-theme="dark"]{
  --bg:#0d0f14; --bg2:#12151c; --bg3:#1a1e27; --bd:#242a36; --bd2:#2f3644;
  --tx:#e7eaf0; --tx2:#a3aab9; --tx3:#727a8c;
  --ac:#818cf8; --ac2:#a5b4fc; --ac-bg:#818cf818; --code-bg:#161a22;
  --pre-bg:#080a0f; --pre-tx:#e6e8ef;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--sans);background:var(--bg);color:var(--tx);line-height:1.7;font-size:15.5px;-webkit-font-smoothing:antialiased}
a{color:var(--ac);text-decoration:none} a:hover{text-decoration:underline}
code{font-family:var(--mono);font-size:.88em;background:var(--code-bg);border:1px solid var(--bd);padding:.1em .4em;border-radius:5px;color:var(--tx)}
pre{position:relative;background:var(--pre-bg);color:var(--pre-tx);border-radius:10px;padding:18px 20px;overflow:auto;font-family:var(--mono);font-size:13px;line-height:1.6;margin:16px 0}
pre code{background:none;border:none;padding:0;color:inherit;font-size:inherit}
.copy-btn{position:absolute;top:8px;right:8px;padding:5px 10px;font-family:var(--sans);font-size:.7rem;font-weight:600;color:#cbd5e1;background:#ffffff14;border:1px solid #ffffff22;border-radius:6px;cursor:pointer;opacity:0;transition:opacity .15s}
pre:hover .copy-btn{opacity:1}
.copy-btn:hover{background:#ffffff26;color:#fff} .copy-btn.done{color:#34d399;border-color:#34d39955}

.doc-top{position:sticky;top:0;z-index:50;display:flex;align-items:center;gap:16px;height:58px;padding:0 22px;background:var(--bg);border-bottom:1px solid var(--bd)}
.doc-logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.02rem;color:var(--tx)}
.doc-logo .dot{width:26px;height:26px;border-radius:7px;background:linear-gradient(135deg,var(--ac),var(--ac2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem}
.doc-logo small{font-weight:600;color:var(--tx3);font-size:.7rem;letter-spacing:.04em;text-transform:uppercase}
.doc-top-actions{margin-left:auto;display:flex;align-items:center;gap:8px}
.doc-btn{display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border:1px solid var(--bd2);border-radius:8px;font-size:.83rem;font-weight:600;color:var(--tx2);background:var(--bg2);cursor:pointer}
.doc-btn:hover{border-color:var(--ac);color:var(--ac);text-decoration:none}
.doc-btn.primary{background:var(--ac);border-color:var(--ac);color:#fff}
.doc-burger{display:none;border:1px solid var(--bd2);background:var(--bg2);border-radius:8px;width:38px;height:36px;font-size:1.1rem;cursor:pointer;color:var(--tx)}

.doc-wrap{display:grid;grid-template-columns:288px 1fr;max-width:1280px;margin:0 auto}
.doc-side{position:sticky;top:58px;align-self:start;height:calc(100vh - 58px);overflow-y:auto;padding:18px 14px 60px;border-right:1px solid var(--bd)}
.doc-search{width:100%;padding:9px 12px;margin-bottom:14px;border:1px solid var(--bd2);border-radius:9px;background:var(--bg2);color:var(--tx);font-size:.85rem;font-family:var(--sans)}
.doc-search:focus{outline:none;border-color:var(--ac)}
.doc-cat-block{margin-top:22px}
.doc-cat-block:first-child{margin-top:2px}
.doc-cat-title{display:flex;align-items:center;gap:8px;width:100%;margin-bottom:8px;padding:8px 11px;font-size:.73rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;border-radius:8px;border:none;cursor:pointer;font-family:inherit;text-align:left}
.doc-cat-title .ic{font-size:.98rem;line-height:1}
.doc-cat-title .doc-cat-name{flex:1}
.doc-cat-chevron{font-size:1rem;line-height:1;opacity:.75;transition:transform .2s}
.doc-cat-block.open .doc-cat-chevron{transform:rotate(90deg)}
.doc-cat-block:not(.open) .doc-cat-pages{display:none}
.doc-cat-pages{margin-left:7px;padding-left:12px;display:flex;flex-direction:column;gap:2px}
.doc-pg{display:block;padding:7px 12px;border-radius:8px;color:var(--tx2);font-size:.88rem;font-weight:600}
.doc-pg:hover{background:var(--bg3);color:var(--tx);text-decoration:none}
.doc-pg.on{font-weight:700}
.doc-sub{margin:2px 0 10px 6px;padding-left:12px;border-left:1px dashed var(--bd2)}
.doc-sub a{display:block;padding:4px 10px;border-radius:7px;color:var(--tx2);font-size:.82rem;font-weight:500}
.doc-sub a:hover{background:var(--bg3);color:var(--tx);text-decoration:none}
.doc-sub a.active{color:var(--ac);font-weight:700}
.doc-noresult{padding:10px 12px;color:var(--tx3);font-size:.85rem;display:none}
.doc-main{padding:30px 48px 100px;min-width:0;max-width:880px}

.doc-crumb{display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--tx3);margin-bottom:18px;flex-wrap:wrap}
.doc-crumb a{color:var(--tx2)} .doc-crumb .sep{opacity:.5} .doc-crumb .cur{color:var(--ac);font-weight:600}

.doc-main h1{font-size:2.1rem;font-weight:800;letter-spacing:-.02em;margin-bottom:10px}
.doc-main h2{font-size:1.5rem;font-weight:800;margin:48px 0 14px;padding-top:14px;border-top:1px solid var(--bd);scroll-margin-top:74px}
.doc-main h2:first-of-type{border-top:none}
.doc-main h3{font-size:1.12rem;font-weight:700;margin:26px 0 10px}
.doc-main p{margin:12px 0;color:var(--tx)}
.doc-main ul,.doc-main ol{margin:12px 0 12px 22px}
.doc-main li{margin:6px 0}
.doc-lead{font-size:1.12rem;color:var(--tx2);margin-bottom:8px}
.doc-meta{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0 6px}
.doc-pill{font-family:var(--mono);font-size:.72rem;font-weight:600;padding:4px 11px;border-radius:999px;background:var(--bg3);border:1px solid var(--bd);color:var(--tx2)}
.callout{display:flex;gap:12px;padding:14px 16px;border-radius:10px;margin:18px 0;background:var(--ac-bg);border:1px solid var(--ac);border-left-width:4px;font-size:.92rem}
.callout.warn{background:#d977060f;border-color:var(--warn)} .callout.ok{background:#16a34a0f;border-color:var(--ok)}
.callout .i{font-size:1.1rem;flex-shrink:0}
.doc-table{width:100%;border-collapse:collapse;margin:16px 0;font-size:.9rem}
.doc-table th,.doc-table td{text-align:left;padding:9px 12px;border:1px solid var(--bd);vertical-align:top}
.doc-table th{background:var(--bg2);font-weight:700}
.steps{counter-reset:s;margin:18px 0;padding:0;list-style:none}
.steps li{position:relative;padding:10px 0 10px 44px;border-bottom:1px solid var(--bd)}
.steps li:before{counter-increment:s;content:counter(s);position:absolute;left:0;top:9px;width:28px;height:28px;border-radius:50%;background:var(--ac);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem}
.tree{font-family:var(--mono);font-size:12.5px;background:var(--code-bg);border:1px solid var(--bd);border-radius:10px;padding:16px 18px;white-space:pre;overflow:auto;color:var(--tx2)}
.doc-foot{border-top:1px solid var(--bd);margin-top:50px;padding-top:22px;color:var(--tx3);font-size:.85rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
.doc-next{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}
.doc-next a{flex:1;min-width:220px;border:1px solid var(--bd);border-radius:12px;padding:16px 18px;background:var(--bg2)}
.doc-next a:hover{border-color:var(--ac);text-decoration:none}
.doc-next .k{font-size:.72rem;color:var(--tx3);text-transform:uppercase;letter-spacing:.06em}
.doc-next .v{font-weight:700;color:var(--tx);margin-top:3px}
/* Démo des composants .ui-* (page Anatomie d'une vue) */
.uidemo{border:1px dashed var(--bd2);border-radius:12px;padding:20px;margin:14px 0;background:var(--bg2)}
.uidemo .d-card{background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.uidemo .d-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid var(--bd2);background:var(--bg);font-weight:600;font-size:.85rem;color:var(--tx)}
.uidemo .d-btn.p{background:var(--ac);border-color:var(--ac);color:#fff}
.uidemo .d-btn.dg{background:#dc2626;border-color:#dc2626;color:#fff}
.uidemo .d-badge{display:inline-block;font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:999px}
.uidemo .b-green{background:#16a34a1f;color:#16a34a}.uidemo .b-amber{background:#d977061f;color:#d97706}.uidemo .b-red{background:#dc26261f;color:#dc2626}.uidemo .b-blue{background:#4f46e51f;color:#4f46e5}

@media(max-width:920px){
  .doc-wrap{grid-template-columns:1fr}
  .doc-side{position:fixed;top:58px;left:0;width:296px;background:var(--bg);z-index:40;transform:translateX(-100%);transition:transform .25s;box-shadow:0 20px 60px rgba(0,0,0,.2)}
  .doc-side.open{transform:none}
  .doc-burger{display:inline-flex;align-items:center;justify-content:center}
  .doc-main{padding:24px 22px 80px}
}
</style>
</head>
<body>

<header class="doc-top">
  <button class="doc-burger" id="burger" aria-label="Menu">☰</button>
  <a href="documentation.php" class="doc-logo"><span class="dot">📘</span> Aegis Framework <small>Docs</small></a>
  <div class="doc-top-actions">
    <button class="doc-btn" id="themeToggle" aria-label="Thème clair/sombre"><span id="ti">🌙</span></button>
    <a class="doc-btn" href="../index.php">← Le site</a>
    <a class="doc-btn primary" href="<?= $h($github) ?>" target="_blank" rel="noopener">⭐ GitHub</a>
  </div>
</header>

<div class="doc-wrap">

  <nav class="doc-side" id="side">
    <input type="text" class="doc-search" id="docSearch" placeholder="🔎 Rechercher dans la doc…" autocomplete="off">
    <div class="doc-noresult" id="docNoResult">Aucun résultat.</div>
    <div id="docNavList">
    <?php
      $catColors = ['#6366f1', '#0ea5e9', '#f59e0b', '#16a34a', '#0d9488', '#db2777', '#9333ea', '#ea580c', '#0891b2']; // couleurs cycliques par catégorie (la FAQ est désormais un menu unique)
      $ci = 0;
    ?>
    <?php foreach ($docNav as $catLabel => $cat):
      $cc = $catColors[$ci % count($catColors)]; $ci++;
      $catActive = ($catLabel === $crumbCat); // catégorie de la page courante → ouverte
    ?>
      <div class="doc-cat-block<?= $catActive ? ' open is-active-cat' : '' ?>">
        <button type="button" class="doc-cat-title" style="color:<?= $cc ?>;background:<?= $cc ?>14" aria-expanded="<?= $catActive ? 'true' : 'false' ?>">
          <span class="ic"><?= $cat['icon'] ?></span> <span class="doc-cat-name"><?= $catLabel ?></span>
          <span class="doc-cat-chevron">›</span>
        </button>
        <div class="doc-cat-pages" style="border-left:2px solid <?= $cc ?>40">
        <?php foreach ($cat['pages'] as $file => [$label, $sections]): $active = ($file === $docPage); ?>
          <a class="doc-pg<?= $active ? ' on' : '' ?>" href="<?= $file ?>" data-label="<?= $h(strip_tags($label)) ?>"<?= $active ? ' style="color:' . $cc . ';background:' . $cc . '1f;box-shadow:inset 2px 0 0 ' . $cc . '"' : '' ?>><?= $label ?></a>
          <?php if ($active && $sections): ?>
          <div class="doc-sub" style="border-left-color:<?= $cc ?>55">
            <?php foreach ($sections as [$anchor, $slabel]): ?>
              <a href="<?= $h($file) ?>#<?= $anchor ?>" data-sec="<?= $anchor ?>" data-label="<?= $h(strip_tags($slabel)) ?>"><?= $slabel ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </nav>

  <main class="doc-main">
    <div class="doc-crumb">
      <a href="documentation.php">Docs</a>
      <?php if ($crumbCat): ?><span class="sep">›</span><span><?= $crumbCat ?></span><?php endif; ?>
      <?php if ($crumbPage): ?><span class="sep">›</span><span class="cur"><?= $crumbPage ?></span><?php endif; ?>
    </div>
