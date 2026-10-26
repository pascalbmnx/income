<?php
/**
 * Client login page.
 * Handles both authentication and logout.
 */
require_once dirname(__DIR__) . '/includes/db.php';

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['client']);
    header('Location: ' . baseUrl() . '/client/login.php');
    exit;
}

// Already authenticated
if (currentClient()) {
    header('Location: ' . baseUrl() . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM client_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $_SESSION['client'] = $user;
            header('Location: ' . baseUrl() . '/index.php');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

// Show a notice if the user was redirected here from a protected service.
$notice = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
if ($error === '' && $notice !== '') {
    $error = $notice;
}

$auth = [
    'pageTitle'      => 'Client Login | Luta Electrical',
    'eyebrow'        => 'Luta Electrical Services',
    'heading'        => 'Welcome back',
    'subheading'     => 'Sign in to access our online services.',
    'error'          => $error,
    'form_type'      => 'client',
    'heroTitle'      => 'Simple access to electrical services.',
    'heroSub'        => 'Submit electrical applications, drawing requests and installation reports from one secure portal.',
    'stat1'          => ['num' => '500+', 'label' => 'Installations Completed'],
    'stat2'          => ['num' => '15+',  'label' => 'Years of Experience', 'class' => 'tertiary'],
    'stat3'          => ['num' => '24/7', 'label' => 'Customer Support', 'class' => 'secondary'],
    'panelIcon'      => 'bolt',
    'panelTitle'     => 'Our Services',
    'panelSub'       => 'Everything you need, in one place',
    'panelItems'     => [
        ['icon' => 'electrical_services', 'label' => 'Electrical Application'],
        ['icon' => 'draw',                'label' => 'Drawing Request'],
        ['icon' => 'assignment',          'label' => 'Installation Report'],
    ],
    'stampIcon'      => 'location_on',
    'stamp'          => 'Morogoro Road, Mikumi, Tanzania',
    'helpIcon'       => 'call',
    'help'           => 'Need help signing in? Call +255 714 250 368',
    'buttonText'     => 'Sign In',
    'registerHref'   => baseUrl() . '/client/register.php',
    'registerLabel'  => 'Create one here',
];

require_once dirname(__DIR__) . '/includes/auth_shell.php';