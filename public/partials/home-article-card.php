<?php
declare(strict_types=1);
/**
 * Carte article (accueil type « Forbes Travel Guide »).
 * Variables : $cardHref (string, vide = carte non cliquable), $cardImg, $cardCat, $cardTitle, $cardExcerpt.
 */
$isClickable = isset($cardHref) && $cardHref !== '';
?>
<article class="ftg-card<?= $isClickable ? '' : ' ftg-card--muted' ?>">
  <?php if ($isClickable): ?>
    <a class="ftg-card__link" href="<?= e($cardHref) ?>">
  <?php else: ?>
    <div class="ftg-card__shell">
  <?php endif; ?>
    <div class="ftg-card__media">
      <img src="<?= e($cardImg) ?>" alt="" loading="lazy" width="640" height="420"<?= img_external_attrs($cardImg) ?>>
    </div>
    <div class="ftg-card__body">
      <p class="ftg-card__cat"><?= e($cardCat) ?></p>
      <h3 class="ftg-card__title"><?= e($cardTitle) ?></h3>
      <p class="ftg-card__excerpt"><?= e($cardExcerpt) ?></p>
    </div>
  <?php if ($isClickable): ?>
    </a>
  <?php else: ?>
    </div>
  <?php endif; ?>
</article>
