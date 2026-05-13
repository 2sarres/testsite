<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
$user = current_user();
$flashes = flash_get_all();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? 'Mon Site Articles') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body<?= !empty($hideSiteHeader) ? ' class="body--home"' : '' ?>>
<?php if (empty($hideSiteHeader)): ?>
<header class="site-header">
  <div class="container">
    <a href="/"><strong>Mon Site Articles</strong></a>
    <?php if ($user): ?>
      <a href="/admin/index.php">Admin</a>
      <a href="/logout.php">Déconnexion</a>
    <?php else: ?>
      <a href="/login.php">Connexion</a>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>
<main class="<?= !empty($hideSiteHeader) ? 'home-main' : 'container' ?>">
  <?php foreach ($flashes as $f): ?>
    <div class="flash <?= e((string)($f['type'] ?? 'success')) ?>"><?= e((string)($f['message'] ?? '')) ?></div>
  <?php endforeach; ?>

