<?php
declare(strict_types=1);
$pageTitle = 'Images de l\'accueil';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);

$uploadMessage = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    
    // Traitement de l'upload pour home_hero_jet
    if (!empty($_FILES['hero_jet_file']['name'])) {
        $uploadedName = validate_uploaded_image($_FILES['hero_jet_file']);
        if ($uploadedName) {
            $target = dirname(__DIR__) . '/uploads/' . $uploadedName;
            if (move_uploaded_file($_FILES['hero_jet_file']['tmp_name'], $target)) {
                set_setting($pdo, 'home_hero_jet', '/uploads/' . $uploadedName);
                $uploadMessage .= 'Image avion uploadée avec succès. ';
            } else {
                $uploadError .= 'Erreur lors du déplacement du fichier avion. ';
            }
        } else {
            $uploadError .= upload_error_message($_FILES['hero_jet_file']['error'] ?? 0) . ' ';
        }
    } elseif (!empty($_POST['home_hero_jet'])) {
        set_setting($pdo, 'home_hero_jet', trim((string)$_POST['home_hero_jet']));
        $uploadMessage .= 'Image avion (URL) enregistrée. ';
    }
    
    // Traitement de l'upload pour home_banner_1
    if (!empty($_FILES['banner_1_file']['name'])) {
        $uploadedName = validate_uploaded_image($_FILES['banner_1_file']);
        if ($uploadedName) {
            $target = dirname(__DIR__) . '/uploads/' . $uploadedName;
            if (move_uploaded_file($_FILES['banner_1_file']['tmp_name'], $target)) {
                set_setting($pdo, 'home_banner_1', '/uploads/' . $uploadedName);
                $uploadMessage .= 'Bannière uploadée avec succès. ';
            } else {
                $uploadError .= 'Erreur lors du déplacement du fichier bannière. ';
            }
        } else {
            $uploadError .= upload_error_message($_FILES['banner_1_file']['error'] ?? 0) . ' ';
        }
    } elseif (!empty($_POST['home_banner_1'])) {
        set_setting($pdo, 'home_banner_1', trim((string)$_POST['home_banner_1']));
        $uploadMessage .= 'Bannière (URL) enregistrée. ';
    }

    // Gestion de la case à cocher pour l'activation/désactivation de la bannière
    $bannerActive = isset($_POST['home_banner_1_active']) ? '1' : '0';
    set_setting($pdo, 'home_banner_1_active', $bannerActive);
    
    if ($uploadMessage) {
        flash_set('success', trim($uploadMessage));
    } elseif ($uploadError) {
        flash_set('error', trim($uploadError));
    }
    
    redirect('/admin/settings-home.php');
}

$heroJetSrc    = get_setting($pdo, 'home_hero_jet', '/assets/images/hero-jet.png');
$banner1       = get_setting($pdo, 'home_banner_1', 'https://picsum.photos/1600/600?random=90');
$banner1Active = get_setting($pdo, 'home_banner_1_active', '1'); // Actif par défaut ('1')
?>

<div class="card">
  <h1>Modifier les images de l'accueil</h1>
  <div class="top-actions" style="margin-bottom: 20px;">
    <a class="btn secondary" href="/">← Retour à l'accueil</a>
  </div>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <h2>En-tête (Avion)</h2>
    
    <label>Image de l'avion (Fond transparent PNG conseillé)</label>
    <div style="display:flex; gap:15px; align-items:flex-start; margin-bottom:20px;">
        <div style="flex-shrink:0;">
            <img src="<?= e($heroJetSrc) ?>" width="120" style="border-radius:8px; border:1px solid #ccc; background:#eee; object-fit:cover; height:80px;">
        </div>
        <div style="flex:1;">
            <div style="margin-bottom:10px;">
                <label style="display:block; font-size:0.9em; color:#666; margin-bottom:5px;">Télécharger une image</label>
                <input type="file" name="hero_jet_file" accept="image/*" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <small style="color:#999;">Formats acceptés: JPG, PNG, GIF, WEBP (max 10 Mo)</small>
            </div>
            <div style="margin-bottom:10px;">
                <label style="display:block; font-size:0.9em; color:#666; margin-bottom:5px;">OU entrer une URL</label>
                <input type="text" name="home_hero_jet" value="<?= e($heroJetSrc) ?>" placeholder="https://exemple.com/image.png" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
        </div>
    </div>

    <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">

    <h2>Bannière intermédiaire</h2>
    
    <div style="margin-bottom: 20px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" name="home_banner_1_active" value="1" <?= $banner1Active === '1' ? 'checked' : '' ?>>
            <strong>Afficher cette bannière sur la page d'accueil</strong>
        </label>
    </div>

    <label>Bannière (Ex: Explore Destinations)</label>
    <div style="display:flex; gap:15px; align-items:flex-start; margin-bottom:20px;">
        <div style="flex-shrink:0;">
            <img src="<?= e($banner1) ?>" width="180" style="border-radius:8px; border:1px solid #ccc; object-fit:cover; height:100px;">
        </div>
        <div style="flex:1;">
            <div style="margin-bottom:10px;">
                <label style="display:block; font-size:0.9em; color:#666; margin-bottom:5px;">Télécharger une image</label>
                <input type="file" name="banner_1_file" accept="image/*" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <small style="color:#999;">Formats acceptés: JPG, PNG, GIF, WEBP (max 10 Mo)</small>
            </div>
            <div style="margin-bottom:10px;">
                <label style="display:block; font-size:0.9em; color:#666; margin-bottom:5px;">OU entrer une URL</label>
                <input type="text" name="home_banner_1" value="<?= e($banner1) ?>" placeholder="https://exemple.com/banniere.jpg" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
        </div>
    </div>

    <br><br>
    <button type="submit" class="btn">Enregistrer les images</button>
  </form>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>