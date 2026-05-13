<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_admin();
db_install($pdo);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier']);
    exit;
}

$file = $_FILES['file'];
$err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($err !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => upload_error_message($err)]);
    exit;
}

$name = validate_uploaded_image($file);
if ($name === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Image invalide. Formats acceptes: JPG, PNG, GIF, WEBP. Taille max: 10 Mo.']);
    exit;
}

$dest = dirname(__DIR__, 2) . '/public/uploads/' . $name;
if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => "Erreur d'enregistrement"]);
    exit;
}

echo json_encode(['location' => '/uploads/' . $name], JSON_UNESCAPED_SLASHES);

