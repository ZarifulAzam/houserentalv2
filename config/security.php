<?php
/**
 * Security utilities for the House Rental system
 */

class SecurityUtils {
    
    /**
     * Generate and validate CSRF tokens
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !$token) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting functionality
     */
    public static function checkRateLimit($action, $max_attempts = 5, $lockout_time = 300) {
        if (!isset($_SESSION[$action . '_attempts'])) {
            $_SESSION[$action . '_attempts'] = [];
        }
        
        $current_time = time();
        
        // Clean old attempts
        $_SESSION[$action . '_attempts'] = array_filter(
            $_SESSION[$action . '_attempts'], 
            function($attempt) use ($current_time, $lockout_time) {
                return ($current_time - $attempt) < $lockout_time;
            }
        );
        
        $attempts_count = count($_SESSION[$action . '_attempts']);
        $is_locked = $attempts_count >= $max_attempts;
        return [
            'is_locked' => $is_locked,
            'attempts_remaining' => max(0, $max_attempts - $attempts_count),
            'lockout_time_remaining' => $is_locked && !empty($_SESSION[$action . '_attempts']) ? $lockout_time - ($current_time - min($_SESSION[$action . '_attempts'])) : 0
        ];
    }
    
    public static function recordAttempt($action) {
        if (!isset($_SESSION[$action . '_attempts'])) {
            $_SESSION[$action . '_attempts'] = [];
        }
        $_SESSION[$action . '_attempts'][] = time();
    }
    
    public static function clearAttempts($action) {
        $_SESSION[$action . '_attempts'] = [];
    }
    
    /**
     * Input validation helpers
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 160;
    }
    
    public static function validateName($name) {
        return strlen($name) >= 2 && 
               strlen($name) <= 120 && 
               preg_match('/^[a-zA-Z\s\-\'\.]+$/', $name);
    }
    
    public static function validatePhone($phone) {
        return empty($phone) || preg_match('/^[\+]?[\d\s\-\(\)]{10,15}$/', $phone);
    }
    
    public static function validatePassword($password) {
        return strlen($password) >= 8 && 
               preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password);
    }
    
    /**
     * Secure password hashing
     */
    public static function hashPassword($password) {
        // Use Argon2ID for better security if available
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3,         // 3 threads
            ]);
        }
        // Fallback to bcrypt with higher cost
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Sanitize user input
     */
    public static function sanitizeInput($input, $type = 'string') {
        $input = trim($input);
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details,
            'session_id' => session_id()
        ];
        
        $log_line = json_encode($log_entry) . PHP_EOL;
        
        // Log to file (ensure logs directory exists and is writable)
        $log_file = __DIR__ . '/../logs/security.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Enhanced session security
     */
    public static function secureLogin($user_data) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user'] = $user_data;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Log successful login
        self::logSecurityEvent('login_success', [
            'user_id' => $user_data['id'],
            'role' => $user_data['role']
        ]);
        
        // Regenerate CSRF token
        self::regenerateCSRFToken();
    }
    
    /**
     * Check session timeout
     */
    public static function checkSessionTimeout($timeout = 7200) { // 2 hours default
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > $timeout) {
            
            self::logSecurityEvent('session_timeout', [
                'user_id' => $_SESSION['user']['id'] ?? null
            ]);
            
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Secure logout
     */
    public static function secureLogout() {
        if (isset($_SESSION['user'])) {
            self::logSecurityEvent('logout', [
                'user_id' => $_SESSION['user']['id']
            ]);
        }
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], 
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Database query helper with error handling
     */
    public static function executeQuery($mysqli, $query, $params = [], $types = '') {
        try {
            $stmt = $mysqli->prepare($query);
            
            if ($params && $types) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            return $stmt->get_result();
            
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            self::logSecurityEvent('database_error', [
                'query' => substr($query, 0, 100),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

/**
 * Form validation class
 */
class FormValidator {
    private $errors = [];
    
    public function validate($field, $value, $rules) {
        $this->errors[$field] = [];
        
        foreach ($rules as $rule => $param) {
            switch ($rule) {
                case 'required':
                    if (empty(trim($value))) {
                        $this->errors[$field][] = ucfirst($field) . ' is required';
                    }
                    break;
                    
                case 'min_length':
                    if (strlen($value) < $param) {
                        $this->errors[$field][] = ucfirst($field) . " must be at least {$param} characters";
                    }
                    break;
                    
                case 'max_length':
                    if (strlen($value) > $param) {
                        $this->errors[$field][] = ucfirst($field) . " must not exceed {$param} characters";
                    }
                    break;
                    
                case 'email':
                    if (!SecurityUtils::validateEmail($value)) {
                        $this->errors[$field][] = 'Please enter a valid email address';
                    }
                    break;
                    
                case 'password':
                    if (!SecurityUtils::validatePassword($value)) {
                        $this->errors[$field][] = 'Password must contain uppercase, lowercase, number, and special character';
                    }
                    break;
                    
                case 'match':
                    if ($value !== $param) {
                        $this->errors[$field][] = 'Passwords do not match';
                    }
                    break;
                    
                case 'in_array':
                    if (!in_array($value, $param, true)) {
                        $this->errors[$field][] = 'Invalid ' . $field . ' selected';
                    }
                    break;
            }
        }
        
        // Remove field if no errors
        if (empty($this->errors[$field])) {
            unset($this->errors[$field]);
        }
        
        return empty($this->errors[$field]);
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getError($field) {
        return $this->errors[$field][0] ?? '';
    }
}