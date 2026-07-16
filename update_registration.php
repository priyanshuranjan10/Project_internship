<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

if (!is_student_logged_in()) {
    redirect('login.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

$student_id = $_SESSION['student_id'];
$selected = $_POST['activities'] ?? [];
$selected = array_values(array_unique(array_map('trim', $selected)));

// Validate the submitted activity IDs actually exist (defence in depth)
$valid_ids = [];
if (!empty($selected)) {
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $types = str_repeat('s', count($selected));
    $stmt = $conn->prepare("SELECT activity_id FROM activities WHERE activity_id IN ($placeholders)");
    $stmt->bind_param($types, ...$selected);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $valid_ids[] = $row['activity_id'];
    }
    $stmt->close();
}

// Simplest safe approach: remove all existing registrations for this student,
// then re-insert the validated selection. Wrapped in a transaction.
$conn->begin_transaction();
try {
    $del = $conn->prepare('DELETE FROM registrations WHERE student_id = ?');
    $del->bind_param('i', $student_id);
    $del->execute();
    $del->close();

    if (!empty($valid_ids)) {
        $ins = $conn->prepare('INSERT INTO registrations (student_id, activity_id) VALUES (?, ?)');
        foreach ($valid_ids as $activity_id) {
            $ins->bind_param('is', $student_id, $activity_id);
            $ins->execute();
        }
        $ins->close();
    }

    $conn->commit();
    $_SESSION['dash_message'] = 'Your activity selections have been updated.';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['dash_message'] = 'Something went wrong updating your activities. Please try again.';
}

redirect('dashboard.php');
