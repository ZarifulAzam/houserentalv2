<?php
require_once __DIR__.'/../../config/session.php';
require_login(['owner','admin']);
require_once __DIR__.'/../../config/db.php';

$uid = (int)($_SESSION['user']['id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Delete the house (foreign key constraints will handle related records)
    $stmt = $mysqli->prepare('DELETE FROM house_information WHERE id = ? AND owner_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
}

header('Location: manage.php');
exit;
?>