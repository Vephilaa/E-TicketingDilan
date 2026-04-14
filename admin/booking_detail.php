<?php
require_once __DIR__ . '/../config/config.php';

if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(APP_URL . 'auth/login.php');
}

if (!isset($_GET['id']) || !ctype_digit((string) $_GET['id'])) {
    setMessage('danger', 'Pesanan tidak valid.');
    redirect(APP_URL . 'admin/bookings.php');
}

$booking_id = (int) $_GET['id'];
$booking = null;
$passengers = [];
$proof = null;
$has_uploaded_proof = false;

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT b.*, u.full_name, u.email, u.phone AS user_phone,
             f.flight_number, f.departure_time, f.arrival_time, f.price,
             a.name AS airline_name, a.code AS airline_code,
             dep.city AS departure_city, dep.name AS departure_airport, dep.code AS departure_code,
             arr.city AS arrival_city, arr.name AS arrival_airport, arr.code AS arrival_code,
             p.id AS payment_id, p.status AS payment_status, p.amount AS payment_amount,
             p.admin_notes AS payment_admin_notes
             FROM bookings b
             JOIN users u ON b.user_id = u.id
             JOIN flights f ON b.flight_id = f.id
             JOIN airlines a ON f.airline_id = a.id
             JOIN airports dep ON f.departure_airport_id = dep.id
             JOIN airports arr ON f.arrival_airport_id = arr.id
             LEFT JOIN payments p ON b.id = p.booking_id
             WHERE b.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $booking_id, PDO::PARAM_INT);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        setMessage('danger', 'Pesanan tidak ditemukan.');
        redirect(APP_URL . 'admin/bookings.php');
    }

    $stmt = $db->prepare('SELECT * FROM booking_passengers WHERE booking_id = ? ORDER BY id');
    $stmt->bindParam(1, $booking_id, PDO::PARAM_INT);
    $stmt->execute();
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($booking['payment_id'])) {
        $stmt = $db->prepare(
            'SELECT file_path, file_name FROM payment_proofs WHERE payment_id = ? ORDER BY upload_date DESC LIMIT 1'
        );
        $stmt->bindParam(1, $booking['payment_id'], PDO::PARAM_INT);
        $stmt->execute();
        $proof = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $has_uploaded_proof = $proof !== null;
    }
} catch (PDOException $e) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data pesanan.');
    redirect(APP_URL . 'admin/bookings.php');
}

$payment_badge_class = 'badge-pending';
if (!empty($booking['payment_status'])) {
    switch ($booking['payment_status']) {
        case 'confirmed':
            $payment_badge_class = 'badge-confirmed';
            break;
        case 'rejected':
        case 'refunded':
            $payment_badge_class = 'badge-cancelled';
            break;
        default:
            $payment_badge_class = $has_uploaded_proof ? 'badge-paid' : 'badge-pending';
    }
}

$page_title = 'Detail Pesanan';
include '../includes/header.php';
?>

