<?php
/**
 * Public site header partial.
 * Expects: $pageTitle (string), $activeNav (string)
 */
if (!isset($pageTitle)) $pageTitle = 'Luta Electrical Services';
if (!isset($activeNav)) $activeNav = '';
$client = currentClient();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($pageTitle); ?></title>
  <link rel="stylesheet" href="<?php echo baseUrl(); ?>/assets/css/style.css">
</head>
<body>
  <header class="header">
    <div class="header-inner container">
      <div class="brand">
        <img src="<?php echo baseUrl(); ?>/assets/images/luta.png" alt="Company Logo" class="logo">
        <div class="brand-text">
          <h1>Luta Electrical</h1>
          <p>Electrical &amp; Installation Services</p>
        </div>
      </div>
      <nav class="nav">
        <a href="<?php echo baseUrl(); ?>/index.php" class="nav-link<?php echo $activeNav === 'home' ? ' active' : ''; ?>">Home</a>
        <a href="<?php echo baseUrl(); ?>/about.php" class="nav-link<?php echo $activeNav === 'about' ? ' active' : ''; ?>">About</a>
        <a href="<?php echo baseUrl(); ?>/contact.php" class="nav-link<?php echo $activeNav === 'contact' ? ' active' : ''; ?>">Contact</a>
        <?php if ($client): ?>
          <a href="<?php echo baseUrl(); ?>/client/login.php?logout=1" class="nav-link">Logout (<?php echo e($client['name']); ?>)</a>
        <?php else: ?>
          <a href="<?php echo baseUrl(); ?>/client/login.php" class="nav-link<?php echo $activeNav === 'login' ? ' active' : ''; ?>">Login</a>
          <a href="<?php echo baseUrl(); ?>/client/register.php" class="nav-link<?php echo $activeNav === 'register' ? ' active' : ''; ?>">Register</a>
        <?php endif; ?>
      </nav>
      <button class="nav-toggle" aria-label="Toggle navigation" id="navToggle">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <main class="container">
