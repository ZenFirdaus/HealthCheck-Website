<?php
/**
 * Health Habits Tracker, Daily Check-in & Health Reminders Helper
 * Integrates daily tracking, weekly aggregations, reminder workflows, and data ownership.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth_helper.php';

if (!function_exists('ensure_user_default_habits')) {
    /**
     * Initializes the default 4 foundational habits for a user if not yet created.
     */
    function ensure_user_default_habits(PDO $pdo, int $userId): array {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM health_habits WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $defaults = [
                [
                    'code'          => 'water',
                    'nama_habit'    => 'Konsumsi Air Harian',
                    'deskripsi'     => 'Menjaga kecukupan hidrasi cairan seluler dan fungsi metabolisme',
                    'ikon'          => 'bi-droplet-half',
                    'warna'         => 'teal',
                    'satuan'        => 'ml',
                    'target_harian' => 2000.0,
                    'is_custom'     => 0
                ],
                [
                    'code'          => 'sleep',
                    'nama_habit'    => 'Durasi Tidur Malam',
                    'deskripsi'     => 'Pemulihan ritme sirkadian dan perbaikan seluler alami',
                    'ikon'          => 'bi-moon-stars',
                    'warna'         => 'blue',
                    'satuan'        => 'jam',
                    'target_harian' => 8.0,
                    'is_custom'     => 0
                ],
                [
                    'code'          => 'steps',
                    'nama_habit'    => 'Target Langkah Kaki',
                    'deskripsi'     => 'Aktivitas gerak mandiri untuk mencegah bahaya perilaku sedentari',
                    'ikon'          => 'bi-person-walking',
                    'warna'         => 'emerald',
                    'satuan'        => 'langkah',
                    'target_harian' => 8000.0,
                    'is_custom'     => 0
                ],
                [
                    'code'          => 'activity',
                    'nama_habit'    => 'Aktivitas Fisik & Olahraga',
                    'deskripsi'     => 'Latihan kardio, jalan cepat, atau olahraga terukur harian',
                    'ikon'          => 'bi-activity',
                    'warna'         => 'coral',
                    'satuan'        => 'menit',
                    'target_harian' => 30.0,
                    'is_custom'     => 0
                ]
            ];

            $ins = $pdo->prepare("INSERT INTO health_habits 
                (user_id, code, nama_habit, deskripsi, ikon, warna, satuan, target_harian, is_active, is_custom) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");

            foreach ($defaults as $d) {
                $ins->execute([
                    $userId,
                    $d['code'],
                    $d['nama_habit'],
                    $d['deskripsi'],
                    $d['ikon'],
                    $d['warna'],
                    $d['satuan'],
                    $d['target_harian'],
                    $d['is_custom']
                ]);
            }
        }

        return get_user_habits($pdo, $userId, true);
    }
}

