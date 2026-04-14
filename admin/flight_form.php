<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is admin
if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(APP_URL . 'auth/login.php');
}

$flight = null;
$airlines = [];
$airports = [];
$errors = [];

// Get flight data if editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("SELECT * FROM flights WHERE id = ?");
        $stmt->bindParam(1, $_GET['id']);
        $stmt->execute();

        $flight = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$flight) {
            setMessage('danger', 'Penerbangan tidak ditemukan.');
            redirect(APP_URL . 'admin/flights.php');
        }
    } catch(PDOException $exception) {
        setMessage('danger', 'Terjadi kesalahan saat mengambil data penerbangan.');
        redirect(APP_URL . 'admin/flights.php');
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get airlines for dropdown
    $stmt = $db->query("SELECT * FROM airlines ORDER BY name");
    $airlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get airports for dropdown
    $stmt = $db->query("SELECT * FROM airports ORDER BY city");
    $airports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flight_number = sanitizeInput($_POST['flight_number']);
    $airline_id = $_POST['airline_id'];
    $departure_airport_id = $_POST['departure_airport_id'];
    $arrival_airport_id = $_POST['arrival_airport_id'];
    $departure_time = normalizeDateTimeForDb($_POST['departure_time'] ?? '');
    $arrival_time = normalizeDateTimeForDb($_POST['arrival_time'] ?? '');
    $price = $_POST['price'];
    $total_seats = $_POST['total_seats'];
    $status = sanitizeInput($_POST['status'] ?? '');
    $allowed_status = ['scheduled', 'delayed', 'cancelled', 'completed'];
    if ($status === '' || !in_array($status, $allowed_status, true)) {
        $status = 'scheduled';
    }

    // Validation
    if (empty($flight_number)) {
        $errors[] = 'Nomor penerbangan harus diisi';
    }
    if (empty($airline_id)) {
        $errors[] = 'Maskapai harus dipilih';
    }
    if (empty($departure_airport_id)) {
        $errors[] = 'Bandara keberangkatan harus dipilih';
    }
    if (empty($arrival_airport_id)) {
        $errors[] = 'Bandara tujuan harus dipilih';
    }
    if ($departure_airport_id == $arrival_airport_id) {
        $errors[] = 'Bandara keberangkatan dan tujuan tidak boleh sama';
    }
    if (empty($departure_time)) {
        $errors[] = 'Waktu keberangkatan harus diisi';
    }
    if (empty($arrival_time)) {
        $errors[] = 'Waktu kedatangan harus diisi';
    }
    if (strtotime($arrival_time) <= strtotime($departure_time)) {
        $errors[] = 'Waktu kedatangan harus setelah waktu keberangkatan';
    }
    if (empty($price) || $price <= 0) {
        $errors[] = 'Harga harus lebih dari 0';
    }
    if (empty($total_seats) || $total_seats <= 0) {
        $errors[] = 'Jumlah kursi harus lebih dari 0';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            if ($flight) {
                // Update existing flight
                $available_seats = $total_seats - ($flight['total_seats'] - $flight['available_seats']);
                if ($available_seats < 0) $available_seats = 0;

                $query = "UPDATE flights SET flight_number = ?, airline_id = ?, departure_airport_id = ?, 
                         arrival_airport_id = ?, departure_time = ?, arrival_time = ?, price = ?, 
                         total_seats = ?, available_seats = ?, status = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $flight_id_update = (int) $_GET['id'];
                $stmt->bindValue(1, $flight_number);
                $stmt->bindValue(2, (int) $airline_id, PDO::PARAM_INT);
                $stmt->bindValue(3, (int) $departure_airport_id, PDO::PARAM_INT);
                $stmt->bindValue(4, (int) $arrival_airport_id, PDO::PARAM_INT);
                $stmt->bindValue(5, $departure_time);
                $stmt->bindValue(6, $arrival_time);
                $stmt->bindValue(7, (float) $price);
                $stmt->bindValue(8, (int) $total_seats, PDO::PARAM_INT);
                $stmt->bindValue(9, (int) $available_seats, PDO::PARAM_INT);
                $stmt->bindValue(10, $status);
                $stmt->bindValue(11, $flight_id_update, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    setMessage('success', 'Penerbangan berhasil diperbarui.');
                    redirect(APP_URL . 'admin/flights.php');
                } else {
                    $errors[] = 'Gagal memperbarui penerbangan.';
                }
            } else {
                // Check if flight number already exists
                $check_stmt = $db->prepare("SELECT id FROM flights WHERE flight_number = ?");
                $check_stmt->bindParam(1, $flight_number);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    $errors[] = 'Nomor penerbangan sudah digunakan';
                } else {
                    // Insert new flight
                    $query = "INSERT INTO flights (flight_number, airline_id, departure_airport_id, 
                             arrival_airport_id, departure_time, arrival_time, price, available_seats, total_seats, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $flight_number);
                    $stmt->bindParam(2, $airline_id);
                    $stmt->bindParam(3, $departure_airport_id);
                    $stmt->bindParam(4, $arrival_airport_id);
                    $stmt->bindParam(5, $departure_time);
                    $stmt->bindParam(6, $arrival_time);
                    $price_val = (float) $price;
                    $seats_total = (int) $total_seats;
                    $stmt->bindValue(7, $price_val);
                    $stmt->bindValue(8, $seats_total, PDO::PARAM_INT);
                    $stmt->bindValue(9, $seats_total, PDO::PARAM_INT);
                    $stmt->bindValue(10, $status);

                    if ($stmt->execute()) {
                        setMessage('success', 'Penerbangan berhasil ditambahkan.');
                        redirect(APP_URL . 'admin/flights.php');
                    } else {
                        $errors[] = 'Gagal menambahkan penerbangan.';
                    }
                }
            }
        } catch(PDOException $exception) {
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

$page_title = $flight ? 'Edit Penerbangan' : 'Tambah Penerbangan';
include '../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <a href="<?php echo APP_URL; ?>admin/flights.php" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="form-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="flight_number">Nomor Penerbangan *</label>
                    <input type="text" id="flight_number" name="flight_number" 
                           value="<?php echo isset($_POST['flight_number']) ? sanitizeInput($_POST['flight_number']) : ($flight ? $flight['flight_number'] : ''); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="airline_id">Maskapai *</label>
                    <select id="airline_id" name="airline_id" required>
                        <option value="">Pilih Maskapai</option>
                        <?php foreach ($airlines as $airline): ?>
                            <option value="<?php echo $airline['id']; ?>" 
                                    <?php echo (isset($_POST['airline_id']) && $_POST['airline_id'] == $airline['id']) || ($flight && $flight['airline_id'] == $airline['id']) ? 'selected' : ''; ?>>
                                <?php echo $airline['name']; ?> (<?php echo $airline['code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="departure_airport_id">Bandara Keberangkatan *</label>
                    <select id="departure_airport_id" name="departure_airport_id" required>
                        <option value="">Pilih Bandara</option>
                        <?php foreach ($airports as $airport): ?>
                            <option value="<?php echo $airport['id']; ?>"
                                    <?php echo (isset($_POST['departure_airport_id']) && $_POST['departure_airport_id'] == $airport['id']) || ($flight && $flight['departure_airport_id'] == $airport['id']) ? 'selected' : ''; ?>>
                                <?php echo $airport['city']; ?> - <?php echo $airport['name']; ?> (<?php echo $airport['code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="arrival_airport_id">Bandara Tujuan *</label>
                    <select id="arrival_airport_id" name="arrival_airport_id" required>
                        <option value="">Pilih Bandara</option>
                        <?php foreach ($airports as $airport): ?>
                            <option value="<?php echo $airport['id']; ?>"
                                    <?php echo (isset($_POST['arrival_airport_id']) && $_POST['arrival_airport_id'] == $airport['id']) || ($flight && $flight['arrival_airport_id'] == $airport['id']) ? 'selected' : ''; ?>>
                                <?php echo $airport['city']; ?> - <?php echo $airport['name']; ?> (<?php echo $airport['code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="departure_time">Waktu Keberangkatan *</label>
                    <input type="datetime-local" id="departure_time" name="departure_time" 
                           value="<?php echo isset($_POST['departure_time']) ? $_POST['departure_time'] : ($flight ? date('Y-m-d\TH:i', strtotime($flight['departure_time'])) : ''); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="arrival_time">Waktu Kedatangan *</label>
                    <input type="datetime-local" id="arrival_time" name="arrival_time" 
                           value="<?php echo isset($_POST['arrival_time']) ? $_POST['arrival_time'] : ($flight ? date('Y-m-d\TH:i', strtotime($flight['arrival_time'])) : ''); ?>" 
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Harga Tiket (IDR) *</label>
                    <input type="number" id="price" name="price" 
                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ($flight ? $flight['price'] : ''); ?>" 
                           min="0" step="1000" required>
                </div>

                <div class="form-group">
                    <label for="total_seats">Total Kursi *</label>
                    <input type="number" id="total_seats" name="total_seats" 
                           value="<?php echo isset($_POST['total_seats']) ? $_POST['total_seats'] : ($flight ? $flight['total_seats'] : ''); ?>" 
                           min="1" required>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status Penerbangan *</label>
                <select id="status" name="status" required>
                    <option value="scheduled" 
                            <?php echo (!isset($_POST['status']) && !$flight) || (isset($_POST['status']) && $_POST['status'] == 'scheduled') || ($flight && $flight['status'] == 'scheduled') ? 'selected' : ''; ?>>
                        Terjadwal
                    </option>
                    <option value="delayed" 
                            <?php echo (isset($_POST['status']) && $_POST['status'] == 'delayed') || ($flight && $flight['status'] == 'delayed') ? 'selected' : ''; ?>>
                        Terlambat
                    </option>
                    <option value="cancelled" 
                            <?php echo (isset($_POST['status']) && $_POST['status'] == 'cancelled') || ($flight && $flight['status'] == 'cancelled') ? 'selected' : ''; ?>>
                            Dibatalkan
                    </option>
                    <option value="completed" 
                            <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') || ($flight && $flight['status'] == 'completed') ? 'selected' : ''; ?>>
                            Selesai
                    </option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $flight ? 'Update Penerbangan' : 'Tambah Penerbangan'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>admin/flights.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
