<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/convert.php';
require_admin();
$pageTitle = 'Review Admissions'; $nav = '';

$allowed = ['pending', 'accepted', 'rejected'];
$filter  = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : '';
$q       = trim((string) ($_GET['q'] ?? ''));
$q       = mb_substr($q, 0, 60);
$page    = max(1, (int) ($_GET['page'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filter = in_array($_POST['f_status'] ?? '', $allowed, true) ? $_POST['f_status'] : '';
    $q      = mb_substr(trim((string) ($_POST['f_q'] ?? '')), 0, 60);
    $page   = max(1, (int) ($_POST['f_page'] ?? 1));
    $back   = 'admin/admissions.php?' . http_build_query(array_filter(['status' => $filter, 'q' => $q, 'page' => $page > 1 ? $page : null]));

    $id = (int) ($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (!csrf_ok()) {
        flash('error', 'Your session expired. Please try again.');
    } elseif ($id < 1 || !in_array($action, ['accept', 'reject', 'convert'], true)) {
        flash('error', 'Invalid request.');
    } elseif ($action === 'convert') {
        $res = convert_applicant_to_student($id);
        flash($res['ok'] ? 'success' : 'error', $res['msg']);
    } else {
        $new = $action === 'accept' ? 'accepted' : 'rejected';
        try {
            $up = db()->prepare("UPDATE admission SET status = ?, reviewed_by = ? WHERE application_id = ? AND status = 'pending'");
            $up->execute([$new, (int) $_SESSION['user_id'], $id]);
            if ($up->rowCount() === 1) {
                flash('success', 'Application ' . app_id_format($id) . ' marked as ' . $new . '.');
            } else {
                flash('error', 'That application was already reviewed or does not exist.');
            }
        } catch (PDOException $ex) {
            error_log('SMS review: ' . $ex->getMessage());
            flash('error', 'Could not update the application. Please try again.');
        }
    }
    redirect($back);
}

$where = []; $params = [];
if ($filter !== '') { $where[] = 'a.status = ?'; $params[] = $filter; }
if ($q !== '') {
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    $cond = "(a.applicant_name LIKE ? ESCAPE '!' OR a.guardian_name LIKE ? ESCAPE '!' OR a.phone LIKE ? ESCAPE '!'";
    array_push($params, $like, $like, $like);
    if (($pid = app_id_parse($q)) !== null) { $cond .= ' OR a.application_id = ?'; $params[] = $pid; }
    $where[] = $cond . ')';
}
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$counts = ['pending' => 0, 'accepted' => 0, 'rejected' => 0];
$rows = []; $total = 0; $per = 15; $loadError = false;
try {
    foreach (db()->query("SELECT status, COUNT(*) AS c FROM admission GROUP BY status")->fetchAll() as $r) {
        $counts[$r['status']] = (int) $r['c'];
    }
    $st = db()->prepare("SELECT COUNT(*) FROM admission a $w");
    $st->execute($params);
    $total = (int) $st->fetchColumn();
    $pages = max(1, (int) ceil($total / $per));
    $page  = min($page, $pages);

    $st = db()->prepare("SELECT a.*, u.name AS reviewer, cu.role AS acct_role FROM admission a
                         LEFT JOIN users u ON u.user_id = a.reviewed_by
                         LEFT JOIN users cu ON cu.email = a.email
                         $w ORDER BY (a.status = 'pending') DESC, a.application_id DESC LIMIT ? OFFSET ?");
    foreach (array_merge($params, [$per, ($page - 1) * $per]) as $i => $v) {
        $st->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    $rows = $st->fetchAll();
} catch (PDOException $ex) {
    error_log('SMS admin list: ' . $ex->getMessage());
    $loadError = true; $pages = 1;
}
$qs = fn(array $o) => http_build_query(array_filter(array_merge(['status' => $filter, 'q' => $q], $o), fn($v) => $v !== '' && $v !== null));

require __DIR__ . '/../includes/header.php';
?>
<h1>Review admissions</h1>
<?php if ($loadError): ?><div class="alert alert-error" role="alert">The admission list could not be loaded. Check that the database tables exist.</div><?php endif; ?>

<section class="stats" aria-label="Summary">
  <a class="stat" href="?"><strong><?= array_sum($counts) ?></strong><span>Total</span></a>
  <a class="stat s-pending" href="?status=pending"><strong><?= $counts['pending'] ?></strong><span>Pending</span></a>
  <a class="stat s-accepted" href="?status=accepted"><strong><?= $counts['accepted'] ?></strong><span>Accepted</span></a>
  <a class="stat s-rejected" href="?status=rejected"><strong><?= $counts['rejected'] ?></strong><span>Rejected</span></a>
</section>

<form method="get" class="filter-bar">
  <label class="sr-only" for="q">Search</label>
  <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search name, guardian, phone or ID" maxlength="60">
  <label class="sr-only" for="status">Status</label>
  <select id="status" name="status">
    <option value="">All statuses</option>
    <?php foreach ($allowed as $s): ?><option value="<?= e($s) ?>"<?= $filter === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
  <?php if ($filter !== '' || $q !== ''): ?><a class="btn btn-outline" href="?">Clear</a><?php endif; ?>
</form>

<?php if (!$rows && !$loadError): ?>
  <p class="empty">No applications match.</p>
<?php elseif ($rows): ?>
<div class="table-scroll">
<table class="data-table">
  <thead><tr><th>ID</th><th>Applicant</th><th>Class</th><th>Applied</th><th>Status</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $aid = (int) $r['application_id']; ?>
    <tr>
      <td data-label="ID"><?= e(app_id_format($aid)) ?></td>
      <td data-label="Applicant">
        <details>
          <summary><?= e($r['applicant_name']) ?></summary>
          <dl class="details">
            <dt>Date of birth</dt><dd><?= e(date('d M Y', strtotime($r['date_of_birth']))) ?></dd>
            <dt>Guardian</dt><dd><?= e($r['guardian_name']) ?></dd>
            <dt>Phone</dt><dd><?= e($r['phone']) ?></dd>
            <dt>Email</dt><dd><?= e($r['email']) ?></dd>
            <?php if ($r['status'] !== 'pending'): ?><dt>Reviewed by</dt><dd><?= e($r['reviewer'] ?? 'Unknown') ?></dd><?php endif; ?>
          </dl>
        </details>
      </td>
      <td data-label="Class"><?= e($r['class_applied']) ?></td>
      <td data-label="Applied"><?= e(date('d M Y', strtotime($r['applied_date']))) ?></td>
      <td data-label="Status"><?= status_badge($r['status']) ?></td>
      <td data-label="Action">
        <?php if ($r['status'] === 'pending'): ?>
          <form method="post" class="inline-actions">
            <?= csrf_field() ?>
            <input type="hidden" name="application_id" value="<?= $aid ?>">
            <input type="hidden" name="f_status" value="<?= e($filter) ?>">
            <input type="hidden" name="f_q" value="<?= e($q) ?>">
            <input type="hidden" name="f_page" value="<?= $page ?>">
            <button class="btn btn-small btn-ok" name="action" value="accept" type="submit" data-confirm="Accept application <?= e(app_id_format($aid)) ?>?">Accept</button>
            <button class="btn btn-small btn-danger" name="action" value="reject" type="submit" data-confirm="Reject application <?= e(app_id_format($aid)) ?>?">Reject</button>
          </form>
        <?php elseif ($r['status'] === 'accepted' && ($r['acct_role'] ?? null) === null): ?>
          <form method="post" class="inline-actions">
            <?= csrf_field() ?>
            <input type="hidden" name="application_id" value="<?= $aid ?>">
            <input type="hidden" name="f_status" value="<?= e($filter) ?>">
            <input type="hidden" name="f_q" value="<?= e($q) ?>">
            <input type="hidden" name="f_page" value="<?= $page ?>">
            <button class="btn btn-small btn-primary" name="action" value="convert" type="submit" data-confirm="Create a student account for <?= e($r['applicant_name']) ?>?">Convert to student</button>
          </form>
        <?php elseif ($r['status'] === 'accepted' && $r['acct_role'] === 'student'): ?><span class="muted">Student account created</span>
        <?php elseif ($r['status'] === 'accepted'): ?><span class="muted">Email used by another account</span>
        <?php else: ?><span class="muted">Reviewed</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Pages">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?<?= e($qs(['page' => $i > 1 ? $i : null])) ?>"<?= $i === $page ? ' class="active" aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