if (!function_exists('get_user_habits')) {
    /**
     * Retrieves all habits for a specific user.
     */
    function get_user_habits(PDO $pdo, int $userId, bool $activeOnly = false): array {
        $sql = "SELECT * FROM health_habits WHERE user_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY is_custom ASC, id_habit ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('get_habit_by_id')) {
    /**
     * Retrieves single habit with strict user ownership validation.
     */
    function get_habit_by_id(PDO $pdo, int $habitId, int $userId): ?array {
        $stmt = $pdo->prepare("SELECT * FROM health_habits WHERE id_habit = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$habitId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('get_user_daily_habit_status')) {
    /**
     * Retrieves all active habits along with logged actual values and progress on a specific date.
     */
    function get_user_daily_habit_status(PDO $pdo, int $userId, string $date): array {
        ensure_user_default_habits($pdo, $userId);

        $sql = "SELECT h.*, 
                       COALESCE(l.nilai_aktual, 0) AS nilai_aktual,
                       COALESCE(l.is_target_tercapai, 0) AS is_target_tercapai,
                       l.catatan AS log_catatan,
                       l.id_log,
                       l.updated_at AS log_updated_at
                FROM health_habits h
                LEFT JOIN habit_logs l ON h.id_habit = l.habit_id AND l.tanggal = ?
                WHERE h.user_id = ? AND h.is_active = 1
                ORDER BY h.is_custom ASC, h.id_habit ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date, $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $target = (float)$r['target_harian'];
            $actual = (float)$r['nilai_aktual'];
            $progress = ($target > 0) ? min(100.0, round(($actual / $target) * 100, 1)) : 0.0;
            $isAchieved = ($actual >= $target && $actual > 0);

            $result[] = array_merge($r, [
                'target_harian'      => $target,
                'nilai_aktual'       => $actual,
                'progress_percent'   => $progress,
                'is_target_tercapai' => $isAchieved ? 1 : 0
            ]);
        }

        return $result;
    }
}

if (!function_exists('log_habit_actual')) {
    /**
     * Upserts an actual value into habit_logs for a specific habit and date.
     */
    function log_habit_actual(PDO $pdo, int $userId, int $habitId, string $date, float $actualValue, ?string $notes = null): bool {
        $habit = get_habit_by_id($pdo, $habitId, $userId);
        if (!$habit) {
            return false;
        }

        $target = (float)$habit['target_harian'];
        $isAchieved = ($actualValue >= $target && $actualValue > 0) ? 1 : 0;

        $sql = "INSERT INTO habit_logs 
                (habit_id, user_id, tanggal, nilai_aktual, is_target_tercapai, catatan, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(habit_id, tanggal) DO UPDATE SET 
                    nilai_aktual = excluded.nilai_aktual,
                    is_target_tercapai = excluded.is_target_tercapai,
                    catatan = excluded.catatan,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$habitId, $userId, $date, $actualValue, $isAchieved, $notes]);
    }
}

if (!function_exists('save_daily_checkin')) {
    /**
     * Saves or updates a daily check-in record and synchronizes to core habit logs.
     */
    function save_daily_checkin(PDO $pdo, int $userId, string $date, array $data): bool {
        ensure_user_default_habits($pdo, $userId);

        $waterMl      = (isset($data['water_ml']) && $data['water_ml'] !== '' && $data['water_ml'] !== null) ? max(0, (float)$data['water_ml']) : null;
        $sleepHours   = (isset($data['sleep_hours']) && $data['sleep_hours'] !== '' && $data['sleep_hours'] !== null) ? max(0, min(24, (float)$data['sleep_hours'])) : null;
        $stepsCount   = (isset($data['steps_count']) && $data['steps_count'] !== '' && $data['steps_count'] !== null) ? max(0, (int)$data['steps_count']) : null;
        $activityType = !empty($data['activity_type']) ? trim($data['activity_type']) : null;
        $activityMins = (isset($data['activity_minutes']) && $data['activity_minutes'] !== '' && $data['activity_minutes'] !== null) ? max(0, (int)$data['activity_minutes']) : null;
        $kondisi      = !empty($data['kondisi_energi']) ? trim($data['kondisi_energi']) : null;
        $catatan      = !empty($data['catatan']) ? trim($data['catatan']) : null;

        $sql = "INSERT INTO daily_checkins 
                (user_id, tanggal, water_ml, sleep_hours, steps_count, activity_type, activity_minutes, kondisi_energi, catatan, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, tanggal) DO UPDATE SET
                    water_ml = excluded.water_ml,
                    sleep_hours = excluded.sleep_hours,
                    steps_count = excluded.steps_count,
                    activity_type = excluded.activity_type,
                    activity_minutes = excluded.activity_minutes,
                    kondisi_energi = excluded.kondisi_energi,
                    catatan = excluded.catatan,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $userId, $date, $waterMl, $sleepHours, $stepsCount, $activityType, $activityMins, $kondisi, $catatan
        ]);

        if ($ok) {
            // Synchronize with habits table for the 4 standard habits
            $habits = get_user_habits($pdo, $userId, true);
            foreach ($habits as $h) {
                if ($h['code'] === 'water' && $waterMl !== null) {
                    log_habit_actual($pdo, $userId, (int)$h['id_habit'], $date, $waterMl);
                } elseif ($h['code'] === 'sleep' && $sleepHours !== null) {
                    log_habit_actual($pdo, $userId, (int)$h['id_habit'], $date, $sleepHours);
                } elseif ($h['code'] === 'steps' && $stepsCount !== null) {
                    log_habit_actual($pdo, $userId, (int)$h['id_habit'], $date, (float)$stepsCount);
                } elseif ($h['code'] === 'activity' && $activityMins !== null) {
                    $actNotes = $activityType ? "Jenis: {$activityType}" : null;
                    log_habit_actual($pdo, $userId, (int)$h['id_habit'], $date, (float)$activityMins, $actNotes);
                }
            }
        }

        return $ok;
    }
}

