<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
    if (empty($_SESSION['__regen'])) {
        session_regenerate_id(true);
        $_SESSION['__regen'] = time();
    } elseif (time() - (int)$_SESSION['__regen'] > 300) {
        session_regenerate_id(true);
        $_SESSION['__regen'] = time();
    }
}

function db_connect(string $dbPath): PDO
{
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
}

function csrf_verify_or_fail(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $token)) {
        http_response_code(403);
        echo "CSRF invalide.";
        exit;
    }
}

function redirect(string $path): void { header('Location: ' . $path); exit; }
function flash_set(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function flash_get_all(): array { $all = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return is_array($all) ? $all : []; }
function current_user(): ?array { return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null; }

function require_login(): array
{
    $u = current_user();
    if (!$u) redirect('/login.php');
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403); echo "Accès refusé."; exit;
    }
    return $u;
}

function password_hash_secure(string $password): string { return password_hash($password, PASSWORD_DEFAULT); }
function password_verify_secure(string $password, string $hash): bool { return password_verify($password, $hash); }

function normalize_slug(string $title): string
{
    $s = trim(mb_strtolower($title, 'UTF-8'));
    $s = preg_replace('~[^\pL\pN]+~u', '-', $s) ?? '';
    $s = trim($s, '-');
    if ($s === '') $s = bin2hex(random_bytes(6));
    return $s;
}

function db_install(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'editor', created_at TEXT NOT NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, slug TEXT NOT NULL UNIQUE,
            excerpt TEXT NOT NULL DEFAULT '', content_html TEXT NOT NULL,
            cover_image TEXT DEFAULT NULL, author_id INTEGER NOT NULL,
            published INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0,
            category_id INTEGER, category_slug TEXT,
            FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
        )"
    );

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_published_created ON articles(published, created_at DESC)");

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE, label TEXT NOT NULL,
            dek TEXT NOT NULL DEFAULT '', accent_img TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS settings (
            key_name TEXT PRIMARY KEY, value TEXT NOT NULL
        )"
    );

    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() === 0) {
        categories_seed_defaults($pdo);
    }

    // --- NETTOYAGE UNSPLASH ---
    $pdo->exec("UPDATE categories SET accent_img = 'https://picsum.photos/800/600?random=' || id WHERE accent_img LIKE '%unsplash%'");
    $pdo->exec("UPDATE articles SET cover_image = 'https://picsum.photos/800/600?random=' || id WHERE cover_image LIKE '%unsplash%'");
}

function categories_seed_defaults(PDO $pdo): void
{
    $defs = [
        'destinations-europeennes' => [
            'label' => 'Destinations européennes',
            'dek' => 'Capitales business, hubs FBO et escapades côtières.',
            'accent_img' => 'https://picsum.photos/800/600?random=1',
        ],
        'vols-long-courriers' => [
            'label' => 'Vols long-courriers',
            'dek' => 'Transatlantiques, confort cabine et routes signature.',
            'accent_img' => 'https://picsum.photos/800/600?random=2',
        ],
        'terminaux-prives-fbo' => [
            'label' => 'Évaluation des terminaux privés',
            'dek' => 'Salons, accès piste, conciergerie et fluidité au sol.',
            'accent_img' => 'https://picsum.photos/800/600?random=3',
        ],
    ];
    $ord = 10;
    $ins = $pdo->prepare('INSERT INTO categories (slug, label, dek, accent_img, sort_order, created_at) VALUES (:slug, :label, :dek, :accent_img, :sort_order, :created_at)');
    foreach ($defs as $slug => $meta) {
        $ins->execute([':slug' => $slug, ':label' => $meta['label'], ':dek' => $meta['dek'], ':accent_img' => $meta['accent_img'], ':sort_order' => $ord, ':created_at' => date('c')]);
        $ord += 10;
    }
}

function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = :k");
    $stmt->execute([':k' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (string)$val : $default;
}

function set_setting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (:k, :v) ON CONFLICT(key_name) DO UPDATE SET value=excluded.value")
        ->execute([':k' => $key, ':v' => $value]);
}

