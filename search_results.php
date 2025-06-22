<?php
session_start();
include 'db_connect.php';

// Cek apakah user login sebagai admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    header("Location: adminlogin.php");
    exit();
}

// Ambil query pencarian
$search_query = '';
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search_query']);
}

// Siapkan array untuk menyimpan hasil pencarian
$search_results = [];

// Jika query tidak kosong, lakukan pencarian
if (!empty($search_query)) {
    // Inisialisasi variabel hasil
    $bookings_result = false;
    $fields_result = false;
    
    // Pencarian di tabel booking (cek setiap tabel booking)
    $booking_tables = ['badminton_booking'];
    $combined_bookings_query = "";
    
    foreach ($booking_tables as $table) {
        // Cek apakah tabel ada
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($check_table) > 0) {
            if (!empty($combined_bookings_query)) {
                $combined_bookings_query .= " UNION ";
            }
            
            // Sesuaikan kolom dengan struktur tabel yang ada
            $combined_bookings_query .= "SELECT *, '$table' as table_source FROM $table 
                                         WHERE customer_name LIKE '%$search_query%' 
                                         OR field LIKE '%$search_query%'";
        }
    }
    
    if (!empty($combined_bookings_query)) {
        $combined_bookings_query .= " ORDER BY booking_date DESC, booking_time ASC";
        $bookings_result = mysqli_query($conn, $combined_bookings_query);
    }
    
    // Pencarian di tabel fields (berdasarkan field_name atau name)
    $field_columns = mysqli_query($conn, "SHOW COLUMNS FROM fields");
    $field_columns_array = [];
    while ($column = mysqli_fetch_assoc($field_columns)) {
        $field_columns_array[] = $column['Field'];
    }
    
    $field_name_column = in_array('field_name', $field_columns_array) ? 'field_name' : 'name';
    $field_type_column = in_array('field_type', $field_columns_array) ? 'field_type' : 'type';
    
    $fields_query = "SELECT * FROM fields WHERE $field_name_column LIKE '%$search_query%' ORDER BY $field_type_column, $field_name_column";
    $fields_result = mysqli_query($conn, $fields_query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - SportField Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-green: #28a745;
            --dark-green: #218838;
            --light-green: #48c774;
            --very-light-green: #ebf9f0;
            --white: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px;
        }
        
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--primary-green);
            padding: 0 20px;
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
            margin-top: 20px;
            margin-left: 20px;
            margin-right: 20px;
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
        
        .text-green {
            color: var(--primary-green);
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
            <a href="logout.php" class="text-white" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fs-3 fw-bold text-gray-800">Hasil Pencarian</h1>
                    <p class="text-muted">Kata kunci: "<?php echo htmlspecialchars($search_query); ?>"</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                    </a>
                </div>
            </header>
            
            <?php if (empty($search_query)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Silakan masukkan kata kunci pencarian.
                </div>
            <?php elseif (!$bookings_result || (is_object($bookings_result) && mysqli_num_rows($bookings_result) == 0) && 
                          (!$fields_result || (is_object($fields_result) && mysqli_num_rows($fields_result) == 0))): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Tidak ditemukan hasil untuk pencarian "<?php echo htmlspecialchars($search_query); ?>".
                </div>
            <?php else: ?>
                
                <!-- Hasil Pencarian Booking -->
                <?php if ($bookings_result && mysqli_num_rows($bookings_result) > 0): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-check me-2 text-green"></i> Pemesanan</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th scope="col">Jenis</th>
                                            <th scope="col">Nama Pelanggan</th>
                                            <th scope="col">Lapangan</th>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col">Waktu</th>
                                            <th scope="col">Status</th>
                                            <!-- <th scope="col">Aksi</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($bookings_result)): 
                                            $booking_type = str_replace('_booking', '', $booking['table_source']);
                                            $booking_type = ucfirst($booking_type);
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking_type); ?></td>
                                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['field']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($booking['booking_time'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $booking['status'];
                                                    $status_class = '';
                                                    $status_text = '';
                                                    
                                                    switch ($status) {
                                                        case 'pending':
                                                            $status_class = 'bg-warning text-dark';
                                                            $status_text = 'Menunggu';
                                                            break;
                                                        case 'confirmed':
                                                            $status_class = 'bg-success text-white';
                                                            $status_text = 'Dikonfirmasi';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'bg-primary text-white';
                                                            $status_text = 'Selesai';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'bg-danger text-white';
                                                            $status_text = 'Dibatalkan';
                                                            break;
                                                        default:
                                                            $status_class = 'bg-secondary text-white';
                                                            $status_text = $status;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <!-- <td>
                                                    <a href="booking_detail.php?id=<?php echo $booking['id']; ?>&type=<?php echo $booking_type; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td> -->
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Hasil Pencarian Lapangan -->
                <?php if ($fields_result && mysqli_num_rows($fields_result) > 0): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-futbol me-2 text-green"></i> Lapangan</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th scope="col">Nama Lapangan</th>
                                            <th scope="col">Tipe</th>
                                            <th scope="col">Harga per Jam</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($field = mysqli_fetch_assoc($fields_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($field['field_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $field_type = $field['field_type'];
                                                    $icon = '';
                                                    switch ($field_type) {
                                                        case 'Badminton':
                                                            $icon = 'fa-table-tennis';
                                                            break;
                                                        default:
                                                            $icon = 'fa-volleyball-ball';
                                                    }
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?> me-1 text-green"></i>
                                                    <?php echo htmlspecialchars($field_type); ?>
                                                </td>
                                                <td>Rp <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $field['status'];
                                                    $status_class = '';
                                                    $status_text = '';
                                                    
                                                    switch ($status) {
                                                        case 'available':
                                                            $status_class = 'bg-success text-white';
                                                            $status_text = 'Tersedia';
                                                            break;
                                                        case 'maintenance':
                                                            $status_class = 'bg-warning text-dark';
                                                            $status_text = 'Pemeliharaan';
                                                            break;
                                                        case 'closed':
                                                            $status_class = 'bg-danger text-white';
                                                            $status_text = 'Tutup';
                                                            break;
                                                        default:
                                                            $status_class = 'bg-secondary text-white';
                                                            $status_text = $status;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td>
                                                    <a href="field_detail.php?id=<?php echo $field['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 