<?php
/**
 * Unified Health Assessment History & Analytics Helper for HealthCheck
 * Integrates all 12 health assessment tools with server-side ownership and data isolation.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth_helper.php';

if (!function_exists('get_tool_metadata')) {
    function get_tool_metadata(): array {
        return [
            'bmi' => [
                'type'           => 'bmi',
                'title'          => 'Indeks Massa Tubuh (BMI)',
                'category'       => 'gizi',
                'category_label' => 'Gizi & Tubuh',
                'table'          => 'cek_bmi',
                'id_column'      => 'id_bmi',
                'color'          => 'blue',
                'icon'           => 'bi-speedometer2',
                'form_url'       => '../bmi/bmi_form.php'
            ],
            'bmr' => [
                'type'           => 'bmr',
                'title'          => 'Kebutuhan Kalori & BMR (TDEE)',
                'category'       => 'gizi',
                'category_label' => 'Energi & Makro',
                'table'          => 'cek_bmr',
                'id_column'      => 'id_bmr',
                'color'          => 'emerald',
                'icon'           => 'bi-fire',
                'form_url'       => '../bmr/bmr_form.php'
            ],
            'whtr' => [
                'type'           => 'whtr',
                'title'          => 'Rasio Pinggang ke Tinggi (WHtR)',
                'category'       => 'gizi',
                'category_label' => 'Adipositas Sentral',
                'table'          => 'cek_whtr',
                'id_column'      => 'id_whtr',
                'color'          => 'teal',
                'icon'           => 'bi-rulers',
                'form_url'       => '../whtr/whtr_form.php'
            ],
            'body_fat' => [
                'type'           => 'body_fat',
                'title'          => 'Persentase Lemak Tubuh (US Navy)',
                'category'       => 'gizi',
                'category_label' => 'Komposisi Tubuh',
                'table'          => 'cek_lemak_tubuh',
                'id_column'      => 'id_fat',
                'color'          => 'blue',
                'icon'           => 'bi-person-lines-fill',
                'form_url'       => '../body_fat/body_fat_form.php'
            ],
            'cairan' => [
                'type'           => 'cairan',
                'title'          => 'Kebutuhan Cairan Harian',
                'category'       => 'gizi',
                'category_label' => 'Hidrasi Sehat',
                'table'          => 'cek_kebutuhan_cairan',
                'id_column'      => 'id_cairan',
                'color'          => 'teal',
                'icon'           => 'bi-droplet-half',
                'form_url'       => '../cairan/cairan_form.php'
            ],
            'tekanan_darah' => [
                'type'           => 'tekanan_darah',
                'title'          => 'Pemeriksaan Tekanan Darah',
                'category'       => 'kardio',
                'category_label' => 'Kardiovaskular',
                'table'          => 'cek_tekanan_darah',
                'id_column'      => 'id_bp',
                'color'          => 'coral',
                'icon'           => 'bi-heart-pulse-fill',
                'form_url'       => '../tekanan_darah/tekanan_darah_form.php'
            ],
            'jantung' => [
                'type'           => 'jantung',
                'title'          => 'Detak Nadi Istirahat (RHR)',
                'category'       => 'kardio',
                'category_label' => 'Kardiovaskular',
                'table'          => 'cek_jantung',
                'id_column'      => 'id_jantung',
                'color'          => 'coral',
                'icon'           => 'bi-heart-pulse',
                'form_url'       => '../jantung/jantung_form.php'
            ],
            'zona_jantung' => [
                'type'           => 'zona_jantung',
                'title'          => 'Zona Latihan Detak Jantung',
                'category'       => 'kardio',
                'category_label' => 'Kardiovaskular',
                'table'          => 'cek_zona_detak_jantung',
                'id_column'      => 'id_zona',
                'color'          => 'blue',
                'icon'           => 'bi-activity',
                'form_url'       => '../zona_jantung/zona_jantung_form.php'
            ],
            'tidur' => [
                'type'           => 'tidur',
                'title'          => 'Kebiasaan & Higiene Tidur',
                'category'       => 'tidur',
                'category_label' => 'Pola Istirahat',
                'table'          => 'cek_kualitas_tidur',
                'id_column'      => 'id_tidur',
                'color'          => 'teal',
                'icon'           => 'bi-moon-stars',
                'form_url'       => '../tidur/tidur_form.php'
            ],
            'aktivitas_fisik' => [
                'type'           => 'aktivitas_fisik',
                'title'          => 'Aktivitas Fisik (IPAQ)',
                'category'       => 'gizi',
                'category_label' => 'Kebugaran Fisik',
                'table'          => 'cek_aktivitas_fisik',
                'id_column'      => 'id_aktivitas',
                'color'          => 'emerald',
                'icon'           => 'bi-person-walking',
                'form_url'       => '../aktivitas_fisik/aktivitas_fisik_form.php'
            ],
            'stres' => [
                'type'           => 'stres',
                'title'          => 'Penilaian Tingkat Stres (PSS-10)',
                'category'       => 'mental',
                'category_label' => 'Kesehatan Mental',
                'table'          => 'cek_stres',
                'id_column'      => 'id_stres',
                'color'          => 'lavender',
                'icon'           => 'bi-emoji-smile',
                'form_url'       => '../stres/stres_form.php'
            ],
            'mental' => [
                'type'           => 'mental',
                'title'          => 'Skrining Mental Mood (PHQ-4)',
                'category'       => 'mental',
                'category_label' => 'Kesehatan Mental',
                'table'          => 'cek_mental',
                'id_column'      => 'id_mental',
                'color'          => 'lavender',
                'icon'           => 'bi-chat-heart',
                'form_url'       => '../mental/mental_form.php'
            ]
        ];
    }
}

if (!function_exists('get_user_unified_history')) {
    /**
     * Aggregates all records belonging to $userId from all 12 tables.
     * Applies in-memory sorting, category filtering, search, and pagination.
     */
    function get_user_unified_history(PDO $pdo, int $userId, array $filters = []): array {
        $meta = get_tool_metadata();
        $allRecords = [];

        foreach ($meta as $toolKey => $m) {
            $table = $m['table'];
            $idCol = $m['id_column'];

            // Query user records
            $stmt = $pdo->prepare("SELECT *, '{$toolKey}' AS tool_key FROM {$table} WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                // Determine normalized primary metric and status based on tool
                $metric = '-';
                $status = '-';
                $color  = 'info';

                switch ($toolKey) {
                    case 'bmi':
                        $metric = number_format((float)$r['bmi'], 1) . ' kg/m²';
                        $status = $r['kondisi'];
                        $color  = str_contains($status, 'Normal') ? 'success' : (str_contains($status, 'Kurus') || str_contains($status, 'Kelebihan') ? 'warning' : 'danger');
                        break;
                    case 'bmr':
                        $metric = number_format((float)$r['tdee'], 0, ',', '.') . ' kkal';
                        $status = $r['aktivitas'];
                        $color  = 'emerald';
                        break;
                    case 'whtr':
                        $metric = number_format((float)$r['rasio_whtr'], 2);
                        $status = $r['kategori_risiko'];
                        $color  = str_contains($status, 'Normal') || str_contains($status, 'Sehat') ? 'success' : (str_contains($status, 'Meningkat') || str_contains($status, 'Kurang') ? 'warning' : 'danger');
                        break;
                    case 'body_fat':
                        $metric = number_format((float)$r['body_fat_percent'], 1) . '%';
                        $status = $r['kategori'];
                        $color  = str_contains($status, 'Fitness') || str_contains($status, 'Athletes') ? 'success' : (str_contains($status, 'Obese') || str_contains($status, 'Kelebihan') ? 'danger' : 'info');
                        break;
                    case 'cairan':
                        $metric = number_format((float)$r['kebutuhan_liter'], 1) . ' Liter (~' . $r['kebutuhan_gelas'] . ' gelas)';
                        $status = $r['tingkat_aktivitas'];
                        $color  = 'teal';
                        break;
                    case 'tekanan_darah':
                        $metric = $r['sistolik'] . ' / ' . $r['diastolik'] . ' mmHg';
                        $status = $r['kategori'];
                        $color  = str_contains($status, 'Normal') ? 'success' : (str_contains($status, 'Krisis') || str_contains($status, 'Tingkat 2') ? 'danger' : 'warning');
                        break;
                    case 'jantung':
                        $metric = $r['resting_hr'] . ' BPM';
                        $status = $r['kategori'];
                        $color  = str_contains($status, 'Normal') ? 'success' : (str_contains($status, 'Tinggi') ? 'danger' : 'info');
                        break;
                    case 'zona_jantung':
                        $metric = 'Max ' . $r['max_hr'] . ' BPM';
                        $status = 'Metode ' . ucfirst($r['metode']);
                        $color  = 'blue';
                        break;
                    case 'tidur':
                        $metric = number_format((float)$r['efisiensi_tidur'], 1) . '% (' . $r['durasi_jam'] . ' jam)';
                        $status = $r['kategori_tidur'];
                        $color  = str_contains($status, 'Sangat Baik') ? 'success' : (str_contains($status, 'Cukup') ? 'info' : (str_contains($status, 'Optimal') ? 'warning' : 'danger'));
                        break;
                    case 'aktivitas_fisik':
                        $metric = number_format((float)$r['total_met_menit'], 0, ',', '.') . ' MET-menit';
                        $status = $r['kategori_aktivitas'];
                        $color  = str_contains($status, 'Tinggi') ? 'success' : (str_contains($status, 'Sedang') ? 'info' : 'warning');
                        break;
                    case 'stres':
                        $metric = $r['skor_stres'] . ' / 40';
                        $status = $r['tingkat_stres'];
                        $color  = ((int)$r['skor_stres'] <= 13) ? 'success' : (((int)$r['skor_stres'] <= 26) ? 'warning' : 'danger');
                        break;
                    case 'mental':
                        $metric = $r['total_skor'] . ' / 12';
                        $status = $r['tingkat'];
                        $color  = ((int)$r['total_skor'] <= 2) ? 'success' : (((int)$r['total_skor'] <= 5) ? 'info' : (((int)$r['total_skor'] <= 8) ? 'warning' : 'danger'));
                        break;
                }

                $createdAt = $r['created_at'] ?? date('Y-m-d H:i:s');
                $timestamp = strtotime($createdAt) ?: time();

                $allRecords[] = [
                    'tool_type'      => $toolKey,
                    'tool_title'     => $m['title'],
                    'category'       => $m['category'],
                    'category_label' => $m['category_label'],
                    'color'          => $m['color'],
                    'icon'           => $m['icon'],
                    'record_id'      => (int)$r[$idCol],
                    'created_at'     => $createdAt,
                    'timestamp'      => $timestamp,
                    'date_formatted' => date('d M Y, H:i', $timestamp),
                    'primary_metric' => $metric,
                    'status_label'   => $status,
                    'status_color'   => $color,
                    'detail_url'     => "history_detail.php?type={$toolKey}&id=" . (int)$r[$idCol],
                    'raw'            => $r
                ];
            }
        }

        // Apply filters
        $filtered = array_filter($allRecords, function($item) use ($filters) {
            // Category filter
            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                if ($item['category'] !== $filters['category']) {
                    return false;
                }
            }

            // Specific tool filter
            if (!empty($filters['type']) && $filters['type'] !== 'all') {
                if ($item['tool_type'] !== $filters['type']) {
                    return false;
                }
            }

            // Date range filter
            if (!empty($filters['date_from'])) {
                $fromTs = strtotime($filters['date_from'] . ' 00:00:00');
                if ($fromTs && $item['timestamp'] < $fromTs) {
                    return false;
                }
            }
            if (!empty($filters['date_to'])) {
                $toTs = strtotime($filters['date_to'] . ' 23:59:59');
                if ($toTs && $item['timestamp'] > $toTs) {
                    return false;
                }
            }

            // Keyword search filter
            if (!empty($filters['search'])) {
                $q = strtolower(trim($filters['search']));
                $aliases = [
                    'tekanan_darah'   => 'tensi tensimeter blood pressure sistolik diastolik',
                    'bmi'             => 'imt indeks massa tubuh',
                    'bmr'             => 'tdee kalori makronutrisi energi',
                    'whtr'            => 'rasio pinggang tinggi adipositas',
                    'body_fat'        => 'lemak tubuh us navy komposisi',
                    'cairan'          => 'air hidrasi minum gelas',
                    'jantung'         => 'detak denyut rhr pulse heart rate',
                    'zona_jantung'    => 'zona karvonen zoladz kardio cardio bpm',
                    'tidur'           => 'sleep jam tidur efisiensi istirahat',
                    'aktivitas_fisik' => 'ipaq bergerak kebugaran met olahraga',
                    'stres'           => 'stress persepsi pss-10 pss10',
                    'mental'          => 'phq-4 phq4 kecemasan depresi mood'
                ];
                $alias = $aliases[$item['tool_type']] ?? '';
                $haystack = strtolower($item['tool_title'] . ' ' . $item['category_label'] . ' ' . $item['status_label'] . ' ' . $item['primary_metric'] . ' ' . $alias);
                if (!str_contains($haystack, $q)) {
                    return false;
                }
            }

            return true;
        });

        // Apply sorting (default: latest first)
        $sortOrder = strtoupper($filters['sort'] ?? 'DESC');
        usort($filtered, function($a, $b) use ($sortOrder) {
            if ($sortOrder === 'ASC') {
                return $a['timestamp'] <=> $b['timestamp'];
            }
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $totalCount = count($filtered);

        // Apply pagination if limit is specified
        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $paginated = array_slice($filtered, $offset, $limit);
        } else {
            $paginated = $filtered;
        }

        return [
            'total_count' => $totalCount,
            'records'     => $paginated
        ];
    }
}

