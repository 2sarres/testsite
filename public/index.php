<?php
declare(strict_types=1);

/**
 * Accueil magazine : catégories et articles viennent de la base (table categories, articles.category_id).
 * Ordre : categories.sort_order, puis articles.sort_order.
 */

$hideSiteHeader = true;
$pageTitle = 'Sky Atlas — Guides voyages & jets d’affaires';
require_once __DIR__ . '/_header.php';

db_install($pdo);

$categories = categories_all_ordered($pdo);
$firstCat = $categories[0] ?? null;
$firstSlug = $firstCat ? (string)$firstCat['slug'] : '';

$stmtFeat = $pdo->query(
    'SELECT a.id, a.title, a.slug, a.excerpt, a.cover_image, a.content_html, a.created_at, a.category_id,
            u.email AS author_email, c.label AS category_label, c.slug AS category_slug
     FROM articles a
     JOIN users u ON u.id = a.author_id
     LEFT JOIN categories c ON c.id = a.category_id
     WHERE a.published = 1
     ORDER BY a.created_at DESC
     LIMIT 1'
);
$featured = $stmtFeat->fetch() ?: null;
$featuredId = $featured ? (int)$featured['id'] : 0;

$stmtByCat = $pdo->prepare(
    'SELECT a.id, a.title, a.slug, a.excerpt, a.cover_image, a.content_html, a.created_at
     FROM articles a
     WHERE a.published = 1
       AND a.category_id = :cid
       AND (:fid = 0 OR a.id != :fid)
     ORDER BY a.sort_order ASC, a.created_at DESC
     LIMIT 6'
);

$heroJetSrc = '/assets/images/hero-jet.png';

$placeholderPool = [
    [
        'img' => 'https://images.unsplash.com/photo-1540965204758-8632e23e6c43?w=720&q=80&auto=format&fit=crop',
        'title' => 'Repérage premium à venir',
        'excerpt' => 'Votre prochain avis de destination : note provisoire, accès FBO et expérience au sol.',
    ],
    [
        'img' => 'https://images.unsplash.com/photo-1529074963764-98f45c47344b?w=720&q=80&auto=format&fit=crop',
        'title' => 'Cabine & confort — évaluation',
        'excerpt' => 'Article à rédiger depuis l’admin : sièges, acoustique, restauration à bord.',
    ],
    [
        'img' => 'https://images.unsplash.com/photo-1506010751457-369bb0678b21?w=720&q=80&auto=format&fit=crop',
        'title' => 'Approche aérienne remarquable',
        'excerpt' => 'Placeholder visuel : remplacez par un article jugé une route ou une vue d’approche.',
    ],
];
?>
<section class="ftg-hero" aria-label="En-tête">
  <div class="ftg-hero__bg" aria-hidden="true"></div>
  <div class="ftg-inner ftg-hero__grid">
    <div class="ftg-hero__copy">
      <p class="ftg-kicker">Guides voyages · aviation d’affaires</p>
      <h1 class="ftg-hero__title">Sky Atlas</h1>
      <p class="ftg-hero__lead">
        Avis indépendants sur les destinations, les vols long-courriers et l’excellence des terminaux privés — avec le même sens du détail qu’un carnet de voyage de luxe.
      </p>
      <?php if ($featured): ?>
        <?php
        $catLabel = (string)($featured['category_label'] ?? '');
        if ($catLabel === '') {
            $catLabel = $firstCat ? (string)$firstCat['label'] : 'Guides';
        }
        $featExcerpt = card_excerpt_preview((string)$featured['excerpt'], (string)$featured['content_html']);
        ?>
        <div class="ftg-hero__pick">
          <p class="ftg-hero__pick-label">À la une</p>
          <p class="ftg-hero__pick-cat"><?= e($catLabel) ?></p>
          <h2 class="ftg-hero__pick-title">
            <a href="/article.php?slug=<?= urlencode((string)$featured['slug']) ?>"><?= e((string)$featured['title']) ?></a>
          </h2>
          <p class="ftg-hero__pick-excerpt"><?= e($featExcerpt) ?></p>
          <a class="ftg-btn ftg-btn--ghost" href="/article.php?slug=<?= urlencode((string)$featured['slug']) ?>">Lire le guide</a>
        </div>
      <?php else: ?>
        <div class="ftg-hero__pick">
          <p class="ftg-hero__pick-label">À la une</p>
          <p class="ftg-hero__pick-cat"><?= e($firstCat ? (string)$firstCat['label'] : 'Guides') ?></p>
          <h2 class="ftg-hero__pick-title">Premier article en préparation</h2>
          <p class="ftg-hero__pick-excerpt">Connectez-vous à l’administration pour publier votre premier avis de destination.</p>
          <a class="ftg-btn ftg-btn--ghost" href="/login.php">Accès rédaction</a>
        </div>
      <?php endif; ?>
    </div>
    <div class="ftg-hero__visual">
      <figure class="ftg-hero__figure">
        <img class="ftg-hero__jet" src="<?= e($heroJetSrc) ?>" alt="" width="900" height="520">
      </figure>
      <div class="ftg-hero__ribbons" aria-hidden="true">
        <img class="ftg-ribbon" src="https://images.unsplash.com/photo-1436491865332-7a61a109c05b?w=280&q=80&auto=format&fit=crop" alt="" loading="lazy" width="280" height="180"<?= img_external_attrs('https://images.unsplash.com/photo-1436491865332-7a61a109c05b?w=280&q=80&auto=format&fit=crop') ?>>
        <img class="ftg-ribbon" src="https://images.unsplash.com/photo-1540965900371-11be79a4bc30?w=280&q=80&auto=format&fit=crop" alt="" loading="lazy" width="280" height="180"<?= img_external_attrs('https://images.unsplash.com/photo-1540965900371-11be79a4bc30?w=280&q=80&auto=format&fit=crop') ?>>
      </div>
    </div>
  </div>
