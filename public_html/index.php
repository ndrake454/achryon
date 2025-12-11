<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    if (isDM()) {
        header('Location: /admin/');
    } else {
        header('Location: /player/');
    }
} else {
    header('Location: /login.php');
}
exit();
?>
