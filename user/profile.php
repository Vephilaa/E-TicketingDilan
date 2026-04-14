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

$user = null;
$errors = [];
$success = false;

// Get user data
try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        setMessage('danger', 'Data pengguna tidak ditemukan.');
        redirect(APP_URL . 'user/flights.php');
    }

} catch(PDOException $exception) {
    setMessage('danger', 'Terjadi kesalahan saat mengambil data pengguna.');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $phone = sanitizeInput($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($full_name)) {
        $errors[] = 'Nama lengkap harus diisi';
    }

    // If changing password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = 'Password saat ini harus diisi untuk mengubah password';
        }
        
        if (empty($new_password)) {
            $errors[] = 'Password baru harus diisi';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Password baru minimal 6 karakter';
        }

        if (empty($confirm_password)) {
            $errors[] = 'Konfirmasi password baru harus diisi';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Konfirmasi password baru tidak cocok';
        }

        // Verify current password
        if (empty($errors) && !password_verify($current_password, $user['password'])) {
            $errors[] = 'Password saat ini salah';
        }
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $full_name);
                $stmt->bindParam(2, $phone);
                $stmt->bindParam(3, $hashed_password);
                $stmt->bindParam(4, $_SESSION['user_id']);
            } else {
                // Update without changing password
                $query = "UPDATE users SET full_name = ?, phone = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $full_name);
                $stmt->bindParam(2, $phone);
                $stmt->bindParam(3, $_SESSION['user_id']);
            }

            if ($stmt->execute()) {
                // Update session data
                $_SESSION['full_name'] = $full_name;
                
                $success = true;
                setMessage('success', 'Profil berhasil diperbarui.');
                
                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bindParam(1, $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = 'Gagal memperbarui profil. Silakan coba lagi.';
            }
        } catch(PDOException $exception) {
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

$page_title = 'Profil Saya';
include '../includes/header.php';
?>

<div class="profile-page">
    <div class="page-header">
        <h1>Profil Saya</h1>
        <a href="<?php echo APP_URL; ?>user/flights.php" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-placeholder">
                        <span><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></span>
                    </div>
                </div>
                <div class="profile-info">
                    <h2><?php echo sanitizeInput($user['full_name']); ?></h2>
                    <p><?php echo sanitizeInput($user['email']); ?></p>
                    <span class="badge badge-user">User</span>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-item">
                    <h3><?php echo getUserBookingCount($user['id']); ?></h3>
                    <p>Total Pesanan</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo getUserCompletedBookings($user['id']); ?></h3>
                    <p>Pesanan Selesai</p>
                </div>
                <div class="stat-item">
                    <h3><?php echo formatDate($user['created_at']); ?></h3>
                    <p>Bergabung</p>
                </div>
            </div>
        </div>

        <div class="edit-profile-card">
            <h3>Edit Profil</h3>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Profil berhasil diperbarui!
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap *</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo sanitizeInput($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo sanitizeInput($user['email']); ?>" readonly>
                    <small>Email tidak dapat diubah. Hubungi admin jika perlu mengubah email.</small>
                </div>

                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo sanitizeInput($user['phone']); ?>">
                </div>

                <div class="form-divider">
                    <h4>Ubah Password</h4>
                    <p>Kosongkan jika tidak ingin mengubah password</p>
                </div>

                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="<?php echo APP_URL; ?>user/flights.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper functions
function getUserBookingCount($userId) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch(PDOException $exception) {
        return 0;
    }
}

function getUserCompletedBookings($userId) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status = 'completed'");
        $stmt->bindParam(1, $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch(PDOException $exception) {
        return 0;
    }
}
?>

<style>
.profile-page {
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

.profile-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.profile-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.profile-avatar {
    flex-shrink: 0;
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
}

.profile-info h2 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.profile-info p {
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.stat-item {
    text-align: center;
}

.stat-item h3 {
    color: #2563eb;
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.stat-item p {
    color: #6b7280;
    font-size: 0.875rem;
}

.edit-profile-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.edit-profile-card h3 {
    color: #1f2937;
    margin-bottom: 1.5rem;
}

.form-divider {
    margin: 2rem 0;
    padding: 1rem 0;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}

.form-divider h4 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.form-divider p {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.badge-user {
    background-color: #dbeafe;
    color: #1e40af;
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
