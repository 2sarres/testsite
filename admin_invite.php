<?php
require_once 'db.php';
// Vérifier que l'utilisateur est admin (ajoute ta logique ici par session ou autre)
// session_start(); vérification de $_SESSION['role'] === 'admin'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = $pdo->prepare("INSERT INTO admin_invites (token, expires_at) VALUES (?, ?)");
    $stmt->execute([$token, $expires]);

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    $url = $base_url . "/register_admin.php?token=$token";
    echo "Lien d'inscription temporaire : <a href='$url'>$url</a> (expire dans 10 minutes)";
    exit;
}
?>
<form method="post">
  <button type="submit">Générer lien d'inscription admin</button>
</form>
