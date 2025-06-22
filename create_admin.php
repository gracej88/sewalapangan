<?php
session_start();
include('db_connect.php');

// Cek apakah tabel users sudah ada
$check_users = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users->num_rows == 0) {
    // Buat tabel users jika belum ada
    $create_users = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        login_type TINYINT NOT NULL DEFAULT 0 COMMENT '0: user, 1: admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($create_users);
}

$success_message = '';
$error_message = '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua field harus diisi";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password tidak cocok";
    } else {
        // Cek apakah email sudah digunakan
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email sudah digunakan";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set nilai login_type = 1 untuk admin
            $login_type = 1;
            
            // Simpan admin baru
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, login_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $email, $hashed_password, $login_type);
            
            if ($stmt->execute()) {
                $success_message = "Admin berhasil dibuat! Sekarang Anda dapat login dengan email dan password tersebut.";
            } else {
                $error_message = "Gagal membuat admin: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Admin Baru - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-create-admin {
            max-width: 500px;
            padding: 15px;
            margin: 0 auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #1e8449;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #1e8449;
            border-color: #1e8449;
        }
        .btn-primary:hover {
            background-color: #166e39;
            border-color: #166e39;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-create-admin">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center mb-0">Buat Admin Baru</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                        <p class="text-center">
                            <a href="adminlogin.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Login sebagai Admin
                            </a>
                        </p>
                    <?php else: ?>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Buat Admin</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="index.php" class="text-decoration-none">Kembali ke Halaman Utama</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 