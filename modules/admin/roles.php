<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['admin']);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2>Roles & Permissions</h2>
	<p>Mini version uses simple roles on `users.role` with values: <code>tenant</code>, <code>owner</code>, <code>admin</code>.</p>
	<p>Admins can change user roles in the Users page.</p>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


