<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

// Authentication Guard
if (!isset($_SESSION['user_id'])) {
	header("Location: login.php");
	exit();
}

$tutor_id = $_GET['tutor_id'] ?? null;
$tutor = null;

if (!empty($tutor_id)) {
	$tutor = getTutorByID($pdo, $tutor_id);
}

// If tutor_id is provided and tutor exists, get sessions for this tutor; otherwise get ALL deleted sessions
if ($tutor) {
	$deletedSessions = getDeletedSessionsByTutor($pdo, $tutor_id);
	$pageTitle = "Recycle Bin — Deleted Sessions for Tutor: " . htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']);
} else {
	$deletedSessions = getAllDeletedSessions($pdo);
	$pageTitle = "Recycle Bin — All Deleted Tutoring Sessions";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $pageTitle; ?></title>
	<link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Top bar showing active logged-in user profile details and logout button -->
	<div class="top-bar">
		<div class="user-info">
			<span class="user-badge">👤 Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
			<span class="user-name">(<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>)</span>
		</div>
		<div class="top-bar-actions">
			<a href="index.php" class="home-btn" style="background: rgba(255, 255, 255, 0.2); color: #fff; padding: 8px 18px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.3);">🏠 Home</a>
			<a href="core/handleForms.php?logout=true" class="logout-btn">Logout</a>
		</div>
	</div>

    <!-- Navigation links -->
	<div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
		<?php if ($tutor) { ?>
			<p style="margin: 0;"><a href="viewtutors.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" style="font-weight: 600;">&larr; Return to <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?>'s Sessions</a></p>
			<span style="color: var(--border);">|</span>
			<p style="margin: 0;"><a href="recyclebin.php" style="color: var(--text-muted); font-weight: 500;">View All Deleted Sessions Across All Tutors</a></p>
		<?php } else { ?>
			<p style="margin: 0;"><a href="index.php" style="font-weight: 600;">&larr; Return to Home</a></p>
		<?php } ?>
	</div>

	<?php if (isset($_GET['restored']) && $_GET['restored'] === 'success') { ?>
		<div class="alert alert-success">
			✅ <strong>Record Restored Successfully!</strong> The session has been returned to the active list.
		</div>
	<?php } ?>

	<div style="background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 25px;">
		<h1 style="color: var(--danger); margin-bottom: 8px;">🗑️ <?php echo $pageTitle; ?></h1>
		<p style="color: var(--text-muted); margin: 0;">
			This page displays records that have been soft-deleted. You can inspect <strong>who deleted the record</strong> and <strong>the exact timestamp it was deleted</strong>, and use the <strong>Restore</strong> action to recover the record back into the active table.
		</p>
	</div>

	<!-- Deleted Sessions Table -->
	<table style="width:100%; margin-top: 10px;">
		<tr>
			<th>Session ID</th>
			<th>Image</th>
			<th>Subject Taught</th>
			<th>Tutor</th>
			<th>Session Date</th>
			<th>Time</th>
			<th>Notes</th>
			<th style="background: #b91c1c;">Deleted By</th>
			<th style="background: #b91c1c;">Date & Time Deleted</th>
			<th style="text-align: center;">Action</th>
		</tr>
		<?php if (empty($deletedSessions)) { ?>
		<tr>
			<td colspan="10" style="text-align: center; color: var(--text-muted); padding: 30px;">
				🎉 <strong>Recycle bin is empty.</strong> No deleted sessions found.
			</td>
		</tr>
		<?php } else { ?>
			<?php foreach ($deletedSessions as $row) { ?>
			<tr>
				<td><strong>#<?php echo htmlspecialchars($row['session_id']); ?></strong></td>
				<td>
					<?php if (!empty($row['image'])) { ?>
						<img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Session Image" style="width: 70px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border);">
					<?php } else { ?>
						<span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">No image</span>
					<?php } ?>
				</td>
				<td><strong style="color: var(--text);"><?php echo htmlspecialchars($row['subject_taught']); ?></strong></td>	  	
				<td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
				<td><?php echo htmlspecialchars($row['session_date']); ?></td>	  	
				<td><?php echo htmlspecialchars(date("g:i A", strtotime($row['start_time'])) . ' - ' . date("g:i A", strtotime($row['end_time']))); ?></td>	  	
				<td><?php echo htmlspecialchars($row['notes'] ?: 'None'); ?></td>
				<td style="background: #fef2f2;">
					<strong style="color: var(--danger); font-size: 0.95rem;">👤 <?php echo htmlspecialchars($row['deleted_by_username'] ?? 'User ID: ' . $row['deleted_by']); ?></strong>
					<br>
					<small style="color: var(--text-muted);">(User ID: <?php echo htmlspecialchars($row['deleted_by']); ?>)</small>
				</td>
				<td style="background: #fef2f2;">
					<strong style="color: var(--danger); font-size: 0.9rem;">🕐 <?php echo htmlspecialchars($row['deleted_at']); ?></strong>
				</td>
				<td style="text-align: center;">
					<form action="core/handleForms.php?session_id=<?php echo htmlspecialchars($row['session_id']); ?>&tutor_id=<?php echo htmlspecialchars($row['tutor_id']); ?>" method="POST" style="background: none; border: none; padding: 0; box-shadow: none; margin: 0; display: inline;">
						<input type="submit" name="restoreSessionBtn" value="♻️ Restore Record" style="background: var(--success); font-size: 0.85rem; height: 38px; padding: 0 16px; margin: 0; box-shadow: var(--shadow);">
					</form>
				</td>	  	
			</tr>
			<?php } ?>
		<?php } ?>
	</table>
</body>
</html>
