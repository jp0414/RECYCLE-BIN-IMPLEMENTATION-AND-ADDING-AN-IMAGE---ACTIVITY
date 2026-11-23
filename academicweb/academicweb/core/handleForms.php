<?php

require_once 'dbConfig.php';
require_once 'models.php';

// ============================================================
// USER AUTHENTICATION HANDLING
// ============================================================

// Register a new user
if (isset($_POST['registerBtn'])) {

	$result = registerUser(
		$pdo,
		$_POST['firstName'],
		$_POST['lastName'],
		$_POST['username'],
		$_POST['password'],
		$_POST['email'],
		$_POST['address'],
		$_POST['age'],
		$_POST['phoneNumber']
	);

	if ($result === true) {
		header("Location: ../login.php?register=success");
		exit();
	} elseif ($result === "username_taken") {
		header("Location: ../register.php?error=username_taken");
		exit();
	} elseif ($result === "email_taken") {
		header("Location: ../register.php?error=email_taken");
		exit();
	} else {
		header("Location: ../register.php?error=failed");
		exit();
	}
}

// Login user
if (isset($_POST['loginBtn'])) {

	$user = loginUser($pdo, $_POST['username'], $_POST['password']);

	if ($user) {
		$_SESSION['user_id'] = $user['user_id'];
		$_SESSION['username'] = $user['username'];
		$_SESSION['first_name'] = $user['first_name'];
		$_SESSION['last_name'] = $user['last_name'];
		header("Location: ../index.php");
		exit();
	} else {
		header("Location: ../login.php?error=invalid");
		exit();
	}
}

// Logout user
if (isset($_GET['logout'])) {
	session_unset();
	session_destroy();
	header("Location: ../login.php?logout=success");
	exit();
}


// ============================================================
// TUTOR HANDLING (PARENT TABLE)
// ============================================================

// Insert a new Tutor
if (isset($_POST['insertTutorBtn'])) {

	$cleanFirstName = trim(strip_tags($_POST['firstName']));
	$cleanLastName = trim(strip_tags($_POST['lastName']));
	$cleanEmail = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
	$cleanPhone = trim(strip_tags($_POST['phoneNumber']));
	$cleanSpecialization = trim(strip_tags($_POST['specialization']));
	$cleanHourlyRate = floatval($_POST['hourlyRate']);

	$query = insertTutor(
		$pdo,
		$cleanFirstName,
		$cleanLastName,
		$cleanEmail,
		$cleanPhone,
		$cleanSpecialization,
		$cleanHourlyRate,
		$_SESSION['user_id']
	);

	if ($query) {
		header("Location: ../index.php");
		exit();
	} else {
		echo "Insertion failed";
	}
}

// Edit an existing Tutor
if (isset($_POST['editTutorBtn'])) {

	$query = updateTutor(
		$pdo,
		$_GET['tutor_id'],
		$_POST['firstName'],
		$_POST['lastName'],
		$_POST['email'],
		$_POST['phoneNumber'],
		$_POST['specialization'],
		$_POST['hourlyRate'],
		$_SESSION['user_id']
	);

	if ($query) {
		header("Location: ../index.php");
		exit();
	} else {
		echo "Edit failed";
	}
}

// Delete a Tutor
if (isset($_POST['deleteTutorBtn'])) {

	$query = deleteTutor($pdo, $_GET['tutor_id']);

	if ($query) {
		header("Location: ../index.php");
		exit();
	} else {
		echo "Deletion failed";
	}
}


// ============================================================
// SESSION HANDLING (CHILD TABLE)
// ============================================================

// Helper function: Handle image upload for sessions
function handleSessionImageUpload() {
	if (isset($_FILES['sessionImage']) && $_FILES['sessionImage']['error'] === UPLOAD_ERR_OK) {
		$uploadDir = __DIR__ . '/../uploads/';
		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0777, true);
		}
		$fileExt = strtolower(pathinfo($_FILES['sessionImage']['name'], PATHINFO_EXTENSION));
		$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

		if (in_array($fileExt, $allowedExts)) {
			$newFileName = 'session_' . time() . '_' . uniqid() . '.' . $fileExt;
			$destination = $uploadDir . $newFileName;

			if (move_uploaded_file($_FILES['sessionImage']['tmp_name'], $destination)) {
				return $newFileName;
			}
		}
	}
	return null;
}

// Insert a new Session under a specific Tutor (with image upload)
if (isset($_POST['insertSessionBtn']) || isset($_POST['insertNewSessionBtn'])) {

	$imageName = handleSessionImageUpload();

	$query = insertSession(
		$pdo,
		$_GET['tutor_id'],
		$_POST['sessionDate'],
		$_POST['startTime'],
		$_POST['endTime'],
		$_POST['subjectTaught'],
		$_POST['notes'],
		$_SESSION['user_id'],
		$imageName
	);

	if ($query) {
		header("Location: ../viewtutors.php?tutor_id=" . $_GET['tutor_id'] . "&added=success");
		exit();
	} else {
		echo "Insertion failed";
	}
}

// Edit an existing Session (with optional image update)
if (isset($_POST['editSessionBtn'])) {

	$imageName = handleSessionImageUpload();

	$query = updateSession(
		$pdo,
		$_GET['session_id'],
		$_POST['sessionDate'],
		$_POST['startTime'],
		$_POST['endTime'],
		$_POST['subjectTaught'],
		$_POST['notes'],
		$_SESSION['user_id'],
		$imageName
	);

	if ($query) {
		header("Location: ../viewtutors.php?tutor_id=" . $_GET['tutor_id'] . "&updated=success");
		exit();
	} else {
		echo "Update failed";
	}
}

// Soft-Delete a Session (moves to recycle bin, tracks deleted_by and deleted_at)
if (isset($_POST['deleteSessionBtn'])) {

	$query = softDeleteSession($pdo, $_GET['session_id'], $_SESSION['user_id']);

	if ($query) {
		header("Location: ../viewtutors.php?tutor_id=" . $_GET['tutor_id'] . "&deleted=success");
		exit();
	} else {
		echo "Deletion failed";
	}
}

// Restore a soft-deleted Session from recycle bin
if (isset($_POST['restoreSessionBtn'])) {

	$query = restoreSession($pdo, $_GET['session_id']);

	if ($query) {
		if (isset($_GET['tutor_id']) && !empty($_GET['tutor_id'])) {
			// Redirect back to viewtutors so the user immediately verifies it's present again in the table!
			header("Location: ../viewtutors.php?tutor_id=" . $_GET['tutor_id'] . "&restored=success");
		} else {
			header("Location: ../recyclebin.php?restored=success");
		}
		exit();
	} else {
		echo "Restore failed";
	}
}

?>
