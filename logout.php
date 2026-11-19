<?php
require ___DIR___ . '/includes/init.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(sessiom_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
flash('success', 'You have beenlogged out.');
redirect('index.php');
