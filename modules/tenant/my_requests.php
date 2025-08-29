<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['tenant','owner','admin']);
	require_once __DIR__.'/../../config/db.php';
	$uid = (int)($_SESSION['user']['id'] ?? 0);
	$stmt = $mysqli->prepare('SELECT r.id, r.status, r.created_at, h.title, h.location FROM rental_requests r JOIN house_information h ON h.id=r.house_id WHERE r.user_id=? ORDER BY r.id DESC');
	$stmt->bind_param('i', $uid);
	$stmt->execute();
	$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2>My Requests</h2>
	<table>
		<tr><th>House</th><th>Location</th><th>Status</th><th>Requested</th></tr>
		<?php foreach ($rows as $r): ?>
			<tr>
				<td><?php echo htmlspecialchars($r['title']); ?></td>
				<td><?php echo htmlspecialchars($r['location']); ?></td>
				<td><?php echo htmlspecialchars($r['status']); ?></td>
				<td><?php echo htmlspecialchars($r['created_at']); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


