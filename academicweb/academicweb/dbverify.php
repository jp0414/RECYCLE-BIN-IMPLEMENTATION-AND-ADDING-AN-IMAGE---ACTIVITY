<?php
require_once 'core/dbConfig.php';
require_once 'core/models.php';

if (!isset($_SESSION['user_id'])) {
	header("Location: login.php");
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Database Verification — Table Structure & Audit Trail</title>
	<link rel="stylesheet" href="styles.css">
</head>
<body>
	<div class="top-bar">
		<div class="user-info">
			<span class="user-badge">👤 Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
			<span class="user-name">(<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>)</span>
		</div>
		<div class="top-bar-actions">
			<a href="index.php" class="home-btn" style="background: rgba(255, 255, 255, 0.2); color: #fff; padding: 8px 18px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.3);">🏠 Home</a>
			<a href="recyclebin.php" class="recycle-btn">🗑️ Recycle Bin</a>
			<a href="core/handleForms.php?logout=true" class="logout-btn">Logout</a>
		</div>
	</div>

	<p><a href="index.php">&larr; Return to Home</a></p>

	<h1 style="margin-top: 15px;">Database Verification — Sessions Table Structure & Audit Columns</h1>
	<p style="color: var(--text-muted); margin-bottom: 20px;">
		This page verifies the database updates (such as in phpMyAdmin): image column per child entity, soft-delete tracking (<code>is_deleted</code>, <code>deleted_by</code>, <code>deleted_at</code>), and audit metadata.
	</p>

	<h2>1. Schema of `sessions` Table (Child Entity)</h2>
	<table style="width:100%; margin-top: 10px; margin-bottom: 30px;">
		<tr>
			<th>Field Name</th>
			<th>Data Type</th>
			<th>Null</th>
			<th>Key</th>
			<th>Default</th>
			<th>Purpose / Description</th>
		</tr>
		<?php
		$cols = $pdo->query("DESCRIBE sessions")->fetchAll(PDO::FETCH_ASSOC);
		$descriptions = [
			'session_id' => 'Primary Key (Auto Increment)',
			'tutor_id' => 'Foreign Key referencing tutors(tutor_id) - Parent Entity',
			'session_date' => 'Date when the tutoring lesson occurs',
			'start_time' => 'Lesson start time',
			'end_time' => 'Lesson end time',
			'subject_taught' => 'Subject or topic covered',
			'notes' => 'Detailed session notes / homework',
			'image' => '⭐️ ONE IMAGE PER CHILD ENTITY: filename of uploaded image in /uploads/',
			'date_added' => 'Timestamp when record was created (DEFAULT CURRENT_TIMESTAMP)',
			'added_by' => 'User ID who created the record',
			'last_updated_by' => 'User ID who last edited the record',
			'last_updated' => 'Timestamp of last update (CURRENT_TIMESTAMP ON UPDATE)',
			'is_deleted' => '🗑️ Soft-Delete Flag (0 = Active, 1 = Moved to Recycle Bin)',
			'deleted_by' => '🗑️ User ID who deleted the record',
			'deleted_at' => '🗑️ Timestamp when the record was soft-deleted'
		];
		foreach ($cols as $col) {
			$highlight = in_array($col['Field'], ['image', 'is_deleted', 'deleted_by', 'deleted_at']) ? 'background: #eff6ff;' : '';
		?>
		<tr style="<?php echo $highlight; ?>">
			<td><strong><code><?php echo htmlspecialchars($col['Field']); ?></code></strong></td>
			<td><code><?php echo htmlspecialchars($col['Type']); ?></code></td>
			<td><?php echo htmlspecialchars($col['Null']); ?></td>
			<td><?php echo htmlspecialchars($col['Key']); ?></td>
			<td><?php echo htmlspecialchars($col['Default'] ?? 'None'); ?></td>
			<td><?php echo htmlspecialchars($descriptions[$col['Field']] ?? ''); ?></td>
		</tr>
		<?php } ?>
	</table>

	<h2>2. All Sessions in Database (Raw Table Data)</h2>
	<table style="width:100%; margin-top: 10px;">
		<tr>
			<th>ID</th>
			<th>Tutor</th>
			<th>Subject</th>
			<th>Image File</th>
			<th>is_deleted</th>
			<th>deleted_by</th>
			<th>deleted_at</th>
			<th>added_by</th>
		</tr>
		<?php
		$all = $pdo->query("SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) AS tutor_name, u.username AS del_user FROM sessions s INNER JOIN tutors t ON s.tutor_id = t.tutor_id LEFT JOIN users u ON s.deleted_by = u.user_id ORDER BY s.session_id DESC")->fetchAll(PDO::FETCH_ASSOC);
		foreach ($all as $r) {
			$statusStyle = $r['is_deleted'] ? 'background: #fef2f2; color: #991b1b;' : '';
		?>
		<tr style="<?php echo $statusStyle; ?>">
			<td><strong>#<?php echo htmlspecialchars($r['session_id']); ?></strong></td>
			<td><?php echo htmlspecialchars($r['tutor_name']); ?></td>
			<td><?php echo htmlspecialchars($r['subject_taught']); ?></td>
			<td><code><?php echo htmlspecialchars($r['image'] ?: 'None'); ?></code></td>
			<td><strong><?php echo $r['is_deleted'] ? '1 (Deleted)' : '0 (Active)'; ?></strong></td>
			<td><?php echo htmlspecialchars($r['del_user'] ? $r['del_user'] . ' (ID: ' . $r['deleted_by'] . ')' : 'None'); ?></td>
			<td><?php echo htmlspecialchars($r['deleted_at'] ?: 'None'); ?></td>
			<td>ID: <?php echo htmlspecialchars($r['added_by']); ?></td>
		</tr>
		<?php } ?>
	</table>
</body>
</html>
