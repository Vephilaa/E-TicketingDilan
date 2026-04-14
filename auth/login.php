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
    $password = $_POST['password'];

    // Validation
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    }
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "SELECT u.*, r.name as role_name 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.id 
                     WHERE u.username = ? OR u.email = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $username);
            $stmt->bindParam(2, $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $row['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['nik'] = $row['nik'];
                    $_SESSION['role'] = $row['role_name'];

                    setMessage('success', 'Selamat datang, ' . $row['full_name'] . '!');

                    // Redirect based on role
                    if ($row['role_name'] === 'admin') {
                        redirect(APP_URL . 'admin/dashboard.php');
                    } else {
                        redirect(APP_URL . 'user/flights.php');
                    }
                } else {
                    $errors[] = 'Password salah';
                }
            } else {
                $errors[] = 'Username atau email tidak ditemukan';
            }
        } catch(PDOException $exception) {
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

$page_title = 'Masuk';
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Masuk ke Akun Anda</h2>
            <p>Silakan masuk untuk melanjutkan</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username atau Email</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? sanitizeInput($_POST['username']) : ''; ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Masuk</button>
        </form>

        <div class="auth-footer">
            <p>Belum punya akun? <a href="<?php echo APP_URL; ?>auth/register.php">Daftar di sini</a></p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
