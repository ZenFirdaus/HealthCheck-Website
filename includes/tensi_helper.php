<?php
/**
 * Blood Pressure Assessment Helper for HealthCheck
 * Reference: AHA/ACC 2017 Guidelines & Konsensus PERHI 2021.
 */

if (!function_exists('classify_blood_pressure')) {
    /**
     * Classifies blood pressure according to AHA/ACC 2017 & PERHI 2021 consensus.
     * When systolic and diastolic fall into different categories, the higher category is selected.
     */
    function classify_blood_pressure(int $systolic, int $diastolic): array {
        $pulsePressure = $systolic - $diastolic;

        // Hypertensive Crisis Range (>180 and/or >120)
        if ($systolic > 180 || $diastolic > 120) {
            return [
                'category'       => 'Krisis Hipertensi',
                'badge'          => 'Krisis Hipertensi',
                'color'          => 'danger',
                'pulse_pressure' => $pulsePressure,
                'is_emergency'   => true,
                'advice'         => 'Tekanan darah Anda terukur sangat tinggi (>180/>120 mmHg). Langkah penanganan: (1) Jika TANPA gejala: duduk tenang, istirahat rileks 5 menit, dan lakukan pengukuran ulang. Jika angka tetap tinggi, segera hubungi dokter. (2) Jika DISERTAI GEJALA (nyeri dada, sesak napas, pusing berputar, pandangan kabur, atau baal/lemas separuh badan): ini merupakan kegawatdaruratan medis (Hipertensi Emergensi) — SEGERA cari bantuan medis darurat di IGD rumah sakit terdekat!'
            ];
        }

        // Stage 2 Hypertension (>=140 or >=90)
        if ($systolic >= 140 || $diastolic >= 90) {
            return [
                'category'       => 'Hipertensi Tingkat 2',
                'badge'          => 'Hipertensi Tk 2',
                'color'          => 'danger',
                'is_emergency'   => false,
                'advice'         => 'Hasil pengukuran Anda berada pada rentang hipertensi derajat 2 (≥140/90 mmHg). Sangat disarankan untuk berkonsultasi dengan dokter untuk evaluasi klinis dan rencana penanganan medis yang tepat.'
            ];
        }

        // Stage 1 Hypertension (130-139 or 80-89)
        if (($systolic >= 130 && $systolic <= 139) || ($diastolic >= 80 && $diastolic <= 89)) {
            return [
                'category'       => 'Hipertensi Tingkat 1',
                'badge'          => 'Hipertensi Tk 1',
                'color'          => 'warning',
                'is_emergency'   => false,
                'advice'         => 'Hasil pengukuran Anda berada pada rentang hipertensi derajat 1. Terapkan pola hidup sehat: kurangi asupan natrium/garam, perbanyak aktivitas aerobik, kelola stres, dan jadwalkan pemeriksaan rutin dengan dokter.'
            ];
        }

        // Elevated / Pra-Hipertensi (120-129 and <80)
        if ($systolic >= 120 && $systolic <= 129 && $diastolic < 80) {
            return [
                'category'       => 'Pra-Hipertensi (Elevated)',
                'badge'          => 'Pra-Hipertensi',
                'color'          => 'warning',
                'is_emergency'   => false,
                'advice'         => 'Tekanan darah sistolik Anda sedikit meningkat (120–129 mmHg). Ini merupakan tanda awal untuk mulai memperbaiki pola makan (diet DASH rendah garam) dan mempertahankan berat badan ideal sebelum berkembang menjadi hipertensi.'
            ];
        }

        // Hypotension (<90 or <60)
        if ($systolic < 90 || $diastolic < 60) {
            return [
                'category'       => 'Hipotensi (Tekanan Darah Rendah)',
                'badge'          => 'Hipotensi',
                'color'          => 'warning',
                'is_emergency'   => false,
                'advice'         => 'Tekanan darah Anda berada di bawah rentang normal (<90/60 mmHg). Jika tidak disertai pusing, lemas, atau pandangan berkunang-kunang, kondisi ini sering kali variasi fisiologis normal. Pastikan hidrasi cukup dan konsultasikan bila ada gejala.'
            ];
        }

        // Normal (<120 and <80)
        return [
            'category'       => 'Normal (Optimal)',
            'badge'          => 'Normal',
            'color'          => 'success',
            'is_emergency'   => false,
            'advice'         => 'Tekanan darah Anda berada dalam rentang ideal (<120/80 mmHg). Pertahankan gaya hidup sehat, konsumsi gizi seimbang, tidur cukup, dan olahraga teratur.'
        ];
    }
}
