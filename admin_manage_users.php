<?php
session_start();
include('db_connect.php');

// Cek apakah user login sebagai admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    $_SESSION['error'] = "Anda harus login sebagai admin terlebih dahulu";
    header("Location: login.php");
    exit();
}

// Fungsi untuk sanitasi input
function clean($str) {
    global $conn;
    $str = trim($str);
    return mysqli_real_escape_string($conn, $str);
}

// Fungsi untuk memeriksa kolom dalam tabel users
function checkUserTableColumns($conn) {
    $result = $conn->query("SHOW COLUMNS FROM users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

// Fungsi untuk memeriksa dan membuat tabel users jika belum ada
function checkAndCreateUsersTable($conn) {
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows == 0) {
        $create_table = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table)) {
            // Tambahkan admin default jika tabel baru dibuat
            $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
            $insert_admin = "INSERT INTO users (name, email, password, status) 
                            VALUES ('Administrator', 'admin@sportfield.com', '$admin_password', 1)";
            $conn->query($insert_admin);
            
            return "Tabel users berhasil dibuat dengan admin default (email: admin@sportfield.com, password: admin123)";
        } else {
            return "Error membuat tabel users: " . $conn->error;
        }
    }
    return "";
}

// Periksa dan buat tabel users jika diperlukan
$table_message = checkAndCreateUsersTable($conn);
if (!empty($table_message)) {
    $_SESSION['success'] = $table_message;
}

// Periksa kolom-kolom yang ada di tabel users
$user_columns = checkUserTableColumns($conn);
$has_login_type = in_array('login_type', $user_columns);
$has_profile_image = in_array('profile_image', $user_columns);
$has_status = in_array('status', $user_columns);

// Tambahkan kolom login_type jika belum ada
if (!$has_login_type) {
    $conn->query("ALTER TABLE users ADD COLUMN login_type TINYINT(1) DEFAULT 0 COMMENT '0=customer, 1=admin'");
    $_SESSION['success'] = "Kolom login_type ditambahkan ke tabel users";
    $has_login_type = true;
}

// Tambahkan kolom status jika belum ada
if (!$has_status) {
    $conn->query("ALTER TABLE users ADD COLUMN status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active'");
    $_SESSION['success'] = "Kolom status ditambahkan ke tabel users";
    $has_status = true;
}

// Proses tambah atau edit user
if (isset($_POST['save_user'])) {
    $id = clean($_POST['id'] ?? '');
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : ''; // Tidak perlu escape untuk password
    $status = $has_status ? clean($_POST['status'] ?? '1') : '1';
    
    // Validasi data
    $errors = [];
    
    // Cek email sudah digunakan
    if (empty($id)) {
        $check_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = "Email sudah digunakan";
        }
    } else {
        $check_query = "SELECT * FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = "Email sudah digunakan";
        }
    }
    
    if (empty($errors)) {
        if (empty($id)) {
            // Tambah data baru
            if (empty($password)) {
                $errors[] = "Password tidak boleh kosong untuk user baru";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (name, email, password";
                $types = "sss";
                $params = array($name, $email, $hashed_password);
                
                if ($has_login_type) {
                    $query .= ", login_type";
                    $types .= "i";
                    $login_type_val = isset($_POST['login_type']) ? clean($_POST['login_type']) : '0';
                    $params[] = $login_type_val;
                }
                
                if ($has_status) {
                    $query .= ", status";
                    $types .= "i";
                    $params[] = $status;
                }
                
                $query .= ") VALUES (?" . str_repeat(", ?", count($params) - 1) . ")";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User berhasil ditambahkan";
                } else {
                    $_SESSION['error'] = "Error: " . $stmt->error;
                }
            }
        } else {
            // Update data yang ada
            $query_parts = [];
            $types = "";
            $params = [];
            
            // Selalu update nama dan email
            $query_parts[] = "name = ?";
            $query_parts[] = "email = ?";
            $types .= "ss";
            $params[] = $name;
            $params[] = $email;
            
            // Update password jika diisi
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_parts[] = "password = ?";
                $types .= "s";
                $params[] = $hashed_password;
            }
            
            // Update login_type jika kolom ada dan nilai diberikan
            if ($has_login_type && isset($_POST['login_type'])) {
                $query_parts[] = "login_type = ?";
                $types .= "i";
                $login_type_val = clean($_POST['login_type']);
                $params[] = $login_type_val;
            }
            
            // Update status jika kolom ada
            if ($has_status) {
                $query_parts[] = "status = ?";
                $types .= "i";
                $params[] = $status;
            }
            
            // Tambahkan ID ke parameter
            $types .= "i";
            $params[] = $id;
            
            $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User berhasil diupdate";
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
            }
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    header("Location: admin_manage_users.php");
    exit();
}

