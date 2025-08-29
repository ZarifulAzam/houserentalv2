<?php
/**
 * Simple authentication helper - reduces code duplication
 */

class Auth {
    
    public static function generateToken() {
        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['token'];
    }
    
    public static function validateToken($token) {
        return isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token);
    }
    
    public static function checkAttempts($type, $max = 5) {
        $key = $type . '_attempts';
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        // Clean attempts older than 5 minutes
        $_SESSION[$key] = array_filter($_SESSION[$key], function($time) {
            return (time() - $time) < 300;
        });
        
        return count($_SESSION[$key]) >= $max;
    }
    
    public static function recordAttempt($type) {
        $key = $type . '_attempts';
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        $_SESSION[$key][] = time();
    }
    
    public static function clearAttempts($type) {
        $_SESSION[$type . '_attempts'] = [];
    }
    
    public static function login($user_data) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user_data;
        $_SESSION['token'] = bin2hex(random_bytes(16));
    }
    
    public static function redirectUser($role) {
        $routes = [
            'admin' => 'modules/admin/dashboard.php',
            'owner' => 'modules/owner/manage.php',
            'tenant' => 'modules/tenant/browse.php'
        ];
        
        $location = $routes[$role] ?? 'modules/tenant/browse.php';
        header("Location: $location");
        exit;
    }
}
?>