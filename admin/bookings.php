<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is admin
if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(APP_URL . 'auth/login.php');
}

$bookings = [];
$filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Build query based on filter
    $query = "SELECT b.*, u.full_name, u.email, f.flight_number, a.name as airline_name,
             dep.city as departure_city, arr.city as arrival_city,
             f.departure_time, f.arrival_time, f.price,
             p.status as payment_status, p.admin_notes,
             pp.file_name as payment_proof_file, pp.file_path as payment_proof_path
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN flights f ON b.flight_id = f.id
             JOIN airlines a ON f.airline_id = a.id
             JOIN airports dep ON f.departure_airport_id = dep.id
             JOIN airports arr ON f.arrival_airport_id = arr.id
             LEFT JOIN payments p ON b.id = p.booking_id
             LEFT JOIN payment_proofs pp ON pp.id = (
                 SELECT MAX(pp2.id) FROM payment_proofs pp2 WHERE pp2.payment_id = p.id
             )";

    $params = [];
    
    if ($filter_status === 'pending_verification') {
        $query .= " WHERE pp.id IS NOT NULL AND (p.status IS NULL OR p.status = '' OR p.status = 'pending')";
    } elseif (in_array($filter_status, ['pending', 'confirmed', 'rejected', 'refunded'], true)) {
        $query .= " WHERE p.status = ?";
        $params[] = $filter_status;
    }
    
    $query .= " ORDER BY b.booking_date DESC";

    $stmt = $db->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data pesanan.');
}

// Handle payment approval/rejection
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');

    try {
        $database = new Database();
        $db = $database->getConnection();

        $db->beginTransaction();

        // Get payment info
        $stmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ?");
        $stmt->bindParam(1, $booking_id);
        $stmt->execute();
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            // Update payment status
            $new_status = ($action === 'approve') ? 'confirmed' : 'rejected';
            $update_query = "UPDATE payments SET status = ?, admin_notes = ?, payment_date = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(1, $new_status);
            $update_stmt->bindParam(2, $admin_notes);
            $update_stmt->bindParam(3, $payment['id']);
            $update_stmt->execute();

            // Gunakan nilai enum yang memang ada di tabel bookings.
            $booking_payment_status = ($action === 'approve') ? 'paid' : 'rejected';
            $booking_status = ($action === 'approve') ? 'confirmed' : 'pending';
            $booking_query = "UPDATE bookings SET payment_status = ?, status = ? WHERE id = ?";
            $booking_stmt = $db->prepare($booking_query);
            $booking_stmt->bindParam(1, $booking_payment_status);
            $booking_stmt->bindParam(2, $booking_status);
            $booking_stmt->bindParam(3, $booking_id);
            $booking_stmt->execute();

            // If approved, create ticket
            if ($action === 'approve') {
                $ticket_number = generateTicketNumber();
                $qr_code = 'QR-' . strtoupper(substr(md5($ticket_number), 0, 20));

                $ticket_check = $db->prepare("SELECT id FROM tickets WHERE booking_id = ?");
                $ticket_check->bindParam(1, $booking_id);
                $ticket_check->execute();

                if (!$ticket_check->fetch(PDO::FETCH_ASSOC)) {
                    $ticket_query = "INSERT INTO tickets (booking_id, ticket_number, qr_code, status) VALUES (?, ?, ?, 'active')";
                    $ticket_stmt = $db->prepare($ticket_query);
                    $ticket_stmt->bindParam(1, $booking_id);
                    $ticket_stmt->bindParam(2, $ticket_number);
                    $ticket_stmt->bindParam(3, $qr_code);
                    $ticket_stmt->execute();
                }
            }

            $db->commit();

            $message = ($action === 'approve') ? 'Pembayaran berhasil dikonfirmasi.' : 'Pembayaran berhasil ditolak.';
            setMessage('success', $message);
        }
    } catch(PDOException $exception) {
        $db->rollBack();
        setMessage('danger', 'Terjadi kesalahan saat memproses pembayaran.');
    }

    redirect(APP_URL . 'admin/bookings.php');
}