if (!function_exists('get_user_latest_indicators')) {
    /**
     * Retrieves the single latest assessment record for each of the 12 tools for $userId.
     * Also checks the previous record to compute individual trends (change delta).
     * Strictly avoids arbitrary composite scoring (No 0-100 Health Score Index).
     */
    function get_user_latest_indicators(PDO $pdo, int $userId): array {
        $meta = get_tool_metadata();
        $indicators = [];

        foreach ($meta as $toolKey => $m) {
            $table = $m['table'];
            $idCol = $m['id_column'];

            // Fetch latest 2 records to compute trend
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE user_id = ? ORDER BY {$idCol} DESC LIMIT 2");
            $stmt->execute([$userId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($records)) {
                $indicators[$toolKey] = [
                    'has_data'       => false,
                    'tool_type'      => $toolKey,
                    'title'          => $m['title'],
                    'category'       => $m['category'],
                    'category_label' => $m['category_label'],
                    'color'          => $m['color'],
                    'icon'           => $m['icon'],
                    'form_url'       => $m['form_url']
                ];
                continue;
            }

            $latest = $records[0];
            $prev   = $records[1] ?? null;

            $timestamp = strtotime($latest['created_at']) ?: time();
            $daysAgo = (int)floor((time() - $timestamp) / 86400);

            // Compute metric, status, and delta trend
            $metric = '';
            $status = '';
            $color = 'info';
            $trendText = '';
            $trendDirection = 'neutral'; // 'up', 'down', 'neutral'

            switch ($toolKey) {
                case 'bmi':
                    $val = (float)$latest['bmi'];
                    $metric = number_format($val, 1) . ' kg/m²';
                    $status = $latest['kondisi'];
                    $color = str_contains($status, 'Normal') ? 'success' : (str_contains($status, 'Kurus') || str_contains($status, 'Kelebihan') ? 'warning' : 'danger');
                    if ($prev) {
                        $pVal = (float)$prev['bmi'];
                        $diff = round($val - $pVal, 1);
                        if ($diff > 0) { $trendText = "+{$diff} kg/m²"; $trendDirection = 'up'; }
                        elseif ($diff < 0) { $trendText = "{$diff} kg/m²"; $trendDirection = 'down'; }
                        else { $trendText = "Stabil"; }
                    }
                    break;
                case 'bmr':
                    $val = (float)$latest['tdee'];
                    $metric = number_format($val, 0, ',', '.') . ' kkal/hari';
                    $status = $latest['aktivitas'];
                    $color = 'emerald';
                    if ($prev) {
                        $diff = round($val - (float)$prev['tdee'], 0);
                        if ($diff > 0) { $trendText = "+{$diff} kkal"; $trendDirection = 'up'; }
                        elseif ($diff < 0) { $trendText = "{$diff} kkal"; $trendDirection = 'down'; }
                        else { $trendText = "Stabil"; }
                    }
                    break;
                case 'whtr':
                    $val = (float)$latest['rasio_whtr'];
                    $metric = number_format($val, 2);
                    $status = $latest['kategori_risiko'];
                    $color = str_contains($status, 'Normal') || str_contains($status, 'Sehat') ? 'success' : 'warning';
                    if ($prev) {
                        $diff = round($val - (float)$prev['rasio_whtr'], 2);
                        if ($diff > 0) { $trendText = "+{$diff}"; $trendDirection = 'up'; }
                        elseif ($diff < 0) { $trendText = "{$diff}"; $trendDirection = 'down'; }
                        else { $trendText = "Stabil"; }
                    }
                    break;
                case 'body_fat':
                    $val = (float)$latest['body_fat_percent'];
                    $metric = number_format($val, 1) . '%';
                    $status = $latest['kategori'];
                    $color = str_contains($status, 'Fitness') || str_contains($status, 'Athletes') ? 'success' : 'info';
                    if ($prev) {
                        $diff = round($val - (float)$prev['body_fat_percent'], 1);
                        if ($diff > 0) { $trendText = "+{$diff}%"; $trendDirection = 'up'; }
                        elseif ($diff < 0) { $trendText = "{$diff}%"; $trendDirection = 'down'; }
                        else { $trendText = "Stabil"; }
                    }
                    break;
                case 'cairan':
                    $val = (float)$latest['kebutuhan_liter'];
                    $metric = number_format($val, 1) . ' Liter/hari';
                    $status = $latest['kebutuhan_gelas'] . ' Gelas air';
                    $color = 'teal';
                    break;
                case 'tekanan_darah':
                    $metric = $latest['sistolik'] . ' / ' . $latest['diastolik'] . ' mmHg';
                    $status = $latest['kategori'];
                    $color = str_contains($status, 'Normal') ? 'success' : (str_contains($status, 'Krisis') || str_contains($status, 'Tingkat 2') ? 'danger' : 'warning');
                    if ($prev) {
                        $diffSis = (int)$latest['sistolik'] - (int)$prev['sistolik'];
                        $diffDia = (int)$latest['diastolik'] - (int)$prev['diastolik'];
                        $trendText = sprintf("%+d / %+d", $diffSis, $diffDia);
                    }
                    break;
                case 'jantung':
                    $val = (int)$latest['resting_hr'];
                    $metric = $val . ' BPM';
                    $status = $latest['kategori'];
                    $color = str_contains($status, 'Normal') ? 'success' : (str_contains($status, 'Tinggi') ? 'danger' : 'info');
                    if ($prev) {
                        $diff = $val - (int)$prev['resting_hr'];
                        if ($diff > 0) { $trendText = "+{$diff} BPM"; $trendDirection = 'up'; }
                        elseif ($diff < 0) { $trendText = "{$diff} BPM"; $trendDirection = 'down'; }
                        else { $trendText = "Stabil"; }
                    }
                    break;
                case 'zona_jantung':
                    $metric = 'Max ' . $latest['max_hr'] . ' BPM';
                    $status = 'Metode ' . ucfirst($latest['metode']);
                    $color = 'blue';
                    break;
                case 'tidur':
                    $metric = number_format((float)$latest['efisiensi_tidur'], 1) . '%';
                    $status = $latest['kategori_tidur'];
                    $color = str_contains($status, 'Sangat Baik') ? 'success' : (str_contains($status, 'Cukup') ? 'info' : 'warning');
                    break;
                case 'aktivitas_fisik':
                    $metric = number_format((float)$latest['total_met_menit'], 0, ',', '.') . ' MET';
                    $status = $latest['kategori_aktivitas'];
                    $color = str_contains($status, 'Tinggi') ? 'success' : (str_contains($status, 'Sedang') ? 'info' : 'warning');
                    break;
                case 'stres':
                    $metric = $latest['skor_stres'] . ' / 40';
                    $status = $latest['tingkat_stres'];
                    $color = ((int)$latest['skor_stres'] <= 13) ? 'success' : (((int)$latest['skor_stres'] <= 26) ? 'warning' : 'danger');
                    break;
                case 'mental':
                    $metric = $latest['total_skor'] . ' / 12';
                    $status = $latest['tingkat'];
                    $color = ((int)$latest['total_skor'] <= 2) ? 'success' : (((int)$latest['total_skor'] <= 5) ? 'info' : 'warning');
                    break;
            }

            $indicators[$toolKey] = [
                'has_data'        => true,
                'tool_type'       => $toolKey,
                'title'           => $m['title'],
                'category'        => $m['category'],
                'category_label'  => $m['category_label'],
                'color'           => $m['color'],
                'icon'            => $m['icon'],
                'form_url'        => $m['form_url'],
                'record_id'       => (int)$latest[$idCol],
                'created_at'      => $latest['created_at'],
                'days_ago'        => $daysAgo,
                'is_recent'       => ($daysAgo <= 14),
                'primary_metric'  => $metric,
                'status_label'    => $status,
                'status_color'    => $color,
                'trend_text'      => $trendText,
                'trend_direction' => $trendDirection,
                'detail_url'      => "history_detail.php?type={$toolKey}&id=" . (int)$latest[$idCol],
                'raw'             => $latest
            ];
        }

        return $indicators;
    }
}

if (!function_exists('get_user_health_trends')) {
    /**
     * Extracts chronological series (ASC) for specific metrics over time.
     * Guaranteed to use only real database records belonging to $userId.
     */
    function get_user_health_trends(PDO $pdo, int $userId): array {
        // 1. BMI & Berat Badan Trends
        $stmtBmi = $pdo->prepare("SELECT created_at, bmi, berat_badan FROM cek_bmi WHERE user_id = ? ORDER BY id_bmi ASC LIMIT 20");
        $stmtBmi->execute([$userId]);
        $bmiSeries = $stmtBmi->fetchAll(PDO::FETCH_ASSOC);

        // 2. Blood Pressure Trends (Systolic & Diastolic)
        $stmtBp = $pdo->prepare("SELECT created_at, sistolik, diastolik, detak_nadi FROM cek_tekanan_darah WHERE user_id = ? ORDER BY id_bp ASC LIMIT 20");
        $stmtBp->execute([$userId]);
        $bpSeries = $stmtBp->fetchAll(PDO::FETCH_ASSOC);

        // 3. Resting Heart Rate Trends
        $stmtHr = $pdo->prepare("SELECT created_at, resting_hr FROM cek_jantung WHERE user_id = ? ORDER BY id_jantung ASC LIMIT 20");
        $stmtHr->execute([$userId]);
        $hrSeries = $stmtHr->fetchAll(PDO::FETCH_ASSOC);

        // 4. Stress Trends (PSS-10)
        $stmtStress = $pdo->prepare("SELECT created_at, skor_stres FROM cek_stres WHERE user_id = ? ORDER BY id_stres ASC LIMIT 20");
        $stmtStress->execute([$userId]);
        $stressSeries = $stmtStress->fetchAll(PDO::FETCH_ASSOC);

        // 5. Sleep Efficiency Trends
        $stmtSleep = $pdo->prepare("SELECT created_at, efisiensi_tidur, durasi_jam FROM cek_kualitas_tidur WHERE user_id = ? ORDER BY id_tidur ASC LIMIT 20");
        $stmtSleep->execute([$userId]);
        $sleepSeries = $stmtSleep->fetchAll(PDO::FETCH_ASSOC);

        return [
            'bmi'            => $bmiSeries,
            'blood_pressure' => $bpSeries,
            'heart_rate'     => $hrSeries,
            'stress'         => $stressSeries,
            'sleep'          => $sleepSeries
        ];
    }
}
