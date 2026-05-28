<?php
require_once 'db.php';
$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM admin_invites WHERE token = ? AND expires_at > NOW() AND used = 0");
$stmt->execute([$token]);
$invite = $stmt->fetch();
if (!$invite) {
    die('Lien invalide ou expiré');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")->execute([$username, $password]);
    $pdo->prepare("UPDATE admin_invites SET used = 1 WHERE id = ?")->execute([$invite['id']]);
    $pdo->commit();
    echo "Nouveau compte admin créé.";
    exit;
}
?>
<form method="post">
    <label>Nom d'utilisateur : <input name="username" required></label><br>
    <label>Mot de passe : <input name="password" type="password" required></label><br>
    <button type="submit">Créer admin</button>
</form>