if (!function_exists('get_daily_checkin')) {
    /**
     * Retrieves daily check-in for a specific user and date.
     */
    function get_daily_checkin(PDO $pdo, int $userId, string $date): ?array {
        $stmt = $pdo->prepare("SELECT * FROM daily_checkins WHERE user_id = ? AND tanggal = ? LIMIT 1");
        $stmt->execute([$userId, $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('get_weekly_habit_summary')) {
    /**
     * Calculates weekly habit metrics and daily time-series based on actual database records.
     * Guaranteed NO arbitrary dummy data.
     */
    function get_weekly_habit_summary(PDO $pdo, int $userId, string $weekStartDate): array {
        $startTimestamp = strtotime($weekStartDate);
        if (!$startTimestamp) {
            $startTimestamp = strtotime('monday this week');
        }

        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime("+{$i} days", $startTimestamp));
            $dates[] = [
                'date'      => $d,
                'day_name'  => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'][$i],
                'day_label' => date('d M', strtotime($d))
            ];
        }

        $endDate = $dates[6]['date'];
        $startDate = $dates[0]['date'];

        // 1. Fetch check-ins for the week
        $stmtCheckin = $pdo->prepare("SELECT * FROM daily_checkins WHERE user_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal ASC");
        $stmtCheckin->execute([$userId, $startDate, $endDate]);
        $checkinRows = $stmtCheckin->fetchAll(PDO::FETCH_ASSOC);

        $checkinMap = [];
        foreach ($checkinRows as $r) {
            $checkinMap[$r['tanggal']] = $r;
        }

        // 2. Fetch all habit logs for the week
        $stmtLogs = $pdo->prepare("SELECT l.*, h.code, h.target_harian 
                                  FROM habit_logs l 
                                  JOIN health_habits h ON l.habit_id = h.id_habit 
                                  WHERE l.user_id = ? AND l.tanggal BETWEEN ? AND ?");
        $stmtLogs->execute([$userId, $startDate, $endDate]);
        $logRows = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        $targetsAchievedCount = 0;
        foreach ($logRows as $lr) {
            if ((int)$lr['is_target_tercapai'] === 1) {
                $targetsAchievedCount++;
            }
        }

        // 3. Build metrics per day
        $waterSeries    = [];
        $sleepSeries    = [];
        $stepsSeries    = [];
        $activitySeries = [];

        $totalWater = 0.0;
        $totalSleep = 0.0;
        $totalSteps = 0;
        $totalActivity = 0;

        $sleepDayCount = 0;
        $stepsDayCount = 0;
        $checkinDaysCount = count($checkinRows);

        foreach ($dates as $d) {
            $curDate = $d['date'];
            $c = $checkinMap[$curDate] ?? null;

            $w = ($c && $c['water_ml'] !== null) ? (float)$c['water_ml'] : 0.0;
            $s = ($c && $c['sleep_hours'] !== null) ? (float)$c['sleep_hours'] : 0.0;
            $st = ($c && $c['steps_count'] !== null) ? (int)$c['steps_count'] : 0;
            $act = ($c && $c['activity_minutes'] !== null) ? (int)$c['activity_minutes'] : 0;

            $waterSeries[]    = $w;
            $sleepSeries[]    = $s;
            $stepsSeries[]    = $st;
            $activitySeries[] = $act;

            $totalWater    += $w;
            $totalActivity += $act;

            if ($s > 0) {
                $totalSleep += $s;
                $sleepDayCount++;
            }
            if ($st > 0) {
                $totalSteps += $st;
                $stepsDayCount++;
            }
        }

        $avgSleep = ($sleepDayCount > 0) ? round($totalSleep / $sleepDayCount, 1) : 0.0;
        $avgSteps = ($stepsDayCount > 0) ? (int)round($totalSteps / $stepsDayCount) : 0;

        return [
            'start_date'             => $startDate,
            'end_date'               => $endDate,
            'prev_week_start'        => date('Y-m-d', strtotime('-7 days', $startTimestamp)),
            'next_week_start'        => date('Y-m-d', strtotime('+7 days', $startTimestamp)),
            'days'                   => $dates,
            'day_labels'             => array_column($dates, 'day_name'),
            'date_labels'            => array_column($dates, 'day_label'),
            'water_series'           => $waterSeries,
            'sleep_series'           => $sleepSeries,
            'steps_series'           => $stepsSeries,
            'activity_series'        => $activitySeries,
            'total_water_ml'         => $totalWater,
            'total_water_liter'      => round($totalWater / 1000, 2),
            'avg_sleep_hours'        => $avgSleep,
            'avg_steps'              => $avgSteps,
            'total_activity_minutes' => $totalActivity,
            'checkin_days_count'     => $checkinDaysCount,
            'targets_achieved_count' => $targetsAchievedCount,
            'has_sufficient_data'    => ($checkinDaysCount >= 2 || $targetsAchievedCount >= 2)
        ];
    }
}

if (!function_exists('ensure_user_default_reminders')) {
    /**
     * Initializes default reminder schedules for a user if none exist.
     */
    function ensure_user_default_reminders(PDO $pdo, int $userId): array {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM health_reminders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $defaults = [
                [
                    'tipe_reminder' => 'water',
                    'judul'         => 'Waktunya Minum Air Sehat',
                    'pesan'         => 'Minum segelas air (250 ml) sekarang untuk menjaga hidrasi dan fokus Anda.',
                    'jam_reminder'  => '10:00',
                    'hari_aktif'    => '1,2,3,4,5,6,7'
                ],
                [
                    'tipe_reminder' => 'activity',
                    'judul'         => 'Jeda Gerak & Peregangan',
                    'pesan'         => 'Berdiri sejenak, regangkan otot, atau lakukan jalan santai 5 menit.',
                    'jam_reminder'  => '15:30',
                    'hari_aktif'    => '1,2,3,4,5,6,7'
                ],
                [
                    'tipe_reminder' => 'checkin',
                    'judul'         => 'Daily Health Check-in',
                    'pesan'         => 'Luangkan 1 menit untuk mencatat aktivitas hidrasi, langkah, dan tidur Anda hari ini.',
                    'jam_reminder'  => '20:00',
                    'hari_aktif'    => '1,2,3,4,5,6,7'
                ],
                [
                    'tipe_reminder' => 'sleep',
                    'judul'         => 'Persiapan Tidur & Higiene Sirkadian',
                    'pesan'         => 'Matikan perangkat elektronik layar biru dan mulai rileks untuk tidur berkualitas.',
                    'jam_reminder'  => '22:00',
                    'hari_aktif'    => '1,2,3,4,5,6,7'
                ]
            ];

            $ins = $pdo->prepare("INSERT INTO health_reminders 
                (user_id, tipe_reminder, judul, pesan, jam_reminder, hari_aktif, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)");

            foreach ($defaults as $d) {
                $ins->execute([
                    $userId,
                    $d['tipe_reminder'],
                    $d['judul'],
                    $d['pesan'],
                    $d['jam_reminder'],
                    $d['hari_aktif']
                ]);
            }
        }

        return get_user_reminders($pdo, $userId);
    }
}

if (!function_exists('get_user_reminders')) {
    /**
     * Retrieves all reminder schedules for a specific user.
     */
    function get_user_reminders(PDO $pdo, int $userId, bool $activeOnly = false): array {
        $sql = "SELECT * FROM health_reminders WHERE user_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY jam_reminder ASC, id_reminder ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('get_user_in_app_notifications')) {
    /**
     * Retrieves in-app notification logs for a user.
     */
    function get_user_in_app_notifications(PDO $pdo, int $userId, int $limit = 10, bool $unreadOnly = false): array {
        $sql = "SELECT * FROM notification_logs WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND status = 'unread'";
        }
        $sql .= " ORDER BY sent_at DESC, id_notif DESC LIMIT " . (int)$limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('generate_due_reminders')) {
    /**
     * Evaluates reminders and idempotently logs due notifications.
     * Can be invoked via CLI cron worker or during user page load.
     */
    function generate_due_reminders(PDO $pdo, ?int $targetUserId = null, ?string $currentTime = null, ?string $currentDate = null): int {
        $nowTime = $currentTime ?: date('H:i');
        $nowDate = $currentDate ?: date('Y-m-d');
        $dayOfWeek = (int)date('N', strtotime($nowDate)); // 1 (Mon) to 7 (Sun)

        $sql = "SELECT r.* FROM health_reminders r WHERE r.is_active = 1";
        $params = [];
        if ($targetUserId !== null) {
            $sql .= " AND r.user_id = ?";
            $params[] = $targetUserId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertedCount = 0;
        $ins = $pdo->prepare("INSERT OR IGNORE INTO notification_logs 
            (user_id, reminder_id, tipe_notif, judul, pesan, tanggal_kirim, sent_at, status)
            VALUES (?, ?, 'in_app', ?, ?, ?, CURRENT_TIMESTAMP, 'unread')");

        foreach ($reminders as $r) {
            $activeDays = explode(',', (string)$r['hari_aktif']);
            if (!in_array((string)$dayOfWeek, $activeDays, true) && !in_array('all', $activeDays, true)) {
                continue;
            }

            // Check if reminder time is due (equal or earlier if evaluating within the day)
            // For cron / page load trigger, we trigger if current time >= reminder time
            if ($nowTime >= $r['jam_reminder']) {
                $ins->execute([
                    $r['user_id'],
                    $r['id_reminder'],
                    $r['judul'],
                    $r['pesan'],
                    $nowDate
                ]);
                if ($ins->rowCount() > 0) {
                    $insertedCount++;
                }
            }
        }

        return $insertedCount;
    }
}
