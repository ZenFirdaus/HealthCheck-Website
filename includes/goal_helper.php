<?php
/**
 * Health Goals & Actionable Milestones Helper for HealthCheck
 * Provides directional progress calculations, milestones management, activity logging,
 * and automatic synchronization with assessments and daily check-ins.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth_helper.php';

if (!function_exists('calculate_goal_progress')) {
    /**
     * Calculates directional progress percentage from initial toward target value.
     * Prevents negative percentages and handles overshoot/division by zero gracefully.
     */
    function calculate_goal_progress(float $initial, float $target, float $current, string $direction = 'increase'): array {
        $span = 0.0;
        $achieved = 0.0;
        $isDecrease = ($direction === 'decrease');

        if ($isDecrease) {
            $span = $initial - $target;
            $achieved = $initial - $current;
        } else {
            $span = $target - $initial;
            $achieved = $current - $initial;
        }

        // Handle edge case where initial equals target
        if (abs($span) < 0.0001) {
            $isMet = $isDecrease ? ($current <= $target) : ($current >= $target);
            return [
                'percentage'      => $isMet ? 100.0 : 0.0,
                'raw_percentage'  => $isMet ? 100.0 : 0.0,
                'is_achieved'     => $isMet,
                'is_opposite'     => false,
                'delta_remaining' => 0.0,
                'status_label'    => $isMet ? 'Target Tercapai' : 'Belum Ada Perubahan'
            ];
        }

        $rawPercent = ($achieved / $span) * 100.0;
        $isOpposite = ($rawPercent < 0.0);
        $isAchieved = ($rawPercent >= 100.0);

        // Clamped percentage: never negative, max 100%
        $clampedPercent = max(0.0, min(100.0, round($rawPercent, 1)));

        // Delta remaining to target
        $deltaRemaining = $isDecrease ? max(0.0, round($current - $target, 2)) : max(0.0, round($target - $current, 2));

        $statusLabel = 'Dalam Proses';
        if ($isAchieved) {
            $statusLabel = 'Target Tercapai';
        } elseif ($isOpposite) {
            $statusLabel = $isDecrease ? 'Mengalami Kenaikan' : 'Mengalami Penurunan';
        } elseif ($clampedPercent >= 75.0) {
            $statusLabel = 'Hampir Tercapai';
        }

        return [
            'percentage'      => $clampedPercent,
            'raw_percentage'  => round($rawPercent, 1),
            'is_achieved'     => $isAchieved,
            'is_opposite'     => $isOpposite,
            'delta_remaining' => $deltaRemaining,
            'status_label'    => $statusLabel
        ];
    }
}

