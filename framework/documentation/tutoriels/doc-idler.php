<?php
/**
 * documentation/tutoriels/doc-idler.php — Astuce « idler » : occuper un cœur CPU
 * pour stabiliser le tickrate. Article importé de FPSMeter, réécrit pour GNP.
 */
$docPage = 'tutoriels/doc-idler.php';
$seo = [
    'title'     => 'Astuce « idler » — stabiliser le tickrate d\'un serveur de jeu · Documentation GameNodePanel',
    'desc'      => "Tutoriel de dépannage : utiliser un petit programme « idler » qui occupe un cœur CPU en priorité minimale pour limiter les changements d'état du processeur et stabiliser le tickrate des serveurs SRCDS/HLDS/CS2 sur un hôte GameNodePanel.",
    'canonical' => 'https://gamenodepanel.com/documentation/tutoriels/doc-idler.php',
];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Stabiliser un serveur de jeu avec le processus « idler »</h1>
    <p class="doc-lead">Une astuce simple de <strong>dépannage</strong> pour les hôtes <strong>GameNodePanel</strong> : faire tourner un petit programme « idler » qui occupe un cœur CPU en priorité minimale, afin de limiter les changements d'état du processeur (idle ↔ actif) qui provoquent des micro-latences sur les serveurs <strong>SRCDS/HLDS/CS2</strong>.</p>
    <div class="doc-meta">
      <span class="doc-pill">SRCDS · HLDS · CS2</span>
      <span class="doc-pill">Solution de secours</span>
      <span class="doc-pill">Linux · root</span>
    </div>

    <div class="callout warn"><span class="i">⚠️</span><div>
      <strong>Solution de secours — à utiliser en connaissance de cause.</strong> L'idler <strong>consomme volontairement 100 % d'un cœur CPU</strong>
      sans utilité réelle (juste pour le garder « éveillé »). Cela augmente la <strong>consommation électrique</strong>, la <strong>chaleur</strong> et
      l'usage CPU. <strong>À éviter sur une machine partagée, peu puissante ou à ressources limitées</strong>, et à proscrire si vos cœurs sont déjà bien occupés.
    </div></div>
    <div class="callout"><span class="i">🪶</span><div>
      <strong>Mieux vaut d'abord les vraies optimisations :</strong> le <a href="tutoriels/doc-realtime.php">script <code>realtime.sh</code></a>
      (priorité CPU temps réel) et, pour aller plus loin, le <a href="tutoriels/doc-kernel-1000hz.php">noyau 1000Hz + Realtime</a>.
      L'idler est surtout utile <strong>quand vous ne pouvez pas recompiler le noyau</strong> et que vous restez sur un noyau 100Hz/250Hz.
    </div></div>

    <h2 id="id-goal">🎯 Objectif du tutoriel</h2>
    <p>Certains serveurs de jeu, notamment SRCDS ou HLDS, peuvent souffrir de légères variations de tickrate ou de micro-lags CPU, même après diverses optimisations. Ce problème provient souvent des changements d'état du processeur <strong>(idle ↔ actif)</strong> qui introduisent des micro-latences.</p>
    <p>Ce tutoriel présente une astuce simple mais efficace : exécuter un petit programme appelé <strong>idler</strong> qui occupe un cœur CPU à 100 % en priorité minimale, afin de stabiliser le comportement du processeur.</p>

    <h2 id="id-steps">🛠️ Étapes de mise en place</h2>
    <p>Ces commandes s'exécutent <strong>sur le serveur hôte</strong> (le VPS/dédié de vos serveurs de jeu, ajouté dans GNP via <strong>Serveurs hôtes</strong>).</p>

    <p><strong>1. Créez un fichier <code>idler.c</code></strong></p>
    <pre><code>cd /home</code></pre>
    <pre><code>nano idler.c</code></pre>
    <p>Copiez le code ci-dessous :</p>
<pre><code class="language-c">int main() {
  while(1);
}</code></pre>

    <p><strong>2. Compilez le programme</strong></p>
    <pre><code>gcc idler.c -o idler</code></pre>

    <p><strong>3. Lancez-le avec une priorité basse</strong></p>
    <pre><code>nice ./idler &amp;</code></pre>
    <p>Le <strong><code>&amp;</code></strong> permet de lancer le processus en arrière-plan.</p>

    <h2 id="id-multicore">💡 Astuce multi-cœur (facultatif)</h2>
    <p>Sur certains systèmes multi-cœurs (<strong>exemple : 4 cœurs</strong>), il peut parfois être utile d'exécuter un processus <strong>idler</strong> dédié sur chaque cœur physique :</p>
<pre><code>nice taskset -c 0 ./idler &amp;
nice taskset -c 1 ./idler &amp;
nice taskset -c 2 ./idler &amp;
nice taskset -c 3 ./idler &amp;</code></pre>
    <div class="callout warn"><span class="i">🔥</span><div>Un idler par cœur = <strong>tous les cœurs à 100 %</strong> en permanence. Réservez ceci à un serveur <strong>dédié</strong> que vous maîtrisez, jamais sur du mutualisé. Adaptez le nombre à vos cœurs <em>réellement</em> disponibles (laissez de la marge pour l'OS et les autres serveurs).</div></div>

    <h2 id="id-result">✅ Résultat attendu</h2>
    <ul>
      <li>Le ou les processus idler gardent le ou les cœurs CPU en activité permanente ;</li>
      <li>cela réduit les variations de fréquence ou d'état C/P du CPU ;</li>
      <li>peut lisser les performances pour un tickrate plus stable, surtout sur CS:S, CS:GO, CS2, etc.</li>
    </ul>

    <h2 id="id-warning">❗ À ne pas oublier</h2>
    <ol>
      <li>Le processus <strong>idler</strong> utilise intentionnellement du CPU sans but réel → à éviter sur des machines partagées ou aux ressources limitées.</li>
      <li>Il ne remplace pas une vraie optimisation noyau.</li>
      <li>Surveillez la charge de votre machine via <code>htop</code>/<code>top</code> — ou directement via l'agent <a href="modules/gnp/doc-odin.php">O.D.I.N</a> de GameNodePanel.</li>
    </ol>

    <h2 id="id-conclusion">🧠 En conclusion</h2>
    <p>Cette méthode peut être un bon <strong>dépannage temporaire</strong>, notamment sur Debian, Ubuntu ou CentOS avec un noyau <strong>100Hz/250Hz</strong>. Elle est <strong>inutile et déconseillée</strong> si vous utilisez déjà un noyau <strong>1000Hz + RT</strong>, car ce dernier offre un comportement temps réel optimisé sans artifice.</p>

    <hr style="border:none;border-top:1px solid var(--bd);margin:24px 0">
    <p style="font-size:.86rem;color:var(--tx3)">
      🧠 <strong>Crédits</strong> — Idée originale : <strong>BehaartesEtwas</strong> · Adaptation : <strong>Slymer</strong>.
    </p>
<?php require __DIR__ . '/../inc/foot.php'; ?>
