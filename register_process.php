<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

function back_with_error($msg) {
    $_SESSION['reg_error'] = $msg;
    redirect('register.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

// ---------- Collect & sanitise input ----------
$name      = trim($_POST['name'] ?? '');
$roll_no   = trim($_POST['roll_no'] ?? '');
$email     = trim($_POST['email'] ?? '');
$mobile    = trim($_POST['mobile'] ?? '');
$gender    = trim($_POST['gender'] ?? 'Prefer not to say');
$course    = trim($_POST['course'] ?? '');
$year_sem  = trim($_POST['year_sem'] ?? '');
$password  = $_POST['password'] ?? '';
$college_id_raw = $_POST['college_id'] ?? '';
$new_college_name = trim($_POST['new_college_name'] ?? '');
$new_city  = trim($_POST['new_city'] ?? '');
$cc_activities = $_POST['cc_activities'] ?? [];
$ec_activities = $_POST['ec_activities'] ?? [];
$terms     = isset($_POST['terms']);

// ---------- SERVER-SIDE VALIDATION (never trust the client) ----------
if ($name === '' || $roll_no === '' || $email === '' || $mobile === '' ||
    $course === '' || $year_sem === '' || $password === '') {
    back_with_error('Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_with_error('Please enter a valid email address.');
}

if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    back_with_error('Please enter a valid 10-digit mobile number.');
}

if (strlen($password) < 6) {
    back_with_error('Password must be at least 6 characters long.');
}

$allowed_genders = ['Male', 'Female', 'Other', 'Prefer not to say'];
if (!in_array($gender, $allowed_genders, true)) {
    $gender = 'Prefer not to say';
}

$all_activities = array_merge($cc_activities, $ec_activities);
if (count($all_activities) === 0) {
    back_with_error('Please select at least one Co-Curricular or Extra-Curricular activity.');
}

if (!$terms) {
    back_with_error('You must agree to the Terms & Conditions.');
}

// ---------- Resolve college (existing or newly-typed) ----------
$college_id = null;

if ($college_id_raw === '__new__') {
    if ($new_college_name === '' || $new_city === '') {
        back_with_error('Please provide your college name and city/state.');
    }
    // Split "City, State" if provided that way; otherwise store as city with blank state
    $parts = array_map('trim', explode(',', $new_city, 2));
    $city  = $parts[0];
    $state = $parts[1] ?? $parts[0];

    // Avoid creating duplicate college rows
    $stmt = $conn->prepare('SELECT college_id FROM colleges WHERE college_name = ? AND city = ?');
    $stmt->bind_param('ss', $new_college_name, $city);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $college_id = (int)$row['college_id'];
    } else {
        $stmt2 = $conn->prepare('INSERT INTO colleges (college_name, city, state) VALUES (?, ?, ?)');
        $stmt2->bind_param('sss', $new_college_name, $city, $state);
        $stmt2->execute();
        $college_id = $stmt2->insert_id;
        $stmt2->close();
    }
    $stmt->close();
} else {
    $college_id = (int)$college_id_raw;
    if ($college_id <= 0) {
        back_with_error('Please select your college.');
    }
}

// ---------- Duplicate registration check (email / roll number) ----------
$stmt = $conn->prepare('SELECT student_id FROM students WHERE email = ? OR roll_no = ?');
$stmt->bind_param('ss', $email, $roll_no);
$stmt->execute();
$dupResult = $stmt->get_result();
if ($dupResult->num_rows > 0) {
    back_with_error('A registration with this email or roll number already exists. Please log in instead.');
}
$stmt->close();

// ---------- Handle optional ID card upload ----------
$id_card_path = null;
if (isset($_FILES['id_card']) && $_FILES['id_card']['error'] === UPLOAD_ERR_OK) {
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    $orig_name = $_FILES['id_card']['name'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed_ext, true) && $_FILES['id_card']['size'] <= 5 * 1024 * 1024) {
        $safe_name = 'idcard_' . preg_replace('/[^A-Za-z0-9_]/', '_', $roll_no) . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/uploads/' . $safe_name;
        if (move_uploaded_file($_FILES['id_card']['tmp_name'], $dest)) {
            $id_card_path = 'uploads/' . $safe_name;
        }
    }
}

// ---------- Insert student (hashed password) ----------
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO students
    (name, roll_no, email, password, mobile, college_id, course, year_sem, gender, id_card_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssissss',
    $name, $roll_no, $email, $hashed_password, $mobile,
    $college_id, $course, $year_sem, $gender, $id_card_path
);

if (!$stmt->execute()) {
    back_with_error('Could not save your registration. Please try again.');
}
$student_id = $stmt->insert_id;
$stmt->close();

// ---------- Insert activity registrations (junction table) ----------
$stmt = $conn->prepare('INSERT INTO registrations (student_id, activity_id) VALUES (?, ?)');
$chosen_names = [];
foreach ($all_activities as $activity_id) {
    $activity_id = trim($activity_id);
    if ($activity_id === '') continue;
    $stmt->bind_param('is', $student_id, $activity_id);
    $stmt->execute(); // UNIQUE KEY on (student_id, activity_id) guards against duplicates
}
$stmt->close();

// Fetch chosen activity names for the confirmation page
$namesStmt = $conn->prepare("SELECT activity_name, activity_type FROM activities WHERE activity_id = ?");
foreach ($all_activities as $activity_id) {
    $activity_id = trim($activity_id);
    if ($activity_id === '') continue;
    $namesStmt->bind_param('s', $activity_id);
    $namesStmt->execute();
    $r = $namesStmt->get_result();
    if ($row = $r->fetch_assoc()) {
        $chosen_names[] = $row;
    }
}
$namesStmt->close();

// ---------- Log the student in & redirect to confirmation ----------
$_SESSION['student_id'] = $student_id;
$_SESSION['student_name'] = $name;
$_SESSION['just_registered_activities'] = $chosen_names;

redirect('confirmation.php');
