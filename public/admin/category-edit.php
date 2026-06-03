<?php
declare(strict_types=1);
$pageTitle = 'Edit Category';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash_set('error', 'Invalid category.'); redirect('/admin/categories.php'); }

$cat = category_by_id($pdo, $id);
if (!$cat) { flash_set('error', 'Category not found.'); redirect('/admin/categories.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();

    if (isset($_POST['delete']) && (string)$_POST['delete'] === '1') {
        if (articles_count_in_category($pdo, $id) > 0) {
            flash_set('error', 'Unable to delete: articles still in this category.');
            redirect('/admin/category-edit.php?id=' . $id);
        }
        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
        flash_set('success', 'Category deleted.');
        redirect('/admin/categories.php');
    }

    $label = trim((string)($_POST['label'] ?? ''));
    $slugManual = trim((string)($_POST['slug'] ?? ''));
    $dek = trim((string)($_POST['dek'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $imgType = $_POST['img_type'] ?? 'url';
    $accentImg = (string)$cat['accent_img'];

    if ($label === '') $errors[] = 'Label is required.';
    $slugBase = $slugManual !== '' ? $slugManual : $label;
    if (normalize_slug($slugBase) === '') $errors[] = 'Invalid slug.';

    if ($imgType === 'upload') {
        $croppedData = trim((string)($_POST['cropped_image'] ?? ''));
        if ($croppedData !== '') {
            $savedPath = process_base64_upload($croppedData);
            if ($savedPath) $accentImg = $savedPath;
            else $errors[] = "Error saving image.";
        }
    } else {
        $postedUrl = trim((string)($_POST['accent_img'] ?? ''));
        if ($postedUrl !== '') $accentImg = $postedUrl;
        if ($accentImg === '') $errors[] = "Image URL is required.";
    }

    if (!$errors) {
        $newSlug = make_unique_category_slug($pdo, $slugBase, $id);
        $pdo->prepare('UPDATE categories SET slug = :slug, label = :label, dek = :dek, accent_img = :img, sort_order = :so WHERE id = :id')
            ->execute([':slug' => $newSlug, ':label' => $label, ':dek' => $dek, ':img' => $accentImg, ':so' => $sortOrder, ':id' => $id]);
        $pdo->prepare('UPDATE articles SET category_slug = :s WHERE category_id = :id')->execute([':s' => $newSlug, ':id' => $id]);
        flash_set('success', 'Category updated.');
        redirect('/admin/category-edit.php?id=' . $id);
    }
}

$cat = category_by_id($pdo, $id);
$currentImg = (string)$cat['accent_img'];
$isUpload = (strpos($currentImg, '/uploads/') === 0);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<div class="card">
  <h1>Edit Category</h1>
  <?php foreach ($errors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>
  
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Label</label>
    <input type="text" name="label" required value="<?= e((string)$cat['label']) ?>">
    <label>Slug URL</label>
    <input type="text" name="slug" required value="<?= e((string)$cat['slug']) ?>">
    <label>Description</label>
    <textarea name="dek" rows="3"><?= e((string)$cat['dek']) ?></textarea>
    
    <hr style="margin:25px 0; border:0; border-top:1px solid #ddd;">
    
    <label>Decorative Image</label>
    <div style="margin-bottom:15px; display:flex; gap:20px; background:#f9f9f9; padding:10px; border-radius:8px;">
        <label style="font-weight:normal;"><input type="radio" name="img_type" value="url" <?= !$isUpload ? 'checked' : '' ?> onchange="toggleImg()"> Use a URL</label>
        <label style="font-weight:normal;"><input type="radio" name="img_type" value="upload" <?= $isUpload ? 'checked' : '' ?> onchange="toggleImg()"> Upload / Replace (Crop)</label>
    </div>

    <div id="div-url" style="<?= $isUpload ? 'display:none;' : '' ?>">
        <input type="url" name="accent_img" value="<?= !$isUpload ? e($currentImg) : '' ?>">
        <?php if(!$isUpload): ?>
            <img src="<?= e($currentImg) ?>" style="max-height:100px; margin-top:10px; border-radius:6px;">
        <?php endif; ?>
    </div>

    <div id="div-upload" style="<?= !$isUpload ? 'display:none;' : '' ?> background:#faf5ec; padding:15px; border:1px solid #d7b88a; border-radius:8px;">
        <input type="file" id="upload-input" accept="image/png, image/jpeg, image/webp">
        <input type="hidden" name="cropped_image" id="cropped_image">
        
        <div id="preview-container" style="<?= !$isUpload ? 'display:none;' : 'margin-top:15px;' ?>">
            <p style="margin:0 0 5px; font-size:0.9rem; color:#666;">Current Image / Preview:</p>
            <img id="preview-img" src="<?= $isUpload ? e($currentImg) : '' ?>" style="max-width:100%; max-height:250px; border-radius:8px; border:1px solid #ccc;">
        </div>
    </div>

    <hr style="margin:25px 0; border:0; border-top:1px solid #ddd;">

    <label>Order</label>
    <input type="number" name="sort_order" value="<?= (int)$cat['sort_order'] ?>">
    
    <p style="margin-top:20px;">
      <button class="btn" type="submit">Save Changes</button>
      <a class="btn secondary" href="/admin/categories.php">Back</a>
    </p>
  </form>
</div>

<div class="card" style="margin-top:30px;">
  <h2>Danger Zone</h2>
  <form method="post" onsubmit="return confirm('Delete this category permanently?');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="delete" value="1">
    <button class="btn danger" type="submit">Delete Category</button>
  </form>
</div>

<div id="cropper-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:12px; width:90%; max-width:800px; display:flex; flex-direction:column;">
        <h2 style="margin-top:0;">Crop Image</h2>
        <div style="width:100%; height:400px; background:#eee; margin-bottom:20px; display:flex; align-items:center; justify-content:center;">
            <img id="image-to-crop" style="max-width:100%; max-height:100%; display:block;">
        </div>
        <div style="text-align:right;">
            <button type="button" class="btn secondary" onclick="closeCropper()">Cancel</button>
            <button type="button" class="btn" onclick="applyCrop()">Apply Crop</button>
        </div>
    </div>
</div>

<script>
let cropper;
const modal = document.getElementById('cropper-modal');
const imageToCrop = document.getElementById('image-to-crop');
const previewImg = document.getElementById('preview-img');
const hiddenInput = document.getElementById('cropped_image');

function toggleImg() {
    const isUpload = document.querySelector('input[name="img_type"][value="upload"]').checked;
    document.getElementById('div-url').style.display = isUpload ? 'none' : 'block';
    document.getElementById('div-upload').style.display = isUpload ? 'block' : 'none';
}

document.getElementById('upload-input').addEventListener('change', function(e) {
    if(e.target.files && e.target.files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(event) {
            imageToCrop.src = event.target.result;
            modal.style.display = 'flex';
            if(cropper) cropper.destroy();
            cropper = new Cropper(imageToCrop, {
                aspectRatio: 16 / 9,
                viewMode: 1,
                autoCropArea: 1,
            });
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

function closeCropper() {
    modal.style.display = 'none';
    document.getElementById('upload-input').value = '';
}

function applyCrop() {
    const canvas = cropper.getCroppedCanvas({ width: 800, height: 450 });
    const base64 = canvas.toDataURL('image/jpeg', 0.85);
    
    hiddenInput.value = base64;
    previewImg.src = base64;
    document.getElementById('preview-container').style.display = 'block';
    
    modal.style.display = 'none';
}
</script>

<?php require dirname(__DIR__) . '/_footer.php'; ?>
