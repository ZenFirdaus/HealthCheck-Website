<?php
/**
 * BMR & TDEE Calculation and Activity Configuration Helper for HealthCheck
 * Single source of truth for physical activity multipliers and Mifflin-St Jeor formula.
 */

if (!function_exists('get_bmr_activity_levels')) {
    function get_bmr_activity_levels(): array {
        return [
            '1.2' => [
                'factor' => 1.2,
                'label' => 'Sedentary (Jarang bergerak)',
                'description' => 'Jarang Bergerak / Sedentary (banyak duduk, tidak ada olahraga rutin)'
            ],
            '1.375' => [
                'factor' => 1.375,
                'label' => 'Ringan (1-3 hari/minggu)',
                'description' => 'Aktivitas Ringan (olahraga 1-3 hari/minggu)'
            ],
            '1.55' => [
                'factor' => 1.55,
                'label' => 'Sedang (3-5 hari/minggu)',
                'description' => 'Aktivitas Sedang (olahraga 3-5 hari/minggu)'
            ],
            '1.725' => [
                'factor' => 1.725,
                'label' => 'Sangat Aktif (6-7 hari/minggu)',
                'description' => 'Sangat Aktif (olahraga berat 6-7 hari/minggu)'
            ],
            '1.9' => [
                'factor' => 1.9,
                'label' => 'Ekstra Aktif (Pekerja berat/atlet)',
                'description' => 'Ekstra Aktif (pekerja fisik berat / atlet latihan 2x sehari)'
            ]
        ];
    }
}

if (!function_exists('get_activity_factor')) {
    /**
     * Resolves and validates the multiplier factor from a key ('1.2') or label ('Sedentary...').
     * Returns null if the activity value is invalid or unrecognized.
     */
    function get_activity_factor(mixed $input): ?float {
        if ($input === null || $input === '') {
            return null;
        }

        $levels = get_bmr_activity_levels();
        $strInput = trim((string)$input);

        // 1. Direct match on multiplier key (e.g. '1.2', '1.375', '1.55', etc.)
        if (isset($levels[$strInput])) {
            return (float)$levels[$strInput]['factor'];
        }

        // Also check if numeric float matches after formatting
        if (is_numeric($strInput)) {
            $floatVal = (float)$strInput;
            foreach ($levels as $item) {
                if (abs($item['factor'] - $floatVal) < 0.0001) {
                    return $item['factor'];
                }
            }
            return null;
        }

        // 2. Match on label text (for database compatibility and legacy rows)
        $lower = strtolower($strInput);
        if (str_contains($lower, 'sedentary') || str_contains($lower, 'jarang')) {
            return 1.2;
        }
        if (str_contains($lower, 'ringan')) {
            return 1.375;
        }
        if (str_contains($lower, 'sangat aktif')) {
            return 1.725;
        }
        if (str_contains($lower, 'ekstra aktif') || str_contains($lower, 'extra')) {
            return 1.9;
        }
        if (str_contains($lower, 'sedang')) {
            return 1.55;
        }

        return null;
    }
}

if (!function_exists('get_activity_label')) {
    /**
     * Returns the canonical label string for database storage and display.
     */
    function get_activity_label(mixed $input): ?string {
        $factor = get_activity_factor($input);
        if ($factor === null) {
            return null;
        }

        $levels = get_bmr_activity_levels();
        foreach ($levels as $item) {
            if (abs($item['factor'] - $factor) < 0.0001) {
                return $item['label'];
            }
        }

        return null;
    }
}

if (!function_exists('get_activity_key_from_db')) {
    /**
     * Maps stored database string or value to option key (default: '1.55') for HTML <select>.
     */
    function get_activity_key_from_db(mixed $dbValue): string {
        $factor = get_activity_factor($dbValue);
        if ($factor === null) {
            return '1.55';
        }

        foreach (get_bmr_activity_levels() as $key => $item) {
            if (abs($item['factor'] - $factor) < 0.0001) {
                return (string)$key;
            }
        }

        return '1.55';
    }
}

