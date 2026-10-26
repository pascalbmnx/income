<?php
/**
 * Client homepage with service forms (Electrical Application,
 * Drawing Request, Installation Report).
 */
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Luta Electrical Services | Home';
$activeNav = 'home';

$pdo = db();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ---- Handle form submissions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only signed-in clients may submit a request.
    if (!currentClient()) {
        $_SESSION['flash'] = 'Please sign in to submit your request.';
        header('Location: ' . baseUrl() . '/client/login.php');
        exit;
    }

    $form = $_POST['form'] ?? '';
    $data = $_POST;

    if ($form === 'application') {
        if (requireFields($data, ['applicant', 'phone', 'address', 'purpose']) === null) {
            $pdo->prepare(
                "INSERT INTO applications (applicant, phone, address, purpose, requested_load, phase, notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                trim($data['applicant']), trim($data['phone']), trim($data['address']), trim($data['purpose']),
                $data['load'] !== '' ? (float) $data['load'] : null,
                trim($data['phase'] ?? ''), $data['notes'] ?? null,
                normalizeStatus($data['status'] ?? null, 'application'),
            ]);
            $_SESSION['flash'] = 'Electrical application submitted successfully!';
        }
    } elseif ($form === 'drawing') {
        if (requireFields($data, ['project', 'applicant', 'phone', 'site', 'type']) === null) {
            $upload = storeFileUpload($_FILES['image'] ?? []);
            $pdo->prepare(
                "INSERT INTO drawings (project, applicant, phone, email, site, drawing_type, copies, notes,
                                       image_path, image_name, image_type, image_size, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                trim($data['project']), trim($data['applicant']), trim($data['phone']), $data['email'] ?? null,
                trim($data['site']), trim($data['type']),
                $data['copies'] !== '' ? (int) $data['copies'] : 1,
                $data['notes'] ?? null,
                $upload['image_path'] ?? null, $upload['image_name'] ?? null,
                $upload['image_type'] ?? null, $upload['image_size'] ?? null,
                normalizeStatus($data['status'] ?? null, 'drawing'),
            ]);
            $_SESSION['flash'] = 'Drawing request submitted successfully!';
        }
    } elseif ($form === 'report') {
        if (requireFields($data, ['contractor', 'premise']) === null) {
            $pdo->prepare(
                "INSERT INTO reports (contractor, license_no, premise, inspect_date, system_voltage,
                                      installed_load, designer, earthing, meter_board, comment, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                trim($data['contractor']), $data['license'] ?? null, trim($data['premise']),
                $data['date'] !== '' ? $data['date'] : null, $data['voltage'] ?? null,
                $data['load'] !== '' ? (float) $data['load'] : null,
                $data['designer'] ?? null,
                !empty($data['earthing']) ? 1 : 0, !empty($data['meter']) ? 1 : 0,
                $data['comment'] ?? null,
                normalizeStatus($data['status'] ?? null, 'report'),
            ]);
            $_SESSION['flash'] = 'Installation report submitted successfully!';
        }
    }
    header('Location: ' . baseUrl() . '/index.php');
    exit;
}

/** Render a "sign in required" panel for a protected service. */
function serviceGate(string $icon, string $title, string $desc): void
{
    echo '<div class="service-gate">';
    echo '<div class="gate-icon">' . $icon . '</div>';
    echo '<h3>' . e($title) . '</h3>';
    echo '<p>' . e($desc) . '</p>';
    echo '<div class="form-actions" style="justify-content:center;">';
    echo '<a class="btn btn-primary" href="' . e(baseUrl() . '/client/login.php') . '">Sign In</a>';
    echo '<a class="btn btn-outline" href="' . e(baseUrl() . '/client/register.php') . '">Create Account</a>';
    echo '</div></div>';
}

/** Card click action: logged-in clients scroll to the form, others go to login. */
function cardOnclick(string $sectionId): string
{
    if (currentClient()) {
        return "document.getElementById('" . $sectionId . "').scrollIntoView({behavior:'smooth'});";
    }
    return "location.href='" . baseUrl() . "/client/login.php';";
}

