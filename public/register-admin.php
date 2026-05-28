<?php
declare(strict_types=1);
$pageTitle = 'Inscription Admin';
require_once __DIR__ . '/_header.php';

db_install($pdo);

// Si l'utilisateur est déjà connecté, rediriger vers l'admin
if (current_user()) {
    redirect('/admin/index.php');
}

// Récupérer le token depuis l'URL
$token = trim($_GET['token'] ?? '');
if ($token === '') {
    http_response_code(400);
    echo '<div class="card"><h1>Erreur</h1><p>Token manquant ou invalide.</p></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

// Vérifier le token
$invite = get_invite_by_token($pdo, $token);
if (!$invite) {
    http_response_code(400);
    echo '<div class="card"><h1>Erreur</h1><p>Lien invalide ou expiré.</p></div>';
    require __DIR__ . '/_footer.php';
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    
    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    
    if (get_user_by_email($pdo, $email)) {
        $errors[] = 'Cet email existe déjà.';
    }
    
    if (strlen($password) < 10) {
        $errors[] = 'Le mot de passe doit faire au moins 10 caractères.';
    }
    
    if ($password !== $password2) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }
    
    // Si pas d'erreurs, créer l'admin
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            create_user($pdo, $email, $password, 'admin');
            mark_invite_used($pdo, (int)$invite['id'], $email);
            $pdo->commit();
            
            $success = true;
            flash_set('success', 'Compte admin créé avec succès ! Connecte-toi maintenant.');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur lors de la création du compte.';
        }
    }
}

$flashes = flash_get_all();
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <h1 style="text-align: center;">Création d'un compte administrateur</h1>
    
    <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach ($errors as $err): ?>
        <div class="flash error">
            <?= e($err) ?>
        </div>
    <?php endforeach; ?>
    
    <?php if ($success): ?>
        <div style="background: #e8f5e9; padding: 2rem; border-radius: 4px; text-align: center; margin: 2rem 0; border-left: 4px solid #4CAF50;">
            <h2 style="color: #2e7d32; margin-top: 0;">✓ Succès !</h2>
            <p style="margin: 1rem 0; color: #558b2f;">Ton compte administrateur a été créé avec succès.</p>
            <a href="/login.php" class="btn" style="background: #4CAF50; margin-top: 1rem;">🔐 Se connecter</a>
        </div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            
            <label for="password" style="margin-top: 1rem;">Mot de passe</label>
            <input type="password" id="password" name="password" required minlength="10" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            <small style="display: block; margin-top: 0.3rem; color: #666;">Minimum 10 caractères</small>
            
            <label for="password2" style="margin-top: 1rem;">Confirmer le mot de passe</label>
            <input type="password" id="password2" name="password2" required minlength="10" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            
            <button class="btn" type="submit" style="background: #4CAF50; color: white; width: 100%; margin-top: 1.5rem; padding: 0.8rem; font-size: 1rem;">✓ Créer le compte administrateur</button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
