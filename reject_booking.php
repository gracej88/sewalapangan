<?php
session_start();
include('db_connect.php');

// Cek apakah user login sebagai admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    $_SESSION['error'] = "Anda harus login sebagai admin terlebih dahulu";
    header("Location: login.php");
    exit();
}

// Ambil ID dan tabel booking dari parameter URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$table_name = isset($_GET['table']) ? $_GET['table'] : '';

// Validasi tabel booking
$valid_tables = ['badminton_booking'];
if (!in_array($table_name, $valid_tables)) {
    $_SESSION['error'] = "Tabel booking tidak valid";
    header("Location: dashboard.php");
    exit();
}

// Cek apakah booking ada
$query = "SELECT * FROM $table_name WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Booking tidak ditemukan";
    header("Location: dashboard.php");
    exit();
}

// Ambil data booking
$booking = $result->fetch_assoc();

// Cek apakah booking masih dalam status pending
if ($booking['status'] != 'pending') {
    $_SESSION['error'] = "Booking ini sudah diverifikasi atau dibatalkan";
    header("Location: dashboard.php");
    exit();
}

// Proses form alasan penolakan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil alasan penolakan
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    // Update status booking menjadi cancelled
    $update_query = "UPDATE $table_name SET status = 'cancelled', rejection_reason = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $rejection_reason, $booking_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Booking berhasil ditolak";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal menolak booking: " . $conn->error;
    }
}

// Tampilkan form untuk alasan penolakan
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tolak Booking - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #1e8449;
            border-color: #1e8449;
        }
        .btn-primary:hover {
            background-color: #186a3b;
            border-color: #186a3b;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Tolak Booking</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6 class="fw-bold">Detail Booking:</h6>
                            <p class="mb-1">Pelanggan: <?php echo $booking['customer_name']; ?></p>
                            <p class="mb-1">Lapangan: <?php echo $booking['field']; ?></p>
                            <p class="mb-1">Tanggal: <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></p>
                            <p>Waktu: <?php echo substr($booking['booking_time'], 0, 5); ?> WIB</p>
                        </div>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="rejection_reason" class="form-label">Alasan Penolakan:</label>
                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                                <div class="form-text">Berikan alasan mengapa booking ini ditolak.</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times me-1"></i> Tolak Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 