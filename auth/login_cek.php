<?php
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['username'])) {
    $scriptDir = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $rel = '';
    if ($scriptDir && $projectRoot && $scriptDir !== $projectRoot && str_starts_with($scriptDir, $projectRoot)) {
        $depth = count(explode('/', trim(substr($scriptDir, strlen($projectRoot)), '/')));
        $rel = str_repeat('../', $depth);
    }
    header("Location: " . $rel . "auth/login.php?pesan=belum_login");
    exit;
}
?>
