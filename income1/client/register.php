<?php
/**
 * Client registration page.
 */
require_once dirname(__DIR__) . '/includes/db.php';

if (currentClient()) {
    header('Location: ' . baseUrl() . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($phone !== '' && strlen($phone) < 7) {
        $error = 'Please enter a valid phone number.';
    } else {
        $stmt = db()->prepare('SELECT id FROM client_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            db()->prepare('INSERT INTO client_users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)')
                ->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int) db()->lastInsertId();
            $_SESSION['client'] = ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone !== '' ? $phone : null];
            header('Location: ' . baseUrl() . '/index.php');
            exit;
        }
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>
      <div class="page-heading">
        <h2>Create Account</h2>
        <p>Register to access our online services.</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
      <?php endif; ?>

      <div class="form auth-form" style="max-width:520px;margin:0 auto;">
        <form method="post" action="register.php" novalidate>
          <div class="form-grid" style="grid-template-columns:1fr;">
            <div class="field">
              <label for="regName">Full Name *</label>
              <input type="text" id="regName" name="name" placeholder="Enter your full name" required autofocus>
            </div>
            <div class="field">
              <label for="regEmail">Email *</label>
              <input type="email" id="regEmail" name="email" placeholder="you@example.com" required>
            </div>
            <div class="field">
              <label for="regPhone">Phone Number</label>
              <input type="tel" id="regPhone" name="phone" placeholder="e.g. 09xxxxxxxx">
            </div>
            <div class="field">
              <label for="regPassword">Password *</label>
              <input type="password" id="regPassword" name="password" placeholder="Min 6 characters" required>
            </div>
            <div class="field">
              <label for="regConfirm">Confirm Password *</label>
              <input type="password" id="regConfirm" name="confirm" placeholder="Repeat password" required>
            </div>
          </div>
          <div class="form-actions" style="justify-content:center;">
            <button type="submit" class="btn btn-primary">Create Account</button>
          </div>
        </form>
        <p class="auth-link" style="text-align:center;margin-top:16px;">
          Already have an account? <a href="login.php">Sign in here</a>
        </p>
      </div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>