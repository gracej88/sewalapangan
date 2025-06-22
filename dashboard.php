<?php
// Start the PHP session (if needed)
session_start();
include('db_connect.php');

// Cek apakah user login sebagai admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    header("Location: adminlogin.php");
    exit();
}

// Fungsi untuk cek kolom tabel fields
function checkFieldColumns($conn) {
    $result = $conn->query("SHOW COLUMNS FROM fields");
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

// Cek apakah tabel fields ada, jika tidak buat tabel
$check_table = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS fields (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(20) NOT NULL,
        location VARCHAR(100),
        price_per_hour DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        // Masukkan data dummy
        $insert_fields = [
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Futsal A', 'futsal', 'Jakarta Selatan', 100000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Futsal B', 'futsal', 'Jakarta Selatan', 120000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Futsal C', 'futsal', 'Jakarta Selatan', 150000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Futsal D', 'futsal', 'Jakarta Selatan', 180000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Badminton 1', 'badminton', 'Jakarta Pusat', 80000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Badminton 2', 'badminton', 'Jakarta Pusat', 80000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Badminton 3', 'badminton', 'Jakarta Pusat', 80000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Badminton 4', 'badminton', 'Jakarta Pusat', 80000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Badminton 5', 'badminton', 'Jakarta Pusat', 80000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Badminton 6', 'badminton', 'Jakarta Pusat', 80000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Tennis Court', 'tennis', 'Jakarta Barat', 200000, 'available')",
            "INSERT INTO fields (name, type, location, price_per_hour, status) VALUES ('Lapangan Tennis 2', 'tennis', 'Jakarta Barat', 220000, 'available')"
        ];
        
        foreach ($insert_fields as $query) {
            $conn->query($query);
        }
    }
}

// Ambil data lapangan berdasarkan jenis
$field_columns = checkFieldColumns($conn);
$name_column = in_array('field_name', $field_columns) ? 'field_name' : 'name';
$type_column = in_array('field_type', $field_columns) ? 'field_type' : 'type';

// Ambil data lapangan futsal
$futsal_fields = [];
$query_futsal = "SELECT * FROM fields WHERE $type_column = 'futsal'";
$result_futsal = $conn->query($query_futsal);
if ($result_futsal && $result_futsal->num_rows > 0) {
    while ($row = $result_futsal->fetch_assoc()) {
        $futsal_fields[] = $row;
    }
}

// Ambil data lapangan badminton
$badminton_fields = [];
$query_badminton = "SELECT * FROM fields WHERE $type_column = 'badminton'";
$result_badminton = $conn->query($query_badminton);
if ($result_badminton && $result_badminton->num_rows > 0) {
    while ($row = $result_badminton->fetch_assoc()) {
        $badminton_fields[] = $row;
    }
}

// Ambil data lapangan tennis
$tennis_fields = [];
$query_tennis = "SELECT * FROM fields WHERE $type_column = 'tennis'";
$result_tennis = $conn->query($query_tennis);
if ($result_tennis && $result_tennis->num_rows > 0) {
    while ($row = $result_tennis->fetch_assoc()) {
        $tennis_fields[] = $row;
    }
}

// Hitung jumlah lapangan yang tersedia dan terpakai
$total_futsal = count($futsal_fields);
$available_futsal = 0;
$occupied_futsal = 0;

$total_badminton = count($badminton_fields);
$available_badminton = 0;
$occupied_badminton = 0;

$total_tennis = count($tennis_fields);
$available_tennis = 0;
$occupied_tennis = 0;

// Hitung status lapangan
foreach ($futsal_fields as $field) {
    if ($field['status'] == 'available') {
        $available_futsal++;
    } else {
        $occupied_futsal++;
    }
}

foreach ($badminton_fields as $field) {
    if ($field['status'] == 'available') {
        $available_badminton++;
    } else {
        $occupied_badminton++;
    }
}

foreach ($tennis_fields as $field) {
    if ($field['status'] == 'available') {
        $available_tennis++;
    } else {
        $occupied_tennis++;
    }
}

// Ambil booking terbaru
$booking_tables = ['futsal_booking', 'badminton_booking', 'tennis_booking'];
$recent_bookings = [];

// Cek apakah tabel booking ada
foreach ($booking_tables as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table->num_rows == 0) {
        $create_table = "CREATE TABLE IF NOT EXISTS $table (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11),
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            field VARCHAR(100) NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20),
            customer_email VARCHAR(100),
            team_name VARCHAR(100),
            notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            payment_method VARCHAR(50),
            payment_date DATETIME,
            payment_proof VARCHAR(255),
            payment_reference VARCHAR(100),
            payment_sender VARCHAR(100),
            payment_bank VARCHAR(50),
            rejection_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->query($create_table);
    }
}

