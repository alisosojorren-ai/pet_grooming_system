<?php
session_start();
session_destroy();
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'login.php';

// Prevent open redirect abuse by limiting to known internal paths
$allowed = ['login.php', 'admin/login.php'];
if(!in_array($redirect, $allowed, true)) {
    $redirect = 'login.php';
}

header("Location: $redirect");
exit();
?>