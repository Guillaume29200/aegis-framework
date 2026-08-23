<?php
/**
 * documentation/documentation.php — Accueil (hub) de la documentation Aegis Framework.
 * Point d'entrée multi-modules : oriente vers le framework et chaque module officiel.
 * (L'ancienne « Présentation » du framework vit désormais dans framework/doc-aegis.php.)
 */
$docPage = 'documentation.php';
$seo = [
    'title'     => 'Documentation — Aegis Framework &amp; ses modules officiels',
    'desc'      => "Documentation d'Aegis Framework et de ses modules officiels (GameNodePanel, Forum…) : prise en main, architecture, sécurité, et guides par module.",
    'canonical' => 'https://gamenodepanel.com/documentation/documentation.php',
];
require __DIR__ . '/inc/head.php';
?>

    <h1>Documentation</h1>
    <p class="doc-lead"><strong>Aegis Framework</strong> est un <strong>socle PHP modulaire</strong> open-source : un framework léger doublé d'une plateforme à modules (administration, authentification et sécurité prêtes à l'emploi). Cette documentation couvre le <strong>framework</strong> lui-même ainsi que ses <strong>modules officiels</strong>. Choisissez une rubrique pour commencer.</p>
    <div class="doc-meta">
      <span class="doc-pill">Aegis Framework v4.0.0</span>
      <span class="doc-pill">PHP ≥ 8.5</span>
      <span class="doc-pill">Open-source</span>
    </div>

    <style>
    .hub-section{margin:30px 0 8px}
    .hub-section h2{border:none;margin-bottom:4px}
    .hub-section .hub-sub{color:var(--tx2);font-size:.92rem;margin:0 0 16px}
    .hub-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(248px,1fr));gap:16px;margin-bottom:8px}
    .hub-card{display:block;background:var(--bg2);border:1px solid var(--bd);border-radius:14px;padding:18px 18px 16px;
      text-decoration:none;color:inherit;transition:border-color .15s,transform .15s,box-shadow .15s}
    .hub-card:hover{text-decoration:none;border-color:var(--ac);transform:translateY(-2px);box-shadow:0 8px 24px -12px var(--ac)}
    .hub-card .ic{font-size:1.9rem;line-height:1;display:block;margin-bottom:10px}
    .hub-card .ti{font-weight:800;font-size:1.02rem;color:var(--tx);margin-bottom:4px}
    .hub-card .de{font-size:.86rem;color:var(--tx2);line-height:1.55}
    .hub-card .go{margin-top:10px;font-size:.82rem;font-weight:700;color:var(--ac)}
    .hub-card.soon{opacity:.72}
    .hub-card.soon .go{color:var(--tx3)}
    .hub-badge{display:inline-block;font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
      background:var(--ac-bg);color:var(--ac);border:1px solid var(--ac);border-radius:999px;padding:1px 7px;margin-left:6px;vertical-align:middle}
    </style>

    <div class="hub-section">
      <h2 id="hub-socle">🏗️ Le socle</h2>
      <p class="hub-sub">Le framework sur lequel tout repose.</p>
      <div class="hub-grid">
        <a class="hub-card" href="framework/doc-aegis.php">
          <span class="ic">📦</span>
          <div class="ti">Aegis Framework</div>
          <div class="de">Architecture MVC modulaire, installation, routing, services, sécurité <em>by design</em> et Security Center.</div>
          <div class="go">Découvrir le framework →</div>
        </a>
        <a class="hub-card" href="framework/doc-module.php">
          <span class="ic">🧩</span>
          <div class="ti">Créer un module</div>
          <div class="de">Guide pas-à-pas : générateur, structure, <code>module.json</code>, routes, vues admin, SQL, packaging.</div>
          <div class="go">Guide développeur →</div>
        </a>
        <a class="hub-card" href="framework/doc-templating.php">
          <span class="ic">🎨</span>
          <div class="ti">Moteur de templates</div>
          <div class="de">Des thèmes publics en <strong>HTML pur</strong>, sans PHP, donc sûrs à téléverser : structure, <code>meta.json</code>, syntaxe, filtres et pièges.</div>
          <div class="go">Écrire un thème →</div>
        </a>
        <a class="hub-card" href="framework/doc-capabilities.php">
          <span class="ic">🧱</span>
          <div class="ti">Fonctionnalités &amp; capacités</div>
          <div class="de">Markdown, Cache, IA, SEO, RGPD, Analytics, TurboNav, reCAPTCHA : huit briques qu'un module <em>déclare</em> au lieu de les réécrire.</div>
          <div class="go">Voir les capacités →</div>
        </a>
        <a class="hub-card" href="modules/analytics/doc-analytics.php">
          <span class="ic">📈</span>
          <div class="ti">Analytics <span class="hub-badge">Par défaut</span></div>
          <div class="de">Mesure d'audience auto-hébergée &amp; sans cookie (RGPD) : tableau de bord, temps réel, objectifs. Module fourni par défaut, désactivable.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
      </div>
    </div>

    <div class="hub-section">
      <h2 id="hub-modules">🚀 Modules officiels</h2>
      <p class="hub-sub">Documentation d'usage complète, module par module.</p>
      <div class="hub-grid">
        <a class="hub-card" href="modules/gnp/doc-gnp.php">
          <span class="ic">🎮</span>
          <div class="ti">GameNodePanel</div>
          <div class="de">Hébergement de serveurs de jeu : hôtes &amp; IP failover, installation, panels par jeu, VEGA, O.D.I.N, Database Manager, marketplace.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/gnv/doc-gnv.php">
          <span class="ic">🧩</span>
          <div class="ti">GameNodeViewer</div>
          <div class="de">Viewer temps réel de serveurs de jeu : protocoles auto-découverts, matrice d'affichage, maps, carte du monde, historique, widgets embeddables, API &amp; SEO.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/forum/doc-forum.php">
          <span class="ic">🗨️</span>
          <div class="ti">Forum</div>
          <div class="de">Communauté complète : catégories &amp; permissions, Markdown, réputation, récompenses, shoutbox, thèmes et SEO.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/blog/doc-blog.php">
          <span class="ic">✍️</span>
          <div class="ti">Blog</div>
          <div class="de">Blog complet : articles Markdown, catégories &amp; tags, commentaires modérés, likes, publication programmée, thèmes ZIP, RSS &amp; SEO.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/aegishost/doc-aegishost.php">
          <span class="ic">🌐</span>
          <div class="ti">AegisHost <span class="hub-badge">Serveur</span></div>
          <div class="de">Panneau d'administration de serveur Debian : sites web, PHP par site, HTTPS, bases de données, Docker, messagerie et sauvegardes — sans jamais tourner en root.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/sauvegarde/doc-sauvegarde.php">
          <span class="ic">💾</span>
          <div class="ti">Sauvegarde <span class="hub-badge">Aegis Backup</span></div>
          <div class="de">Sauvegardes chiffrées, périmètre fichiers &amp; BDD, plannings automatiques, synchro cloud (S3) et restauration.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/marketplace/doc-mp.php">
          <span class="ic">🛒</span>
          <div class="ti">Marketplace</div>
          <div class="de">Boutique de produits dématérialisés : catalogue, paiement PayPal, commandes &amp; factures PDF, promotions, avis vérifiés, vitrine à thèmes.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/gne/doc-gne.php">
          <span class="ic">🏆</span>
          <div class="ti">GameNodeEsport <span class="hub-badge">Gratuit</span></div>
          <div class="de">Le CMS des équipes et communautés gaming : roster, matchs, actualités, recrutement, boutique, dons, streamers Twitch. <strong>Deux thèmes offerts.</strong></div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
        <a class="hub-card" href="modules/gnh/doc-gnh.php">
          <span class="ic">🗄️</span>
          <div class="ti">GameNodeHosting <span class="hub-badge">Requiert GNP</span></div>
          <div class="de">Transformez GameNodePanel en hébergeur : boutique, tarification au slot, paiement, livraison automatique des serveurs, espace client et <strong>support intégré sans surcoût</strong>.</div>
          <div class="go">Ouvrir la rubrique →</div>
        </a>
      </div>
    </div>

    <div class="hub-section">
      <h2 id="hub-ressources">🎓 Ressources</h2>
      <p class="hub-sub">Pour aller plus loin et trouver des réponses rapides.</p>
      <div class="hub-grid">
        <a class="hub-card" href="tutoriels/tuto-ssh-root.php">
          <span class="ic">🎓</span>
          <div class="ti">Tutoriels &amp; articles</div>
          <div class="de">Guides pratiques : activer le login root SSH, dimensionner les ressources d'un hôte…</div>
          <div class="go">Voir les tutoriels →</div>
        </a>
        <a class="hub-card" href="faq/doc-faq.php">
          <span class="ic">❓</span>
          <div class="ti">FAQ</div>
          <div class="de">Toutes les questions fréquentes regroupées : Aegis, GameNodePanel, Forum, Blog, Marketplace, Tickets, Sauvegarde.</div>
          <div class="go">Consulter la FAQ →</div>
        </a>
      </div>
    </div>

    <div class="callout">
      <span class="i">📦</span>
      <div><strong>Aegis Framework est gratuit et open-source.</strong> Récupérez-le, étudiez-le ou contribuez sur GitHub :
      <a href="<?= $h($github) ?>" target="_blank" rel="noopener"><?= $h($github) ?></a>.</div>
    </div>

    <div class="doc-foot">
      <span>Aegis Framework v4.0.0 · documentation officielle</span>
      <span><a href="<?= $h($github) ?>" target="_blank" rel="noopener">Dépôt GitHub ↗</a></span>
    </div>

<?php require __DIR__ . '/inc/foot.php'; ?>
