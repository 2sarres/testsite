<?php
declare(strict_types=1);
$pageTitle = 'Modifier la catégorie';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'Catégorie invalide.');
    redirect('/admin/categories.php');
}

$cat = category_by_id($pdo, $id);
if (!$cat) {
    flash_set('error', 'Catégorie introuvable.');
    redirect('/admin/categories.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();

    if (isset($_POST['delete']) && (string)$_POST['delete'] === '1') {
        if (articles_count_in_category($pdo, $id) > 0) {
            flash_set('error', 'Impossible de supprimer : des articles sont encore dans cette catégorie. Déplacez-les d’abord.');
            redirect('/admin/category-edit.php?id=' . $id);
        }
        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
        flash_set('success', 'Catégorie supprimée.');
        redirect('/admin/categories.php');
    }

    $label = trim((string)($_POST['label'] ?? ''));
    $slugManual = trim((string)($_POST['slug'] ?? ''));
    $dek = trim((string)($_POST['dek'] ?? ''));
    $accentImg = trim((string)($_POST['accent_img'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($label === '') {
        $errors[] = 'Le libellé est obligatoire.';
    }
    $slugBase = $slugManual !== '' ? $slugManual : $label;
    if (normalize_slug($slugBase) === '') {
        $errors[] = 'Slug invalide.';
    }
    if ($accentImg === '') {
        $errors[] = 'L’URL de l’image décorative est obligatoire (https).';
    }

    if (!$errors) {
        $newSlug = make_unique_category_slug($pdo, $slugBase, $id);
        $pdo->prepare(
            'UPDATE categories SET slug = :slug, label = :label, dek = :dek, accent_img = :img, sort_order = :so WHERE id = :id'
        )->execute([
            ':slug' => $newSlug,
            ':label' => $label,
            ':dek' => $dek,
            ':img' => $accentImg,
            ':so' => $sortOrder,
            ':id' => $id,
        ]);
        $pdo->prepare('UPDATE articles SET category_slug = :s WHERE category_id = :id')->execute([
            ':s' => $newSlug,
            ':id' => $id,
        ]);
        flash_set('success', 'Catégorie mise à jour.');
        redirect('/admin/category-edit.php?id=' . $id);
    }
}

$cat = category_by_id($pdo, $id);
?>
<div class="card">
  <h1>Modifier la catégorie</h1>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Libellé</label>
    <input type="text" name="label" required value="<?= e((string)$cat['label']) ?>">
    <label>Slug URL</label>
    <input type="text" name="slug" required value="<?= e((string)$cat['slug']) ?>">
    <label>Chapô</label>
    <textarea name="dek" rows="3"><?= e((string)$cat['dek']) ?></textarea>
    <label>Image décorative (URL https)</label>
    <input type="url" name="accent_img" required value="<?= e((string)$cat['accent_img']) ?>">
    <label>Ordre</label>
    <input type="number" name="sort_order" value="<?= (int)$cat['sort_order'] ?>">
    <p>
      <button class="btn" type="submit">Enregistrer</button>
      <a class="btn secondary" href="/admin/categories.php">Retour</a>
    </p>
  </form>
</div>

<div class="card">
  <h2>Supprimer</h2>
  <p class="meta">Uniquement si aucun article n’utilise cette catégorie.</p>
  <form method="post" onsubmit="return confirm('Supprimer cette catégorie ?');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="delete" value="1">
    <button class="btn danger" type="submit">Supprimer</button>
  </form>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>
