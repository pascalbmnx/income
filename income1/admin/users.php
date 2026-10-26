<?php
/**
 * Admin - manage users.
 * Provides CRUD on the admin_users table. Only 'admin' role can manage users.
 */
require_once __DIR__ . '/includes/auth.php';

// Only admins can access this page.
if (!hasRole('admin')) {
    http_response_code(403);
    $adminTitle = 'Forbidden';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="page-heading"><h2>Forbidden</h2><p>You do not have permission to manage users.</p><p><a href="' . baseUrl() . '/admin/index.php">Back to dashboard</a></p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$adminTitle = 'Manage Users / Admin';
$adminActive = 'users';

$pdo = db();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $id      = (int) ($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $role     = (string) ($_POST['role'] ?? 'viewer');
    if (!in_array($role, ADMIN_ROLES, true)) $role = 'viewer';

    try {
        if ($action === 'create') {
            if ($username === '') throw new RuntimeException('Username is required.');
            if (strlen($password) < 6) throw new RuntimeException('Password must be at least 6 characters.');
            $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) throw new RuntimeException('That username already exists.');
            $pdo->prepare('INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, ?)')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $_SESSION['flash'] = 'User created.';
        } elseif ($action === 'update' && $id > 0) {
            // prevent removing/locking yourself out
            $me = $_SESSION['admin_user'];
            if ($id === (int) $me['id'] && $role !== $me['role']) throw new RuntimeException('You cannot change your own role.');
            $pdo->prepare('UPDATE admin_users SET username = ?, role = ? WHERE id = ?')->execute([$username, $role, $id]);
            if ($password !== '') {
                $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            $_SESSION['flash'] = 'User updated.';
        } elseif ($action === 'delete' && $id > 0) {
            $me = $_SESSION['admin_user'];
            if ($id === (int) $me['id']) throw new RuntimeException('You cannot delete your own account.');
            $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
            $_SESSION['flash'] = 'User deleted.';
        }
        header('Location: ' . baseUrl() . '/admin/users.php');
        exit;
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

$users = $pdo->query('SELECT id, username, role, created_at FROM admin_users ORDER BY id ASC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
    <div class="page-heading">
      <h2>Manage Users</h2>
      <p>Create and manage administrator accounts.</p>
    </div>

    <?php if ($flash): ?><div class="alert alert-success"><?php echo e($flash); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-head"><h3>Create New User</h3></div>
      <div class="panel-body">
        <form method="post" action="users.php" class="user-form" novalidate>
          <input type="hidden" name="action" value="create">
          <div class="field">
            <label for="nuUsername">Username</label>
            <input type="text" id="nuUsername" name="username" placeholder="New username" required>
          </div>
          <div class="field">
            <label for="nuPassword">Password</label>
            <input type="password" id="nuPassword" name="password" placeholder="Min 6 characters" required>
          </div>
          <div class="field">
            <label for="nuRole">Role</label>
            <select id="nuRole" name="role">
              <option value="viewer">Viewer</option>
              <option value="editor">Editor</option>
              <option value="admin" selected>Admin</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create User</button>
          </div>
        </form>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Existing Users</h3></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="5" class="empty-state">No users found.</td></tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><?php echo (int) $u['id']; ?></td>
                <td>
                  <input form="userForm<?php echo (int) $u['id']; ?>" type="text" name="username" value="<?php echo e($u['username']); ?>" required>
                </td>
                <td>
                  <select form="userForm<?php echo (int) $u['id']; ?>" name="role">
                    <?php foreach (ADMIN_ROLES as $r): ?>
                      <option value="<?php echo $r; ?>"<?php echo $r === $u['role'] ? ' selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td><?php echo e(date('Y-m-d', strtotime($u['created_at']))); ?></td>
                <td>
                  <form id="userForm<?php echo (int) $u['id']; ?>" method="post" action="users.php" class="inline-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                    <button type="submit" class="btn btn-outline btn-small">Save</button>
                  </form>
                  <form method="post" action="users.php" class="inline-form" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
