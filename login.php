<?php
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) redirect_to_dashboard();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $conn = db_connect();
        // Check user including deleted_at
        $stmt = $conn->prepare("SELECT id, name, email, password, role, deleted_at FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            if ($user['deleted_at'] !== null) {
                $error = 'Your account has been deactivated. Please contact the administrator.';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                redirect_to_dashboard();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please fill in both fields.';
    }
}
$page_title = 'Log In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:2rem;">
    <div class="form-card">
        <h2 class="form-title">Log In</h2>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required autofocus></div>
            <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary btn-full mt-2">Log In</button>
        </form>
        <p class="form-footer">Don't have an account? <a href="<?= base_url('register.php') ?>">Register →</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>