function categories_all_ordered(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function category_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :s LIMIT 1');
    $stmt->execute([':s' => trim($slug)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function category_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function category_slug_for_id(PDO $pdo, int $categoryId): string
{
    $stmt = $pdo->prepare('SELECT slug FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $categoryId]);
    $s = $stmt->fetchColumn();
    return $s ? (string)$s : '';
}

function make_unique_category_slug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $base = normalize_slug($title);
    $slug = $base;
    $i = 2;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug AND id != :id LIMIT 1');
            $stmt->execute([':slug' => $slug, ':id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
            $stmt->execute([':slug' => $slug]);
        }
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

// === LA FONCTION MANQUANTE EST ICI ===
function next_article_sort_order(PDO $pdo, int $categoryId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM articles WHERE category_id = :cid');
    $stmt->execute([':cid' => $categoryId]);
    $max = (int)$stmt->fetchColumn();
    return $max + 10;
}

// === L'AUTRE FONCTION MANQUANTE EST ICI ===
function articles_count_in_category(PDO $pdo, int $categoryId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = :cid');
    $stmt->execute([':cid' => $categoryId]);
    return (int)$stmt->fetchColumn();
}

function img_external_attrs(string $src): string
{
    if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0) {
        return ' referrerpolicy="no-referrer" ';
    }
    return '';
}

function card_excerpt_preview(string $excerpt, string $contentHtml): string
{
    $e = trim($excerpt);
    if ($e !== '') return $e;
    $t = trim(strip_tags($contentHtml));
    if ($t === '') return '';
    if (mb_strlen($t, 'UTF-8') > 168) return mb_substr($t, 0, 165, 'UTF-8') . '…';
    return $t;
}

function has_any_user(PDO $pdo): bool
{
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users");
    $row = $stmt->fetch();
    return ((int)($row['c'] ?? 0)) > 0;
}

function create_user(PDO $pdo, string $email, string $password, string $role = 'editor'): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO users(email, password_hash, role, created_at)
         VALUES(:email, :password_hash, :role, :created_at)"
    );
    $stmt->execute([
        ':email' => mb_strtolower(trim($email), 'UTF-8'),
        ':password_hash' => password_hash_secure($password),
        ':role' => $role,
        ':created_at' => date('c'),
    ]);
    return (int)$pdo->lastInsertId();
}

function get_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => mb_strtolower(trim($email), 'UTF-8')]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function make_unique_slug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $base = normalize_slug($title);
    $slug = $base;
    $i = 2;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE slug = :slug AND id != :id LIMIT 1");
            $stmt->execute([':slug' => $slug, ':id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE slug = :slug LIMIT 1");
            $stmt->execute([':slug' => $slug]);
        }
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

function validate_uploaded_image(array $file): ?string
{
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) return null;
    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) return null;
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 10 * 1024 * 1024) return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg',
        'image/png' => 'png', 'image/x-png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) return null;

    $ext = $allowed[$mime];
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    return $name;
}

/**
 * Envoie un email via PHPMailer (SMTP sécurisé)
 * @return bool true si succès, false sinon
 */
function send_email(string $to, string $subject, string $htmlBody, string $fromName = ''): bool
{
    require_once __DIR__ . '/email-config.php';
    
    // Charger PHPMailer
    require_once __DIR__ . '/../phpmailer/src/Exception.php';
    require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/src/SMTP.php';
    
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
        
        $mail->CharSet = 'UTF-8';
        
        // Contenu
        $mail->setFrom(SMTP_FROM, $fromName ?: 'Contact Site');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        
        // Envoyer
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

function upload_error_message(int $errorCode): string
{
    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) return "Image trop volumineuse (max 10 Mo).";
    if ($errorCode === UPLOAD_ERR_PARTIAL) return "Upload interrompu, reessaie.";
    if ($errorCode === UPLOAD_ERR_NO_FILE) return "Aucun fichier envoye.";
    if ($errorCode === UPLOAD_ERR_NO_TMP_DIR || $errorCode === UPLOAD_ERR_CANT_WRITE || $errorCode === UPLOAD_ERR_EXTENSION) return "Erreur serveur pendant l'upload.";
    return "Image invalide. Formats acceptes: JPG, PNG, GIF, WEBP.";
}

function process_base64_upload(string $base64Data): ?string
{
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $data = substr($base64Data, strpos($base64Data, ',') + 1);
        $type = strtolower($type[1]);
        $data = base64_decode($data);
        if ($data !== false) {
            $ext = $type === 'jpeg' ? 'jpg' : $type;
            $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_crop.' . $ext;
            $target = dirname(__DIR__) . '/public/uploads/' . $filename;
            file_put_contents($target, $data);
            return '/uploads/' . $filename;
        }
    }
    return null;
}
