<?php
declare(strict_types=1);
$pageTitle = 'Gestion des utilisateurs';
require_once __DIR__ . '/../_header.php';

db_install($pdo);
$user = require_admin();

// Restriction stricte de la gestion des utilisateurs aux seuls administrateurs
if (($user['role'] ?? 'admin') !== 'admin') {
    http_response_code(403);
    echo '<div class="card"><h1>Accès interdit</h1><p>Seuls les administrateurs peuvent accéder à la gestion des utilisateurs.</p></div>';
    require __DIR__ . '/../_footer.php';
    exit;
}

// SÉCURITÉ : Crée la colonne 'is_active' si elle n'existe pas encore dans la table
try {
    $pdo->query("SELECT is_active FROM users LIMIT 1");
} catch (\Throwable $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1");
    } catch (\Throwable $ex) {}
}

$errors = [];
$redirectNeeded = false;
$failedUserId = null; // Permet d'afficher le bouton de désactivation ciblé en cas d'erreur de suppression

// Traiter l'activation / la désactivation d'un compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_user_id'])) {
    csrf_verify_or_fail();
    $userId = (int)$_POST['toggle_status_user_id'];
    
    if ($userId === (int)$user['id']) {
        $errors[] = 'Tu ne peux pas désactiver ton propre compte.';
    } else {
        $targetUser = get_user_by_id($pdo, $userId);
        if ($targetUser) {
            // Récupérer le statut actuel
            $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentStatus = (int)$stmt->fetchColumn();
            
            $newStatus = $currentStatus === 1 ? 0 : 1;
            $actionText = $newStatus === 0 ? 'désactivé (accès bloqué)' : 'réactivé (accès autorisé)';
            
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            flash_set('success', "Le compte de {$targetUser['email']} a été $actionText avec succès.");
            $redirectNeeded = true;
        } else {
            $errors[] = 'Utilisateur non trouvé.';
        }
    }
}

// Traiter la suppression d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    csrf_verify_or_fail();
    
    $userId = (int)$_POST['delete_user_id'];
    
    if ($userId === (int)$user['id']) {
        $errors[] = 'Tu ne peux pas te supprimer toi-même.';
    } else {
        $userToDelete = get_user_by_id($pdo, $userId);
        if ($userToDelete) {
            try {
                delete_user($pdo, $userId);
                flash_set('success', "Utilisateur {$userToDelete['email']} supprimé avec succès.");
                $redirectNeeded = true;
            } catch (\Throwable $e) {
                $errors[] = "Impossible de supprimer " . $userToDelete['email'] . " car ce compte possède des articles ! Tu dois d'abord supprimer ses articles pour pouvoir détruire son profil.";
                $failedUserId = $userId; // On stocke l'ID pour proposer le bouton de désactivation
            }
        } else {
            $errors[] = 'Utilisateur non trouvé.';
        }
    }
}

// Récupérer tous les utilisateurs avec leur statut d'activité
$users = $pdo->query("SELECT id, email, role, first_name, last_name, created_at, COALESCE(is_active, 1) as is_active FROM users ORDER BY created_at DESC")->fetchAll();
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
        <div class="flash error" style="background: #ffebee; color: #c62828; padding: 1.2rem; border-left: 4px solid #f44336; margin-bottom: 1.5rem; border-radius: 4px;">
            <div style="margin-bottom: 0.5rem;">⚠️ <strong>Action refusée :</strong> <?= e($err) ?></div>
            
            <!-- BOUTON DÉSACTIVATION D'URGENCE LORSQUE LA SUPPRESSION ÉCHOUE -->
            <?php if ($failedUserId !== null): ?>
                <div style="margin-top: 0.8rem;">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="toggle_status_user_id" value="<?= $failedUserId ?>">
                        <button type="submit" class="btn" style="background: #ff9800; color: white; padding: 0.5rem 1rem; font-weight: bold; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                            🔒 Désactiver l'accès à ce compte plutôt que de le supprimer
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="/admin/invites.php" class="btn" style="background: #4CAF50;">+ Générer une invitation</a>
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
                        <th style="padding: 0.7rem; text-align: left;">Nom</th>
                        <th style="padding: 0.7rem; text-align: left;">Email</th>
                        <th style="padding: 0.7rem; text-align: left;">Rôle</th>
                        <th style="padding: 0.7rem; text-align: left;">Statut</th>
                        <th style="padding: 0.7rem; text-align: left;">Créé le</th>
                        <th style="padding: 0.7rem; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #ddd; background: <?= (int)$u['is_active'] === 0 ? '#fcfcfc' : 'white' ?>;">
                            <td style="padding: 0.7rem; color: <?= (int)$u['is_active'] === 0 ? '#999' : '#333' ?>;">
                                <strong><?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '—') ?></strong>
                            </td>
                            <td style="padding: 0.7rem; color: <?= (int)$u['is_active'] === 0 ? '#999' : '#333' ?>;">
                                <?= e($u['email']) ?>
                                <?php if ($u['id'] === (int)$user['id']): ?>
                                    <span style="background: #ffeb3b; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; margin-left: 0.5rem;"> (toi)</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.7rem;">
                                <span style="background: <?= $u['role'] === 'admin' ? '#4CAF50' : '#2196F3' ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">
                                    <?= e(strtoupper($u['role'])) ?>
                                </span>
                            </td>
                            <td style="padding: 0.7rem;">
                                <?php if ((int)$u['is_active'] === 1): ?>
                                    <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">🟢 ACTIF</span>
                                <?php else: ?>
                                    <span style="background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">🔴 INACTIF</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.7rem; font-size: 0.9rem; color: #666;">
                                <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                            </td>
                            <td style="padding: 0.7rem;">
                                <?php if ($u['id'] !== (int)$user['id']): ?>
                                    <!-- BOUTON ACTIVER / DÉSACTIVER -->
                                    <form method="post" style="display: inline; margin-right: 0.5rem;">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="toggle_status_user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn" style="background: #ff9800; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem; border: none; border-radius: 4px; cursor: pointer;">
                                            <?= (int)$u['is_active'] === 1 ? '🔒 Désactiver' : '🔓 Réactiver' ?>
                                        </button>
                                    </form>

                                    <!-- BOUTON SUPPRIMER -->
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="delete_user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn" style="background: #f44336; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem; border: none; border-radius: 4px; cursor: pointer;" onclick="return confirm('Supprimer définitivement cet utilisateur ?')">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.9rem; font-style: italic;">Aucune action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd; background: #f9f9f9; padding: 1rem; border-radius: 4px;">
        <h3 style="margin-top: 0;">ℹ️ Gestion des accès</h3>
        <p style="margin: 0; font-size: 0.95rem; color: #555; line-height: 1.4;">
            La <strong>désactivation</strong> bloque immédiatement la connexion d'un membre sans toucher à ses publications. C'est l'alternative idéale si la <strong>suppression</strong> définitive est bloquée par la présence d'articles reliés à son profil.
        </p>
    </div>
</div>

<?php
if ($redirectNeeded) {
    redirect('/admin/users.php');
}
require __DIR__ . '/../_footer.php';
?>