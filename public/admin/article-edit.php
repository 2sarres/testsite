<?php
declare(strict_types=1);
$pageTitle = "Modifier l'article";
require_once dirname(__DIR__) . '/_header.php';
$user = require_admin();
db_install($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'Article invalide.');
    redirect('/admin/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch();
if (!$article) {
    flash_set('error', 'Article introuvable.');
    redirect('/admin/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();

    if (isset($_POST['delete']) && $_POST['delete'] === '1') {
        $del = $pdo->prepare("DELETE FROM articles WHERE id = :id");
        $del->execute([':id' => $id]);
        flash_set('success', 'Article supprimé.');
        redirect('/admin/index.php');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $contentHtml = (string)($_POST['content_html'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;
    $coverImage = (string)($article['cover_image'] ?? '');
    $cats = categories_all_ordered($pdo);
    $validCatIds = array_map('intval', array_column($cats, 'id'));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    if (!in_array($categoryId, $validCatIds, true)) {
        $categoryId = (int)($validCatIds[0] ?? 0);
    }
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    if ($sortOrder <= 0) {
        $sortOrder = (int)($article['sort_order'] ?? 0);
    }

    if ($title === '') {
        $errors[] = 'Le titre est obligatoire.';
    }
    if (mb_strlen($excerpt, 'UTF-8') > 500) {
        $errors[] = 'L’extrait doit faire 500 caractères max.';
    }
    if (trim(strip_tags($contentHtml)) === '') {
        $errors[] = 'Le contenu est obligatoire.';
    }

    if (!empty($_FILES['cover_image']) && is_array($_FILES['cover_image'])) {
        $imgName = validate_uploaded_image($_FILES['cover_image']);
        if ($imgName === null && (int)($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Image de couverture invalide.';
        } elseif ($imgName !== null) {
            $target = dirname(__DIR__, 2) . '/public/uploads/' . $imgName;
            if (!move_uploaded_file((string)$_FILES['cover_image']['tmp_name'], $target)) {
                $errors[] = "Impossible d'enregistrer l'image de couverture.";
            } else {
                $coverImage = $imgName;
            }
        }
    }

    if (!$errors) {
        $slug = make_unique_slug($pdo, $title, $id);
        $catSlug = category_slug_for_id($pdo, $categoryId);
        if ($catSlug === '') {
            $catSlug = 'non-classe';
        }
        $up = $pdo->prepare(
            "UPDATE articles
             SET title = :title, slug = :slug, excerpt = :excerpt, content_html = :content_html,
                 cover_image = :cover_image, published = :published, category_slug = :category_slug,
                 category_id = :category_id, sort_order = :sort_order, updated_at = :updated_at
             WHERE id = :id"
        );
        $up->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => $excerpt,
            ':content_html' => $contentHtml,
            ':cover_image' => ($coverImage !== '' ? $coverImage : null),
            ':published' => $published,
            ':category_slug' => $catSlug,
            ':category_id' => $categoryId,
            ':sort_order' => $sortOrder,
            ':updated_at' => date('c'),
            ':id' => $id,
        ]);
        flash_set('success', 'Article mis à jour.');
        redirect('/admin/article-edit.php?id=' . $id);
    }
}

$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$article = $stmt->fetch();

$cats = categories_all_ordered($pdo);
$categoryId = (int)($article['category_id'] ?? 0);
$validCatIds = array_map('intval', array_column($cats, 'id'));
if (!in_array($categoryId, $validCatIds, true) && $validCatIds !== []) {
    $categoryId = (int)$validCatIds[0];
}
$sortOrderVal = (int)($article['sort_order'] ?? 0);
?>
<div class="card">
  <h1>Modifier l'article</h1>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Catégorie</label>
    <select name="category_id">
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $categoryId) ? 'selected' : '' ?>><?= e((string)$c['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Ordre dans la catégorie</label>
    <input type="number" name="sort_order" value="<?= $sortOrderVal ?>">
    <label>Titre</label>
    <input type="text" name="title" required value="<?= e((string)$article['title']) ?>">
    <label>Extrait (optionnel)</label>
    <textarea name="excerpt" rows="3"><?= e((string)$article['excerpt']) ?></textarea>
    <label>Image de couverture (optionnel)</label>
    <?php if (!empty($article['cover_image'])): ?>
      <p><img src="/uploads/<?= e((string)$article['cover_image']) ?>" alt="" style="max-width:250px"></p>
    <?php endif; ?>
    <input type="file" name="cover_image" accept="image/*">
    <label>Contenu</label>
    <textarea id="content_html" name="content_html" rows="12"><?= e((string)$article['content_html']) ?></textarea>
    <label><input type="checkbox" name="published" value="1" <?= ((int)$article['published'] === 1) ? 'checked' : '' ?>> Publié</label>
    <br><br>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn secondary" href="/admin/index.php">Retour</a>
  </form>
</div>

<div class="card">
  <h2>Suppression</h2>
  <form method="post" onsubmit="return confirm('Supprimer définitivement cet article ?');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="delete" value="1">
    <button class="btn danger" type="submit">Supprimer l'article</button>
  </form>
</div>

<script src="https://cdn.tiny.cloud/1/1dug8qkolte5l18uwjdmjarp7qaucozlzjg9xyn6dvcfocms/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
  selector: '#content_html',
  height: 500,
  plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount help quickbars autoresize',
  toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | removeformat code fullscreen',
  menubar: 'file edit view insert format tools table help',
  images_upload_url: '/admin/upload-image.php',
  automatic_uploads: true,
  images_reuse_filename: false,
  image_title: true,
  image_caption: true,
  image_advtab: true,
  object_resizing: 'img',
  quickbars_selection_toolbar: 'bold italic underline | fontfamily fontsize | quicklink h2 h3 blockquote',
  content_style: "body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 16px; line-height: 1.6; } img { max-width: 100%; height: auto; } img.align-left { float:left; margin:0 16px 10px 0; } img.align-right { float:right; margin:0 0 10px 16px; } img.centered { display:block; margin: 10px auto; }",
  style_formats: [
    { title: 'Titre Premium', block: 'h2', styles: { color: '#102947' } },
    { title: 'Sous titre', block: 'h3', styles: { color: '#184e77' } },
    { title: 'Image Gauche', selector: 'img', classes: 'align-left' },
    { title: 'Image Droite', selector: 'img', classes: 'align-right' },
    { title: 'Image Centree', selector: 'img', classes: 'centered' }
  ]
});
</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>

