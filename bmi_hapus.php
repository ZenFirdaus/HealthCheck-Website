<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/login_cek.php';
require_once __DIR__ . '/role.php';

if (!is_admin()) {
    header("Location: index.php");
    exit;
}

$id_bmi = (int)($_GET['id_bmi'] ?? $_GET['id'] ?? 0);

if ($id_bmi > 0) {
    $stmt = $pdo->prepare("DELETE FROM cek_bmi WHERE id_bmi = ?");
    $stmt->execute([$id_bmi]);
}

header("Location: admin.php?pesan=berhasil_hapus");
exit;
