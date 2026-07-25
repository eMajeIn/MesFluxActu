<?php
require dirname(__DIR__) . '/src/amorce.php';

$articles = array_slice(Contenu::tous(), 0, 30);
$dernier  = $articles[0]['horodatage'] ?? time();

header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?= e($CONFIG['titre']) ?></title>
    <link><?= e(url_site('/')) ?></link>
    <description><?= e($CONFIG['description']) ?></description>
    <language><?= e($CONFIG['langue']) ?></language>
    <lastBuildDate><?= e(date(DATE_RSS, $dernier)) ?></lastBuildDate>
    <atom:link href="<?= e(url_site('/rss.xml')) ?>" rel="self" type="application/rss+xml"/>
<?php foreach ($articles as $article):
    $lien = url_site('/a/' . $article['slug']);
?>
    <item>
      <title><?= e($article['titre']) ?></title>
      <link><?= e($lien) ?></link>
      <guid isPermaLink="true"><?= e($lien) ?></guid>
      <pubDate><?= e(date(DATE_RSS, $article['horodatage'])) ?></pubDate>
      <description><?= e($article['chapeau'] ?? '') ?></description>
      <content:encoded xmlns:content="http://purl.org/rss/1.0/modules/content/"><![CDATA[<?= str_replace(']]>', ']]&gt;', Markdown::versHtml((string) ($article['corps'] ?? ''))) ?>]]></content:encoded>
<?php foreach ($article['tags'] as $tag): ?>
      <category><?= e((string) $tag) ?></category>
<?php endforeach; ?>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