if (!function_exists('calculate_bmr')) {
    /**
     * Mifflin - St Jeor BMR calculation.
     * Pria: (10 * BB) + (6.25 * TB) - (5 * Umur) + 5
     * Wanita: (10 * BB) + (6.25 * TB) - (5 * Umur) - 161
     */
    function calculate_bmr(string $gender, float $weightKg, float $heightCm, int $ageYears): float {
        $g = strtoupper(trim($gender));
        if ($g === 'L' || $g === 'M' || $g === 'PRIA' || $g === 'LAKI-LAKI') {
            $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $ageYears) + 5;
        } else {
            $bmr = (10 * $weightKg) + (6.25 * $heightCm) - (5 * $ageYears) - 161;
        }
        return round($bmr, 0);
    }
}

if (!function_exists('calculate_tdee')) {
    /**
     * Total Daily Energy Expenditure.
     * TDEE = BMR * activity multiplier
     */
    function calculate_tdee(float $bmr, float $activityFactor): float {
        return round($bmr * $activityFactor, 0);
    }
}

if (!function_exists('get_dietary_goals')) {
    function get_dietary_goals(): array {
        return [
            'maintain' => [
                'key' => 'maintain',
                'label' => 'Pertahankan Berat Badan (Ideal)',
                'description' => 'Konsumsi kalori seimbang sebanding kalori yang dikeluarkan setiap hari',
                'adjustment' => 0
            ],
            'lose_weight' => [
                'key' => 'lose_weight',
                'label' => 'Turunkan Berat Badan (Defisit)',
                'description' => 'Defisit terkontrol ~450 kkal untuk penurunan bertahap 0.3 - 0.5 kg/minggu',
                'adjustment' => -450
            ],
            'gain_weight' => [
                'key' => 'gain_weight',
                'label' => 'Naikkan Berat Badan (Surplus)',
                'description' => 'Surplus terukur ~450 kkal diimbangi latihan kekuatan untuk peningkatan massa otot',
                'adjustment' => 450
            ]
        ];
    }
}

if (!function_exists('calculate_target_calories')) {
    function calculate_target_calories(float $tdee, string $goal = 'maintain', string $gender = 'L'): float {
        $goals = get_dietary_goals();
        $adj = $goals[$goal]['adjustment'] ?? 0;
        $target = $tdee + $adj;
        $minSafe = in_array(strtoupper(trim($gender)), ['P', 'W', 'PEREMPUAN'], true) ? 1200.0 : 1400.0;
        return max($minSafe, round($target, 0));
    }
}

if (!function_exists('calculate_macronutrients')) {
    /**
     * Estimates macronutrient distribution in grams based on target calories.
     * Standard balanced split: 50% Carbs, 20% Protein, 30% Fat
     * 1g Carb = 4 kcal, 1g Protein = 4 kcal, 1g Fat = 9 kcal
     */
    function calculate_macronutrients(float $targetCalories, string $split = 'balanced'): array {
        $calories = max(500.0, $targetCalories);

        switch ($split) {
            case 'high_protein':
                $pPct = 0.30; $cPct = 0.40; $fPct = 0.30;
                break;
            case 'low_carb':
                $pPct = 0.30; $cPct = 0.30; $fPct = 0.40;
                break;
            case 'balanced':
            default:
                $pPct = 0.20; $cPct = 0.50; $fPct = 0.30;
                break;
        }

        $proteinGrams = round(($calories * $pPct) / 4, 1);
        $carbsGrams   = round(($calories * $cPct) / 4, 1);
        $fatGrams     = round(($calories * $fPct) / 9, 1);

        return [
            'target_calories' => round($calories, 0),
            'split'           => $split,
            'protein_gram'    => $proteinGrams,
            'protein_calories'=> round($calories * $pPct, 0),
            'protein_pct'     => (int)($pPct * 100),
            'carbs_gram'      => $carbsGrams,
            'carbs_calories'  => round($calories * $cPct, 0),
            'carbs_pct'       => (int)($cPct * 100),
            'fat_gram'        => $fatGrams,
            'fat_calories'    => round($calories * $fPct, 0),
            'fat_pct'         => (int)($fPct * 100)
        ];
    }
}
