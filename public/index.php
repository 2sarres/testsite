<?php
declare(strict_types=1);
$hideSiteHeader = true;
$pageTitle = 'Accueil';
require_once __DIR__ . '/_header.php';
db_install($pdo);

$categories = categories_all_ordered($pdo);
$isAdmin = ($user && ($user['role'] ?? '') === 'admin');

// Variables administrables
$heroJetSrc    = get_setting($pdo, 'home_hero_jet', '/assets/images/hero-jet.png');
$banner1       = get_setting($pdo, 'home_banner_1', 'https://picsum.photos/1600/600?random=90');
$banner1Active = get_setting($pdo, 'home_banner_1_active', '1');

// Article mis en avant (sans affichage direct du bloc "À la une")
$featured = $pdo->query('SELECT a.*, c.label as category_label FROM articles a LEFT JOIN categories c ON c.id = a.category_id WHERE a.published = 1 ORDER BY a.created_at DESC LIMIT 1')->fetch();
?>

<section class="ftg-hero" aria-label="En-tête">
  <?php if ($isAdmin): ?>
    <div style="position:fixed; top:20px; right:20px; z-index:1000; display:flex; gap:10px;">
      <a href="/admin/settings-home.php" class="btn" style="box-shadow: 0 4px 12px rgba(0,0,0,0.3);">✏️ Modifier les images</a>
      <a href="/admin/index.php" class="btn secondary">Administration</a>
    </div>
  <?php endif; ?>
  
  <div class="ftg-hero__bg" aria-hidden="true"></div>
  <div class="ftg-inner ftg-hero__grid">
    <div class="ftg-hero__copy">
      <p class="ftg-kicker">Guides voyages · aviation d’affaires</p>
      <h1 class="ftg-hero__title">Sky Atlas</h1>
      <p class="ftg-hero__lead">
        Avis indépendants sur les destinations, les vols long-courriers et l’excellence des terminaux privés — avec le même sens du détail qu’un carnet de voyage de luxe.
      </p>
    </div>
    <div class="ftg-hero__visual">
      <figure class="ftg-hero__figure">
        <img class="ftg-hero__jet" src="<?= e($heroJetSrc) ?>" alt="" width="900" height="520">
      </figure>
    </div>
  </div>
</section>

<div id="sortable-categories">
<?php foreach ($categories as $index => $meta): 
    $cid = (int)$meta['id'];
    
    // Alterner gauche/droite
    $isReverse = ($index % 2 !== 0);
    // Alterner fond Blanc / fond Beige (un sur deux)
    $bgClass = ($index % 2 === 0) ? '' : 'category-block--beige';
?>
  <section class="category-block <?= $bgClass ?>" data-id="<?= $cid ?>">
    <div class="forbes-split <?= $isReverse ? 'forbes-split--reverse' : '' ?>">
      
      <div class="forbes-split__img" style="background-image: url('<?= e($meta['accent_img']) ?>');">
         <?php if ($isAdmin): ?>
            <a href="/admin/category-edit.php?id=<?= $cid ?>" class="edit-pencil" title="Modifier la catégorie">✏️</a>
         <?php endif; ?>
      </div>
      
      <div class="forbes-split__text">
         <p class="forbes-split__text-kicker">WE ARE</p>
         <h2 class="forbes-split__text-title">
            <a href="/category.php?slug=<?= e($meta['slug']) ?>" style="color:inherit; text-decoration:none;">
              <?= e($meta['label']) ?>
            </a>
         </h2>
         <p class="forbes-split__text-desc"><?= e($meta['dek']) ?></p>
      </div>

    </div>
  </section>

  <?php if ($index === 0 && $banner1Active === '1'): ?>
    <section class="forbes-banner" style="background-image: url('<?= e($banner1) ?>');">
      <?php if ($isAdmin): ?>
        <a href="/admin/settings-home.php" class="edit-pencil" title="Modifier la bannière">✏️</a>
      <?php endif; ?>
      <div class="forbes-banner__box">Explore Destinations</div>
    </section>
  <?php endif; ?>

<?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
new Sortable(document.getElementById('sortable-categories'), {
    animation: 150, 
    handle: '.forbes-split__text',
    onEnd: function() {
        const ids = Array.from(document.querySelectorAll('.category-block')).map(el => el.dataset.id);
        fetch('/admin/ajax-reorder.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ type:'category', ids }) 
        }).then(()=>window.location.reload());
    }
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>