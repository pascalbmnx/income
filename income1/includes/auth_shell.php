<?php
/**
 * Shared dark-glassmorphism auth shell (login pages).
 *
 * Expects an $auth array:
 *   pageTitle      (string) <title>
 *   eyebrow        (string) badge label
 *   heading        (string) card heading
 *   subheading     (string) card subheading
 *   error          (string) optional error message
 *   form_type      (string) 'client' | 'admin'
 *   heroTitle      (string) left-pane headline
 *   heroSub        (string) left-pane paragraph
 *   stat1/stat2/stat3 (array) ['num'=>, 'label'=>, 'class'=>]  class: ''|'tertiary'|'secondary'
 *   panelIcon      (string) material symbol for the small info card
 *   panelTitle     (string)
 *   panelSub       (string)
 *   panelItems     (array, optional) [['icon'=>, 'label'=>], ...]
 *   stampIcon      (string)
 *   stamp          (string)
 *   helpIcon       (string)
 *   help           (string) support/help strip text
 *   buttonText     (string)
 *   registerHref/registerLabel  (client) or backHref (admin)
 *   footerBrandIcon / footerBrand (string)
 *   footerLinks    (array) label => href
 *
 * Renders the full standalone page.
 */
if (empty($auth) || !is_array($auth)) {
    http_response_code(500);
    exit('Auth shell misconfigured.');
}

$isClient = ($auth['form_type'] ?? 'client') !== 'admin';
$errorText = $auth['error'] ?? '';

$fieldName  = $isClient ? 'email'    : 'username';
$fieldType  = $isClient ? 'email'    : 'text';
$fieldLabel = $isClient ? 'Work Email' : 'Username';
$fieldIcon  = $isClient ? 'alternate_email' : 'person';
$fieldPlaceholder = $isClient ? 'name@organization.com' : 'Enter username';

