<?php
declare(strict_types=1);

// FORCER L'AFFICHAGE DES ERREURS (ANTI PAGE BLANCHE)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$pageTitle = 'Générer une invitation';
require_once __DIR__ . '/../_header.php';

try {
    db_install($pdo);
    $user = require_admin();

    // Sécurité : Seul l'admin principal peut voir cette page
    if (($user['role'] ?? '') !== 'admin') {
        echo "<div class='card'><h1>Accès refusé</h1><p>Seuls les administrateurs peuvent générer des invitations.</p><a href='/admin/index.php' class='btn'>Retour</a></div>";
        require __DIR__ . '/../_footer.php';
        exit;
    }

    // Migration : on s'assure que la colonne 'role' existe bien !
    try {
        $pdo->query("SELECT role FROM admin_invites LIMIT 1");
    } catch (\Throwable $e) {
        $pdo->exec("ALTER TABLE admin_invites ADD COLUMN role TEXT NOT NULL DEFAULT 'admin'");
    }

    // Traiter la suppression
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invite'])) {
        csrf_verify_or_fail();
        $inviteId = (int)$_POST['delete_invite'];
        delete_invite($pdo, $inviteId);
        flash_set('success', 'Invitation supprimée.');
        redirect('/admin/invites.php');
    }

    // Traiter la génération
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invite'])) {
        csrf_verify_or_fail();
        
        $duration = (int)($_POST['duration'] ?? 60);
        $duration = max(10, min(1440, $duration));
        
        $role = trim($_POST['role'] ?? 'admin');
        if (!in_array($role, ['admin', 'editor'], true)) {
            $role = 'admin';
        }
        
        $token = create_invite($pdo, (int)$user['id'], $role, $duration);
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $inviteLink = $baseUrl . '/register-admin.php?token=' . $token;
        
        flash_set('success', "Invitation (" . strtoupper($role) . ") générée avec succès ! Elle expire dans $duration minutes.");
        $_SESSION['last_invite_link'] = $inviteLink;
        redirect('/admin/invites.php');
    }

    // Récupérer TOUTES les invitations
    $stmt = $pdo->prepare(
        "SELECT ai.*, u.email as created_by_email 
         FROM admin_invites ai
         LEFT JOIN users u ON ai.created_by = u.id
         ORDER BY ai.created_at DESC"
    );
    $stmt->execute();
    $invites = $stmt->fetchAll();

    $flashes = flash_get_all();
    $lastInviteLink = $_SESSION['last_invite_link'] ?? null;
    unset($_SESSION['last_invite_link']);
    
    // Définir l'URL de base pour la création dynamique des liens de copie
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

?>

