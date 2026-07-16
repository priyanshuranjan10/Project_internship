<?php
/**
 * ONE-TIME SETUP SCRIPT
 * ---------------------
 * Run this once in the browser (e.g. http://localhost/udaanfest/setup.php)
 * after you have:
 *   1. Created the database by importing sql/schema.sql in phpMyAdmin.
 *   2. Confirmed includes/db.php has the right host/user/pass/dbname.
 *
 * It will:
 *   - Import data/activities_import.csv into the `activities` table using
 *     fgetcsv(), as required by Section 5 of the assignment.
 *   - Seed a few sample colleges (including one in Katihar, Bihar) so the
 *     registration form's college dropdown isn't empty.
 *   - Create the default admin account (admin / Admin@123) using
 *     password_hash(), since a hardcoded hash isn't portable across installs.
 *
 * Safe to re-run: it skips rows/records that already exist.
 * DELETE this file (or password-protect it) once your project is set up,
 * so it isn't left open on a live/demo server.
 */

require_once __DIR__ . '/includes/db.php';

$log = [];

// ---------- 1. Import activities from CSV using fgetcsv() ----------
$csvPath = __DIR__ . '/data/activities_import.csv';
$imported = 0;
$skipped = 0;

if (($handle = fopen($csvPath, 'r')) !== false) {
    $header = fgetcsv($handle); // skip header row: activity_id, activity_name, activity_type, category, level
    $stmt = $conn->prepare('INSERT INTO activities (activity_id, activity_name, activity_type, category, level)
                             VALUES (?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE activity_name = VALUES(activity_name)');
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 5) continue;
        [$id, $aname, $atype, $cat, $lvl] = $row;
        $stmt->bind_param('sssss', $id, $aname, $atype, $cat, $lvl);
        $stmt->execute();
        $imported++;
    }
    $stmt->close();
    fclose($handle);
    $log[] = "Imported/updated $imported activities from CSV.";
} else {
    $log[] = "ERROR: could not open $csvPath";
}

// ---------- 2. Seed a few sample colleges ----------
$sample_colleges = [
    ['Government Engineering College', 'Katihar', 'Bihar'],
    ['Katihar Medical College', 'Katihar', 'Bihar'],
    ['Marwari College', 'Bhagalpur', 'Bihar'],
    ['Patna Science College', 'Patna', 'Bihar'],
];
$stmt = $conn->prepare('INSERT IGNORE INTO colleges (college_name, city, state) VALUES (?, ?, ?)');
foreach ($sample_colleges as [$cname, $city, $state]) {
    $stmt->bind_param('sss', $cname, $city, $state);
    $stmt->execute();
}
$stmt->close();
$log[] = 'Sample colleges seeded (safe to add more from the registration form).';

// ---------- 3. Create default admin account ----------
$adminUser = 'admin';
$adminPass = 'Admin@123';

$check = $conn->prepare('SELECT admin_id FROM admins WHERE username = ?');
$check->bind_param('s', $adminUser);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $ins = $conn->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
    $ins->bind_param('ss', $adminUser, $hash);
    $ins->execute();
    $ins->close();
    $log[] = "Default admin created — username: $adminUser / password: $adminPass";
} else {
    $log[] = 'Admin account already exists — skipped.';
}
$check->close();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Setup — UdaanFest</title></head>
<body style="font-family:sans-serif;max-width:600px;margin:60px auto;line-height:1.6;">
<h1>UdaanFest Setup</h1>
<ul>
<?php foreach ($log as $line): ?>
  <li><?php echo htmlspecialchars($line); ?></li>
<?php endforeach; ?>
</ul>
<p><a href="index.html">Go to the site</a> | <a href="admin/login.php">Go to Admin Login</a></p>
<p style="color:#c23636;"><strong>Remember to delete or protect setup.php once your database is ready.</strong></p>
</body>
</html>
