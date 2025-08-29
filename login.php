<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    Auth::redirectUser($_SESSION['user']['role']);
}

$error = '';
$success = $_GET['success'] ?? '';

// Check if locked due to too many attempts
$is_locked = Auth::checkAttempts('login');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['token'] ?? '';
    
    if (!Auth::validateToken($token)) {
        $error = 'Invalid form submission. Please try again.';
    } elseif (!$email || !$password) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            Auth::login([
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);
            Auth::clearAttempts('login');
            Auth::redirectUser($user['role']);
        } else {
            Auth::recordAttempt('login');
            $remaining = 5 - count($_SESSION['login_attempts'] ?? []);
            $error = $remaining > 0 ? 
                "Invalid credentials. $remaining attempts remaining." : 
                "Too many failed attempts. Try again in 5 minutes.";
        }
    }
}

if ($is_locked) {
    $error = "Account locked. Try again in 5 minutes.";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>House Rental - Sign In</title>
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
            <a href="register.php" class="btn primary">Create Account</a>
        </div>
    </nav>
</header>

<main>
    <div class="container" style="max-width: 400px; margin: 80px auto;">
        <h2 style="text-align: center; margin-bottom: 30px;">Welcome Back</h2>
        
        <?php if ($error): ?>
            <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success === 'registered'): ?>
            <div class="flash success">Account created! You can now sign in.</div>
        <?php endif; ?>
        
        <?php if (!$is_locked): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo Auth::generateToken(); ?>">
            
            <label>Email Address
                <input class="input" type="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </label>
            
            <label>Password
                <input class="input" type="password" name="password" required>
            </label>
            
            <button class="btn primary" type="submit" style="width: 100%; margin-top: 16px;">
                Sign In
            </button>
        </form>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #1f2437;">
            <p>Don't have an account?</p>
            <a href="register.php" class="btn">Create Account</a>
        </div>
        
        <!-- <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--muted);">
            <p><strong>Demo:</strong> admin@example.com / Admin@123</p>
        </div> -->
    </div>
</main>

<footer>
    <p>&copy; <?php echo date('Y'); ?> House Rental System</p>
</footer>
</body>
</html>