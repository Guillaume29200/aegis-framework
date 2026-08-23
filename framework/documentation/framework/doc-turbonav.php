<?php
/**
 * documentation/doc-turbonav.php — TurboNav, la navigation SPA d'Aegis Framework.
 */
$docPage = 'framework/doc-turbonav.php';
$seo = [
    'title'     => 'TurboNav — navigation SPA · Documentation Aegis Framework',
    'desc'      => "TurboNav v2.1.0 : navigation type SPA d'Aegis Framework, sans dépendance. Swap AJAX, cache avec TTL, préchargement au survol, interception des formulaires GET, API JavaScript, événements, en-têtes et helper PHP.",
    'canonical' => 'https://gamenodepanel.com/documentation/framework/doc-turbonav.php',
];
require __DIR__ . '/../inc/head.php';
?>

    <h1>TurboNav <span style="font-weight:400">— navigation SPA</span></h1>
    <p class="doc-lead">TurboNav remplace les rechargements de page complets par des <strong>échanges de contenu en AJAX</strong> : effet « application monopage » (SPA) <strong>sans React, ni Vue, ni aucune dépendance</strong> — du vanilla JS, ~535 lignes.</p>
    <div class="doc-meta">
      <span class="doc-pill">Vanilla JS</span>
      <span class="doc-pill">Zéro dépendance</span>
      <span class="doc-pill">v2.1.0</span>
      <span class="doc-pill">framework/assets/js/turbo-nav.js</span>
    </div>

    <h2 id="tn-intro">Présentation</h2>
    <p>Quand on clique sur un lien interne (ou qu'on soumet un formulaire de recherche/filtre), TurboNav intercepte l'action, récupère la page cible en arrière-plan et <strong>remplace uniquement la zone de contenu</strong> (pas tout le document). Le résultat : pas de flash blanc, l'état de l'interface (menu, scroll, widgets) est préservé, et la navigation paraît instantanée.</p>
    <ul>
      <li><strong>Sans framework</strong> — aucune librairie externe, aucun build.</li>
      <li><strong>Cache mémoire</strong> des pages déjà visitées (jusqu'à 20, avec expiration automatique).</li>
      <li><strong>Préchargement au survol</strong> des liens (~80 ms avant le clic).</li>
      <li><strong>Formulaires <code>GET</code> interceptés</strong> (recherche, filtres, pagination) — pas seulement les liens.</li>
      <li><strong>API JavaScript</strong> (<code>window.TURBONAV</code>) pour naviguer ou invalider le cache depuis votre propre code.</li>
      <li><strong>Dégradation gracieuse</strong> : en cas d'erreur réseau ou de timeout, repli automatique sur un rechargement complet.</li>
    </ul>
    <div class="callout"><span class="i">⚡</span><div>TurboNav s'active globalement depuis <strong>Configuration → ⚡ TurboNav</strong>. Désactivé, le site fonctionne normalement en navigation classique — aucune autre partie du framework n'en dépend.</div></div>

    <h2 id="tn-how">Fonctionnement</h2>
    <ol class="steps">
      <li>Clic sur un lien interne éligible (ou soumission d'un formulaire <code>GET</code> éligible) → TurboNav annule la navigation native.</li>
      <li>Le HTML de la cible est récupéré en <code>fetch</code> (ou servi depuis le cache s'il est encore valide).</li>
      <li>La zone <code>#admin-content</code> de la page actuelle est remplacée par celle de la cible ; les scripts qu'elle contient sont réexécutés.</li>
      <li>Le menu, la barre du haut et le titre de page (<code>.adm-nav</code> / <code>.adm-topnav</code> / <code>.adm-header-title</code>) sont resynchronisés, car l'état « actif » du menu est calculé côté serveur.</li>
      <li>L'URL est mise à jour via <code>history.pushState</code> (le bouton « précédent » fonctionne, y compris pour revenir en arrière depuis le cache).</li>
      <li>Les événements <code>turbonav:before-swap</code> / <code>turbonav:after-swap</code> sont émis pour que le JS de la page se réinitialise.</li>
    </ol>
    <table class="doc-table">
      <tr><th>Paramètre interne</th><th>Valeur par défaut</th><th>Rôle</th></tr>
      <tr><td><code>contentSelector</code></td><td><code>#admin-content</code></td><td>Zone remplacée à chaque navigation</td></tr>
      <tr><td><code>cacheEnabled</code></td><td><code>true</code></td><td>Active/désactive le cache mémoire des pages visitées</td></tr>
      <tr><td><code>cacheMaxSize</code></td><td><code>20</code></td><td>Nombre de pages gardées en cache (éviction de la plus ancienne au-delà)</td></tr>
      <tr><td><code>cacheTTL</code></td><td><code>45000</code> ms</td><td>Durée de vie d'une entrée de cache ; passé ce délai elle est traitée comme absente et re-récupérée</td></tr>
      <tr><td><code>timeout</code></td><td><code>8000</code> ms</td><td>Délai max d'un fetch avant repli sur un rechargement complet</td></tr>
      <tr><td><code>prefetchDelay</code></td><td><code>80</code> ms</td><td>Attente avant déclenchement du préchargement au survol</td></tr>
      <tr><td><code>reloadOnError</code></td><td><code>true</code></td><td>Rechargement complet si le fetch échoue</td></tr>
    </table>
    <div class="callout"><span class="i">ℹ️</span><div>Ces paramètres sont des constantes internes au script (<code>CONFIG</code>), pas une API de configuration à l'exécution — il n'existe pas de <code>window.TURBONAV = { cacheTTL: … }</code>. Seul le drapeau d'activation (<a href="#tn-config">voir plus bas</a>) est lu dynamiquement.</div></div>

    <h2 id="tn-req">Prérequis HTML</h2>
    <p>Pour qu'une page soit « turbonavigable », son contenu principal doit être dans un conteneur d'<strong>id <code>admin-content</code></strong> — c'est lui qui est échangé. Le thème admin d'Aegis le fournit déjà ; vos vues n'ont rien à faire de spécial.</p>
    <pre><code>&lt;main&gt;
  &lt;div id="admin-content"&gt;
    &lt;!-- le contenu de la page (remplacé par TurboNav) --&gt;
  &lt;/div&gt;
&lt;/main&gt;</code></pre>

    <h2 id="tn-config">Activation &amp; configuration</h2>
    <p>TurboNav lit un drapeau injecté par le serveur <strong>avant</strong> de charger son script :</p>
    <pre><code>&lt;script&gt;window.TURBONAV = { enabled: true };&lt;/script&gt;
&lt;script src="/framework/assets/js/turbo-nav.js" defer&gt;&lt;/script&gt;</code></pre>
    <p>Dans Aegis, ce drapeau provient de la constante <code>TURBONAV_ENABLED</code> (lue en base au démarrage), pilotée par la page <strong>Configuration → TurboNav</strong>. Aucune autre intervention n'est requise.</p>
    <p>Une fois initialisé, le script confirme son activation dans la console (<code>[TurboNav] ✅ v2.1.0 actif — préchargement au survol activé</code>) et remplace l'objet <code>window.TURBONAV</code> par son <a href="#tn-api">API publique</a>.</p>

    <h3>Sur les pages publiques d'un module</h3>
    <p>L'administration bénéficie de TurboNav d'office. Pour qu'un <strong>module</strong> en fasse profiter ses pages visiteur, cochez la capacité <strong>⚡ TurboNav</strong> à la génération — ou ajoutez <code>"turbonav"</code> à la clé <code>capabilities</code> de son <code>module.json</code>.</p>
    <p>Le script est alors posé par la variable <code>{{{ body_end }}}</code> du gabarit de pied de page, versionné par la date de modification du fichier — le cache du navigateur se rafraîchit donc tout seul.</p>
    <div class="callout warn"><span class="i">⚡</span><div><strong>Deux conditions, pas une.</strong> Cocher la capacité pose le script ; celui-ci reste <em>en veille</em> tant que TurboNav n'est pas activé dans <strong>Configuration → TurboNav</strong>. La navigation SPA change le comportement de tout le site : le dernier mot revient à l'administrateur, pas au module. Voir <a href="framework/doc-capabilities.php">Fonctionnalités &amp; capacités</a>.</div></div>

    <h2 id="tn-events">Événements</h2>
    <p>TurboNav émet deux événements sur <code>document</code> à chaque navigation. C'est le point d'ancrage pour réinitialiser votre JavaScript.</p>
    <table class="doc-table">
      <tr><th>Événement</th><th>Moment</th><th>Détail (<code>event.detail</code>)</th></tr>
      <tr><td><code>turbonav:before-swap</code></td><td>Juste avant de remplacer le contenu</td><td><code>{ url }</code></td></tr>
      <tr><td><code>turbonav:after-swap</code></td><td>Après l'injection du nouveau contenu <strong>et</strong> le chargement des scripts externes qu'il contient</td><td><code>{ url }</code></td></tr>
    </table>
    <pre><code>document.addEventListener('turbonav:after-swap', function (e) {
  console.log('Nouvelle page :', e.detail.url);
});</code></pre>

    <h2 id="tn-api">API JavaScript</h2>
    <p>Une fois initialisé, TurboNav expose un objet global <code>window.TURBONAV</code> pour piloter la navigation depuis votre propre code :</p>
    <table class="doc-table">
      <tr><th>Méthode</th><th>Rôle</th></tr>
      <tr><td><code>TURBONAV.navigate(url, pushState = true)</code></td><td>Déclenche une navigation TurboNav vers <code>url</code>, comme si l'utilisateur avait cliqué un lien. <code>pushState = false</code> évite d'ajouter une entrée dans l'historique (remplacement silencieux).</td></tr>
      <tr><td><code>TURBONAV.invalidateCache(url)</code></td><td>Supprime une entrée précise du cache. Sans argument, vide <strong>tout</strong> le cache.</td></tr>
      <tr><td><code>TURBONAV.prefetch(url)</code></td><td>Lance un préchargement manuel, identique à celui déclenché par le survol.</td></tr>
    </table>
    <pre><code>// Après une suppression AJAX, invalider le cache de la liste puis y retourner
fetch('/admin/items/42', { method: 'DELETE' }).then(function () {
  TURBONAV.invalidateCache('/admin/items');
  TURBONAV.navigate('/admin/items');
});</code></pre>
    <div class="callout warn"><span class="i">⚠️</span><div><code>window.TURBONAV</code> n'expose cette API qu'<strong>après</strong> l'initialisation du script (le drapeau <code>{ enabled: true }</code> lu, une seule fois). Si votre code peut s'exécuter avant, protégez l'appel avec <code>typeof TURBONAV !== 'undefined' &amp;&amp; TURBONAV.navigate(...)</code> ou placez-le dans un écouteur <code>turbonav:after-swap</code>.</div></div>

    <h2 id="tn-prefetch">Préchargement au survol</h2>
    <p>Dès que la souris reste ~80 ms sur un lien éligible, TurboNav lance le <code>fetch</code> en avance (avec l'en-tête <a href="#tn-headers"><code>X-TurboNav-Prefetch</code></a>). Au clic, la page est souvent déjà prête → navigation quasi instantanée. Le préchargement est dédoublonné (un lien survolé plusieurs fois n'est récupéré qu'une fois) et alimente directement le cache utilisé par une navigation ultérieure.</p>
    <p>Quitter le lien avant la fin du délai annule le préchargement programmé — y compris pour les liens contenant à la fois une icône et du texte.</p>

    <h2 id="tn-forms">Formulaires GET</h2>
    <p>Depuis la v2.1.0, TurboNav intercepte aussi la soumission des <strong>formulaires en méthode <code>GET</code></strong> (recherche, filtres, pagination…) : la requête part en <code>fetch</code> et seule la zone de contenu est remplacée, au lieu d'un rechargement complet de page.</p>
    <p>Un formulaire est intercepté si :</p>
    <ul>
      <li>sa méthode est <code>GET</code> (attribut <code>method="get"</code>, ou absence d'attribut — le défaut HTML) ;</li>
      <li>sa cible est <code>_self</code> (pas de <code>target</code>, ou <code>target="_self"</code>) ;</li>
      <li>son <code>action</code> pointe vers une URL de même origine ;</li>
      <li>il ne porte pas <code>data-no-turbonav</code> (ou l'alias <code>data-no-turbo</code>).</li>
    </ul>
    <pre><code>&lt;form action="/admin/logs" method="get"&gt;
  &lt;input type="text" name="q" placeholder="Rechercher…"&gt;
  &lt;button type="submit"&gt;Filtrer&lt;/button&gt;
&lt;/form&gt;</code></pre>
    <div class="callout"><span class="i">💡</span><div>Les formulaires <code>POST</code> ne sont <strong>jamais</strong> interceptés (ils mutent des données) : ils déclenchent toujours une soumission classique, avec rechargement complet.</div></div>

    <h2 id="tn-optout">Exclure un lien ou un formulaire (opt-out)</h2>
    <p>Pour forcer une navigation classique (rechargement complet) sur un élément précis — téléchargement, déconnexion, page hors zone admin — ajoutez l'attribut <code>data-no-turbonav</code> :</p>
    <pre><code>&lt;a href="/export.csv" data-no-turbonav&gt;Exporter&lt;/a&gt;
&lt;a href="/logout" data-no-turbonav&gt;Déconnexion&lt;/a&gt;
&lt;form action="/admin/export" method="get" data-no-turbonav&gt;…&lt;/form&gt;</code></pre>
    <div class="callout"><span class="i">💡</span><div>L'alias <code>data-no-turbo</code> (sans le suffixe <code>-nav</code>) est également reconnu, pour compatibilité — les deux orthographes ont un effet strictement identique sur les liens comme sur les formulaires. Préférez <code>data-no-turbonav</code> dans du nouveau code.</div></div>
    <p>Sont déjà ignorés automatiquement, sans rien ajouter : liens externes (autre origine), ancres <code>#</code>, <code>javascript:</code>, <code>target="_blank"</code> (ou tout <code>target</code> autre que <code>_self</code>), liens <code>download</code>, et — côté formulaires — toute méthode autre que <code>GET</code>.</p>

    <h2 id="tn-headers">En-têtes HTTP &amp; intégration PHP</h2>
    <p>Chaque requête envoyée par TurboNav porte des en-têtes qui permettent au backend de distinguer une navigation TurboNav d'un chargement de page classique — et un simple préchargement au survol d'un vrai clic :</p>
    <table class="doc-table">
      <tr><th>En-tête</th><th>Présent quand</th><th>Valeur</th></tr>
      <tr><td><code>X-TurboNav</code></td><td>Toute requête émise par TurboNav (clic, formulaire, préchargement, <code>TURBONAV.navigate()</code>)</td><td><code>1</code></td></tr>
      <tr><td><code>X-TurboNav-Prefetch</code></td><td>Uniquement les requêtes de préchargement au survol</td><td><code>1</code></td></tr>
      <tr><td><code>X-Requested-With</code></td><td>Toute requête émise par TurboNav</td><td><code>XMLHttpRequest</code></td></tr>
    </table>
    <p>Côté PHP, Aegis expose le helper global <code>is_turbonav_prefetch(): bool</code> pour réagir spécifiquement à un préchargement — typiquement pour <strong>ne pas</strong> déclencher un effet de bord (compteur de vues, log d'audit, notification) simplement parce que l'utilisateur a survolé un lien sans jamais cliquer :</p>
    <pre><code>// Dans un contrôleur : n'incrémente le compteur de vues
// que sur une vraie navigation, pas un simple survol
if (!is_turbonav_prefetch()) {
    $this->articleService->incrementViews($id);
}</code></pre>
    <div class="callout"><span class="i">💡</span><div>Un préchargement charge réellement la page (le HTML est généré côté serveur comme pour une requête normale) — seul le <em>rendu</em> n'a pas encore eu lieu tant que l'utilisateur n'a pas cliqué. <code>is_turbonav_prefetch()</code> sert donc à protéger les effets de bord métier, pas à éviter le travail de rendu lui-même.</div></div>

    <h2 id="tn-js">Écrire du JS compatible</h2>
    <p>Le piège classique : un script qui s'initialise au <code>DOMContentLoaded</code> ne se réexécute pas après un swap TurboNav (le document n'est pas rechargé). Bonnes pratiques :</p>
    <ul>
      <li><strong>Initialisez</strong> votre JS aussi sur <code>turbonav:after-swap</code> (pas seulement au chargement).</li>
      <li><strong>Nettoyez</strong> timers, intervalles et écouteurs globaux sur <code>turbonav:before-swap</code>.</li>
      <li><strong>Délimitez</strong> votre code à la zone de contenu pour éviter les doublons d'écouteurs.</li>
    </ul>
    <pre><code>function initMaPage() {
  var el = document.getElementById('mon-widget');
  if (!el || el.dataset.ready) return;   // évite la double-init
  el.dataset.ready = '1';
  // … votre logique …
}

// 1) Au premier chargement
document.addEventListener('DOMContentLoaded', initMaPage);
// 2) Après chaque navigation TurboNav
document.addEventListener('turbonav:after-swap', initMaPage);

// Nettoyage avant le prochain swap
document.addEventListener('turbonav:before-swap', function () {
  clearInterval(window.__monTimer);
});</code></pre>
    <div class="callout warn"><span class="i">⚠️</span><div>Si un widget « disparaît » ou « se duplique » après navigation, c'est presque toujours une init non rejouée (manque <code>after-swap</code>) ou un écouteur non nettoyé (<code>before-swap</code>).</div></div>

    <h3>Scripts inline et <code>&lt;script src&gt;</code></h3>
    <p>Après un swap, TurboNav réexécute les balises <code>&lt;script&gt;</code> inline présentes dans le contenu injecté — nécessaire car un navigateur n'exécute jamais un <code>&lt;script&gt;</code> inséré via <code>innerHTML</code>. Ces scripts sont volontairement exécutés <strong>en portée globale</strong> (pas dans une IIFE) : une déclaration <code>function maFonction(){}</code> reste appelable depuis un <code>onclick="maFonction()"</code> après navigation. Conséquence acceptée de ce choix : en cas de revisite d'une page, la console peut afficher un avertissement bénin du type <code>« maFonction has already been declared »</code>, sans impact fonctionnel.</p>
    <p>Les balises <code>&lt;script src="…"&gt;</code> déjà présentes au premier chargement de la page ne sont pas rechargées lors d'un swap (déduplication interne) ; un script externe encore inconnu est chargé une seule fois, avant l'exécution des scripts inline, puis mémorisé pour les swaps suivants.</p>

    <h2 id="tn-changelog">Historique</h2>
    <p>Dernière évolution notable : <strong>v2.1.0</strong></p>
    <ul>
      <li>Interception automatique des <a href="#tn-forms">formulaires <code>GET</code></a> (recherche, filtres).</li>
      <li>Cache avec durée de vie (<code>cacheTTL</code>, 45 s par défaut) — une entrée périmée est traitée comme absente et re-récupérée.</li>
      <li>En-tête <code>X-TurboNav-Prefetch</code> + helper PHP <code>is_turbonav_prefetch()</code> pour distinguer préchargement et vraie navigation côté serveur.</li>
      <li>Alias d'attribut <code>data-no-turbo</code> accepté en plus de <code>data-no-turbonav</code>, sur les liens comme sur les formulaires.</li>
      <li>Correctif : quitter la souris d'un lien avant la fin du délai de préchargement annule désormais correctement la requête programmée, y compris pour les liens combinant icône et texte.</li>
    </ul>

    <h2 id="tn-tshoot">Dépannage</h2>
    <table class="doc-table">
      <tr><th>Symptôme</th><th>Cause / solution</th></tr>
      <tr><td>Le contenu ne change pas en AJAX</td><td>TurboNav désactivé (Configuration → TurboNav) ou <code>#admin-content</code> absent de la page cible.</td></tr>
      <tr><td>Mon script ne tourne plus après navigation</td><td>Init liée à <code>DOMContentLoaded</code> uniquement → écoutez aussi <code>turbonav:after-swap</code>.</td></tr>
      <tr><td>Écouteurs/timers en double</td><td>Pas de nettoyage sur <code>turbonav:before-swap</code> + pas de garde anti double-init.</td></tr>
      <tr><td>Un lien ou un formulaire doit recharger toute la page</td><td>Ajoutez <code>data-no-turbonav</code> (alias <code>data-no-turbo</code> accepté).</td></tr>
      <tr><td>Mon formulaire de recherche ne passe pas en AJAX</td><td>Vérifiez <code>method="get"</code>, l'absence de <code>target</code>, une action de même origine, et l'absence de <code>data-no-turbonav</code>.</td></tr>
      <tr><td>« X has already been declared » dans la console</td><td>Avertissement bénin lié à la réexécution des scripts inline en portée globale — sans effet sur le fonctionnement (voir <a href="#tn-js">Écrire du JS compatible</a>).</td></tr>
      <tr><td>Rechargement complet inattendu</td><td>Repli automatique : le fetch a échoué ou dépassé le timeout (8 s par défaut).</td></tr>
      <tr><td>Une page affiche des données obsolètes après une modification ailleurs</td><td>Le cache mémoire (TTL 45 s) a servi une version encore valide mais périmée métier — invalidez-la avec <code>TURBONAV.invalidateCache(url)</code>.</td></tr>
    </table>

    <?php require __DIR__ . '/../inc/foot.php'; ?>
