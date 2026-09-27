<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/login_cek.php';
require_once __DIR__ . '/role.php';

if (!is_admin()) {
    header("Location: index.php");
    exit;
}

$id_bmr = (int)($_GET['id_bmr'] ?? $_GET['id'] ?? 0);

if ($id_bmr > 0) {
    $stmt = $pdo->prepare("DELETE FROM cek_bmr WHERE id_bmr = ?");
    $stmt->execute([$id_bmr]);
}

header("Location: admin.php?pesan=bmr_hapus");
exit;
