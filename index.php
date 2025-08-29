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

// Get basic stats
$available_count = 0;
$total_count = 0;

try {
    $result = $mysqli->query('SELECT COUNT(*) as total, SUM(status="available") as available FROM house_information');
    if ($result) {
        $stats = $result->fetch_assoc();
        $total_count = (int)$stats['total'];
        $available_count = (int)$stats['available'];
    }
} catch (Exception $e) {
    // Silently handle database errors for guest users
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>House Rental - Find Your Perfect Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <script defer src="assets/js/script.js"></script>
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <h2 style="margin:0; color:var(--accent);">House Rental</h2>
        </div>
        <div class="nav-links">
            <a href="modules/tenant/browse.php">Browse Houses</a>
            <a href="login.php" class="btn">Sign In</a>
            <a href="register.php" class="btn primary">Get Started</a>
        </div>
    </nav>
</header>

<main>
    <div class="container landing-main">
        <h1 class="landing-title" style="font-size: 3rem; margin-bottom: 20px;">Find Your Perfect Home</h1>
        <p class="landing-subtitle">
            Connect with trusted property owners and discover houses that match your needs and budget.
        </p>
        
        <div class="landing-actions">
            <a href="register.php" class="btn primary btn-enhanced">Start Your Search</a>
            <a href="modules/tenant/browse.php" class="btn btn-enhanced">Browse Houses</a>
        </div>
        
        <?php if ($available_count > 0): ?>
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number available"><?php echo $available_count; ?></span>
                    <span class="stat-label">Available Now</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number total"><?php echo $total_count; ?></span>
                    <span class="stat-label">Total Properties</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="role-cards">
        <div class="container role-card">
            <div class="role-icon tenant-icon">🏠</div>
            <h3 style="color: var(--text); margin-bottom: 12px;">For Tenants</h3>
            <p style="color: var(--muted); margin-bottom: 20px;">Browse and rent houses from verified property owners</p>
            <a href="register.php?role=tenant" class="btn primary">Find Houses</a>
        </div>
        
        <div class="container role-card">
            <div class="role-icon owner-icon">🏡</div>
            <h3 style="color: var(--text); margin-bottom: 12px;">For Property Owners</h3>
            <p style="color: var(--muted); margin-bottom: 20px;">List your properties and connect with reliable tenants</p>
            <a href="register.php?role=owner" class="btn primary">List Property</a>
        </div>
    </div>
</main>

<footer>
    <p>&copy; <?php echo date('Y'); ?> House Rental System</p>
</footer>
</body>
</html>