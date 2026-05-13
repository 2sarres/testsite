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
    $accentImg = trim((string)($_POST['accent_img'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 100);

    if ($label === '') {
        $errors[] = 'Le libellé est obligatoire.';
    }
    $slugBase = $slugManual !== '' ? $slugManual : $label;
    if (normalize_slug($slugBase) === '') {
        $errors[] = 'Impossible de générer un slug : précisez le champ slug URL.';
    }
    if (!$errors) {
        $slug = make_unique_category_slug($pdo, $slugBase);
        if ($accentImg === '') {
            $accentImg = 'https://images.unsplash.com/photo-1436491865332-7a61a109c05b?w=520&q=80&auto=format&fit=crop';
        }
        $stmt = $pdo->prepare(
            'INSERT INTO categories (slug, label, dek, accent_img, sort_order, created_at)
             VALUES (:slug, :label, :dek, :accent_img, :sort_order, :created_at)'
        );
        $stmt->execute([
            ':slug' => $slug,
            ':label' => $label,
            ':dek' => $dek,
            ':accent_img' => $accentImg,
            ':sort_order' => $sortOrder,
            ':created_at' => date('c'),
        ]);
        flash_set('success', 'Catégorie créée.');
        redirect('/admin/categories.php');
    }
}
?>
<div class="card">
  <h1>Nouvelle catégorie</h1>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Libellé (titre affiché)</label>
    <input type="text" name="label" required value="<?= e($label) ?>">
    <label>Slug URL (optionnel, sinon dérivé du libellé)</label>
    <input type="text" name="slug" value="<?= e($slugManual) ?>" placeholder="ex: destinations-europeennes">
    <label>Chapô / description courte</label>
    <textarea name="dek" rows="3"><?= e($dek) ?></textarea>
    <label>Image décorative (URL https)</label>
    <input type="url" name="accent_img" value="<?= e($accentImg) ?>" placeholder="https://...">
    <label>Ordre (nombre, plus petit = plus haut)</label>
    <input type="number" name="sort_order" value="<?= (int)$sortOrder ?>">
    <p><button class="btn" type="submit">Créer</button>
    <a class="btn secondary" href="/admin/categories.php">Annuler</a></p>
  </form>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>
