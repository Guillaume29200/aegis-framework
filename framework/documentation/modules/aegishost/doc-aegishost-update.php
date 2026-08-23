<?php
/**
 * documentation/modules/aegishost/doc-aegishost-update.php — AegisHost, partie 6 : mise à jour.
 */
$docPage = 'modules/aegishost/doc-aegishost-update.php';
$seo = [
    'title'     => 'AegisHost — Partie 6 : mise à jour &amp; maintenance · Documentation',
    'desc'      => "Mettre à jour AegisHost : le module et son agent privilégié vont par paire. Mise à jour en un clic depuis Aide et réparation, repli en console, lecture du journal de l'agent et diagnostic du panneau.",
    'canonical' => 'https://gamenodepanel.com/documentation/modules/aegishost/doc-aegishost-update.php',
];
require __DIR__ . '/../../inc/head.php';

$code_msg = <<<'TXT'
L'agent installé est trop ancien pour les sauvegardes.
Mettez-le à jour depuis la page Aide, puis revenez ici.
TXT;

$code_agent_only = <<<'TXT'
cd /root/aegishost          # là où vous aviez déposé le dossier install/
sudo bash install.sh --agent-only
TXT;

$code_check = <<<'TXT'
# Quelle version de l'agent tourne réellement ?
sudo /usr/local/bin/aegishost-agent version
TXT;

$code_log = <<<'TXT'
# Le journal de l'agent, en direct
sudo tail -f /var/log/aegishost-agent.log
TXT;

