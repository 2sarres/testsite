<?php
declare(strict_types=1);
$pageTitle = 'Article';
require_once __DIR__ . '/_header.php';
db_install($pdo);

$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = $pdo->prepare("SELECT a.*, u.email AS author_email, c.label AS category_label FROM articles a JOIN users u ON u.id = a.author_id LEFT JOIN categories c ON c.id = a.category_id WHERE a.slug = :slug AND a.published = 1 LIMIT 1");
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    echo '<div class="container" style="padding:100px 0; text-align:center;"><h1>Article introuvable</h1><br><a href="/" class="btn">Retour</a></div>';
    require __DIR__ . '/_footer.php'; exit;
}
?>

<div class="article-page-wrap">
    <div class="article-hero">
        <p style="color:var(--gold); font-weight:600; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.1em; margin-bottom:15px;">
            <?= e((string)($article['category_label'] ?? 'Général')) ?>
        </p>
        <h1 class="article-hero__title"><?= e((string)$article['title']) ?></h1>
        <p style="color:#999; font-style:italic;">Par <?= e((string)$article['author_email']) ?> · <?= date('d/m/Y', strtotime($article['created_at'])) ?></p>
    </div>

    <?php if (!empty($article['cover_image'])): ?>
        <div class="article-cover">
            <img src="/uploads/<?= e((string)$article['cover_image']) ?>" alt="Cover">
        </div>
    <?php endif; ?>

    <div class="article-body">
        <div class="article-content">
            <?= (string)$article['content_html'] ?>
        </div>
        <div style="margin-top:50px; border-top:1px solid #eee; padding-top:30px;">
            <a href="/" style="text-decoration:none; color:var(--gold); font-weight:600;">← Retour à l'accueil</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>