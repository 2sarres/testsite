<?php
declare(strict_types=1);
$pageTitle = 'Admin';
require_once dirname(__DIR__) . '/_header.php';
$user = require_admin();
db_install($pdo);

$rows = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.published, a.created_at, a.updated_at, a.sort_order,
            c.label AS category_label
     FROM articles a
     LEFT JOIN categories c ON c.id = a.category_id
     ORDER BY c.sort_order ASC, a.sort_order ASC, a.created_at DESC"
)->fetchAll();
?>
<div class="card">
  <h1>Administration</h1>
  <p>Connecté en tant que <?= e((string)$user['email']) ?>.</p>
  <div class="top-actions">
    <a class="btn" href="/admin/article-new.php">Nouvel article</a>
    <a class="btn secondary" href="/admin/categories.php">Catégories & ordre</a>
    <a class="btn secondary" href="/">Voir le site</a>
  </div>
</div>

<div class="card">
  <h2>Articles</h2>
  <?php if (!$rows): ?>
    <p>Aucun article pour l'instant.</p>
  <?php else: ?>
    <?php foreach ($rows as $r): ?>
      <div style="padding:10px 0;border-bottom:1px solid #e5e7eb;">
        <strong><?= e((string)$r['title']) ?></strong><br>
        <span class="meta">
          <?= e((string)($r['category_label'] ?? '—')) ?> ·
          ordre <?= (int)($r['sort_order']) ?> ·
          slug: <?= e((string)$r['slug']) ?> -
          <?= ((int)$r['published'] === 1) ? 'publié' : 'brouillon' ?> -
          MAJ <?= e(date('d/m/Y H:i', strtotime((string)$r['updated_at']))) ?>
        </span><br>
        <a class="btn secondary" href="/admin/article-edit.php?id=<?= (int)$r['id'] ?>">Modifier</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>

