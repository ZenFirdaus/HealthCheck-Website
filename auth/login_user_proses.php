<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

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
            if (password_verify($password, $user['password'])) {
                $isMatch = true;
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
                    $upd->execute([$newHash, $user['id']]);
                }
            } elseif ($password === $user['password']) {
                // Transparent migration of legacy plaintext password
                $isMatch = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $user['id']]);
            }

            if ($isMatch) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['job'] = $user['role'] ?? 'user';
                
                if ($_SESSION['job'] === 'admin') {
                    header("Location: ../admin/admin.php");
                } else {
                    header("Location: ../index.php");
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
