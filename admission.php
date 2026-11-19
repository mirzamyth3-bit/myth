<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Apply for Admission'; $nav = 'admission';
$classes = config()['classes'];

$old = ['applicant_name' => '', 'date_of_birth' => '', 'guardian_name' => '', 'phone' => '', 'email' => '', 'class_applied' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors['form'] = 'Your session expired. Please try again.';
        $old = array_merge($old, array_intersect_key($_POST, $old));
    } else {
        [$clean, $errors] = validate_admission($_POST);
        $old = $clean;

        if (!$errors) {
            try {
                $dup = db()->prepare("SELECT COUNT(*) FROM admission
                                      WHERE applicant_name = ? AND date_of_birth = ? AND phone = ? AND status = 'pending'");
                $dup->execute([$clean['applicant_name'], $clean['date_of_birth'], $clean['phone']]);
                if ((int) $dup->fetchColumn() > 0) {
                    $errors['form'] = 'An application for this applicant is already pending. Use "Track Application" to check it.';
                } else {
                    $ins = db()->prepare("INSERT INTO admission
                        (applicant_name, date_of_birth, guardian_name, phone, email, class_applied, status, applied_date, reviewed_by)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NULL)");
                    $ins->execute([$clean['applicant_name'], $clean['date_of_birth'], $clean['guardian_name'],
                                   $clean['phone'], $clean['email'], $clean['class_applied'], date('Y-m-d')]);
                    $id = (int) db()->lastInsertId();
                    slip_grant($id);
                    session_regenerate_id(true);
                    redirect('admission_done.php?id=' . $id);
                }
            } catch (PDOException $ex) {
                error_log('SMS admission insert: ' . $ex->getMessage());
                $errors['form'] = 'Sorry, we could not save your application right now. Please try again later.';
            }
        }
    }
}

$maxDob = (new DateTime('today'))->modify('-' . config()['min_age'] . ' years')->format('Y-m-d');
$minDob = (new DateTime('today'))->modify('-' . config()['max_age'] . ' years')->format('Y-m-d');
function field_err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<span class="field-error" id="err-' . e($k) . '">' . e($errors[$k]) . '</span>' : '';
}
require __DIR__ . '/includes/header.php';
?>
<h1>Online admission form</h1>
<p class="lead">Fill in the details below. After you submit, you will get an application ID and a printable slip. All fields are required.</p>

<?php if (!empty($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<form method="post" action="<?= e(url('admission.php')) ?>" class="form" novalidate data-admission-form>
  <?= csrf_field() ?>
  <fieldset>
    <legend>Applicant details</legend>
    <div class="grid-2">
      <div class="field">
        <label for="applicant_name">Applicant full name</label>
        <input type="text" id="applicant_name" name="applicant_name" maxlength="100" required autocomplete="off"
               value="<?= e($old['applicant_name']) ?>" aria-describedby="err-applicant_name">
        <?= field_err($errors, 'applicant_name') ?>
      </div>
      <div class="field">
        <label for="date_of_birth">Date of birth</label>
        <input type="date" id="date_of_birth" name="date_of_birth" required min="<?= e($minDob) ?>" max="<?= e($maxDob) ?>"
               value="<?= e($old['date_of_birth']) ?>" aria-describedby="err-date_of_birth">
        <?= field_err($errors, 'date_of_birth') ?>
      </div>
    </div>
    <div class="field">
      <label for="class_applied">Class applying for</label>
      <select id="class_applied" name="class_applied" required aria-describedby="err-class_applied">
        <option value="">Select a class</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= e($c) ?>"<?= $old['class_applied'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
      <?= field_err($errors, 'class_applied') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Guardian details</legend>
    <div class="field">
      <label for="guardian_name">Guardian full name</label>
      <input type="text" id="guardian_name" name="guardian_name" maxlength="100" required autocomplete="off"
             value="<?= e($old['guardian_name']) ?>" aria-describedby="err-guardian_name">
      <?= field_err($errors, 'guardian_name') ?>
    </div>
    <div class="grid-2">
      <div class="field">
        <label for="phone">Mobile number</label>
        <input type="tel" id="phone" name="phone" inputmode="tel" maxlength="20" required placeholder="01712345678"
               value="<?= e($old['phone']) ?>" aria-describedby="err-phone">
        <?= field_err($errors, 'phone') ?>
      </div>
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" maxlength="120" required
               value="<?= e($old['email']) ?>" aria-describedby="err-email">
        <?= field_err($errors, 'email') ?>
      </div>
    </div>
  </fieldset>

  <button class="btn btn-primary" type="submit">Submit application</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
