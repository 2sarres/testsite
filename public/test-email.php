<?php
declare(strict_types=1);

require_once __DIR__ . '/src/email-config.php';
require_once __DIR__ . '/src/bootstrap.php';

// Afficher les erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Charger PHPMailer
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

echo "<h2>Test d'envoi d'email</h2>";
echo "<p>Configuration SMTP:</p>";
echo "<ul>";
echo "<li>Host: " . SMTP_HOST . "</li>";
echo "<li>Port: " . SMTP_PORT . "</li>";
echo "<li>User: " . SMTP_USER . "</li>";
echo "<li>From: " . SMTP_FROM . "</li>";
echo "</ul>";

try {
    $mail = new PHPMailer(true);
    
    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    
    // Sécurité SSL/TLS
    if (SMTP_PORT === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    
    // DEBUG
    $mail->SMTPDebug = 2;
    
    $mail->CharSet = 'UTF-8';
    
    // Contenu
    $mail->setFrom(SMTP_FROM, 'Test');
    $mail->addAddress(SMTP_USER);
    $mail->isHTML(true);
    $mail->Subject = 'Test d\'envoi';
    $mail->Body = '<h1>Test</h1><p>Cet email est un test</p>';
    
    // Envoyer
    $mail->send();
    echo "<div style='color: green; background: #dfd; padding: 10px; border: 1px solid green;'>";
    echo "<strong>✓ Email envoyé avec succès!</strong>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #fdd; padding: 10px; border: 1px solid red;'>";
    echo "<strong>✗ Erreur:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
