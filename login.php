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
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <span toggle="#password" class="fa fa-fw fa-eye-slash field-icon toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></span>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div class="checkbox" style="display: flex; align-items: center;">
                    <input type="checkbox" id="remember" style="margin-right: 0.5rem;">
                    <label for="remember" style="font-size: 0.85rem; color: #6b625c;">Remember me</label>
                </div>
                <a href="<?= base_url('forgot_password.php') ?>" class="forgot-link">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Log In</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="<?= base_url('register.php') ?>" class="register-link-highlight">Sign up now</a>
        </div>
    </div>
</div>

<style>
    /* Additional styling for links */
    .forgot-link {
        font-size: 0.85rem;
        color: #8a837c;
        text-decoration: none;
        transition: color 0.2s;
        border-bottom: 1px dashed #e0dbd4;
    }
    .forgot-link:hover {
        color: #c13b2b;
        border-bottom-color: #c13b2b;
    }
    .register-link {
        text-align: center;
        margin-top: 1.5rem;
        font-size: 0.9rem;
        color: #6b625c;
        border-top: 1px solid #ece5df;
        padding-top: 1.5rem;
    }
    .register-link-highlight {
        color: #c13b2b;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .register-link-highlight:hover {
        color: #96201a;
        text-decoration: underline;
    }
    .checkbox label {
        cursor: pointer;
    }
</style>

<script>
    document.querySelectorAll('.toggle-password').forEach(item => {
        item.addEventListener('click', function() {
            const input = document.querySelector(this.getAttribute('toggle'));
            if (input.getAttribute('type') === 'password') {
                input.setAttribute('type', 'text');
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                input.setAttribute('type', 'password');
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
