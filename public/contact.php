<?php
declare(strict_types=1);
$pageTitle = 'Me contacter';
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

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['subject'] = trim($_POST['subject'] ?? '');
    $formData['message'] = trim($_POST['message'] ?? '');

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
        $adminEmail = ADMIN_EMAIL; // Utilise la configuration
        
        $emailSubject = "[Contact] " . e($formData['subject']);
        
        $emailBody = "
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2>Nouveau message de contact</h2>
            <p><strong>Nom:</strong> " . e($formData['name']) . "</p>
            <p><strong>Email:</strong> " . e($formData['email']) . "</p>
            <p><strong>Sujet:</strong> " . e($formData['subject']) . "</p>
            <hr>
            <h3>Message:</h3>
            <p>" . nl2br(e($formData['message'])) . "</p>
        </body>
        </html>";

        // Utilise la fonction SMTP secure
        if (send_email($adminEmail, $emailSubject, $emailBody, $formData['name'])) {
            flash_set('success', 'Votre message a été envoyé avec succès. Merci de nous avoir contacté!');
            redirect('/contact.php');
        } else {
            $errors[] = 'Une erreur s\'est produite lors de l\'envoi du message. Veuillez réessayer.';
        }
    }
}

require dirname(__FILE__) . '/_header.php';
?>

<div class="contact-page">
    <h1>Me contacter</h1>
    <p>Vous avez une question ou un commentaire? Remplissez le formulaire ci-dessous et je vous répondrai dès que possible.</p>

    <?php if (!empty($errors)): ?>
        <div class="flash error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="contact-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="name">Votre nom *</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                value="<?= e($formData['name']) ?>" 
                required 
                placeholder="Jean Dupont"
            >
        </div>

        <div class="form-group">
            <label for="email">Votre email *</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="<?= e($formData['email']) ?>" 
                required 
                placeholder="jean@example.com"
            >
        </div>

        <div class="form-group">
            <label for="subject">Sujet *</label>
            <input 
                type="text" 
                id="subject" 
                name="subject" 
                value="<?= e($formData['subject']) ?>" 
                required 
                placeholder="À quel sujet?"
            >
        </div>

        <div class="form-group">
            <label for="message">Message *</label>
            <textarea 
                id="message" 
                name="message" 
                rows="8" 
                required 
                placeholder="Écrivez votre message ici..."
            ><?= e($formData['message']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Envoyer le message</button>
    </form>
</div>

<?php require dirname(__FILE__) . '/_footer.php'; ?>
