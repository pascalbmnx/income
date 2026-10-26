<?php
/**
 * Admin dashboard.
 * Shows summary stats and lists of client submissions.
 */
require_once __DIR__ . '/includes/auth.php';

$adminTitle = 'Dashboard / Admin';
$adminActive = 'dashboard';

$pdo = db();

// ---- Stats ----
function statCount(PDO $pdo, string $table, string $statusCol = 'status', array $statuses = []): int
{
    $where = $statuses ? " WHERE $statusCol IN (" . implode(',', array_fill(0, count($statuses), '?')) . ')' : '';
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM `$table`$where");
    $stmt->execute($statuses);
    return (int) $stmt->fetch()['c'];
}

$stats = [
    'applications' => statCount($pdo, 'applications'),
    'drawings'     => statCount($pdo, 'drawings'),
    'reports'      => statCount($pdo, 'reports'),
    'messages'     => statCount($pdo, 'messages'),
];
$stats['total'] = array_sum($stats);
$stats['action'] = statCount($pdo, 'applications', 'status', ['new', 'pending'])
    + statCount($pdo, 'drawings', 'status', ['new', 'pending'])
    + statCount($pdo, 'reports', 'status', ['new', 'pending'])
    + statCount($pdo, 'messages', 'status', ['unread']);

