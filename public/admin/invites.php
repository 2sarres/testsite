<?php
declare(strict_types=1);
$pageTitle = 'Générer une invitation';
require_once __DIR__ . '/../_header.php';

db_install($pdo);
$user = require_admin();

// SÉCURITÉ ANTI-PAGE BLANCHE : Répare la base de données SQLite
// Ajoute la colonne 'role' à 'admin_invites' si elle n'existe pas encore.
try {
    $pdo->query("SELECT role FROM admin_invites LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE admin_invites ADD COLUMN role TEXT NOT NULL DEFAULT 'admin'");
    } catch (Exception $ex) {
        // Échec silencieux
    }
}

// Traiter la suppression d'une invitation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invite'])) {
    csrf_verify_or_fail();
    $inviteId = (int)$_POST['delete_invite'];
    delete_invite($pdo, $inviteId);
    flash_set('success', 'Invitation supprimée.');
    redirect('/admin/invites.php');
}

// Traiter la génération d'une nouvelle invitation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invite'])) {
    csrf_verify_or_fail();
    
    $duration = (int)($_POST['duration'] ?? 60);
    $duration = max(10, min(1440, $duration)); // Entre 10 et 1440 minutes
    
    // Récupérer le rôle choisi
    $role = trim($_POST['role'] ?? 'admin');
    if (!in_array($role, ['admin', 'editor'], true)) {
        $role = 'admin';
    }
    
    // Utilise la fonction create_invite de ton helpers.php qui gère déjà les rôles
    $token = create_invite($pdo, (int)$user['id'], $role, $duration);
    
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $inviteLink = $baseUrl . '/register-admin.php?token=' . $token;
    
    flash_set('success', "Invitation (" . strtoupper($role) . ") générée avec succès ! Elle expire dans $duration minutes.");
    $_SESSION['last_invite_link'] = $inviteLink;
    redirect('/admin/invites.php');
}

// Récupérer TOUTES les invitations actives (Admin et Éditeur)
$stmt = $pdo->prepare(
    "SELECT ai.*, u.email as created_by_email 
     FROM admin_invites ai
     LEFT JOIN users u ON ai.created_by = u.id
     WHERE ai.used = 0 AND ai.expires_at > datetime('now')
     ORDER BY ai.created_at DESC"
);
$stmt->execute();
$invites = $stmt->fetchAll();

$flashes = flash_get_all();
$lastInviteLink = $_SESSION['last_invite_link'] ?? null;
unset($_SESSION['last_invite_link']);
?>

<div class="card">
    <h1>Générer une invitation</h1>
    
    <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endforeach; ?>

    <form method="post" style="margin-bottom: 2rem; background: #f9f9f9; padding: 1rem; border-radius: 4px;">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        
        <label for="role" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Rôle à attribuer</label>
        <select id="role" name="role" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; margin-bottom: 1rem;">
            <option value="admin">Administrateur (Accès complet)</option>
            <option value="editor">Éditeur (Création d'articles uniquement)</option>
        </select>
        
        <label for="duration" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Durée de validité (minutes)</label>
        <input type="number" id="duration" name="duration" value="60" min="10" max="1440" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
        <small style="display: block; margin-top: 0.3rem; color: #666;">Entre 10 et 1440 minutes (24 heures max)</small>
        
        <button class="btn" type="submit" name="generate_invite" style="background: #4CAF50; margin-top: 1rem; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 4px; cursor: pointer;">🔗 Générer une invitation</button>
    </form>

    <?php if ($lastInviteLink): ?>
        <div style="background: #e8f5e9; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem; border-left: 4px solid #4CAF50;">
            <h3 style="margin-top: 0; color: #2e7d32;">✓ Lien d'invitation généré</h3>
            <p style="word-break: break-all; margin: 1rem 0; background: white; padding: 0.7rem; border-radius: 3px; border: 1px solid #c8e6c9;">
                <code><?= e($lastInviteLink) ?></code>
            </p>
            <button onclick="navigator.clipboard.writeText('<?= addslashes($lastInviteLink) ?>'); alert('Lien copié dans le presse-papiers !');" class="btn" style="background: #4CAF50; margin-top: 0.5rem; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                📋 Copier le lien
            </button>
            <p style="margin-top: 1rem; margin-bottom: 0; font-size: 0.9rem; color: #558b2f;">
                📧 Partage ce lien avec la personne qui doit créer son compte.
            </p>
        </div>
    <?php endif; ?>

    <h2 style="margin-top: 2rem;">Invitations actives (<?= count($invites) ?>)</h2>
    
    <?php if (empty($invites)): ?>
        <p style="color: #666; background: #f5f5f5; padding: 1rem; border-radius: 4px;">Aucune invitation active actuellement.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 0.7rem; text-align: left;">Créée par</th>
                        <th style="padding: 0.7rem; text-align: left;">Rôle visé</th>
                        <th style="padding: 0.7rem; text-align: left;">Créée le</th>
                        <th style="padding: 0.7rem; text-align: left;">Expire le</th>
                        <th style="padding: 0.7rem; text-align: left;">Token</th>
                        <th style="padding: 0.7rem; text-align: left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invites as $invite): 
                        $createdAt = new DateTime($invite['created_at']);
                        $expiresAt = new DateTime($invite['expires_at']);
                        $now = new DateTime();
                        $remaining = $expiresAt->diff($now);
                    ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 0.7rem;">
                                <strong><?= e($invite['created_by_email'] ?? 'Système') ?></strong>
                            </td>
                            <td style="padding: 0.7rem;">
                                <span style="background: <?= ($invite['role'] ?? 'admin') === 'admin' ? '#4CAF50' : '#2196F3' ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">
                                    <?= e(strtoupper($invite['role'] ?? 'admin')) ?>
                                </span>
                            </td>
                            <td style="padding: 0.7rem; font-size: 0.9rem;">
                                <?= date('d/m/Y H:i', strtotime($invite['created_at'])) ?>
                            </td>
                            <td style="padding: 0.7rem; font-size: 0.9rem;">
                                <strong><?= date('d/m/Y H:i', strtotime($invite['expires_at'])) ?></strong>
                                <br>
                                <span style="color: <?= $remaining->invert === 1 || ($remaining->h === 0 && $remaining->i === 0) ? '#f44336' : '#666' ?>; font-size: 0.8rem;">
                                    <?php
                                    if ($expiresAt < $now) {
                                        echo "Expiré";
                                    } elseif ($remaining->d > 0) {
                                        echo "Expire dans " . $remaining->d . "j " . $remaining->h . "h";
                                    } else {
                                        echo "Expire dans " . $remaining->h . "h " . $remaining->i . "m";
                                    }
                                    ?>
                                </span>
                            </td>
                            <td style="padding: 0.7rem;">
                                <code style="font-size: 0.8rem; background: #f0f0f0; padding: 3px 6px; border-radius: 3px; word-break: break-all;">
                                    <?= e(substr($invite['token'], 0, 16)) ?>...
                                </code>
                            </td>
                            <td style="padding: 0.7rem;">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="delete_invite" value="<?= (int)$invite['id'] ?>">
                                    <button type="submit" class="btn" style="background: #f44336; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem; border: none; border-radius: 4px; cursor: pointer;" onclick="return confirm('Supprimer cette invitation ?')">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="/admin/users.php" class="btn" style="background: #2196F3; color: white; padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none;">👥 Gérer les utilisateurs</a>
        <a href="/admin/index.php" class="btn" style="background: #2196F3; color: white; padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none;">← Retour au tableau de bord</a>
    </div>
</div>

<?php require __DIR__ . '/../_footer.php'; ?>