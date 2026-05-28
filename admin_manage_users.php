<?php
require_once 'db.php';
// Vérifier que l'utilisateur est admin (ajoute ta logique ici)
// session_start(); vérification de $_SESSION['role'] === 'admin'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $idToDelete = (int)$_POST['delete_id'];
    if ($idToDelete !== ($_SESSION['user_id'] ?? -1)) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$idToDelete]);
    }
}
$users = $pdo->query("SELECT id, username, role FROM users")->fetchAll();
?>
<table border="1">
<tr><th>Nom</th><th>Rôle</th><th>Action</th></tr>
<?php foreach ($users as $user): ?>
<tr>
  <td><?= htmlspecialchars($user['username']) ?></td>
  <td><?= htmlspecialchars($user['role']) ?></td>
  <td>
    <?php if ($user['id'] !== ($_SESSION['user_id'] ?? -1)): ?>
      <form method="post" style="display:inline">
        <input type="hidden" name="delete_id" value="<?= $user['id'] ?>">
        <button type="submit" onclick="return confirm('Supprimer ce compte ?')">Supprimer</button>
      </form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</table>