// Cek kolom dalam tabel booking
$booking_columns = [];
foreach ($booking_tables as $table) {
    $check_columns = $conn->query("SHOW COLUMNS FROM $table");
    if ($check_columns) {
        $columns = [];
        while ($col = $check_columns->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        $booking_columns[$table] = $columns;
    }
}

// Query untuk mengambil booking terbaru
$today = date('Y-m-d');
$query_recent_bookings = "";

foreach ($booking_tables as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table->num_rows > 0) {
        if (!empty($query_recent_bookings)) {
            $query_recent_bookings .= " UNION ";
        }
        
        $query_recent_bookings .= "SELECT *, '$table' as table_source FROM $table WHERE booking_date >= '$today'";
    }
}

if (!empty($query_recent_bookings)) {
    $query_recent_bookings .= " ORDER BY booking_date ASC, booking_time ASC LIMIT 5";
    $result_recent_bookings = $conn->query($query_recent_bookings);
    
    if ($result_recent_bookings && $result_recent_bookings->num_rows > 0) {
        while ($row = $result_recent_bookings->fetch_assoc()) {
            $recent_bookings[] = $row;
        }
    }
} else {
    // Tidak ada tabel booking yang ditemukan atau query kosong
    $recent_bookings = [];
}

// Fungsi untuk cek apakah lapangan sedang digunakan pada saat ini
function isFieldCurrentlyOccupied($field_name, $bookings) {
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    foreach ($bookings as $booking) {
        if ($booking['field'] == $field_name && 
            $booking['booking_date'] == $current_date && 
            $booking['status'] == 'confirmed') {
            
            // Tambahkan 2 jam ke booking_time untuk mendapatkan waktu akhir (asumsi booking 2 jam)
            $booking_time = strtotime($booking['booking_time']);
            $end_time = date('H:i:s', strtotime('+2 hours', $booking_time));
            
            // Cek apakah waktu saat ini berada di antara waktu booking dan waktu akhir
            if ($current_time >= $booking['booking_time'] && $current_time <= $end_time) {
                return ['occupied' => true, 'end_time' => $end_time];
            }
        }
    }
    
    return ['occupied' => false];
}

// Fungsi untuk mendapatkan semua booking hari ini
function getTodaysBookings($conn) {
    $today = date('Y-m-d');
    $bookings = [];
    
    $booking_tables = ['futsal_booking', 'badminton_booking', 'tennis_booking'];
    
    foreach ($booking_tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            $query = "SELECT * FROM $table WHERE booking_date = '$today'";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $bookings[] = $row;
                }
            }
        }
    }
    
    return $bookings;
}

