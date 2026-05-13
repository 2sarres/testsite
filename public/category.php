<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

db_install($pdo);

$slug = trim((string)($_GET['slug'] ?? ''));
$category = $slug !== '' ? category_by_slug($pdo, $slug) : null;
$pageTitle = $category ? ((string)$category['label'] . ' — Sky Atlas') : 'Catégorie introuvable';

require_once __DIR__ . '/_header.php';

if (!$category) {
    http_response_code(404);
    echo '<div class="container"><div class="card"><h1>Catégorie introuvable</h1><p><a href="/">Retour à l’accueil</a></p></div></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT a.id, a.title, a.slug, a.excerpt, a.cover_image, a.content_html, a.created_at
     FROM articles a
     WHERE a.published = 1 AND a.category_id = :cid
     ORDER BY a.sort_order ASC, a.created_at DESC'
);
$stmt->execute([':cid' => (int)$category['id']]);
$rows = $stmt->fetchAll();

$accent = (string)$category['accent_img'];
?>
<div class="container ftg-category-page">
  <div class="card ftg-category-hero">
    <p class="meta"><a href="/">← Accueil</a></p>
    <h1 class="ftg-section__title"><?= e((string)$category['label']) ?></h1>
    <p class="ftg-section__dek"><?= e((string)$category['dek']) ?></p>
    <?php if ($accent !== ''): ?>
      <p class="ftg-category-hero__img"><img src="<?= e($accent) ?>" alt="" width="900" height="420" style="max-width:100%;height:auto;border-radius:12px;"<?= img_external_attrs($accent) ?>></p>
    <?php endif; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="card"><p>Aucun article dans cette catégorie pour le moment.</p></div>
  <?php else: ?>
    <div class="ftg-grid">
      <?php
      foreach ($rows as $row):
          $cardHref = '/article.php?slug=' . urlencode((string)$row['slug']);
          $cardImg = !empty($row['cover_image'])
              ? '/uploads/' . (string)$row['cover_image']
              : $accent;
          $cardCat = (string)$category['label'];
          $cardTitle = (string)$row['title'];
          $cardExcerpt = card_excerpt_preview((string)$row['excerpt'], (string)$row['content_html']);
          if ($cardExcerpt === '') {
              $cardExcerpt = 'Lire l’avis complet.';
          }
          require __DIR__ . '/partials/home-article-card.php';
      endforeach;
      ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
