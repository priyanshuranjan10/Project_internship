<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

if (!is_student_logged_in() || empty($_SESSION['just_registered_activities'])) {
    // If someone lands here directly without just registering, send them to the dashboard/register
    if (is_student_logged_in()) {
        redirect('dashboard.php');
    }
    redirect('register.php');
}

$assetBase = '';
$pageTitle = 'Registration Confirmed';
require_once __DIR__ . '/includes/header.php';

$activities = $_SESSION['just_registered_activities'];
$name = $_SESSION['student_name'] ?? 'Student';
unset($_SESSION['just_registered_activities']); // show this only once
?>

<section class="section">
  <div class="container">
    <div class="card confirm-box">
      <div class="check-circle">&#10003;</div>
      <h1>You're Registered, <?php echo htmlspecialchars($name); ?>!</h1>
      <p>Thank you for registering with UdaanFest. A summary of your chosen activities is below.
        You can review or update your registration any time from your dashboard.</p>

      <div class="chosen-list">
        <h3>Your Activities</h3>
        <ul>
          <?php foreach ($activities as $a): ?>
            <li>
              <?php echo htmlspecialchars($a['activity_name']); ?>
              <span class="badge <?php echo $a['activity_type'] === 'Co-Curricular' ? 'badge-cc' : 'badge-ec'; ?>">
                <?php echo htmlspecialchars($a['activity_type']); ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!--
        Bonus idea (Section 8): to email this confirmation, call PHP's mail()
        here with the student's email and the $activities list, e.g.:

        mail($studentEmail, 'UdaanFest Registration Confirmed',
             "You're registered for: " . implode(', ', array_column($activities, 'activity_name')));

        mail() requires a configured SMTP/sendmail setup on your server, so it's
        left as an optional enhancement rather than a hard dependency here.
      -->

      <div style="margin-top:28px;">
        <a href="dashboard.php" class="btn btn-primary">Go to My Dashboard</a>
        <a href="index.html" class="btn btn-outline" style="color:var(--indigo);border-color:var(--indigo);">Back to Home</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
