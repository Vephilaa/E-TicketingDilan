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

$flight = null;
$passengers_count = 1;
$errors = [];
$logged_in_full_name = isset($_SESSION['full_name']) ? sanitizeInput($_SESSION['full_name']) : '';
$logged_in_nik = isset($_SESSION['nik']) ? sanitizeInput($_SESSION['nik']) : '';
$default_passenger_name_1 = isset($_POST['passenger_name_1']) ? sanitizeInput($_POST['passenger_name_1']) : $logged_in_full_name;
$default_passenger_nik_1 = isset($_POST['passenger_id_1']) ? sanitizeInput($_POST['passenger_id_1']) : $logged_in_nik;

// Get flight data
if (!isset($_GET['flight_id']) || !is_numeric($_GET['flight_id'])) {
    setMessage('danger', 'Penerbangan tidak valid.');
    redirect(APP_URL . 'user/flights.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT f.*, a.name as airline_name, a.code as airline_code,
                         dep.name as departure_airport, dep.city as departure_city,
                         arr.name as arrival_airport, arr.city as arrival_city
                         FROM flights f
                         JOIN airlines a ON f.airline_id = a.id
                         JOIN airports dep ON f.departure_airport_id = dep.id
                         JOIN airports arr ON f.arrival_airport_id = arr.id
                         WHERE f.id = ? AND f.status IN ('scheduled', 'delayed')
                         AND f.available_seats > 0 AND f.departure_time >= NOW()");
    $stmt->bindParam(1, $_GET['flight_id']);
    $stmt->execute();

    $flight = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flight) {
        setMessage('danger', 'Penerbangan tidak ditemukan, waktu keberangkatan sudah lewat, atau tidak tersedia.');
        redirect(APP_URL . 'user/flights.php');
    }

    if ($flight['available_seats'] < $passengers_count) {
        setMessage('danger', 'Kursi tidak mencukupi untuk ' . $passengers_count . ' penumpang.');
        redirect(APP_URL . 'user/flights.php');
    }

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data penerbangan.');
    redirect(APP_URL . 'user/flights.php');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passengers = [];
    $total_price = $flight['price'] * $passengers_count;

    // Validate passenger data
    for ($i = 1; $i <= $passengers_count; $i++) {
        $name = sanitizeInput($_POST['passenger_name_' . $i]);
        $id_number = sanitizeInput($_POST['passenger_id_' . $i]);
        $birth_date = $_POST['passenger_birth_' . $i];
        $gender = $_POST['passenger_gender_' . $i];

        if (empty($name) || empty($id_number) || empty($birth_date) || empty($gender)) {
            $errors[] = 'Data penumpang ke-' . $i . ' tidak lengkap';
        } else {
            $passengers[] = [
                'name' => $name,
                'id_number' => $id_number,
                'birth_date' => $birth_date,
                'gender' => $gender
            ];
        }
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $db->beginTransaction();

            // Generate booking code
            $booking_code = generateBookingCode();

            // Create booking
            $booking_query = "INSERT INTO bookings (booking_code, user_id, flight_id, total_passengers, total_price, status) 
                             VALUES (?, ?, ?, ?, ?, 'pending')";
            $booking_stmt = $db->prepare($booking_query);
            $booking_stmt->bindParam(1, $booking_code);
            $booking_stmt->bindParam(2, $_SESSION['user_id']);
            $booking_stmt->bindParam(3, $flight['id']);
            $booking_stmt->bindParam(4, $passengers_count);
            $booking_stmt->bindParam(5, $total_price);
            $booking_stmt->execute();

            $booking_id = $db->lastInsertId();

            // Add passengers
            foreach ($passengers as $index => $passenger) {
                $passenger_query = "INSERT INTO booking_passengers (booking_id, name, id_number, birth_date, gender) 
                                   VALUES (?, ?, ?, ?, ?)";
                $passenger_stmt = $db->prepare($passenger_query);
                $passenger_stmt->bindParam(1, $booking_id);
                $passenger_stmt->bindParam(2, $passenger['name']);
                $passenger_stmt->bindParam(3, $passenger['id_number']);
                $passenger_stmt->bindParam(4, $passenger['birth_date']);
                $passenger_stmt->bindParam(5, $passenger['gender']);
                $passenger_stmt->execute();
            }

            // Create payment record
            $payment_query = "INSERT INTO payments (booking_id, amount, status) VALUES (?, ?, 'pending')";
            $payment_stmt = $db->prepare($payment_query);
            $payment_stmt->bindParam(1, $booking_id);
            $payment_stmt->bindParam(2, $total_price);
            $payment_stmt->execute();

            // Update available seats
            $new_available_seats = $flight['available_seats'] - $passengers_count;
            $seat_query = "UPDATE flights SET available_seats = ? WHERE id = ?";
            $seat_stmt = $db->prepare($seat_query);
            $seat_stmt->bindParam(1, $new_available_seats);
            $seat_stmt->bindParam(2, $flight['id']);
            $seat_stmt->execute();

            $db->commit();

            setMessage('success', 'Pesanan berhasil dibuat! Kode booking: ' . $booking_code);
            redirect(APP_URL . 'user/payment.php?booking_id=' . $booking_id);

        } catch(PDOException $exception) {
            $db->rollBack();
            $errors[] = 'Terjadi kesalahan saat membuat pesanan. Silakan coba lagi.';
        }
    }
}

