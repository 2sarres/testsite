<?php
declare(strict_types=1);

/**
 * Configuration de l'application
 */

// Email administrateur (à modifier avec votre adresse email)
define('ADMIN_EMAIL', 'site.aeroclub.buhl@gmail.com');

// Optionnel: nom du site
define('SITE_NAME', 'Sky Atlas');

/**
 * Configuration SMTP pour PHPMailer
 * Utilise les identifiants de votre adresse Gmail
 */
define('SMTP_USER', 'site.aeroclub.buhl@gmail.com');
define('SMTP_PASS', 'rgjqospeloqywsol'); // Mot de passe d'application Gmail
define('SMTP_FROM', 'site.aeroclub.buhl@gmail.com');

/**
 * IMPORTANT pour Gmail:
 * 1. Activez la "Validation en 2 étapes" sur votre compte Gmail
 * 2. Générez un "Mot de passe d'application" ici: https://myaccount.google.com/apppasswords
 * 3. Utilisez ce mot de passe dans SMTP_PASS
 * 4. Remplacez les adresses email par les vôtres si différentes
 */

