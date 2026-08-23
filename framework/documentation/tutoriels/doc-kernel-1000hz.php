<?php
/**
 * documentation/tutoriels/doc-kernel-1000hz.php — Recompiler un noyau Linux
 * 1000Hz + PREEMPT_RT pour stabiliser les serveurs de jeu. Article importé de
 * FPSMeter, réécrit pour GameNodePanel.
 */
$docPage = 'tutoriels/doc-kernel-1000hz.php';
$seo = [
    'title'     => 'Noyau Linux 1000Hz + Realtime (PREEMPT_RT) pour serveurs de jeu · Documentation GameNodePanel',
    'desc'      => "Tutoriel avancé : recompiler un noyau Linux en 1000Hz + PREEMPT_RT pour stabiliser tickrate et FPS de vos serveurs HLDS/SRCDS/CS2 hébergés via GameNodePanel. Prérequis, patch RT, configuration, compilation et avertissements.",
    'canonical' => 'https://gamenodepanel.com/documentation/tutoriels/doc-kernel-1000hz.php',
];
require __DIR__ . '/../inc/head.php';
?>
    <h1>Recompiler son noyau Linux en 1000Hz Realtime</h1>
    <p class="doc-lead">Pour les hôtes <strong>GameNodePanel</strong> les plus exigeants : recompiler un noyau Linux en mode <strong>temps réel (PREEMPT_RT)</strong> avec une fréquence d'horloge à <strong>1000Hz</strong>, afin de lisser au maximum le tickrate et les FPS de vos serveurs <strong>HLDS/SRCDS/CS2</strong>.</p>
    <div class="doc-meta">
      <span class="doc-pill">PREEMPT_RT · 1000Hz</span>
      <span class="doc-pill">Debian 11/12 · Ubuntu 22.04+ · RHEL 9+</span>
      <span class="doc-pill">root requis</span>
      <span class="doc-pill">⚠️ Expert</span>
    </div>

    <div class="callout warn"><span class="i">⛔</span><div>
      <strong>Opération experte &amp; risquée — à vos risques et périls.</strong> Recompiler et installer un noyau personnalisé
      peut rendre la machine <strong>non amorçable</strong> (kernel panic au boot) si une option est mal réglée. N'effectuez ceci que
      sur un <strong>serveur dédié / VPS dont vous maîtrisez le boot</strong>, avec un <strong>accès console de secours</strong>
      (KVM/IPMI, mode rescue de l'hébergeur) pour pouvoir revenir en arrière. <strong>Jamais directement en production</strong> sans test préalable.
    </div></div>
    <div class="callout"><span class="i">🪶</span><div>
      <strong>Vous cherchez juste un gain rapide ?</strong> Le tutoriel <a href="tutoriels/doc-realtime.php">Script <code>realtime.sh</code> (priorité CPU)</a>
      apporte déjà une bonne partie du bénéfice <strong>sans recompiler le noyau</strong> — commencez par là. Le 1000Hz + RT s'adresse
      aux infrastructures qui veulent pousser la stabilité au maximum.
    </div></div>
    <div class="callout"><span class="i">🖥️</span><div>
      Ceci s'applique au <strong>serveur hôte</strong> (le VPS/dédié de vos serveurs de jeu, ajouté dans GNP via <strong>Serveurs hôtes</strong>),
      pas au panel. Prévoyez aussi du <strong>temps</strong> : la compilation peut durer de longues minutes selon le CPU.
    </div></div>

    <h2 id="k-goal">✨ Objectif</h2>
    <p>Optimiser la stabilité de vos serveurs de jeu HLDS/SRCDS/CS2 en recompilant un noyau Linux en mode temps réel (PREEMPT_RT) avec une fréquence d'horloge à 1000Hz.</p>

    <h2 id="k-why">🧠 Pourquoi un noyau 1000Hz + Realtime ?</h2>
    <p>Les noyaux Linux standards utilisent une fréquence d'horloge système de <strong>100Hz</strong> ou <strong>250Hz</strong>, soit des interruptions système toutes les 10 ms ou 4 ms environ. Sur un serveur de jeu, chaque milliseconde compte : une réponse tardive du CPU peut engendrer du lag, des micro-freezes, ou des variations de tickrate.</p>
    <p>Passer à un noyau <strong>1000Hz</strong>, c'est interroger le système toutes les <strong>1 ms</strong>, pour une précision et une réactivité bien plus fines.</p>
    <p>💡 Couplé au patch <strong>RealTime</strong>, cela permet au serveur de jeu (HLDS, SRCDS, CS2…) :</p>
    <ul>
      <li>d'obtenir une priorité CPU absolue ;</li>
      <li>de réduire les pics de latence ;</li>
      <li>d'améliorer la régularité du framerate et des ticks ;</li>
      <li>d'augmenter la fluidité ressentie par les joueurs ;</li>
      <li>d'éliminer les ralentissements dus au multitâche système.</li>
    </ul>
    <p>Ce combo (1000Hz + RT) est particulièrement recommandé pour :</p>
    <ul>
      <li>les serveurs compétitifs ;</li>
      <li>les environnements e-sport ou LAN ;</li>
      <li>les infrastructures où chaque ms de latence CPU impacte l'expérience de jeu.</li>
    </ul>

    <h2 id="k-prereq">🚧 Prérequis</h2>
    <p>Vous devez être en <strong>root</strong> (<code>sudo -i</code> ou <code>su -</code>), sur un serveur dédié ou VPS compatible.</p>
    <p><strong>Packages requis sur Debian/Ubuntu :</strong></p>
    <pre><code>apt update && apt install -y build-essential libncurses-dev bison flex libssl-dev libelf-dev bc wget curl zstd git</code></pre>

    <h2 id="k-download">📁 1. Télécharger les sources du noyau</h2>
    <p>Rendez-vous sur <a href="https://www.kernel.org" target="_blank" rel="noopener">kernel.org</a> et téléchargez la dernière version stable avec support PREEMPT_RT (ex. Linux 6.8.x ou 6.9.x).</p>
    <pre><code>wget https://cdn.kernel.org/pub/linux/kernel/v6.x/linux-6.9.tar.xz</code></pre>
    <pre><code>wget https://cdn.kernel.org/pub/linux/kernel/projects/rt/6.9/patch-6.9-rt1.patch.gz</code></pre>

    <h2 id="k-patch">📂 2. Extraction et patch RT</h2>
    <pre><code>tar -xf linux-6.9.tar.xz && cd linux-6.9 && zcat ../patch-6.9-rt1.patch.gz | patch -p1</code></pre>

    <h2 id="k-config">🔧 3. Configuration du noyau</h2>
    <p>Copier la configuration actuelle si présente :</p>
    <pre><code>cp -v /boot/config-$(uname -r) .config</code></pre>
    <p>Lancer la configuration :</p>
    <pre><code>make menuconfig</code></pre>
    <p>Paramètres à modifier :</p>
    <ol>
      <li><strong>General setup &gt; Preemption Model</strong> : Fully Preemptible Kernel (Real-Time)</li>
      <li><strong>Processor type and features &gt; Timer frequency</strong> : 1000 Hz</li>
      <li>Activer : <strong>High Resolution Timer</strong>, <strong>Tickless System</strong></li>
      <li>Désactiver : <strong>CPU frequency scaling</strong>, suspend/hibernate</li>
    </ol>

    <h2 id="k-build">🛠️ 4. Compilation et installation</h2>
    <pre><code>make -j$(nproc)</code></pre>
    <pre><code>make modules_install</code></pre>
    <pre><code>make install</code></pre>
    <p>Générer l'initramfs (si nécessaire) :</p>
    <pre><code>update-initramfs -c -k 6.9.0-rt1</code></pre>
    <p>Mettre à jour GRUB :</p>
    <pre><code>update-grub</code></pre>

    <h2 id="k-reboot">🚗 5. Redémarrage</h2>
    <pre><code>reboot</code></pre>
    <p>Après redémarrage, vérifiez que le nouveau noyau est en place :</p>
    <pre><code>uname -r</code></pre>
    <p>Vous devriez voir un noyau type : <strong>6.9.0-rt1</strong></p>
    <div class="callout warn"><span class="i">🧯</span><div>Si la machine ne redémarre pas correctement, utilisez le <strong>menu GRUB</strong> (touche Shift/Échap au boot) pour sélectionner l'ancien noyau, ou le <strong>mode rescue</strong> de votre hébergeur. Ne supprimez jamais l'ancien noyau tant que le nouveau n'est pas validé stable.</div></div>

    <h2 id="k-tips">ℹ️ Astuces</h2>
    <ol>
      <li>Les noyaux RT sont plus sensibles aux drivers tiers.</li>
      <li>Évitez les modules NVIDIA propriétaires ou une virtualisation exotique sans test.</li>
      <li>Le RT permet de stabiliser tickrate/FPS sur CS2/SRCDS/HLDS, mais ne fait pas de miracles si le CPU est <strong>déjà saturé</strong> ! Surveillez la charge via <a href="modules/gnp/doc-odin.php">O.D.I.N</a>, et dimensionnez vos slots/ressources (voir <a href="tutoriels/doc-ressources.php">Ressources &amp; limites par hôte</a>).</li>
    </ol>

    <h2 id="k-refs">🎓 Références</h2>
    <ol>
      <li><a href="https://wiki.linuxfoundation.org/realtime/start" target="_blank" rel="noopener">wiki.linuxfoundation.org/realtime/start</a></li>
      <li><a href="https://wiki.debian.org/HowToRecompileKernel" target="_blank" rel="noopener">wiki.debian.org/HowToRecompileKernel</a></li>
      <li><a href="https://kernel.org" target="_blank" rel="noopener">kernel.org</a></li>
      <li><a href="http://wiki.fragaholics.de/index.php/EN:Linux_Kernel_Optimization" target="_blank" rel="noopener">wiki.fragaholics.de — Linux Kernel Optimization</a></li>
    </ol>

    <hr style="border:none;border-top:1px solid var(--bd);margin:24px 0">
    <p style="font-size:.86rem;color:var(--tx3)">🧠 <strong>Crédits</strong> — Rédacteur : <strong>Slymer</strong>. Article réécrit pour la documentation GameNodePanel.</p>
<?php require __DIR__ . '/../inc/foot.php'; ?>
