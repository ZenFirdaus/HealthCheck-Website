<?php
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../auth/role.php';

if (!is_admin()) {
    header("Location: ../index.php");
    exit;
}

header("Location: ../admin/admin.php#tab-bmi");
exit;
