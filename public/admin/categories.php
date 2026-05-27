<?php
declare(strict_types=1);
$pageTitle = 'Catégories';
require_once dirname(__DIR__) . '/_header.php';
$user = require_admin();
db_install($pdo);

// Suppression de catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    csrf_verify_or_fail();
    $catId = (int)$_POST['delete_category'];
    
    // Vérifier qu'il n'y a pas d'articles dans cette catégorie
    $countArticles = articles_count_in_category($pdo, $catId);
    if ($countArticles > 0) {
        flash_set('error', 'Impossible de supprimer cette catégorie : elle contient ' . $countArticles . ' article(s). Déplacez ou supprimez d\'abord les articles.');
    } else {
        $del = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $del->execute([':id' => $catId]);
        flash_set('success', 'Catégorie supprimée avec succès.');
    }
    redirect('/admin/categories.php');
}

// Mise à jour de l'ordre d'affichage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    csrf_verify_or_fail();
    $orders = $_POST['sort_order'] ?? [];
    if (is_array($orders)) {
        $upd = $pdo->prepare('UPDATE categories SET sort_order = :o WHERE id = :id');
        foreach ($orders as $id => $val) {
            $id = (int)$id;
            $o = (int)$val;
            if ($id > 0) {
                $upd->execute([':o' => $o, ':id' => $id]);
            }
        }
    }
    flash_set('success', 'Ordre des catégories enregistré.');
    redirect('/admin/categories.php');
}

$cats = categories_all_ordered($pdo);
?>
<div class="card">
  <h1>Catégories</h1>
  <p>Gérez les rubriques du site, leur ordre d'affichage et l'URL (<code>slug</code>) utilisée pour <a href="/">l'accueil</a> et les pages liste.</p>
  <div class="top-actions">
    <a class="btn" href="/admin/category-new.php">+ Nouvelle catégorie</a>
    <a class="btn secondary" href="/admin/index.php">Retour admin</a>
  </div>
</div>

<div class="card">
  <h2>Ordre d'affichage</h2>
  <p class="meta">Plus petit nombre = affiché plus haut sur l'accueil.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="save_order" value="1">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #e5e7eb;">
          <th style="padding:8px;">Ordre</th>
          <th style="padding:8px;">Libellé</th>
          <th style="padding:8px;">Slug</th>
          <th style="padding:8px;">Articles</th>
          <th style="padding:8px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
          <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:8px;">
              <input type="number" name="sort_order[<?= (int)$c['id'] ?>]" value="<?= (int)$c['sort_order'] ?>" style="width:90px;">
            </td>
            <td style="padding:8px;"><?= e((string)$c['label']) ?></td>
            <td style="padding:8px;"><code><?= e((string)$c['slug']) ?></code></td>
            <td style="padding:8px;"><?= articles_count_in_category($pdo, (int)$c['id']) ?></td>
            <td style="padding:8px;">
              <a class="btn secondary" href="/admin/category-edit.php?id=<?= (int)$c['id'] ?>">Modifier</a>
              <a class="btn secondary" href="/admin/category-articles.php?id=<?= (int)$c['id'] ?>">Ordre articles</a>
              <a class="btn secondary" href="/category.php?slug=<?= urlencode((string)$c['slug']) ?>">Voir</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr ? (La catégorie doit être vide)');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" name="delete_category" value="<?= (int)$c['id'] ?>" class="btn secondary" style="background-color:#dc2626; color:white;">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p style="margin-top:14px;"><button class="btn" type="submit">Enregistrer l'ordre</button></p>
  </form>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>
