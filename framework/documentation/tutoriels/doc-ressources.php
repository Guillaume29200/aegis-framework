<?php
$docPage = 'tutoriels/doc-ressources.php';
$seo = [
    'title' => 'Ressources & limites par hôte — Documentation · GameNodePanel',
    'desc'  => "Allouer des cœurs CPU et de la RAM dédiés à un serveur de jeu, réserver des ressources pour l'OS, et limiter le nombre de slots maximum par serveur hôte pour éviter la surcharge. Méthode de dimensionnement et exemples par jeu (CS 1.6, CS2, Minecraft, Windrose).",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-ressources.php',
];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Ressources &amp; limites par serveur hôte</h1>
    <p class="doc-lead">Le bloc <strong>« 🖥️ Ressources &amp; limites par serveur dédié »</strong> (page <em>Configuration GNP</em>) permet, <strong>pour chaque serveur hôte</strong>, de réserver des ressources pour l'OS, d'allouer des cœurs/RAM <strong>dédiés</strong> aux serveurs de jeu, et de fixer un <strong>nombre de slots maximum</strong>. Objectif : <strong>plus de performance</strong> et un hôte <strong>jamais surchargé</strong>.</p>
    <div class="doc-meta"><span class="doc-pill">Par hôte</span><span class="doc-pill">CPU / RAM dédiés</span><span class="doc-pill">Slots max</span><span class="doc-pill">Anti-surcharge</span></div>

    <h2 id="res-tldr">L'essentiel en 30 secondes</h2>
    <div class="callout ok"><span class="i">🎯</span><div>
      Vous gérez <strong>combien de puissance</strong> chaque hôte met à disposition, et <strong>comment</strong> elle est partagée :
      <strong>réserver</strong> une part pour l'OS, <strong>partager</strong> le reste entre vos serveurs de jeu (mutualisé) ou en
      <strong>dédier</strong> une part à un serveur précis, et fixer une <strong>limite de joueurs</strong> pour ne jamais saturer la machine.
    </div></div>

    <p><strong>L'analogie de l'immeuble :</strong> votre serveur hôte est un immeuble.</p>
    <ul>
      <li>🧹 <strong>Les parties communes</strong> (ménage, ascenseur) = les <em>ressources réservées à l'OS</em>. On ne les loue jamais.</li>
      <li>🛋️ <strong>La colocation</strong> = le <em>mutualisé</em> : plusieurs serveurs partagent les mêmes pièces. Souple, mais un coloc bruyant gêne les autres.</li>
      <li>🚪 <strong>Le studio privé</strong> = le <em>dédié</em> : un serveur a ses propres murs (cœurs/RAM réservés). Tranquille et stable, mais ça occupe de la place.</li>
      <li>🪧 <strong>La capacité affichée</strong> = les <em>slots max</em> : on n'accueille pas plus de monde que ce que l'immeuble tient confortablement.</li>
    </ul>

    <h3 id="res-tldr-3">Les 3 réglages en un coup d'œil</h3>
    <table class="doc-table">
      <tr><th>Réglage</th><th>Son rôle</th><th>Quand y toucher</th></tr>
      <tr><td><strong>Cœurs / RAM réservés (OS)</strong></td><td>Mettre de côté de quoi faire tourner le système</td><td>Une fois, à la config de l'hôte</td></tr>
      <tr><td><strong>CPU / RAM dédiés</strong></td><td>Garantir de la puissance à un serveur précis</td><td>À la création d'un serveur exigeant</td></tr>
      <tr><td><strong>Slots max</strong></td><td>Plafonner le total de joueurs de l'hôte</td><td>Une fois, selon la puissance de la machine</td></tr>
    </table>
    <p class="doc-lead" style="font-size:.95rem">👇 Le reste de la page détaille chaque point, avec une méthode de dimensionnement et des exemples par jeu.</p>

    <h2 id="res-intro">Dédié vs mutualisé</h2>
    <p>Par défaut, les serveurs de jeu d'un même hôte partagent ses ressources (<strong>mutualisation</strong>) : c'est souple, mais un serveur gourmand peut pénaliser les autres (lag, tickrate qui chute).</p>
    <p>En <strong>dédié</strong>, on réserve un nombre de <strong>cœurs CPU</strong> et une quantité de <strong>RAM</strong> exclusifs à un serveur de jeu. Il ne partage plus ces ressources : performances stables et isolées. Idéal pour les serveurs exigeants ou « vitrines » (gros Minecraft moddé, serveur compétitif, etc.).</p>
    <table class="doc-table">
      <tr><th></th><th>Mutualisé</th><th>Dédié</th></tr>
      <tr><td>Performance</td><td>Variable (dépend des voisins)</td><td>Stable, garantie</td></tr>
      <tr><td>Capacité de l'hôte</td><td>Maximale (densité)</td><td>Réduite (ressources bloquées)</td></tr>
      <tr><td>Idéal pour</td><td>Petits serveurs, tests</td><td>Serveurs exigeants / full H24</td></tr>
    </table>

    <h2 id="res-reserves">Réserves OS &amp; calcul du disponible</h2>
    <p>Un serveur hôte ne doit <strong>jamais</strong> être alloué à 100 % : l'OS, SSH, FTP, les bases de données et les services système ont besoin d'air. Le champ <strong>« cœurs réservés / RAM réservée »</strong> met ces ressources de côté.</p>
    <p>Le <strong>disponible</strong> proposé à l'allocation se calcule ainsi :</p>
    <div class="callout"><span class="i">🧮</span><div><strong>Disponible = Total de l'hôte − Réservé (OS) − Déjà alloué aux autres serveurs.</strong> Quand il tombe à 0, GNP n'autorise plus que la mutualisation (et l'indique clairement à la création d'un serveur).</div></div>
    <p>Laisser un champ <strong>vide</strong> = l'hôte hérite du défaut global. Exemple sain : sur un 8 cœurs / 32 Go, réserver <strong>2 cœurs</strong> et <strong>4 Go</strong> pour le système.</p>

    <h2 id="res-dedie">Allouer du CPU / RAM dédié</h2>
    <p>À la <strong>création d'un serveur de jeu</strong>, si l'hôte dispose de ressources dédiées libres, vous pouvez choisir le nombre de cœurs et la RAM à lui réserver. Le serveur tourne alors avec une <strong>affinité CPU</strong> (taskset) et/ou une <strong>limite mémoire</strong> dédiée.</p>
    <ul>
      <li><strong>CPU dédié</strong> → tickrate régulier, pas de « vol » de cœur par un voisin. Crucial pour les FPS (CS, FiveM) et les serveurs compétitifs.</li>
      <li><strong>RAM dédiée</strong> → pas d'OOM ni de swap quand un autre serveur gonfle. Crucial pour Minecraft moddé, Conan, Palworld…</li>
    </ul>
    <div class="callout"><span class="i">💡</span><div>Sur un gros serveur dédié, séparer 2–3 « gros » jeux en dédié et laisser le reste en mutualisé est souvent le meilleur compromis densité / performance.</div></div>

    <h2 id="res-slots">Slots maximum par hôte</h2>
    <p>Le champ <strong>« Slots max »</strong> plafonne le nombre total de joueurs que l'hôte accepte, tous serveurs de jeu confondus. GNP refuse la création/installation d'un serveur si la somme des slots dépasse ce plafond.</p>
    <p>Le but : <strong>éviter de promettre plus de joueurs que la machine ne peut en tenir</strong> en restant fluide. C'est un garde-fou commercial <em>et</em> technique : mieux vaut 800 slots qui tournent parfaitement que 1500 qui lag.</p>

    <h2 id="res-dimensionner">Comment dimensionner les slots ?</h2>
    <p>Il n'existe <strong>pas de chiffre magique</strong> : le bon plafond dépend de plusieurs facteurs combinés.</p>
    <table class="doc-table">
      <tr><th>Facteur</th><th>Impact</th></tr>
      <tr><td><strong>Le jeu</strong></td><td>Énorme. CS 1.6 (léger) ≠ CS2 ou Minecraft moddé (très lourds).</td></tr>
      <tr><td><strong>Fréquence CPU (GHz)</strong></td><td>Décisif : la plupart des serveurs de jeu sont <em>mono-thread</em>. Un 4,5 GHz écrase un 2 GHz à nombre de cœurs égal.</td></tr>
      <tr><td><strong>Nombre de cœurs</strong></td><td>Permet de faire tourner <em>plus de serveurs en parallèle</em> (pas forcément un serveur « plus gros »).</td></tr>
      <tr><td><strong>RAM</strong></td><td>Plafond dur : un serveur qui manque de RAM swappe ou crash.</td></tr>
      <tr><td><strong>Plugins / mods par serveur</strong></td><td>Chaque plugin ajoute du coût CPU/RAM par tick. 50 plugins ≠ vanilla.</td></tr>
      <tr><td><strong>Taux de remplissage H24</strong></td><td>10 serveurs <em>full 24/7</em> ≠ 10 serveurs à moitié vides. On dimensionne sur la charge réelle attendue.</td></tr>
      <tr><td><strong>Tickrate visé</strong></td><td>Un serveur 128-tick coûte bien plus qu'un 64-tick.</td></tr>
    </table>
    <div class="callout warn"><span class="i">⚠️</span><div>Le matériel prime sur le chiffre. On ne met pas 1000 slots max sur un <strong>Intel 2 GHz / 2 cœurs</strong> comme sur un <strong>4,5 GHz / 12 cœurs</strong> — même jeu, capacités sans commune mesure.</div></div>

    <h2 id="res-exemples">Exemples concrets</h2>
    <p>À titre <strong>indicatif</strong> (à ajuster selon votre matériel et vos plugins) :</p>

    <h3>Counter-Strike 1.6 — très léger</h3>
    <p>Sur un <strong>8 cœurs / 32 Go</strong> dédié uniquement à du CS 1.6, on peut viser <strong>~1000 slots</strong> au total répartis sur plusieurs serveurs — <em>mais</em> ça dépend du nombre de plugins par serveur et de combien seront full H24. Plus de plugins ou plus de serveurs pleins → on baisse le plafond.</p>

    <h3>Counter-Strike 2 — très gourmand</h3>
    <p>Configuration minimale d'un serveur : <strong>4 threads CPU</strong> (≈ i5 750+), <strong>8 Go RAM</strong>, <strong>85 Go</strong> de stockage. Valve ne donne pas de « nombre de joueurs » car un serveur CS2 vise généralement de petits effectifs (5v5 / 64-128 tick) — la consommation est par <em>serveur</em>, pas par joueur. On dimensionne donc en « combien de serveurs CS2 par hôte », pas en milliers de slots.</p>

    <h3>Windrose — barème par joueurs</h3>
    <table class="doc-table">
      <tr><th>Joueurs</th><th>CPU</th><th>RAM</th><th>Stockage</th></tr>
      <tr><td>2 joueurs</td><td>Xeon Scalable, 2 cœurs, 3,2 GHz</td><td>8 Go</td><td>35 Go SSD</td></tr>
      <tr><td>4 joueurs</td><td>Xeon Scalable, 2 cœurs, 3,2 GHz</td><td>12 Go</td><td>35 Go SSD</td></tr>
      <tr><td>10 joueurs</td><td>Xeon Scalable, 2 cœurs, 3,2 GHz</td><td>16 Go</td><td>35 Go SSD</td></tr>
    </table>
    <p>Ici, c'est surtout la <strong>RAM</strong> qui grimpe avec le nombre de joueurs — typique des jeux de type survie/sandbox (chargement de monde, entités).</p>

    <h3>Minecraft — variable selon mods</h3>
    <p>Un vanilla 20 joueurs tient dans 2–4 Go ; un gros modpack 20 joueurs peut exiger 8–12 Go et un cœur rapide à lui seul. <strong>Réserver de la RAM dédiée</strong> est ici quasi obligatoire pour éviter qu'un pic GC d'un serveur fasse lagger les autres.</p>

    <h2 id="res-methode">Méthode recommandée</h2>
    <ol>
      <li><strong>Connaître l'hôte</strong> : fréquence CPU, nb de cœurs, RAM, disque. C'est la base de tout.</li>
      <li><strong>Réserver l'OS</strong> (ex. 2 cœurs / 4 Go) avant toute allocation.</li>
      <li><strong>Estimer le coût d'un serveur type</strong> pour vos jeux (RAM + 1 thread rapide), plugins inclus.</li>
      <li><strong>Partir bas, mesurer, ajuster</strong> : surveillez CPU/RAM réels via <a href="modules/gnp/doc-odin.php">O.D.I.N</a> et <a href="modules/gnp/doc-vega.php">VEGA</a>, puis remontez le plafond de slots si la marge le permet.</li>
      <li><strong>Dédier les gros serveurs</strong>, mutualiser le reste.</li>
    </ol>
    <div class="callout"><span class="i">✅</span><div>Règle d'or : un hôte <strong>stable à 70 % de charge en pic</strong> vaut mieux qu'un hôte saturé. La marge absorbe les events, les redémarrages et les serveurs qui se remplissent en même temps.</div></div>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
