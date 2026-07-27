<?php
const ACCES_LIBRE = true;
require dirname(__DIR__) . '/src/amorce.php';

$reglages = acces_reglages();

// Verrou désactivé : cette page n'a pas lieu d'être.
if (!$reglages['actif']) {
    header('Location: /', true, 302);
    exit;
}

// Déconnexion
if (isset($_GET['sortie'])) {
    acces_fermer();
    header('Location: /acces', true, 302);
    exit;
}

if (acces_ouvert()) {
    header('Location: ' . acces_cible_sure((string) ($_GET['r'] ?? '/')), true, 302);
    exit;
}

$erreur = '';
$etat   = acces_etat_essais();
$cible  = acces_cible_sure((string) ($_GET['r'] ?? '/'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etat = acces_etat_essais();

    if ($etat['bloque']) {
        $erreur = 'Trop de tentatives. Réessayez dans ' . ceil($etat['secondes'] / 60) . ' minutes.';
    } elseif (!acces_jeton_valide((string) ($_POST['jeton'] ?? ''))) {
        $erreur = 'Formulaire expiré. Recommencez.';
    } else {
        $saisie = (string) ($_POST['code'] ?? '');
        // Le délai constant limite l'intérêt d'une mesure de temps de réponse.
        usleep(random_int(120000, 260000));

        if (password_verify($saisie, $reglages['empreinte'])) {
            acces_effacer_essais();
            acces_ouvrir();
            header('Location: ' . acces_cible_sure((string) ($_POST['r'] ?? '/')), true, 302);
            exit;
        }

        acces_compter_echec();
        $etat   = acces_etat_essais();
        $erreur = $etat['restant'] > 0
            ? 'Code incorrect. ' . $etat['restant'] . ' tentative' . ($etat['restant'] > 1 ? 's' : '') . ' restante' . ($etat['restant'] > 1 ? 's' : '') . '.'
            : 'Trop de tentatives. Réessayez dans ' . ceil($etat['secondes'] / 60) . ' minutes.';
    }
}

$jeton = acces_jeton_emettre();

header('X-Robots-Tag: noindex, nofollow');
entete('Accès', 'Cet espace demande un code.', '/acces');
?>

<div class="portail">
  <h1 class="portail__titre"><?= e($CONFIG['titre']) ?></h1>
  <p class="portail__intro">Cet espace est privé. Saisissez le code d'accès.</p>

  <?php if ($erreur !== ''): ?>
    <p class="portail__erreur" role="alert"><?= e($erreur) ?></p>
  <?php endif; ?>

  <form class="portail__form" method="post" action="/acces" autocomplete="off">
    <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
    <input type="hidden" name="r" value="<?= e($cible) ?>">

    <label class="portail__label" for="code">Code</label>
    <input
      class="portail__champ"
      id="code"
      name="code"
      type="password"
      inputmode="numeric"
      autocomplete="current-password"
      required
      autofocus
      <?= $etat['bloque'] ? 'disabled' : '' ?>>

    <button class="portail__bouton" type="submit" <?= $etat['bloque'] ? 'disabled' : '' ?>>
      Entrer
    </button>
  </form>
</div>

<?php pied(); ?>
