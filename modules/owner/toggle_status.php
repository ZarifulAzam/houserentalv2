<?php
require_once __DIR__.'/../../config/session.php';
require_login(['owner','admin']);
require_once __DIR__.'/../../config/db.php';

$uid = (int)($_SESSION['user']['id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Verify ownership before updating
    $stmt = $mysqli->prepare('SELECT status FROM house_information WHERE id = ? AND owner_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->fetch_assoc()) {
        // Toggle status
        $stmt = $mysqli->prepare('UPDATE house_information SET status = IF(status="available","rented","available") WHERE id = ? AND owner_id = ?');
        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
    }
}

header('Location: manage.php');
exit;
?>