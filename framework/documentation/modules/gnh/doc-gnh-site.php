<?php
/**
 * documentation/modules/gnh/doc-gnh-site.php — Site public, thèmes et contenus.
 */
$docPage = 'modules/gnh/doc-gnh-site.php';
$seo = [
    'title'     => 'GameNodeHosting — Site public & thèmes · Documentation Aegis Framework',
    'desc'      => "Le site visiteur de GameNodeHosting : trois surfaces de thèmes, options par page, pages système et légales, navigation, FAQ, vitrine des serveurs interrogés en direct, SEO et page 404.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gnh/doc-gnh-site.php',
];
require __DIR__ . '/../../inc/head.php';

$code_surfaces = <<<'TXT'
Views/client/
├── site/          le site visiteur : accueil, offres, jeux, FAQ, pages
│   └── default/
├── panel/         l'espace client : serveurs, factures, profil, sessions
│   └── default/
└── gamepanel/     le panel d'UN serveur : config, extensions, V.E.G.A
    └── default/
TXT;
?>

    <h1>Site public &amp; thèmes</h1>
    <p class="doc-lead">GameNodeHosting ne rend aucune page en PHP côté visiteur. Tout passe par des <strong>thèmes en HTML pur</strong>, répartis sur <strong>trois surfaces indépendantes</strong> — parce qu'une vitrine commerciale et un panel de gestion n'ont pas les mêmes besoins.</p>
    <div class="doc-meta">
      <span class="doc-pill">3 surfaces</span>
      <span class="doc-pill">HTML sans PHP</span>
      <span class="doc-pill">Options par page</span>
    </div>

    <h2 id="gnhs-surfaces">Les trois surfaces</h2>
    <p>Chaque surface a son <strong>propre thème actif</strong>, changeable indépendamment des autres.</p>
    <div class="tree"><?= $h($code_surfaces) ?></div>
    <table class="doc-table">
      <tr><th>Surface</th><th>Libellé</th><th>Ce qu'elle habille</th></tr>
      <tr><td><code>site</code></td><td>Site public</td><td>Accueil, offres, fiches jeu, vitrine des serveurs, FAQ, pages, tunnel de commande, 404</td></tr>
      <tr><td><code>panel</code></td><td>Panel client</td><td>Tableau de bord client, serveurs, factures, sessions, profil, tickets</td></tr>
      <tr><td><code>gamepanel</code></td><td>Panel de jeu</td><td>La gestion d'<strong>un</strong> serveur : configuration, extensions, V.E.G.A, fichiers</td></tr>
    </table>
    <div class="callout"><span class="i">🎨</span><div>Vous pouvez donc offrir une vitrine très marketing et un panel très sobre, sans compromis — ce sont deux thèmes distincts, pas deux pages du même.</div></div>

    <h2 id="gnhs-themes">Installer un thème</h2>
    <p>Depuis <em>Réglages → Thèmes</em> : activation, téléversement par ZIP, suppression — le tout par surface. Un thème est du HTML et du JSON, <strong>jamais du PHP</strong> : en téléverser un ne peut donc pas exécuter de code. L'archive est refusée si elle contient une extension interdite ou un chemin remontant, et elle est plafonnée à 20 Mo.</p>

    <h2 id="gnhs-options">Les options de thème</h2>
    <p>Un thème déclare ses propres réglages ; l'administration construit le formulaire à partir de cette déclaration. Aucun écran n'est écrit pour un thème en particulier.</p>
    <p>Huit types d'options sont acceptés : <code>text</code>, <code>textarea</code>, <code>image</code>, <code>color</code>, <code>url</code>, <code>bool</code>, <code>select</code>, <code>number</code>.</p>
    <div class="callout ok"><span class="i">📄</span><div>La particularité de GameNodeHosting : les options sont <strong>rangées par page</strong>. Le thème déclare quels réglages concernent l'accueil, lesquels concernent les offres, la FAQ, le tunnel de commande… L'administrateur règle donc une page à la fois, au lieu de chercher dans une liste unique de deux cents champs.</div></div>
    <p>Les pages configurables sont : accueil, offres, serveurs, fiche jeu, FAQ, tunnel de commande, pages libres et erreurs. Les options acceptent aussi des <strong>groupes d'éléments répétables</strong> (jusqu'à 24), pour les listes d'arguments, de témoignages ou de blocs de réassurance.</p>
    <p>Un bouton de remise à zéro rend au thème ses valeurs d'origine, et les images téléversées qui ne sont plus référencées sont repérées pour ne pas encombrer le disque indéfiniment.</p>

    <h2 id="gnhs-pages">Les pages</h2>
    <p>Deux natures de pages coexistent dans <code>gnh_pages</code>.</p>
    <table class="doc-table">
      <tr><th>Nature</th><th>Exemples</th><th>Règle</th></tr>
      <tr><td><strong>Pages système</strong></td><td>Accueil, Nos offres, Nos serveurs, Récapitulatif, Espace client</td><td><strong>Non supprimables</strong> : leur contenu est produit par le module. Vous pouvez les renommer et les sortir du menu, pas les effacer.</td></tr>
      <tr><td><strong>Pages légales</strong></td><td>Conditions de location, Mentions légales, Politique de confidentialité</td><td>Créées à l'installation, <strong>non supprimables</strong>, mais leur contenu vous appartient entièrement.</td></tr>
      <tr><td><strong>Pages libres</strong></td><td>Ce que vous voulez</td><td>Créées, publiées, dépubliées ou supprimées librement. <code>/hosting/p/{slug}</code></td></tr>
    </table>
    <p>L'éditeur accepte le <strong>Markdown</strong> (capacité du framework) et propose un aperçu avant publication.</p>
    <div class="callout"><span class="i">⚖️</span><div>Les trois pages légales sont semées <strong>vides de votre texte mais présentes</strong> : un site qui vend doit les avoir. Le module ne prétend pas rédiger vos conditions à votre place.</div></div>

    <h2 id="gnhs-menu">La navigation</h2>
    <p>Un écran unique, entièrement en AJAX : création, modification, activation, suppression et <strong>réordonnancement</strong> par glisser-déposer. Chaque entrée choisit son emplacement — <code>header</code>, <code>footer</code> ou <code>both</code> — et sa cible (<code>_self</code> ou <code>_blank</code>).</p>

    <h2 id="gnhs-faq">La FAQ</h2>
    <p>Questions et réponses réordonnables, activables une à une, affichées sur <code>/hosting/faq</code>. Même écran AJAX que la navigation.</p>

    <h2 id="gnhs-vitrine">La vitrine des serveurs</h2>
    <p>C'est l'argument de vente le plus honnête du module : <strong>un futur client juge mieux sur pièces que sur promesses</strong>. La page <code>/hosting/serveurs</code> montre les serveurs déjà hébergés, leur jeu, leur adresse — et permet de s'y connecter pour juger la latence.</p>
    <p>Le moteur de protocoles est <strong>embarqué</strong> dans le module (dossier <code>Viewer/</code>) : la vitrine n'exige pas l'installation d'un second module. Sont pris en charge Minecraft, Quake 3, Call of Duty 4, FiveM, Mumble et Discord.</p>
    <table class="doc-table">
      <tr><th>Précaution</th><th>Pourquoi</th></tr>
      <tr><td>Seuls les serveurs <strong>en ligne et actifs</strong> sont montrés</td><td>Une vitrine pleine de serveurs éteints dessert plus qu'elle ne sert.</td></tr>
      <tr><td>Un <strong>cache de quelques minutes</strong>, partagé par tous les visiteurs</td><td>Sans lui, chaque visite ouvrirait un socket par serveur — cent visiteurs simultanés suffiraient à faire tomber la page.</td></tr>
      <tr><td>La page est <strong>publique et sans authentification</strong></td><td>Rien de ce qui identifie le propriétaire n'y figure.</td></tr>
    </table>

    <h2 id="gnhs-seo">SEO &amp; réseaux sociaux</h2>
    <p>Un onglet de réglages dédié alimente la capacité <strong>SEO</strong> du framework : titre, description, image de partage. Un onglet <strong>Social</strong> gère les liens de vos réseaux, et le logo du site se téléverse depuis les réglages.</p>

    <h2 id="gnhs-404">La page 404</h2>
    <p>Toute adresse inconnue sous le préfixe public tombe sur la 404 <strong>du thème</strong>.</p>
    <div class="callout warn"><span class="i">🚧</span><div>
      Sans ce repli, une adresse fautive tombait sur la page d'erreur du framework : écran noir sans le thème du site, et un bouton « Retour au dashboard » proposé à un visiteur <strong>qui n'est pas administrateur</strong>. Les routes de repli sont enregistrées <strong>en dernier</strong> — le routeur retient la première route qui correspond, toutes les autres gardent donc la main. Et elles sont en <strong>GET seulement</strong> : l'envoi d'un formulaire vers une adresse inconnue doit continuer de remonter plutôt que d'être avalé en silence.
    </div></div>

    <div class="doc-foot">
      <span>GameNodeHosting · site public</span>
      <span><a href="modules/gnh/doc-gnh-admin.php">Administration &amp; réglages →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
