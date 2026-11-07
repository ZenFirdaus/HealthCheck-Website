<?php
/**
 * Waist-to-Height Ratio (WHtR) Helper for HealthCheck
 * Reference: Ashwell M, Gibson S (BMJ Open 2016); Browning et al. (Nutr Res Rev 2010).
 * Rule of Thumb: "Keep your waist circumference to less than half your height."
 */

if (!function_exists('calculate_whtr')) {
    function calculate_whtr(float $waistCm, float $heightCm): float {
        if ($heightCm <= 0) {
            return 0.0;
        }
        return round($waistCm / $heightCm, 2);
    }
}

if (!function_exists('get_healthy_waist_limit')) {
    function get_healthy_waist_limit(float $heightCm): float {
        return round(0.5 * $heightCm, 1);
    }
}

if (!function_exists('get_whtr_category')) {
    function get_whtr_category(float $ratio): array {
        if ($ratio < 0.40) {
            return [
                'category' => 'Kurang (Underweight Central)',
                'badge'    => 'Kurus',
                'color'    => 'warning',
                'advice'   => 'Rasio lingkar pinggang terhadap tinggi badan Anda berada di bawah ambang normal. Pastikan asupan nutrisi seimbang untuk mencegah risiko malnutrisi atau massa otot yang terlalu rendah.'
            ];
        } elseif ($ratio <= 0.49) {
            return [
                'category' => 'Sehat / Risiko Normal (Ideal)',
                'badge'    => 'Sehat / Ideal',
                'color'    => 'success',
                'advice'   => 'Rasio lingkar pinggang Anda berada dalam batas sehat (kurang dari setengah tinggi badan). Pertahankan pola makan gizi seimbang dan aktivitas fisik teratur.'
            ];
        } elseif ($ratio <= 0.59) {
            return [
                'category' => 'Risiko Meningkat (Increased Risk)',
                'badge'    => 'Risiko Meningkat',
                'color'    => 'warning',
                'advice'   => 'Lingkar pinggang Anda mulai melampaui setengah tinggi badan, mengindikasikan akumulasi lemak viseral (lemak perut). Dianjurkan untuk meningkatkan aktivitas aerobik dan mengurangi konsumsi gula sederhana.'
            ];
        } else {
            return [
                'category' => 'Risiko Sangat Tinggi (Very High Risk)',
                'badge'    => 'Risiko Sangat Tinggi',
                'color'    => 'danger',
                'advice'   => 'Rasio lingkar pinggang Anda berada pada kategori risiko kardiometabolik sangat tinggi. Kondisi ini berhubungan erat dengan hipertensi, resistensi insulin, dan penyakit jantung. Sangat disarankan berkonsultasi dengan dokter untuk evaluasi komprehensif.'
            ];
        }
    }
}
