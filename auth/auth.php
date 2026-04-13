<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Change this to match your project folder (or '' if at root)
define('PROJECT_ROOT', '/campus_ems');

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function current_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user_name() {
    return $_SESSION['name'] ?? '';
}

function require_login($roles = '') {
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
    if (!empty($roles)) {
        $allowed = (array)$roles;
        if (!in_array(current_role(), $allowed, true)) {
            redirect_to_dashboard();
        }
    }
}

function redirect_to_dashboard() {
    switch (current_role()) {
        case 'admin':     header('Location: ' . base_url('admin/dashboard.php')); break;
        case 'organizer': header('Location: ' . base_url('organizer/dashboard.php')); break;
        default:          header('Location: ' . base_url('student/dashboard.php')); break;
    }
    exit;
}

function base_url($path = '') {
    return rtrim(PROJECT_ROOT, '/') . '/' . ltrim($path, '/');
}

function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ----- Back button -----
function back_button($fallback_url = 'index.php') {
    $fallback = base_url($fallback_url);
    echo <<<HTML
    <div class="back-button-wrapper">
        <button onclick="goBackOrFallback('{$fallback}')" class="back-button">← Back</button>
    </div>
    <script>
    function goBackOrFallback(fallback) {
        if (document.referrer && window.history.length > 1) window.history.back();
        else window.location.href = fallback;
    }
    </script>
HTML;
}

// ----- Forward button -----
function forward_button($target_url, $label = 'Next →') {
    $url = base_url($target_url);
    echo <<<HTML
    <div class="forward-wrapper" style="display:inline-block; margin-left:1rem;">
        <a href="{$url}" class="back-button" style="text-decoration:none;">{$label}</a>
    </div>
HTML;
}

/**
 * Upload a file with validation.
 * @param array $file $_FILES array element
 * @param string $target_dir Absolute server path to upload directory
 * @param array $allowed_types Array of allowed extensions (e.g., ['jpg','png'])
 * @param int $max_size Maximum file size in bytes (default 2MB)
 * @return string|null Returns the full server path on success, null on failure
 */
function upload_file($file, $target_dir, $allowed_types = ['jpg','jpeg','png','gif'], $max_size = 2097152) {
    // Check for upload errors
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validate file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        return null;
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return null;
    }
    
    // Create target directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $new_name = uniqid() . '.' . $ext;
    $target_path = $target_dir . $new_name;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_path;
    }
    
    return null;
}
?>