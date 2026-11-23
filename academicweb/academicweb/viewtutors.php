<?php // Include database configuration and data models
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

// Authentication Guard: Ensure user is logged in before viewing tutor sessions
if (!isset($_SESSION['user_id'])) {
	header("Location: login.php");
	exit();
}

// Retrieve tutor ID from query parameters and fetch tutor profile
$tutor_id = $_GET['tutor_id'] ?? null;
$tutor = getTutorByID($pdo, $tutor_id); 

// Guard clause: stop execution if tutor does not exist
if (!$tutor) {
	header("Location: index.php");
	exit();
}

$deletedCountForTutor = countDeletedSessions($pdo, $tutor_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Tutoring Sessions - <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></title>
	<link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Top bar showing active logged-in user profile details and actions -->
	<div class="top-bar">
		<div class="user-info">
			<span class="user-badge">👤 Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
			<span class="user-name">(<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>)</span>
		</div>
		<div class="top-bar-actions" style="display: flex; gap: 10px; align-items: center;">
			<a href="index.php" class="home-btn" style="background: rgba(255, 255, 255, 0.2); color: #fff; padding: 8px 18px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.3);">🏠 Home</a>
			<a href="recyclebin.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" class="recycle-btn">
				🗑️ Recycle Bin <?php if ($deletedCountForTutor > 0) { ?><span style="background: var(--danger); color: #fff; padding: 2px 7px; border-radius: 10px; font-size: 0.75rem; margin-left: 4px;"><?php echo $deletedCountForTutor; ?></span><?php } ?>
			</a>
			<a href="core/handleForms.php?logout=true" class="logout-btn">Logout</a>
		</div>
	</div>

    <!-- Navigation link back to main tutor directory -->
	<p><a href="index.php">&larr; Return to Home</a></p>

	<!-- Notifications / Alerts -->
	<?php if (isset($_GET['restored']) && $_GET['restored'] === 'success') { ?>
		<div class="alert alert-success">
			✅ <strong>Record Restored!</strong> The tutoring session has been successfully restored and is present once again in the table below.
		</div>
	<?php } ?>

	<?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'success') { ?>
		<div class="alert alert-danger" style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;">
			🗑️ <strong>Record Deleted!</strong> The session has been moved to the <a href="recyclebin.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" style="font-weight: 700; text-decoration: underline; color: #991b1b;">Recycle Bin</a>. You can inspect who deleted it, when it was deleted, or restore it anytime.
		</div>
	<?php } ?>

	<?php if (isset($_GET['added']) && $_GET['added'] === 'success') { ?>
		<div class="alert alert-success">
			✅ <strong>New Session Added!</strong> The session and attached image are now recorded.
		</div>
	<?php } ?>

	<?php if (isset($_GET['updated']) && $_GET['updated'] === 'success') { ?>
		<div class="alert alert-success">
			✅ <strong>Session Updated Successfully!</strong>
		</div>
	<?php } ?>

    <!-- Parent tutor overview summary -->
	<div style="background: var(--surface); padding: 20px 24px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); margin-top: 15px; margin-bottom: 25px;">
		<h1 style="margin: 0 0 6px 0;">Tutor: <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h1>
		<p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
			<strong>Specialization:</strong> <?php echo htmlspecialchars($tutor['specialization']); ?> &nbsp;|&nbsp; 
			<strong>Email:</strong> <?php echo htmlspecialchars($tutor['email']); ?> &nbsp;|&nbsp; 
			<strong>Hourly Rate:</strong> $<?php echo htmlspecialchars(number_format($tutor['hourly_rate'], 2)); ?>/hr
		</p>
	</div>

    <!-- Form for logging a new tutoring session under this tutor (with image upload) -->
	<h2>Add New Tutoring Session (Child Entity)</h2>
	<form action="core/handleForms.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" method="POST" enctype="multipart/form-data">
		<p>
			<label for="subjectTaught">Subject Taught</label> 
			<input type="text" name="subjectTaught" id="subjectTaught" placeholder="e.g. Calculus I, SAT Math, Physics" required>
		</p>
		<p>
			<label for="sessionDate">Session Date</label> 
			<input type="date" name="sessionDate" id="sessionDate" required>
		</p>
		<p>
			<label for="startTime">Start Time</label> 
			<input type="time" name="startTime" id="startTime" required>
		</p>
		<p>
			<label for="endTime">End Time</label> 
			<input type="time" name="endTime" id="endTime" required>
		</p>
		<p>
			<label for="notes">Session Notes</label> 
			<input type="text" name="notes" id="notes" placeholder="Topics covered, student progress, homework assigned">
		</p>
		<p>
			<label for="sessionImage">Session Image (One Image Per Child Entity)</label> 
			<input type="file" name="sessionImage" id="sessionImage" accept="image/*" style="max-width: 400px;">
			<small style="color: var(--text-muted);">Attach an image for this session (e.g. worksheet, photo of notes, student work).</small>
		</p>
		<p>
			<input type="submit" name="insertSessionBtn" value="Add Session">
		</p>
	</form>

    <!-- Tutoring sessions data table with audit logs -->
	<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; margin-bottom: 12px;">
		<h2>Tutoring Sessions List (Active Records)</h2>
		<a href="recyclebin.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: #fef3c7; color: #b45309; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; text-decoration: none; border: 1px solid #fde68a;">
			🗑️ View Recycle Bin <?php if ($deletedCountForTutor > 0) { ?><span style="background: var(--danger); color: #fff; padding: 1px 7px; border-radius: 10px; font-size: 0.75rem;"><?php echo $deletedCountForTutor; ?></span><?php } ?>
		</a>
	</div>

	<!-- Search form for finding sessions by subject, notes, or date -->
	<form action="viewtutors.php" method="GET" class="search-form" style="display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px;">
		<input type="hidden" name="tutor_id" value="<?php echo htmlspecialchars($tutor_id); ?>">
		<p style="flex: 1; margin-bottom: 0;">
			<label for="search">Search Sessions</label>
			<input type="text" name="search" id="search" placeholder="Search by subject, notes, or date..." 
				   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="max-width: 100%;">
		</p>
		<p style="margin-bottom: 0;">
			<input type="submit" value="🔍 Search">
		</p>
		<?php if (!empty($_GET['search'])) { ?>
		<p style="margin-bottom: 0;">
			<a href="viewtutors.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" style="display: inline-block; padding: 10px 20px; background: var(--text-muted); color: #fff; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none;">✕ Clear</a>
		</p>
		<?php } ?>
	</form>

	<?php 
	$searchKeyword = $_GET['search'] ?? '';
	if (!empty($searchKeyword)) {
		$sessions = searchSessionsByTutor($pdo, $tutor_id, $searchKeyword);
	} else {
		$sessions = getSessionsByTutor($pdo, $tutor_id);
	}
	?>

	<?php if (!empty($searchKeyword)) { ?>
		<p style="color: var(--text-muted); margin-bottom: 10px;">
			🔍 Search results for "<strong><?php echo htmlspecialchars($searchKeyword); ?></strong>" 
			— <?php echo count($sessions); ?> active session(s) found.
		</p>
	<?php } ?>

	<table style="width:100%; margin-top: 15px;">
		<tr>
			<th>Session ID</th>
			<th>Image</th>
			<th>Subject Taught</th>
			<th>Date</th>
			<th>Time</th>
			<th>Notes</th>
			<th>Tutor</th>
			<th>Date Added</th>
			<th>Added By</th>
			<th>Last Updated By</th>
			<th>Last Updated</th>
			<th>Action</th>
		</tr>
		<?php 
		if (empty($sessions)) {
		?>
		<tr>
			<td colspan="12" style="text-align: center; color: var(--text-muted); padding: 30px;">
				No active sessions found for this tutor. 
				<?php if ($deletedCountForTutor > 0) { ?>
					<br><a href="recyclebin.php?tutor_id=<?php echo htmlspecialchars($tutor_id); ?>" style="color: var(--primary); font-weight: 600;">Check Recycle Bin (<?php echo $deletedCountForTutor; ?> deleted record(s) available for restore)</a>
				<?php } ?>
			</td>
		</tr>
		<?php } else { ?>
			<?php 
			foreach ($sessions as $row) { ?>
			<tr>
				<td><strong>#<?php echo htmlspecialchars($row['session_id']); ?></strong></td>
				<td>
					<?php if (!empty($row['image'])) { ?>
						<a href="uploads/<?php echo htmlspecialchars($row['image']); ?>" target="_blank" title="Click to open full image">
							<img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Session Image" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
						</a>
					<?php } else { ?>
						<span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">No image</span>
					<?php } ?>
				</td>
				<td><strong><?php echo htmlspecialchars($row['subject_taught']); ?></strong></td>	  	
				<td><?php echo htmlspecialchars($row['session_date']); ?></td>	  	
				<td><?php echo htmlspecialchars(date("g:i A", strtotime($row['start_time'])) . ' - ' . date("g:i A", strtotime($row['end_time']))); ?></td>	  	
				<td><?php echo htmlspecialchars($row['notes'] ?: 'None'); ?></td>
				<td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
				<td><?php echo htmlspecialchars($row['date_added']); ?></td>
				<td><?php echo htmlspecialchars($row['added_by_username'] ?? 'Unknown'); ?></td>
				<td><?php echo htmlspecialchars($row['last_updated_by_username'] ?? $row['added_by_username'] ?? 'Unknown'); ?></td>
				<td><?php echo htmlspecialchars($row['last_updated'] ?? 'N/A'); ?></td>
				<td>
					<a href="editsessions.php?session_id=<?php echo $row['session_id']; ?>&tutor_id=<?php echo $tutor_id; ?>">Edit</a>
					<a href="deletesessions.php?session_id=<?php echo $row['session_id']; ?>&tutor_id=<?php echo $tutor_id; ?>">Delete</a>
				</td>	  	
			</tr>
			<?php } ?>
		<?php } ?>
	</table>
</body>
</html>
