<?php
$docPage = 'modules/forum/doc-forum-content.php';
$seo = [
    'title' => 'Forum — Contenu & Markdown · Documentation Aegis Framework',
    'desc'  => "Rédiger sur le forum Aegis : sujets, réponses, Markdown sur-mesure et extensions BBCode (spoiler, quote, YouTube, couleur), mentions, emojis, réactions, votes, sondages et tags.",
    'canonical' => 'https://gamenodepanel.com/documentation/doc-forum-content.php',
];
require __DIR__ . '/../../inc/head.php';
?>
    <h1>Contenu &amp; Markdown</h1>
    <p class="doc-lead">Le forum utilise un <strong>moteur Markdown maison</strong> doublé d'extensions <strong>BBCode</strong>, le tout rendu de façon <strong>sécurisée</strong>. Voici la syntaxe à disposition de vos membres et les fonctions d'interaction.</p>
    <div class="doc-meta"><span class="doc-pill">Markdown</span><span class="doc-pill">BBCode</span><span class="doc-pill">Réactions &amp; votes</span><span class="doc-pill">Sondages</span></div>

    <h2 id="fc-topics">Sujets</h2>
    <p>Un membre autorisé crée un sujet depuis une catégorie (titre + contenu, et en option des <strong>tags</strong> et un <strong>sondage</strong>). Le titre fait de 3 à 200 caractères, le contenu de 10 à 50 000. Un <strong>anti-spam</strong> impose un délai entre deux publications (ignoré pour les modérateurs).</p>

    <h2 id="fc-replies">Réponses &amp; édition</h2>
    <p>Les réponses suivent les mêmes règles (et la permission <em>Répondre</em> de la catégorie). Un membre peut <strong>éditer</strong> ou <strong>supprimer</strong> ses propres messages (les modérateurs ceux des autres) ; le <strong>premier message</strong> d'un sujet ne peut pas être supprimé seul. La <strong>prévisualisation</strong> en direct rend le Markdown avant publication.</p>

    <h2 id="fc-markdown">Markdown sur-mesure</h2>
    <table class="doc-table">
      <tr><th>Syntaxe</th><th>Rendu</th></tr>
      <tr><td><code># Titre</code> … <code>#### Titre</code></td><td>Titres (h1 à h4)</td></tr>
      <tr><td><code>**gras**</code> · <code>*italique*</code> · <code>~~barré~~</code></td><td>Mise en forme</td></tr>
      <tr><td><code>`code`</code> · <code>``` … ```</code></td><td>Code en ligne / bloc (avec bouton copier)</td></tr>
      <tr><td><code>&gt; citation</code></td><td>Bloc de citation</td></tr>
      <tr><td><code>- liste</code> · <code>1. liste</code></td><td>Listes à puces / numérotées</td></tr>
      <tr><td><code>| a | b |</code></td><td>Tableaux</td></tr>
      <tr><td><code>[texte](url)</code> · <code>![alt](url)</code></td><td>Liens / images</td></tr>
      <tr><td><code>---</code></td><td>Séparateur horizontal</td></tr>
    </table>

    <h2 id="fc-bbcode">Extensions BBCode</h2>
    <table class="doc-table">
      <tr><th>Balise</th><th>Effet</th></tr>
      <tr><td><code>[spoiler]…[/spoiler]</code></td><td>Contenu masqué, révélé au clic</td></tr>
      <tr><td><code>[quote=Pseudo]…[/quote]</code></td><td>Citation attribuée (utilisée par le bouton « Citer »)</td></tr>
      <tr><td><code>[youtube]url[/youtube]</code></td><td>Vidéo YouTube intégrée (l'ID est validé)</td></tr>
      <tr><td><code>[color=#hex|nom]…[/color]</code></td><td>Couleur du texte (valeur restreinte)</td></tr>
      <tr><td><code>[size=sm|md|lg|xl]…[/size]</code></td><td>Taille du texte (valeurs prédéfinies)</td></tr>
    </table>
    <div class="callout"><span class="i">🔐</span><div><strong>Sécurité :</strong> tout le contenu est HTML-échappé <em>avant</em> traitement, les URL passent par un filtre qui bloque <code>javascript:</code>, <code>data:</code>, <code>vbscript:</code>… et les valeurs de couleur/taille/langage sont restreintes par expression régulière. Aucun HTML brut de l'utilisateur n'est exécuté.</div></div>

    <h2 id="fc-mentions">Mentions &amp; emojis</h2>
    <p><code>@pseudo</code> crée un lien vers le profil <strong>et notifie</strong> le membre mentionné. Les raccourcis <code>:emoji:</code> (ex. <code>:fire:</code>, <code>:thumbsup:</code>, <code>:rocket:</code>) sont convertis en emojis unicode.</p>

    <h2 id="fc-reactions">Réactions &amp; votes</h2>
    <p>Chaque message peut recevoir des <strong>réactions</strong> (👍 ❤️ 😂 😮 😢 😡) et, si activés, des <strong>votes ↑/↓</strong>. Le vote pour son propre message est interdit ; une réaction reçue rapporte de l'XP à l'auteur. Les totaux se mettent à jour en AJAX.</p>

    <h2 id="fc-polls">Sondages</h2>
    <p>À la création d'un sujet, on peut joindre un <strong>sondage</strong> (question + ≥ 2 options, choix unique ou multiple, date de fin optionnelle). Les membres votent depuis le sujet ; les résultats s'affichent en barres. (Des <strong>sondages widgets</strong> indépendants existent aussi pour la sidebar/footer — voir Personnalisation.)</p>

    <h2 id="fc-tags">Tags &amp; recherche</h2>
    <p>Les sujets peuvent porter des <strong>tags</strong>. La <strong>recherche</strong> (<code>/forum/search</code>) couvre titres &amp; contenus et se combine avec un filtre par tag ; une liste de tags populaires est proposée. Les pages de recherche sont volontairement <em>noindex</em> (voir SEO).</p>

    <?php require __DIR__ . '/../../inc/foot.php'; ?>
