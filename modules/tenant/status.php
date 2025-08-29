<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['tenant','owner','admin']);
	require_once __DIR__.'/../../config/db.php';
	$reqId = (int)($_GET['id'] ?? 0);
	$stmt = $mysqli->prepare('SELECT r.id, r.status, r.created_at, h.title FROM rental_requests r JOIN house_information h ON h.id=r.house_id WHERE r.id=?');
	$stmt->bind_param('i', $reqId);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2>Request Status</h2>
	<?php if ($row): ?>
		<p>House: <strong><?php echo htmlspecialchars($row['title']); ?></strong></p>
		<p>Status: <span class="badge <?php echo $row['status']==='approved'?'ok':($row['status']==='rejected'?'warn':''); ?>"><?php echo htmlspecialchars($row['status']); ?></span></p>
		<p>Requested: <?php echo htmlspecialchars($row['created_at']); ?></p>
	<?php else: ?>
		<p>Request not found.</p>
	<?php endif; ?>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


