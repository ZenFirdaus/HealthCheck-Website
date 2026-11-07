<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: login_admin.php?pesan=Username dan password wajib diisi");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$username]);
        $adminUser = $stmt->fetch();

        $valid = false;
        if ($adminUser) {
            if (password_verify($password, $adminUser['password'])) {
                $valid = true;
                if (password_needs_rehash($adminUser['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
                    $upd->execute([$newHash, $adminUser['id']]);
                }
            } elseif ($password === $adminUser['password']) {
                // Transparent migration for legacy plaintext password
                $valid = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $adminUser['id']]);
            }
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$adminUser['id'];
            $_SESSION['username'] = $adminUser['username'];
            $_SESSION['job'] = 'admin';
            header("Location: ../admin/admin.php");
            exit;
        }
    } catch (\Throwable $e) {}

    header("Location: login_admin.php?pesan=Username atau password admin salah");
    exit;
} else {
    header("Location: login_admin.php");
    exit;
}
