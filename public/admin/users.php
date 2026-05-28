<?php
declare(strict_types=1);
$pageTitle = 'Gestion des utilisateurs';
require_once __DIR__ . '/../_header.php';

db_install($pdo);
$user = require_admin();

$errors = [];
$deleted = false;

// Traiter la suppression d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    csrf_verify_or_fail();
    
    $userId = (int)$_POST['delete_user_id'];
    
    // Empêcher de se supprimer soi-même
    if ($userId === (int)$user['id']) {
        $errors[] = 'Tu ne peux pas te supprimer toi-même.';
    } else {
        // Vérifier que l'utilisateur existe
        $userToDelete = get_user_by_id($pdo, $userId);
        if ($userToDelete) {
            delete_user($pdo, $userId);
            flash_set('success', "Utilisateur {$userToDelete['email']} supprimé avec succès.");
            $deleted = true;
        } else {
            $errors[] = 'Utilisateur non trouvé.';
        }
    }
}

// Récupérer tous les utilisateurs
$users = get_all_users($pdo);
$flashes = flash_get_all();

?>

<div class="card">
    <h1>Gestion des utilisateurs</h1>
    
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
    
    <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="/admin/invites.php" class="btn" style="background: #4CAF50;">+ Générer une invitation admin</a>
        <a href="/admin/index.php" class="btn" style="background: #2196F3;">← Retour au tableau de bord</a>
    </div>
    
    <h2 style="margin-top: 2rem;">Liste des utilisateurs (<?= count($users) ?>)</h2>
    
    <?php if (empty($users)): ?>
        <p style="color: #666; background: #f5f5f5; padding: 1rem; border-radius: 4px;">Aucun utilisateur trouvé.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 0.7rem; text-align: left;">Email</th>
                        <th style="padding: 0.7rem; text-align: left;">Rôle</th>
                        <th style="padding: 0.7rem; text-align: left;">Créé le</th>
                        <th style="padding: 0.7rem; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 0.7rem;">
                                <strong><?= e($u['email']) ?></strong>
                                <?php if ($u['id'] === (int)$user['id']): ?>
                                    <span style="background: #ffeb3b; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; margin-left: 0.5rem;"> (toi)</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.7rem;">
                                <span style="background: <?= $u['role'] === 'admin' ? '#4CAF50' : '#2196F3' ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 0.9rem;">
                                    <?= e(strtoupper($u['role'])) ?>
                                </span>
                            </td>
                            <td style="padding: 0.7rem; font-size: 0.9rem;">
                                <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                            </td>
                            <td style="padding: 0.7rem;">
                                <?php if ($u['id'] !== (int)$user['id']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="delete_user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn" style="background: #f44336; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem;" onclick="return confirm('Êtes-vous certain ? Cette action est irréversible.\n\nUtilisateur : <?= e($u['email']) ?>')">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.9rem;">Impossible à supprimer</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd; background: #f9f9f9; padding: 1rem; border-radius: 4px;">
        <h3 style="margin-top: 0;">ℹ️ Informations</h3>
        <ul style="margin: 0.5rem 0;">
            <li>Total d'utilisateurs : <strong><?= count($users) ?></strong></li>
            <li>Administrateurs : <strong><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></strong></li>
            <li>Éditeurs : <strong><?= count(array_filter($users, fn($u) => $u['role'] === 'editor')) ?></strong></li>
        </ul>
    </div>
</div>

<?php
if ($deleted) {
    redirect('/admin/users.php');
}
require __DIR__ . '/../_footer.php';
?>