if (!function_exists('get_goal_categories_meta')) {
    function get_goal_categories_meta(): array {
        return [
            'body_nutrition' => [
                'name'        => 'Gizi & Komposisi Tubuh',
                'description' => 'Target berat badan, lingkar pinggang, atau lemak tubuh bertahap',
                'color'       => 'teal',
                'icon'        => 'bi-speedometer2',
                'types'       => [
                    'weight_loss' => ['name' => 'Menurunkan Berat Badan', 'unit' => 'kg', 'direction' => 'decrease'],
                    'weight_gain' => ['name' => 'Menaikkan Berat Badan', 'unit' => 'kg', 'direction' => 'increase'],
                    'waist'       => ['name' => 'Mengecilkan Lingkar Pinggang', 'unit' => 'cm', 'direction' => 'decrease']
                ]
            ],
            'physical_activity' => [
                'name'        => 'Aktivitas Fisik & Kebugaran',
                'description' => 'Target langkah kaki harian atau frekuensi olahraga mingguan',
                'color'       => 'emerald',
                'icon'        => 'bi-person-walking',
                'types'       => [
                    'steps'            => ['name' => 'Target Langkah Harian', 'unit' => 'langkah', 'direction' => 'increase'],
                    'activity_minutes' => ['name' => 'Aktivitas Olahraga Mingguan', 'unit' => 'menit', 'direction' => 'increase']
                ]
            ],
            'sleep_lifestyle' => [
                'name'        => 'Pola Tidur & Pemulihan',
                'description' => 'Target durasi dan konsistensi jam tidur berkualitas',
                'color'       => 'blue',
                'icon'        => 'bi-moon-stars',
                'types'       => [
                    'sleep_duration' => ['name' => 'Target Durasi Tidur Harian', 'unit' => 'jam', 'direction' => 'increase']
                ]
            ],
            'hydration' => [
                'name'        => 'Hidrasi & Cairan',
                'description' => 'Target konsumsi air minum untuk hidrasi optimal',
                'color'       => 'teal',
                'icon'        => 'bi-droplet-half',
                'types'       => [
                    'water_intake' => ['name' => 'Target Asupan Air Harian', 'unit' => 'ml', 'direction' => 'increase']
                ]
            ],
            'personal' => [
                'name'        => 'Target Personal Mandiri',
                'description' => 'Target kebiasaan personal dengan parameter kustom Anda',
                'color'       => 'lavender',
                'icon'        => 'bi-flag',
                'types'       => [
                    'custom_inc' => ['name' => 'Peningkatan Rutinitas (Makin Tinggi)', 'unit' => 'kali', 'direction' => 'increase'],
                    'custom_dec' => ['name' => 'Pengurangan Kebiasaan (Makin Rendah)', 'unit' => 'kali', 'direction' => 'decrease']
                ]
            ]
        ];
    }
}

if (!function_exists('create_health_goal')) {
    /**
     * Creates a new health goal and logs activity.
     */
    function create_health_goal(PDO $pdo, int $userId, array $data): int {
        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $category    = trim($data['category'] ?? 'body_nutrition');
        $goalType    = trim($data['goal_type'] ?? 'custom');
        $initialVal  = (float)($data['initial_value'] ?? 0);
        $targetVal   = (float)($data['target_value'] ?? 0);
        $currentVal  = isset($data['current_value']) ? (float)$data['current_value'] : $initialVal;
        $unit        = trim($data['unit'] ?? '');
        $direction   = trim($data['direction'] ?? 'increase');
        $startDate   = trim($data['start_date'] ?? date('Y-m-d'));
        $targetDate  = trim($data['target_date'] ?? date('Y-m-d', strtotime('+30 days')));
        $notes       = trim($data['notes'] ?? '');
        $isFeatured  = !empty($data['is_featured']) ? 1 : 0;

        if ($isFeatured === 1) {
            $pdo->prepare("UPDATE health_goals SET is_featured = 0 WHERE user_id = ?")->execute([$userId]);
        }

        $sql = "INSERT INTO health_goals 
            (user_id, title, description, category, goal_type, initial_value, target_value, current_value, unit, direction, start_date, target_date, status, is_featured, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId, $title, $description, $category, $goalType, $initialVal, $targetVal, $currentVal, $unit, $direction, $startDate, $targetDate, $isFeatured, $notes
        ]);
        $goalId = (int)$pdo->lastInsertId();

        // Log initial progress entry
        $calc = calculate_goal_progress($initialVal, $targetVal, $currentVal, $direction);
        $progSql = "INSERT INTO goal_progress_logs (goal_id, user_id, current_value, progress_percentage, source_type, notes, recorded_at) VALUES (?, ?, ?, ?, 'manual', 'Nilai awal target dibuat', ?)";
        $pdo->prepare($progSql)->execute([$goalId, $userId, $currentVal, $calc['percentage'], $startDate]);

        // Log goal creation activity
        log_goal_activity($pdo, $userId, $goalId, 'goal_created', "Target '{$title}' berhasil dibuat dengan sasaran {$targetVal} {$unit}.");

        return $goalId;
    }
}

