<?php
require_once __DIR__.'/config/session.php';

// Clear all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], 
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to index page (works regardless of directory structure)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_url = $protocol . $host . $script_dir;

header('Location: ' . $base_url . '/index.php');
exit;
?>