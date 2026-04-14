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

$booking = null;
$ticket = null;
$passengers = [];

// Get booking data
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    setMessage('danger', 'Pesanan tidak valid.');
    redirect(APP_URL . 'user/bookings.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get booking with related data
    $query = "SELECT b.*, f.flight_number, a.name as airline_name, a.code as airline_code,
             dep.city as departure_city, dep.name as departure_airport, dep.code as departure_airport_code,
             arr.city as arrival_city, arr.name as arrival_airport, arr.code as arrival_airport_code,
             f.departure_time, f.arrival_time, f.price
             FROM bookings b
             JOIN flights f ON b.flight_id = f.id
             JOIN airlines a ON f.airline_id = a.id
             JOIN airports dep ON f.departure_airport_id = dep.id
             JOIN airports arr ON f.arrival_airport_id = arr.id
             WHERE b.id = ? AND b.user_id = ? AND b.payment_status = 'paid'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_GET['booking_id']);
    $stmt->bindParam(2, $_SESSION['user_id']);
    $stmt->execute();

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        setMessage('danger', 'Pesanan tidak ditemukan atau pembayaran belum disetujui admin.');
        redirect(APP_URL . 'user/bookings.php');
    }

    // Get passengers
    $stmt = $db->prepare("SELECT * FROM booking_passengers WHERE booking_id = ?");
    $stmt->bindParam(1, $booking['id']);
    $stmt->execute();

    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if ticket exists, create if not
    $stmt = $db->prepare("SELECT * FROM tickets WHERE booking_id = ?");
    $stmt->bindParam(1, $booking['id']);
    $stmt->execute();

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        // Generate ticket
        $ticket_number = generateTicketNumber();
        $qr_code = 'QR-' . strtoupper(substr(md5($ticket_number), 0, 20));

        $insert_query = "INSERT INTO tickets (booking_id, ticket_number, qr_code, status) VALUES (?, ?, ?, 'active')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(1, $booking['id']);
        $insert_stmt->bindParam(2, $ticket_number);
        $insert_stmt->bindParam(3, $qr_code);
        $insert_stmt->execute();

        $ticket = [
            'ticket_number' => $ticket_number,
            'qr_code' => $qr_code,
            'issued_date' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
    }

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data tiket.');
    redirect(APP_URL . 'user/bookings.php');
}

$page_title = 'E-Ticket';
include '../includes/header.php';
?>

