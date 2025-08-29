<?php 
// Determine the correct path to session.php based on current file location
$session_path = '';
if (strpos($_SERVER['PHP_SELF'], 'modules/') !== false) {
    // We're in a module subdirectory
    $session_path = __DIR__ . '/../../config/session.php';
} else {
    // We're in the root directory
    $session_path = __DIR__ . '/../config/session.php';
}

if (file_exists($session_path)) {
    require_once $session_path;
} else {
    require_once __DIR__ . '/../config/session.php';
}

// Determine base URL for assets
$base_url = '';
if (strpos($_SERVER['PHP_SELF'], 'modules/') !== false) {
    $base_url = '../../';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>House Rental</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <script defer src="<?php echo $base_url; ?>assets/js/script.js"></script>
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <a href="<?php echo $base_url; ?>index.php" style="text-decoration: none;">
                <h2 style="margin:0; color:var(--accent);">🏠 House Rental</h2>
            </a>
        </div>
        <div class="nav-links">
            <?php if (isset($_SESSION['user'])): ?>
                <?php $role = $_SESSION['user']['role'] ?? 'tenant'; ?>
                
                <a href="<?php echo $base_url; ?>modules/tenant/browse.php">Browse Houses</a>
                
                <?php if ($role === 'admin'): ?>
                    <a href="<?php echo $base_url; ?>modules/admin/dashboard.php">Admin Dashboard</a>
                    <a href="<?php echo $base_url; ?>modules/admin/users.php">Manage Users</a>
                    <a href="<?php echo $base_url; ?>modules/admin/houses.php">All Houses</a>
                <?php elseif ($role === 'owner'): ?>
                    <a href="<?php echo $base_url; ?>modules/owner/manage.php">My Houses</a>
                    <a href="<?php echo $base_url; ?>modules/owner/requests.php">Rental Requests</a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>modules/tenant/my_requests.php">My Requests</a>
                <?php endif; ?>
                
                <span class="user-info">Hi, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <a href="<?php echo $base_url; ?>logout.php" class="btn danger" style="margin-left: 8px;">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>index.php">Login</a>
                <a href="<?php echo $base_url; ?>register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<main>