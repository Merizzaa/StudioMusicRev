<?php
require_once 'includes/config.php';

// TAMBAHKAN: Cek parameter registered
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = "Registrasi berhasil! Silakan login dengan akun Anda.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    
    // Cek user di database - bisa dengan username atau email
    $sql = "SELECT id, username, password, role, email FROM users WHERE username = ? OR email = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $login, $login);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $email);
                
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        // Password benar, buat session
                        session_start();
                        
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $role;
                        
                        // Redirect berdasarkan role
                        switch ($role) {
                            case 'admin':
                                header("Location: admin/index.php");
                                break;
                            case 'staff':
                                header("Location: staff/index.php");
                                break;
                            case 'customer':
                                header("Location: customer/index.php");
                                break;
                            default:
                                header("Location: index.php");
                        }
                        exit();
                    } else {
                        $error = "Password salah.";
                    }
                }
            } else {
                $error = "Username atau email tidak ditemukan.";
            }
        } else {
            $error = "Terjadi kesalahan. Silakan coba lagi.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>

<?php include 'includes/header.php'; ?>
<div class="login-container">
    <div class="login-form">
        <h2>Login</h2>
        
        <!-- TAMBAHKAN: Notifikasi registrasi berhasil -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="login">Username atau Email</label>
                <input type="text" id="login" name="login" required 
                       placeholder="Masukkan username atau email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Masukkan password">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>