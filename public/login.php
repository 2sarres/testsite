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
    
    // Vérification stricte et sécurisée des identifiants
    if (!$user || !password_verify_secure($password, (string)$user['password_hash'])) {
        $errors[] = 'Identifiants invalides.';
    } else {
        // Sécurité : Vérifier si le compte a été désactivé
        $isActive = (int)($user['is_active'] ?? 1);
        if ($isActive === 0) {
            $errors[] = 'Accès refusé : Ce compte a été désactivé.';
        } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'email' => (string)$user['email'],
                'role' => (string)$user['role'],
                'first_name' => (string)($user['first_name'] ?? ''),
                'last_name' => (string)($user['last_name'] ?? ''),
            ];
            session_regenerate_id(true);
            flash_set('success', 'Connexion réussie.');
            redirect('/admin/index.php');
        }
    }
}

$flashes = flash_get_all();
?>
<div class="card" style="max-width: 450px; margin: 4rem auto; padding: 2rem;">
  <h1 style="text-align: center;">Connexion</h1>
  
  <?php foreach ($flashes as $flash): ?>
    <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>

  <?php foreach ($errors as $err): ?>
    <div class="flash error" style="border-left: 4px solid #f44336;"><?= e($err) ?></div>
  <?php endforeach; ?>
  
  <form method="post" style="margin-top: 1.5rem;">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <label style="font-weight: bold;">Email</label>
    <input type="email" name="email" required style="width: 100%; padding: 0.8rem; box-sizing: border-box; margin-bottom: 1rem; border-radius: 4px; border: 1px solid #ccc;">
    
    <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <label style="font-weight: bold;">Mot de passe</label>
        <a href="/forgot-password.php" style="font-size: 0.85rem; color: #2196F3; text-decoration: none;">Mot de passe oublié ?</a>
    </div>
    <input type="password" name="password" required style="width: 100%; padding: 0.8rem; box-sizing: border-box; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid #ccc;">
    
    <button class="btn" type="submit" style="width: 100%; padding: 0.8rem; font-size: 1rem; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🔐 Se connecter</button>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>