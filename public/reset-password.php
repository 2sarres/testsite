<?php
declare(strict_types=1);
$pageTitle = 'Réinitialisation';
require_once __DIR__ . '/_header.php';

db_install($pdo);

if (current_user()) {
    redirect('/admin/index.php');
}

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    echo '<div class="card" style="max-width: 450px; margin: 4rem auto; padding: 2rem; text-align: center;"><h1>Lien invalide</h1><p>Le lien est manquant ou invalide.</p><a href="/login.php" class="btn">Retour</a></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

// Vérifier la validité du token
$user = verify_password_reset_token($pdo, $token);
if (!$user) {
    echo '<div class="card" style="max-width: 450px; margin: 4rem auto; padding: 2rem; text-align: center;"><h1>Lien expiré</h1><p>Ce lien de réinitialisation est invalide ou a expiré (validité de 1 heure).</p><a href="/forgot-password.php" class="btn">Demander un nouveau lien</a></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 10) {
        $errors[] = 'Le mot de passe doit faire au moins 10 caractères.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    } else {
        if (update_password_with_token($pdo, $token, $password)) {
            flash_set('success', 'Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter.');
            redirect('/login.php');
        } else {
            $errors[] = 'Une erreur système est survenue. Veuillez réessayer.';
        }
    }
}
?>

<div class="card" style="max-width: 450px; margin: 4rem auto; padding: 2rem;">
  <h1 style="text-align: center;">Nouveau mot de passe</h1>
  <p style="color: #666; text-align: center; margin-bottom: 2rem;">Compte ciblé : <strong><?= e($user['email']) ?></strong></p>

  <?php foreach ($errors as $err): ?>
    <div class="flash error" style="border-left: 4px solid #f44336;"><?= e($err) ?></div>
  <?php endforeach; ?>
  
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    
    <label style="font-weight: bold;">Nouveau mot de passe</label>
    <input type="password" name="password" required minlength="10" style="width: 100%; padding: 0.8rem; box-sizing: border-box; border-radius: 4px; border: 1px solid #ccc;">
    <small style="display: block; margin-bottom: 1rem; color: #666;">Minimum 10 caractères.</small>
    
    <label style="font-weight: bold;">Confirmer le mot de passe</label>
    <input type="password" name="password_confirm" required minlength="10" style="width: 100%; padding: 0.8rem; box-sizing: border-box; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid #ccc;">
    
    <button class="btn" type="submit" style="width: 100%; padding: 0.8rem; font-size: 1rem; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">💾 Enregistrer et se connecter</button>
  </form>
</div>

<?php require __DIR__ . '/_footer.php'; ?>