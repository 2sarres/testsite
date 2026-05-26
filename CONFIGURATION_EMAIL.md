# Configuration du formulaire de contact - SMTP

## 🔧 Configuration requise

Votre formulaire de contact utilise maintenant SMTP pour envoyer les emails de manière fiable.

### 1. Ouvrir le fichier de configuration

Ouvrez: `src/email-config.php`

### 2. Choisir un service d'email

#### 🔹 Option A: Gmail (recommandé pour les tests)

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'votre-mot-de-passe-application');
define('SMTP_FROM', 'votre-email@gmail.com');
```

**Étapes pour Gmail:**
1. Allez sur: https://myaccount.google.com/apppasswords
2. Sélectionnez "Mail" et "Windows" (ou votre système)
3. Générez un mot de passe d'application (16 caractères)
4. Copiez ce mot de passe dans `SMTP_PASS`

⚠️ **Important:** 
- Vous devez avoir la validation en 2 étapes activée sur votre compte Gmail
- Utilisez le mot de passe d'application, PAS votre mot de passe habituel

#### 🔹 Option B: Outlook / Office365

```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@outlook.com');
define('SMTP_PASS', 'votre-mot-de-passe');
define('SMTP_FROM', 'votre-email@outlook.com');
```

#### 🔹 Option C: Autre hébergeur

Contactez votre hébergeur pour obtenir:
- Adresse SMTP (ex: smtp.votre-hebergeur.com)
- Port SMTP (généralement 25, 587 ou 465)
- Utilisateur et mot de passe
- Adresse de départ

Puis configurez `email-config.php` avec ces paramètres.

### 3. Vérifier la configuration du site

Ouvrez: `src/config.php`

Vérifiez que `ADMIN_EMAIL` est configurée correctement:
```php
define('ADMIN_EMAIL', 'votre-email@example.com');
```

### 4. Tester le formulaire

1. Rendez-vous sur: http://localhost/contact.php
2. Remplissez le formulaire
3. Cliquez sur "Envoyer le message"
4. Vérifiez que vous avez reçu l'email

## 🐛 Dépannage

### "Une erreur s'est produite lors de l'envoi du message"

1. **Vérifiez les logs PHP:**
   - Cherchez les messages d'erreur dans les logs PHP (généralement dans `c:\wamp64\logs`)
   - Ou testez localement avec: `error_log("test");`

2. **Vérifiez les identifiants SMTP:**
   - Confirmez que l'adresse et le mot de passe sont corrects
   - Assurez-vous qu'il n'y a pas d'espaces

3. **Vérifiez le port:**
   - Port 25: généralement bloqué (ne pas utiliser)
   - Port 587: TLS (recommandé)
   - Port 465: SSL

4. **Pour Gmail:**
   - Vérifiez que vous avez généré un mot de passe d'application (pas le mot de passe habituel)
   - Vérifiez que la validation 2FA est activée

5. **En cas de problème persistant:**
   - Regardez dans `c:\wamp64\logs\error.log`
   - Ou testez en local avec Mailpit (voir ci-dessous)

## 🧪 Test en local sans internet

### Utiliser Mailpit

1. Téléchargez Mailpit: https://github.com/axllent/mailpit/releases
2. Lancez-le
3. Configurez dans `email-config.php`:
   ```php
   define('SMTP_HOST', 'localhost');
   define('SMTP_PORT', 1025);
   define('SMTP_USER', '');
   define('SMTP_PASS', '');
   define('SMTP_FROM', 'noreply@localhost');
   ```
4. Ouvrez http://localhost:8025 pour voir les emails capturés

## 📧 Format des emails reçus

Chaque email de contact contient:
- **De:** Le nom du visiteur
- **Répondre à:** L'email du visiteur
- **Sujet:** `[Contact] Sujet du message`
- **Corps:** Message formaté en HTML

## 🚀 Améliorations possibles

- [ ] Ajouter un captcha pour éviter les spams
- [ ] Sauvegarder les messages en base de données
- [ ] Envoyer une confirmation d'email au visiteur
- [ ] Ajouter des pièces jointes
- [ ] Envoyer à plusieurs adresses

## ✅ Checklist de configuration

- [ ] J'ai configuré `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`
- [ ] J'ai configuré `ADMIN_EMAIL` dans `config.php`
- [ ] J'ai testé le formulaire et reçu un email
- [ ] L'email contient le message correctement formaté

