<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';

// If user is already logged in, redirect to their appropriate dashboard
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    if ($email && $pass) {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            $role = $user['role'];
            if ($role === 'admin') {
                header('Location: modules/admin/dashboard.php');
            } elseif ($role === 'owner') {
                header('Location: modules/owner/manage.php');
            } else {
                header('Location: modules/tenant/browse.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>House Rental - Login</title>
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
    <div class="container" style="max-width: 400px; margin: 60px auto;">
        <h2 style="text-align: center; margin-bottom: 30px;">Welcome Back</h2>
        
        <?php if ($error): ?>
            <div class="flash error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <label>Email Address
                <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </label>
            <label>Password
                <input class="input" type="password" name="password" required>
            </label>
            <button class="btn primary" type="submit" style="width: 100%; margin-top: 8px;">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #1f2437;">
            <p>Don't have an account?</p>
            <a href="register.php" class="btn" style="display: inline-block;">Create Account</a>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--muted);">
            <p><strong>Demo Accounts:</strong></p>
            <p>Admin: admin@example.com / Admin@123</p>
        </div>
    </div>
</main>
<footer>
    <p>&copy; <?php echo date('Y'); ?> House Rental System</p>
</footer>
</body>
</html>