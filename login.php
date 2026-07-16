<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';

if (is_student_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // email or roll number
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Please enter your email/roll number and password.';
    } else {
        $stmt = $conn->prepare('SELECT student_id, name, password FROM students WHERE email = ? OR roll_no = ?');
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['student_id'] = $row['student_id'];
                $_SESSION['student_name'] = $row['name'];
                redirect('dashboard.php');
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No registration found with that email/roll number.';
        }
        $stmt->close();
    }
}

$assetBase = '';
$pageTitle = 'Student Login';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <h1 class="section-title">Student Login</h1>
    <p class="section-sub">View or update the activities you've registered for.</p>

    <div class="card" style="max-width:440px;">
      <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="field" style="margin-bottom:16px;">
          <label for="identifier">Email or Roll Number</label>
          <input type="text" id="identifier" name="identifier" required>
        </div>
        <div class="field" style="margin-bottom:16px;">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <div class="form-actions" style="text-align:left;">
          <button type="submit" class="btn btn-primary">Log In</button>
        </div>
      </form>
      <p style="margin-top:16px;font-size:0.88rem;">New here? <a href="register.php">Register now</a>.</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
