<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

if (!is_student_logged_in()) {
    redirect('login.php');
}

$student_id = $_SESSION['student_id'];

// Fetch student + college info
$stmt = $conn->prepare('SELECT s.*, c.college_name, c.city, c.state
                         FROM students s JOIN colleges c ON s.college_id = c.college_id
                         WHERE s.student_id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    redirect('logout.php');
}

// Fetch this student's currently-registered activity IDs
$currentIds = [];
$stmt = $conn->prepare('SELECT activity_id FROM registrations WHERE student_id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $currentIds[] = $row['activity_id'];
}
$stmt->close();

// All activities, grouped, for the edit form
$cc_result = $conn->query("SELECT activity_id, activity_name, category FROM activities WHERE activity_type='Co-Curricular' ORDER BY category, activity_name");
$ec_result = $conn->query("SELECT activity_id, activity_name, category FROM activities WHERE activity_type='Extra-Curricular' ORDER BY category, activity_name");

$assetBase = '';
$pageTitle = 'My Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <h1 class="section-title">Welcome, <?php echo htmlspecialchars($student['name']); ?></h1>
    <p class="section-sub">
      <?php echo htmlspecialchars($student['college_name']); ?> ·
      <?php echo htmlspecialchars($student['course']); ?>, <?php echo htmlspecialchars($student['year_sem']); ?>
    </p>

    <?php if (!empty($_SESSION['dash_message'])): ?>
      <div class="alert alert-success" style="max-width:760px;margin:0 auto 20px;">
        <?php echo htmlspecialchars($_SESSION['dash_message']); unset($_SESSION['dash_message']); ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2>Update My Activities</h2>
      <p style="color:#5c5470;font-size:0.9rem;">Tick or untick activities below and save — this
        directly updates the <code>registrations</code> table.</p>

      <form action="update_registration.php" method="POST">
        <fieldset class="activity-fieldset" style="margin-bottom:18px;">
          <legend>Co-Curricular Activities</legend>
          <div class="checkbox-grid">
            <?php while ($a = $cc_result->fetch_assoc()): ?>
              <label>
                <input type="checkbox" name="activities[]" value="<?php echo htmlspecialchars($a['activity_id']); ?>"
                  <?php echo in_array($a['activity_id'], $currentIds, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($a['activity_name']); ?>
              </label>
            <?php endwhile; ?>
          </div>
        </fieldset>

        <fieldset class="activity-fieldset">
          <legend>Extra-Curricular Activities</legend>
          <div class="checkbox-grid">
            <?php while ($a = $ec_result->fetch_assoc()): ?>
              <label>
                <input type="checkbox" name="activities[]" value="<?php echo htmlspecialchars($a['activity_id']); ?>"
                  <?php echo in_array($a['activity_id'], $currentIds, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($a['activity_name']); ?>
              </label>
            <?php endwhile; ?>
          </div>
        </fieldset>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:30px;">
      <h2>My Details</h2>
      <table class="data-table">
        <tr><th>Roll Number</th><td><?php echo htmlspecialchars($student['roll_no']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($student['email']); ?></td></tr>
        <tr><th>Mobile</th><td><?php echo htmlspecialchars($student['mobile']); ?></td></tr>
        <tr><th>Gender</th><td><?php echo htmlspecialchars($student['gender']); ?></td></tr>
        <tr><th>College</th><td><?php echo htmlspecialchars($student['college_name']); ?>, <?php echo htmlspecialchars($student['city']); ?>, <?php echo htmlspecialchars($student['state']); ?></td></tr>
      </table>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
