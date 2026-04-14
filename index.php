<?php
require_once __DIR__ . '/config/config.php';

// If user is logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(APP_URL . 'admin/dashboard.php');
    } else {
        redirect(APP_URL . 'user/flights.php');
    }
}

$page_title = 'Selamat Datang di Dilan Maskapai';
include 'includes/header.php';
?>

<div class="landing-page">
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <h1>Terbang dengan Nyaman dan Aman</h1>
                <p><?php echo APP_NAME; ?> — mitra terpercaya perjalanan udara Anda. Lihat jadwal penerbangan dan pesan tiket dengan mudah.</p>
                <div class="hero-actions">
                    <a href="<?php echo APP_URL; ?>auth/register.php" class="btn btn-primary btn-large">Daftar Sekarang</a>
                    <a href="<?php echo APP_URL; ?>auth/login.php" class="btn btn-secondary btn-large">Masuk</a>
                </div>
            </div>
            <div class="hero-image" aria-hidden="true">
                <div class="plane-icon">&#9992;</div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Mengapa Memilih <?php echo APP_NAME; ?>?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">&#128197;</div>
                    <h3>Jadwal Lengkap</h3>
                    <p>Lihat semua jadwal penerbangan yang tersedia dengan informasi lengkap dan real-time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">&#128176;</div>
                    <h3>Harga Terbaik</h3>
                    <p>Dapatkan penawaran harga terbaik untuk berbagai rute penerbangan domestik.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">&#128274;</div>
                    <h3>Pembayaran Aman</h3>
                    <p>Sistem pembayaran yang aman dengan verifikasi admin untuk setiap transaksi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">&#127915;</div>
                    <h3>E-Ticket Digital</h3>
                    <p>Tiket elektronik yang dapat diakses kapan saja dan dimana saja.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="destinations">
        <div class="container">
            <h2>Destinasi Populer</h2>
            <div class="destinations-grid">
                <div class="destination-card">
                    <h3>Jakarta</h3>
                    <p>Ibukota Indonesia</p>
                </div>
                <div class="destination-card">
                    <h3>Denpasar</h3>
                    <p>Pulau Dewata Bali</p>
                </div>
                <div class="destination-card">
                    <h3>Surabaya</h3>
                    <p>Kota Pahlawan</p>
                </div>
                <div class="destination-card">
                    <h3>Medan</h3>
                    <p>Kota Melayu</p>
                </div>
                <div class="destination-card">
                    <h3>Makassar</h3>
                    <p>Kota Daeng</p>
                </div>
                <div class="destination-card">
                    <h3>Yogyakarta</h3>
                    <p>Kota Budaya</p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2>Cara Mudah Pesan Tiket</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Daftar atau Masuk</h3>
                    <p>Buat akun atau masuk ke akun yang sudah ada.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Lihat Jadwal</h3>
                    <p>Pilih penerbangan dari jadwal yang tersedia.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Isi Data Penumpang</h3>
                    <p>Lengkapi data diri penumpang dengan benar.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Bayar dan Upload Bukti</h3>
                    <p>Lakukan pembayaran dan upload bukti transfer.</p>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <h3>Dapatkan E-Ticket</h3>
                    <p>Setelah diverifikasi, e-ticket akan tersedia.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Siap untuk Terbang?</h2>
                <p>Bergabunglah dengan ribuan pelanggan yang telah mempercayai perjalanan mereka kepada kami.</p>
                <a href="<?php echo APP_URL; ?>auth/register.php" class="btn btn-primary btn-large">Mulai Sekarang</a>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
