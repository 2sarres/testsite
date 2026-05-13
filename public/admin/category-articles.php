<?php
declare(strict_types=1);
$pageTitle = 'Ordre des articles';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);

$id = (int)($_GET['id'] ?? 0);
$category = $id > 0 ? category_by_id($pdo, $id) : null;
if (!$category) {
    flash_set('error', 'Catégorie invalide.');
    redirect('/admin/categories.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_article_order'])) {
    csrf_verify_or_fail();
    $orders = $_POST['article_sort'] ?? [];
    if (is_array($orders)) {
        $upd = $pdo->prepare('UPDATE articles SET sort_order = :o, updated_at = :u WHERE id = :id AND category_id = :cid');
        foreach ($orders as $aid => $val) {
            $aid = (int)$aid;
            $o = (int)$val;
            if ($aid > 0) {
                $upd->execute([
                    ':o' => $o,
                    ':u' => date('c'),
                    ':id' => $aid,
                    ':cid' => $id,
                ]);
            }
        }
    }
    flash_set('success', 'Ordre des articles enregistré.');
    redirect('/admin/category-articles.php?id=' . $id);
}

$stmt = $pdo->prepare(
    'SELECT id, title, slug, sort_order, published FROM articles WHERE category_id = :cid ORDER BY sort_order ASC, id ASC'
);
$stmt->execute([':cid' => $id]);
$articles = $stmt->fetchAll();
?>
<div class="card">
  <h1>Ordre des articles — <?= e((string)$category['label']) ?></h1>
  <p class="meta">Plus petit nombre = affiché en premier dans la catégorie (page d’accueil et <a href="/category.php?slug=<?= urlencode((string)$category['slug']) ?>">page liste</a>).</p>
  <div class="top-actions">
    <a class="btn secondary" href="/admin/categories.php">Catégories</a>
    <a class="btn secondary" href="/admin/index.php">Articles</a>
  </div>
</div>

<div class="card">
  <?php if (!$articles): ?>
    <p>Aucun article dans cette catégorie.</p>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="save_article_order" value="1">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid #e5e7eb;">
            <th style="padding:8px;">Ordre</th>
            <th style="padding:8px;">Titre</th>
            <th style="padding:8px;">État</th>
            <th style="padding:8px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($articles as $a): ?>
            <tr style="border-bottom:1px solid #f0f0f0;">
              <td style="padding:8px;">
                <input type="number" name="article_sort[<?= (int)$a['id'] ?>]" value="<?= (int)$a['sort_order'] ?>" style="width:100px;">
              </td>
              <td style="padding:8px;"><?= e((string)$a['title']) ?></td>
              <td style="padding:8px;"><?= ((int)$a['published'] === 1) ? 'publié' : 'brouillon' ?></td>
              <td style="padding:8px;"><a class="btn secondary" href="/admin/article-edit.php?id=<?= (int)$a['id'] ?>">Modifier</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p style="margin-top:14px;"><button class="btn" type="submit">Enregistrer l’ordre</button></p>
    </form>
  <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>
