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

// Re-requête pour afficher les infos fraîches après l'update
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
$isPublished = ((int)$article['published'] === 1);
?>
<style>
/* CSS pour le bouton slider de visibilité */
.visibility-container { background: #f9f9f9; padding: 1.5rem; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 1.5rem; }
.visibility-label { font-weight: bold; display: block; margin-bottom: 15px; color: #333; }
.switch-wrapper { display: flex; align-items: center; gap: 15px; }
.switch { position: relative; display: inline-block; width: 60px; height: 34px; margin: 0; }
.switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
input:checked + .slider { background-color: #4CAF50; }
input:checked + .slider:before { transform: translateX(26px); }
.state-text { transition: 0.3s; font-size: 1rem; }
</style>

<div class="card">
  <h1>Modifier l'article</h1>
  <?php $flashes = flash_get_all(); foreach ($flashes as $f): ?>
    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <div class="visibility-container">
        <label class="visibility-label">👁️ Visibilité de l'article :</label>
        <div class="switch-wrapper">
            <span class="state-text state-prive" id="label-prive" style="<?= !$isPublished ? 'font-weight:bold; color:#333;' : 'font-weight:normal; color:#999;' ?>">Privé (Brouillon)</span>
            <label class="switch">
                <input type="checkbox" name="published" value="1" id="publish-toggle" <?= $isPublished ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span class="state-text state-public" id="label-public" style="<?= $isPublished ? 'font-weight:bold; color:#4CAF50;' : 'font-weight:normal; color:#999;' ?>">Publique (En ligne)</span>
        </div>
    </div>
    
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
    
    <br><br>
    <button class="btn" type="submit" style="background:#2196F3;">💾 Enregistrer les modifications</button>
    <a class="btn secondary" href="/admin/index.php">Retour au tableau de bord</a>
  </form>
</div>

<div class="card" style="border: 1px solid #f44336;">
  <h2 style="color: #f44336; margin-top:0;">Zone dangereuse</h2>
  <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cet article ? Cette action est irréversible.');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="delete" value="1">
    <button class="btn danger" type="submit" style="background: #f44336;">🗑️ Supprimer l'article</button>
  </form>
</div>

<script>
document.getElementById('publish-toggle').addEventListener('change', function() {
    const lblPrive = document.getElementById('label-prive');
    const lblPublic = document.getElementById('label-public');
    if(this.checked) {
        lblPrive.style.fontWeight = 'normal'; lblPrive.style.color = '#999';
        lblPublic.style.fontWeight = 'bold'; lblPublic.style.color = '#4CAF50';
    } else {
        lblPrive.style.fontWeight = 'bold'; lblPrive.style.color = '#333';
        lblPublic.style.fontWeight = 'normal'; lblPublic.style.color = '#999';
    }
});
</script>

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