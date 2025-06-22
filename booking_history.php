<?php
session_start();
include('db_connect.php');

// Cek apakah sudah login (periksa semua kemungkinan variabel session login)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['user'])) {
    // Redirect ke halaman login
    header("Location: login.php");
    exit();
}

// Ambil user_id dari salah satu variabel session yang tersedia
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? 0;

// Ambil email dari session atau dari database
if (!isset($_SESSION['email']) && $user_id > 0) {
    $get_email_query = "SELECT email FROM users WHERE id = ?";
    $stmt = $conn->prepare($get_email_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $_SESSION['email'] = $user_data['email'];
        }
    }
}

// Fungsi untuk mendapatkan riwayat booking dari semua tabel
function getBookingHistory($conn, $user_id) {
    $booking_history = [];
    
    // Ambil email user berdasarkan ID
    $user_email = "";
    if ($user_id > 0) {
        $email_query = "SELECT email FROM users WHERE id = ?";
        $stmt = $conn->prepare($email_query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $email_result = $stmt->get_result();
            if ($email_result && $email_result->num_rows > 0) {
                $user_data = $email_result->fetch_assoc();
                $user_email = $user_data['email'];
                // Simpan email di session jika belum ada
                if (!isset($_SESSION['email'])) {
                    $_SESSION['email'] = $user_email;
                }
            }
        }
    } 
    
    // Jika email masih kosong, coba ambil dari session
    if (empty($user_email) && isset($_SESSION['email'])) {
        $user_email = $_SESSION['email'];
    }
    
    // Jika masih belum ada email, cek booking dengan user_id saja
    if (empty($user_email) && $user_id > 0) {
        $user_email = 'dummy@example.com'; // Gunakan dummy email yang tidak akan cocok
    }
    
    // Cek struktur tabel fields (apakah menggunakan field_name atau name)
    $check_field_name = false;
    $check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
    if ($check_fields->num_rows > 0) {
        $check_column = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
        $check_field_name = $check_column->num_rows > 0;
    }
    
    $join_clause = "";
    $field_column = "";
    
    // Buat join clause berdasarkan struktur tabel yang ada
    if ($check_field_name) {
        $join_clause = "JOIN fields f ON b.field = f.field_name";
        $field_column = "f.price_per_hour";
    } else {
        // Coba alternatif jika kolom field_name tidak ada
        $check_name_column = $conn->query("SHOW COLUMNS FROM fields LIKE 'name'");
        if ($check_name_column->num_rows > 0) {
            $join_clause = "JOIN fields f ON b.field = f.name";
            $field_column = "f.price_per_hour";
        } else {
            // Jika tak ada join yang cocok, gunakan booking tanpa join
            $join_clause = "";
            $field_column = "0 as price_per_hour";
        }
    }
    
    // Periksa tabel badminton_booking
    $check_badminton = $conn->query("SHOW TABLES LIKE 'badminton_booking'");
    if ($check_badminton->num_rows > 0) {
        // Cek apakah kolom user_id ada di tabel badminton_booking
        $check_column = $conn->query("SHOW COLUMNS FROM badminton_booking LIKE 'user_id'");
        $has_user_id = $check_column->num_rows > 0;
        
        // Ambil booking dari tabel badminton_booking
        if ($has_user_id) {
            $badminton_query = "SELECT b.*, 'badminton' as field_type ";
            
            // Tambahkan join dan price_per_hour jika tabel fields dan join clause tersedia
            if (!empty($join_clause)) {
                $badminton_query .= ", $field_column ";
            } else {
                $badminton_query .= ", 0 as price_per_hour ";
            }
            
            $badminton_query .= " FROM badminton_booking b ";
            
            // Tambahkan join clause jika tersedia
            if (!empty($join_clause)) {
                $badminton_query .= " $join_clause ";
            }
            
            $badminton_query .= " WHERE b.customer_email = ? OR b.user_id = ?
                               ORDER BY b.booking_date DESC, b.booking_time DESC";
            
            $stmt = $conn->prepare($badminton_query);
            $stmt->bind_param("si", $user_email, $user_id);
        } else {
            $badminton_query = "SELECT b.*, 'badminton' as field_type ";
            
            // Tambahkan join dan price_per_hour jika tabel fields dan join clause tersedia
            if (!empty($join_clause)) {
                $badminton_query .= ", $field_column ";
            } else {
                $badminton_query .= ", 0 as price_per_hour ";
            }
            
            $badminton_query .= " FROM badminton_booking b ";
            
            // Tambahkan join clause jika tersedia
            if (!empty($join_clause)) {
                $badminton_query .= " $join_clause ";
            }
            
            $badminton_query .= " WHERE b.customer_email = ?
                               ORDER BY b.booking_date DESC, b.booking_time DESC";
            
            $stmt = $conn->prepare($badminton_query);
            $stmt->bind_param("s", $user_email);
        }
        
        $stmt->execute();
        $badminton_result = $stmt->get_result();
        
        if ($badminton_result && $badminton_result->num_rows > 0) {
            while ($row = $badminton_result->fetch_assoc()) {
                $booking_history[] = $row;
            }
        }
    }
    
    // Urutkan semua booking berdasarkan tanggal terbaru
    usort($booking_history, function($a, $b) {
        $date_a = strtotime($a['booking_date'] . ' ' . $a['booking_time']);
        $date_b = strtotime($b['booking_date'] . ' ' . $b['booking_time']);
        return $date_b - $date_a;
    });
    
    return $booking_history;
}

