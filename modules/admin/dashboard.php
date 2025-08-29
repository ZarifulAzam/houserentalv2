<?php
// dashboard.php - Merged file with role-based redirection and admin dashboard
require_once __DIR__.'/config/session.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$role = $_SESSION['user']['role'];

// Redirect to appropriate dashboard based on role
if ($role === 'admin') {
    // Admin dashboard code
    require_once __DIR__.'/config/db.php';
    $stats = [];
    $stats['users'] = $mysqli->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'];
    $stats['houses'] = $mysqli->query('SELECT COUNT(*) c FROM house_information')->fetch_assoc()['c'];
    $stats['requests'] = $mysqli->query('SELECT COUNT(*) c FROM rental_requests')->fetch_assoc()['c'];
    $stats['available'] = $mysqli->query('SELECT COUNT(*) c FROM house_information WHERE status="available"')->fetch_assoc()['c'];
    $stats['rented'] = $mysqli->query('SELECT COUNT(*) c FROM house_information WHERE status="rented"')->fetch_assoc()['c'];
    
    // Display admin dashboard
    require_once __DIR__.'/includes/header.php';
?>
<div class="container">
    <h2>Admin Dashboard</h2>
    <div class="row">
        <div class="col card"><div class="content"><h3>Users</h3><p><?php echo (int)$stats['users']; ?></p></div></div>
        <div class="col card"><div class="content"><h3>Houses</h3><p><?php echo (int)$stats['houses']; ?></p></div></div>
        <div class="col card"><div class="content"><h3>Requests</h3><p><?php echo (int)$stats['requests']; ?></p></div></div>
        <div class="col card"><div class="content"><h3>Available</h3><p><?php echo (int)$stats['available']; ?></p></div></div>
        <div class="col card"><div class="content"><h3>Rented</h3><p><?php echo (int)$stats['rented']; ?></p></div></div>
    </div>
    <p style="margin-top:12px">
        <a class="btn" href="/house_rental/modules/admin/users.php">Manage Users</a>
        <a class="btn" href="/house_rental/modules/admin/roles.php">Roles</a>
        <a class="btn" href="/house_rental/modules/admin/houses.php">All Houses</a>
    </p>
</div>
<?php 
    require_once __DIR__.'/includes/footer.php';
} elseif ($role === 'owner') {
    header('Location: modules/owner/manage.php');
    exit;
} else {
    header('Location: modules/tenant/browse.php');
    exit;
}
?>