<?php
declare(strict_types=1);
$pageTitle = 'Connexion';
require_once __DIR__ . '/_header.php';

db_install($pdo);

// Si l'utilisateur est déjà connecté, rediriger vers l'administration
if (current_user()) {
    redirect('/admin/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $userRow = get_user_by_email($pdo, $email);
    
    if ($userRow && password_verify_secure($password, $userRow['password_hash'])) {
        
        // VÉRIFICATION DU STATUT DU COMPTE (ACTIF / INACTIF)
        $stmt = $pdo->prepare("SELECT COALESCE(is_active, 1) FROM users WHERE id = ?");
        $stmt->execute([$userRow['id']]);
        $isActive = (int)$stmt->fetchColumn();
        
        if ($isActive === 0) {
            $errors[] = 'Ce compte a été désactivé par un administrateur. Votre accès est suspendu.';
        } else {
            // Connexion autorisée
            $_SESSION['user'] = $userRow;
            flash_set('success', 'Connexion réussie !');
            redirect('/admin/index.php');
        }
    } else {
        $errors[] = 'Identifiants invalides ou introuvables.';
    }
}

$flashes = flash_get_all();
?>

<div class="card" style="max-width: 420px; margin: 4rem auto; padding: 2rem;">
    <h1 style="text-align: center; margin-bottom: 1.5rem;">Espace Connexion</h1>
    
    <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endforeach; ?>
    
    <?php foreach ($errors as $err): ?>
        <div class="flash error" style="background: #ffebee; color: #c62828; padding: 0.8rem; border-left: 4px solid #f44336; margin-bottom: 1rem; border-radius: 4px;">
            <?= e($err) ?>
        </div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        
        <label for="email" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Adresse email</label>
        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 1.2rem;">
        
        <label for="password" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Mot de passe</label>
        <input type="password" id="password" name="password" required style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 1.5rem;">
        
        <button type="submit" class="btn" style="background: #2196F3; color: white; width: 100%; padding: 0.8rem; font-size: 1rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            🔐 Se connecter
        </button>
    </form>
    
    <div style="text-align: center; margin-top: 1.5rem;">
        <a href="/" style="color: #666; text-decoration: none; font-size: 0.9rem;">← Retour au site public</a>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>