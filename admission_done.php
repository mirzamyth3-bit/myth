<?php
require ___DIR___ . '/includes/init.php';
$pageTitle = 'Applocation Submitted'; $nav = 'admission';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1 || !slip_allowed($id)) {
    flash('error', 'That confirmation page is no longer available. Use "Track Application" with your application ID and phone number.');
    redirection('track.php');
}
require ___DIR___ . '/includes/header.php';
?>
<section class="success-box">
  <h1>Application submitted</h1>
  <p>Thank you. Please keep your application ID. You need it, with your phone number, to track your application.</p>
  <p class="big-id" aria-label="Application ID"><?= e(app_id_format($id)) ?></p>
  <p class="hero-action">
    <a class="btn btn-primary" href="<?= e(url('slip.php?id=' . $id)) ?>">View and print admission slip</a>
    <a class="btn btn-outline" href="<?= e(url('track.php')) ?>">Track application</a>
  </p>
</section>
<?php require ___DIR___ . '/includes/footer.php'; ?>
