<?php
/**
 * Admin login page.
 * Handles both authentication and logout.
 */
require_once dirname(__DIR__) . '/includes/db.php';

// Logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . baseUrl() . '/admin/login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            session_regenerate_id(true);
            $_SESSION['admin_user'] = $user;
            header('Location: ' . baseUrl() . '/admin/index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}

$pdo = db();
$count = function (string $table, string $statusCol = 'status', array $statuses = []) use ($pdo): int {
    $where = $statuses ? " WHERE $statusCol IN (" . implode(',', array_fill(0, count($statuses), '?')) . ')' : '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`$where");
    $stmt->execute($statuses);
    return (int) $stmt->fetchColumn();
};

$appCount = $count('applications');
$drawCount = $count('drawings');
$repCount = $count('reports');
$action = $count('applications', 'status', ['new', 'pending'])
    + $count('drawings', 'status', ['new', 'pending'])
    + $count('reports', 'status', ['new', 'pending'])
    + $count('messages', 'status', ['unread']);

$auth = [
    'pageTitle'      => 'Admin Login | Luta Electrical',
    'eyebrow'        => 'Admin Operations',
    'heading'        => 'Welcome back',
    'subheading'     => 'Sign in to manage client requests.',
    'error'          => $error,
    'form_type'      => 'admin',
    'heroTitle'      => 'The control room for client services.',
    'heroSub'        => 'Manage electrical applications, drawing requests and installation reports from one dashboard.',
    'stat1'          => ['num' => $appCount, 'label' => 'Applications'],
    'stat2'          => ['num' => $drawCount, 'label' => 'Drawings', 'class' => 'tertiary'],
    'stat3'          => ['num' => $repCount, 'label' => 'Reports', 'class' => 'secondary'],
    'panelIcon'      => 'mark_email_unread',
    'panelTitle'     => 'Client Inbox',
    'panelSub'       => $action . ' request' . ($action === 1 ? '' : 's') . ' need' . ($action === 1 ? 's' : '') . ' attention',
    'stampIcon'      => 'admin_panel_settings',
    'stamp'          => 'Restricted area - authorized staff only',
    'helpIcon'       => 'verified_user',
    'help'           => 'Protected area: only registered staff may sign in',
    'buttonText'     => 'Sign in to Admin Panel',
    'backHref'       => baseUrl() . '/index.php',
];

require_once dirname(__DIR__) . '/includes/auth_shell.php';