<?php
/**
 * documentation/modules/gne/doc-gne-equipes.php — Jeux, équipes, joueurs, matchs.
 */
$docPage = 'modules/gne/doc-gne-equipes.php';
$seo = [
    'title'     => 'GameNodeEsport — Équipes, joueurs & matchs · Documentation Aegis Framework',
    'desc'      => "Le cœur compétitif de GameNodeEsport : jeux, équipes et roster, fiches joueurs, configurations PC, marques de matériel, calendrier des matchs, résultats et statistiques de forme.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/gne/doc-gne-equipes.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>Équipes, joueurs &amp; matchs</h1>
    <p class="doc-lead">C'est le cœur du module : qui joue, à quoi, contre qui, et avec quel résultat. Tout le reste — actualités, boutique, recrutement — tourne autour de ces quatre objets.</p>
    <div class="doc-meta">
      <span class="doc-pill">Roster par jeu</span>
      <span class="doc-pill">Calendrier &amp; résultats</span>
      <span class="doc-pill">Courbe de forme</span>
    </div>

    <h2 id="gnee-jeux">Les jeux</h2>
    <p>Tout part de là. Un jeu porte son nom, son visuel et son identifiant d'URL. Il sert ensuite à ranger les équipes, à filtrer les matchs, et à organiser la page des membres.</p>
    <div class="callout"><span class="i">🎮</span><div>Une communauté multi-gaming déclare autant de jeux qu'elle en pratique. Une équipe mono-jeu n'en déclare qu'un : les filtres disparaissent d'eux-mêmes quand il n'y a rien à filtrer.</div></div>

    <h2 id="gnee-equipes">Les équipes</h2>
    <p>Une équipe appartient à un jeu et porte son roster. L'écran d'administration liste, recherche et filtre par jeu ; chaque équipe se modifie et gère ses joueurs depuis une page dédiée.</p>
    <p>Côté visiteur, deux pages : la <strong>liste des équipes</strong> et la <strong>fiche d'une équipe</strong> — avec son roster, son bilan, son classement et ses dernières rencontres.</p>

    <h2 id="gnee-joueurs">Les joueurs</h2>
    <p>Un joueur est une fiche : pseudo, identité, rôle dans l'équipe, avatar, réseaux. Il peut être rattaché à un compte inscrit, ou rester une simple fiche.</p>
    <p>La <strong>fiche publique</strong> assemble l'identité, l'équipe, la configuration PC et les derniers matchs de l'équipe. Elle est en lecture seule : la saisie passe par l'administration ou par l'espace membre.</p>

    <h3 id="gnee-annuaire">L'annuaire des membres</h3>
    <p>Un « membre » est une fiche joueur — c'est là que vivent les profils, qu'ils soient rattachés à une équipe ou non.</p>
    <div class="callout ok"><span class="i">👥</span><div>Les comptes inscrits qui <strong>n'ont pas encore rempli leur profil</strong> sont listés à la suite. L'annuaire reflète ainsi la communauté réelle, et pas seulement les joueurs déjà en équipe — c'est une nuance qui change tout pour une communauté ouverte.</div></div>
    <p>L'annuaire se filtre par jeu et par équipe, se trie, et signale qui est en ligne.</p>

    <h2 id="gnee-config">Les configurations PC</h2>
    <p>Une fiche matériel par joueur : processeur, carte graphique, mémoire, écran, périphériques. Le joueur peut la remplir lui-même depuis son espace membre (<code>/mon-compte/config-pc</code>).</p>
    <p>C'est un classique du site d'équipe — le public veut savoir sur quoi jouent les joueurs — et c'est aussi un support de partenariat.</p>

    <h3 id="gnee-marques">Les marques de matériel</h3>
    <p>Un référentiel de marques, avec leur logo. Les configurations PC s'y rattachent, ce qui permet d'afficher les logos des équipementiers sur les fiches joueurs — et de valoriser un sponsor matériel sans le saisir vingt fois.</p>

    <h2 id="gnee-matchs">Les matchs</h2>
    <p>Calendrier et résultats dans le même objet. Un match porte son jeu, son équipe, son adversaire, sa date, sa compétition et son score. L'administration filtre par jeu, par équipe et par résultat.</p>
    <p>Côté visiteur : la page des matchs, avec le prochain match mis en avant, et le détail des rencontres passées.</p>

    <h3 id="gnee-libelles">Les libellés d'agenda</h3>
    <div class="callout"><span class="i">🕐</span><div>
      Le moteur de gabarits <strong>ne sait pas comparer deux dates</strong> : « Aujourd'hui » ou « Dans 2 h 15 » ne peuvent pas se déduire côté thème. Ces libellés sont donc calculés en PHP, dans un service unique partagé par l'accueil et par la page des matchs. Les calculer dans chaque contrôleur les aurait fait diverger à la première retouche.
    </div></div>

    <h3 id="gnee-stats">Les statistiques</h3>
    <p>Bilan (victoires / nuls / défaites), moyennes, série en cours et <strong>courbe de forme</strong>.</p>
    <div class="callout ok"><span class="i">📈</span><div>
      La courbe est fournie au thème sous forme de <strong>coordonnées déjà calculées</strong>, qu'il n'a qu'à poser dans un <code>&lt;svg&gt;</code>. Aucune bibliothèque de graphiques, aucun JavaScript — et surtout : <strong>un thème créé par un client reste du HTML</strong>. C'est la même règle que partout dans le module, appliquée jusqu'au graphique.
    </div></div>

    <h2 id="gnee-zero">Le piège du zéro</h2>
    <div class="callout warn"><span class="i">⚠️</span><div>
      Un détail qui a coûté un bug réel : un score de <strong>0</strong> est « faux » pour le moteur de gabarits. Écrit naïvement, <code>0:2</code> s'affichait <code>–:2</code>. Les scores sont donc envoyés au thème sous forme de <strong>libellés préparés en PHP</strong> (<code>score_us_label</code>, <code>score_opponent_label</code>). Si vous écrivez un thème, utilisez ces clés et non les valeurs brutes — voir <a href="framework/doc-templating.php#tpl-traps">les pièges du moteur</a>.
    </div></div>

    <div class="doc-foot">
      <span>GameNodeEsport · compétition</span>
      <span><a href="modules/gne/doc-gne-contenu.php">Contenus &amp; communauté →</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