// Ambil riwayat booking
$booking_history = getBookingHistory($conn, $user_id);

if (empty($booking_history)) {
    // Kode ini menggantikan kondisi dengan show_debug
    // Jika tidak ada booking history, tampilkan pesan kosong
    // tanpa query tambahan untuk debugging
}

// Debug link sudah didefinisikan $show_debug di atas
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - SportField</title>
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
        
        .booking-list {
            margin-top: 2rem;
        }
        
        .booking-card {
            margin-bottom: 1.5rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .booking-header {
            background-color: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .booking-body {
            padding: 1.5rem;
        }
        
        .booking-footer {
            background-color: #f8f9fa;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-confirmed {
            background-color: #28a745;
            color: white;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 0;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="frontpage.php">
                <i class="fas fa-futbol me-2"></i>
                SportField
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontpage.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="booking_history.php">Riwayat Booking</a>
                    </li>
                </ul>
                <a href="logout.php" class="btn btn-outline-light btn-logout">
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
                    <h2 class="mb-1"><i class="fas fa-history me-2"></i> Riwayat Pemesanan</h2>
                    <p class="mb-0">Lihat semua pemesanan lapangan yang telah Anda lakukan.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Booking History -->
    <div class="container">
        <div class="booking-list">
            <?php if (isset($_SESSION['payment_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['payment_success']; unset($_SESSION['payment_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['booking_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($booking_history)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="far fa-calendar-times"></i>
                    </div>
                    <h4>Belum Ada Pemesanan</h4>
                    <p class="text-muted">Anda belum memiliki riwayat pemesanan lapangan. Mulai pesan lapangan sekarang!</p>

                </div>
            <?php else: ?>
                <?php foreach ($booking_history as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-capitalize"><?php echo $booking['field_type']; ?> - <?php echo $booking['field']; ?></span>
                                <small class="d-block text-muted">
                                    Booking ID: #<?php echo $booking['id']; ?>
                                </small>
                            </div>
                            <div>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($booking['status']) {
                                    case 'confirmed':
                                        $status_class = 'badge-confirmed';
                                        $status_text = 'Dikonfirmasi';
                                        break;
                                    case 'pending_confirmation':
                                        $status_class = 'badge-pending';
                                        $status_text = 'Menunggu Konfirmasi';
                                        break;
                                    case 'pending_payment':
                                        $status_class = 'badge-pending';
                                        $status_text = 'Menunggu Pembayaran';
                                        break;
                                    case 'rejected':
                                        $status_class = 'badge-rejected';
                                        $status_text = 'Ditolak';
                                        break;
                                    case 'booked':
                                        $status_class = 'badge-confirmed';
                                        $status_text = 'Terbooking';
                                        break;
                                    default:
                                        $status_class = 'badge-confirmed';
                                        $status_text = $booking['status'];
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                        </div>
                        <div class="booking-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-3">Detail Booking</h5>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <small class="text-muted">Tanggal & Waktu</small>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="fw-bold">
                                                <?php echo date('d F Y', strtotime($booking['booking_date'])); ?>, 
                                                <?php echo date('H:i', strtotime($booking['booking_time'])); ?> WIB
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <small class="text-muted">Nama Pemesan</small>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="fw-bold"><?php echo $booking['customer_name']; ?></div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <small class="text-muted">Kontak</small>
                                        </div>
                                        <div class="col-md-8">
                                            <div><?php echo $booking['customer_email']; ?></div>
                                            <div><?php echo $booking['customer_phone'] ?? '-'; ?></div>
                                        </div>
                                    </div>
                                    <?php if (isset($booking['payment_method']) && !empty($booking['payment_method'])): ?>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <small class="text-muted">Metode Pembayaran</small>
                                        </div>
                                        <div class="col-md-8">
                                            <div><?php echo $booking['payment_method']; ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (isset($booking['rejection_reason']) && !empty($booking['rejection_reason'])): ?>
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <small class="text-muted">Alasan Penolakan</small>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="text-danger"><?php echo $booking['rejection_reason']; ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="mb-3">Harga</h5>
                                    <div class="h4 text-success">
                                        Rp <?php echo number_format($booking['price_per_hour'] ?? 0, 0, ',', '.'); ?>
                                    </div>
                                    <small class="text-muted">Per jam</small>
                                    
                                    <?php if ($booking['status'] == 'pending_payment'): ?>
                                        <div class="mt-3">
                                            <a href="payment_transfer.php?id=<?php echo $booking['id']; ?>&type=<?php echo $booking['field_type']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-money-bill-wave me-2"></i> Bayar Sekarang
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="booking-footer text-end">
                            <?php if ($booking['status'] == 'confirmed'): ?>
                                <a href="#" class="btn btn-outline-success btn-sm" onclick="window.print()">
                                    <i class="fas fa-print me-2"></i> Cetak Bukti Booking
                                </a>
                            <?php endif; ?>
                            <?php if ($booking['status'] == 'pending_confirmation'): ?>
                                <span class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i> Pembayaran sedang diverifikasi oleh admin
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="py-4 bg-light mt-5">
        <div class="container text-center">
            <p class="mb-0">© 2023 SportField. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 