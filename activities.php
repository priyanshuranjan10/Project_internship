<?php
require_once __DIR__ . '/includes/db.php';
$assetBase = '';
$pageTitle = 'Activities';
require_once __DIR__ . '/includes/header.php';

// Fetch all activities from MySQL — never hardcoded in HTML
$result = $conn->query("SELECT activity_id, activity_name, activity_type, category, level
                         FROM activities ORDER BY activity_type, category, activity_name");

$cc = [];
$ec = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['activity_type'] === 'Co-Curricular') {
            $cc[] = $row;
        } else {
            $ec[] = $row;
        }
    }
}
?>

<section class="section">
  <div class="container">
    <h1 class="section-title">All Activities</h1>
    <p class="section-sub">Pulled live from the <code>activities</code> table — update the
      database and this page updates automatically.</p>

    <?php if (!$result): ?>
      <div class="alert alert-error">
        Could not load activities from the database. Make sure the <code>activities</code>
        table has been created and imported (see <code>sql/schema.sql</code> and
        <code>data/activities_import.csv</code>).
      </div>
    <?php else: ?>

    <h2 style="margin-top:40px;">Co-Curricular <span style="color:#8a8298;font-size:0.9rem;">(<?php echo count($cc); ?>)</span></h2>
    <div class="activity-grid">
      <?php foreach ($cc as $a): ?>
        <div class="activity-chip">
          <div class="a-name"><?php echo htmlspecialchars($a['activity_name']); ?></div>
          <div class="a-meta"><?php echo htmlspecialchars($a['category']); ?> · <?php echo htmlspecialchars($a['level']); ?></div>
          <span class="badge badge-cc">Co-Curricular</span>
        </div>
      <?php endforeach; ?>
    </div>

    <h2 style="margin-top:44px;">Extra-Curricular <span style="color:#8a8298;font-size:0.9rem;">(<?php echo count($ec); ?>)</span></h2>
    <div class="activity-grid">
      <?php foreach ($ec as $a): ?>
        <div class="activity-chip">
          <div class="a-name"><?php echo htmlspecialchars($a['activity_name']); ?></div>
          <div class="a-meta"><?php echo htmlspecialchars($a['category']); ?> · <?php echo htmlspecialchars($a['level']); ?></div>
          <span class="badge badge-ec">Extra-Curricular</span>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:40px;">
      <a href="register.php" class="btn btn-primary">Register for These Activities</a>
    </div>

    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
