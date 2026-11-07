<?php
function is_admin() {
    return isset($_SESSION['job']) && $_SESSION['job'] === 'admin';
}
function is_user() {
    return isset($_SESSION['job']) && $_SESSION['job'] === 'user';
}
?>
