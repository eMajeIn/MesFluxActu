<?php
require dirname(__DIR__) . '/src/amorce.php';

$slug    = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]/', '', (string) $_GET['slug']) : '';
$article = $slug !== '' ? Contenu::parSlug($slug) : null;

if ($article === null) {
    http_response_code(404);
    entete('Page introuvable', '', '/');
    ?>
    <div class="vide">
      <h2 class="vide__titre">Cet article n'existe pas</h2>
      <p>Il a peut-être été retiré, ou l'adresse comporte une coquille. <a href="/">Revenir au journal</a>.</p>
    </div>
    <?php
    pied();
    exit;
}

[$libelleLabo, $teinte] = labo_infos($article['labo']);
$voisins = Contenu::voisins($article['slug']);

entete($article['titre'], (string) ($article['chapeau'] ?? ''), '/a/' . $article['slug']);
?>

<a class="retour" href="/">← Le journal</a>

<article style="--teinte: <?= e($teinte) ?>">
  <header class="article__entete">
    <a class="jeton" href="/labo/<?= e($article['labo']) ?>"><?= e($libelleLabo) ?></a>
    <h1 class="article__titre"><?= e($article['titre']) ?></h1>
    <?php if (!empty($article['chapeau'])): ?>
      <p class="article__chapeau"><?= e($article['chapeau']) ?></p>
    <?php endif; ?>
    <p class="meta">
      <time datetime="<?= e(date('c', $article['horodatage'])) ?>"><?= e(date_longue($article['horodatage'])) ?></time>
      · <?= Contenu::dureeLecture($article) ?> min
      <?php if (!empty($article['maj'])): ?>
        · mis à jour le <?= e(date_longue((int) strtotime((string) $article['maj']))) ?>
      <?php endif; ?>
    </p>
  </header>

  <div class="prose">
    <?= Markdown::versHtml((string) ($article['corps'] ?? '')) ?>
  </div>

  <?php if ($article['sources'] !== []): ?>
    <section class="sources">
      <h2 class="sources__titre">Sources</h2>
      <ul class="sources__liste">
        <?php foreach ($article['sources'] as $source):
            $domaine = parse_url((string) $source['url'], PHP_URL_HOST) ?: '';
        ?>
          <li>
            <a href="<?= e($source['url']) ?>" rel="noopener noreferrer" target="_blank">
              <?= e($source['titre'] ?? $source['url']) ?>
            </a>
            <span class="sources__domaine"><?= e(preg_replace('/^www\./', '', $domaine)) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php if ($article['tags'] !== []): ?>
    <div class="etiquettes">
      <?php foreach ($article['tags'] as $tag): ?>
        <a class="etiquette" href="/t/<?= e(rawurlencode(mb_strtolower((string) $tag))) ?>">#<?= e($tag) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</article>

<?php if ($voisins['precedent'] !== null || $voisins['suivant'] !== null): ?>
  <nav class="voisins" aria-label="Articles voisins">
    <?php if ($voisins['precedent'] !== null): ?>
      <a class="voisin" href="/a/<?= e($voisins['precedent']['slug']) ?>">
        <span class="voisin__role">Précédent</span>
        <span class="voisin__titre"><?= e($voisins['precedent']['titre']) ?></span>
      </a>
    <?php else: ?>
      <span></span>
    <?php endif; ?>

    <?php if ($voisins['suivant'] !== null): ?>
      <a class="voisin est-suivant" href="/a/<?= e($voisins['suivant']['slug']) ?>">
        <span class="voisin__role">Suivant</span>
        <span class="voisin__titre"><?= e($voisins['suivant']['titre']) ?></span>
      </a>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<?php pied(); ?>