require_once __DIR__ . '/includes/header.php';
?>
      <div class="hero" style="background-image: url('<?php echo baseUrl(); ?>/assets/images/bg.png');">
        <div class="hero-overlay">
          <h2>Welcome to Luta Electrical</h2>
          <p>Request electrical applications, drawings, and submit installation reports all in one place.</p>
        </div>
      </div>

      <?php if ($flash): ?><div class="alert alert-success"><?php echo e($flash); ?></div><?php endif; ?>

      <div class="cards">
        <div class="card" onclick="<?php echo cardOnclick('application'); ?>">
          <div class="card-icon">&#9889;</div>
          <h3>Electrical Application</h3>
          <p>Request a new electrical power supply or modify an existing electrical installation.</p>
          <button type="button" class="btn btn-card">Apply Now</button>
        </div>
        <div class="card" onclick="<?php echo cardOnclick('drawing'); ?>">
          <div class="card-icon">&#128207;</div>
          <h3>Drawing Request</h3>
          <p>Submit a request for site / electrical layout drawings.</p>
          <button type="button" class="btn btn-card">Request Drawing</button>
        </div>
        <div class="card" onclick="<?php echo cardOnclick('report'); ?>">
          <div class="card-icon">&#128221;</div>
          <h3>Installation Report</h3>
          <p>Fill and submit your electrical installation report for inspection.</p>
          <button type="button" class="btn btn-card">Fill Report</button>
        </div>
      </div>

      <div class="contact-strip">
        <h3>Need help?</h3>
        <p>Call us at <a href="tel:+255714250368">+255 714 250 368</a> or email <a href="mailto:service@lutaelectrical.com">service@lutaelectrical.com</a></p>
      </div>

      <hr style="border:none;border-top:1px solid var(--border);margin:36px 0;">

      <section id="application" class="form-page">
        <div class="page-heading">
          <h2>Electrical Application</h2>
          <p>Fill in the form below to request an electrical application.</p>
        </div>
        <?php if (currentClient()): ?>
        <form id="applicationForm" class="form" method="post" action="index.php" data-validate novalidate>
          <input type="hidden" name="form" value="application">
          <div class="form-grid">
            <div class="field">
              <label for="appApplicant">Full Name *</label>
              <input type="text" id="appApplicant" name="applicant" placeholder="Enter your full name" required>
            </div>
            <div class="field">
              <label for="appPhone">Phone Number *</label>
              <input type="tel" id="appPhone" name="phone" placeholder="e.g. 09xxxxxxxx" required>
            </div>
            <div class="field field-full">
              <label for="appAddress">Service Address *</label>
              <input type="text" id="appAddress" name="address" placeholder="House / apartment / building address" required>
            </div>
            <div class="field field-full">
              <label for="appPurpose">Purpose of Supply *</label>
              <select id="appPurpose" name="purpose" required>
                <option value="">Select purpose</option>
                <option value="residential">Residential</option>
                <option value="commercial">Commercial</option>
                <option value="industrial">Industrial</option>
                <option value="agricultural">Agricultural</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="field">
              <label for="appLoad">Requested Load (kW) *</label>
              <input type="number" id="appLoad" name="load" placeholder="e.g. 5.5" min="0" step="0.1" required>
            </div>
            <div class="field">
              <label for="appPhase">Phase *</label>
              <select id="appPhase" name="phase" required>
                <option value="">Select phase</option>
                <option value="single">Single Phase</option>
                <option value="three">Three Phase</option>
              </select>
            </div>
            <div class="field field-full">
              <label for="appNotes">Additional Notes</label>
              <textarea id="appNotes" name="notes" rows="4" placeholder="Any additional information"></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Application</button>
            <button type="reset" class="btn btn-outline">Clear</button>
          </div>
        </form>
        <?php else: ?>
        <?php serviceGate('&#9889;', 'Electrical Application', 'Request a new electrical power supply or modify an existing installation. Sign in to apply.'); ?>
        <?php endif; ?>
      </section>

      <section id="drawing" class="form-page" style="margin-top:36px;">
        <div class="page-heading">
          <h2>Drawing Request</h2>
          <p>Submit a request for site or electrical layout drawings.</p>
        </div>
        <?php if (currentClient()): ?>
        <form id="drawingForm" class="form" method="post" action="index.php" enctype="multipart/form-data" data-validate novalidate>
          <input type="hidden" name="form" value="drawing">
          <div class="form-grid">
            <div class="field">
              <label for="drwProject">Project Name *</label>
              <input type="text" id="drwProject" name="project" placeholder="Project / site name" required>
            </div>
            <div class="field">
              <label for="drwApplicant">Applicant Name *</label>
              <input type="text" id="drwApplicant" name="applicant" placeholder="Enter your full name" required>
            </div>
            <div class="field">
              <label for="drwPhone">Phone Number *</label>
              <input type="tel" id="drwPhone" name="phone" placeholder="e.g. 09xxxxxxxx" required>
            </div>
            <div class="field">
              <label for="drwEmail">Email</label>
              <input type="email" id="drwEmail" name="email" placeholder="you@example.com">
            </div>
            <div class="field field-full">
              <label for="drwSite">Site Location *</label>
              <input type="text" id="drwSite" name="site" placeholder="Project site / region / city" required>
            </div>
            <div class="field">
              <label for="drwType">Drawing Type *</label>
              <select id="drwType" name="type" required>
                <option value="">Select drawing type</option>
                <option value="site">Site Plan</option>
                <option value="electrical">Electrical Layout</option>
                <option value="singleline">Single Line Diagram</option>
                <option value="asbuilt">As-Built Drawing</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="field">
              <label for="drwCopies">Number of Copies</label>
              <input type="number" id="drwCopies" name="copies" placeholder="e.g. 2" min="1" max="20" value="1">
            </div>
            <div class="field field-full">
              <label for="drwNotes">Description / Remarks</label>
              <textarea id="drwNotes" name="notes" rows="4" placeholder="Describe what is needed"></textarea>
            </div>
            <div class="field field-full">
              <label>Rough Image for Drawing</label>
              <div class="upload-box" id="drwUpload">
                <input type="file" id="drwImage" name="image" accept="image/*" class="upload-input">
                <div class="upload-content">
                  <span class="upload-icon">&#128247;</span>
                  <p class="upload-title">Click to upload a rough image</p>
                  <p class="upload-hint">PNG, JPG or GIF - up to 5 MB</p>
                </div>
                <div class="upload-preview hidden">
                  <img id="drwImagePreview" alt="Rough image preview">
                  <p class="upload-file-name" id="drwImageName"></p>
                  <button type="button" class="btn btn-outline btn-small" id="drwRemoveImage">Remove</button>
                </div>
              </div>
              <p class="field-note">Attach a sketch, photo, or rough drawing you want converted into an official drawing.</p>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Drawing Request</button>
            <button type="reset" class="btn btn-outline">Clear</button>
          </div>
        </form>
        <?php else: ?>
        <?php serviceGate('&#128207;', 'Drawing Request', 'Submit a request for site or electrical layout drawings. Sign in to request.'); ?>
        <?php endif; ?>
      </section>

      <section id="report" class="form-page" style="margin-top:36px;">
        <div class="page-heading">
          <h2>Installation Report</h2>
          <p>Fill and submit your electrical installation report for inspection.</p>
        </div>
        <?php if (currentClient()): ?>
        <form id="reportForm" class="form" method="post" action="index.php" data-validate novalidate>
          <input type="hidden" name="form" value="report">
          <div class="form-grid">
            <div class="field">
              <label for="repContractor">Installer / Contractor Name *</label>
              <input type="text" id="repContractor" name="contractor" placeholder="Name of the installer" required>
            </div>
            <div class="field">
              <label for="repLicense">License Number</label>
              <input type="text" id="repLicense" name="license" placeholder="Electrical contractor license">
            </div>
            <div class="field field-full">
              <label for="repPremise">Premise / Site *</label>
              <input type="text" id="repPremise" name="premise" placeholder="Location of the installation" required>
            </div>
            <div class="field">
              <label for="repDate">Inspection Date *</label>
              <input type="date" id="repDate" name="date" required>
            </div>
            <div class="field">
              <label for="repVoltage">System Voltage</label>
              <select id="repVoltage" name="voltage">
                <option value="">Select voltage</option>
                <option value="220">220V / 230V Single Phase</option>
                <option value="380">380V / 400V Three Phase</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="field">
              <label for="repLoad">Installed Load (kW)</label>
              <input type="number" id="repLoad" name="load" placeholder="e.g. 5.5" min="0" step="0.1">
            </div>
            <div class="field">
              <label for="repDesigner">Certified Designer</label>
              <input type="text" id="repDesigner" name="designer" placeholder="Designer name &amp; seal">
            </div>
            <div class="field field-full">
              <label class="check-label"><input type="checkbox" id="repEarthing" name="earthing"> Earthing system installed and tested</label>
            </div>
            <div class="field field-full">
              <label class="check-label"><input type="checkbox" id="repMeter" name="meter"> Meter board / service entrance installed correctly</label>
            </div>
            <div class="field field-full">
              <label for="repComment">Inspector / Contractor Comments</label>
              <textarea id="repComment" name="comment" rows="4" placeholder="Any comments, test results, or remarks"></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Report</button>
            <button type="reset" class="btn btn-outline">Clear</button>
          </div>
        </form>
        <?php else: ?>
        <?php serviceGate('&#128221;', 'Installation Report', 'Fill and submit your electrical installation report for inspection. Sign in to continue.'); ?>
        <?php endif; ?>
      </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
