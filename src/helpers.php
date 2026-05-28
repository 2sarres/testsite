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
    // Les éditeurs et les admins peuvent accéder à l'interface globale.
    // Les restrictions spécifiques (ex: gestion utilisateurs) se font sur les pages concernées.
    if (!in_array($u['role'] ?? '', ['admin', 'editor'])) {
        http_response_code(403); echo "Accès refusé. Rôle non autorisé."; exit;
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

    // MIGRATION SÉCURISÉE : Ajout des colonnes de récupération de mot de passe et profil
    try { $pdo->query("SELECT first_name FROM users LIMIT 1"); } catch (\Throwable $e) {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN first_name TEXT DEFAULT ''"); } catch (\Throwable $ex) {}
    }
    try { $pdo->query("SELECT last_name FROM users LIMIT 1"); } catch (\Throwable $e) {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN last_name TEXT DEFAULT ''"); } catch (\Throwable $ex) {}
    }
    try { $pdo->query("SELECT is_active FROM users LIMIT 1"); } catch (\Throwable $e) {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1"); } catch (\Throwable $ex) {}
    }
    try { $pdo->query("SELECT reset_token FROM users LIMIT 1"); } catch (\Throwable $e) {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN reset_token TEXT DEFAULT NULL"); } catch (\Throwable $ex) {}
    }
    try { $pdo->query("SELECT reset_expires_at FROM users LIMIT 1"); } catch (\Throwable $e) {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires_at TEXT DEFAULT NULL"); } catch (\Throwable $ex) {}
    }

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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admin_invites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token TEXT NOT NULL UNIQUE,
            created_by INTEGER NOT NULL,
            role TEXT NOT NULL DEFAULT 'admin',
            created_at TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            used INTEGER NOT NULL DEFAULT 0,
            used_at TEXT,
            used_by_email TEXT,
            FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
        )"
    );

    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() === 0) {
        categories_seed_defaults($pdo);
    }
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

function next_article_sort_order(PDO $pdo, int $categoryId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM articles WHERE category_id = :cid');
    $stmt->execute([':cid' => $categoryId]);
    $max = (int)$stmt->fetchColumn();
    return $max + 10;
}

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

function get_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => mb_strtolower(trim($email), 'UTF-8')]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_all_users(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

function delete_user(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    return $stmt->execute([':id' => $userId]);
}

// ==== GESTION DES MOTS DE PASSE =====

function generate_password_reset(PDO $pdo, string $email): ?string
{
    $user = get_user_by_email($pdo, $email);
    if (!$user) return null;
    
    $token = bin2hex(random_bytes(32));
    $expires = date('c', time() + 3600); // Expire dans 1 heure
    
    $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE id = :id");
    $stmt->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);
    
    return $token;
}

function verify_password_reset_token(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_expires_at > datetime('now') LIMIT 1");
    $stmt->execute([':token' => $token]);
    return $stmt->fetch() ?: null;
}

function update_password_with_token(PDO $pdo, string $token, string $newPassword): bool
{
    $user = verify_password_reset_token($pdo, $token);
    if (!$user) return false;
    
    $hash = password_hash_secure($newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, reset_token = NULL, reset_expires_at = NULL WHERE id = :id");
    return $stmt->execute([':hash' => $hash, ':id' => $user['id']]);
}

// ==== GESTION DES EMAILS ADMIN =====
function get_all_admin_emails(PDO $pdo): array
{
    // On ne récupère que les emails des administrateurs actifs
    $stmt = $pdo->query("SELECT email FROM users WHERE role = 'admin' AND COALESCE(is_active, 1) = 1");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
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
?>