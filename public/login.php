<?php
declare(strict_types=1);
$pageTitle = 'Connexion';
require_once __DIR__ . '/_header.php';

db_install($pdo);
if (current_user()) {
    redirect('/admin/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $user = get_user_by_email($pdo, $email);
    if (!$user || !password_verify_secure($password, (string)$user['password_hash'])) {
        $errors[] = 'Identifiants invalides.';
    } else {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];
        session_regenerate_id(true);
        flash_set('success', 'Connexion réussie.');
        redirect('/admin/index.php');
    }
}
?>
<div class="card">
  <h1>Connexion</h1>
  <?php foreach ($errors as $err): ?>
    <div class="flash error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Mot de passe</label>
    <input type="password" name="password" required>
    <button class="btn" type="submit">Se connecter</button>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