<div class="ticket-page">
    <div class="ticket-container">
        <div class="ticket-header">
            <h2>E-Ticket Pesawat</h2>
            <div class="ticket-actions">
                <button type="button" onclick="printTicket()" class="btn btn-secondary">
                    <span aria-hidden="true">&#128424;</span> Cetak Tiket
                </button>
                <button type="button" onclick="downloadPDF()" class="btn btn-primary">
                    <span aria-hidden="true">&#128190;</span> Download PDF
                </button>
            </div>
        </div>

        <div class="ticket-content" id="ticket-content">
            <div class="ticket-main">
                <div class="ticket-header-info">
                    <div class="ticket-logo">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>E-Ticket Elektronik</p>
                    </div>
                    <div class="ticket-number">
                        <h3>No. Tiket: <?php echo $ticket['ticket_number']; ?></h3>
                        <p>Kode Booking: <?php echo $booking['booking_code']; ?></p>
                    </div>
                </div>

                <div class="ticket-flight">
                    <div class="flight-header">
                        <div class="airline-info">
                            <h3><?php echo $booking['airline_name']; ?></h3>
                            <p><?php echo $booking['airline_code']; ?> - <?php echo $booking['flight_number']; ?></p>
                        </div>
                        <div class="ticket-status">
                            <span class="badge badge-<?php echo $ticket['status']; ?>">
                                <?php echo ucfirst($ticket['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="flight-route">
                        <div class="departure">
                            <div class="airport-info">
                                <h2><?php echo $booking['departure_airport_code']; ?></h2>
                                <h3><?php echo $booking['departure_city']; ?></h3>
                                <p><?php echo $booking['departure_airport']; ?></p>
                            </div>
                            <div class="time-info">
                                <h4><?php echo date('H:i', strtotime($booking['departure_time'])); ?></h4>
                                <p><?php echo formatDate($booking['departure_time']); ?></p>
                            </div>
                        </div>

                        <div class="flight-connector">
                            <div class="flight-line">
                                <span class="plane-icon" aria-hidden="true">&#9992;</span>
                            </div>
                            <div class="flight-duration">
                                <p><?php echo calculateFlightDuration($booking['departure_time'], $booking['arrival_time']); ?></p>
                            </div>
                        </div>

                        <div class="arrival">
                            <div class="airport-info">
                                <h2><?php echo $booking['arrival_airport_code']; ?></h2>
                                <h3><?php echo $booking['arrival_city']; ?></h3>
                                <p><?php echo $booking['arrival_airport']; ?></p>
                            </div>
                            <div class="time-info">
                                <h4><?php echo date('H:i', strtotime($booking['arrival_time'])); ?></h4>
                                <p><?php echo formatDate($booking['arrival_time']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ticket-passengers">
                    <h3>Data Penumpang</h3>
                    <div class="passengers-list">
                        <?php foreach ($passengers as $index => $passenger): ?>
                            <div class="passenger-item">
                                <div class="passenger-number">
                                    <span><?php echo $index + 1; ?></span>
                                </div>
                                <div class="passenger-info">
                                    <h4><?php echo sanitizeInput($passenger['name']); ?></h4>
                                    <p>ID: <?php echo sanitizeInput($passenger['id_number']); ?></p>
                                    <p><?php echo $passenger['gender'] === 'male' ? 'Laki-laki' : 'Perempuan'; ?>, <?php echo formatDate($passenger['birth_date']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ticket-footer">
                    <div class="qr-section">
                        <div class="qr-code">
                            <div class="qr-placeholder">
                                <span>QR</span>
                                <small><?php echo $ticket['qr_code']; ?></small>
                            </div>
                        </div>
                        <div class="qr-info">
                            <h4>Kode QR</h4>
                            <p>Tunjukkan kode ini saat check-in</p>
                        </div>
                    </div>

                    <div class="ticket-info">
                        <div class="info-item">
                            <span>Tanggal Diterbitkan:</span>
                            <span><?php echo formatDateTime($ticket['issued_date']); ?></span>
                        </div>
                        <div class="info-item">
                            <span>Total Pembayaran:</span>
                            <span><?php echo formatCurrency($booking['total_price']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ticket-instructions">
                <h4>Petunjuk Penting:</h4>
                <ul>
                    <li>Tiket ini berlaku untuk penerbangan pada tanggal yang tertera</li>
                    <li>Harap tiba di bandara 2 jam sebelum keberangkatan untuk penerbangan domestik</li>
                    <li>Bawa identitas diri yang sesuai dengan data penumpang</li>
                    <li>Tunjukkan e-ticket ini dan identitas diri saat check-in</li>
                    <li>Cetak tiket ini jika diperlukan untuk check-in di bandara</li>
                </ul>
            </div>
        </div>
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
.ticket-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
}

.ticket-container {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
}

.ticket-header h2 {
    margin: 0;
}

.ticket-actions {
    display: flex;
    gap: 1rem;
}

.ticket-actions .btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
}

.ticket-actions .btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.ticket-content {
    padding: 2rem;
}

.ticket-main {
    border: 2px solid #e5e7eb;
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.ticket-header-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.ticket-logo h1 {
    color: #2563eb;
    margin: 0;
    font-size: 1.5rem;
}

.ticket-logo p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.ticket-number h3 {
    color: #1f2937;
    margin: 0;
    font-size: 1.25rem;
}

.ticket-number p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.flight-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.airline-info h3 {
    color: #1f2937;
    margin: 0;
}

.airline-info p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.flight-route {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
}

.departure,
.arrival {
    flex: 1;
    text-align: center;
}

.airport-info h2 {
    color: #2563eb;
    font-size: 2rem;
    margin: 0;
}

.airport-info h3 {
    color: #1f2937;
    margin: 0.25rem 0;
}

.airport-info p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.time-info h4 {
    color: #1f2937;
    font-size: 1.5rem;
    margin: 0.5rem 0 0.25rem;
}

.time-info p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.flight-connector {
    flex: 0.5;
    text-align: center;
}

.flight-line {
    position: relative;
    height: 2px;
    background: #e5e7eb;
    margin: 1rem 0;
}

.plane-icon {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 0 0.5rem;
    color: #2563eb;
}

.flight-duration p {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}

.ticket-passengers {
    margin-bottom: 2rem;
}

.ticket-passengers h3 {
    color: #1f2937;
    margin-bottom: 1rem;
}

.passengers-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.passenger-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.passenger-number {
    width: 40px;
    height: 40px;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.passenger-info h4 {
    color: #1f2937;
    margin: 0 0 0.25rem;
}

.passenger-info p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.ticket-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 2px solid #f3f4f6;
}

.qr-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.qr-placeholder {
    width: 80px;
    height: 80px;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.qr-placeholder span {
    font-size: 1.5rem;
    font-weight: 600;
    color: #6b7280;
}

.qr-placeholder small {
    font-size: 0.625rem;
    color: #6b7280;
    text-align: center;
}

.qr-info h4 {
    color: #1f2937;
    margin: 0;
}

.qr-info p {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
}

.ticket-info {
    text-align: right;
}

.info-item {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 0.5rem;
}

.info-item span:first-child {
    color: #6b7280;
}

.info-item span:last-child {
    color: #1f2937;
    font-weight: 500;
}

.ticket-instructions {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
}

.ticket-instructions h4 {
    color: #1f2937;
    margin: 0 0 1rem;
}

.ticket-instructions ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #6b7280;
}

.ticket-instructions li {
    margin-bottom: 0.5rem;
}

@media print {
    .ticket-header {
        background: none;
        color: #1f2937;
    }
    
    .ticket-actions {
        display: none;
    }
    
    .ticket-content {
        padding: 0;
    }
    
    .ticket-main {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .ticket-instructions {
        background: none;
    }
}

@media (max-width: 768px) {
    .ticket-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .ticket-header-info {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .flight-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .flight-route {
        flex-direction: column;
        gap: 1rem;
    }
    
    .flight-connector {
        transform: rotate(90deg);
        width: 100px;
    }
    
    .ticket-footer {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .qr-section {
        justify-content: center;
    }
    
    .ticket-info {
        text-align: center;
    }
}
</style>

<script>
function printTicket() {
    window.print();
}

function downloadPDF() {
    // Create a simple text-based ticket for now
    const ticketContent = document.getElementById('ticket-content').innerText;
    const blob = new Blob([ticketContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ticket_<?php echo $booking['booking_code']; ?>.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include '../includes/footer.php'; ?>