</section>

<div class="ftg-deco-band" aria-hidden="true">
  <div class="ftg-inner ftg-deco-band__inner">
    <img src="https://images.unsplash.com/photo-1474302771287-df387ea836e2?w=420&q=80&auto=format&fit=crop" alt="" loading="lazy" width="420" height="260"<?= img_external_attrs('https://images.unsplash.com/photo-1474302771287-df387ea836e2?w=420&q=80&auto=format&fit=crop') ?>>
    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=420&q=80&auto=format&fit=crop" alt="" loading="lazy" width="420" height="260"<?= img_external_attrs('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=420&q=80&auto=format&fit=crop') ?>>
    <img src="https://images.unsplash.com/photo-1464037866556-7112c0a20452?w=420&q=80&auto=format&fit=crop" alt="" loading="lazy" width="420" height="260"<?= img_external_attrs('https://images.unsplash.com/photo-1464037866556-7112c0a20452?w=420&q=80&auto=format&fit=crop') ?>>
  </div>
</div>

<?php foreach ($categories as $meta): ?>
  <?php
  $cid = (int)$meta['id'];
  $slug = (string)$meta['slug'];
  $stmtByCat->execute([':cid' => $cid, ':fid' => $featuredId]);
  $catRows = $stmtByCat->fetchAll();
  ?>
  <section class="ftg-section" id="cat-<?= e($slug) ?>">
    <div class="ftg-inner">
      <header class="ftg-section__head">
        <div>
          <h2 class="ftg-section__title">
            <a class="ftg-section__title-link" href="/category.php?slug=<?= urlencode($slug) ?>"><?= e((string)$meta['label']) ?></a>
          </h2>
          <p class="ftg-section__dek"><?= e((string)$meta['dek']) ?></p>
          <p class="ftg-section__more"><a class="ftg-section__more-link" href="/category.php?slug=<?= urlencode($slug) ?>">Voir tous les articles de cette catégorie →</a></p>
        </div>
        <div class="ftg-section__art">
          <?php $accent = (string)$meta['accent_img']; ?>
          <img src="<?= e($accent) ?>" alt="" loading="lazy" width="360" height="240"<?= img_external_attrs($accent) ?>>
        </div>
      </header>

      <div class="ftg-grid">
        <?php if ($catRows): ?>
          <?php
          foreach ($catRows as $row):
              $cardHref = '/article.php?slug=' . urlencode((string)$row['slug']);
              $cardImg = !empty($row['cover_image'])
                  ? '/uploads/' . (string)$row['cover_image']
                  : $accent;
              $cardCat = (string)$meta['label'];
              $cardTitle = (string)$row['title'];
              $cardExcerpt = card_excerpt_preview((string)$row['excerpt'], (string)$row['content_html']);
              if ($cardExcerpt === '') {
                  $cardExcerpt = 'Avis de destination — ouvrez pour la note complète.';
              }
              require __DIR__ . '/partials/home-article-card.php';
          endforeach;
          ?>
        <?php else: ?>
          <?php
          foreach ($placeholderPool as $ph):
              $cardHref = '';
              $cardImg = (string)$ph['img'];
              $cardCat = (string)$meta['label'];
              $cardTitle = (string)$ph['title'] . ' · ' . (string)$meta['label'];
              $cardExcerpt = (string)$ph['excerpt'];
              require __DIR__ . '/partials/home-article-card.php';
          endforeach;
          ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endforeach; ?>

<footer class="home-discrete-footer">
  <div class="ftg-inner home-discrete-footer__inner">
    <span class="home-discrete-footer__brand">Sky Atlas</span>
    <?php if ($user): ?>
      <a href="/admin/index.php">Rédaction</a>
      <span class="home-discrete-footer__sep">·</span>
      <a href="/logout.php">Déconnexion</a>
    <?php else: ?>
      <a href="/login.php">Accès rédacteurs</a>
    <?php endif; ?>
  </div>
</footer>

<?php require __DIR__ . '/_footer.php'; ?>
