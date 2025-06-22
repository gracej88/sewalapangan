<?php
session_start();
include('db_connect.php');

$booking_id = $_GET['id'] ?? '';
$booking_type = $_GET['type'] ?? '';

// Validasi data
if (empty($booking_id) || empty($booking_type)) {
    $_SESSION['error'] = "Data booking tidak lengkap";
    header("Location: frontpage.php");
    exit();
}

// Validasi tipe booking
$valid_types = ['badminton'];
if (!in_array($booking_type, $valid_types)) {
    $_SESSION['error'] = "Tipe booking tidak valid";
    header("Location: frontpage.php");
    exit();
}

// Tentukan tabel berdasarkan tipe booking
$booking_table = $booking_type . '_booking';

// Ambil data booking
$query = "SELECT * FROM $booking_table WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Data booking tidak ditemukan";
    header("Location: frontpage.php");
    exit();
}

$booking_data = $result->fetch_assoc();

// Format tanggal dan waktu
$booking_date = date('j F Y', strtotime($booking_data['booking_date']));
$booking_time = date('H:i', strtotime($booking_data['booking_time'])) . " - " . date('H:i', strtotime($booking_data['booking_time'] . ' +1 hour'));

// Format harga
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Jika booking_type adalah 'futsal', gunakan kolom field
// Jika bukan, gunakan fieldChoice
$field_name = $booking_data['field'] ?? $booking_data['fieldChoice'] ?? '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background-color: #f8f9fa;
        }

        .navbar-custom {
            background-color: var(--primary-green);
        }

        .card-header-custom {
            background-color: var(--primary-green);
            color: white;
        }

        .btn-custom-green {
            background-color: var(--primary-green);
            color: white;
            border: none;
            transition: all 0.3s;
        }

        .btn-custom-green:hover {
            background-color: var(--dark-green);
            color: white;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--primary-green);
            margin-bottom: 1rem;
        }

        .custom-card {
            border-radius: 10px;
            overflow: hidden;
            border: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-futbol me-2"></i>SportField</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="frontpage.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $booking_type; ?>_booking.php">Pesan</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="custom-card card shadow-sm">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Konfirmasi Pembayaran</h5>
                    </div>
                    <div class="card-body p-5 text-center">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="mb-4">Bukti Pembayaran Berhasil Dikirim!</h3>
                        <p class="lead">Terima kasih telah melakukan pembayaran. Tim kami akan memverifikasi pembayaran Anda dalam waktu 1x24 jam.</p>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mt-5">
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Detail Booking</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-4 text-muted">Nomor Booking</div>
                                            <div class="col-md-8">#<?php echo $booking_data['id']; ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4 text-muted">Tanggal & Waktu</div>
                                            <div class="col-md-8"><?php echo $booking_date; ?>, <?php echo $booking_time; ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4 text-muted">Lapangan</div>
                                            <div class="col-md-8"><?php echo $field_name; ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4 text-muted">Status</div>
                                            <div class="col-md-8"><span class="badge bg-warning">Menunggu Verifikasi</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="frontpagelogin.php" class="btn btn-custom-green px-4 py-2">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 