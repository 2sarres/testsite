<?php
declare(strict_types=1);
$pageTitle = 'Article';
require_once __DIR__ . '/_header.php';

db_install($pdo);
$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    echo '<div class="card"><h1>Article introuvable</h1></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

$stmt = $pdo->prepare(
    "SELECT a.*, u.email AS author_email
     FROM articles a
     JOIN users u ON u.id = a.author_id
     WHERE a.slug = :slug AND a.published = 1
     LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    echo '<div class="card"><h1>Article introuvable</h1></div>';
    require __DIR__ . '/_footer.php';
    exit;
}
?>
<article class="card">
  <h1><?= e((string)$article['title']) ?></h1>
  <p class="meta">
    Publié le <?= e(date('d/m/Y H:i', strtotime((string)$article['created_at']))) ?>
    par <?= e((string)$article['author_email']) ?>
  </p>
  <?php if (!empty($article['cover_image'])): ?>
    <p><img src="/uploads/<?= e((string)$article['cover_image']) ?>" alt="" style="max-width:100%;height:auto;border-radius:8px;"></p>
  <?php endif; ?>
  <div class="article-content">
    <?= (string)$article['content_html'] ?>
  </div>
</article>
<?php require __DIR__ . '/_footer.php'; ?>

