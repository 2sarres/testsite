<?php
declare(strict_types=1);
$pageTitle = 'Mon Profil';
require_once dirname(__DIR__) . '/_header.php';

db_install($pdo);
$user = require_admin();

$errors = [];

// Récupérer en temps réel les données fraîches de l'utilisateur depuis la BDD
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$currentUserData = $stmt->fetch();

if (!$currentUserData) {
    redirect('/logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    
    // Validations
    if ($firstName === '' || $lastName === '') {
        $errors[] = 'Le prénom et le nom sont obligatoires.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    
    // Vérifier si l'email n'est pas déjà pris par un AUTRE utilisateur
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
    $stmt->execute([
        ':email' => mb_strtolower($email, 'UTF-8'),
        ':id' => $user['id']
    ]);
    if ($stmt->fetch()) {
        $errors[] = 'Cette adresse email est déjà utilisée par un autre compte.';
    }
    
    // Gestion facultative du mot de passe
    $updatePassword = false;
    if ($password !== '') {
        if (strlen($password) < 10) {
            $errors[] = 'Le nouveau mot de passe doit faire au moins 10 caractères.';
        }
        if ($password !== $password2) {
            $errors[] = 'Les mots de passe de confirmation ne correspondent pas.';
        }
        $updatePassword = true;
    }
    
    // Si aucune erreur, exécution de la mise à jour
    if (!$errors) {
        try {
            if ($updatePassword) {
                $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, password_hash = :password_hash WHERE id = :id");
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => mb_strtolower($email, 'UTF-8'),
                    ':password_hash' => password_hash_secure($password),
                    ':id' => $user['id']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email WHERE id = :id");
                $stmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => mb_strtolower($email, 'UTF-8'),
                    ':id' => $user['id']
                ]);
            }
            
            // Mise à jour immédiate des variables de session pour l'affichage en cours
            $_SESSION['user']['first_name'] = $firstName;
            $_SESSION['user']['last_name'] = $lastName;
            $_SESSION['user']['email'] = mb_strtolower($email, 'UTF-8');
            
            flash_set('success', 'Vos informations personnelles ont été mises à jour avec succès !');
            redirect('/admin/profile.php');
        } catch (\Throwable $e) {
            $errors[] = 'Erreur lors de la mise à jour de votre profil.';
        }
    }
}

$flashes = flash_get_all();
?>

<div class="card" style="max-width: 600px; margin: 2rem auto;">
    <h1>⚙️ Mon Profil Personnel</h1>
    <p style="color: #666; margin-bottom: 2rem;">Modifie ici les informations affichées sur tes articles et tes identifiants de connexion.</p>
    
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
        
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <div style="flex: 1;">
                <label for="first_name" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Prénom</label>
                <input type="text" id="first_name" name="first_name" required value="<?= e($_POST['first_name'] ?? $currentUserData['first_name'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="flex: 1;">
                <label for="last_name" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Nom</label>
                <input type="text" id="last_name" name="last_name" required value="<?= e($_POST['last_name'] ?? $currentUserData['last_name'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
            </div>
        </div>

        <label for="email" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Adresse email</label>
        <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? $currentUserData['email'] ?? '') ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 1.5rem;">
        
        <div style="background: #f9f9f9; padding: 1.2rem; border-radius: 4px; margin-bottom: 1.5rem; border-left: 4px solid #9c27b0;">
            <h3 style="margin-top: 0; font-size: 1rem; color: #333;">🔒 Changer de mot de passe (optionnel)</h3>
            <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Laisse ces deux champs vides si tu ne souhaites pas modifier ton mot de passe actuel.</p>
            
            <label for="password" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" minlength="10" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 1rem; background: white;">
            <small style="display: block; margin-top: -0.7rem; margin-bottom: 1rem; color: #666; font-size: 0.8rem;">Minimum 10 caractères</small>
            
            <label for="password2" style="display: block; margin-bottom: 0.4rem; font-weight: bold;">Confirmer le nouveau mot de passe</label>
            <input type="password" id="password2" name="password2" minlength="10" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; background: white;">
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn" style="background: #4CAF50; color: white; padding: 0.8rem 1.5rem; font-size: 1rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; flex: 1;">
                💾 Enregistrer les modifications
            </button>
            <a href="/admin/index.php" class="btn secondary" style="text-align: center; line-height: 1.2; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 4px; flex: 1;">
                ← Annuler
            </a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/_footer.php'; ?>