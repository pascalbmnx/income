<?php
/**
 * Client Contact page.
 */
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Contact Us | Luta Electrical';
$activeNav = 'contact';

$pdo = db();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    if (requireFields($data, ['name', 'email', 'subject', 'message']) === null) {
        $pdo->prepare("INSERT INTO messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, ?)")
            ->execute([
                trim($data['name']), trim($data['email']), trim($data['subject']), trim($data['message']),
                normalizeStatus($data['status'] ?? null, 'message'),
            ]);
        $_SESSION['flash'] = 'Your message has been sent successfully!';
    } else {
        $_SESSION['flash'] = 'Please fill in all required fields.';
    }
    header('Location: ' . baseUrl() . '/contact.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
      <div class="page-heading">
        <h2>Contact Us</h2>
        <p>Reach out to our team for any questions or support.</p>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo e($flash); ?></div><?php endif; ?>

      <div class="contact-grid">
        <div class="contact-info">
          <div class="contact-item">
            <span class="contact-icon">&#128205;</span>
            <div>
              <h4>Address</h4>
              <p>Morogoro Road, Mikumi, Tanzania</p>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">&#9742;</span>
            <div>
              <h4>Phone</h4>
              <p><a href="tel:+255714250368">+255 714 250 368</a></p>
              <p><a href="tel:+255758032697">+255 758 032 697</a></p>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">&#9993;</span>
            <div>
              <h4>Email</h4>
              <p><a href="mailto:service@lutaelectrical.com">service@lutaelectrical.com</a></p>
              <p><a href="mailto:support@lutaelectrical.com">support@lutaelectrical.com</a></p>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">&#104;&#100;</span>
            <div>
              <h4>Working Hours</h4>
              <p>Mon - Sat: 8:00 AM - 6:00 PM</p>
              <p>Sunday: Closed</p>
            </div>
          </div>
        </div>

        <form id="contactForm" class="form contact-form" method="post" action="contact.php" data-validate novalidate>
          <div class="form-grid">
            <div class="field">
              <label for="ctName">Full Name *</label>
              <input type="text" id="ctName" name="name" placeholder="Enter your name" required>
            </div>
            <div class="field">
              <label for="ctEmail">Email *</label>
              <input type="email" id="ctEmail" name="email" placeholder="you@example.com" required>
            </div>
            <div class="field field-full">
              <label for="ctSubject">Subject *</label>
              <input type="text" id="ctSubject" name="subject" placeholder="What is it about?" required>
            </div>
            <div class="field field-full">
              <label for="ctMessage">Message *</label>
              <textarea id="ctMessage" name="message" rows="5" placeholder="Write your message here" required></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send Message</button>
            <button type="reset" class="btn btn-outline">Clear</button>
          </div>
        </form>
      </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