<div class="card">
    <h1>Générer une invitation</h1>
    
    <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endforeach; ?>

    <form method="post" style="margin-bottom: 2rem; background: #f9f9f9; padding: 1.5rem; border-radius: 4px; border: 1px solid #ddd;">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        
        <label for="role" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Rôle à attribuer</label>
        <select id="role" name="role" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; margin-bottom: 1.5rem;">
            <option value="admin">Administrateur (Accès complet)</option>
            <option value="editor">Éditeur (Création d'articles uniquement)</option>
        </select>
        
        <label for="duration" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Durée de validité (minutes)</label>
        <input type="number" id="duration" name="duration" value="60" min="10" max="1440" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        <small style="display: block; margin-top: 0.3rem; color: #666; margin-bottom: 1.5rem;">Entre 10 et 1440 minutes (24 heures max)</small>
        
        <button class="btn" type="submit" name="generate_invite" style="background: #4CAF50; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%;">🔗 Générer le lien d'invitation</button>
    </form>

    <?php if ($lastInviteLink): ?>
        <div style="background: #e8f5e9; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem; border-left: 4px solid #4CAF50;">
            <h3 style="margin-top: 0; color: #2e7d32;">✓ Lien d'invitation généré</h3>
            <p style="word-break: break-all; margin: 1rem 0; background: white; padding: 1rem; border-radius: 3px; border: 1px solid #c8e6c9; font-size: 1.1rem;">
                <code><?= e($lastInviteLink) ?></code>
            </p>
            <button onclick="navigator.clipboard.writeText('<?= addslashes($lastInviteLink) ?>'); alert('Lien copié dans le presse-papiers !');" class="btn" style="background: #4CAF50; margin-top: 0.5rem; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                📋 Copier le lien
            </button>
        </div>
    <?php endif; ?>

    <h2 style="margin-top: 2rem;">Invitations générées (<?= count($invites) ?>)</h2>
    
    <?php if (empty($invites)): ?>
        <p style="color: #666; background: #f5f5f5; padding: 1rem; border-radius: 4px;">Aucune invitation pour le moment.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 0.7rem; text-align: left;">Créée par</th>
                        <th style="padding: 0.7rem; text-align: left;">Rôle visé</th>
                        <th style="padding: 0.7rem; text-align: left;">Créée le</th>
                        <th style="padding: 0.7rem; text-align: left;">Statut</th>
                        <th style="padding: 0.7rem; text-align: left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invites as $invite): 
                        $createdAt = new DateTime($invite['created_at']);
                        $expiresAt = new DateTime($invite['expires_at']);
                        $now = new DateTime();
                        $isExpired = $expiresAt < $now;
                        $isUsed = (int)$invite['used'] === 1;
                    ?>
                        <tr style="border-bottom: 1px solid #ddd; background: <?= $isUsed ? '#f9f9f9' : 'white' ?>;">
                            <td style="padding: 0.7rem; color: <?= $isUsed ? '#999' : '#333' ?>;">
                                <strong><?= e($invite['created_by_email'] ?? 'Système') ?></strong>
                            </td>
                            <td style="padding: 0.7rem;">
                                <span style="background: <?= ($invite['role'] ?? 'admin') === 'admin' ? '#4CAF50' : '#2196F3' ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: bold;">
                                    <?= e(strtoupper($invite['role'] ?? 'admin')) ?>
                                </span>
                            </td>
                            <td style="padding: 0.7rem; font-size: 0.9rem; color: <?= $isUsed ? '#999' : '#333' ?>;">
                                <?= date('d/m/Y H:i', strtotime($invite['created_at'])) ?>
                            </td>
                            <td style="padding: 0.7rem; font-size: 0.9rem;">
                                <?php if ($isUsed): ?>
                                    <span style="color: #4CAF50; font-weight: bold;">✅ Utilisé par <?= e($invite['used_by_email'] ?? '?') ?></span>
                                <?php elseif ($isExpired): ?>
                                    <span style="color: #f44336; font-weight: bold;">❌ Expiré</span>
                                <?php else: ?>
                                    <span style="color: #ff9800; font-weight: bold;">⏳ En attente</span><br>
                                    <span style="font-size: 0.8rem; color: #666;">Expire le <?= date('d/m/Y H:i', strtotime($invite['expires_at'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.7rem;">
                                
                                <?php if (!$isUsed && !$isExpired): ?>
                                    <?php $currentInviteLink = $baseUrl . '/register-admin.php?token=' . $invite['token']; ?>
                                    <button type="button" onclick="navigator.clipboard.writeText('<?= addslashes($currentInviteLink) ?>'); alert('Lien copié dans le presse-papiers !');" class="btn" style="background: #2196F3; color: white; padding: 0.4rem 0.8rem; font-size: 0.9rem; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; margin-bottom: 0.5rem; display: inline-block;">
                                        📋 Copier
                                    </button>
                                <?php endif; ?>
                                
                                <form method="post" style="display: inline-block;">
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
        <a href="/admin/users.php" class="btn" style="background: #2196F3; color: white; text-decoration: none;">👥 Gérer les utilisateurs</a>
        <a href="/admin/index.php" class="btn" style="background: #666; color: white; text-decoration: none;">← Retour au tableau de bord</a>
    </div>
</div>

<?php 
} catch (\Throwable $e) {
    echo "<div class='card' style='max-width: 600px; margin: 4rem auto; border-top: 4px solid #f44336; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>";
    echo "<h1 style='color: #d32f2f;'>🚨 Erreur Serveur Détectée</h1>";
    echo "<p>La page a rencontré un problème qui l'empêche de s'afficher. Voici les détails techniques :</p>";
    echo "<div style='background: #ffebee; padding: 1rem; border-radius: 4px; border: 1px solid #ffcdd2; color: #c62828; margin-bottom: 1rem; font-family: monospace; word-break: break-all;'>";
    echo "<strong>Message :</strong> " . e($e->getMessage()) . "<br><br>";
    echo "<strong>Fichier :</strong> " . e($e->getFile()) . " (Ligne " . $e->getLine() . ")";
    echo "</div>";
    echo "<a href='/admin/index.php' class='btn' style='background: #2196F3; color: white; text-decoration: none;'>Retourner à l'accueil admin</a>";
    echo "</div>";
}

require __DIR__ . '/../_footer.php'; 
?>