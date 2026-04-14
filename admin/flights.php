<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is admin
if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(APP_URL . 'auth/login.php');
}

$flights = [];
$airlines = [];
$airports = [];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all flights with related data
    $query = "SELECT f.*, a.name as airline_name, a.code as airline_code,
              dep.name as departure_airport, dep.city as departure_city,
              arr.name as arrival_airport, arr.city as arrival_city
              FROM flights f
              JOIN airlines a ON f.airline_id = a.id
              JOIN airports dep ON f.departure_airport_id = dep.id
              JOIN airports arr ON f.arrival_airport_id = arr.id
              ORDER BY f.departure_time DESC";
    $stmt = $db->query($query);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get airlines for dropdown
    $stmt = $db->query("SELECT * FROM airlines ORDER BY name");
    $airlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get airports for dropdown
    $stmt = $db->query("SELECT * FROM airports ORDER BY city");
    $airports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data penerbangan.');
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("DELETE FROM flights WHERE id = ?");
        $stmt->bindParam(1, $_GET['delete']);
        $stmt->execute();

        setMessage('success', 'Penerbangan berhasil dihapus.');
        redirect(APP_URL . 'admin/flights.php');
    } catch(PDOException $exception) {
        setMessage('danger', 'Gagal menghapus penerbangan. Mungkin ada pesanan terkait.');
    }
}

$page_title = 'Kelola Penerbangan';
include '../includes/header.php';
?>

<div class="admin-page">
    <div class="page-header">
        <h1>Kelola Penerbangan</h1>
        <a href="<?php echo APP_URL; ?>admin/flight_form.php" class="btn btn-primary">
            <span>+</span> Tambah Penerbangan
        </a>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Penerbangan</th>
                    <th>Maskapai</th>
                    <th>Rute</th>
                    <th>Berangkat</th>
                    <th>Tiba</th>
                    <th>Harga</th>
                    <th>Kursi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($flights)): ?>
                    <tr>
                        <td colspan="10" class="text-center">Belum ada data penerbangan</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php foreach ($flights as $flight): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $flight['flight_number']; ?></td>
                            <td>
                                <?php echo $flight['airline_name']; ?>
                                <small>(<?php echo $flight['airline_code']; ?>)</small>
                            </td>
                            <td>
                                <?php echo $flight['departure_city']; ?> - <?php echo $flight['arrival_city']; ?>
                                <br>
                                <small><?php echo $flight['departure_airport']; ?> - <?php echo $flight['arrival_airport']; ?></small>
                            </td>
                            <td><?php echo formatDateTime($flight['departure_time']); ?></td>
                            <td><?php echo formatDateTime($flight['arrival_time']); ?></td>
                            <td><?php echo formatCurrency($flight['price']); ?></td>
                            <td>
                                <?php echo $flight['available_seats']; ?>/<?php echo $flight['total_seats']; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $flight['status']; ?>">
                                    <?php echo ucfirst($flight['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo APP_URL; ?>admin/flight_form.php?id=<?php echo $flight['id']; ?>" 
                                       class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="<?php echo APP_URL; ?>admin/flights.php?delete=<?php echo $flight['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus penerbangan ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
