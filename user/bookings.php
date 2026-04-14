<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage('danger', 'Silakan masuk terlebih dahulu.');
    redirect(APP_URL . 'auth/login.php');
}

// Check if user is not admin
if (isAdmin()) {
    redirect(APP_URL . 'admin/dashboard.php');
}

$bookings = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user bookings with related data
    $query = "SELECT b.*, f.flight_number, a.name as airline_name, a.code as airline_code,
             dep.city as departure_city, arr.city as arrival_city,
             f.departure_time, f.arrival_time, f.price,
             p.status as payment_status, p.admin_notes,
             pp.id as payment_proof_id
             FROM bookings b
             JOIN flights f ON b.flight_id = f.id
             JOIN airlines a ON f.airline_id = a.id
             JOIN airports dep ON f.departure_airport_id = dep.id
             JOIN airports arr ON f.arrival_airport_id = arr.id
             LEFT JOIN payments p ON b.id = p.booking_id
             LEFT JOIN payment_proofs pp ON pp.id = (
                 SELECT MAX(pp2.id) FROM payment_proofs pp2 WHERE pp2.payment_id = p.id
             )
             WHERE b.user_id = ?
             ORDER BY b.booking_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data pesanan.');
}

$page_title = 'Pesanan Saya';
include '../includes/header.php';
?>

<div class="bookings-page">
    <div class="page-header">
        <h1>Pesanan Saya</h1>
        <a href="<?php echo APP_URL; ?>user/flights.php" class="btn btn-primary">Lihat Jadwal</a>
    </div>

    <div class="bookings-container">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">&#9992;</div>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki pesanan tiket pesawat. Lihat jadwal penerbangan yang tersedia!</p>
                <a href="<?php echo APP_URL; ?>user/flights.php" class="btn btn-primary">Lihat Jadwal</a>
            </div>
        <?php else: ?>
            <div class="bookings-list">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-info">
                                <h3>Kode Booking: <?php echo $booking['booking_code']; ?></h3>
                                <p class="booking-date"><?php echo formatDate($booking['booking_date']); ?></p>
                            </div>
                            <div class="booking-status">
                                <span class="badge badge-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                                <?php
                                $payment_badge = 'badge-pending';
                                $payment_label = 'Menunggu Pembayaran';
                                if ($booking['payment_status'] === 'confirmed') {
                                    $payment_badge = 'badge-confirmed';
                                    $payment_label = 'Dikonfirmasi';
                                } elseif ($booking['payment_status'] === 'rejected') {
                                    $payment_badge = 'badge-cancelled';
                                    $payment_label = 'Ditolak';
                                } elseif (!empty($booking['payment_proof_id'])) {
                                    $payment_badge = 'badge-paid';
                                    $payment_label = 'Menunggu Verifikasi';
                                }
                                ?>
                                <span class="badge <?php echo $payment_badge; ?>">
                                    Pembayaran: <?php echo $payment_label; ?>
                                </span>
                            </div>
                        </div>

                        <div class="booking-flight">
                            <div class="flight-info">
                                <div class="airline">
                                    <strong><?php echo $booking['airline_name']; ?></strong>
                                    <small><?php echo $booking['airline_code']; ?> - <?php echo $booking['flight_number']; ?></small>
                                </div>
                                <div class="flight-route">
                                    <div class="departure">
                                        <h4><?php echo $booking['departure_city']; ?></h4>
                                        <p><?php echo formatDateTime($booking['departure_time']); ?></p>
                                    </div>
                                    <div class="flight-arrow">
                                        <span aria-hidden="true">&#8594;</span>
                                    </div>
                                    <div class="arrival">
                                        <h4><?php echo $booking['arrival_city']; ?></h4>
                                        <p><?php echo formatDateTime($booking['arrival_time']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="booking-details">
                                <div class="detail-item">
                                    <span>Penumpang:</span>
                                    <span><?php echo $booking['total_passengers']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Total Harga:</span>
                                    <span><?php echo formatCurrency($booking['total_price']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="booking-actions">
                            <?php if ($booking['payment_status'] === 'pending' && empty($booking['payment_proof_id'])): ?>
                                <a href="<?php echo APP_URL; ?>user/payment.php?booking_id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-primary">Lanjutkan Pembayaran</a>
                            <?php elseif ($booking['payment_status'] === 'rejected'): ?>
                                <a href="<?php echo APP_URL; ?>user/payment.php?booking_id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-secondary">Upload Ulang Bukti</a>
                            <?php elseif ($booking['payment_status'] === 'confirmed'): ?>
                                <a href="<?php echo APP_URL; ?>user/ticket.php?booking_id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-primary">Lihat Tiket</a>
                            <?php elseif ($booking['payment_status'] === 'pending' && !empty($booking['payment_proof_id'])): ?>
                                <span class="btn btn-secondary">Menunggu Verifikasi Admin</span>
                            <?php endif; ?>
                            
                            <?php
                            $detail_url = ($booking['payment_status'] === 'confirmed')
                                ? APP_URL . 'user/ticket.php?booking_id=' . (int) $booking['id']
                                : APP_URL . 'user/payment.php?booking_id=' . (int) $booking['id'];
                            ?>
                            <a href="<?php echo $detail_url; ?>" class="btn btn-secondary">Detail</a>
                        </div>

                        <?php if ($booking['payment_status'] === 'rejected' && $booking['admin_notes']): ?>
                            <div class="admin-notes">
                                <strong>Catatan Admin:</strong> <?php echo sanitizeInput($booking['admin_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bookings-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    color: #1f2937;
}

.bookings-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.empty-state {
    background: white;
    padding: 4rem 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 2rem;
}

.booking-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.booking-info h3 {
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.booking-date {
    color: #6b7280;
    font-size: 0.875rem;
}

.booking-status {
    display: flex;
    gap: 0.5rem;
}

.booking-flight {
    padding: 1.5rem;
}

.booking-details {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    gap: 2rem;
}

.detail-item {
    display: flex;
    gap: 0.5rem;
}

.detail-item span:first-child {
    color: #6b7280;
}

.detail-item span:last-child {
    color: #1f2937;
    font-weight: 500;
}

.booking-actions {
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
}

.admin-notes {
    padding: 1rem 1.5rem;
    background: #fef2f2;
    color: #991b1b;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .booking-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .booking-status {
        flex-wrap: wrap;
    }
    
    .booking-details {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .booking-actions {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
