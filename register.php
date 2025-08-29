<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin') {
        header('Location: modules/admin/dashboard.php');
    } elseif ($role === 'owner') {
        header('Location: modules/owner/manage.php');
    } else {
        header('Location: modules/tenant/browse.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Validation
    if (!$name || !$email || !$pass || !$confirm_pass || !$role) {
        $error = 'All fields are required';
    } elseif ($pass !== $confirm_pass) {
        $error = 'Passwords do not match';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!in_array($role, ['tenant', 'owner'], true)) {
        $error = 'Please select a valid account type';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            $error = 'Email address is already registered';
        } else {
            // Create new user
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare('INSERT INTO users(name, email, password, role) VALUES(?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $email, $hash, $role);
            
            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now login.';
                // Clear form data on success
                $name = $email = $role = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>House Rental - Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script defer src="assets/js/script.js"></script>
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <h2 style="margin:0; color:var(--accent);">🏠 House Rental</h2>
        </div>
    </nav>
</header>
<main>
    <div class="container" style="max-width: 500px; margin: 40px auto;">
        <h2 style="text-align: center; margin-bottom: 30px;">Create Account</h2>
        
        <?php if ($error): ?>
            <div class="flash error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="flash success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <label>Full Name
                <input class="input" type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            </label>
            
            <label>Email Address
                <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </label>
            
            <label>Password
                <input class="input" type="password" name="password" required>
                <small style="color: var(--muted);">Minimum 6 characters</small>
            </label>
            
            <label>Confirm Password
                <input class="input" type="password" name="confirm_password" required>
            </label>
            
            <label>Account Type
                <select name="role" class="input" required>
                    <option value="">Choose your role</option>
                    <option value="tenant" <?php if(($_POST['role'] ?? '') === 'tenant') echo 'selected'; ?>>
                        🏠 Tenant - I want to rent houses
                    </option>
                    <option value="owner" <?php if(($_POST['role'] ?? '') === 'owner') echo 'selected'; ?>>
                        🏡 Owner - I want to rent out my houses
                    </option>
                </select>
                <small style="color: var(--muted);">Choose based on what you want to do in the system</small>
            </label>
            
            <?php if (!$success): ?>
                <button class="btn primary" type="submit" style="width: 100%; margin-top: 16px;">Create Account</button>
            <?php endif; ?>
        </form>
        
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #1f2437;">
            <p>Already have an account?</p>
            <a href="index.php" class="btn">Login Here</a>
        </div>
    </div>
</main>
<footer>
    <p>&copy; <?php echo date('Y'); ?> House Rental System</p>
</footer>
</body>
</html>