// ---- Application delete / status update handling ----
$flash = null;
if (empty($_GET['poll'])) {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
} else {
    $flash = $_SESSION['flash'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole('editor')) {
    $action = $_POST['action'] ?? '';
    $resource = $_POST['resource'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $tables = ['applications' => 'applications', 'drawings' => 'drawings', 'reports' => 'reports', 'messages' => 'messages'];

    if (isset($tables[$resource]) && $id > 0) {
        if ($action === 'update_status') {
            $newStatus = (string) ($_POST['status'] ?? '');
            $allowed = ALLOWED_STATUS[$resource === 'messages' ? 'message' : substr($resource, 0, -1)] ?? ['new'];
            if (in_array($newStatus, $allowed, true)) {
                $pdo->prepare("UPDATE `{$tables[$resource]}` SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
                $_SESSION['flash'] = 'Status updated.';
            }
        } elseif ($action === 'delete') {
            // remove uploaded image for drawings
            if ($resource === 'drawings') {
                $stmt = $pdo->prepare("SELECT image_path FROM drawings WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetch();
                if ($img && $img['image_path']) {
                    $file = dirname(__DIR__) . '/' . $img['image_path'];
                    if (is_file($file)) @unlink($file);
                }
            }
            $pdo->prepare("DELETE FROM `{$tables[$resource]}` WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = 'Record deleted.';
        }
    }
    header('Location: ' . baseUrl() . '/admin/index.php');
    exit;
}

// ---- Row data for tables ----
$apps  = $pdo->query('SELECT * FROM applications ORDER BY created_at DESC')->fetchAll();
$draws = $pdo->query('SELECT * FROM drawings ORDER BY created_at DESC')->fetchAll();
$reps  = $pdo->query('SELECT * FROM reports ORDER BY created_at DESC')->fetchAll();
$msgs  = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
    <?php if ($flash): ?><div class="alert alert-success"><?php echo e($flash); ?></div><?php endif; ?>

    <div class="page-heading" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
      <div>
        <h2>Dashboard</h2>
        <p>Overview of all client activity.</p>
      </div>
      <a href="https://nikonekt.tanesco.co.tz/application" target="_blank" rel="noopener" class="btn btn-accent">+ New Application</a>
    </div>

    <div class="stats">
      <div class="stat-card"><div class="stat-num"><?php echo $stats['total']; ?></div><div class="stat-label">Total Submissions</div></div>
      <div class="stat-card"><div class="stat-num"><?php echo $stats['applications']; ?></div><div class="stat-label">Applications</div></div>
      <div class="stat-card"><div class="stat-num"><?php echo $stats['drawings']; ?></div><div class="stat-label">Drawings</div></div>
      <div class="stat-card"><div class="stat-num"><?php echo $stats['reports']; ?></div><div class="stat-label">Reports</div></div>
      <div class="stat-card"><div class="stat-num"><?php echo $stats['messages']; ?></div><div class="stat-label">Messages</div></div>
      <div class="stat-card"><div class="stat-num"><?php echo $stats['action']; ?></div><div class="stat-label">Needs Attention</div><div class="stat-pill"><?php echo $stats['action']; ?> to review</div></div>
    </div>

    <?php
    $sectionTitle = 'Recent Submissions';
    $sectionDesc  = 'The latest client requests across all forms.';
    $tableHead    = ['Date', 'Type', 'From', 'Summary', 'Status', 'Actions'];
    $recent = [];
    foreach ([['t' => 'applications', 'label' => 'Electrical Application', 'row' => $apps],
              ['t' => 'drawings', 'label' => 'Drawing Request', 'row' => $draws],
              ['t' => 'reports', 'label' => 'Installation Report', 'row' => $reps],
              ['t' => 'messages', 'label' => 'Contact Message', 'row' => $msgs]] as $src) {
        foreach ($src['row'] as $r) {
            $summary = '';
            if ($src['t'] === 'applications') $summary = ($r['purpose'] ?? '-') . ($r['requested_load'] ? ' / ' . $r['requested_load'] . ' kW' : '');
            elseif ($src['t'] === 'drawings') $summary = $r['drawing_type'] ?? '-';
            elseif ($src['t'] === 'reports')  $summary = ($r['premise'] ?? '-') . ($r['inspect_date'] ? ' (' . $r['inspect_date'] . ')' : '');
            elseif ($src['t'] === 'messages') $summary = $r['subject'] ?? '-';
            $from = ($src['t'] === 'reports') ? ($r['contractor'] ?? '') : ($r['applicant'] ?? $r['name'] ?? '');
            $recent[] = ['date' => $r['created_at'], 'type' => $src['label'], 'from' => $from, 'summary' => $summary, 'status' => $r['status'], 'resource' => $src['t'], 'id' => $r['id']];
        }
    }
    usort($recent, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
    $recent = array_slice($recent, 0, 8);
    ?>

    <div class="panel">
      <div class="panel-head">
        <h3><?php echo $sectionTitle; ?></h3>
        <a href="#applications" class="btn btn-outline btn-small">View all</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><?php foreach ($tableHead as $th): ?><th><?php echo e($th); ?></th><?php endforeach; ?></tr></thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="6" class="empty-state">No submissions yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td><?php echo e(date('Y-m-d H:i', strtotime($r['date']))); ?></td>
                <td><?php echo e($r['type']); ?></td>
                <td><?php echo e($r['from']); ?></td>
                <td><?php echo e($r['summary']); ?></td>
                <td><span class="badge <?php echo e($r['status']); ?>"><?php echo e($r['status']); ?></span></td>
                <td>
                  <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="resource" value="<?php echo e($r['resource']); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Delete this record permanently?');">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php
    // Render a full resource table given rows + column mapping.
    function renderTable(array $rows, array $columns, string $resource, string $labelField, array $statusOptions): void
    {
        echo '<div class="panel" id="' . $resource . '">';
        echo '<div class="panel-head"><h3>' . e(ucfirst($resource)) . '</h3></div>';
        echo '<div class="table-wrap"><table><thead><tr>';
        foreach ($columns as $th) echo '<th>' . e($th) . '</th>';
        echo '<th>Status</th><th>Actions</th></tr></thead><tbody>';
        if (!$rows) {
            echo '<tr><td colspan="' . (count($columns) + 2) . '" class="empty-state">No records found.</td></tr>';
        } else {
            foreach ($rows as $r) {
                echo '<tr>';
                foreach ($columns as $key => $th) {
                    $val = $r[$key] ?? '';
                    // Image column
                    if (in_array($key, ['image_path'], true) && $val) {
                        echo '<td><a href="' . e(uploadUrl($val)) . '" target="_blank"><img class="thumb" src="' . e(uploadUrl($val)) . '" alt="Drawing"></a></td>';
                        continue;
                    }
                    // Boolean flags
                    if (in_array($key, ['earthing', 'meter_board'], true)) {
                        echo '<td>' . ($val ? 'Yes' : 'No') . '</td>';
                        continue;
                    }
                    if ($val === '' || $val === null) $val = '-';
                    // Load columns get a unit suffix
                    if (in_array($key, ['requested_load', 'installed_load'])) $val .= ' kW';
                    echo '<td>' . e($val) . '</td>';
                }
                echo '<td>';
                echo '<form method="post" class="inline-form">';
                echo '<input type="hidden" name="action" value="update_status">';
                echo '<input type="hidden" name="resource" value="' . e($resource) . '">';
                echo '<input type="hidden" name="id" value="' . (int) $r['id'] . '">';
                echo '<select name="status" onchange="this.form.submit()">';
                foreach ($statusOptions as $opt) {
                    $sel = $opt === $r['status'] ? ' selected' : '';
                    echo '<option value="' . e($opt) . '"' . $sel . '>' . e($opt) . '</option>';
                }
                echo '</select></form>';
                echo '</td>';
                echo '<td>';
                echo '<form method="post" class="inline-form">';
                echo '<input type="hidden" name="action" value="delete">';
                echo '<input type="hidden" name="resource" value="' . e($resource) . '">';
                echo '<input type="hidden" name="id" value="' . (int) $r['id'] . '">';
                echo '<button type="submit" class="btn btn-danger btn-small" onclick="return confirm(\'Delete permanently?\');">Delete</button>';
                echo '</form></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table></div></div>';
    }
    ?>

    <?php
    renderTable($apps,
        ['created_at' => 'Date', 'applicant' => 'Applicant', 'phone' => 'Phone', 'address' => 'Address', 'purpose' => 'Purpose', 'requested_load' => 'Load', 'phase' => 'Phase'],
        'applications', 'applicant', ALLOWED_STATUS['application']);
    renderTable($draws,
        ['created_at' => 'Date', 'project' => 'Project', 'applicant' => 'Applicant', 'drawing_type' => 'Type', 'site' => 'Site', 'image_path' => 'Image', 'copies' => 'Copies'],
        'drawings', 'project', ALLOWED_STATUS['drawing']);
    renderTable($reps,
        ['created_at' => 'Date', 'contractor' => 'Contractor', 'premise' => 'Premise', 'system_voltage' => 'Voltage', 'installed_load' => 'Load', 'earthing' => 'Earthing', 'meter_board' => 'Meter'],
        'reports', 'contractor', ALLOWED_STATUS['report']);
    renderTable($msgs,
        ['created_at' => 'Date', 'name' => 'Name', 'email' => 'Email', 'subject' => 'Subject'],
        'messages', 'name', ALLOWED_STATUS['message']);
    ?>

    <script>
    (function () {
      var main = document.querySelector('main');
      if (!main) return;
      setInterval(function () {
        fetch(window.location.pathname + '?poll=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (res) { return res.text(); })
          .then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var fresh = doc.querySelector('main');
            if (fresh && fresh.innerHTML !== main.innerHTML) {
              var y = window.scrollY;
              main.innerHTML = fresh.innerHTML;
              window.scrollTo(0, y);
            }
          })
          .catch(function () {});
      }, 1000);
    })();
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
