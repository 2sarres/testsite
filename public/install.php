<?php
declare(strict_types=1);
require_once __DIR__ . '/_header.php';

db_install($pdo);

if (has_any_user($pdo)) {
    echo '<div class="card"><h1>Installation déjà effectuée</h1><p>Un utilisateur existe déjà.</p></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Le mot de passe doit faire au moins 10 caractères.';
    }
    if ($password !== $password2) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        create_user($pdo, $email, $password, 'admin');
        flash_set('success', 'Installation terminée. Connecte-toi avec ton compte admin.');
        redirect('/login.php');
    }
}
?>
<div class="card">
  <h1>Installation du site</h1>
  <p>Crée le premier compte administrateur.</p>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Email admin</label>
    <input type="email" name="email" required>
    <label>Mot de passe</label>
    <input type="password" name="password" required minlength="10">
    <label>Confirmer le mot de passe</label>
    <input type="password" name="password2" required minlength="10">
    <button class="btn" type="submit">Installer</button>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

