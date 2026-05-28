<?php
declare(strict_types=1);
$pageTitle = 'Article';
require_once __DIR__ . '/_header.php';
db_install($pdo);

$slug = trim((string)($_GET['slug'] ?? ''));

// Ajout de u.first_name et u.last_name dans la requête
$stmt = $pdo->prepare("SELECT a.*, u.email AS author_email, u.first_name, u.last_name, c.label AS category_label, c.slug AS cat_slug FROM articles a JOIN users u ON u.id = a.author_id LEFT JOIN categories c ON c.id = a.category_id WHERE a.slug = :slug AND a.published = 1 LIMIT 1");
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    echo '<div class="container" style="padding:100px 0; text-align:center;"><h1>Article introuvable</h1><br><a href="/" class="btn">Retour</a></div>';
    require __DIR__ . '/_footer.php'; exit;
}

// Logique du lien de retour
$backLink = '/';
$backText = "← Retour à l'accueil";
if (!empty($article['cat_slug'])) {
    $backLink = '/category.php?slug=' . urlencode((string)$article['cat_slug']);
    $backText = "← Retour à la catégorie";
}

// Définition de l'auteur : Prénom Nom, ou Email si vide
$authorName = trim(($article['first_name'] ?? '') . ' ' . ($article['last_name'] ?? ''));
if ($authorName === '') {
    $authorName = $article['author_email'];
}
?>

<div class="article-page-wrap">
    
    <?php if (!empty($article['cover_image'])): ?>
        <div class="article-cover">
            <img src="/uploads/<?= e((string)$article['cover_image']) ?>" alt="Cover">
        </div>
    <?php endif; ?>

    <div class="article-hero">
        <p style="color:var(--gold); font-weight:700; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.15em; margin-bottom:15px;">
            <?= e((string)($article['category_label'] ?? 'Général')) ?>
        </p>
        <h1 class="article-hero__title"><?= e((string)$article['title']) ?></h1>
        <p style="color:#666; font-style:italic;">Par <?= e($authorName) ?> · <?= date('d/m/Y', strtotime($article['created_at'])) ?></p>
    </div>

    <div class="article-body">
        <div class="article-content">
            <?= (string)$article['content_html'] ?>
        </div>
        
        <div style="margin-top:70px; border-top:1px solid var(--line); padding-top:40px;">
            <a href="<?= e($backLink) ?>" style="text-decoration:none; color:var(--gold); font-weight:600; font-size: 1.05rem; transition: color 0.2s;"><?= e($backText) ?></a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>