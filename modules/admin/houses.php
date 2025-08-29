<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['admin']);
	require_once __DIR__.'/../../config/db.php';
	$rows=$mysqli->query('SELECT h.id,h.title,h.location,h.rent_price,h.status,u.name as owner FROM house_information h LEFT JOIN users u ON u.id=h.owner_id ORDER BY h.id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2>All Houses</h2>
	<table>
		<tr><th>Title</th><th>Owner</th><th>Location</th><th>Rent</th><th>Status</th></tr>
		<?php foreach($rows as $h):?>
		<tr>
			<td><?php echo htmlspecialchars($h['title']);?></td>
			<td><?php echo htmlspecialchars($h['owner'] ?? '—');?></td>
			<td><?php echo htmlspecialchars($h['location']);?></td>
			<td>$<?php echo (int)$h['rent_price'];?></td>
			<td><?php echo htmlspecialchars($h['status']);?></td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


