<?php
require dirname(__DIR__) . '/src/amorce.php';

$laboActif = isset($_GET['labo']) ? preg_replace('/[^a-z0-9_-]/', '', (string) $_GET['labo']) : '';

if ($laboActif !== '' && isset($CONFIG['labos'][$laboActif])) {
    $articles = Contenu::parLabo($laboActif);
    $titrePage = labo_infos($laboActif)[0];
    $canonique = '/labo/' . $laboActif;
} else {
    $laboActif = '';
    $articles  = Contenu::tous();
    $titrePage = '';
    $canonique = '/';
}

$articles = array_slice($articles, 0, $CONFIG['par_page']);

entete($titrePage, '', $canonique);
?>

<nav class="filtres" aria-label="Filtrer par laboratoire">
  <a class="filtre<?= $laboActif === '' ? ' est-actif' : '' ?>" href="/">Tout</a>
<?php foreach ($CONFIG['labos'] as $cle => [$libelle, $teinte]): ?>
  <a class="filtre<?= $laboActif === $cle ? ' est-actif' : '' ?>" href="/labo/<?= e($cle) ?>"><?= e($libelle) ?></a>
<?php endforeach; ?>
</nav>

<?php if ($articles === []): ?>
  <div class="vide">
    <h2 class="vide__titre">Rien encore ici</h2>
    <p>Le premier article paraîtra dès que l'agent aura trouvé une nouveauté qui mérite d'être racontée.</p>
  </div>
<?php else: ?>
  <div class="journal">
  <?php foreach ($articles as $article):
      [$libelleLabo, $teinte] = labo_infos($article['labo']);
      $duree = Contenu::dureeLecture($article);
      $nbSources = count($article['sources']);
  ?>
    <article class="entree" style="--teinte: <?= e($teinte) ?>">
      <div class="entree__date">
        <time datetime="<?= e(date('Y-m-d', $article['horodatage'])) ?>">
          <span class="entree__jour"><?= e(date('d.m', $article['horodatage'])) ?></span>
          <span class="entree__annee"><?= e(date('Y', $article['horodatage'])) ?></span>
        </time>
      </div>
      <div class="entree__corps">
        <a class="jeton" href="/labo/<?= e($article['labo']) ?>"><?= e($libelleLabo) ?></a>
        <h2 class="entree__titre">
          <a href="/a/<?= e($article['slug']) ?>"><?= e($article['titre']) ?></a>
        </h2>
        <?php if (!empty($article['chapeau'])): ?>
          <p class="entree__chapeau"><?= e($article['chapeau']) ?></p>
        <?php endif; ?>
        <p class="meta">
          <?= $duree ?> min de lecture<?= $nbSources > 0 ? ' · ' . $nbSources . ' source' . ($nbSources > 1 ? 's' : '') : '' ?>
        </p>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php pied(); ?>
