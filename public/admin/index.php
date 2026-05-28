<?php
declare(strict_types=1);
$pageTitle = 'Admin';
require_once dirname(__DIR__) . '/_header.php';

// Un simple require_login() (via require_admin qui a été allégé) 
// autorise l'accès à cette page globale aux éditeurs et aux admins.
$user = require_admin(); 
db_install($pdo);

$rows = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.published, a.created_at, a.updated_at, a.sort_order,
            c.label AS category_label
     FROM articles a
     LEFT JOIN categories c ON c.id = a.category_id
     ORDER BY c.sort_order ASC, a.sort_order ASC, a.created_at DESC"
)->fetchAll();

// On récupère le nom à afficher dans le message de bienvenue
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if ($displayName === '') {
    $displayName = $user['email'];
}
?>
<div class="card">
  <h1>Administration</h1>
  <p>Connecté en tant que <strong><?= e($displayName) ?></strong> (Rôle : <?= e(strtoupper($user['role'] ?? 'EDITOR')) ?>).</p>
  <div class="top-actions">
    <a class="btn" href="/admin/article-new.php">Nouvel article</a>
    <a class="btn secondary" href="/admin/categories.php">Catégories & ordre</a>
    
    <a class="btn secondary" href="/admin/profile.php" style="background: #9c27b0; color: white;">⚙️ Mon Profil</a>
    
    <?php if (($user['role'] ?? 'editor') === 'admin'): ?>
      <a class="btn secondary" href="/admin/users.php" style="background: #2196F3; color: white;">👥 Gestion des utilisateurs</a>
    <?php endif; ?>
    
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