$stats = [$auth['stat1'] ?? [], $auth['stat2'] ?? [], $auth['stat3'] ?? []];
$panelItems = $auth['panelItems'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($auth['pageTitle'] ?? 'Sign In'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo baseUrl(); ?>/assets/css/login.css">
</head>
<body class="auth-body">
  <div class="auth-screen">
    <!-- Background Animated Aurora Light Discs -->
    <div class="auth-aurora">
      <div class="disc disc-1"></div>
      <div class="disc disc-2"></div>
      <div class="disc disc-3"></div>
    </div>

    <div class="auth-grid">
      <!-- Brand & Services Pane -->
      <div class="auth-brand">
        <div class="brand-badge">
          <span class="badge-dot">
            <span></span><span></span>
          </span>
          <span class="badge-label">
            <span class="material-symbols-outlined ms">electrical_services</span>
            <?php echo e($auth['eyebrow'] ?? 'Luta Electrical'); ?>
          </span>
        </div>

        <div class="brand-copy">
          <h1 class="hero-title"><?php echo e($auth['heroTitle'] ?? 'Welcome to Luta Electrical'); ?></h1>
          <p class="hero-sub"><?php echo e($auth['heroSub'] ?? 'Electrical &amp; installation services for residential, commercial and industrial clients.'); ?></p>
        </div>

        <!-- Highlights grid -->
        <div class="stats-grid">
          <?php foreach ($stats as $s): ?>
            <?php if (empty($s['num'])) { continue; } ?>
            <div class="stat">
              <span class="stat-num <?php echo e($s['class'] ?? ''); ?>"><?php echo e($s['num']); ?></span>
              <span class="stat-label"><?php echo e($s['label'] ?? ''); ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Info / services panel -->
        <div class="panel">
          <div class="panel-top">
            <div class="panel-avatar"><span class="material-symbols-outlined ms"><?php echo e($auth['panelIcon'] ?? 'bolt'); ?></span></div>
            <div>
              <p class="panel-title"><?php echo e($auth['panelTitle'] ?? ''); ?></p>
              <p class="panel-sub"><?php echo e($auth['panelSub'] ?? ''); ?></p>
            </div>
          </div>
          <?php if ($panelItems): ?>
            <div class="panel-list">
              <?php foreach ($panelItems as $it): ?>
                <div class="panel-row">
                  <span class="material-symbols-outlined"><?php echo e($it['icon'] ?? 'check_circle'); ?></span>
                  <span><?php echo e($it['label'] ?? ''); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Office stamp -->
        <div class="stamp">
          <span class="material-symbols-outlined ms"><?php echo e($auth['stampIcon'] ?? 'location_on'); ?></span>
          <span><?php echo e($auth['stamp'] ?? 'Morogoro Road, Mikumi, Tanzania'); ?></span>
        </div>
      </div>

      <!-- Glassmorphism login container -->
      <div class="auth-card-holder">
        <div class="auth-card">
          <div class="card-glow"></div>

          <div class="card-head">
            <div>
              <h2><?php echo e($auth['heading'] ?? 'Welcome back'); ?></h2>
              <p><?php echo e($auth['subheading'] ?? 'Sign in to continue.'); ?></p>
            </div>
            <div class="lock-badge" title="Secure encrypted connection">
              <span class="material-symbols-outlined">lock</span>
            </div>
          </div>

          <?php if ($errorText): ?>
            <div class="auth-error">
              <span class="material-symbols-outlined">error</span>
              <span><?php echo e($errorText); ?></span>
            </div>
          <?php endif; ?>

          <form class="auth-form" method="post" action="<?php echo e($_SERVER['SCRIPT_NAME']); ?>" novalidate>
            <div class="field-wrap">
              <label class="field-label" for="credential-input"><?php echo e($fieldLabel); ?></label>
              <div class="field-input-wrap">
                <span class="input-icon"><span class="material-symbols-outlined"><?php echo e($fieldIcon); ?></span></span>
                <input class="field-input" id="credential-input" name="<?php echo e($fieldName); ?>" placeholder="<?php echo e($fieldPlaceholder); ?>" required type="<?php echo e($fieldType); ?>" autofocus>
              </div>
            </div>

            <div class="field-wrap">
              <label class="field-label" for="password-input">Password</label>
              <div class="field-input-wrap">
                <span class="input-icon"><span class="material-symbols-outlined">key</span></span>
                <input class="field-input password-input" id="password-input" name="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required type="password">
                <button aria-label="Toggle password visibility" class="input-toggle" id="toggle-password" type="button">
                  <span class="material-symbols-outlined" id="eye-icon">visibility</span>
                </button>
              </div>
            </div>

            <button class="submit-btn" type="submit">
              <span class="submit-shimmer"></span>
              <span><?php echo e($auth['buttonText'] ?? 'Sign In'); ?></span>
              <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
            </button>
          </form>

          <div class="help-strip">
            <span class="material-symbols-outlined"><?php echo e($auth['helpIcon'] ?? 'support_agent'); ?></span>
            <span><?php echo e($auth['help'] ?? 'Need help? Contact service@lutaelectrical.com'); ?></span>
          </div>

          <div class="auth-foot">
            <?php if ($isClient): ?>
            <p>
              Don't have an account?
              <a href="<?php echo e($auth['registerHref'] ?? 'register.php'); ?>"><?php echo e($auth['registerLabel'] ?? 'Create one here'); ?></a>
            </p>
            <?php else: ?>
            <p><a href="<?php echo e($auth['backHref'] ?? baseUrl() . '/index.php'); ?>">&larr; Back to website</a></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  (function () {
    var toggleBtn = document.getElementById('toggle-password');
    var pwdInput = document.getElementById('password-input');
    var eyeIcon = document.getElementById('eye-icon');
    if (toggleBtn && pwdInput && eyeIcon) {
      toggleBtn.addEventListener('click', function () {
        var isPassword = pwdInput.getAttribute('type') === 'password';
        pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
        eyeIcon.textContent = isPassword ? 'visibility_off' : 'visibility';
      });
    }
  })();
  </script>
</body>
</html>