// Proses hapus user
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $id = clean($_GET['delete_id']);
    
    // Admin tidak bisa menghapus dirinya sendiri
    if ($_SESSION['id'] == $id) {
        $_SESSION['error'] = "Anda tidak dapat menghapus akun yang sedang login";
    } else {
        // Hapus data user (tanpa memeriksa gambar profil karena kolom mungkin tidak ada)
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "User berhasil dihapus";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
    }
    
    header("Location: admin_manage_users.php");
    exit();
}

// Ambil data untuk edit
$edit_data = [];
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $id = clean($_GET['edit_id']);
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Ambil semua data user
$users = [];
$query = "SELECT * FROM users ORDER BY id DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: #1e8449;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: white;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85);
        }
        
        .navbar-nav .nav-link:hover {
            color: white;
        }
        
        .navbar-nav .nav-link.active {
            color: white;
            font-weight: bold;
        }
        
        .btn-logout {
            color: white;
            border-color: white;
        }
        
        .header-banner {
            background-color: #2ecc71;
            color: white;
            padding: 2rem 0;
        }
        
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .user-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .badge-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-customer {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-futbol me-2"></i>
                SportField Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_manage_fields.php">Kelola Lapangan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_manage_users.php">Kelola Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_verify_payment.php">Verifikasi Pembayaran</a>
                    </li>
                </ul>
                <a href="adminlogin.php" class="btn btn-outline-light btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Header Banner -->
    <div class="header-banner">
        <div class="container">
            <div class="d-flex align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-users me-2"></i> Kelola Pengguna</h2>
                    <p class="mb-0">Tambah, edit atau hapus data pengguna sistem.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container my-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Form Tambah/Edit -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo (!empty($edit_data)) ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? ''; ?>">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_data['name'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $edit_data['email'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <?php echo (empty($edit_data)) ? '(Wajib)' : '(Kosongkan jika tidak ingin merubah)'; ?></label>
                                <input type="password" class="form-control" id="password" name="password" <?php echo (empty($edit_data)) ? 'required' : ''; ?>>
                            </div>
                            
                            <input type="hidden" name="status" value="<?php echo $edit_data['status'] ?? '1'; ?>">
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="save_user" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Simpan
                                </button>
                                <?php if (!empty($edit_data)): ?>
                                    <a href="admin_manage_users.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Batal
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Pengguna -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Daftar Pengguna</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info">
                                Belum ada data pengguna. Silakan tambahkan pengguna baru.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="15%">Foto</th>
                                            <th width="25%">Nama</th>
                                            <th width="25%">Email</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-user fa-2x"></i>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $user['name']; ?>
                                                    <?php if (isset($user['login_type']) && $user['login_type'] == 1): ?>
                                                        <span class="badge bg-danger">Admin</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td>
                                                    <a href="admin_manage_users.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($_SESSION['id'] != $user['id']): ?>
                                                        <a href="admin_manage_users.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="py-4 bg-light mt-5">
        <div class="container text-center">
            <p class="mb-0">© 2023 SportField Admin. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 