$code_sudoers = <<<'TXT'
# Vérifier la règle sudoers (ne jamais l'éditer autrement que par visudo)
sudo visudo -f /etc/sudoers.d/aegishost
TXT;
?>

    <h1>AegisHost — Partie 6 : mise à jour &amp; maintenance</h1>
    <p class="doc-lead">Le module et son agent vont <strong>par paire</strong>. Comprendre ce couplage, c'est éviter la seule friction récurrente d'AegisHost — et savoir quoi faire quand un écran vous dit que l'agent est trop ancien.</p>
    <div class="doc-meta">
      <span class="doc-pill">menu : 🛟 Aide et réparation</span>
      <span class="doc-pill">module + agent</span>
      <span class="doc-pill">agent signé</span>
    </div>

    <h2 id="a6-pair">Pourquoi deux versions</h2>
    <p>AegisHost n'est pas un seul programme mais deux, qui vivent à des endroits différents :</p>
    <ul>
      <li>Le <strong>module</strong>, dans <code>modules/AegisHost/</code> — les écrans, la logique d'affichage. Il tourne en <code>www-data</code>.</li>
      <li>L'<strong>agent</strong>, dans <code>/usr/local/bin/aegishost-agent</code> — le seul composant qui a les droits root, et le seul à savoir écrire dans <code>/etc</code>, créer un site ou piloter Docker.</li>
    </ul>

    <p>Une nouvelle fonctionnalité demande presque toujours une <strong>nouvelle action</strong> côté agent. Un module récent face à un agent ancien réclame donc une action que celui-ci ne connaît pas — et l'écran vous le dit franchement plutôt que d'échouer avec un message technique :</p>
    <pre><code><?= $h($code_msg) ?></code></pre>

    <div class="callout"><span class="i">📌</span><div>Chaque version du module annonce l'agent qu'elle réclame, dans son <strong>journal des modifications</strong> : « Agent 1.50.0 requis (livré et signé avec ce module) ». L'agent voulu est <em>toujours</em> livré dans le module — vous n'avez jamais à le chercher ailleurs.</div></div>

    <h2 id="a6-module">Mettre à jour le module</h2>
    <p>Comme n'importe quel module d'Aegis Framework : remplacez le contenu de <code>modules/AegisHost/</code> par la nouvelle version, en conservant les droits de l'utilisateur du serveur web.</p>
    <p>Aucune donnée n'est perdue : les sites, les comptes FTP et les bases vivent en base de données et sur le disque, pas dans le module. Les sauvegardes, elles, sont dans <code>/var/backups/aegishost/</code> et ne bougent pas.</p>

    <h2 id="a6-agent">Mettre à jour l'agent</h2>

    <h3>En un clic, depuis le panneau</h3>
    <p>C'est la voie normale. <strong>🛟 Aide et réparation</strong> compare l'agent en place avec celui livré par le module ; s'il est en retard, une réparation est proposée et l'installe.</p>

    <div class="callout ok"><span class="i">🔏</span><div>L'agent <strong>vérifie sa propre signature</strong> avant de se remplacer. Un fichier modifié, tronqué ou substitué en chemin est refusé — c'est ce qui rend cette mise à jour automatique acceptable pour un composant root.</div></div>

    <h3>Le cas où ça ne suffit pas</h3>
    <p>Un agent très ancien ne connaît pas encore l'action qui lui permet de se remplacer. Le panneau ne propose alors pas la réparation automatique : il faut passer une fois par la console, avec le dossier <code>install/</code> de la partie 1.</p>
    <pre><code><?= $h($code_agent_only) ?></code></pre>
    <p>Cette commande ne touche à <strong>rien d'autre</strong> : elle ne réinstalle ni serveur web, ni base de données, et ne redémarre aucun service au-delà du nécessaire. Elle est sans danger sur un serveur en production.</p>

    <p>Pour savoir ce qui tourne réellement :</p>
    <pre><code><?= $h($code_check) ?></code></pre>

    <h2 id="a6-order">Dans quel ordre</h2>
    <ol class="steps">
      <li><strong>Sauvegardez</strong> — au minimum la configuration du serveur. C'est l'affaire d'une minute et cela vous couvre.</li>
      <li><strong>Remplacez le module.</strong></li>
      <li><strong>Ouvrez Aide et réparation</strong> et lancez la mise à jour de l'agent si elle est proposée.</li>
      <li><strong>Rechargez une page du panneau</strong> pour vérifier que le bandeau d'alerte a disparu.</li>
    </ol>

    <div class="callout warn"><span class="i">↔️</span><div>L'inverse — agent récent, module ancien — ne pose aucun problème : l'agent garde ses anciennes actions. C'est toujours le module qui réclame, jamais l'agent.</div></div>

    <h2 id="a6-help">Aide et réparation</h2>
    <p>Cet écran est le point de départ de tout dépannage. Il vérifie ce dont le panneau a besoin pour travailler et propose de remettre d'aplomb ce qui peut l'être :</p>
    <ul>
      <li><strong>Les droits</strong> du panneau sur ses propres fichiers et sur les secrets de la base.</li>
      <li><strong>La règle <code>sudoers</code></strong> — celle qui autorise <code>www-data</code> à appeler l'agent, et rien d'autre. C'est la panne la plus fréquente après une intervention en console.</li>
      <li><strong>L'agent</strong> : présent, à jour, joignable.</li>
    </ul>
    <p>Un second onglet rassemble les <strong>commandes utiles en console</strong>, pour les cas où le panneau lui-même ne répond plus.</p>

    <div class="callout"><span class="i">🚩</span><div>Quand cet écran détecte quelque chose, un <strong>bandeau</strong> apparaît en haut de toutes les pages du panneau. Il ne s'agit pas d'un avertissement décoratif : tant qu'il est là, une partie des fonctions ne marchera pas.</div></div>

    <h2 id="a6-log">Le journal de l'agent</h2>
    <p><strong>📜 Journal</strong> montre ce que l'agent a réellement fait, en direct. C'est là qu'on va quand une opération s'est mal passée : le détail y est, là où le message d'écran est forcément raccourci.</p>
    <p>Depuis le serveur, le même journal se lit directement :</p>
    <pre><code><?= $h($code_log) ?></code></pre>

    <h2 id="a6-tshoot">Quand le panneau ne répond plus</h2>

    <h3>« L'agent privilégié n'est pas installé »</h3>
    <p>Le fichier a disparu, ou <code>open_basedir</code> empêche PHP de le voir. Le panneau distingue les deux cas et le dit. Dans le premier, reposez-le avec <code>--agent-only</code>.</p>

    <h3>« Vérifiez la règle sudoers »</h3>
    <p>L'agent est là mais <code>www-data</code> n'a pas le droit de l'appeler. La réparation automatique règle le cas ; sinon :</p>
    <pre><code><?= $h($code_sudoers) ?></code></pre>

    <h3>« L'exécution de commandes est désactivée »</h3>
    <p><code>shell_exec</code> et <code>proc_open</code> figurent dans <code>disable_functions</code> du PHP qui sert le panneau. Ce sont ces fonctions qui lui permettent d'appeler son agent : sans elles, il ne peut rien faire. Le panneau refuse d'ailleurs de les désactiver lui-même sur le site qui l'héberge — mais rien ne l'empêche d'être coupé en dehors de lui.</p>

    <h3>« Read-only file system » alors que le disque va bien</h3>
    <p>Symptôme classique du bac à sable de PHP-FPM : sous <code>ProtectSystem</code>, <code>/etc</code> et <code>/usr</code> sont en lecture seule pour tous les descendants du service — donc pour l'agent appelé depuis une page. L'agent contourne cela de lui-même en se relançant hors du bac à sable ; si le message revient, le journal dira à quelle étape.</p>

    <h3>Une opération longue semble figée</h3>
    <p>Les opérations longues — installation d'un composant, d'une application Docker, sauvegarde — tournent en <strong>tâche de fond</strong> et se poursuivent même si vous fermez l'onglet. Le suivi se reprend dans <strong>📜 Journal</strong>.</p>

    <div class="callout"><span class="i">➡️</span><div>La suite : <a href="modules/aegishost/doc-aegishost-security.php">Partie 7 — la sécurité</a>, et la <a href="../../faq/doc-faq-aegishost.php">FAQ AegisHost</a> pour les questions rapides.</div></div>

    <div class="doc-foot">
      <span>AegisHost · partie 6 : mise à jour &amp; maintenance</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/../../inc/foot.php'; ?>
