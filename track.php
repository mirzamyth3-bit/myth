<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Track Application'; $nav = 'track';

$MAX_FAILS = 5; $WINDOW = 600;
$result = null; $error = ''; $idIn = ''; $phoneIn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idIn = trim((string) ($_POST['application_id'] ?? ''));
    $phoneIn = trim((string) ($_POST['phone'] ?? ''));
    $now = time();
    $fails = array_values(array_filter($_SESSION['track_fail'] ?? [], fn($t) => $t > $now - $WINDOW));

    if (!csrf_ok()) {
        $error = 'Your session expired. Please try again.';
    } elseif (count($fails) >= $MAX_FAILS) {
        $error = 'Too many unsuccessful attempts. Please wait a few minutes and try again.';
    } else {
        $id = app_id_parse($idIn);
        $phone = phone_normalize($phoneIn);
        $row = null;
        if ($id !== null && $phone !== '') {
            try {
                $st = db()->prepare("SELECT application_id, applicant_name, class_applied, status, applied_date
                                     FROM admission WHERE application_id = ? AND phone = ?");
                $st->execute([$id, $phone]);
                $row = $st->fetch() ?: null;
            } catch (PDOException $ex) {
                error_log('SMS track: ' . $ex->getMessage());
                $error = 'Sorry, we could not check your application right now. Please try again later.';
            }
        }
        if ($row) {
            $result = $row;
            slip_grant((int) $row['application_id']);
            $fails = [];
        } elseif ($error === '') {
            $fails[] = $now;
            $error = 'No application found for that ID and phone number. Please check both and try again.';
        }
    }
    $_SESSION['track_fail'] = $fails;
}

$messages = [
    'pending'  => 'Your application has been received and is waiting for review by the school.',
    'accepted' => 'Congratulations! Your application has been accepted. The school will contact you with the next steps.',
    'rejected' => 'We are sorry, your application could not be accepted this time. Please contact the school office for details.',
];
require __DIR__ . '/includes/header.php';
?>
<h1>Track your application</h1>
<p class="lead">Enter the application ID you received (for example <strong>ADM-00012</strong>) and the mobile number used in the form.</p>

<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

<form method="post" action="<?= e(url('track.php')) ?>" class="form narrow" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="application_id">Application ID</label>
    <input type="text" id="application_id" name="application_id" required maxlength="20" autocomplete="off" value="<?= e($idIn) ?>">
  </div>
  <div class="field">
    <label for="phone">Mobile number</label>
    <input type="tel" id="phone" name="phone" inputmode="tel" required maxlength="20" placeholder="01712345678" value="<?= e($phoneIn) ?>">
  </div>
  <button class="btn btn-primary" type="submit">Check status</button>
</form>

<?php if ($result): ?>
<section class="result-box" aria-live="polite">
  <h2>Application <?= e(app_id_format((int) $result['application_id'])) ?></h2>
  <table class="info-table">
    <tr><th>Applicant</th><td><?= e($result['applicant_name']) ?></td></tr>
    <tr><th>Class applied</th><td><?= e($result['class_applied']) ?></td></tr>
    <tr><th>Applied on</th><td><?= e(date('d M Y', strtotime($result['applied_date']))) ?></td></tr>
    <tr><th>Status</th><td><?= status_badge($result['status']) ?></td></tr>
  </table>
  <p><?= e($messages[$result['status']] ?? '') ?></p>
  <a class="btn btn-outline" href="<?= e(url('slip.php?id=' . (int) $result['application_id'])) ?>">View admission slip</a>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
