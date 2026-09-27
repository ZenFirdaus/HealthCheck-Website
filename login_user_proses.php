<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: login.php?pesan=gagal");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $isMatch = false;
            // Check password_verify if hashed, or plain text match
            if (password_verify($password, $user['password'])) {
                $isMatch = true;
            } elseif ($password === $user['password']) {
                $isMatch = true;
            }

            if ($isMatch) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['job'] = $user['role'] ?? 'user';
                
                if ($_SESSION['job'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            }
        }
    } catch (\Throwable $e) {}

    header("Location: login.php?pesan=gagal");
    exit;
} else {
    header("Location: login.php");
    exit;
}
