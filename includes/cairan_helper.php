<?php
/**
 * Daily Fluid Needs (Kebutuhan Cairan Harian) Helper for HealthCheck
 * Reference: Kemenkes RI (Permenkes No. 41 Tahun 2014); ACSM (Sawka et al., 2007).
 */

if (!function_exists('get_fluid_activity_levels')) {
    function get_fluid_activity_levels(): array {
        return [
            'sedang' => [
                'key'         => 'sedang',
                'label'       => 'Aktivitas Normal / Ringan',
                'description' => 'Aktivitas harian biasa (banyak di dalam ruangan, mobilitas wajar)',
                'bonus_ml'    => 0
            ],
            'aktif' => [
                'key'         => 'aktif',
                'label'       => 'Olahraga Sedang (30–60 menit/hari)',
                'description' => 'Latihan fisik atau olahraga aerobik 30 sampai 60 menit per hari',
                'bonus_ml'    => 400
            ],
            'sangat_aktif' => [
                'key'         => 'sangat_aktif',
                'label'       => 'Olahraga Berat / Pekerja Fisik (>60 menit)',
                'description' => 'Latihan intensif, lari jarak jauh, atau pekerja lapangan luar ruangan',
                'bonus_ml'    => 800
            ]
        ];
    }
}

if (!function_exists('get_fluid_climate_factors')) {
    function get_fluid_climate_factors(): array {
        return [
            'sedang' => [
                'key'         => 'sedang',
                'label'       => 'Suhu Normal / Ber-AC',
                'description' => 'Suhu ruangan nyaman atau sejuk (20–26°C)',
                'bonus_ml'    => 0
            ],
            'panas' => [
                'key'         => 'panas',
                'label'       => 'Cuaca Panas / Tropis Luar Ruangan',
                'description' => 'Suhu panas (>30°C) atau cuaca lembap yang memicu lebih banyak keringat',
                'bonus_ml'    => 350
            ]
        ];
    }
}

if (!function_exists('calculate_fluid_needs')) {
    /**
     * Calculates daily water needs in milliliters and liters.
     * Baseline: 35 ml per kg body weight + exercise bonus + climate bonus.
     */
    function calculate_fluid_needs(float $weightKg, string $activity = 'sedang', string $climate = 'sedang'): array {
        $weight = max(15.0, min(250.0, $weightKg));

        $actLevels = get_fluid_activity_levels();
        $climFactors = get_fluid_climate_factors();

        $actBonus = $actLevels[$activity]['bonus_ml'] ?? 0;
        $climBonus = $climFactors[$climate]['bonus_ml'] ?? 0;

        $baseMl = $weight * 35.0;
        $totalMl = round($baseMl + $actBonus + $climBonus, 0);

        // Physiological limits: minimum 1000 ml, maximum 6000 ml
        $totalMl = max(1000.0, min(6000.0, $totalMl));
        $totalLiter = round($totalMl / 1000.0, 1);
        $totalGelas = (int)round($totalMl / 250.0);

        return [
            'weight_kg'   => $weight,
            'base_ml'     => round($baseMl, 0),
            'activity'    => $activity,
            'climate'     => $climate,
            'kebutuhan_ml'=> $totalMl,
            'kebutuhan_l' => $totalLiter,
            'gelas'       => $totalGelas
        ];
    }
}