$page_title = 'Kelola Pesanan';
include '../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1>Kelola Pesanan</h1>
        <div class="filter-buttons">
            <a href="<?php echo APP_URL; ?>admin/bookings.php" class="btn btn-secondary <?php echo empty($filter_status) ? 'active' : ''; ?>">
                Semua
            </a>
            <a href="<?php echo APP_URL; ?>admin/bookings.php?status=pending_verification" class="btn btn-secondary <?php echo $filter_status === 'pending_verification' ? 'active' : ''; ?>">
                Menunggu Verifikasi
            </a>
            <a href="<?php echo APP_URL; ?>admin/bookings.php?status=confirmed" class="btn btn-secondary <?php echo $filter_status === 'confirmed' ? 'active' : ''; ?>">
                Dikonfirmasi
            </a>
            <a href="<?php echo APP_URL; ?>admin/bookings.php?status=rejected" class="btn btn-secondary <?php echo $filter_status === 'rejected' ? 'active' : ''; ?>">
                Ditolak
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Booking</th>
                    <th>Nama Penumpang</th>
                    <th>Email</th>
                    <th>Penerbangan</th>
                    <th>Rute</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="9" class="text-center">Belum ada data pesanan</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <strong><?php echo $booking['booking_code']; ?></strong>
                                <br>
                                <small><?php echo formatDate($booking['booking_date']); ?></small>
                            </td>
                            <td><?php echo sanitizeInput($booking['full_name']); ?></td>
                            <td><?php echo sanitizeInput($booking['email']); ?></td>
                            <td>
                                <?php echo $booking['airline_name']; ?>
                                <br>
                                <small><?php echo $booking['flight_number']; ?></small>
                            </td>
                            <td>
                                <?php echo $booking['departure_city']; ?> - <?php echo $booking['arrival_city']; ?>
                                <br>
                                <small><?php echo formatDateTime($booking['departure_time']); ?></small>
                            </td>
                            <td><?php echo formatCurrency($booking['total_price']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $has_proof = !empty($booking['payment_proof_path']);
                                $can_verify = $has_proof && !in_array($booking['payment_status'], ['confirmed', 'rejected', 'refunded'], true);
                                $payment_badge = 'badge-pending';
                                if ($booking['payment_status'] === 'confirmed') {
                                    $payment_badge = 'badge-confirmed';
                                } elseif ($booking['payment_status'] === 'rejected') {
                                    $payment_badge = 'badge-cancelled';
                                } elseif ($has_proof) {
                                    $payment_badge = 'badge-paid';
                                }
                                ?>
                                <span class="badge <?php echo $payment_badge; ?>">
                                    <?php 
                                    $status_map = [
                                        'pending' => $has_proof ? 'Menunggu Verifikasi' : 'Belum Upload',
                                        '' => $has_proof ? 'Menunggu Verifikasi' : 'Belum Upload',
                                        'confirmed' => 'Dikonfirmasi',
                                        'rejected' => 'Ditolak',
                                        'refunded' => 'Dikembalikan'
                                    ];
                                    $payment_status_label = $status_map[$booking['payment_status'] ?? ''] ?? 'Menunggu Verifikasi';
                                    echo $payment_status_label;
                                    ?>
                                </span>
                                <?php if ($booking['payment_proof_file']): ?>
                                    <br>
                                    <small>Ada bukti</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="aksi-stack">
                                    <a href="<?php echo APP_URL; ?>admin/booking_detail.php?id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm btn-secondary">Detail</a>

                                    <?php if ($can_verify): ?>
                                        <form method="POST" class="aksi-form aksi-form-approve">
                                            <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-primary">Approve</button>
                                        </form>

                                        <form method="POST" class="aksi-form aksi-form-reject" onsubmit="return handleReject(this, '<?php echo sanitizeInput($booking['booking_code']); ?>')">
                                            <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="admin_notes" value="">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.filter-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-buttons .btn.active {
    background-color: #2563eb;
    color: white;
}

.admin-page .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.admin-page .table {
    min-width: 1100px;
}

.aksi-stack {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
}

.aksi-form {
    margin: 0;
}

.aksi-form .btn {
    width: 100%;
    justify-content: center;
}

@media (max-width: 768px) {
    .filter-buttons {
        flex-wrap: wrap;
    }
}
</style>

<script>
function handleReject(form, bookingCode) {
    const reason = prompt('Alasan penolakan untuk booking ' + bookingCode + ':');
    if (!reason || !reason.trim()) {
        alert('Penolakan butuh alasan.');
        return false;
    }
    const notesInput = form.querySelector('input[name=\"admin_notes\"]');
    if (notesInput) {
        notesInput.value = reason.trim();
    }
    return confirm('Yakin menolak pembayaran booking ' + bookingCode + '?');
}

document.querySelectorAll('.aksi-form-approve').forEach((form) => {
    form.addEventListener('submit', function(e) {
        const bookingId = form.querySelector('input[name=\"booking_id\"]')?.value || '';
        if (!confirm('Yakin approve pembayaran untuk booking #' + bookingId + '?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
