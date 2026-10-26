<?php
/**
 * Database connection and shared application helpers.
 * Database: electrical_services (MariaDB / MySQL)
 */

declare(strict_types=1);
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'electrical_services');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('UPLOAD_URL', 'uploads');

const ALLOWED_STATUS = [
    'application' => ['new', 'pending', 'approved', 'rejected'],
    'drawing'     => ['new', 'pending', 'approved', 'rejected'],
    'report'      => ['new', 'pending', 'approved', 'rejected'],
    'message'     => ['unread', 'read', 'archived'],
];

const ADMIN_ROLES = ['admin', 'editor', 'viewer'];

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}

/** Site root URL helper (e.g. /income1) regardless of current page depth. */
function baseUrl(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $rootFs     = str_replace('\\', '/', dirname(__DIR__)); // <site>/includes -> <site>

    // Relative URL portion of the current script from the site root.
    $rel = ltrim(substr($scriptFile, strlen($rootFs)), '/');
    if ($rel !== '' && substr($scriptName, -strlen($rel)) === $rel) {
        $scriptName = substr($scriptName, 0, -strlen($rel));
    }
    $scriptName = rtrim($scriptName, '/');

    $cached = "{$scheme}://{$host}{$scriptName}";
    return $cached;
}

/** Escape HTML safely for output. */
function e($value, bool $double = true): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', $double);
}

/** Ensure the admin login auth is present. */
function requireAuth(): void
{
    if (empty($_SESSION['admin_user'])) {
        header('Location: ' . baseUrl() . '/admin/login.php');
        exit;
    }
}

/** Return the currently logged-in admin user (or null). */
function currentUser(): ?array
{
    return $_SESSION['admin_user'] ?? null;
}

/** Is the current user allowed a given role level (admin=3, editor=2, viewer=1)? */
function hasRole(string $minimumRole): bool
{
    $user = currentUser();
    if (!$user) return false;
    $level = ['admin' => 3, 'editor' => 2, 'viewer' => 1];
    $min = $level[$minimumRole] ?? 1;
    $cur = $level[$user['role'] ?? 'viewer'] ?? 1;
    return $cur >= $min;
}

/** Write an upload's base64 data to disk and return its metadata. */
function storeUpload(array $data): ?array
{
    if (empty($data['imageData'])) {
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $raw = $data['imageData'];
    $decoded = base64_decode((string) preg_replace('#^data:[^,]+;base64,#i', '', $raw), false);
    if ($decoded === false || $decoded === '') {
        return null;
    }

    if (!preg_match('#^data:([^;,]+);base64,#i', $raw, $m)) {
        return null;
    }
    $mime = strtolower($m['1']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
        return null;
    }

    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime];
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $path = UPLOAD_DIR . '/' . $filename;

    if (file_put_contents($path, $decoded) === false) {
        return null;
    }

    $size = filesize($path);
    $name = $data['imageName'] ?? $filename;

    return [
        'image_path' => UPLOAD_URL . '/' . $filename,
        'image_name' => mb_substr($name, 0, 255),
        'image_type' => $mime,
        'image_size' => $size,
    ];
}

/** Store a multipart-uploaded file from $_FILES and return its metadata. */
function storeFileUpload(array $file): ?array
{
    if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $mime = strtolower((string) ($file['type'] ?? ''));
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $path = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        return null;
    }

    return [
        'image_path' => UPLOAD_URL . '/' . $filename,
        'image_name' => mb_substr(basename((string) ($file['name'] ?? '')), 0, 255),
        'image_type' => $mime,
        'image_size' => (int) ($file['size'] ?? 0),
    ];
}

/** Validate that required fields are present and non-empty. Returns error message or null. */
function requireFields(array $data, array $fields): ?string
{
    foreach ($fields as $f) {
        if (!isset($data[$f]) || trim((string) $data[$f]) === '') {
            return "Missing required field: {$f}";
        }
    }
    return null;
}

/** Normalize a status value against an allowed list, defaulting to the first. */
function normalizeStatus($value, string $resource): string
{
    $allowed = ALLOWED_STATUS[$resource] ?? ['new'];
    $value = strtolower(trim((string) $value));
    return in_array($value, $allowed, true) ? $value : $allowed[0];
}

/** Build an absolute URL for an uploaded file. */
function uploadUrl(string $url): string
{
    return baseUrl() . '/' . ltrim($url, '/');
}

/** Ensure a client (public site) login is present. */
function requireClientAuth(): void
{
    if (empty($_SESSION['client'])) {
        header('Location: ' . baseUrl() . '/client/login.php');
        exit;
    }
}

/** Return the currently logged-in client (or null). */
function currentClient(): ?array
{
    return $_SESSION['client'] ?? null;
}
