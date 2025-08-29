<?php
// Separate session bootstrap
if (session_status() === PHP_SESSION_NONE) {
    $secure = false; // set true if using HTTPS
    $httponly = true;
    $samesite = 'Lax';
    if (PHP_VERSION_ID < 70300) {
        session_set_cookie_params(0, '/; samesite=' . $samesite, '', $secure, $httponly);
    } else {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    }
    session_start();
}

function require_login(array $roles = []) {
    if (!isset($_SESSION['user'])) {
        // Determine the correct path to login.php
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, 'modules/') !== false) {
            // We're in a subdirectory, go up to root
            header('Location: ../../login.php');
        } else {
            // We're in root directory
            header('Location: login.php');
        }
        exit;
    }
    
    if ($roles) {
        $userRole = $_SESSION['user']['role'] ?? 'tenant';
        if (!in_array($userRole, $roles, true)) {
            http_response_code(403);
            echo 'Forbidden - You do not have permission to access this page.';
            exit;
        }
    }
}
?>