<?php
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage('danger', 'Silakan masuk terlebih dahulu.');
    redirect(APP_URL . 'auth/login.php');
}

// Check if user is not admin
if (isAdmin()) {
    redirect(APP_URL . 'admin/dashboard.php');
}

$flights = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all available flights with related data
    $query = "SELECT f.*, a.name as airline_name, a.code as airline_code,
             dep.name as departure_airport, dep.city as departure_city,
             arr.name as arrival_airport, arr.city as arrival_city
             FROM flights f
             JOIN airlines a ON f.airline_id = a.id
             JOIN airports dep ON f.departure_airport_id = dep.id
             JOIN airports arr ON f.arrival_airport_id = arr.id
             WHERE f.available_seats > 0
             AND f.status IN ('scheduled', 'delayed')
             AND DATE(f.departure_time) >= CURDATE()
             ORDER BY f.departure_time ASC";
    $stmt = $db->query($query);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data penerbangan.');
}

$page_title = 'Jadwal Penerbangan';
include '../includes/header.php';
?>

<div class="flights-page">
    <div class="page-header">
        <h1>Jadwal Penerbangan Tersedia</h1>
        <p>Pilih penerbangan yang Anda inginkan dari jadwal berikut:</p>
    </div>

    <div class="flights-container">
        <?php if (empty($flights)): ?>
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">&#9992;</div>
                <h3>Belum Ada Jadwal Penerbangan</h3>
                <p>Tidak ada jadwal untuk hari ini ke depan (status terjadwal/terlambat, masih ada kursi). Tambahkan jadwal di admin dengan tanggal keberangkatan minimal hari ini.</p>
                <a href="<?php echo APP_URL; ?>" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            <div class="flight-list">
                <?php foreach ($flights as $flight): ?>
                    <?php
                    $dep_ts = strtotime($flight['departure_time']);
                    $bisa_pesan = ($dep_ts !== false && $dep_ts >= time());
                    ?>
                    <div class="flight-card<?php echo $bisa_pesan ? '' : ' flight-card--past'; ?>">
                        <div class="flight-info">
                            <div class="airline">
                                <strong><?php echo $flight['airline_name']; ?></strong>
                                <small><?php echo $flight['airline_code']; ?></small>
                            </div>
                            <div class="flight-route">
                                <div class="departure">
                                    <h4><?php echo $flight['departure_city']; ?></h4>
                                    <p><?php echo formatDateTime($flight['departure_time']); ?></p>
                                </div>
                                <div class="flight-arrow">
                                    <span aria-hidden="true">&#8594;</span>
                                    <small><?php echo calculateFlightDuration($flight['departure_time'], $flight['arrival_time']); ?></small>
                                </div>
                                <div class="arrival">
                                    <h4><?php echo $flight['arrival_city']; ?></h4>
                                    <p><?php echo formatDateTime($flight['arrival_time']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="flight-number">
                                <p><strong>Kode:</strong> <?php echo $flight['flight_number']; ?></p>
                                <p><strong>Kursi tersedia:</strong> <?php echo $flight['available_seats']; ?></p>
                            </div>
                            <div class="flight-price">
                                <h3><?php echo formatCurrency($flight['price']); ?></h3>
                                <p>per penumpang</p>
                            </div>
                        </div>

                        <div class="flight-action">
                            <?php if ($bisa_pesan): ?>
                                <a href="<?php echo APP_URL; ?>user/booking.php?flight_id=<?php echo (int) $flight['id']; ?>"
                                   class="btn btn-primary">Pesan Tiket</a>
                            <?php else: ?>
                                <span class="btn btn-secondary flight-action-muted" title="Waktu keberangkatan sudah lewat">Tidak bisa dipesan</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to calculate flight duration
function calculateFlightDuration($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    $diff = $arr->diff($dep);
    
    $hours = $diff->h;
    $minutes = $diff->i;
    
    if ($hours > 0) {
        return $hours . ' jam ' . $minutes . ' menit';
    } else {
        return $minutes . ' menit';
    }
}
?>

<style>
.flights-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    color: #1f2937;
    margin-bottom: 1rem;
}

.page-header p {
    color: #6b7280;
    font-size: 1.125rem;
}

.flights-container {
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

.flight-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.flight-card {
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: center;
    transition: all 0.3s;
    background: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.flight-card:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.1);
    transform: translateY(-2px);
}

.flight-card--past {
    opacity: 0.88;
}

.flight-card--past:hover {
    transform: none;
}

.flight-action-muted {
    pointer-events: none;
    cursor: not-allowed;
    opacity: 0.9;
}

.flight-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.airline strong {
    color: #1f2937;
}

.airline small {
    color: #6b7280;
}

.flight-route {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.departure h4,
.arrival h4 {
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.departure p,
.arrival p {
    color: #6b7280;
    font-size: 0.875rem;
}

.flight-arrow {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #2563eb;
}

.flight-arrow small {
    color: #6b7280;
    font-size: 0.75rem;
}

.flight-details {
    text-align: center;
}

.flight-number p {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.flight-price h3 {
    color: #2563eb;
    font-size: 1.25rem;
}

.flight-price p {
    color: #6b7280;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .flight-card {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .flight-route {
        justify-content: center;
    }
    
    .flight-action {
        text-align: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
