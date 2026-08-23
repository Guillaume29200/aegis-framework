<?php
/**
 * documentation/tutoriels/doc-realtime.php — Script realtime.sh : stabiliser les
 * serveurs de jeu (HLDS/SRCDS) en priorité temps réel. Article importé de FPSMeter,
 * réécrit pour GameNodePanel.
 */
$docPage = 'tutoriels/doc-realtime.php';
$seo = [
    'title'     => 'Script realtime.sh — stabiliser ses serveurs de jeu (CPU temps réel) · Documentation GameNodePanel',
    'desc'      => "Tutoriel : utiliser un script realtime.sh pour donner une priorité CPU temps réel à vos serveurs HLDS/SRCDS (Counter-Strike, CS2, TF2, L4D, DoD) hébergés via GameNodePanel. Installation, cron, source d'horloge, et avertissements importants.",
    'canonical' => 'https://gamenodepanel.com/documentation/tutoriels/doc-realtime.php',
];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Stabiliser ses serveurs de jeu : le script <code>realtime.sh</code></h1>
    <p class="doc-lead">Sur un hôte <strong>GameNodePanel</strong>, vous pouvez donner à vos serveurs de jeu une <strong>priorité CPU temps réel</strong> pour lisser le tickrate et réduire les micro-lags. Ce tutoriel fournit un script <code>realtime.sh</code> qui repère automatiquement vos serveurs <strong>HLDS/SRCDS</strong> et leur applique cette priorité.</p>
    <div class="doc-meta">
      <span class="doc-pill">Source / GoldSrc</span>
      <span class="doc-pill">CS · CS2 · TF2 · L4D · DoD</span>
      <span class="doc-pill">Linux · root</span>
      <span class="doc-pill">Avancé</span>
    </div>

    <p>Compatible avec les serveurs basés sur <strong>HLDS/SRCDS</strong>, notamment :</p>
    <ul>
      <li>Counter-Strike (1.6, Source, Global Offensive) &amp; <strong>Counter-Strike 2</strong></li>
      <li>Left 4 Dead 1 / 2</li>
      <li>Day of Defeat</li>
      <li>Team Fortress</li>
    </ul>

    <div class="callout warn"><span class="i">⚠️</span><div>
      <strong>À lire avant toute chose — manipulation avancée &amp; à vos risques.</strong> La priorité « temps réel » (RT)
      est une fonctionnalité système puissante : <strong>mal employée, elle peut rendre un serveur instable, voire le figer</strong>
      (un processus RT mal borné peut affamer le noyau et les autres services). Ce tutoriel s'adresse à des administrateurs
      à l'aise avec Linux, sur un <strong>serveur hôte dédié dont vous avez le contrôle root</strong>.
    </div></div>
    <div class="callout"><span class="i">🧪</span><div>
      <strong>Recommandations :</strong> testez d'abord sur un hôte <strong>hors production</strong> ; évitez sur un petit VPS
      mutualisé ou à faible nombre de cœurs ; gardez un accès console de secours (KVM/IPMI) au cas où la machine deviendrait
      injoignable ; surveillez la charge après application (l'agent <a href="modules/gnp/doc-odin.php">O.D.I.N</a> de GNP est idéal pour ça).
    </div></div>

    <h2 id="rt-what">À quoi sert ce script ?</h2>
    <p>Le script <code>realtime.sh</code> utilise le <strong>scheduling temps réel de Linux</strong> : il accorde une priorité CPU
    plus élevée aux processus critiques (vos serveurs de jeu). Concrètement, il :</p>
    <ul>
      <li>repère les processus actifs (ex. <code>srcds_linux</code>, <code>cs2</code>, <code>hlds_*</code>…) ;</li>
      <li>applique une priorité temps réel via <code>chrt -f -p</code> ;</li>
      <li>optimise aussi certains processus internes liés au timer du noyau.</li>
    </ul>
    <p>🎯 <strong>Résultat :</strong> le système traite vos serveurs de jeu en priorité, ce qui réduit la latence CPU et les micro-lags.</p>

    <h2 id="rt-fps">Est-ce que ça augmente les FPS ?</h2>
    <p>Indirectement. Le script ne <em>génère</em> pas plus de FPS serveur, mais il <strong>stabilise leur cadence</strong> et réduit
    les ralentissements liés au multitâche de l'OS.</p>
    <div class="callout ok"><span class="i">➕</span><div>Moins de fluctuations ➝ plus de fluidité ➝ meilleure expérience pour les joueurs.</div></div>

    <h2 id="rt-install">Installation &amp; utilisation</h2>
    <p>Toutes les commandes s'exécutent <strong>sur le serveur hôte</strong> (le VPS/dédié qui héberge vos serveurs de jeu),
    en <strong>root</strong> — par exemple via une connexion SSH, ou le terminal de votre hébergeur.</p>
    <div class="callout"><span class="i">🖥️</span><div>Dans GameNodePanel, c'est l'hôte que vous avez ajouté dans <strong>Serveurs hôtes</strong> (<code>gestion-dedie</code>). Le script agit sur les processus des serveurs que GNP y a installés.</div></div>

    <ol class="steps">
      <li>Créez le fichier :
        <pre><code>nano /home/realtime.sh</code></pre>
      </li>
      <li>Copiez-y le contenu suivant :
<pre><code class="language-bash">#!/bin/sh
PROCESS_NAMES="srcds_linux srcds_i686 srcds_i486 srcds_amd hlds_i686 hlds_i486 hlds_amd cs2"
for name in $PROCESS_NAMES; do
PIDS=$(pidof $name)
for p in $PIDS; do
chrt -f -p 98 $p
done
done
# Optimisation supplémentaire pour noyaux RT
PIDS=$(ps ax | grep sirq-hrtimer | grep -v grep | awk '{print $1}')
for p in $PIDS; do
chrt -f -p 99 $p
done
PIDS=$(ps ax | grep sirq-timer | grep -v grep | awk '{print $1}')
for p in $PIDS; do
chrt -f -p 51 $p
done</code></pre>
      </li>
      <li>Rendez-le exécutable :
        <pre><code>chmod 755 /home/realtime.sh</code></pre>
      </li>
      <li>Ajoutez-le au cron pour une exécution toutes les 5 minutes :
        <pre><code>sudo nano /etc/crontab</code></pre>
        Ajoutez à la fin :
        <pre><code>*/5 * * * * root /home/realtime.sh &gt; /dev/null 2&gt;&amp;1</code></pre>
      </li>
    </ol>
    <p>✅ C'est tout : vos serveurs bénéficient d'un scheduling prioritaire, réappliqué automatiquement (utile car les PID changent à chaque redémarrage de serveur).</p>
    <div class="callout warn"><span class="i">🧯</span><div>
      Le cron réapplique la priorité toutes les 5 minutes, y compris aux serveurs <strong>nouvellement démarrés</strong>.
      Gardez à l'esprit que la priorité <code>98/99</code> est <strong>très haute</strong> : ne l'étendez pas à d'autres
      processus sans comprendre l'impact, et n'augmentez pas la fréquence du cron inutilement.
    </div></div>

    <h2 id="rt-clocksource">Astuce bonus : changer la source d'horloge système</h2>
    <p>Linux utilise une <strong>source d'horloge</strong> (clocksource) pour le timing système. Par défaut c'est souvent
    <code>tsc</code>, qui peut être instable sur certains CPU. Vous pouvez tester une horloge plus fiable comme <code>hpet</code> ou <code>acpi_pm</code> :</p>
    <ul>
      <li>Afficher les sources disponibles :
        <pre><code>cat /sys/devices/system/clocksource/clocksource0/available_clocksource</code></pre>
      </li>
      <li>Voir la source actuelle :
        <pre><code>cat /sys/devices/system/clocksource/clocksource0/current_clocksource</code></pre>
      </li>
      <li>Changer temporairement pour <code>hpet</code> :
        <pre><code>echo hpet | sudo tee /sys/devices/system/clocksource/clocksource0/current_clocksource</code></pre>
      </li>
    </ul>
    <div class="callout"><span class="i">ℹ️</span><div>Ce changement est <strong>temporaire</strong> et sera perdu au redémarrage. Pour le rendre permanent, il faut modifier la ligne de démarrage <strong>GRUB</strong> — une opération sensible : une mauvaise valeur peut empêcher la machine de redémarrer correctement. À ne faire qu'en connaissance de cause, avec un accès console de secours.</div></div>

    <h2 id="rt-gnp">Et avec GameNodePanel ?</h2>
    <p>GNP installe et pilote vos serveurs de jeu sur l'hôte ; ce script agit au niveau <strong>système</strong>, en complément, sur les processus correspondants. Quelques conseils d'intégration :</p>
    <ul>
      <li>Appliquez-le sur les hôtes qui font tourner du <strong>Source/GoldSrc</strong> (CS, CS2, TF2, L4D, DoD) — là où le gain de stabilité est le plus net.</li>
      <li>Après activation, suivez la charge CPU/RAM réelle via <a href="modules/gnp/doc-odin.php">O.D.I.N</a> et la santé via <a href="modules/gnp/doc-vega.php">VEGA</a> pour valider l'effet.</li>
      <li>Couplé aux <strong>cœurs/RAM dédiés</strong> de GNP (voir <a href="tutoriels/doc-ressources.php">Ressources &amp; limites par hôte</a>), vous obtenez une isolation des performances encore meilleure.</li>
    </ul>

    <div class="callout ok"><span class="i">🎉</span><div><strong>Félicitations !</strong> Vous venez de franchir une étape importante vers un serveur de jeu plus stable et plus professionnel. N'hésitez pas à partager l'astuce avec d'autres admins.</div></div>

    <hr style="border:none;border-top:1px solid var(--bd);margin:24px 0">
    <p style="font-size:.86rem;color:var(--tx3)">
      🧠 <strong>Crédits</strong> — Idée originale : <strong>BehaartesEtwas</strong> · Adaptation &amp; script : <strong>Slymer</strong>.
    </p>
<?php require __DIR__ . '/../inc/foot.php'; ?>
