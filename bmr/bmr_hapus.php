<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../auth/role.php';

if (!is_admin()) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("405 Method Not Allowed: Penghapusan data harus melalui metode POST dengan token keamanan.");
}

require_csrf_token();

$id_bmr = (int)($_POST['id_bmr'] ?? $_POST['id'] ?? 0);

if ($id_bmr > 0) {
    assert_record_access($pdo, 'cek_bmr', 'id_bmr', $id_bmr);
    $stmt = $pdo->prepare("DELETE FROM cek_bmr WHERE id_bmr = ?");
    $stmt->execute([$id_bmr]);
}

header("Location: ../admin/admin.php?pesan=bmr_hapus");
exit;