$page_title = 'Detail Pemesanan';
include '../includes/header.php';
?>

<div class="booking-page">
    <div class="booking-container">
        <div class="flight-summary">
            <h2>Detail Penerbangan</h2>
            <div class="flight-card">
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
                        <p><strong>Penumpang:</strong> <?php echo $passengers_count; ?></p>
                    </div>
                    <div class="flight-price">
                        <h3><?php echo formatCurrency($flight['price']); ?></h3>
                        <p>per penumpang</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="passenger-form">
            <h2>Data Penumpang</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="self_booking_checkbox" checked>
                        Pesan untuk diri sendiri
                    </label>
                    <small>Nama dan NIK penumpang akan diisi otomatis dari akun Anda.</small>
                </div>

                <?php for ($i = 1; $i <= $passengers_count; $i++): ?>
                    <div class="passenger-section">
                        <h3>Penumpang <?php echo $i; ?></h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="passenger_name_<?php echo $i; ?>">Nama Lengkap *</label>
                                <input type="text" id="passenger_name_<?php echo $i; ?>" 
                                       name="passenger_name_<?php echo $i; ?>"
                                       value="<?php echo $i === 1 ? $default_passenger_name_1 : (isset($_POST['passenger_name_' . $i]) ? sanitizeInput($_POST['passenger_name_' . $i]) : ''); ?>"
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="passenger_id_<?php echo $i; ?>">NIK *</label>
                                <input type="text" id="passenger_id_<?php echo $i; ?>" 
                                       name="passenger_id_<?php echo $i; ?>"
                                       value="<?php echo $i === 1 ? $default_passenger_nik_1 : (isset($_POST['passenger_id_' . $i]) ? sanitizeInput($_POST['passenger_id_' . $i]) : ''); ?>"
                                       required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="passenger_birth_<?php echo $i; ?>">Tanggal Lahir *</label>
                                <input type="date" id="passenger_birth_<?php echo $i; ?>" 
                                       name="passenger_birth_<?php echo $i; ?>"
                                       value="<?php echo isset($_POST['passenger_birth_' . $i]) ? sanitizeInput($_POST['passenger_birth_' . $i]) : ''; ?>"
                                       required>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin *</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="passenger_gender_<?php echo $i; ?>" 
                                               value="male"
                                               <?php echo (isset($_POST['passenger_gender_' . $i]) && $_POST['passenger_gender_' . $i] === 'male') ? 'checked' : ''; ?>
                                               required> Laki-laki
                                    </label>
                                    <label>
                                        <input type="radio" name="passenger_gender_<?php echo $i; ?>" 
                                               value="female"
                                               <?php echo (isset($_POST['passenger_gender_' . $i]) && $_POST['passenger_gender_' . $i] === 'female') ? 'checked' : ''; ?>> Perempuan
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>

                <div class="booking-summary">
                    <h3>Ringkasan Pembayaran</h3>
                    <div class="summary-item">
                        <span>Harga per penumpang:</span>
                        <span><?php echo formatCurrency($flight['price']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Jumlah penumpang:</span>
                        <span><?php echo $passengers_count; ?></span>
                    </div>
                    <div class="summary-item total">
                        <span>Total:</span>
                        <span><?php echo formatCurrency($flight['price'] * $passengers_count); ?></span>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?php echo APP_URL; ?>user/search.php" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">Lanjut ke Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selfBookingCheckbox = document.getElementById('self_booking_checkbox');
    const passengerNameInput = document.getElementById('passenger_name_1');
    const passengerNikInput = document.getElementById('passenger_id_1');
    const accountFullName = <?php echo json_encode($logged_in_full_name); ?>;
    const accountNik = <?php echo json_encode($logged_in_nik); ?>;

    if (!selfBookingCheckbox || !passengerNameInput || !passengerNikInput) {
        return;
    }

    const syncSelfBookingFields = function () {
        if (selfBookingCheckbox.checked) {
            passengerNameInput.value = accountFullName || '';
            passengerNikInput.value = accountNik || '';
            passengerNameInput.readOnly = true;
            passengerNikInput.readOnly = true;
        } else {
            passengerNameInput.readOnly = false;
            passengerNikInput.readOnly = false;
            passengerNameInput.value = '';
            passengerNikInput.value = '';
        }
    };

    selfBookingCheckbox.addEventListener('change', function () {
        syncSelfBookingFields();
    });

    syncSelfBookingFields();
});
</script>

<?php include '../includes/footer.php'; ?>
