<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Admission Slip'; $nav = 'admission';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1 || !slip_allowed($id)) {
    flash('error', 'To open your slip, first track your application with the ID and phone number.');
    redirect('track.php');
}
$row = null;
try {
    $st = db()->prepare("SELECT * FROM admission WHERE application_id = ?");
    $st->execute([$id]);
    $row = $st->fetch() ?: null;
} catch (PDOException $ex) {
    error_log('SMS slip: ' . $ex->getMessage());
}
if (!$row) {
    flash('error', 'Application not found.');
    redirect('track.php');
}
$school = config()['school'];
require __DIR__ . '/includes/header.php';
?>
<article class="slip" id="slip">
  <header class="slip-head">
    <h1><?= e($school['name']) ?></h1>
    <p><?= e($school['address']) ?> &middot; <?= e($school['phone']) ?></p>
    <h2>Admission Application Slip</h2>
  </header>
  <p class="big-id"><?= e(app_id_format((int) $row['application_id'])) ?></p>
  <table class="info-table">
    <tr><th>Applicant name</th><td><?= e($row['applicant_name']) ?></td></tr>
    <tr><th>Date of birth</th><td><?= e(date('d M Y', strtotime($row['date_of_birth']))) ?></td></tr>
    <tr><th>Class applied</th><td><?= e($row['class_applied']) ?></td></tr>
    <tr><th>Guardian name</th><td><?= e($row['guardian_name']) ?></td></tr>
    <tr><th>Mobile number</th><td><?= e($row['phone']) ?></td></tr>
    <tr><th>Email</th><td><?= e($row['email']) ?></td></tr>
    <tr><th>Applied on</th><td><?= e(date('d M Y', strtotime($row['applied_date']))) ?></td></tr>
    <tr><th>Status</th><td><?= status_badge($row['status']) ?></td></tr>
  </table>
  <p class="slip-note">This slip confirms that your application was received. It is not an offer of admission. Keep the application ID to track your status online.</p>
</article>
<p class="no-print">
  <button class="btn btn-primary" type="button" data-print>Print / Save as PDF</button>
  <a class="btn btn-outline" href="<?= e(url('track.php')) ?>">Track application</a>
</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
