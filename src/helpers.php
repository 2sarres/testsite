<?php
declare(strict_types=1);

function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
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

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

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

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get_all(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($all) ? $all : [];
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('/login.php');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Accès refusé.";
        exit;
    }
    return $u;
}

function password_hash_secure(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function password_verify_secure(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function normalize_slug(string $title): string
{
    $s = trim(mb_strtolower($title, 'UTF-8'));
    $s = preg_replace('~[^\pL\pN]+~u', '-', $s) ?? '';
    $s = trim($s, '-');
    if ($s === '') {
        $s = bin2hex(random_bytes(6));
    }
    return $s;
}

function db_install(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'editor',
            created_at TEXT NOT NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT NOT NULL DEFAULT '',
            content_html TEXT NOT NULL,
            cover_image TEXT DEFAULT NULL,
            author_id INTEGER NOT NULL,
            published INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
        )"
    );

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_published_created ON articles(published, created_at DESC)");

    articles_migrate_schema($pdo);
}

/**
 * Migrations : category_slug (legacy), table categories, category_id, sort_order sur articles.
 */
function articles_migrate_schema(PDO $pdo): void
{
    $articleCols = $pdo->query("PRAGMA table_info(articles)")->fetchAll(PDO::FETCH_ASSOC);
    $articleColNames = array_column($articleCols, 'name');

    if (!in_array('category_slug', $articleColNames, true)) {
        $pdo->exec("ALTER TABLE articles ADD COLUMN category_slug TEXT NOT NULL DEFAULT 'destinations-europeennes'");
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            label TEXT NOT NULL,
            dek TEXT NOT NULL DEFAULT '',
            accent_img TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        )"
    );

    $catCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount === 0) {
        categories_seed_defaults($pdo);
    }

    $articleCols = $pdo->query("PRAGMA table_info(articles)")->fetchAll(PDO::FETCH_ASSOC);
    $articleColNames = array_column($articleCols, 'name');

    if (!in_array('category_id', $articleColNames, true)) {
        $pdo->exec('ALTER TABLE articles ADD COLUMN category_id INTEGER');
        $pdo->exec(
            'UPDATE articles SET category_id = (
                SELECT c.id FROM categories c WHERE c.slug = articles.category_slug LIMIT 1
            ) WHERE EXISTS (
                SELECT 1 FROM categories c WHERE c.slug = articles.category_slug
            )'
        );
        $firstId = (int)$pdo->query('SELECT id FROM categories ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn();
        if ($firstId > 0) {
            $pdo->exec('UPDATE articles SET category_id = ' . $firstId . ' WHERE category_id IS NULL');
        }
    }

    $articleCols = $pdo->query('PRAGMA table_info(articles)')->fetchAll(PDO::FETCH_ASSOC);
    $articleColNames = array_column($articleCols, 'name');
    $addedSort = false;
    if (!in_array('sort_order', $articleColNames, true)) {
        $pdo->exec('ALTER TABLE articles ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0');
        $addedSort = true;
    }
    if ($addedSort) {
        $pdo->exec('UPDATE articles SET sort_order = id * 10 WHERE sort_order = 0');
    }

    $pdo->exec(
        'UPDATE articles SET category_slug = (
            SELECT c.slug FROM categories c WHERE c.id = articles.category_id LIMIT 1
        ) WHERE category_id IS NOT NULL AND EXISTS (
            SELECT 1 FROM categories c WHERE c.id = articles.category_id
        )'
    );
}

function categories_seed_defaults(PDO $pdo): void
{
    $defs = article_category_definitions();
    $ord = 10;
    $ins = $pdo->prepare(
        'INSERT INTO categories (slug, label, dek, accent_img, sort_order, created_at)
         VALUES (:slug, :label, :dek, :accent_img, :sort_order, :created_at)'
    );
    foreach ($defs as $slug => $meta) {
        $ins->execute([
            ':slug' => $slug,
            ':label' => (string)$meta['label'],
            ':dek' => (string)$meta['dek'],
            ':accent_img' => (string)$meta['accent_img'],
            ':sort_order' => $ord,
            ':created_at' => date('c'),
        ]);
        $ord += 10;
    }
}

/**
 * Données initiales uniquement (seed si la table categories est vide).
 */
function article_category_definitions(): array
{
    return [
        'destinations-europeennes' => [
            'label' => 'Destinations européennes',
            'dek' => 'Capitales business, hubs FBO et escapades côtières.',
            'accent_img' => 'https://images.unsplash.com/photo-1464037866556-7112c0a20452?w=520&q=80&auto=format&fit=crop',
        ],
        'vols-long-courriers' => [
            'label' => 'Vols long-courriers',
            'dek' => 'Transatlantiques, confort cabine et routes signature.',
            'accent_img' => 'https://images.unsplash.com/photo-1436491865332-7a61a109c05b?w=520&q=80&auto=format&fit=crop',
        ],
        'terminaux-prives-fbo' => [
            'label' => 'Évaluation des terminaux privés',
            'dek' => 'Salons, accès piste, conciergerie et fluidité au sol.',
            'accent_img' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=520&q=80&auto=format&fit=crop',
        ],
        'interieurs-cabine' => [
            'label' => 'Intérieurs de cabine',
            'dek' => 'Finitions, espace, restauration et ambiance à bord.',
            'accent_img' => 'https://images.unsplash.com/photo-1540965900371-11be79a4bc30?w=520&q=80&auto=format&fit=crop',
        ],
        'vues-aeriennes' => [
            'label' => 'Vues aériennes & approches',
            'dek' => 'Panoramas, approches remarquables et corridors aériens.',
            'accent_img' => 'https://images.unsplash.com/photo-1474302771287-df387ea836e2?w=520&q=80&auto=format&fit=crop',
        ],
    ];
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
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
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

/** Attributs img pour hébergeurs externes (évite blocages liés au Referer). */
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
    if ($e !== '') {
        return $e;
    }
    $t = trim(strip_tags($contentHtml));
    if ($t === '') {
        return '';
    }
    if (mb_strlen($t, 'UTF-8') > 168) {
        return mb_substr($t, 0, 165, 'UTF-8') . '…';
    }
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

        if (!$stmt->fetch()) {
            return $slug;
        }

        $slug = $base . '-' . $i;
        $i++;
    }
}

function validate_uploaded_image(array $file): ?string
{
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 10 * 1024 * 1024) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return null;
    }

    $ext = $allowed[$mime];
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    return $name;
}

function upload_error_message(int $errorCode): string
{
    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        return "Image trop volumineuse (max 10 Mo).";
    }
    if ($errorCode === UPLOAD_ERR_PARTIAL) {
        return "Upload interrompu, reessaie.";
    }
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return "Aucun fichier envoye.";
    }
    if ($errorCode === UPLOAD_ERR_NO_TMP_DIR || $errorCode === UPLOAD_ERR_CANT_WRITE || $errorCode === UPLOAD_ERR_EXTENSION) {
        return "Erreur serveur pendant l'upload.";
    }
    return "Image invalide. Formats acceptes: JPG, PNG, GIF, WEBP.";
}