<div class="admin-page admin-booking-detail">
    <div class="page-header">
        <div>
            <h1>Detail Pesanan</h1>
            <p class="text-muted"><?php echo sanitizeInput($booking['booking_code']); ?> — <?php echo sanitizeInput($booking['full_name']); ?></p>
        </div>
        <a href="<?php echo APP_URL; ?>admin/bookings.php" class="btn btn-secondary">Kembali ke daftar</a>
    </div>

    <div class="detail-grid detail-grid-top">
        <div class="form-container">
            <h2 class="detail-section-title">Penerbangan</h2>
            <p><strong>Maskapai:</strong> <?php echo sanitizeInput($booking['airline_name']); ?> (<?php echo sanitizeInput($booking['airline_code']); ?>)</p>
            <p><strong>No. penerbangan:</strong> <?php echo sanitizeInput($booking['flight_number']); ?></p>
            <p><strong>Rute:</strong> <?php echo sanitizeInput($booking['departure_city']); ?> (<?php echo sanitizeInput($booking['departure_code']); ?>) → <?php echo sanitizeInput($booking['arrival_city']); ?> (<?php echo sanitizeInput($booking['arrival_code']); ?>)</p>
            <p><strong>Berangkat:</strong> <?php echo formatDateTime($booking['departure_time']); ?></p>
            <p><strong>Tiba:</strong> <?php echo formatDateTime($booking['arrival_time']); ?></p>
        </div>

        <div class="form-container">
            <h2 class="detail-section-title">Pemesan</h2>
            <p><strong>Nama:</strong> <?php echo sanitizeInput($booking['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo sanitizeInput($booking['email']); ?></p>
            <?php if (!empty($booking['user_phone'])): ?>
                <p><strong>Telepon:</strong> <?php echo sanitizeInput($booking['user_phone']); ?></p>
            <?php endif; ?>
            <p><strong>Tanggal booking:</strong> <?php echo formatDateTime($booking['booking_date']); ?></p>
            <p><strong>Penumpang:</strong> <?php echo (int) $booking['total_passengers']; ?></p>
            <p><strong>Total:</strong> <?php echo formatCurrency($booking['total_price']); ?></p>
            <p><strong>Status pesanan:</strong> <span class="badge badge-<?php echo sanitizeInput($booking['status']); ?>"><?php echo ucfirst(sanitizeInput($booking['status'])); ?></span></p>
        </div>
    </div>

    <div class="form-container detail-panel-payment">
        <h2 class="detail-section-title">Pembayaran</h2>
        <?php if (!empty($booking['payment_id'])): ?>
            <p><strong>Status pembayaran:</strong> <span class="badge <?php echo htmlspecialchars($payment_badge_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $booking['payment_status'] === 'pending' && $has_uploaded_proof ? 'Menunggu Verifikasi' : ucfirst(str_replace('_', ' ', sanitizeInput($booking['payment_status']))); ?></span></p>
            <p><strong>Jumlah:</strong> <?php echo formatCurrency($booking['payment_amount']); ?></p>
            <?php if (!empty($booking['payment_admin_notes'])): ?>
                <p><strong>Catatan admin:</strong> <?php echo nl2br(sanitizeInput($booking['payment_admin_notes'])); ?></p>
            <?php endif; ?>
            <?php if ($proof && !empty($proof['file_path'])): ?>
                <p><strong>Bukti terakhir:</strong> <?php echo sanitizeInput($proof['file_name']); ?></p>
                <?php $proof_url = APP_URL . htmlspecialchars($proof['file_path'], ENT_QUOTES, 'UTF-8'); ?>
                <p>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openProofModal('<?php echo $proof_url; ?>')">
                        Lihat bukti
                    </button>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p>Belum ada data pembayaran untuk pesanan ini.</p>
        <?php endif; ?>
    </div>

    <div id="proofModal" class="proof-modal" aria-hidden="true">
        <div class="proof-modal-backdrop" onclick="closeProofModal()"></div>
        <div class="proof-modal-dialog" role="dialog" aria-modal="true" aria-label="Bukti Pembayaran">
            <button type="button" class="proof-modal-close" onclick="closeProofModal()" aria-label="Tutup">
                &times;
            </button>
            <div class="proof-modal-body">
                <img id="proofModalImage" src="" alt="Bukti Pembayaran">
            </div>
        </div>
    </div>

    <?php if (!empty($passengers)): ?>
        <div class="table-container detail-passengers-table">
            <h2 class="detail-section-title detail-passengers-heading">Daftar penumpang</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>No. identitas</th>
                        <th>Tanggal lahir</th>
                        <th>Jenis kelamin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passengers as $p): ?>
                        <tr>
                            <td><?php echo sanitizeInput($p['name']); ?></td>
                            <td><?php echo sanitizeInput($p['id_number']); ?></td>
                            <td><?php echo formatDate($p['birth_date']); ?></td>
                            <td><?php echo $p['gender'] === 'male' ? 'Laki-laki' : 'Perempuan'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<style>
.proof-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 2000;
}

.proof-modal.is-open {
    display: block;
}

.proof-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
}

.proof-modal-dialog {
    position: relative;
    max-width: min(980px, 92vw);
    max-height: 86vh;
    margin: 6vh auto 0;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.proof-modal-close {
    position: absolute;
    top: 10px;
    right: 12px;
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.08);
    color: #0f172a;
    font-size: 26px;
    line-height: 40px;
    cursor: pointer;
}

.proof-modal-close:hover {
    background: rgba(15, 23, 42, 0.12);
}

.proof-modal-body {
    padding: 18px;
    max-height: 86vh;
    overflow: auto;
}

.proof-modal-body img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 10px;
    border: 1px solid rgba(148, 163, 184, 0.25);
}
</style>

<script>
function openProofModal(url) {
    const modal = document.getElementById('proofModal');
    const img = document.getElementById('proofModalImage');
    img.src = url;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
}

function closeProofModal() {
    const modal = document.getElementById('proofModal');
    const img = document.getElementById('proofModalImage');
    img.src = '';
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('proofModal');
        if (modal && modal.classList.contains('is-open')) {
            closeProofModal();
        }
    }
});
</script>
