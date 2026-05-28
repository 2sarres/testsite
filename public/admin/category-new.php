<?php
declare(strict_types=1);
$pageTitle = 'Nouvel article';
require_once dirname(__DIR__) . '/_header.php';
$user = require_admin();
db_install($pdo);

$cats = categories_all_ordered($pdo);
$defaultCatId = (int)($cats[0]['id'] ?? 0);

$errors = [];
$title = '';
$excerpt = '';
$contentHtml = '';
$published = 1;
$categoryId = $defaultCatId;
$sortOrder = $defaultCatId > 0 ? next_article_sort_order($pdo, $defaultCatId) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    $title = trim((string)($_POST['title'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $contentHtml = (string)($_POST['content_html'] ?? '');
    $published = isset($_POST['published']) ? 1 : 0;
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    $validIds = array_map('intval', array_column($cats, 'id'));
    if (!in_array($categoryId, $validIds, true)) {
        $categoryId = $defaultCatId;
    }
    if ($sortOrder <= 0) {
        $sortOrder = next_article_sort_order($pdo, $categoryId);
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

    $coverImage = null;
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
        $slug = make_unique_slug($pdo, $title);
        $catSlug = category_slug_for_id($pdo, $categoryId);
        if ($catSlug === '') {
            $catSlug = 'non-classe';
        }
        $stmt = $pdo->prepare(
            "INSERT INTO articles(title, slug, excerpt, content_html, cover_image, author_id, published, category_slug, category_id, sort_order, created_at, updated_at)
             VALUES(:title, :slug, :excerpt, :content_html, :cover_image, :author_id, :published, :category_slug, :category_id, :sort_order, :created_at, :updated_at)"
        );
        $now = date('c');
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => $excerpt,
            ':content_html' => $contentHtml,
            ':cover_image' => $coverImage,
            ':author_id' => (int)$user['id'],
            ':published' => $published,
            ':category_slug' => $catSlug,
            ':category_id' => $categoryId,
            ':sort_order' => $sortOrder,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        flash_set('success', 'Article créé.');
        redirect('/admin/index.php');
    }
}
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
.state-prive { color: #666; }
.state-public { color: #666; }
/* Logique de couleur en fonction de l'état (géré par CSS pur grace à has() ou fallback avec JS simplifié si besoin) */
input:not(:checked) ~ .slider-texts .state-prive { font-weight: bold; color: #333; }
</style>

<div class="card">
  <h1>Nouvel article</h1>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <div class="visibility-container">
        <label class="visibility-label">👁️ Visibilité de l'article :</label>
        <div class="switch-wrapper">
            <span class="state-text state-prive" id="label-prive" style="<?= !$published ? 'font-weight:bold; color:#333;' : 'font-weight:normal; color:#999;' ?>">Privé (Brouillon)</span>
            <label class="switch">
                <input type="checkbox" name="published" value="1" id="publish-toggle" <?= $published ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span class="state-text state-public" id="label-public" style="<?= $published ? 'font-weight:bold; color:#4CAF50;' : 'font-weight:normal; color:#999;' ?>">Publique (En ligne)</span>
        </div>
    </div>
    
    <label>Catégorie</label>
    <select name="category_id">
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $categoryId) ? 'selected' : '' ?>><?= e((string)$c['label']) ?></option>
      <?php endforeach; ?>
    </select>
    
    <label>Ordre dans la catégorie (optionnel, laissez 0 pour placer en fin)</label>
    <input type="number" name="sort_order" value="<?= (int)$sortOrder ?>" placeholder="0 = auto">
    
    <label>Titre</label>
    <input type="text" name="title" required value="<?= e($title) ?>">
    
    <label>Extrait (optionnel)</label>
    <textarea name="excerpt" rows="3"><?= e($excerpt) ?></textarea>
    
    <label>Image de couverture (optionnel)</label>
    <input type="file" name="cover_image" accept="image/*">
    
    <label>Contenu</label>
    <textarea id="content_html" name="content_html" rows="12"><?= e($contentHtml) ?></textarea>
    
    <br><br>
    <button class="btn" type="submit" style="background:#4CAF50;">Créer l'article</button>
    <a class="btn secondary" href="/admin/index.php">Annuler</a>
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