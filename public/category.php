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
    echo '<div class="container"><div class="card" style="text-align:center; padding: 60px;"><h1>Catégorie introuvable</h1><br><a href="/" class="btn">Retour à l’accueil</a></div></div>';
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

<div class="article-page-wrap">
  <div class="container">
    
    <div style="text-align: center; margin: 40px auto 70px; max-width: 800px;">
      <p class="article-hero__meta" style="margin-bottom: 20px;">
        <a href="/" style="text-decoration:none; color: var(--gold);">← Retour à l'accueil</a>
      </p>
      <h1 style="font-family: 'Cormorant Garamond', Georgia, serif; font-size: clamp(3rem, 5vw, 4rem); color: #111; margin-bottom: 20px;">
        <?= e((string)$category['label']) ?>
      </h1>
      <p style="font-size: 1.15rem; color: #666; line-height: 1.7;">
        <?= e((string)$category['dek']) ?>
      </p>
    </div>

    <?php if (!$rows): ?>
      <div style="text-align:center; padding: 60px; color:#888;">
        <p>Aucun article dans cette catégorie pour le moment.</p>
      </div>
    <?php else: ?>
      <div class="ftg-grid">
        <?php
        foreach ($rows as $row):
            $cardHref = '/article.php?slug=' . urlencode((string)$row['slug']);
            $cardImg = !empty($row['cover_image']) ? '/uploads/' . (string)$row['cover_image'] : $accent;
            $cardCat = (string)$category['label'];
            $cardTitle = (string)$row['title'];
            $cardExcerpt = card_excerpt_preview((string)$row['excerpt'], (string)$row['content_html']);
            
            require __DIR__ . '/partials/home-article-card.php';
        endforeach;
        ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>