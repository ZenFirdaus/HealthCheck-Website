<?php
/**
 * Body Fat Percentage (US Navy Method) Helper for HealthCheck
 * Reference: Hodgdon JA, Beckett MB. Naval Health Research Center (1984).
 * Classification: American Council on Exercise (ACE).
 */

if (!function_exists('calculate_body_fat_navy')) {
    /**
     * Calculates Body Fat Percentage using the US Navy Circumference Method.
     * All circumference and height inputs must be in centimeters (cm).
     * Returns null if inputs lead to non-positive log arguments or invalid math.
     */
    function calculate_body_fat_navy(string $gender, float $heightCm, float $waistCm, float $neckCm, ?float $hipCm = null): ?float {
        $g = strtoupper(trim($gender));
        $isFemale = in_array($g, ['P', 'W', 'PEREMPUAN', 'FEMALE'], true);

        if ($heightCm <= 0 || $neckCm <= 0 || $waistCm <= 0) {
            return null;
        }

        if (!$isFemale) {
            // Male equation: waist must be greater than neck
            $diff = $waistCm - $neckCm;
            if ($diff <= 0) {
                return null;
            }
            $denom = 1.0324 - (0.19077 * log10($diff)) + (0.15456 * log10($heightCm));
            if ($denom <= 0) {
                return null;
            }
            $fat = (495.0 / $denom) - 450.0;
        } else {
            // Female equation: waist + hip must be greater than neck
            $hip = (float)($hipCm ?? 0);
            if ($hip <= 0) {
                return null;
            }
            $diff = $waistCm + $hip - $neckCm;
            if ($diff <= 0) {
                return null;
            }
            $denom = 1.29579 - (0.35004 * log10($diff)) + (0.22100 * log10($heightCm));
            if ($denom <= 0) {
                return null;
            }
            $fat = (495.0 / $denom) - 450.0;
        }

        if (is_nan($fat) || is_infinite($fat)) {
            return null;
        }

        // Clamp to physiologically sensible range (2% to 65%)
        $fat = max(2.0, min(65.0, round($fat, 1)));
        return $fat;
    }
}

if (!function_exists('get_body_fat_category')) {
    function get_body_fat_category(float $fatPct, string $gender = 'L'): array {
        $g = strtoupper(trim($gender));
        $isFemale = in_array($g, ['P', 'W', 'PEREMPUAN', 'FEMALE'], true);

        if (!$isFemale) {
            if ($fatPct < 6.0) {
                return [
                    'category' => 'Lemak Esensial (Essential Fat)',
                    'badge'    => 'Esensial',
                    'color'    => 'warning',
                    'advice'   => 'Persentase lemak tubuh Anda sangat rendah pada level minimal esensial biologis (2-5%). Hati-hati terhadap defisiensi energi, penurunan imunitas, dan gangguan hormon.'
                ];
            } elseif ($fatPct <= 13.9) {
                return [
                    'category' => 'Atlet (Athletes)',
                    'badge'    => 'Atlet',
                    'color'    => 'success',
                    'advice'   => 'Tingkat komposisi lemak tubuh Anda sangat prima dan ramping, lazim dijumpai pada atlet dan binaragawan aktif.'
                ];
            } elseif ($fatPct <= 17.9) {
                return [
                    'category' => 'Kebugaran Prima (Fitness)',
                    'badge'    => 'Fitness Ideal',
                    'color'    => 'success',
                    'advice'   => 'Persentase lemak tubuh Anda berada pada rentang ideal untuk kebugaran fisik dan kesehatan kardiometabolik jangka panjang.'
                ];
            } elseif ($fatPct <= 24.9) {
                return [
                    'category' => 'Rata-rata Wajar (Acceptable)',
                    'badge'    => 'Rata-rata',
                    'color'    => 'info',
                    'advice'   => 'Persentase lemak tubuh Anda berada pada ambang rata-rata populasi umum sehat. Pertahankan latihan resistensi dan pola makan teratur.'
                ];
            } else {
                return [
                    'category' => 'Kelebihan Lemak (Obese)',
                    'badge'    => 'Kelebihan Lemak',
                    'color'    => 'danger',
                    'advice'   => 'Persentase lemak tubuh Anda melebihi ambang batas rekomendasi (≥ 25%). Pertimbangkan defisit kalori terkontrol dan latihan fisik terprogram untuk mengurangi risiko sindrom metabolik.'
                ];
            }
        } else {
            // Female ACE category
            if ($fatPct < 14.0) {
                return [
                    'category' => 'Lemak Esensial (Essential Fat)',
                    'badge'    => 'Esensial',
                    'color'    => 'warning',
                    'advice'   => 'Persentase lemak tubuh Anda berada pada level lemak esensial minimal perempuan (10-13%). Kadar lemak yang terlalu rendah pada wanita dapat mengganggu siklus menstruasi dan densitas tulang.'
                ];
            } elseif ($fatPct <= 20.9) {
                return [
                    'category' => 'Atlet (Athletes)',
                    'badge'    => 'Atlet',
                    'color'    => 'success',
                    'advice'   => 'Komposisi tubuh Anda sangat prima dan kencang, umum dimiliki oleh atlet wanita dan pelari profesional.'
                ];
            } elseif ($fatPct <= 24.9) {
                return [
                    'category' => 'Kebugaran Prima (Fitness)',
                    'badge'    => 'Fitness Ideal',
                    'color'    => 'success',
                    'advice'   => 'Persentase lemak tubuh Anda dalam kategori kebugaran optimal untuk wanita dewasa sehat.'
                ];
            } elseif ($fatPct <= 31.9) {
                return [
                    'category' => 'Rata-rata Wajar (Acceptable)',
                    'badge'    => 'Rata-rata',
                    'color'    => 'info',
                    'advice'   => 'Persentase lemak tubuh Anda dalam rentang wajar wanita pada umumnya. Jaga pola makan seimbang dan hidrasi cukup.'
                ];
            } else {
                return [
                    'category' => 'Kelebihan Lemak (Obese)',
                    'badge'    => 'Kelebihan Lemak',
                    'color'    => 'danger',
                    'advice'   => 'Persentase lemak tubuh Anda berada di atas 32%. Disarankan untuk mengombinasikan latihan kardio dan latihan beban serta mengatur porsi makan seimbang.'
                ];
            }
        }
    }
}
