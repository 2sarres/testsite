<?php
declare(strict_types=1);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require dirname(__DIR__) . '/phpmailer/src/Exception.php';
require dirname(__DIR__) . '/phpmailer/src/PHPMailer.php';
require dirname(__DIR__) . '/phpmailer/src/SMTP.php';

$pageTitle = 'Nous contacter';
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/config.php';

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_fail();

    $formData['name'] = htmlspecialchars(trim($_POST['name'] ?? ''));
    $formData['email'] = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $formData['subject'] = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $formData['message'] = htmlspecialchars(trim($_POST['message'] ?? ''));

    if ($formData['name'] === '') {
        $errors[] = 'Le nom est requis.';
    }
    if ($formData['email'] === '') {
        $errors[] = 'L\'email est requis.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide.';
    }
    if ($formData['subject'] === '') {
        $errors[] = 'Le sujet est requis.';
    }
    if ($formData['message'] === '') {
        $errors[] = 'Le message est requis.';
    }

    if (empty($errors)) {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST; // Correction pour prendre le Host depuis email-config.php
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->CharSet = "UTF-8";
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // NOUVEAUTÉ : Envoyer le mail à TOUS les administrateurs
            $adminEmails = get_all_admin_emails($pdo);
            if (empty($adminEmails)) {
                // Fallback sécurité si aucun admin trouvé
                $mail->addAddress(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : SMTP_USER);
            } else {
                foreach ($adminEmails as $adminEmail) {
                    $mail->addAddress($adminEmail);
                }
            }
            
            // Reply-To pour répondre à l'expéditeur
            $mail->addReplyTo($formData['email'], $formData['name']);
            
            // From
            $mail->setFrom(SMTP_FROM, defined('SITE_NAME') ? SITE_NAME : 'Site Web');

            // Contenu de l'email
            $mail->isHTML(true);
            $mail->Subject = '[Contact] ' . $formData['subject'];
            
            $message = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Nouveau message de contact</title>
                <style>
                    @media only screen and (max-width: 600px) {
                        .container {
                            width: 100% !important;
                        }
                        .content {
                            padding: 20px !important;
                        }
                    }
                    body {
                        margin: 0; 
                        padding: 0; 
                        font-family: Arial, sans-serif; 
                        background-color: rgb(200, 230, 236); 
                    }
                    .container {
                        max-width: 600px; 
                        margin: 0 auto; 
                        background-color: white;
                        border-radius: 8px;
                        overflow: hidden;
                        margin-top: 20px;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                    }
                    .content {
                        padding: 40px; 
                        text-align: left; 
                        color: #333;
                    }
                    h1 {
                        margin-top: 0;
                        color: rgb(36, 159, 187);
                        border-bottom: 2px solid #eee;
                        padding-bottom: 10px;
                    }
                    .info-block {
                        background: #f9f9f9;
                        padding: 15px;
                        border-left: 4px solid rgb(36, 159, 187);
                        margin: 20px 0;
                        border-radius: 4px;
                    }
                    strong {
                        color: #555;
                    }
                    p {
                        margin: 10px 0;
                        line-height: 1.6;
                        color: #444;
                    }
                </style>
            </head>
            <body>
                <table class="container" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="content">
                            <h1>Nouveau message de ' . $formData['name'] . '</h1>
                            <div class="info-block">
                                <p><strong>Nom :</strong> ' . $formData['name'] . '</p>
                                <p><strong>Email :</strong> ' . $formData['email'] . '</p>
                                <p><strong>Sujet :</strong> ' . $formData['subject'] . '</p>
                            </div>
                            <h2 style="color: #666; font-size: 16px;">Message :</h2>
                            <p style="background: #f0f8ff; padding: 15px; border-radius: 4px;">' . nl2br($formData['message']) . '</p>
                            <hr style="border: none; border-top: 1px solid #eee; margin-top: 30px;">
                            <p style="font-size: 12px; color: #999; text-align: center;">Répondez directement à cet email pour contacter ' . $formData['name'] . '.</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ';
    
            $mail->Body = $message;
            
            if ($mail->send()) {
                flash_set('success', 'Votre message a été envoyé avec succès aux administrateurs. Merci de nous avoir contacté !');
                redirect('/contact.php');
            } else {
                error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
                $errors[] = 'Une erreur s\'est produite lors de l\'envoi du message. Veuillez réessayer.';
            }
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            $errors[] = 'Une erreur s\'est produite lors de l\'envoi du message.';
        }
    }
}
require dirname(__FILE__) . '/_header.php';
?>

<div class="contact-page">
    <h1>Nous contacter</h1>
    <p>Vous avez une question ou un commentaire ? Remplissez le formulaire ci-dessous et nous vous répondrons dès que possible.</p>

    <?php $flashes = flash_get_all(); foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($errors)): ?>
        <div class="flash error">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="contact-form" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="name" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Votre nom *</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                value="<?= e($formData['name']) ?>" 
                required 
                placeholder="Jean Dupont"
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
            >
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="email" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Votre email *</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?= e($formData['email']) ?>" 
                required 
                placeholder="jean@example.com"
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
            >
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="subject" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Sujet *</label>
            <input 
                type="text" 
                id="subject" 
                name="subject" 
                value="<?= e($formData['subject']) ?>" 
                required 
                placeholder="À quel sujet?"
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
            >
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="message" style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Message *</label>
            <textarea 
                id="message" 
                name="message" 
                rows="8" 
                required 
                placeholder="Écrivez votre message ici..."
                style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;"
            ><?= e($formData['message']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="background: #2196F3; color: white; padding: 1rem 2rem; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%;">Envoyer le message</button>
    </form>
</div>

<?php require dirname(__FILE__) . '/_footer.php'; ?>