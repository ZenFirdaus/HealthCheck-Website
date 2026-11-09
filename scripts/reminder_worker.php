<?php
/**
 * CLI Scheduled Reminder Worker for HealthCheck
 * Usage:
 *   php scripts/reminder_worker.php
 *   php scripts/reminder_worker.php --dry-run
 *   php scripts/reminder_worker.php --user=2
 */

if (php_sapi_name() !== 'cli' && empty($_SERVER['HTTP_X_CRON_AUTH'])) {
    http_response_code(403);
    die("Akses ditolak: Skrip worker ini hanya dapat dijalankan melalui CLI (cron job).\n");
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/habit_helper.php';

$options = getopt('', ['dry-run', 'user:', 'help']);

if (isset($options['help'])) {
    echo "HealthCheck Reminder Worker\n";
    echo "Options:\n";
    echo "  --dry-run       Cek jadwal tanpa menyimpan ke log notifikasi\n";
    echo "  --user=ID       Jalankan hanya untuk ID pengguna tertentu\n";
    exit(0);
}

$isDryRun = isset($options['dry-run']);
$userId = isset($options['user']) ? (int)$options['user'] : null;

$currentTime = date('H:i');
$currentDate = date('Y-m-d');

echo "[" . date('Y-m-d H:i:s') . "] Memulai evaluasi reminder (Waktu: {$currentTime}, Tanggal: {$currentDate})...\n";

if ($isDryRun) {
    echo "Mode DRY-RUN aktif: Tidak ada notifikasi yang disimpan.\n";
    $count = 0;
} else {
    $count = generate_due_reminders($pdo, $userId, $currentTime, $currentDate);
    echo "Berhasil menghasilkan {$count} notifikasi baru (idempoten per hari).\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Selesai.\n";
