<?php
/**
 * documentation/modules/aegishost/doc-aegishost-comparatif.php — AegisHost face à aaPanel et CyberPanel.
 */
$docPage = 'modules/aegishost/doc-aegishost-comparatif.php';
$seo = [
    'title'     => 'AegisHost face à aaPanel et CyberPanel · Documentation',
    'desc'      => "Comparatif honnête entre AegisHost, aaPanel et CyberPanel : empreinte mémoire, surface d'attaque, architecture des privilèges, fonctionnalités couvertes et modèle tarifaire. Y compris ce qu'AegisHost ne sait pas faire.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-comparatif.php',
];
require __DIR__ . '/../../inc/head.php';
?>

    <h1>AegisHost face à aaPanel et CyberPanel</h1>
    <p class="doc-lead">AegisHost n'a jamais cherché à concurrencer aaPanel ni CyberPanel. Il a été écrit pour répondre à une autre question : <strong>que se passe-t-il si l'on ne garde que ce dont on se sert vraiment ?</strong> Cette page compare ce qui est comparable, et dit franchement où les deux autres restent devant.</p>
    <div class="doc-meta">
      <span class="doc-pill">comparatif</span>
      <span class="doc-pill">3,24 Mo · 138 fichiers</span>
      <span class="doc-pill">sources officielles</span>
      <span class="doc-pill">mis à jour : août 2026</span>
    </div>

    <style>
    .cmp-tbl{width:100%;border-collapse:collapse;margin:18px 0;font-size:.88rem}
    .cmp-tbl th,.cmp-tbl td{padding:10px 12px;border:1px solid var(--bd);vertical-align:top;text-align:left}
    .cmp-tbl thead th{background:var(--bg3);font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:var(--tx2)}
    .cmp-tbl thead th.us{color:var(--ac)}
    .cmp-tbl td.us{background:var(--ac-bg)}
    .cmp-tbl tbody th{background:var(--bg2);font-weight:700;width:23%;color:var(--tx)}
    .cmp-wrap{overflow-x:auto}
    .cmp-y{color:#16a34a;font-weight:800}
    .cmp-n{color:var(--tx3);font-weight:700}
    [data-theme="dark"] .cmp-y{color:#4ade80}
    .cmp-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px;margin:18px 0 6px}
    .cmp-card{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:15px 16px}
    .cmp-card .ti{font-weight:800;font-size:.97rem;margin-bottom:5px;display:flex;align-items:center;gap:7px}
    .cmp-card .de{font-size:.85rem;color:var(--tx2);line-height:1.58}
    .cmp-card.win{border-color:color-mix(in srgb,var(--ac) 40%,var(--bd))}
    .cmp-src{font-size:.78rem;color:var(--tx3);line-height:1.6;margin-top:6px}
    .cmp-src a{color:var(--tx2);text-decoration:underline}
    .cmp-split{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:16px 0}
    @media(max-width:720px){.cmp-split{grid-template-columns:1fr}}
    </style>

    <div class="callout"><span class="i">⚖️</span><div><strong>Comment cette page a été écrite.</strong> Les chiffres concernant aaPanel et CyberPanel viennent de <strong>leur documentation officielle</strong> et d'avis de sécurité publics, tous liés en bas de page. Ceux qui concernent AegisHost sont des <strong>mesures faites sur une machine réelle</strong>, et signalées comme telles. Aucune valeur n'est estimée ni extrapolée : quand une donnée n'est pas publiée, la case le dit.</div></div>

    <h2 id="cp-position">Trois projets, trois intentions</h2>
    <p>Comparer ces trois panneaux sans préciser ce que chacun cherche à faire n'aurait aucun sens — c'est ce qui explique presque toutes les différences qui suivent.</p>

    <div class="cmp-cards">
      <div class="cmp-card">
        <div class="ti">🅰️ aaPanel</div>
        <div class="de">Panneau généraliste chinois, gratuit avec des extensions payantes, très large en fonctionnalités : LAMP/LEMP, conteneurs, magasin de greffons, gestion multi-sites poussée. Il vise <strong>tous les usages</strong>, du particulier à l'hébergeur.</div>
      </div>
      <div class="cmp-card">
        <div class="ti">🌀 CyberPanel</div>
        <div class="de">Panneau bâti autour d'<strong>OpenLiteSpeed</strong>, orienté performance web et hébergement mutualisé : comptes revendeurs, quotas, e-mail, DNS intégrés. Une version Entreprise ajoute LiteSpeed Enterprise.</div>
      </div>
      <div class="cmp-card win">
        <div class="ti">🌐 AegisHost</div>
        <div class="de">Module d'Aegis Framework, pensé pour <strong>une seule machine et une seule personne</strong> : celle qui administre son VPS. Il couvre ce que 99 % des utilisateurs de VPS font réellement, et rien au-delà. Ce n'est pas un panneau d'hébergeur.</div>
      </div>
    </div>

    <h2 id="cp-ram">L'empreinte mémoire</h2>
    <p>C'est le point de départ du projet. Un VPS à 5 € par mois offre souvent 1 ou 2 Go de mémoire, et il est difficile d'accepter qu'une part importante parte dans l'outil censé <em>administrer</em> le serveur plutôt que dans ce qu'il héberge.</p>

    <div class="cmp-wrap">
    <table class="cmp-tbl">
      <thead><tr><th></th><th class="us">AegisHost</th><th>aaPanel</th><th>CyberPanel</th></tr></thead>
      <tbody>
        <tr>
          <th>Mémoire minimale annoncée</th>
          <td class="us">Aucune exigence propre : le module vit dans une administration Aegis Framework déjà en place</td>
          <td>512 Mo minimum, 1 Go recommandé</td>
          <td>1 Go minimum, 2 Go recommandé</td>
        </tr>
        <tr>
          <th>Poids du panneau lui-même</th>
          <td class="us"><strong>3,24 Mo</strong>, archive d'installation comprise — 2,69 Mo sans elle, dont 392 Ko pour l'agent</td>
          <td>Non publié</td>
          <td>Non publié</td>
        </tr>
        <tr>
          <th>Disque minimal annoncé</th>
          <td class="us">Aucune exigence propre</td>
          <td>1 Go</td>
          <td>10 Go</td>
        </tr>
        <tr>
          <th>Service permanent ajouté</th>
          <td class="us"><span class="cmp-n">Aucun</span> — l'agent est un script lancé à la demande, puis terminé</td>
          <td>Service Python permanent</td>
          <td>Service Python/Django permanent + base interne</td>
        </tr>
      </tbody>
    </table>
    </div>

    <div class="callout ok"><span class="i">📏</span><div><strong>Mesure sur machine réelle, côté AegisHost.</strong> Sur un serveur Debian faisant tourner <em>simultanément</em> Apache, MariaDB, Fail2ban, la pile de messagerie, Docker (démon seul, hors conteneurs) et le panneau, la consommation observée reste <strong>sous les 500 Mo</strong>. Cette valeur est une mesure sur une configuration donnée, pas une promesse contractuelle : elle dépend de vos sites, de vos bases et de vos conteneurs.</div></div>

    <p>La raison de cet écart est structurelle, et non le fruit d'une optimisation : <strong>AegisHost n'ajoute aucun service permanent</strong>. Il n'y a pas de démon de panneau qui tourne en permanence en attendant qu'on s'y connecte. Les pages sont servies par le PHP qui sert déjà votre site, et les opérations privilégiées passent par un script qui démarre, fait son travail et s'arrête. Un panneau qui ne tourne pas ne consomme rien.</p>

    <div class="callout warn"><span class="i">🔍</span><div><strong>Soyons précis sur un point souvent mal cité.</strong> aaPanel documente une empreinte modeste pour <em>le panneau seul</em>. Les chiffres élevés que l'on voit circuler correspondent à la <em>pile complète</em> installée par le panneau — serveur web, base de données, PHP, service du panneau, greffons — ce qui n'est pas la même mesure. La comparaison ci-dessus porte sur les exigences officiellement publiées, qui sont, elles, directement comparables.</div></div>

    <h2 id="cp-surface">La surface d'attaque</h2>
    <p>C'est la différence la plus importante de cette page, et la moins visible dans une grille de fonctionnalités.</p>

    <p>aaPanel et CyberPanel installent chacun <strong>un service web supplémentaire, qui écoute sur un port dédié, accessible depuis Internet</strong> : c'est par là qu'on rejoint le panneau. Cela signifie qu'un serveur qui en est équipé est <em>découvrable</em> : n'importe qui peut balayer l'Internet à la recherche de ce port et dresser la liste des machines concernées.</p>

    <p>Ce n'est pas une inquiétude théorique. En octobre 2024, une faille d'exécution de code <strong>sans authentification préalable</strong> dans CyberPanel (CVE-2024-51378, score CVSS 9.8) a été exploitée en masse par plusieurs rançongiciels — PSAUX, C3RB3R et une variante de Babuk. Le moteur de recherche Censys recensait alors <strong>près de 61 000 interfaces CyberPanel exposées</strong>, et plus de 22 000 systèmes ont été compromis. aaPanel connaît sa propre suite d'avis de sécurité, dont des exécutions de code à distance sans authentification.</p>

    <div class="cmp-wrap">
    <table class="cmp-tbl">
      <thead><tr><th></th><th class="us">AegisHost</th><th>aaPanel</th><th>CyberPanel</th></tr></thead>
      <tbody>
        <tr>
          <th>Port de panneau exposé</th>
          <td class="us"><span class="cmp-y">Aucun</span> — le panneau est une section de votre administration existante</td>
          <td>Port dédié (31750 par défaut sur les versions récentes)</td>
          <td>Port 8090</td>
        </tr>
        <tr>
          <th>Découvrable par balayage Internet</th>
          <td class="us"><span class="cmp-y">Non</span> : rien ne distingue le serveur d'un site web ordinaire</td>
          <td>Oui</td>
          <td>Oui — près de 61 000 instances recensées par Censys en 2024</td>
        </tr>
        <tr>
          <th>Authentification</th>
          <td class="us">Celle de votre CMS (module Auth), avec les rôles déjà en place</td>
          <td>Compte panneau dédié + chemin d'accès secret</td>
          <td>Compte panneau dédié</td>
        </tr>
        <tr>
          <th>Le composant web tourne-t-il en root ?</th>
          <td class="us"><span class="cmp-y">Non.</span> PHP n'a aucun privilège ; tout passe par un agent signé à liste d'actions fermée</td>
          <td colspan="2" style="text-align:center">Le service du panneau dispose des privilèges nécessaires à l'administration système</td>
        </tr>
      </tbody>
    </table>
    </div>

    <div class="callout"><span class="i">🧭</span><div><strong>Une nuance qu'il faut poser.</strong> Ne pas exposer de port n'est pas en soi un gage de robustesse, et personne ne devrait vous vendre le secret comme une protection. Ce qui compte ici est différent et mesurable : <strong>il n'y a pas de second service à maintenir, à mettre à jour et à surveiller</strong>. La surface exposée d'AegisHost est celle de votre site — que vous protégez déjà — et non celle de votre site <em>plus</em> celle d'un panneau. Une faille dans un panneau qu'aucun scanner ne peut trouver n'est pas exploitable en masse ; c'est cette exploitation de masse, et non l'attaque ciblée, qui fait les campagnes de rançongiciel décrites plus haut.</div></div>

    <p>S'ajoute à cela le choix d'architecture décrit dans la <a href="modules/aegishost/doc-aegishost-security.php">partie 7</a> : le panneau <strong>nomme une action</strong> dans une liste fermée, il n'envoie jamais de ligne de commande, les secrets passent par l'entrée standard plutôt que par les arguments, et l'agent revalide tout de son côté. Le panneau ne touche notamment <strong>jamais</strong> à la socket Docker, dont l'accès équivaut à être root.</p>

    <h2 id="cp-feat">Les fonctionnalités</h2>
    <p>C'est ici qu'aaPanel et CyberPanel prennent l'avantage, et c'est <strong>assumé</strong>. AegisHost ne couvre que ce qu'un administrateur de VPS utilise réellement.</p>

    <div class="cmp-wrap">
    <table class="cmp-tbl">
      <thead><tr><th>Fonction</th><th class="us">AegisHost</th><th>aaPanel</th><th>CyberPanel</th></tr></thead>
      <tbody>
        <tr><th>Sites web &amp; vhosts</th><td class="us"><span class="cmp-y">Oui</span></td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Version de PHP par site</th><td class="us"><span class="cmp-y">Oui</span> (8.1 → 8.5)</td><td>Oui</td><td>Oui</td></tr>
        <tr><th>HTTPS Let's Encrypt</th><td class="us"><span class="cmp-y">Oui</span>, avec compte à rebours d'expiration</td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Bases de données</th><td class="us"><span class="cmp-y">Oui</span></td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Comptes FTP</th><td class="us"><span class="cmp-y">Oui</span></td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Docker</th><td class="us"><span class="cmp-y">Oui</span> — catalogue de 15 applications prêtes</td><td>Oui (greffon)</td><td>Oui (Docker Manager)</td></tr>
        <tr><th>Messagerie</th><td class="us"><span class="cmp-y">Oui</span> — Postfix + Dovecot</td><td>Oui (greffon)</td><td>Oui, intégrée</td></tr>
        <tr><th>Sauvegardes locales</th><td class="us"><span class="cmp-y">Oui</span></td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Sauvegardes hors-site S3</th><td class="us"><span class="cmp-y">Oui</span>, chiffrées AES-256 avant l'envoi</td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Pare-feu &amp; Fail2ban</th><td class="us"><span class="cmp-y">Oui</span>, avec géolocalisation des bannis</td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Durcissement SSH avec retour arrière automatique</th><td class="us"><span class="cmp-y">Oui</span></td><td>Partiel</td><td>Partiel</td></tr>
        <tr><th>Reprise d'un serveur déjà en service</th><td class="us"><span class="cmp-y">Oui</span> — scan et adoption des vhosts existants</td><td>Partiel</td><td>Partiel</td></tr>
        <tr><th>Serveur DNS intégré</th><td class="us"><span class="cmp-n">Non</span></td><td>Oui</td><td>Oui (PowerDNS)</td></tr>
        <tr><th>Comptes revendeurs &amp; quotas clients</th><td class="us"><span class="cmp-n">Non</span></td><td>Oui</td><td>Oui</td></tr>
        <tr><th>Gestion de plusieurs serveurs</th><td class="us"><span class="cmp-n">Non</span></td><td>Oui</td><td>Partiel</td></tr>
        <tr><th>Magasin de greffons tiers</th><td class="us"><span class="cmp-n">Non</span></td><td>Oui, fourni</td><td>Limité</td></tr>
        <tr><th>OpenLiteSpeed / LiteSpeed</th><td class="us"><span class="cmp-n">Non</span> — Nginx ou Apache</td><td>Partiel</td><td>Oui, c'est son socle</td></tr>
        <tr><th>Accès GPU pour les conteneurs</th><td class="us"><span class="cmp-n">Non</span></td><td>Partiel</td><td>Partiel</td></tr>
      </tbody>
    </table>
    </div>

    <p>Les six « non » de la fin ne sont pas des chantiers en retard : ce sont des <strong>refus</strong>. Un serveur DNS, des comptes revendeurs et une gestion multi-serveurs sont les fonctions d'un panneau d'hébergeur. Les ajouter reviendrait à construire exactement l'usine à gaz que ce projet cherchait à éviter — avec la surface d'attaque, la mémoire et la complexité qui vont avec.</p>

    <h2 id="cp-price">Le modèle tarifaire</h2>

    <div class="cmp-wrap">
    <table class="cmp-tbl">
      <thead><tr><th></th><th class="us">AegisHost</th><th>aaPanel</th><th>CyberPanel</th></tr></thead>
      <tbody>
        <tr>
          <th>Modèle</th>
          <td class="us"><strong>49,90 € une fois, à vie</strong></td>
          <td>Gratuit, puis abonnement pour les éditions supérieures</td>
          <td>Édition OpenLiteSpeed gratuite ; édition Entreprise sous licence LiteSpeed</td>
        </tr>
        <tr>
          <th>Tarif publié</th>
          <td class="us">Paiement unique, mises à jour comprises</td>
          <td>Pro : 3,99 $/mois ou 39,90 $/an — Entreprise : 9,99 $/mois ou 99,90 $/an</td>
          <td>Tarif Entreprise non publié : à demander à l'éditeur</td>
        </tr>
        <tr>
          <th>Coût sur trois ans</th>
          <td class="us">49,90 €</td>
          <td>0 € en édition gratuite ; environ 120 $ en Pro</td>
          <td>0 € en édition OpenLiteSpeed</td>
        </tr>
      </tbody>
    </table>
    </div>

    <div class="callout"><span class="i">💶</span><div><strong>Disons-le sans détour : les deux autres ont une édition gratuite, pas AegisHost.</strong> Si le budget est le seul critère, l'édition gratuite d'aaPanel ou de CyberPanel est imbattable. AegisHost se paie une fois et ne se repaie jamais — pas d'abonnement, pas de palier, pas de fonction retenue derrière une édition supérieure. Vous achetez un outil fini, pas un accès.</div></div>

    <h2 id="cp-choose">Lequel choisir ?</h2>

    <div class="cmp-split">
      <div class="cmp-card win">
        <div class="ti">✅ AegisHost si…</div>
        <div class="de">
          <p style="margin:0 0 8px">Vous administrez <strong>votre propre VPS ou serveur dédié</strong>, seul ou à quelques-uns.</p>
          <p style="margin:0 0 8px">Vous tenez à ce que la mémoire aille à vos sites plutôt qu'au panneau.</p>
          <p style="margin:0 0 8px">Vous utilisez déjà <strong>Aegis Framework</strong> et voulez tout piloter depuis la même administration.</p>
          <p style="margin:0 0 8px">Vous préférez <strong>ne pas exposer de panneau</strong> sur Internet.</p>
          <p style="margin:0">Vous préférez payer une fois.</p>
        </div>
      </div>
      <div class="cmp-card">
        <div class="ti">↪️ aaPanel ou CyberPanel si…</div>
        <div class="de">
          <p style="margin:0 0 8px">Vous <strong>revendez de l'hébergement</strong> et avez besoin de comptes clients et de quotas.</p>
          <p style="margin:0 0 8px">Vous administrez <strong>plusieurs serveurs</strong> depuis un point unique.</p>
          <p style="margin:0 0 8px">Vous avez besoin d'un <strong>serveur DNS intégré</strong>.</p>
          <p style="margin:0 0 8px">Vous voulez <strong>OpenLiteSpeed</strong> ou LiteSpeed Enterprise (CyberPanel).</p>
          <p style="margin:0">Vous n'utilisez pas Aegis Framework, ou le budget est nul.</p>
        </div>
      </div>
    </div>

    <p>Ces trois outils ne s'adressent pas au même public, et il n'y a aucune honte à ce qu'AegisHost soit le plus petit des trois : c'était l'objectif. Il vise la personne qui a loué un VPS pour y poser quelques sites, un serveur de jeu et une messagerie, qui veut le faire proprement, et à qui l'on n'a jamais proposé autre chose qu'un panneau conçu pour un hébergeur.</p>

    <h2 id="cp-sources">Sources</h2>
    <p class="cmp-src">
      Prérequis aaPanel — <a href="https://www.aapanel.com/docs/guide/quickstart.html" target="_blank" rel="noopener nofollow">documentation officielle, Quick Start ↗</a><br>
      Prérequis et port CyberPanel — <a href="https://cyberpanel.net/KnowledgeBase/home/installing-cyberpanel/" target="_blank" rel="noopener nofollow">base de connaissances officielle ↗</a><br>
      CVE-2024-51378 et exploitation par rançongiciel — <a href="https://censys.com/advisory/cve-2024-51378/" target="_blank" rel="noopener nofollow">avis Censys ↗</a>, <a href="https://ccb.belgium.be/advisories/warning-critical-vulnerabilities-cyberpanel-are-under-active-exploitation-deliver" target="_blank" rel="noopener nofollow">Centre pour la Cybersécurité Belgique ↗</a><br>
      Vulnérabilités aaPanel — <a href="https://www.cvedetails.com/vendor/23472/Aapanel.html" target="_blank" rel="noopener nofollow">liste CVE publique ↗</a>, <a href="https://fenrisk.com/rce-aapanel" target="_blank" rel="noopener nofollow">analyse Fenrisk (CVE-2025-48702) ↗</a><br>
      Tarifs aaPanel — grille publique de l'éditeur, relevée en août 2026.<br>
      Mesures AegisHost — relevés sur serveur Debian 13, pile complète au repos.
    </p>

    <p class="cmp-src">Les marques aaPanel et CyberPanel appartiennent à leurs éditeurs respectifs. Cette page compare des choix techniques, sans dénigrer des projets sérieux qui répondent à d'autres besoins. Si vous constatez une donnée périmée ou inexacte, signalez-la : elle sera corrigée.</p>

    <div class="doc-foot">
      <span>AegisHost · comparatif</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
