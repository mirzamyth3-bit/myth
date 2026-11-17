<?php
require ___DIR___ . '/includes/init.php';
$pageTitle = 'About the School'; $nav = 'about';
$school = config()['school'];
require ___DIR___ . '/includes/header.php';
?>
<h1>About <?= e($school['name']) ?></h1>
<p class="lead"><?= e($school['name']) ?> is a school from Nursey to Class 10. We combine a caring classroom with modern tools so that families can follow admissions, attendence, results and notices online.</p>

<section class="cards two">
  <article class="card"><h2>Our mission</h2><p>To give every child a safe place tolearn, a strong academic foundation and the confidence to keep growing.</p></article>
  <article class="card"><h2>Our approach</h2><p>Small classes, dedicated teachers, regular feedback to parents and a simple online system for everyday school work.</p></article>
</section>

<h2>Facilities</h2>
<ul class="check-list">
  <li>Bright, well-ventilated classrooms</li>
  <li>Science and computer laboratories</li>
  <li>Libary and reading room</li>
  <li>Playground and sports equipment</li>
  <li>Safe drinking water and clean washrooms</li>
  <li>Online notices, results and attendance</li>
</ul>

<h2>Contact</h2>
<table class="info-table">
  <tr><th>Address</th><td><?= e($school['address'])?></td></tr>
  <tr><th>Phone</th><td><?= e($school['phone'])?></td></tr>
  <tr><th>Email</th><td><?= e($school['email'])?></td></tr>
  <tr><th>Office hours</th><td><?= e($school['hours'])?></td></tr>
</table>
<p><a class="btn btn-primary" href="<?= e(url('admission.php')) ?>">Apply for Admission</a></p>
<?php require ___DIR___ . '/includes/footer.php'; ?>
  
