<?php
declare(strict_types=1);
$isClickable = isset($cardHref) && $cardHref !== '';
global $user;
$isAdmin = ($user && ($user['role'] ?? '') === 'admin');
?>
<article class="ftg-card<?= $isClickable ? '' : ' ftg-card--muted' ?>" style="position:relative;">
  
  <?php if ($isAdmin && !empty($articleId)): ?>
    <a href="/admin/article-edit.php?id=<?= $articleId ?>" class="edit-pencil" title="Modifier l'article">✏️</a>
  <?php endif; ?>

  <a class="ftg-card__link" href="<?= e($cardHref) ?>" style="display:block; text-decoration:none; color:inherit;">
    <div class="ftg-card__media">
      <img src="<?= e($cardImg) ?>" alt="" loading="lazy" width="640" height="420"<?= img_external_attrs($cardImg) ?>>
    </div>
    <div class="ftg-card__body">
      <p class="ftg-card__cat"><?= e($cardCat) ?></p>
      <h3 class="ftg-card__title"><?= e($cardTitle) ?></h3>
      <p class="ftg-card__excerpt"><?= e($cardExcerpt) ?></p>
    </div>
  </a>
</article>