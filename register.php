<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    Auth::redirectUser($_SESSION['user']['role']);
}

$preselected_role = $_GET['role'] ?? '';
$error = '';
$errors = [];

// Check if locked
$is_locked = Auth::checkAttempts('register', 3);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $token = $_POST['token'] ?? '';
    
    // Validate token
    if (!Auth::validateToken($token)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Simple validation
        if (!$name || strlen($name) < 2) $errors['name'] = 'Name must be at least 2 characters';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required';
        if (!$password || strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters';
        if ($password !== $confirm) $errors['confirm'] = 'Passwords do not match';
        if (!in_array($role, ['tenant', 'owner'])) $errors['role'] = 'Please select account type';
        
        if (empty($errors)) {
            // Check if email exists
            $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            
            if ($stmt->get_result()->fetch_assoc()) {
                $errors['email'] = 'Email already registered';
                Auth::recordAttempt('register');
            } else {
                // Create user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('INSERT INTO users(name, email, phone, password, role) VALUES(?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $name, $email, $phone, $hash, $role);
                
                if ($stmt->execute()) {
                    Auth::clearAttempts('register');
                    header('Location: login.php?success=registered');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                    Auth::recordAttempt('register');
                }
            }
        } else {
            Auth::recordAttempt('register');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>House Rental - Create Account</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <a href="index.php" style="text-decoration: none;">
                <h2 style="margin:0; color:var(--accent);">House Rental</h2>
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="modules/tenant/browse.php">Browse Houses</a>
            <a href="login.php" class="btn">Sign In</a>
        </div>
    </nav>
</header>

<main>
    <div class="container" style="max-width: 500px; margin: 60px auto;">
        <h2 style="text-align: center; margin-bottom: 30px;">Create Your Account</h2>
        
        <?php if ($error): ?>
            <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!$is_locked): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo Auth::generateToken(); ?>">
            
            <label>Full Name
                <input class="input <?php echo isset($errors['name']) ? 'error' : ''; ?>" 
                       type="text" name="name" 
                       value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <small style="color: var(--danger);"><?php echo $errors['name']; ?></small>
                <?php endif; ?>
            </label>
            
            <label>Email Address
                <input class="input <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                       type="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <small style="color: var(--danger);"><?php echo $errors['email']; ?></small>
                <?php endif; ?>
            </label>
            
            <label>Phone (Optional)
                <input class="input" type="tel" name="phone" 
                       value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                       placeholder="+880 1XXX-XXXXXX">
            </label>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <label>Password
                    <input class="input <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                           type="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <small style="color: var(--danger);"><?php echo $errors['password']; ?></small>
                    <?php endif; ?>
                </label>
                
                <label>Confirm Password
                    <input class="input <?php echo isset($errors['confirm']) ? 'error' : ''; ?>" 
                           type="password" name="confirm_password" required>
                    <?php if (isset($errors['confirm'])): ?>
                        <small style="color: var(--danger);"><?php echo $errors['confirm']; ?></small>
                    <?php endif; ?>
                </label>
            </div>
            
            <label>Account Type
                <select class="input <?php echo isset($errors['role']) ? 'error' : ''; ?>" name="role" required>
                    <option value="">Choose account type</option>
                    <option value="tenant" <?php if($preselected_role === 'tenant' || ($_POST['role'] ?? '') === 'tenant') echo 'selected'; ?>>
                        Tenant - Find houses to rent
                    </option>
                    <option value="owner" <?php if($preselected_role === 'owner' || ($_POST['role'] ?? '') === 'owner') echo 'selected'; ?>>
                        Owner - List properties for rent
                    </option>
                </select>
                <?php if (isset($errors['role'])): ?>
                    <small style="color: var(--danger);"><?php echo $errors['role']; ?></small>
                <?php endif; ?>
            </label>
            
            <button class="btn primary" type="submit" style="width: 100%; margin-top: 16px;">
                Create Account
            </button>
        </form>
        <?php else: ?>
            <div class="flash error">Too many attempts. Try again in 5 minutes.</div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #1f2437;">
            <p>Already have an account?</p>
            <a href="login.php" class="btn">Sign In</a>
        </div>
    </div>
</main>

<footer>
    <p>&copy; <?php echo date('Y'); ?> House Rental System</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.querySelector('input[name="password"]');
    const confirm = document.querySelector('input[name="confirm_password"]');
    
    // Real-time password match check
    if (password && confirm) {
        function checkMatch() {
            if (confirm.value && password.value !== confirm.value) {
                confirm.style.borderColor = 'var(--danger)';
            } else {
                confirm.style.borderColor = '';
            }
        }
        
        password.addEventListener('input', checkMatch);
        confirm.addEventListener('input', checkMatch);
    }
    
    // Simple form validation
    if (form) {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Creating...';
            
            setTimeout(() => {
                button.disabled = false;
                button.textContent = 'Create Account';
            }, 5000);
        });
    }
});
</script>

<style>
.input.error {
    border-color: var(--danger);
}

label small {
    display: block;
    margin-top: 4px;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
</body>
</html>