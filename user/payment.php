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
$payment = null;
$payment_proof = null;
$errors = [];

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
             dep.city as departure_city, arr.city as arrival_city,
             f.departure_time, f.arrival_time, f.price
             FROM bookings b
             JOIN flights f ON b.flight_id = f.id
             JOIN airlines a ON f.airline_id = a.id
             JOIN airports dep ON f.departure_airport_id = dep.id
             JOIN airports arr ON f.arrival_airport_id = arr.id
             WHERE b.id = ? AND b.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_GET['booking_id']);
    $stmt->bindParam(2, $_SESSION['user_id']);
    $stmt->execute();

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        setMessage('danger', 'Pesanan tidak ditemukan.');
        redirect(APP_URL . 'user/bookings.php');
    }

    // Get payment data
    $stmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ?");
    $stmt->bindParam(1, $_GET['booking_id']);
    $stmt->execute();

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        setMessage('danger', 'Data pembayaran tidak ditemukan.');
        redirect(APP_URL . 'user/bookings.php');
    }

    $stmt = $db->prepare("SELECT * FROM payment_proofs WHERE payment_id = ? ORDER BY upload_date DESC, id DESC LIMIT 1");
    $stmt->bindParam(1, $payment['id']);
    $stmt->execute();
    $payment_proof = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Check if payment is already confirmed
    if ($payment['status'] === 'confirmed') {
        redirect(APP_URL . 'user/ticket.php?booking_id=' . $_GET['booking_id']);
    }

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data pesanan.');
    redirect(APP_URL . 'user/bookings.php');
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = MAX_FILE_SIZE;

        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Format file tidak diizinkan. Hanya JPG, JPEG, dan PNG yang diperbolehkan.';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            try {
                $database = new Database();
                $db = $database->getConnection();

                $db->beginTransaction();

                // Generate unique filename
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $file_name = 'payment_' . $booking['booking_code'] . '_' . time() . '.' . $file_extension;
                $relative_file_path = UPLOAD_PATH . $file_name;
                $upload_dir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . trim(UPLOAD_PATH, '/\\');
                $absolute_file_path = $upload_dir . DIRECTORY_SEPARATOR . $file_name;

                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                    throw new RuntimeException('Folder upload tidak dapat dibuat.');
                }

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $absolute_file_path)) {
                    // Insert payment proof
                    $proof_query = "INSERT INTO payment_proofs (payment_id, file_name, file_path, file_size) 
                                   VALUES (?, ?, ?, ?)";
                    $proof_stmt = $db->prepare($proof_query);
                    $proof_stmt->bindParam(1, $payment['id']);
                    $proof_stmt->bindParam(2, $file_name);
                    $proof_stmt->bindParam(3, $relative_file_path);
                    $proof_stmt->bindParam(4, $file['size']);
                    $proof_stmt->execute();

                    // Tetap gunakan status enum yang valid di database.
                    $update_query = "UPDATE payments SET status = 'pending', admin_notes = NULL WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(1, $payment['id']);
                    $update_stmt->execute();

                    // `paid` di tabel bookings berarti bukti sudah diunggah dan menunggu verifikasi admin.
                    $booking_query = "UPDATE bookings SET payment_status = 'paid' WHERE id = ?";
                    $booking_stmt = $db->prepare($booking_query);
                    $booking_stmt->bindParam(1, $booking['id']);
                    $booking_stmt->execute();

                    $db->commit();

                    setMessage('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
                    redirect(APP_URL . 'user/bookings.php');
                } else {
                    $errors[] = 'Gagal mengunggah file. Silakan coba lagi.';
                }
            } catch(Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    } else {
        $errors[] = 'Silakan pilih file bukti pembayaran.';
    }
}

$page_title = 'Pembayaran';
include '../includes/header.php';
?>

<div class="payment-page">
    <div class="payment-container">
        <div class="payment-instructions">
            <h2>Informasi Pembayaran</h2>
            <div class="instruction-card">
                <h3>Transfer ke Rekening:</h3>
                <div class="bank-info">
                    <p><strong>Bank:</strong> Bank Central Asia (BCA)</p>
                    <p><strong>No. Rekening:</strong> 1234567890</p>
                    <p><strong>Atas Nama:</strong> PT Dilan Maskapai Indonesia</p>
                </div>
                
                <h3>Catatan Penting:</h3>
                <ul>
                    <li>Transfer sesuai dengan total pembayaran</li>
                    <li>Upload bukti transfer dalam format JPG/PNG</li>
                    <li>Maksimal ukuran file 5MB</li>
                    <li>Pastikan bukti transfer jelas terbaca</li>
                </ul>
            </div>
        </div>

        <div class="payment-details">
            <h2>Detail Pesanan</h2>
            <div class="booking-summary">
                <div class="summary-header">
                    <h3>Kode Booking: <?php echo $booking['booking_code']; ?></h3>
                    <span class="badge badge-<?php echo $booking['status']; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </div>

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

                <div class="payment-summary">
                    <div class="summary-item">
                        <span>Harga per penumpang:</span>
                        <span><?php echo formatCurrency($booking['price']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Jumlah penumpang:</span>
                        <span><?php echo $booking['total_passengers']; ?></span>
                    </div>
                    <div class="summary-item total">
                        <span>Total Pembayaran:</span>
                        <span><?php echo formatCurrency($booking['total_price']); ?></span>
                    </div>
                </div>

                <?php if (($payment['status'] === 'pending' && !$payment_proof) || $payment['status'] === 'rejected'): ?>
                    <div class="upload-section">
                        <h3><?php echo $payment['status'] === 'rejected' ? 'Upload Ulang Bukti Pembayaran' : 'Upload Bukti Pembayaran'; ?></h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($payment['status'] === 'rejected'): ?>
                            <div class="alert alert-warning">
                                <p><?php echo $payment['admin_notes'] ?: 'Bukti pembayaran sebelumnya ditolak. Silakan unggah ulang bukti yang lebih jelas.'; ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <div class="form-group">
                                <label for="payment_proof">Pilih File Bukti Pembayaran *</label>
                                <input type="file" id="payment_proof" name="payment_proof" 
                                       accept="image/jpeg,image/png,image/jpg" required>
                                <small>Format: JPG, JPEG, PNG. Maksimal: 5MB</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full">Upload Bukti Pembayaran</button>
                        </form>
                    </div>
                <?php elseif ($payment['status'] === 'pending' && $payment_proof): ?>
                    <div class="status-section">
                        <div class="alert alert-info">
                            <h4>Menunggu Verifikasi</h4>
                            <p>Bukti pembayaran Anda sedang diverifikasi oleh admin. Proses ini biasanya memakan waktu 1x24 jam.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