// Ambil booking hari ini
$todays_bookings = getTodaysBookings($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportField - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v5.15.4/css/all.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --primary-green: #1e8449;
            --secondary-green: #2ecc71;
            --light-green: #abebc6;
            --very-light-green: #e8f8f5;
            --dark-green: #186a3b;
            --white: #ffffff;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
        }
        
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: var(--primary-green);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            color: var(--white);
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green);
            font-weight: bold;
        }
        
        .main-content {
            margin-top: 90px;
            margin-left: 20px;
            margin-right: 20px;
            transition: margin-left 0.3s;
        }
        
        .btn-green {
            background: var(--primary-green);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: normal;
            transition: background 0.3s, box-shadow 0.3s;
        }
        
        .btn-green:hover {
            background: var(--dark-green);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .status-reserved {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #FFC107;
        }
        
        .status-ongoing {
            background-color: rgba(23, 162, 184, 0.1);
            border-left: 4px solid #17A2B8;
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28A745;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge i {
            margin-right: 4px;
        }
        
        .field-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            perspective: 1000px;
        }
        
        .field-slot {
            aspect-ratio: 16/9;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s ease;
            position: relative;
            transform-style: preserve-3d;
            border-radius: 10px;
        }
        
        .field-slot-available {
            background-color: #e6f3e6;
            border-color: var(--primary-green);
            color: var(--primary-green);
        }
        
        .field-slot-occupied {
            background-color: #f8d7da;
            border-color: #DC3545;
            color: #DC3545;
            cursor: not-allowed;
        }
        
        .field-slot-reserved {
            background-color: #fff3cd;
            border-color: #FFC107;
            color: #FFC107;
        }
        
        .field-slot-selected {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(0,123,255,0.5);
            border-color: #007BFF;
            z-index: 10;
        }
        
        .field-slot-label {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .field-info {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 0.8rem;
        }
        
        .text-green {
            color: var(--primary-green);
        }
        
        .bg-green {
            background-color: var(--primary-green);
        }
        
        .sport-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--very-light-green);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green);
            margin-right: 15px;
        }
        
        .chart-container {
            height: 200px;
            position: relative;
        }
        
        /* Style untuk tabel daftar lapangan */
        .table-fields tbody tr {
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 8px;
            border-radius: 5px;
        }
        
        .table-fields tbody tr td {
            padding: 15px;
            border-bottom: 4px solid #f9f9f9;
            background: white;
        }
        
        .table-fields thead th {
            background-color: #f2f7f2;
            padding: 12px 15px;
            border-bottom: 2px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="fixed-header">
        <div class="d-flex align-items-center">
            <div class="d-flex align-items-center">
                <div style="background: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--primary-green); font-weight: bold; border-radius: 5px; margin-right: 10px;">
                    <i class="fas fa-futbol"></i>
                </div>
                <span class="fw-bold fs-4">SportField Admin</span>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="profile-img me-2">A</div>
            <span class="fw-bold me-3">Admin</span>
            <a href="adminlogin.php" class="text-white" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fs-2 fw-bold text-gray-800">Dashboard Admin</h1>
                    <p class="text-muted">SportField Management System</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="admin_manage_fields.php" class="btn btn-green">
                        <i class="fas fa-futbol me-2"></i> Kelola Lapangan
                    </a>
                    <a href="admin_manage_users.php" class="btn btn-outline-success">
                        <i class="fas fa-users me-2"></i> Kelola Pengguna
                    </a>
                    <div class="position-relative">
                        <form action="search_results.php" method="GET">
                            <input type="text" name="search_query" placeholder="Cari ID booking, nama pelanggan, atau lapangan" class="form-control rounded-pill px-4 py-2" style="width: 300px;">
                            <button type="submit" class="btn bg-transparent border-0 position-absolute end-0 top-50 translate-middle-y me-3 text-muted">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>
            
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row g-4 mb-4">
    <!-- Lapangan Bulu Tangkis (Kiri) -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="sport-icon me-3">
                        <i class="fas fa-table-tennis fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5 class="mb-2 fw-bold">Lapangan Bulu Tangkis</h5>
                        <div class="d-flex gap-4">
                            <div>
                                <div class="text-muted">Total Lapangan</div>
                                <div class="fs-4 fw-bold"><?php echo $total_badminton; ?></div>
                            </div>
                            <div>
                                <div class="text-muted">Tersedia</div>
                                <div class="fs-4 fw-bold text-success"><?php echo $available_badminton; ?></div>
                            </div>
                            <div>
                                <div class="text-muted">Terpakai</div>
                                <div class="fs-4 fw-bold text-danger"><?php echo $occupied_badminton; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Terbaru (Kanan) -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i> Booking Terbaru</h5>
                <a href="#" class="text-decoration-none">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_bookings)): ?>
                    <div class="text-center py-4">
                        <div class="mb-3"><i class="fas fa-calendar-times fa-3x text-muted"></i></div>
                        <p class="text-muted">Belum ada booking terbaru</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): 
                        $booking_date = date('Y-m-d', strtotime($booking['booking_date']));
                        $is_today = ($booking_date == date('Y-m-d'));
                        $status_badge = '';
                        switch($booking['status']) {
                            case 'pending':
                                $status_badge = '<span class="badge bg-warning">DP 50%</span>';
                                break;
                            case 'confirmed':
                                $status_badge = '<span class="badge bg-success">Lunas</span>';
                                break;
                            case 'cancelled':
                                $status_badge = '<span class="badge bg-danger">Dibatalkan</span>';
                                break;
                            default:
                                $status_badge = '<span class="badge bg-secondary">Pending</span>';
                        }
                    ?>
                    <div class="booking-item d-flex mb-3 pb-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <div class="fw-bold"><?php echo $booking['customer_name']; ?></div>
                                <div><?php echo $is_today ? 'Hari ini' : date('d M Y', strtotime($booking['booking_date'])); ?></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <div class="text-muted"><?php echo $booking['field']; ?> • <?php echo substr($booking['booking_time'], 0, 5); ?> - <?php echo date('H:i', strtotime($booking['booking_time'] . ' + 2 hours')); ?></div>
                                <div><?php echo $status_badge; ?></div>
                            </div>
                            <?php if ($booking['status'] == 'pending'): ?>
                                <div class="mt-2">
                                    <a href="verify_booking.php?id=<?php echo $booking['id']; ?>&table=<?php echo $booking['table_source']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-check me-1"></i> Verifikasi
                                    </a>
                                    <a href="reject_booking.php?id=<?php echo $booking['id']; ?>&table=<?php echo $booking['table_source']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times me-1"></i> Tolak
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
            
            <!-- Fields Management Section -->
            <div class="row mb-4 mt-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold text-green">
                                <i class="fas fa-th-large me-2"></i> Daftar Lapangan
                            </h5>
                            <a href="admin_manage_fields.php" class="btn btn-sm btn-green">
                                <i class="fas fa-plus me-1"></i> Tambah Lapangan Baru
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-fields">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Lapangan</th>
                                            <th>Jenis</th>
                                            <th>Lokasi</th>
                                            <th>Harga/Jam</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Periksa apakah tabel fields ada
                                        $check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
                                        if ($check_fields->num_rows > 0) {
                                            // Cek apakah menggunakan kolom field_name atau name
                                            $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
                                            $field_col = ($check_field_name->num_rows > 0) ? 'field_name' : 'name';
                                            
                                            // Cek apakah menggunakan kolom field_type atau type
                                            $check_field_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_type'");
                                            $type_col = ($check_field_type->num_rows > 0) ? 'field_type' : 'type';
                                            
                                            // Ambil data lapangan
                                            $fields_query = "SELECT * FROM fields ORDER BY id DESC LIMIT 6";
                                            $fields_result = $conn->query($fields_query);
                                            
                                            if ($fields_result && $fields_result->num_rows > 0) {
                                                while ($field = $fields_result->fetch_assoc()) {
                                                    echo '<tr>';
                                                    echo '<td>' . $field['id'] . '</td>';
                                                    echo '<td>' . $field[$field_col] . '</td>';
                                                    echo '<td class="text-capitalize">' . $field[$type_col] . '</td>';
                                                    echo '<td>' . ($field['location'] ?? '-') . '</td>';
                                                    echo '<td>Rp ' . number_format($field['price_per_hour'], 0, ',', '.') . '</td>';
                                                    echo '<td>';
                                                    if ($field['status'] == 'available') {
                                                        echo '<span class="badge bg-success">Tersedia</span>';
                                                    } else {
                                                        echo '<span class="badge bg-warning text-dark">Pemeliharaan</span>';
                                                    }
                                                    echo '</td>';
                                                    echo '<td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="admin_manage_fields.php?edit_id=' . $field['id'] . '" class="btn btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-success" onclick="editPrice(' . $field['id'] . ', \'' . $field[$field_col] . '\', ' . $field['price_per_hour'] . ')">
                                                                <i class="fas fa-tags"></i>
                                                            </button>
                                                        </div>
                                                    </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="7" class="text-center py-4">Belum ada data lapangan</td></tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="7" class="text-center py-4">Tabel fields belum dibuat. <a href="update_database.php">Klik disini</a> untuk update database.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                            // Periksa jumlah total lapangan
                            if (isset($fields_result) && $fields_result->num_rows > 6) {
                                echo '<div class="text-center mt-3">
                                    <a href="admin_manage_fields.php" class="btn btn-outline-primary btn-sm">
                                        Lihat Semua Lapangan
                                    </a>
                                </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Price Update Modal -->
            <div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="priceModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-green text-white">
                            <h5 class="modal-title" id="priceModalLabel">Edit Harga Lapangan</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="priceForm" action="update_field_price.php" method="post">
                                <input type="hidden" id="field_id" name="field_id">
                                <div class="mb-3">
                                    <label for="field_name" class="form-label">Nama Lapangan</label>
                                    <input type="text" class="form-control" id="field_name" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="price_per_hour" class="form-label">Harga Per Jam (Rp)</label>
                                    <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" required>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-green">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simplified Chart.js code for field usage chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('fieldUsageChart').getContext('2d');
            const fieldUsageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Penggunaan Lapangan',
                        data: [12, 19, 3, 5, 2, 3, 7],
                        backgroundColor: 'rgba(30, 132, 73, 0.2)',
                        borderColor: 'rgba(30, 132, 73, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

        function showField(field) {
            // Sembunyikan semua layout lapangan
            document.getElementById('fieldLayout').innerHTML = '';
            
            let title = '';

            switch (field) {
                case 'Futsal':
                    title = 'Layout Lapangan - Futsal';
                    // Panggil fungsi untuk menampilkan layout lapangan futsal
                    displayFutsalLayout();
                    break;
                case 'Badminton':
                    title = 'Layout Lapangan - Bulu Tangkis';
                    // Panggil fungsi untuk menampilkan layout lapangan badminton
                    displayBadmintonLayout();
                    break;
                case 'Tenis':
                    title = 'Layout Lapangan - Tenis';
                    // Panggil fungsi untuk menampilkan layout lapangan tennis
                    displayTennisLayout();
                    break;
                default:
                    title = 'Pilih lapangan untuk melihat informasi';
            }
            
            document.getElementById('fieldTitle').innerHTML = `<i class="fas fa-map-marker-alt me-2 text-green"></i>${title}`;
        }

        // Fungsi untuk menampilkan layout lapangan futsal
        function displayFutsalLayout() {
            const layout = document.getElementById('fieldLayout');
            
            // Ambil data lapangan futsal dari PHP
            fetch('get_field_data.php?type=futsal')
                .then(response => response.json())
                .then(data => {
                    // Buat elemen lapangan futsal berdasarkan data
                    let html = '';
                    data.forEach((field, index) => {
                        const fieldStatus = field.status === 'available' ? 'field-slot-available' : 
                                           (field.status === 'maintenance' ? 'field-slot-occupied' : 'field-slot-reserved');
                        const statusText = field.status === 'available' ? 'Tersedia' : 
                                          (field.status === 'maintenance' ? 'Pemeliharaan' : 'Reservasi');
                        
                        html += `
                            <div class="field-slot ${fieldStatus}" data-field="${field.name}">
                                <span class="field-slot-label">Futsal ${String(index + 1).padStart(2, '0')}</span>
                            <span class="position-absolute top-50 start-50 translate-middle fs-3">
                                <i class="fas fa-futbol"></i>
                            </span>
                                <span class="field-info">${statusText}</span>
                        </div>
                    `;
                    });
                    
                    layout.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    layout.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data lapangan</div>';
                });
        }

        // Fungsi untuk menampilkan layout lapangan badminton
        function displayBadmintonLayout() {
            const layout = document.getElementById('fieldLayout');
            
            // Ambil data lapangan badminton dari PHP
            fetch('get_field_data.php?type=badminton')
                .then(response => response.json())
                .then(data => {
                    // Buat elemen lapangan badminton berdasarkan data
                    let html = '';
                    data.forEach((field, index) => {
                        const fieldStatus = field.status === 'available' ? 'field-slot-available' : 
                                           (field.status === 'maintenance' ? 'field-slot-occupied' : 'field-slot-reserved');
                        const statusText = field.status === 'available' ? 'Tersedia' : 
                                          (field.status === 'maintenance' ? 'Pemeliharaan' : 'Reservasi');
                        
                        html += `
                            <div class="field-slot ${fieldStatus}" data-field="${field.name}">
                                <span class="field-slot-label">Badminton ${String(index + 1).padStart(2, '0')}</span>
                            <span class="position-absolute top-50 start-50 translate-middle fs-3">
                                    <i class="fas fa-table-tennis"></i>
                            </span>
                                <span class="field-info">${statusText}</span>
                        </div>
                    `;
                    });
                    
                    layout.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    layout.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data lapangan</div>';
                });
        }

        // Fungsi untuk menampilkan layout lapangan tennis
        function displayTennisLayout() {
            const layout = document.getElementById('fieldLayout');
            
            // Ambil data lapangan tennis dari PHP
            fetch('get_field_data.php?type=tennis')
                .then(response => response.json())
                .then(data => {
                    // Buat elemen lapangan tennis berdasarkan data
                    let html = '';
                    data.forEach((field, index) => {
                        const fieldStatus = field.status === 'available' ? 'field-slot-available' : 
                                           (field.status === 'maintenance' ? 'field-slot-occupied' : 'field-slot-reserved');
                        const statusText = field.status === 'available' ? 'Tersedia' : 
                                          (field.status === 'maintenance' ? 'Pemeliharaan' : 'Reservasi');
                        
                        html += `
                            <div class="field-slot ${fieldStatus}" data-field="${field.name}">
                                <span class="field-slot-label">Tennis ${String(index + 1).padStart(2, '0')}</span>
                            <span class="position-absolute top-50 start-50 translate-middle fs-3">
                                <i class="fas fa-baseball-ball"></i>
                            </span>
                                <span class="field-info">${statusText}</span>
                        </div>
                    `;
                    });
                    
                    layout.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    layout.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data lapangan</div>';
                });
        }

        // Fungsi untuk menampilkan modal edit harga
        function editPrice(fieldId, fieldName, price) {
            document.getElementById('field_id').value = fieldId;
            document.getElementById('field_name').value = fieldName;
            document.getElementById('price_per_hour').value = price;
            
            // Tampilkan modal
            var priceModal = new bootstrap.Modal(document.getElementById('priceModal'));
            priceModal.show();
        }
    </script>
</body>
</html>