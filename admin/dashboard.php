<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is admin
if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(APP_URL . 'auth/login.php');
}

$total_users = 0;
$total_flights = 0;
$total_bookings = 0;
$pending_payments = 0;
$recent_bookings = [];

// Get dashboard statistics
try {
    $database = new Database();
    $db = $database->getConnection();

    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total flights
    $stmt = $db->query("SELECT COUNT(*) as total FROM flights");
    $total_flights = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total bookings
    $stmt = $db->query("SELECT COUNT(*) as total FROM bookings");
    $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pending payments
    $stmt = $db->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
    $pending_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent bookings
    $query = "SELECT b.*, u.full_name, f.flight_number, a1.city as departure_city, a2.city as arrival_city 
              FROM bookings b 
              JOIN users u ON b.user_id = u.id 
              JOIN flights f ON b.flight_id = f.id 
              JOIN airports a1 ON f.departure_airport_id = a1.id 
              JOIN airports a2 ON f.arrival_airport_id = a2.id 
              ORDER BY b.booking_date DESC 
              LIMIT 5";
    $stmt = $db->query($query);
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data dashboard.');
}

$page_title = 'Dashboard Admin';
include '../includes/header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Dashboard Admin</h1>
        <p>Selamat datang di panel administrasi <?php echo APP_NAME; ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <span aria-hidden="true">&#128101;</span>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Pengguna</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span aria-hidden="true">&#9992;</span>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_flights; ?></h3>
                <p>Total Penerbangan</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span aria-hidden="true">&#128203;</span>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Pesanan</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <span aria-hidden="true">&#128179;</span>
            </div>
            <div class="stat-content">
                <h3><?php echo $pending_payments; ?></h3>
                <p>Pembayaran Menunggu</p>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="recent-bookings">
            <h2>Pesanan Terbaru</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Booking</th>
                            <th>Nama Penumpang</th>
                            <th>Penerbangan</th>
                            <th>Rute</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_bookings)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada pesanan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo $booking['booking_code']; ?></td>
                                    <td><?php echo sanitizeInput($booking['full_name']); ?></td>
                                    <td><?php echo $booking['flight_number']; ?></td>
                                    <td><?php echo $booking['departure_city']; ?> - <?php echo $booking['arrival_city']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($booking['booking_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Aksi Cepat</h2>
            <div class="action-grid">
                <a href="<?php echo APP_URL; ?>admin/flight_form.php" class="action-card">
                    <span class="action-icon" aria-hidden="true">&#10133;</span>
                    <h3>Tambah Penerbangan</h3>
                    <p>Buat jadwal penerbangan baru</p>
                </a>

                <a href="<?php echo APP_URL; ?>admin/flights.php" class="action-card">
                    <span class="action-icon" aria-hidden="true">&#9992;</span>
                    <h3>Kelola Penerbangan</h3>
                    <p>Lihat, edit, atau hapus data penerbangan</p>
                </a>

                <a href="<?php echo APP_URL; ?>admin/bookings.php" class="action-card">
                    <span class="action-icon" aria-hidden="true">&#128203;</span>
                    <h3>Kelola Pesanan</h3>
                    <p>Lihat dan verifikasi pembayaran tiket</p>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
