<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__DIR__) . '/phpmailer/src/Exception.php';
require dirname(__DIR__) . '/phpmailer/src/PHPMailer.php';
require dirname(__DIR__) . '/phpmailer/src/SMTP.php';
require_once dirname(__DIR__) . '/src/email-config.php';

$pageTitle = 'Mot de passe oublié';
require_once __DIR__ . '/_header.php';

db_install($pdo);

if (current_user()) {
    redirect('/admin/index.php');
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();
    $email = trim((string)($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Veuillez entrer une adresse email valide.';
    } else {
        // Génère le token (retourne null si email introuvable, mais on ne dit pas à l'utilisateur si l'email existe ou non pour des raisons de sécurité)
        $token = generate_password_reset($pdo, $email);
        
        if ($token) {
            $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
            
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = 'ssl';
                $mail->Port = SMTP_PORT;
                $mail->CharSet = "UTF-8";
                $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

                $mail->setFrom(SMTP_FROM, 'Administration du site');
                $mail->addAddress($email);
                
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; padding: 20px; color: #333; background-color: #f9f9f9; border-radius: 8px;">
                    <h2>Demande de réinitialisation de mot de passe</h2>
                    <p>Bonjour,</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                    <a href="' . $resetLink . '" style="display: inline-block; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 15px 0;">Réinitialiser mon mot de passe</a>
                    <p>Ce lien est valide pendant 1 heure. Si vous n\'avez pas fait cette demande, vous pouvez ignorer cet email en toute sécurité.</p>
                </div>';
                
                $mail->send();
            } catch (Exception $e) {
                // Erreur d'envoi ignorée silencieusement pour l'utilisateur public
            }
        }
        
        // Message générique de sécurité
        $successMessage = "Si un compte est associé à cette adresse email, un lien de réinitialisation vient de vous être envoyé. Vérifiez votre boîte de réception (et vos spams).";
    }
}
?>

<div class="card" style="max-width: 450px; margin: 4rem auto; padding: 2rem;">
  <h1 style="text-align: center;">Mot de passe oublié</h1>
  
  <?php if ($successMessage): ?>
    <div style="background: #e8f5e9; padding: 1.5rem; border-radius: 4px; text-align: center; border-left: 4px solid #4CAF50; color: #2e7d32; line-height: 1.5;">
        ✅ <strong>Action prise en compte.</strong><br>
        <?= e($successMessage) ?>
    </div>
    <div style="text-align: center; margin-top: 2rem;">
        <a href="/login.php" class="btn" style="background: #2196F3; color: white; text-decoration: none;">Retour à la connexion</a>
    </div>
  <?php else: ?>
      <p style="color: #666; text-align: center; margin-bottom: 2rem; line-height: 1.4;">Entrez l'adresse email associée à votre compte. Nous vous enverrons un lien sécurisé pour définir un nouveau mot de passe.</p>

      <?php foreach ($errors as $err): ?>
        <div class="flash error" style="border-left: 4px solid #f44336;"><?= e($err) ?></div>
      <?php endforeach; ?>
      
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        
        <label style="font-weight: bold;">Adresse Email</label>
        <input type="email" name="email" required style="width: 100%; padding: 0.8rem; box-sizing: border-box; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid #ccc;">
        
        <button class="btn" type="submit" style="width: 100%; padding: 0.8rem; font-size: 1rem; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">✉️ Envoyer le lien</button>
      </form>
      
      <div style="text-align: center; margin-top: 1.5rem;">
          <a href="/login.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">← Retour à la connexion</a>
      </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>