<?php
/**
 * Client About page.
 */
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'About Us | Luta Electrical';
$activeNav = 'about';

require_once __DIR__ . '/includes/header.php';
?>
      <div class="page-heading">
        <h2>About Our Company</h2>
        <p>Learn more about Luta Electrical Services.</p>
      </div>

      <div class="about-section">
        <div class="about-content">
          <div class="about-logo">
            <img src="<?php echo baseUrl(); ?>/assets/images/luta.png" alt="Company Logo">
          </div>
          <div class="about-text">
            <h2>We Power Your Future</h2>
            <p>We are a trusted electrical services company dedicated to delivering safe, reliable, and efficient electrical applications and installations. Our team of certified engineers and technicians supports clients from the initial application and drawing stage through to inspection and final installation reports.</p>
            <p>With years of experience in the field, we serve residential, commercial, and industrial customers with professionalism, transparency, and a strong commitment to quality and safety standards.</p>
          </div>
        </div>
        <div class="about-highlights">
          <div class="highlight">
            <span class="highlight-num">500+</span>
            <span class="highlight-label">Installations Completed</span>
          </div>
          <div class="highlight">
            <span class="highlight-num">15+</span>
            <span class="highlight-label">Years of Experience</span>
          </div>
          <div class="highlight">
            <span class="highlight-num">24/7</span>
            <span class="highlight-label">Customer Support</span>
          </div>
        </div>
      </div>

      <div class="contact-strip">
        <h3>Ready to get started?</h3>
        <p><a href="<?php echo baseUrl(); ?>/index.php">Submit your application or report</a> online today, or <a href="<?php echo baseUrl(); ?>/contact.php">contact us</a> for assistance.</p>
      </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
