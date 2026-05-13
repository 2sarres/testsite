<?php
declare(strict_types=1);
$pageTitle = 'Images de l\'accueil';
require_once dirname(__DIR__) . '/_header.php';
require_admin();
db_install($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    set_setting($pdo, 'home_hero_jet', trim((string)($_POST['home_hero_jet'] ?? '')));
    set_setting($pdo, 'home_banner_1', trim((string)($_POST['home_banner_1'] ?? '')));
    
    flash_set('success', 'Les images de la page d\'accueil ont été mises à jour.');
    redirect('/admin/settings-home.php');
}

$heroJetSrc = get_setting($pdo, 'home_hero_jet', '/assets/images/hero-jet.png');
$banner1    = get_setting($pdo, 'home_banner_1', 'https://picsum.photos/1600/600?random=90');
?>

<div class="card">
  <h1>Modifier les images de l'accueil</h1>
  <div class="top-actions" style="margin-bottom: 20px;">
    <a class="btn secondary" href="/">← Retour à l'accueil</a>
  </div>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <h2>En-tête (Avion)</h2>
    
    <label>Image de l'avion (Fond transparent PNG conseillé)</label>
    <div style="display:flex; gap:15px; align-items:center;">
        <img src="<?= e($heroJetSrc) ?>" width="100" style="border-radius:8px; border:1px solid #ccc; background:#eee;">
        <input type="text" name="home_hero_jet" value="<?= e($heroJetSrc) ?>" required>
    </div>

    <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">

    <h2>Bannière intermédiaire</h2>
    
    <label>Bannière (Ex: Explore Destinations)</label>
    <div style="display:flex; gap:15px; align-items:center;">
        <img src="<?= e($banner1) ?>" width="150" style="border-radius:8px; border:1px solid #ccc;">
        <input type="text" name="home_banner_1" value="<?= e($banner1) ?>" required>
    </div>

    <br><br>
    <button type="submit" class="btn">Enregistrer les images</button>
  </form>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>