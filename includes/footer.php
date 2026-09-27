</main>

<footer class="py-5 mt-auto">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center mb-3">
                    <span class="brand-badge" style="width:32px; height:32px; font-size: 0.9rem;"><i class="bi bi-heart-pulse-fill"></i></span>
                    <span class="brand-text fs-5">Health<span>Check</span></span>
                </div>
                <p class="text-secondary small mb-3">
                    Platform alat kesehatan digital mandiri yang dirancang untuk membantu Anda memantau kebugaran, indeks massa tubuh, kalori harian, target detak jantung, dan kesehatan emosional dengan mudah, aman, dan informatif.
                </p>
                <div class="disclaimer-box m-0 p-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-warning"></i>
                        <div>
                            <strong class="d-block text-dark">Disclaimer Medis Penting:</strong>
                            <small class="text-muted">
                                Hasil perhitungan dan evaluasi pada website ini semata-mata bersifat informatif dan edukatif berdasarkan parameter yang Anda masukkan. Hasil ini bukan pengganti saran, diagnosis, maupun perawatan medis profesional dari dokter atau tenaga kesehatan.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3 offset-lg-1">
                <h6 class="text-dark fw-bold mb-3">Alat Kesehatan</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="bmi_form.php">Kalkulator BMI (IMT)</a></li>
                    <li><a href="bmr_form.php">Kalkulator BMR & Kalori (TDEE)</a></li>
                    <li><a href="jantung_form.php">Kalkulator Detak Jantung</a></li>
                    <li><a href="mental_form.php">Screening Mental Mandiri</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-3">
                <h6 class="text-dark fw-bold mb-3">Akses Akun</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <?php if (isset($_SESSION['username'])): ?>
                        <li><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Login sebagai <?= htmlspecialchars($_SESSION['username']) ?></span></li>
                        <?php if (isset($_SESSION['job']) && $_SESSION['job'] === 'admin'): ?>
                            <li><a href="admin.php">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php" class="text-danger">Keluar (Logout)</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Masuk Pengguna</a></li>
                        <li><a href="register.php">Daftar Akun Baru</a></li>
                        <li><a href="login_admin.php">Masuk Administrator</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <hr class="border-secondary-subtle my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
            <p class="mb-0">&copy; <?= date('Y') ?> HealthCheck. Simple code, clean structure, professional result.</p>
            <p class="mb-0">Dibuat untuk kesadaran gaya hidup sehat yang berkelanjutan.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
