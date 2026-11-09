<?php
/**
 * Heart Rate Training Zones Helper for HealthCheck
 * Reference: Tanaka et al. (JACC 2001) for HR Max; Karvonen Method (HRR-based); Zoladz Method (HR-deficit based).
 */

if (!function_exists('calculate_max_hr')) {
    function calculate_max_hr(int $age): int {
        return (int)round(208.0 - (0.7 * $age));
    }
}

if (!function_exists('calculate_karvonen_zones')) {
    /**
     * Calculates 5 training zones using Karvonen formula: Target = RHR + (% * HRR)
     */
    function calculate_karvonen_zones(int $age, int $rhr): array {
        $maxHr = calculate_max_hr($age);
        $hrr = max(10, $maxHr - $rhr);

        $zones = [
            1 => [
                'zone'        => 1,
                'name'        => 'Zona 1: Pemulihan Aktif (Active Recovery)',
                'intensity'   => '50% – 60% HRR',
                'min'         => (int)round($rhr + (0.50 * $hrr)),
                'max'         => (int)round($rhr + (0.60 * $hrr)),
                'color'       => 'teal',
                'benefit'     => 'Pemanasan, pendinginan, dan pemulihan sirkulasi darah setelah latihan berat.'
            ],
            2 => [
                'zone'        => 2,
                'name'        => 'Zona 2: Pembakaran Lemak (Fat Burn & Base Endurance)',
                'intensity'   => '60% – 70% HRR',
                'min'         => (int)round($rhr + (0.60 * $hrr)),
                'max'         => (int)round($rhr + (0.70 * $hrr)),
                'color'       => 'emerald',
                'benefit'     => 'Optimalisasi efisiensi oksidasi lemak dan pembangunan daya tahan aerobik dasar jangka panjang.'
            ],
            3 => [
                'zone'        => 3,
                'name'        => 'Zona 3: Aerobik & Kapasitas Paru (Aerobic Tempo)',
                'intensity'   => '70% – 80% HRR',
                'min'         => (int)round($rhr + (0.70 * $hrr)),
                'max'         => (int)round($rhr + (0.80 * $hrr)),
                'color'       => 'blue',
                'benefit'     => 'Memperkuat otot bilik jantung, meningkatkan volume sekuncup, dan efisiensi paru.'
            ],
            4 => [
                'zone'        => 4,
                'name'        => 'Zona 4: Ambang Laktat (Anaerobic Threshold)',
                'intensity'   => '80% – 90% HRR',
                'min'         => (int)round($rhr + (0.80 * $hrr)),
                'max'         => (int)round($rhr + (0.90 * $hrr)),
                'color'       => 'amber',
                'benefit'     => 'Meningkatkan batas toleransi tubuh terhadap akumulasi asam laktat dan kecepatan lari.'
            ],
            5 => [
                'zone'        => 5,
                'name'        => 'Zona 5: Beban Maksimal (Max Effort / VO2 Max)',
                'intensity'   => '90% – 100% HRR',
                'min'         => (int)round($rhr + (0.90 * $hrr)),
                'max'         => $maxHr,
                'color'       => 'coral',
                'benefit'     => 'Daya ledak interval pendek neuromuskular (lakukan hanya dalam durasi beberapa detik/menit).'
            ]
        ];

        return [
            'max_hr' => $maxHr,
            'rhr'    => $rhr,
            'hrr'    => $hrr,
            'method' => 'karvonen',
            'zones'  => $zones
        ];
    }
}

if (!function_exists('calculate_zoladz_zones')) {
    /**
     * Calculates 5 training zones using Zoladz method based on fixed HR deficits from Max HR:
     * Z1 = MaxHR - 50 ± 5, Z2 = MaxHR - 40 ± 5, Z3 = MaxHR - 30 ± 5, Z4 = MaxHR - 20 ± 5, Z5 = MaxHR - 10 ± 5
     */
    function calculate_zoladz_zones(int $age): array {
        $maxHr = calculate_max_hr($age);

        $zones = [
            1 => [
                'zone'      => 1,
                'name'      => 'Zona 1: Pemulihan (Recovery)',
                'intensity' => 'Max HR - 50 ± 5 BPM',
                'min'       => max(60, $maxHr - 55),
                'max'       => max(60, $maxHr - 45),
                'color'     => 'teal',
                'benefit'   => 'Pemanasan dan regenerasi serat otot.'
            ],
            2 => [
                'zone'      => 2,
                'name'      => 'Zona 2: Aerobik Ringan (Extensive Endurance)',
                'intensity' => 'Max HR - 40 ± 5 BPM',
                'min'       => max(60, $maxHr - 45),
                'max'       => max(60, $maxHr - 35),
                'color'     => 'emerald',
                'benefit'   => 'Latihan daya tahan jarak jauh berintensitas rendah.'
            ],
            3 => [
                'zone'      => 3,
                'name'      => 'Zona 3: Ambang Aerobik (Intensive Endurance)',
                'intensity' => 'Max HR - 30 ± 5 BPM',
                'min'       => max(60, $maxHr - 35),
                'max'       => max(60, $maxHr - 25),
                'color'     => 'blue',
                'benefit'   => 'Peningkatan kapasitas aerobik dan adaptasi glikogen otot.'
            ],
            4 => [
                'zone'      => 4,
                'name'      => 'Zona 4: Ambang Anaerobik (Sub-Threshold)',
                'intensity' => 'Max HR - 20 ± 5 BPM',
                'min'       => max(60, $maxHr - 25),
                'max'       => max(60, $maxHr - 15),
                'color'     => 'amber',
                'benefit'   => 'Latihan tempo cepat mendekati ambang akumulasi asam laktat.'
            ],
            5 => [
                'zone'      => 5,
                'name'      => 'Zona 5: Daya Tahan Spesifik (Anaerobic Capacity)',
                'intensity' => 'Max HR - 10 ± 5 BPM',
                'min'       => max(60, $maxHr - 15),
                'max'       => $maxHr,
                'color'     => 'coral',
                'benefit'   => 'Pengembangan kapasitas laktat puncak dan VO2 Max.'
            ]
        ];

        return [
            'max_hr' => $maxHr,
            'method' => 'zoladz',
            'zones'  => $zones
        ];
    }
}
