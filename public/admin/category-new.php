<?php
declare(strict_types=1);
$pageTitle = 'Nouvelle catégorie';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);

$errors = [];
$label = '';
$slugManual = '';
$dek = '';
$accentImg = '';
$sortOrder = 100;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    $label = trim((string)($_POST['label'] ?? ''));
    $slugManual = trim((string)($_POST['slug'] ?? ''));
    $dek = trim((string)($_POST['dek'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 100);
    $imgType = $_POST['img_type'] ?? 'url';

    if ($label === '') $errors[] = 'Le libellé est obligatoire.';
    $slugBase = $slugManual !== '' ? $slugManual : $label;
    if (normalize_slug($slugBase) === '') $errors[] = 'Impossible de générer un slug.';

    // Traitement de l'image (URL ou Base64 Croppée)
    if ($imgType === 'upload') {
        $croppedData = trim((string)($_POST['cropped_image'] ?? ''));
        if ($croppedData !== '') {
            $savedPath = process_base64_upload($croppedData);
            if ($savedPath) {
                $accentImg = $savedPath;
            } else {
                $errors[] = "Erreur lors de l'enregistrement de l'image recadrée.";
            }
        } else {
            $errors[] = "Veuillez uploader et recadrer une image.";
        }
    } else {
        $accentImg = trim((string)($_POST['accent_img'] ?? ''));
        if ($accentImg === '') $errors[] = "L'URL de l'image est obligatoire.";
    }

    if (!$errors) {
        $slug = make_unique_category_slug($pdo, $slugBase);
        $stmt = $pdo->prepare(
            'INSERT INTO categories (slug, label, dek, accent_img, sort_order, created_at)
             VALUES (:slug, :label, :dek, :accent_img, :sort_order, :created_at)'
        );
        $stmt->execute([
            ':slug' => $slug, ':label' => $label, ':dek' => $dek,
            ':accent_img' => $accentImg, ':sort_order' => $sortOrder, ':created_at' => date('c'),
        ]);
        flash_set('success', 'Catégorie créée.');
        redirect('/admin/categories.php');
    }
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<div class="card">
  <h1>Nouvelle catégorie</h1>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" id="cat-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <label>Libellé (titre affiché)</label>
    <input type="text" name="label" required value="<?= e($label) ?>">
    
    <label>Slug URL (optionnel)</label>
    <input type="text" name="slug" value="<?= e($slugManual) ?>" placeholder="ex: destinations-europeennes">
    
    <label>Chapô / description courte</label>
    <textarea name="dek" rows="3"><?= e($dek) ?></textarea>
    
    <hr style="margin:25px 0; border:0; border-top:1px solid #ddd;">
    
    <label>Image décorative de la catégorie</label>
    <div style="margin-bottom:15px; display:flex; gap:20px; background:#f9f9f9; padding:10px; border-radius:8px;">
        <label style="font-weight:normal;"><input type="radio" name="img_type" value="url" checked onchange="toggleImg()"> Saisir une URL existante</label>
        <label style="font-weight:normal;"><input type="radio" name="img_type" value="upload" onchange="toggleImg()"> Uploader et recadrer une image</label>
    </div>

    <div id="div-url">
        <input type="url" name="accent_img" value="<?= e($accentImg) ?>" placeholder="https://picsum.photos/...">
    </div>

    <div id="div-upload" style="display:none; background:#faf5ec; padding:15px; border:1px solid #d7b88a; border-radius:8px;">
        <input type="file" id="upload-input" accept="image/png, image/jpeg, image/webp">
        <input type="hidden" name="cropped_image" id="cropped_image">
        
        <div id="preview-container" style="display:none; margin-top:15px;">
            <p style="margin:0 0 5px; font-size:0.9rem; color:#666;">Aperçu de l'image finale :</p>
            <img id="preview-img" style="max-width:100%; max-height:250px; border-radius:8px; border:1px solid #ccc;">
        </div>
    </div>

    <hr style="margin:25px 0; border:0; border-top:1px solid #ddd;">

    <label>Ordre (nombre, plus petit = plus haut)</label>
    <input type="number" name="sort_order" value="<?= (int)$sortOrder ?>">
    
    <p style="margin-top:20px;">
        <button class="btn" type="submit">Créer la catégorie</button>
        <a class="btn secondary" href="/admin/categories.php">Annuler</a>
    </p>
  </form>
</div>

<div id="cropper-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:12px; width:90%; max-width:800px; display:flex; flex-direction:column;">
        <h2 style="margin-top:0;">Recadrer l'image</h2>
        <p class="meta">Ajustez l'image pour qu'elle corresponde parfaitement au design (Ratio 16:9).</p>
        <div style="width:100%; height:400px; background:#eee; margin-bottom:20px; display:flex; align-items:center; justify-content:center;">
            <img id="image-to-crop" style="max-width:100%; max-height:100%; display:block;">
        </div>
        <div style="text-align:right;">
            <button type="button" class="btn secondary" onclick="closeCropper()">Annuler</button>
            <button type="button" class="btn" onclick="applyCrop()">Appliquer le recadrage</button>
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
                aspectRatio: 16 / 9, // Format paysage Forbes
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