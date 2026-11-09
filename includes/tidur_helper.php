<?php
/**
 * Sleep Habit Assessment Helper for HealthCheck
 * Non-diagnostic sleep hygiene & sleep habit assessment (honest alternative to proprietary PSQI).
 * Evaluates duration, latency, nocturnal awakenings, screen habits, and morning restoration.
 */

if (!function_exists('calculate_sleep_habit_score')) {
    /**
     * Evaluates sleep habit parameters and calculates a composite sleep hygiene score (0–15).
     * Lower score = better sleep habits; higher score = greater sleep hygiene disturbance.
     */
    function calculate_sleep_habit_score(float $durationHours, int $latencyMinutes, int $awakenings, string $screenDevice, string $morningFreshness): array {
        $score = 0;

        // 1. Duration scoring (Optimal: 7–9 hours for adults)
        if ($durationHours >= 7.0 && $durationHours <= 9.0) {
            $durationScore = 0;
        } elseif (($durationHours >= 6.0 && $durationHours < 7.0) || ($durationHours > 9.0 && $durationHours <= 10.0)) {
            $durationScore = 1;
        } elseif ($durationHours >= 5.0 && $durationHours < 6.0) {
            $durationScore = 2;
        } else {
            $durationScore = 3; // < 5 hours or > 10 hours
        }
        $score += $durationScore;

        // 2. Sleep Latency scoring (Time to fall asleep)
        if ($latencyMinutes <= 15) {
            $latencyScore = 0;
        } elseif ($latencyMinutes <= 30) {
            $latencyScore = 1;
        } elseif ($latencyMinutes <= 60) {
            $latencyScore = 2;
        } else {
            $latencyScore = 3; // > 60 minutes
        }
        $score += $latencyScore;

        // 3. Nocturnal Awakenings scoring
        if ($awakenings === 0) {
            $awakeningScore = 0;
        } elseif ($awakenings <= 1) {
            $awakeningScore = 1;
        } elseif ($awakenings <= 2) {
            $awakeningScore = 2;
        } else {
            $awakeningScore = 3; // >= 3 times
        }
        $score += $awakeningScore;

        // 4. Pre-sleep Screen / Device usage
        $screen = strtolower(trim($screenDevice));
        if ($screen === 'tidak' || $screen === 'jarang') {
            $screenScore = 0;
        } elseif ($screen === 'kadang') {
            $screenScore = 1;
        } else {
            $screenScore = 3; // 'sering' / 'setiap_malam'
        }
        $score += $screenScore;

        // 5. Morning Restorativeness
        $fresh = strtolower(trim($morningFreshness));
        if ($fresh === 'sangat_segar') {
            $freshScore = 0;
        } elseif ($fresh === 'cukup_segar') {
            $freshScore = 1;
        } elseif ($fresh === 'kurang_segar') {
            $freshScore = 2;
        } else {
            $freshScore = 3; // 'lelah_mengantuk'
        }
        $score += $freshScore;

        // Sleep Efficiency estimation: based on duration vs (duration + latency/60 + awakenings*0.25)
        $timeInBed = $durationHours + ($latencyMinutes / 60.0) + ($awakenings * 0.25);
        $efficiency = $timeInBed > 0 ? min(100.0, round(($durationHours / $timeInBed) * 100.0, 1)) : 85.0;

        // Categorization based on composite score (0–15)
        if ($score <= 3) {
            $category = 'Kualitas Tidur Sangat Baik';
            $badge    = 'Sangat Baik';
            $color    = 'success';
            $advice   = 'Pola dan kebiasaan tidur Anda sangat optimal (durasi cukup, latensi wajar, dan bangun dengan segar). Pertahankan konsistensi jadwal tidur Anda setiap hari.';
        } elseif ($score <= 7) {
            $category = 'Kualitas Tidur Cukup (Gangguan Ringan)';
            $badge    = 'Cukup Baik';
            $color    = 'info';
            $advice   = 'Kebiasaan tidur Anda tergolong cukup baik, namun terdapat ruang perbaikan pada latensi tidur atau jeda layar sebelum tidur. Hindari paparan gawai 30 menit sebelum beristirahat.';
        } elseif ($score <= 11) {
            $category = 'Kualitas Tidur Kurang Optimal';
            $badge    = 'Kurang Optimal';
            $color    = 'warning';
            $advice   = 'Pola tidur Anda menunjukkan indikasi higiene tidur yang kurang optimal (durasi kurang/berlebih, sering terbangun, atau bangun dalam kondisi lelah). Terapkan jadwal tidur konsisten dan ciptakan suasana kamar yang sejuk dan gelap.';
        } else {
            $category = 'Gangguan Pola Tidur Signifikan';
            $badge    = 'Perlu Perhatian';
            $color    = 'danger';
            $advice   = 'Hasil evaluasi mengindikasikan beban gangguan tidur yang signifikan. Jika keluhan insomnia atau lelah kronis ini berlangsung lebih dari 3 minggu dan mengganggu produktivitas harian, sangat dianjurkan untuk berkonsultasi dengan dokter umum atau spesialis gangguan tidur.';
        }

        return [
            'score'           => $score,
            'max_score'       => 15,
            'efficiency'      => $efficiency,
            'category'        => $category,
            'badge'           => $badge,
            'color'           => $color,
            'advice'          => $advice,
            'duration_hours'  => $durationHours,
            'latency_minutes' => $latencyMinutes,
            'awakenings'      => $awakenings
        ];
    }
}
