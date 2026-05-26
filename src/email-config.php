<?php
declare(strict_types=1);

/**
 * Configuration des emails SMTP
 * Pour utiliser Gmail ou un serveur SMTP externe
 */

// ============================================
// CONFIGURATION SMTP - À PERSONNALISER
// ============================================

// Option 1: Gmail
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'votre-email@gmail.com');
// define('SMTP_PASS', 'votre-mot-de-passe-application'); // Voir note ci-dessous
// define('SMTP_FROM', 'votre-email@gmail.com');

// Option 2: Outlook
// define('SMTP_HOST', 'smtp.office365.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'votre-email@outlook.com');
// define('SMTP_PASS', 'votre-mot-de-passe');
// define('SMTP_FROM', 'votre-email@outlook.com');

// Option 3: Configuration personnalisée (demander à votre hébergeur)
// define('SMTP_HOST', 'smtp.votre-hebergeur.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'votre-utilisateur');
// define('SMTP_PASS', 'votre-mot-de-passe');
// define('SMTP_FROM', 'noreply@votre-domaine.com');

// Configuration par défaut (OVH - à adapter)
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USER', 'site.aeroclub.buhl@gmail.com');
    define('SMTP_PASS', 'rgjqospeloqywsol'); // À modifier
    define('SMTP_FROM', 'site.aeroclub.buhl@gmail.com');
}

/**
 * IMPORTANT pour Gmail:
 * 1. Activez la "Validation en 2 étapes" sur votre compte Gmail
 * 2. Générez un "Mot de passe d'application" ici: https://myaccount.google.com/apppasswords
 * 3. Utilisez ce mot de passe dans SMTP_PASS (pas votre mot de passe Gmail habituel)
 * 4. Remplacez 'votre_mot_de_passe_application' ci-dessus par le mot de passe généré
 * 
 * IMPORTANT pour Outlook/Office365:
 * 1. Utilisez votre email et mot de passe Outlook habituels
 * 2. Assurez-vous que les "Applis moins sécurisées" sont autorisées si nécessaire
 */
