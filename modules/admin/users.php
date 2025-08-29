<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['admin']);
	require_once __DIR__.'/../../config/db.php';
	if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['id'],$_POST['role'])){
		$id=(int)$_POST['id'];$role=$_POST['role'];
		$stmt=$mysqli->prepare('UPDATE users SET role=? WHERE id=?');
		$stmt->bind_param('si',$role,$id);
		$stmt->execute();
	}
	$users=$mysqli->query('SELECT id,name,email,role FROM users ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2>Manage Users</h2>
	<table>
		<tr><th>Name</th><th>Email</th><th>Role</th><th>Change</th></tr>
		<?php foreach($users as $u):?>
		<tr>
			<td><?php echo htmlspecialchars($u['name']);?></td>
			<td><?php echo htmlspecialchars($u['email']);?></td>
			<td><?php echo htmlspecialchars($u['role']);?></td>
			<td>
				<form method="post" style="display:flex;gap:8px;align-items:center">
					<input type="hidden" name="id" value="<?php echo (int)$u['id'];?>">
					<select class="input" name="role">
						<?php foreach(['tenant','owner','admin'] as $r):?>
							<option value="<?php echo $r; ?>" <?php if($u['role']===$r) echo 'selected';?>><?php echo $r; ?></option>
						<?php endforeach; ?>
					</select>
					<button class="btn">Update</button>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


