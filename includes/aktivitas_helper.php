<?php
/**
 * Physical Activity Assessment Helper (IPAQ Short Form) for HealthCheck
 * Reference: Craig CL, et al. (Med Sci Sports Exerc 2003); Guidelines for Data Processing and Analysis of the IPAQ.
 */

if (!function_exists('calculate_ipaq_activity')) {
    /**
     * Calculates IPAQ Short Form scores and categorizes physical activity level.
     * Applies official IPAQ data cleaning rules:
     * - Exclude bouts < 10 minutes (re-coded to 0).
     * - Truncate variables > 180 minutes/day to 180 minutes.
     */
    function calculate_ipaq_activity(int $vigDays, int $vigMin, int $modDays, int $modMin, int $walkDays, int $walkMin, float $sittingHours): array {
        // Enforce valid bounds
        $vDays = max(0, min(7, $vigDays));
        $mDays = max(0, min(7, $modDays));
        $wDays = max(0, min(7, $walkDays));

        // IPAQ Rule: Exclude activities < 10 mins; truncate to 180 min
        $vMinClean = ($vigMin < 10) ? 0 : min(180, $vigMin);
        $mMinClean = ($modMin < 10) ? 0 : min(180, $modMin);
        $wMinClean = ($walkMin < 10) ? 0 : min(180, $walkMin);

        // If days is 0, minutes must be 0
        if ($vDays === 0) $vMinClean = 0;
        if ($mDays === 0) $mMinClean = 0;
        if ($wDays === 0) $wMinClean = 0;

        // MET Coefficients: Vigorous = 8.0, Moderate = 4.0, Walking = 3.3
        $vigMet  = 8.0 * $vDays * $vMinClean;
        $modMet  = 4.0 * $mDays * $mMinClean;
        $walkMet = 3.3 * $wDays * $wMinClean;

        $totalMetMinutes = round($vigMet + $modMet + $walkMet, 0);
        $totalDays = max($vDays, $mDays, $wDays);
        $totalActiveDays = min(7, $vDays + $mDays + $wDays);

        // Official IPAQ Classification
        // Category 3: High
        $isHigh = false;
        if ($vDays >= 3 && $totalMetMinutes >= 1500) {
            $isHigh = true;
        } elseif ($totalActiveDays >= 7 && $totalMetMinutes >= 3000) {
            $isHigh = true;
        }

        // Category 2: Moderate
        $isModerate = false;
        if (!$isHigh) {
            if ($vDays >= 3 && $vMinClean >= 20) {
                $isModerate = true;
            } elseif (($mDays >= 5 || $wDays >= 5) && ($mMinClean >= 30 || $wMinClean >= 30)) {
                $isModerate = true;
            } elseif ($totalActiveDays >= 5 && $totalMetMinutes >= 600) {
                $isModerate = true;
            }
        }

        if ($isHigh) {
            $kategori = 'Tinggi (Highly Active)';
            $badge    = 'Tinggi';
            $color    = 'success';
            $advice   = 'Tingkat aktivitas fisik Anda tergolong tinggi dan melampaui rekomendasi minimum WHO. Pola ini sangat efektif memelihara kebugaran kardiorespirasi dan metabolisme.';
        } elseif ($isModerate) {
            $kategori = 'Sedang (Moderately Active)';
            $badge    = 'Sedang / Cukup';
            $color    = 'info';
            $advice   = 'Aktivitas fisik mingguan Anda telah memenuhi rekomendasi dasar kesehatan WHO (setara ≥150 menit aktivitas sedang per minggu). Pertahankan rutinitas olahraga Anda.';
        } else {
            $kategori = 'Rendah (Sedentary / Inactive)';
            $badge    = 'Rendah / Sedentari';
            $color    = 'warning';
            $advice   = 'Total pengeluaran energi aktivitas fisik Anda berada di bawah ambang batas sehat WHO (<600 MET-menit/minggu). Cobalah menambah jalan kaki minimal 30 menit sehari atau bersepeda ringan.';
        }

        // Sitting risk
        $sittingWarning = false;
        if ($sittingHours >= 8.0) {
            $sittingWarning = true;
            $advice .= ' Perhatian: Waktu duduk ≥8 jam sehari meningkatkan risiko kardiometabolik. Selingi dengan berdiri atau peregangan tiap 45 menit.';
        }

        return [
            'total_met'        => $totalMetMinutes,
            'vigorous_met'     => round($vigMet, 0),
            'moderate_met'     => round($modMet, 0),
            'walking_met'      => round($walkMet, 0),
            'kategori'         => $kategori,
            'badge'            => $badge,
            'color'            => $color,
            'advice'           => $advice,
            'sitting_hours'    => $sittingHours,
            'sitting_warning'  => $sittingWarning
        ];
    }
}
