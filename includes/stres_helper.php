<?php
/**
 * Stress Assessment Helper (PSS-10) for HealthCheck
 * Reference: Cohen S, Kamarck T, Mermelstein R (J Health Soc Behav 1983).
 * Perceived Stress Scale - 10 Items (with official reverse scoring on items 4, 5, 7, 8).
 */

if (!function_exists('calculate_pss_stress_score')) {
    /**
     * Calculates PSS-10 stress score from 10 Likert responses (0 to 4).
     * Items 4, 5, 7, 8 are positive items and scored in reverse (0->4, 1->3, 2->2, 3->1, 4->0).
     */
    function calculate_pss_stress_score(array $answers): array {
        $q = [];
        for ($i = 1; $i <= 10; $i++) {
            $val = filter_var($answers['q' . $i] ?? 0, FILTER_VALIDATE_INT);
            $q[$i] = max(0, min(4, (int)$val));
        }

        // Direct scoring on negative items: 1, 2, 3, 6, 9, 10
        $directScore = $q[1] + $q[2] + $q[3] + $q[6] + $q[9] + $q[10];

        // Reverse scoring on positive items: 4, 5, 7, 8 (4 - value)
        $reverseScore = (4 - $q[4]) + (4 - $q[5]) + (4 - $q[7]) + (4 - $q[8]);

        $totalScore = $directScore + $reverseScore;

        if ($totalScore <= 13) {
            $tingkat = 'Tingkat Stres Rendah (Low Stress)';
            $badge   = 'Stres Rendah';
            $color   = 'success';
            $advice  = 'Tingkat persepsi stres Anda dalam sebulan terakhir tergolong rendah dan terkontrol dengan baik. Tetap rawat keseimbangan ritme kerja, relaksasi, dan kebiasaan hidup sehat.';
        } elseif ($totalScore <= 26) {
            $tingkat = 'Tingkat Stres Sedang (Moderate Stress)';
            $badge   = 'Stres Sedang';
            $color   = 'warning';
            $advice  = 'Anda merasakan tekanan hidup pada taraf sedang yang lazim dialami dalam aktivitas harian. Luangkan waktu untuk istirahat teratur, bicarakan beban pikiran dengan rekan dekat, dan lakukan teknik pernapasan atau olahraga ringan saat merasa lelah.';
        } else {
            $tingkat = 'Tingkat Stres Tinggi (High Perceived Stress)';
            $badge   = 'Stres Tinggi';
            $color   = 'danger';
            $advice  = 'Hasil penapisan mengindikasikan beban persepsi stres yang cukup berat dalam sebulan terakhir. Sangat dianjurkan untuk memprioritaskan perawatan diri, mengurangi beban berlebih, dan mempertimbangkan konsultasi bersama psikolog klinis atau konselor profesional.';
        }

        return [
            'total_score'   => $totalScore,
            'max_score'     => 40,
            'direct_score'  => $directScore,
            'reverse_score' => $reverseScore,
            'tingkat'       => $tingkat,
            'badge'         => $badge,
            'color'         => $color,
            'advice'        => $advice
        ];
    }
}
