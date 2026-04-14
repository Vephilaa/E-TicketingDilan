<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(APP_URL . 'admin/dashboard.php');
    } else {
        redirect(APP_URL . 'user/flights.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitizeInput($_POST['full_name']);
    $nik = sanitizeInput($_POST['nik']);
    $phone = sanitizeInput($_POST['phone']);
    $role_id = 2;

    // Validation
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    }

    if (empty($email)) {
        $errors[] = 'Email harus diisi';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Format email tidak valid';
    }

    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }

    if (empty($full_name)) {
        $errors[] = 'Nama lengkap harus diisi';
    }

    if (empty($nik)) {
        $errors[] = 'NIK harus diisi';
    } elseif (!preg_match('/^\d{16}$/', $nik)) {
        $errors[] = 'NIK harus 16 digit angka';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Ensure database schema has nik column before using it in queries
            $nik_column_stmt = $db->query("SHOW COLUMNS FROM users LIKE 'nik'");
            if ($nik_column_stmt->rowCount() === 0) {
                $errors[] = 'Database belum mendukung kolom NIK. Jalankan update database terlebih dahulu.';
            }

            if (!empty($errors)) {
                throw new Exception('Schema users.nik belum tersedia');
            }

            // Check if username, email, or NIK already exists
            $check_query = "SELECT id FROM users WHERE username = ? OR email = ? OR nik = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $username);
            $check_stmt->bindParam(2, $email);
            $check_stmt->bindParam(3, $nik);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $errors[] = 'Username, email, atau NIK sudah digunakan';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insert_query = "INSERT INTO users (username, email, password, full_name, nik, phone, role_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(1, $username);
                $insert_stmt->bindParam(2, $email);
                $insert_stmt->bindParam(3, $hashed_password);
                $insert_stmt->bindParam(4, $full_name);
                $insert_stmt->bindParam(5, $nik);
                $insert_stmt->bindParam(6, $phone);
                $insert_stmt->bindParam(7, $role_id);

                if ($insert_stmt->execute()) {
                    setMessage('success', 'Pendaftaran berhasil! Silakan masuk ke akun Anda.');
                    redirect(APP_URL . 'auth/login.php');
                } else {
                    $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
                }
            }
        } catch(Exception $exception) {
            if (empty($errors)) {
                $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Daftar';
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Buat Akun Baru</h2>
            <p>Daftar untuk mulai memesan tiket penerbangan.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? sanitizeInput($_POST['username']) : ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? sanitizeInput($_POST['email']) : ''; ?>" 
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="full_name">Nama Lengkap *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo isset($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : ''; ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="nik">NIK *</label>
                <input type="text" id="nik" name="nik" maxlength="16" minlength="16" inputmode="numeric"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       placeholder="Masukkan 16 digit NIK"
                       value="<?php echo isset($_POST['nik']) ? sanitizeInput($_POST['nik']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="phone">Nomor Telepon</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Daftar</button>
        </form>

        <div class="auth-footer">
            <p>Sudah punya akun? <a href="<?php echo APP_URL; ?>auth/login.php">Masuk di sini</a></p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
