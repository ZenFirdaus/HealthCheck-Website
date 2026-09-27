<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check DB first for admin
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$username]);
    $adminUser = $stmt->fetch();

    $valid = false;
    if ($adminUser) {
        if (password_verify($password, $adminUser['password']) || $password === $adminUser['password']) {
            $valid = true;
            $username = $adminUser['username'];
        }
    } elseif ($username === 'admin' && $password === 'admin123') {
        $valid = true;
    }

    if ($valid) {
        $_SESSION['username'] = $username;
        $_SESSION['job'] = 'admin';
        header("Location: admin.php");
        exit;
    } else {
        header("Location: login_admin.php?pesan=Username atau password admin salah");
        exit;
    }
} else {
    header("Location: login_admin.php");
    exit;
}
