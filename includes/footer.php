</main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3><?php echo APP_NAME; ?></h3>
                <p>Sistem pemesanan tiket pesawat terpercaya untuk perjalanan Anda.</p>
            </div>
            <div class="footer-section">
                <h4>Layanan</h4>
                <ul>
                    <?php if (isLoggedIn() && !isAdmin()): ?>
                        <li><a href="<?php echo APP_URL; ?>user/flights.php">Jadwal Penerbangan</a></li>
                        <li><a href="<?php echo APP_URL; ?>user/bookings.php">Riwayat Pesanan</a></li>
                        <li><a href="<?php echo APP_URL; ?>user/profile.php">Profil</a></li>
                    <?php elseif (isLoggedIn() && isAdmin()): ?>
                        <li><a href="<?php echo APP_URL; ?>admin/dashboard.php">Dashboard Admin</a></li>
                        <li><a href="<?php echo APP_URL; ?>admin/flights.php">Kelola Penerbangan</a></li>
                        <li><a href="<?php echo APP_URL; ?>admin/bookings.php">Kelola Pesanan</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo APP_URL; ?>auth/login.php">Masuk</a></li>
                        <li><a href="<?php echo APP_URL; ?>auth/register.php">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Bantuan</h4>
                <ul>
                    <li><a href="#">Hubungi Kami</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Kontak</h4>
                <p>Email: info@dilanmaskapai.com</p>
                <p>Telepon: +62 21 1234 5678</p>
                <p>WhatsApp: +62 812 3456 7890</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script src="<?php echo APP_URL; ?>assets/js/script.js"></script>
</body>
</html>
