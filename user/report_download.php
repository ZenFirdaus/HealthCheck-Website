<?php
$pageTitle = 'Laporan Kesehatan (Health Report PDF) — HealthCheck';
$activePage = 'history';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/history_helper.php';

$userId = current_user_id();
if (!$userId) {
    header("Location: ../auth/login.php?pesan=belum_login");
    exit;
}

$profile = get_current_user_profile($pdo);
$username = $_SESSION['username'] ?? 'Member';
$displayName = !empty($profile['nama_lengkap']) ? $profile['nama_lengkap'] : $username;
$calcAge = !empty($profile['tanggal_lahir']) ? calculate_age_from_dob($profile['tanggal_lahir']) : null;

// Determine period filter
$period = trim($_GET['period'] ?? 'all');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$filterParams = ['sort' => 'DESC'];
$periodLabel = 'Seluruh Riwayat Pemeriksaan';

if ($period === '30d') {
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo   = date('Y-m-d');
    $filterParams['date_from'] = $dateFrom;
    $filterParams['date_to']   = $dateTo;
    $periodLabel = '30 Hari Terakhir (' . date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y') . ')';
} elseif ($period === '90d') {
    $dateFrom = date('Y-m-d', strtotime('-90 days'));
    $dateTo   = date('Y-m-d');
    $filterParams['date_from'] = $dateFrom;
    $filterParams['date_to']   = $dateTo;
    $periodLabel = '90 Hari Terakhir (' . date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y') . ')';
} elseif (!empty($dateFrom) || !empty($dateTo)) {
    if (!empty($dateFrom)) $filterParams['date_from'] = $dateFrom;
    if (!empty($dateTo))   $filterParams['date_to']   = $dateTo;
    $periodLabel = 'Periode Khusus (' . ($dateFrom ?: 'Awal') . ' s/d ' . ($dateTo ?: 'Hari Ini') . ')';
}

$historyData = get_user_unified_history($pdo, $userId, $filterParams);
$records = $historyData['records'];
$indicators = get_user_latest_indicators($pdo, $userId);