if (!function_exists('update_health_goal')) {
    /**
     * Updates an existing health goal ensuring user ownership.
     */
    function update_health_goal(PDO $pdo, int $userId, int $goalId, array $data): bool {
        $goal = get_goal_by_id($pdo, $userId, $goalId);
        if (!$goal) {
            return false;
        }

        $title       = trim($data['title'] ?? $goal['title']);
        $description = trim($data['description'] ?? $goal['description']);
        $targetVal   = isset($data['target_value']) ? (float)$data['target_value'] : (float)$goal['target_value'];
        $targetDate  = trim($data['target_date'] ?? $goal['target_date']);
        $notes       = trim($data['notes'] ?? $goal['notes']);
        $isFeatured  = isset($data['is_featured']) ? (int)$data['is_featured'] : (int)$goal['is_featured'];

        if ($isFeatured === 1) {
            $pdo->prepare("UPDATE health_goals SET is_featured = 0 WHERE user_id = ?")->execute([$userId]);
        }

        $sql = "UPDATE health_goals SET 
                    title = ?, description = ?, target_value = ?, target_date = ?, is_featured = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id_goal = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$title, $description, $targetVal, $targetDate, $isFeatured, $notes, $goalId, $userId]);

        if ($ok) {
            log_goal_activity($pdo, $userId, $goalId, 'goal_updated', "Informasi target '{$title}' diperbarui.");
        }

        return $ok;
    }
}

