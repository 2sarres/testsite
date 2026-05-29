<?php
declare(strict_types=1);
$pageTitle = 'Images et textes de l\'accueil';
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

    // Gestion du toggle pour l'activation/désactivation de la bannière
    $bannerActive = isset($_POST['home_banner_1_active']) ? '1' : '0';
    set_setting($pdo, 'home_banner_1_active', $bannerActive);
    
    // NOUVEAU : Sauvegarde des textes administrables du Hero
    set_setting($pdo, 'home_hero_kicker', trim((string)($_POST['home_hero_kicker'] ?? '')));
    set_setting($pdo, 'site_name', trim((string)($_POST['site_name'] ?? '')));
    set_setting($pdo, 'home_hero_lead', trim((string)($_POST['home_hero_lead'] ?? '')));

    if ($uploadMessage) {
        flash_set('success', trim($uploadMessage));
    } elseif ($uploadError) {
        flash_set('error', trim($uploadError));
    }
    
    redirect('/admin/settings-home.php');
}

$heroJetSrc    = get_setting($pdo, 'home_hero_jet', '/assets/images/hero-jet.png');
$banner1       = get_setting($pdo, 'home_banner_1', 'https://picsum.photos/1600/600?random=90');
$banner1Active = get_setting($pdo, 'home_banner_1_active', '1');
$isBannerActive = ($banner1Active === '1');

// NOUVEAU : Récupération des textes avec leurs valeurs par défaut d'origine
$heroKicker    = get_setting($pdo, 'home_hero_kicker', 'Guides voyages · aviation d’affaires');
$siteName      = get_setting($pdo, 'site_name', 'Sky Atlas');
$heroLead      = get_setting($pdo, 'home_hero_lead', 'Avis indépendants sur les destinations, les vols long-courriers et l’excellence des terminaux privés — avec le même sens du détail qu’un carnet de voyage de luxe.');
?>

<style>
/* CSS ULTRA-PRIORITAIRE POUR LE SLIDER - VERSION ÉPURÉE */
.visibility-container { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 1rem 0; margin-top: 1rem; margin-bottom: 1.5rem; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
.visibility-label { font-weight: bold; margin: 0; color: #333; font-size: 1.05rem; }
.switch-wrapper { display: flex; align-items: center; gap: 15px; }

.switch { position: relative !important; display: inline-block !important; width: 50px !important; height: 28px !important; margin: 0 !important; padding: 0 !important; }
.switch input[type="checkbox"] { opacity: 0 !important; width: 0 !important; height: 0 !important; position: absolute !important; margin: 0 !important; pointer-events: none !important; }

.slider { position: absolute !important; cursor: pointer !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; background-color: #ccc !important; transition: .4s !important; border-radius: 34px !important; display: block !important; border: none !important; margin: 0 !important; padding: 0 !important; }
.slider:before { position: absolute !important; content: "" !important; height: 20px !important; width: 20px !important; left: 4px !important; bottom: 4px !important; background-color: white !important; transition: .4s !important; border-radius: 50% !important; box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important; margin: 0 !important; }

.switch input[type="checkbox"]:checked + .slider { background-color: #4CAF50 !important; }
.switch input[type="checkbox"]:checked + .slider:before { transform: translateX(22px) !important; }

.state-text { transition: 0.3s; font-size: 0.95rem; }

/* Styles additionnels pour les champs de texte */
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
.form-group input[type="text"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; font-family: inherit; box-sizing: border-box; }
</style>

<div class="card">
  <h1>Modifier l'accueil</h1>
  <div class="top-actions" style="margin-bottom: 20px;">
    <a class="btn secondary" href="/">← Retour à l'accueil</a>
  </div>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <h2>Textes de l'en-tête (Hero)</h2>
    
    <div class="form-group">
        <label for="home_hero_kicker">Surtitre (Kicker)</label>
        <input type="text" id="home_hero_kicker" name="home_hero_kicker" value="<?= e($heroKicker) ?>" required>
    </div>

    <div class="form-group">
        <label for="site_name">Nom du site (Titre principal / Brand)</label>
        <input type="text" id="site_name" name="site_name" value="<?= e($siteName) ?>" required>
        <small style="color:#777;">Remplacera "Sky Atlas" partout sur la page d'accueil.</small>
    </div>

    <div class="form-group">
        <label for="home_hero_lead">Texte de description (Lead)</label>
        <textarea id="home_hero_lead" name="home_hero_lead" rows="4" required><?= e($heroLead) ?></textarea>
    </div>

    <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">

    <h2>Visuel de l'en-tête (Avion)</h2>
    
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
    
    <div class="visibility-container">
        <span class="visibility-label">Affichage de la bannière :</span>
        <div class="switch-wrapper">
            <span class="state-text" id="label-banner-off" style="<?= !$isBannerActive ? 'font-weight:bold; color:#333;' : 'font-weight:normal; color:#999;' ?>">Masquée</span>
            <label class="switch">
                <input type="checkbox" name="home_banner_1_active" value="1" id="banner-toggle" <?= $isBannerActive ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span class="state-text" id="label-banner-on" style="<?= $isBannerActive ? 'font-weight:bold; color:#4CAF50;' : 'font-weight:normal; color:#999;' ?>">Affichée</span>
        </div>
    </div>

    <label style="margin-top: 15px; display: block;">Image de la bannière (Ex: Explore Destinations)</label>
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

    <br>
    <button type="submit" class="btn">Enregistrer les paramètres</button>
  </form>
</div>

<script>
document.getElementById('banner-toggle').addEventListener('change', function() {
    const lblOff = document.getElementById('label-banner-off');
    const lblOn = document.getElementById('label-banner-on');
    if(this.checked) {
        lblOff.style.fontWeight = 'normal'; lblOff.style.color = '#999';
        lblOn.style.fontWeight = 'bold'; lblOn.style.color = '#4CAF50';
    } else {
        lblOff.style.fontWeight = 'bold'; lblOff.style.color = '#333';
        lblOn.style.fontWeight = 'normal'; lblOn.style.color = '#999';
    }
});
</script>

<?php require dirname(__DIR__) . '/_footer.php'; ?>