// Action: Generate and download PDF
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    require_once __DIR__ . '/../includes/fpdf/fpdf.php';

    class HealthReportPDF extends FPDF {
        public string $reportUser = '';
        public string $reportDate = '';

        function Header() {
            // Brand bar
            $this->SetFillColor(25, 54, 91); // Primary Medical Navy
            $this->Rect(0, 0, 210, 22, 'F');

            $this->SetY(6);
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(0, 6, 'HEALTHCHECK  |  PERSONAL HEALTH ASSESSMENT REPORT', 0, 1, 'C');

            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(230, 240, 250);
            $this->Cell(0, 4, 'Dokumen Ringkasan & Riwayat Pemeriksaan Biometrik Mandiri', 0, 1, 'C');

            $this->Ln(8);
        }

        function Footer() {
            $this->SetY(-18);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(120, 130, 140);
            $this->Cell(0, 4, 'HealthCheck Platform - Dokumen ini bersifat informasi edukatif mandiri, bukan pengganti diagnosis medis dokter.', 0, 1, 'C');
            $this->Cell(0, 4, 'Halaman ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
        }
    }

    $pdf = new HealthReportPDF('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->SetMargins(15, 26, 15);
    $pdf->AddPage();

    // 1. Identitas Pengguna & Metadata Laporan
    $pdf->SetFillColor(245, 248, 252);
    $pdf->SetDrawColor(220, 230, 240);
    $pdf->Rect(15, 28, 180, 24, 'DF');

    $pdf->SetY(30);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(25, 54, 91);
    $pdf->Cell(100, 6, 'IDENTITAS PENGGUNA', 0, 0, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 110, 120);
    $pdf->Cell(80, 6, 'Tanggal Cetak: ' . date('d F Y, H:i') . ' WIB', 0, 1, 'R');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(40, 50, 60);
    $pdf->Cell(90, 5, 'Nama Lengkap : ' . $displayName, 0, 0, 'L');
    $pdf->Cell(90, 5, 'Periode Laporan : ' . $periodLabel, 0, 1, 'L');

    $infoExtra = [];
    if (!empty($profile['jenis_kelamin'])) {
        $infoExtra[] = 'Jenis Kelamin: ' . ($profile['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan');
    }
    if ($calcAge !== null) {
        $infoExtra[] = 'Umur: ' . $calcAge . ' thn';
    }
    if (!empty($profile['tinggi_badan'])) {
        $infoExtra[] = 'Tinggi: ' . $profile['tinggi_badan'] . ' cm';
    }
    if (!empty($profile['berat_badan'])) {
        $infoExtra[] = 'Berat: ' . $profile['berat_badan'] . ' kg';
    }

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 110, 120);
    $pdf->Cell(180, 4, !empty($infoExtra) ? implode('  |  ', $infoExtra) : 'Profil biometrik dasar belum dilengkapi.', 0, 1, 'L');

    $pdf->Ln(5);

    // 2. Ringkasan Status Biometrik Terkini (Snapshot per Kategori)
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(25, 54, 91);
    $pdf->Cell(0, 6, 'A. RINGKASAN STATUS BIOMETRIK TERKINI', 0, 1, 'L');

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(230, 238, 246);
    $pdf->SetTextColor(25, 54, 91);
    $pdf->Cell(55, 6, 'Alat Evaluasi Mandiri', 1, 0, 'L', true);
    $pdf->Cell(45, 6, 'Hasil Pengukuran Terakhir', 1, 0, 'L', true);
    $pdf->Cell(50, 6, 'Klasifikasi / Kategori', 1, 0, 'L', true);
    $pdf->Cell(30, 6, 'Tanggal', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(40, 50, 60);

    $hasAnyData = false;
    foreach ($indicators as $toolKey => $ind) {
        if ($ind['has_data']) {
            $hasAnyData = true;
            $pdf->Cell(55, 5.5, $ind['title'], 1, 0, 'L');
            $pdf->Cell(45, 5.5, $ind['primary_metric'], 1, 0, 'L');
            $pdf->Cell(50, 5.5, substr($ind['status_label'], 0, 32), 1, 0, 'L');
            $pdf->Cell(30, 5.5, date('d/m/Y', strtotime($ind['created_at'])), 1, 1, 'C');
        }
    }

    if (!$hasAnyData) {
        $pdf->Cell(180, 6, 'Belum ada data pemeriksaan mandiri tercatat.', 1, 1, 'C');
    }

    $pdf->Ln(6);

    // 3. Log Riwayat Pemeriksaan dalam Periode
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(25, 54, 91);
    $pdf->Cell(0, 6, 'B. RIWAYAT PEMERIKSAAN (' . count($records) . ' CATATAN DALAM PERIODE)', 0, 1, 'L');

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(230, 238, 246);
    $pdf->SetTextColor(25, 54, 91);
    $pdf->Cell(25, 6, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell(50, 6, 'Jenis Pemeriksaan', 1, 0, 'L', true);
    $pdf->Cell(45, 6, 'Hasil Terukur', 1, 0, 'L', true);
    $pdf->Cell(60, 6, 'Status / Kategori', 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(40, 50, 60);

    if (!empty($records)) {
        foreach (array_slice($records, 0, 25) as $rec) {
            $pdf->Cell(25, 5.5, date('d/m/Y', $rec['timestamp']), 1, 0, 'C');
            $pdf->Cell(50, 5.5, substr($rec['tool_title'], 0, 30), 1, 0, 'L');
            $pdf->Cell(45, 5.5, substr($rec['primary_metric'], 0, 25), 1, 0, 'L');
            $pdf->Cell(60, 5.5, substr($rec['status_label'], 0, 38), 1, 1, 'L');
        }
    } else {
        $pdf->Cell(180, 6, 'Tidak ada riwayat pemeriksaan dalam periode yang dipilih.', 1, 1, 'C');
    }

    $pdf->Ln(6);

    // 4. Catatan & Lembar Disclaimer Medis
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(25, 54, 91);
    $pdf->Cell(0, 5, 'C. CATATAN & BATASAN PENGGUNAAN MEDIS', 0, 1, 'L');

    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetTextColor(80, 90, 100);
    $disclaimer = "1. Dokumen ini dicetak secara otomatis dari arsip evaluasi mandiri pengguna pada platform digital HealthCheck.\n"
                . "2. Seluruh angka dan klasifikasi bersifat skrining edukatif berbasis parameter standar (WHO Asia-Pacific, Mifflin-St Jeor, Ashwell & Gibson, US Navy, AHA/ACC, PERHI, Tanaka, IPAQ, dan PSS-10).\n"
                . "3. Laporan ini BUKAN pengganti rekam medis formal rumah sakit, pemeriksaan laboratorium, maupun diagnosis dokter spesialis.\n"
                . "4. Jika Anda memiliki riwayat hipertensi, penyakit kardiovaskular, keluhan psikologis yang menetap, atau gejala akut, segera konsultasikan langsung dengan dokter di fasilitas pelayanan kesehatan.";

    $pdf->MultiCell(180, 3.8, $disclaimer, 1, 'L');

    // Output binary PDF download
    $filename = 'HealthCheck_Report_' . date('Ymd_His') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $pdf->Output('S');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ekspor Laporan PDF</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="eyebrow-text">Ekspor Dokumen</span>
                        <h4 class="card-heading mb-0">Health Report PDF Generator</h4>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-1">
                        Format: PDF Terstandar
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <p class="small text-muted mb-4">
                        Unduh salinan resmi rangkuman biometrik mandiri Anda untuk keperluan arsip pribadi atau sebagai bahan pendukung saat berkonsultasi dengan dokter keluarga Anda.
                    </p>

                    <!-- Filter Form Periode Laporan -->
                    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                        <h6 class="fw-bold text-dark mb-3">Pilih Periode Riwayat yang Disertakan</h6>
                        <form action="report_download.php" method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small" for="period">Rentang Waktu Cepat</label>
                                <select name="period" id="period" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                                    <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Seluruh Riwayat Pemeriksaan</option>
                                    <option value="30d" <?= $period === '30d' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                                    <option value="90d" <?= $period === '90d' ? 'selected' : '' ?>>90 Hari Terakhir</option>
                                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Kustomisasi Tanggal</option>
                                </select>
                            </div>

                            <div class="col-md-3 date-range-field" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
                                <label class="form-label small" for="date_from">Dari Tanggal</label>
                                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>">
                            </div>

                            <div class="col-md-3 date-range-field" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
                                <label class="form-label small" for="date_to">Sampai Tanggal</label>
                                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>">
                            </div>

                            <div class="col-12 d-flex gap-2 pt-2">
                                <button type="submit" name="action" value="preview" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-filter me-1"></i> Perbarui Pratinjau
                                </button>
                                <button type="submit" name="action" value="download" class="btn btn-primary-health btn-sm px-4">
                                    <i class="bi bi-download me-1"></i> Unduh Berkas PDF Sekarang
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Pratinjau Ringkasan Laporan -->
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Pratinjau Data yang Akan Tercetak:</h6>
                    
                    <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important;">
                        <div class="row g-2 small text-muted mb-3">
                            <div class="col-md-6">Nama Pengguna: <strong class="text-dark d-block"><?= htmlspecialchars($displayName) ?></strong></div>
                            <div class="col-md-6">Periode Terpilih: <strong class="text-dark d-block"><?= htmlspecialchars($periodLabel) ?></strong></div>
                        </div>
                        <div class="small text-muted">
                            Total rekaman dalam laporan: <strong class="text-primary"><?= count($records) ?> catatan</strong>.
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Keamanan & Kepatuhan Privasi</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Hanya Anda (pengguna terautentikasi) yang berhak mengunduh dan mencetak rekam kesehatan pribadi ini. Laporan ini dilindungi oleh prinsip isolasi data server-side HealthCheck.
                        </span>
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomDates(val) {
    const fields = document.querySelectorAll('.date-range-field');
    fields.forEach(f => {
        f.style.display = (val === 'custom') ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
