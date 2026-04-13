<?php
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) redirect_to_dashboard();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');
    $role = trim($_POST['role'] ?? 'student');

    if ($name === '') $errors[] = 'Full name is required.';
    elseif (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
    if ($email === '') $errors[] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($password === '') $errors[] = 'Password is required.';
    elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['student','organizer'])) $role = 'student';

    if (empty($errors)) {
        $conn = db_connect();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $stmt->close();
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $email, $hashed, $role);
            if ($stmt->execute()) {
                set_flash('success', 'Account created! You can now log in.');
                header('Location: ' . base_url('login.php'));
                exit;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
        $conn->close();
    }
}
$page_title = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:2rem;">
    <div class="form-card">
        <h2 class="form-title">Create Account</h2>
        <p class="text-center text-muted mb-3" style="margin-top:-.5rem;">Join the campus event community</p>
        <?php if ($errors): ?>
            <div class="alert alert-error"><strong>Please fix the following:</strong><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required></div>
            <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm" class="form-control" required></div>
            </div>
            <div class="form-group">
                <label class="form-label">I am a…</label>
                <select name="role" class="form-control">
                    <option value="student" <?= (($_POST['role'] ?? 'student') === 'student') ? 'selected' : '' ?>>Student</option>
                    <option value="organizer" <?= (($_POST['role'] ?? '') === 'organizer') ? 'selected' : '' ?>>Event Organizer</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-full mt-2">Create Account</button>
        </form>
        <p class="form-footer">Already have an account? <a href="<?= base_url('login.php') ?>">Log in →</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>