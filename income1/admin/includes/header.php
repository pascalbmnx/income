<?php
/**
 * Admin header partial.
 * Expects: $adminTitle (string), $adminActive (string)
 */
if (!isset($adminTitle)) $adminTitle = 'Admin Panel';
if (!isset($adminActive)) $adminActive = '';
$u = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($adminTitle); ?></title>
  <link rel="stylesheet" href="<?php echo baseUrl(); ?>/admin/assets/css/admin.css">
</head>
<body>
  <header class="header">
    <div class="header-inner container">
      <div class="brand">
        <img src="<?php echo baseUrl(); ?>/assets/images/luta.png" alt="Company Logo" class="logo">
        <div class="brand-text">
          <h1>Admin Panel</h1>
          <p>Client Requests Management</p>
        </div>
      </div>
      <nav class="nav">
        <a href="https://nikonekt.tanesco.co.tz/application" target="_blank" rel="noopener" class="btn btn-accent btn-small">+ New Application</a>
        <a href="<?php echo baseUrl(); ?>/admin/index.php" class="nav-link<?php echo $adminActive === 'dashboard' ? ' active' : ''; ?>">Dashboard</a>
        <?php if (hasRole('viewer')): ?>
        <a href="<?php echo baseUrl(); ?>/admin/users.php" class="nav-link<?php echo $adminActive === 'users' ? ' active' : ''; ?>">Users</a>
        <?php endif; ?>
        <a href="<?php echo baseUrl(); ?>/admin/login.php?logout=1" class="nav-link">Logout</a>
      </nav>
    </div>
  </header>
  <main class="container">
