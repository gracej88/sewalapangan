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

// Proses tambah atau edit field
if (isset($_POST['save_field'])) {
    $id = clean($_POST['id'] ?? '');
    $field_name = clean($_POST['field_name']);
    $field_type = clean($_POST['field_type']);
    $price_per_hour = clean($_POST['price_per_hour']);
    $status = clean($_POST['status'] ?? 'available');
    
    // Cek apakah tabel menggunakan field_name atau name
    $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    if ($check_field_name->num_rows > 0) {
        $field_col = 'field_name';
    } else {
        $field_col = 'name';
    }
    
    // Cek apakah tabel menggunakan field_type atau type
    $check_field_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_type'");
    if ($check_field_type->num_rows > 0) {
        $type_col = 'field_type';
    } else {
        $type_col = 'type';
    }
    
    // Cek apakah ada kolom status di tabel fields
    $check_status = $conn->query("SHOW COLUMNS FROM fields LIKE 'status'");
    if ($check_status->num_rows == 0) {
        // Tambahkan kolom status jika belum ada
        $conn->query("ALTER TABLE fields ADD COLUMN status VARCHAR(20) DEFAULT 'available' AFTER price_per_hour");
    }
    
    if (empty($id)) {
        // Tambah data baru
        $query = "INSERT INTO fields ($field_col, $type_col, price_per_hour, status) ";
        $values = "VALUES (?, ?, ?, ?)";
        $types = "ssds";
        $params = array($field_name, $field_type, $price_per_hour, $status);
        
        $query .= $values;
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Lapangan berhasil ditambahkan";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
    } else {
        // Update data yang ada
        $query = "UPDATE fields SET $field_col = ?, $type_col = ?, price_per_hour = ?, status = ? WHERE id = ?";
        $types = "ssdsi";
        $params = array($field_name, $field_type, $price_per_hour, $status, $id);
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Lapangan berhasil diupdate";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
    }
    
    header("Location: admin_manage_fields.php");
    exit();
}

// Proses hapus field
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $id = clean($_GET['delete_id']);
    
    // Cek apakah lapangan sedang digunakan untuk booking
    $tables = ['badminton_booking'];
    $in_use = false;
    
    foreach ($tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            // Ambil nama field dari lapangan yang akan dihapus
            $field_name_query = "SELECT * FROM fields WHERE id = ?";
            $stmt = $conn->prepare($field_name_query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $field_result = $stmt->get_result();
            
            if ($field_result && $field_result->num_rows > 0) {
                $field_data = $field_result->fetch_assoc();
                
                // Gunakan field_name atau name tergantung struktur tabel
                $field_name = isset($field_data['field_name']) ? $field_data['field_name'] : $field_data['name'];
                
                // Cek apakah sedang digunakan untuk booking
                $check_usage = $conn->query("SELECT COUNT(*) as count FROM $table WHERE field = '$field_name'");
                $usage_result = $check_usage->fetch_assoc();
                
                if ($usage_result['count'] > 0) {
                    $in_use = true;
                    break;
                }
            }
        }
    }
    
    if ($in_use) {
        $_SESSION['error'] = "Lapangan tidak dapat dihapus karena sedang digunakan untuk booking";
    } else {
        // Hapus gambar jika ada
        $get_image = $conn->query("SELECT image FROM fields WHERE id = $id");
        if ($get_image->num_rows > 0) {
            $image_data = $get_image->fetch_assoc();
            if (!empty($image_data['image']) && file_exists($image_data['image'])) {
                unlink($image_data['image']);
            }
        }
        
        // Hapus data lapangan
        $delete_query = "DELETE FROM fields WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Lapangan berhasil dihapus";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }
    }
    
    header("Location: admin_manage_fields.php");
    exit();
}

// Ambil data untuk edit
$edit_data = [];
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $id = clean($_GET['edit_id']);
    $query = "SELECT * FROM fields WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
}

// Ambil semua data lapangan
$fields = [];
$query = "SELECT * FROM fields ORDER BY id DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fields[] = $row;
    }
}

// Cek kolom tabel fields untuk form
$check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
$field_col = ($check_field_name->num_rows > 0) ? 'field_name' : 'name';

$check_field_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_type'");
$type_col = ($check_field_type->num_rows > 0) ? 'field_type' : 'type';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan - Admin SportField</title>
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
        
        .field-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
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
                        <a class="nav-link active" href="admin_manage_fields.php">Kelola Lapangan</a>
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
                    <h2 class="mb-1"><i class="fas fa-th me-2"></i> Kelola Lapangan</h2>
                    <p class="mb-0">Tambah, edit atau hapus data lapangan olahraga.</p>
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
                        <h5 class="mb-0"><?php echo (!empty($edit_data)) ? 'Edit Lapangan' : 'Tambah Lapangan Baru'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? ''; ?>">
                            
                            <div class="mb-3">
                                <label for="field_name" class="form-label">Nama Lapangan</label>
                                <input type="text" class="form-control" id="field_name" name="field_name" value="<?php echo isset($edit_data[$field_col]) ? $edit_data[$field_col] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="field_type" class="form-label">Jenis Lapangan</label>
                                <select class="form-select" id="field_type" name="field_type" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="badminton" <?php echo (isset($edit_data[$type_col]) && $edit_data[$type_col] == 'badminton') ? 'selected' : ''; ?>>Badminton</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_per_hour" class="form-label">Harga Per Jam (Rp)</label>
                                <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" value="<?php echo $edit_data['price_per_hour'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available" <?php echo (isset($edit_data['status']) && $edit_data['status'] == 'available') ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="maintenance" <?php echo (isset($edit_data['status']) && $edit_data['status'] == 'maintenance') ? 'selected' : ''; ?>>Pemeliharaan</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="save_field" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Simpan
                                </button>
                                <?php if (!empty($edit_data)): ?>
                                    <a href="admin_manage_fields.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Batal
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Lapangan -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Daftar Lapangan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($fields)): ?>
                            <div class="alert alert-info">
                                Belum ada data lapangan. Silakan tambahkan lapangan baru.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">ID</th>
                                            <th width="15%">Gambar</th>
                                            <th width="20%">Nama Lapangan</th>
                                            <th width="15%">Jenis</th>
                                            <th width="15%">Harga/Jam</th>
                                            <th width="15%">Status</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fields as $field): ?>
                                            <tr>
                                                <td><?php echo $field['id']; ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">No Image</span>
                                                </td>
                                                <td>
                                                    <?php echo isset($field[$field_col]) ? $field[$field_col] : $field['name']; ?>
                                                    <?php if (isset($field['status']) && $field['status'] == 'maintenance'): ?>
                                                        <span class="badge bg-warning text-dark">Pemeliharaan</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-capitalize">
                                                    <?php echo isset($field[$type_col]) ? $field[$type_col] : $field['type']; ?>
                                                </td>
                                                <td>Rp <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php if (isset($field['status'])): ?>
                                                        <?php if ($field['status'] == 'available'): ?>
                                                            <span class="badge bg-success">Tersedia</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Pemeliharaan</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Tersedia</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="admin_manage_fields.php?edit_id=<?php echo $field['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="admin_manage_fields.php?delete_id=<?php echo $field['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus lapangan ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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