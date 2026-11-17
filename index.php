<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Home'; $nav = 'home';

$notices = safe_rows("SELECT notice_id, title, details, `date` FROM notice
                      WHERE notice_scope IN ('public','all')
                      ORDER BY `date` DESC, notice_id DESC LIMIT 3");
$photos  = safe_rows("SELECT image_id, title, image_path FROM gallery ORDER BY upload_date DESC, image_id DESC LIMIT 6");
$school  = config()['school'];
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Welcome to <?= e($school['name']) ?></h1>
  <p><?= e($school['tagline']) ?>. Apply online, follow your application and stay up to date with school news.</p>
  <p class="hero-actions">
    <a class="btn btn-primary" href="<?= e(url('admission.php')) ?>">Apply for Admission</a>
    <a class="btn btn-light" href="<?= e(url('track.php')) ?>">Track Application</a>
  </p>
</section>

<section class="cards three">
  <article class="card"><h2>Online admission</h2><p>Fill in one short form for Nursery to Class 10. No paperwork and no queue.</p></article>
  <article class="card"><h2>Track your status</h2><p>Use your application ID to see whether it is pending, accepted or rejected.</p></article>
  <article class="card"><h2>Admission slip</h2><p>Print or save a confirmation slip right after you submit the form.</p></article>
</section>

<section>
  <div class="section-head"><h2>Latest notices</h2><a href="<?= e(url('notices.php')) ?>">View all</a></div>
  <?php if (!$notices): ?>
    <p class="empty">No public notices at the moment.</p>
  <?php else: foreach ($notices as $n): ?>
    <article class="notice">
      <h3><?= e($n['title']) ?></h3>
      <time datetime="<?= e($n['date']) ?>"><?= e(date('d M Y', strtotime($n['date']))) ?></time>
      <p><?= e(mb_strimwidth($n['details'], 0, 180, '...')) ?></p>
    </article>
  <?php endforeach; endif; ?>
</section>

<section>
  <div class="section-head"><h2>From our gallery</h2><a href="<?= e(url('gallery.php')) ?>">View all</a></div>
  <?php if (!$photos): ?>
    <p class="empty">Photos will appear here soon.</p>
  <?php else: ?>
    <div class="gallery-grid">
      <?php foreach ($photos as $p): $f = basename($p['image_path']); ?>
        <figure><img loading="lazy" src="<?= e(url('uploads/gallery/' . rawurlencode($f))) ?>" alt="<?= e($p['title']) ?>"><figcaption><?= e($p['title']) ?></figcaption></figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
