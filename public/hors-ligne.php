<?php
const ACCES_LIBRE = true;
require dirname(__DIR__) . '/src/amorce.php';

http_response_code(200);
header('X-Robots-Tag: noindex');
entete('Hors ligne', 'Aucune connexion réseau.', '/hors-ligne');
?>

<div class="vide">
  <h2 class="vide__titre">Pas de connexion</h2>
  <p>
    Cette page n'est pas disponible hors ligne. Les articles déjà consultés
    restent lisibles : revenez au <a href="/">journal</a> pour y accéder.
  </p>
</div>

<?php pied(); ?>