if (!function_exists('set_goal_status')) {
    /**
     * Updates goal status ('active', 'paused', 'completed', 'archived').
     */
    function set_goal_status(PDO $pdo, int $userId, int $goalId, string $status): bool {
        $goal = get_goal_by_id($pdo, $userId, $goalId);
        if (!$goal) {
            return false;
        }

        $allowed = ['active', 'paused', 'completed', 'archived'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : $goal['completed_at'];

        $sql = "UPDATE health_goals SET status = ?, completed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id_goal = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$status, $completedAt, $goalId, $userId]);

        if ($ok) {
            $statusLabels = [
                'active'    => 'diaktifkan kembali',
                'paused'    => 'dijeda sementara',
                'completed' => 'diselesaikan dengan sukses',
                'archived'  => 'diarsipkan'
            ];
            $actionDesc = $statusLabels[$status] ?? $status;
            log_goal_activity($pdo, $userId, $goalId, "goal_{$status}", "Target '{$goal['title']}' {$actionDesc}.");
        }

        return $ok;
    }
}

if (!function_exists('set_featured_goal')) {
    /**
     * Sets a specific goal as featured and unfeatures others.
     */
    function set_featured_goal(PDO $pdo, int $userId, int $goalId): bool {
        $goal = get_goal_by_id($pdo, $userId, $goalId);
        if (!$goal) {
            return false;
        }

        $pdo->prepare("UPDATE health_goals SET is_featured = 0 WHERE user_id = ?")->execute([$userId]);
        $stmt = $pdo->prepare("UPDATE health_goals SET is_featured = 1, updated_at = CURRENT_TIMESTAMP WHERE id_goal = ? AND user_id = ?");
        return $stmt->execute([$goalId, $userId]);
    }
}

if (!function_exists('delete_health_goal')) {
    /**
     * Deletes a health goal and cascades related progress, milestones, and activities.
     */
    function delete_health_goal(PDO $pdo, int $userId, int $goalId): bool {
        $goal = get_goal_by_id($pdo, $userId, $goalId);
        if (!$goal) {
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM health_goals WHERE id_goal = ? AND user_id = ?");
        return $stmt->execute([$goalId, $userId]);
    }
}

if (!function_exists('get_goal_by_id')) {
    /**
     * Retrieves a single health goal with calculated progress metrics and ownership check.
     */
    function get_goal_by_id(PDO $pdo, int $userId, int $goalId): ?array {
        $stmt = $pdo->prepare("SELECT * FROM health_goals WHERE id_goal = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$goalId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $calc = calculate_goal_progress(
            (float)$row['initial_value'],
            (float)$row['target_value'],
            (float)$row['current_value'],
            $row['direction']
        );

        $row['progress_percent'] = $calc['percentage'];
        $row['raw_percentage']   = $calc['raw_percentage'];
        $row['is_achieved']      = $calc['is_achieved'];
        $row['is_opposite']      = $calc['is_opposite'];
        $row['delta_remaining']  = $calc['delta_remaining'];
        $row['progress_status']  = $calc['status_label'];

        return $row;
    }
}

if (!function_exists('get_user_goals')) {
    /**
     * Retrieves filtered and sorted list of goals for a specific user.
     */
    function get_user_goals(PDO $pdo, int $userId, array $filters = []): array {
        $sql = "SELECT * FROM health_goals WHERE user_id = ?";
        $params = [$userId];

        // Filter status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        // Filter category
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        // Keyword search
        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $like = '%' . trim($filters['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY is_featured DESC, status = 'active' DESC, id_goal DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $calc = calculate_goal_progress(
                (float)$r['initial_value'],
                (float)$r['target_value'],
                (float)$r['current_value'],
                $r['direction']
            );

            $r['progress_percent'] = $calc['percentage'];
            $r['raw_percentage']   = $calc['raw_percentage'];
            $r['is_achieved']      = $calc['is_achieved'];
            $r['is_opposite']      = $calc['is_opposite'];
            $r['delta_remaining']  = $calc['delta_remaining'];
            $r['progress_status']  = $calc['status_label'];

            $result[] = $r;
        }

        return $result;
    }
}

if (!function_exists('get_featured_goal')) {
    /**
     * Retrieves the single featured goal or the latest active goal for the dashboard.
     */
    function get_featured_goal(PDO $pdo, int $userId): ?array {
        $stmt = $pdo->prepare("SELECT * FROM health_goals WHERE user_id = ? AND is_featured = 1 AND status = 'active' LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Fallback to latest active goal
            $stmt = $pdo->prepare("SELECT * FROM health_goals WHERE user_id = ? AND status = 'active' ORDER BY id_goal DESC LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row) {
            return null;
        }

        return get_goal_by_id($pdo, $userId, (int)$row['id_goal']);
    }
}

if (!function_exists('get_goal_stats_summary')) {
    /**
     * Calculates summary counts of user's goals.
     */
    function get_goal_stats_summary(PDO $pdo, int $userId): array {
        $all = get_user_goals($pdo, $userId);

        $activeCount = 0;
        $completedCount = 0;
        $pausedCount = 0;
        $archivedCount = 0;
        $nearlyDoneCount = 0;

        foreach ($all as $g) {
            if ($g['status'] === 'active') {
                $activeCount++;
                if ($g['progress_percent'] >= 75.0 && $g['progress_percent'] < 100.0) {
                    $nearlyDoneCount++;
                }
            } elseif ($g['status'] === 'completed') {
                $completedCount++;
            } elseif ($g['status'] === 'paused') {
                $pausedCount++;
            } elseif ($g['status'] === 'archived') {
                $archivedCount++;
            }
        }

        return [
            'total'          => count($all),
            'active'         => $activeCount,
            'completed'      => $completedCount,
            'paused'         => $pausedCount,
            'archived'       => $archivedCount,
            'nearly_done'    => $nearlyDoneCount
        ];
    }
}

if (!function_exists('record_goal_progress')) {
    /**
     * Records a new progress log entry and updates goal current_value.
     */
    function record_goal_progress(PDO $pdo, int $userId, int $goalId, float $currentValue, ?string $notes = null, string $sourceType = 'manual', ?string $sourceRecordId = null, ?string $recordedAt = null): bool {
        $goal = get_goal_by_id($pdo, $userId, $goalId);
        if (!$goal) {
            return false;
        }

        $date = $recordedAt ?: date('Y-m-d');
        $calc = calculate_goal_progress(
            (float)$goal['initial_value'],
            (float)$goal['target_value'],
            $currentValue,
            $goal['direction']
        );

        // Insert into progress logs
        $stmt = $pdo->prepare("INSERT INTO goal_progress_logs 
            (goal_id, user_id, current_value, progress_percentage, source_type, source_record_id, notes, recorded_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([
            $goalId, $userId, $currentValue, $calc['percentage'], $sourceType, $sourceRecordId, $notes, $date
        ]);

        if ($ok) {
            // Update goal current_value
            $updSql = "UPDATE health_goals SET current_value = ?, updated_at = CURRENT_TIMESTAMP WHERE id_goal = ? AND user_id = ?";
            $pdo->prepare($updSql)->execute([$currentValue, $goalId, $userId]);

            // Auto-complete if achieved
            if ($calc['is_achieved'] && $goal['status'] === 'active') {
                set_goal_status($pdo, $userId, $goalId, 'completed');
            } else {
                log_goal_activity($pdo, $userId, $goalId, 'progress_updated', "Progres diperbarui ke {$currentValue} {$goal['unit']} ({$calc['percentage']}%).");
            }
        }

        return $ok;
    }
}

if (!function_exists('get_goal_progress_logs')) {
    /**
     * Retrieves chronological progress logs for a specific goal.
     */
    function get_goal_progress_logs(PDO $pdo, int $userId, int $goalId): array {
        $stmt = $pdo->prepare("SELECT * FROM goal_progress_logs WHERE goal_id = ? AND user_id = ? ORDER BY recorded_at ASC, id_progress ASC");
        $stmt->execute([$goalId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('sync_external_data_to_goals')) {
    /**
     * Automatically synchronizes latest assessment results or daily check-ins to active goals.
     * Prevents duplicate synchronization for the same source record.
     */
    function sync_external_data_to_goals(PDO $pdo, int $userId): int {
        $goals = get_user_goals($pdo, $userId, ['status' => 'active']);
        $syncedCount = 0;

        foreach ($goals as $g) {
            $goalId = (int)$g['id_goal'];
            $type = $g['goal_type'];

            // 1. Sync Weight
            if (in_array($type, ['weight_loss', 'weight_gain', 'weight'], true)) {
                $stmt = $pdo->prepare("SELECT id_bmi AS id, berat_badan AS val, created_at FROM cek_bmi WHERE user_id = ? ORDER BY id_bmi DESC LIMIT 1");
                $stmt->execute([$userId]);
                $latest = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($latest) {
                    $srcId = 'bmi_' . $latest['id'];
                    $exists = (int)fetch_col($pdo, "SELECT count(*) FROM goal_progress_logs WHERE goal_id = ? AND source_record_id = ?", [$goalId, $srcId]);
                    if ($exists === 0) {
                        $date = date('Y-m-d', strtotime($latest['created_at']));
                        record_goal_progress($pdo, $userId, $goalId, (float)$latest['val'], 'Sinkronisasi otomatis dari tes BMI', 'calculator', $srcId, $date);
                        $syncedCount++;
                    }
                }
            }

            // 2. Sync Waist Circumference
            if ($type === 'waist') {
                $stmt = $pdo->prepare("SELECT id_whtr AS id, lingkar_pinggang AS val, created_at FROM cek_whtr WHERE user_id = ? ORDER BY id_whtr DESC LIMIT 1");
                $stmt->execute([$userId]);
                $latest = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($latest) {
                    $srcId = 'whtr_' . $latest['id'];
                    $exists = (int)fetch_col($pdo, "SELECT count(*) FROM goal_progress_logs WHERE goal_id = ? AND source_record_id = ?", [$goalId, $srcId]);
                    if ($exists === 0) {
                        $date = date('Y-m-d', strtotime($latest['created_at']));
                        record_goal_progress($pdo, $userId, $goalId, (float)$latest['val'], 'Sinkronisasi otomatis dari tes WHtR', 'calculator', $srcId, $date);
                        $syncedCount++;
                    }
                }
            }

            // 3. Sync Steps from Daily Check-ins
            if ($type === 'steps') {
                $stmt = $pdo->prepare("SELECT id_checkin AS id, steps_count AS val, tanggal FROM daily_checkins WHERE user_id = ? AND steps_count IS NOT NULL ORDER BY tanggal DESC LIMIT 1");
                $stmt->execute([$userId]);
                $latest = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($latest) {
                    $srcId = 'checkin_steps_' . $latest['id'];
                    $exists = (int)fetch_col($pdo, "SELECT count(*) FROM goal_progress_logs WHERE goal_id = ? AND source_record_id = ?", [$goalId, $srcId]);
                    if ($exists === 0) {
                        record_goal_progress($pdo, $userId, $goalId, (float)$latest['val'], 'Sinkronisasi otomatis dari Daily Check-in', 'checkin', $srcId, $latest['tanggal']);
                        $syncedCount++;
                    }
                }
            }

            // 4. Sync Sleep from Daily Check-ins
            if ($type === 'sleep_duration') {
                $stmt = $pdo->prepare("SELECT id_checkin AS id, sleep_hours AS val, tanggal FROM daily_checkins WHERE user_id = ? AND sleep_hours IS NOT NULL ORDER BY tanggal DESC LIMIT 1");
                $stmt->execute([$userId]);
                $latest = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($latest) {
                    $srcId = 'checkin_sleep_' . $latest['id'];
                    $exists = (int)fetch_col($pdo, "SELECT count(*) FROM goal_progress_logs WHERE goal_id = ? AND source_record_id = ?", [$goalId, $srcId]);
                    if ($exists === 0) {
                        record_goal_progress($pdo, $userId, $goalId, (float)$latest['val'], 'Sinkronisasi otomatis dari Daily Check-in', 'checkin', $srcId, $latest['tanggal']);
                        $syncedCount++;
                    }
                }
            }

            // 5. Sync Water from Daily Check-ins
            if ($type === 'water_intake') {
                $stmt = $pdo->prepare("SELECT id_checkin AS id, water_ml AS val, tanggal FROM daily_checkins WHERE user_id = ? AND water_ml IS NOT NULL ORDER BY tanggal DESC LIMIT 1");
                $stmt->execute([$userId]);
                $latest = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($latest) {
                    $srcId = 'checkin_water_' . $latest['id'];
                    $exists = (int)fetch_col($pdo, "SELECT count(*) FROM goal_progress_logs WHERE goal_id = ? AND source_record_id = ?", [$goalId, $srcId]);
                    if ($exists === 0) {
                        record_goal_progress($pdo, $userId, $goalId, (float)$latest['val'], 'Sinkronisasi otomatis dari Daily Check-in', 'checkin', $srcId, $latest['tanggal']);
                        $syncedCount++;
                    }
                }
            }
        }

        return $syncedCount;
    }
}

if (!function_exists('fetch_col')) {
    function fetch_col(PDO $pdo, string $sql, array $params = []): mixed {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}

// -------------------------------------------------------------
// Milestones Management
// -------------------------------------------------------------

if (!function_exists('create_goal_milestone')) {
    function create_goal_milestone(PDO $pdo, int $userId, int $goalId, array $data): int {
        $goal = get_goal_by_id($pdo, $userId, $goalId);
        if (!$goal) {
            return 0;
        }

        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $targetVal   = (isset($data['target_value']) && $data['target_value'] !== '') ? (float)$data['target_value'] : null;
        $unit        = trim($data['unit'] ?? $goal['unit']);
        $seqOrder    = max(1, (int)($data['sequence_order'] ?? 1));
        $targetDate  = !empty($data['target_date']) ? trim($data['target_date']) : null;

        $stmt = $pdo->prepare("INSERT INTO goal_milestones 
            (goal_id, user_id, title, description, target_value, unit, sequence_order, target_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$goalId, $userId, $title, $description, $targetVal, $unit, $seqOrder, $targetDate]);
        $msId = (int)$pdo->lastInsertId();

        log_goal_activity($pdo, $userId, $goalId, 'milestone_created', "Milestone baru '{$title}' ditambahkan.");
        return $msId;
    }
}

if (!function_exists('update_goal_milestone')) {
    function update_goal_milestone(PDO $pdo, int $userId, int $milestoneId, array $data): bool {
        $stmt = $pdo->prepare("SELECT * FROM goal_milestones WHERE id_milestone = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$milestoneId, $userId]);
        $ms = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ms) {
            return false;
        }

        $title       = trim($data['title'] ?? $ms['title']);
        $description = trim($data['description'] ?? $ms['description']);
        $targetVal   = isset($data['target_value']) ? (float)$data['target_value'] : $ms['target_value'];
        $seqOrder    = isset($data['sequence_order']) ? (int)$data['sequence_order'] : (int)$ms['sequence_order'];
        $targetDate  = !empty($data['target_date']) ? trim($data['target_date']) : $ms['target_date'];

        $upd = $pdo->prepare("UPDATE goal_milestones SET 
            title = ?, description = ?, target_value = ?, sequence_order = ?, target_date = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id_milestone = ? AND user_id = ?");
        return $upd->execute([$title, $description, $targetVal, $seqOrder, $targetDate, $milestoneId, $userId]);
    }
}

if (!function_exists('set_milestone_status')) {
    function set_milestone_status(PDO $pdo, int $userId, int $milestoneId, string $status): bool {
        $stmt = $pdo->prepare("SELECT * FROM goal_milestones WHERE id_milestone = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$milestoneId, $userId]);
        $ms = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ms) {
            return false;
        }

        $allowed = ['pending', 'in_progress', 'completed', 'skipped'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

        $upd = $pdo->prepare("UPDATE goal_milestones SET status = ?, completed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id_milestone = ? AND user_id = ?");
        $ok = $upd->execute([$status, $completedAt, $milestoneId, $userId]);

        if ($ok && $status === 'completed') {
            log_goal_activity($pdo, $userId, (int)$ms['goal_id'], 'milestone_completed', "Milestone '{$ms['title']}' telah selesai dicapai.");
        }

        return $ok;
    }
}

if (!function_exists('delete_goal_milestone')) {
    function delete_goal_milestone(PDO $pdo, int $userId, int $milestoneId): bool {
        $stmt = $pdo->prepare("SELECT id_milestone FROM goal_milestones WHERE id_milestone = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$milestoneId, $userId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $del = $pdo->prepare("DELETE FROM goal_milestones WHERE id_milestone = ? AND user_id = ?");
        return $del->execute([$milestoneId, $userId]);
    }
}

if (!function_exists('get_goal_milestones')) {
    function get_goal_milestones(PDO $pdo, int $userId, int $goalId): array {
        $stmt = $pdo->prepare("SELECT * FROM goal_milestones WHERE goal_id = ? AND user_id = ? ORDER BY sequence_order ASC, id_milestone ASC");
        $stmt->execute([$goalId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('get_milestone_suggestions')) {
    /**
     * Provides structured, realistic, evidence-based milestone suggestions for health goals.
     */
    function get_milestone_suggestions(string $category, string $goalType, float $initial, float $target, string $unit): array {
        $suggestions = [];

        if ($goalType === 'weight_loss') {
            $totalLoss = max(0.0, $initial - $target);
            $step1 = round($initial - ($totalLoss * 0.25), 1);
            $step2 = round($initial - ($totalLoss * 0.50), 1);
            $step3 = round($initial - ($totalLoss * 0.75), 1);

            $suggestions = [
                ['title' => 'Fase 1: Adaptasi Pola Makan Sehat', 'desc' => "Mulai kurangi asupan gula berlebih dan targetkan berat {$step1} {$unit}", 'target_val' => $step1, 'order' => 1],
                ['title' => 'Fase 2: Defisit Kalori Konsisten', 'desc' => "Kombinasikan defisit 300–500 kkal dan capai target {$step2} {$unit}", 'target_val' => $step2, 'order' => 2],
                ['title' => 'Fase 3: Penguatan Kebugaran Fisik', 'desc' => "Tingkatkan intensitas latihan kardio dan capai {$step3} {$unit}", 'target_val' => $step3, 'order' => 3],
                ['title' => 'Fase 4: Capai Target Akhir & Pemeliharaan', 'desc' => "Menstabilkan gaya hidup sehat pada berat ideal {$target} {$unit}", 'target_val' => $target, 'order' => 4],
            ];
        } elseif ($goalType === 'steps') {
            $suggestions = [
                ['title' => 'Tahap 1: Membiasakan 5.000 Langkah', 'desc' => 'Jalan santai minimal 20 menit setiap hari', 'target_val' => 5000, 'order' => 1],
                ['title' => 'Tahap 2: Tingkatkan ke 7.500 Langkah', 'desc' => 'Gunakan tangga dan pilih jalan kaki untuk jarak dekat', 'target_val' => 7500, 'order' => 2],
                ['title' => 'Tahap 3: Capai Target 10.000 Langkah', 'desc' => 'Konsistensi 10.000 langkah harian untuk kesehatan jantung', 'target_val' => $target, 'order' => 3],
            ];
        } elseif ($goalType === 'sleep_duration') {
            $suggestions = [
                ['title' => 'Tahap 1: Tetapkan Jadwal Tidur Konsisten', 'desc' => 'Tidur dan bangun pada jam yang sama setiap hari', 'target_val' => 7.0, 'order' => 1],
                ['title' => 'Tahap 2: Kurangi Paparan Layar Gawai', 'desc' => 'Hentikan gawai 30 menit sebelum tidur malam', 'target_val' => 7.5, 'order' => 2],
                ['title' => 'Tahap 3: Capai Durasi Optimal 8 Jam', 'desc' => 'Tidur lelap 8 jam untuk pemulihan sirkadian maksimal', 'target_val' => $target, 'order' => 3],
            ];
        } elseif ($goalType === 'water_intake') {
            $suggestions = [
                ['title' => 'Tahap 1: Minum 1 Gelas Air Saat Bangun', 'desc' => 'Mengawali hidrasi pagi hari sebanyak 250–500 ml', 'target_val' => 1500, 'order' => 1],
                ['title' => 'Tahap 2: Membawa Botol Minum Pribadi', 'desc' => 'Menjaga asupan teratur sepanjang hari', 'target_val' => 2000, 'order' => 2],
                ['title' => 'Tahap 3: Capai Hidrasi Harian Ideal', 'desc' => "Mencapai target harian penuh {$target} {$unit}", 'target_val' => $target, 'order' => 3],
            ];
        } else {
            $suggestions = [
                ['title' => 'Tahap 1: Memulai Kebiasaan Baru', 'desc' => 'Bangun konsistensi di minggu-minggu awal', 'target_val' => null, 'order' => 1],
                ['title' => 'Tahap 2: Pertahankan Rutinitas', 'desc' => 'Evaluasi kendala dan pertahankan kedisiplinan', 'target_val' => null, 'order' => 2],
                ['title' => 'Tahap 3: Capai Target Akhir', 'desc' => 'Mewujudkan sasaran target kesehatan secara konsisten', 'target_val' => $target, 'order' => 3],
            ];
        }

        return $suggestions;
    }
}

// -------------------------------------------------------------
// Goal Activity Logging
// -------------------------------------------------------------

if (!function_exists('log_goal_activity')) {
    function log_goal_activity(PDO $pdo, int $userId, int $goalId, string $type, string $description): bool {
        $stmt = $pdo->prepare("INSERT INTO goal_activity_logs (goal_id, user_id, activity_type, description) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$goalId, $userId, $type, $description]);
    }
}

if (!function_exists('get_recent_goal_activities')) {
    function get_recent_goal_activities(PDO $pdo, int $userId, int $limit = 5): array {
        $stmt = $pdo->prepare("SELECT a.*, g.title AS goal_title, g.unit, g.category 
            FROM goal_activity_logs a 
            JOIN health_goals g ON a.goal_id = g.id_goal 
            WHERE a.user_id = ? 
            ORDER BY a.created_at DESC, a.id_activity DESC LIMIT " . (int)$limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
