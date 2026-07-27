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

$jeton    = acces_jeton_emettre();
$longueur = $reglages['longueur'];   // 0 = longueur libre
$bloque   = $etat['bloque'];

header('X-Robots-Tag: noindex, nofollow');
entete('Accès', 'Cet espace demande un code.', '/acces');
?>

<div class="portail">
  <div class="portail__modale" role="dialog" aria-modal="true" aria-labelledby="portail-titre">

    <h1 class="portail__titre" id="portail-titre"><?= e($CONFIG['titre']) ?></h1>
    <p class="portail__intro">Cet espace est privé. Composez le code d'accès.</p>

    <?php if ($erreur !== ''): ?>
      <p class="portail__erreur" role="alert"><?= e($erreur) ?></p>
    <?php endif; ?>

    <form class="portail__form" id="portail-form" method="post" action="/acces" autocomplete="off">
      <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
      <input type="hidden" name="r" value="<?= e($cible) ?>">

      <?php /* Saisie de secours : seule visible si le JavaScript ne tourne pas. */ ?>
      <div class="portail__repli" id="portail-repli">
        <label class="portail__label" for="code">Code</label>
        <input
          class="portail__champ"
          id="code"
          name="code"
          type="password"
          inputmode="numeric"
          pattern="[0-9]*"
          <?= $longueur > 0 ? 'maxlength="' . $longueur . '"' : '' ?>
          autocomplete="current-password"
          required
          autofocus
          <?= $bloque ? 'disabled' : '' ?>>

        <button class="portail__bouton" type="submit" <?= $bloque ? 'disabled' : '' ?>>
          Entrer
        </button>
      </div>

      <?php /* Clavier cliquable : révélé par le script ci-dessous. */ ?>
      <div class="clavier" id="clavier" hidden>
        <div class="clavier__temoins" id="clavier-temoins" aria-hidden="true">
          <?php for ($i = 0; $i < $longueur; $i++): ?>
            <span class="clavier__temoin"></span>
          <?php endfor; ?>
        </div>

        <p class="clavier__etat" id="clavier-etat" role="status" aria-live="polite"></p>

        <div class="clavier__grille">
          <?php foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $chiffre): ?>
            <button class="clavier__touche" type="button" data-chiffre="<?= $chiffre ?>" <?= $bloque ? 'disabled' : '' ?>><?= $chiffre ?></button>
          <?php endforeach; ?>

          <?php if ($longueur > 0): ?>
            <span class="clavier__creux" aria-hidden="true"></span>
          <?php else: ?>
            <button class="clavier__touche clavier__touche--secondaire" type="button" id="clavier-effacer" aria-label="Effacer le dernier chiffre" <?= $bloque ? 'disabled' : '' ?>>&#9003;</button>
          <?php endif; ?>

          <button class="clavier__touche" type="button" data-chiffre="0" <?= $bloque ? 'disabled' : '' ?>>0</button>

          <?php if ($longueur > 0): ?>
            <button class="clavier__touche clavier__touche--secondaire" type="button" id="clavier-effacer" aria-label="Effacer le dernier chiffre" <?= $bloque ? 'disabled' : '' ?>>&#9003;</button>
          <?php else: ?>
            <button class="clavier__touche clavier__touche--valider" type="button" id="clavier-valider" aria-label="Valider le code" <?= $bloque ? 'disabled' : '' ?>>&#10003;</button>
          <?php endif; ?>
        </div>
      </div>
    </form>

  </div>
</div>

<script>
(function () {
  'use strict';

  var form    = document.getElementById('portail-form');
  var repli   = document.getElementById('portail-repli');
  var clavier = document.getElementById('clavier');
  var champ   = document.getElementById('code');
  if (!form || !repli || !clavier || !champ) return;

  var LONGUEUR = <?= (int) $longueur ?>;   // 0 = longueur libre
  var BLOQUE   = <?= $bloque ? 'true' : 'false' ?>;

  var temoins = document.getElementById('clavier-temoins');
  var etat    = document.getElementById('clavier-etat');
  var effacer = document.getElementById('clavier-effacer');
  var valider = document.getElementById('clavier-valider');

  // Le champ texte n'est plus saisi à la main : il ne sert qu'à porter la
  // valeur jusqu'au POST. « required » sur un champ masqué empêcherait
  // l'envoi, on le retire.
  repli.hidden      = true;
  champ.required    = false;
  champ.autofocus   = false;
  clavier.hidden    = false;

  var code    = '';
  var envoye  = false;

  function annoncer() {
    if (!etat) return;
    if (code.length === 0)  { etat.textContent = 'Aucun chiffre saisi.'; return; }
    etat.textContent = LONGUEUR > 0
      ? code.length + ' chiffre' + (code.length > 1 ? 's' : '') + ' sur ' + LONGUEUR + '.'
      : code.length + ' chiffre' + (code.length > 1 ? 's' : '') + ' saisi' + (code.length > 1 ? 's' : '') + '.';
  }

  function peindre(silencieux) {
    if (!temoins) return;

    if (LONGUEUR > 0) {
      var cases = temoins.children;
      for (var i = 0; i < cases.length; i++) {
        cases[i].classList.toggle('clavier__temoin--plein', i < code.length);
      }
    } else {
      // Longueur libre : un témoin apparaît à chaque chiffre.
      while (temoins.children.length > code.length) {
        temoins.removeChild(temoins.lastChild);
      }
      while (temoins.children.length < code.length) {
        var t = document.createElement('span');
        t.className = 'clavier__temoin clavier__temoin--plein';
        temoins.appendChild(t);
      }
    }
    if (!silencieux) annoncer();
  }

  function envoyer() {
    if (envoye || code === '') return;
    envoye = true;
    champ.value = code;
    clavier.classList.add('clavier--envoi');
    form.submit();
  }

  function ajouter(chiffre) {
    if (BLOQUE || envoye) return;
    if (LONGUEUR > 0 && code.length >= LONGUEUR) return;

    code += chiffre;
    peindre();

    // Court délai : le dernier témoin a le temps de s'afficher.
    if (LONGUEUR > 0 && code.length === LONGUEUR) {
      window.setTimeout(envoyer, 140);
    }
  }

  function retirer() {
    if (BLOQUE || envoye || code === '') return;
    code = code.slice(0, -1);
    peindre();
  }

  clavier.addEventListener('click', function (ev) {
    var touche = ev.target.closest ? ev.target.closest('[data-chiffre]') : null;
    if (touche) ajouter(touche.getAttribute('data-chiffre'));
  });

  if (effacer) effacer.addEventListener('click', retirer);
  if (valider) valider.addEventListener('click', envoyer);

  // Le clavier physique reste utilisable.
  document.addEventListener('keydown', function (ev) {
    if (ev.metaKey || ev.ctrlKey || ev.altKey) return;

    if (ev.key >= '0' && ev.key <= '9') {
      ajouter(ev.key);
      ev.preventDefault();
    } else if (ev.key === 'Backspace') {
      retirer();
      ev.preventDefault();
    } else if (ev.key === 'Enter') {
      envoyer();
      ev.preventDefault();
    }
  });

  peindre(true);
})();
</script>

<?php pied(); ?>
