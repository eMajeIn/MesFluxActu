<?php
require dirname(__DIR__) . '/src/amorce.php';

$tag = isset($_GET['tag']) ? mb_strtolower(trim(rawurldecode((string) $_GET['tag']))) : '';
$tag = preg_replace('/[^\p{L}\p{N} _-]/u', '', $tag) ?? '';

$articles = $tag !== '' ? Contenu::parTag($tag) : [];

entete('#' . $tag, 'Articles étiquetés ' . $tag, '/t/' . rawurlencode($tag));
?>

<a class="retour" href="/">← Le journal</a>

<div class="article__entete">
  <h1 class="article__titre">#<?= e($tag) ?></h1>
  <p class="meta"><?= count($articles) ?> article<?= count($articles) > 1 ? 's' : '' ?></p>
</div>

<?php if ($articles === []): ?>
  <div class="vide">
    <h2 class="vide__titre">Aucun article sous cette étiquette</h2>
    <p>Essayez une autre entrée, ou <a href="/">parcourez le journal complet</a>.</p>
  </div>
<?php else: ?>
  <div class="journal">
  <?php foreach ($articles as $article):
      [$libelleLabo, $teinte] = labo_infos($article['labo']);
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
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php pied(); ?>
