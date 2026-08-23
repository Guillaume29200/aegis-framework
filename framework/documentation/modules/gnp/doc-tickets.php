<?php
/**
 * Ancienne page du module Tickets — déplacée.
 *
 * Tickets était une extension payante de GameNodePanel. Le module autonome
 * n'existe plus : son support est désormais pré-intégré à GameNodeHosting,
 * sans surcoût, et documenté là-bas.
 *
 * Cette page est conservée en redirection permanente : l'adresse a été
 * publiée, la supprimer casserait les liens existants.
 */
$cible = '../gnh/doc-gnh-tickets.php';

if (!headers_sent()) {
    header('Location: ' . $cible, true, 301);
}
?><!doctype html>
<meta charset="utf-8">
<meta http-equiv="refresh" content="0; url=<?= htmlspecialchars($cible, ENT_QUOTES, 'UTF-8') ?>">
<title>Page déplacée — Support GameNodeHosting</title>
<p>Le module Tickets est désormais intégré à GameNodeHosting, sans surcoût.
   <a href="<?= htmlspecialchars($cible, ENT_QUOTES, 'UTF-8') ?>">Ouvrir la nouvelle page →</a></p>
