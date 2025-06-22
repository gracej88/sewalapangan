<?php
session_start();
include('db_connect.php');

// Pastikan hanya admin yang bisa akses halaman ini
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fungsi untuk mendapatkan semua pembayaran yang menunggu verifikasi
function getPendingPayments($conn) {
    $pending_payments = [];
    
    // Cek dulu apakah kolom field_name atau name yang ada di tabel fields
    $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    $field_name_column = ($check_field_name->num_rows > 0) ? 'field_name' : 'name';
    
    // Ambil dari tabel badminton_booking
    $badminton_query = "SELECT b.*, 'badminton' as field_type, f.price_per_hour 
                       FROM badminton_booking b 
                       LEFT JOIN fields f ON b.field = f.$field_name_column 
                       WHERE b.status = 'pending_confirmation'";
    $badminton_result = $conn->query($badminton_query);
    if ($badminton_result && $badminton_result->num_rows > 0) {
        while ($row = $badminton_result->fetch_assoc()) {
            $pending_payments[] = $row;
        }
    }
    
    return $pending_payments;
}

// Proses verifikasi pembayaran
if (isset($_POST['verify'])) {
    $booking_id = $_POST['booking_id'] ?? 0;
    $field_type = $_POST['field_type'] ?? '';
    
    if (!empty($booking_id) && !empty($field_type)) {
        $booking_table = '';
        switch ($field_type) {
            case 'badminton':
                $booking_table = 'badminton_booking';
                break;
            default:
                $_SESSION['admin_message'] = "Jenis lapangan tidak valid";
                header("Location: admin_verify_payment.php");
                exit();
        }
        
        // Update status booking menjadi confirmed
        $update_query = "UPDATE $booking_table SET status = 'confirmed' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_success'] = "Pembayaran berhasil diverifikasi";
        } else {
            $_SESSION['admin_message'] = "Gagal memverifikasi pembayaran: " . $stmt->error;
        }
        
        header("Location: admin_verify_payment.php");
        exit();
    }
}

// Proses penolakan pembayaran
if (isset($_POST['reject'])) {
    $booking_id = $_POST['booking_id'] ?? 0;
    $field_type = $_POST['field_type'] ?? '';
    $reject_reason = $_POST['reject_reason'] ?? '';
    
    if (!empty($booking_id) && !empty($field_type)) {
        $booking_table = '';
        switch ($field_type) {
            case 'badminton':
                $booking_table = 'badminton_booking';
                break;
            default:
                $_SESSION['admin_message'] = "Jenis lapangan tidak valid";
                header("Location: admin_verify_payment.php");
                exit();
        }
        
        // Update status booking menjadi rejected
        $update_query = "UPDATE $booking_table SET status = 'rejected', rejection_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $reject_reason, $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_success'] = "Pembayaran berhasil ditolak";
        } else {
            $_SESSION['admin_message'] = "Gagal menolak pembayaran: " . $stmt->error;
        }
        
        header("Location: admin_verify_payment.php");
        exit();
    }
}

// Ambil semua pembayaran yang menunggu verifikasi
$pending_payments = getPendingPayments($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
            padding-top: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .payment-proof-img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
        }
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-money-check-alt mr-2"></i> Verifikasi Pembayaran</h2>
            </div>
            <div class="col-md-6 text-right">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['admin_success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['admin_success']; unset($_SESSION['admin_success']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['admin_message']; unset($_SESSION['admin_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($pending_payments)): ?>
            <div class="card">
                <div class="card-body empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h4>Tidak ada pembayaran yang menunggu verifikasi</h4>
                    <p class="text-muted">Semua pembayaran telah diverifikasi</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($pending_payments as $payment): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Booking ID: #<?php echo $payment['id']; ?></span>
                        <span class="badge badge-pending">Menunggu Verifikasi</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Detail Booking</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Jenis Lapangan</td>
                                        <td>: <?php echo ucfirst($payment['field_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Nama Lapangan</td>
                                        <td>: <?php echo $payment['field']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal</td>
                                        <td>: <?php echo date('d-m-Y', strtotime($payment['booking_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Waktu</td>
                                        <td>: <?php echo date('H:i', strtotime($payment['booking_time'])); ?> WIB</td>
                                    </tr>
                                    <tr>
                                        <td>Harga</td>
                                        <td>: Rp <?php echo number_format($payment['price_per_hour'] ?? 0, 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Nama Pemesan</td>
                                        <td>: <?php echo $payment['customer_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Telepon</td>
                                        <td>: <?php echo $payment['customer_phone']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Email</td>
                                        <td>: <?php echo $payment['customer_email']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Metode Pembayaran</td>
                                        <td>: <?php echo $payment['payment_method'] ?? 'Tidak ada'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Pembayaran</td>
                                        <td>: <?php echo isset($payment['payment_date']) ? date('d-m-Y H:i', strtotime($payment['payment_date'])) : 'Tidak ada'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>: <span class="badge badge-warning">Menunggu Verifikasi</span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Bukti Pembayaran</h5>
                                <?php if (isset($payment['payment_proof']) && !empty($payment['payment_proof'])): ?>
                                    <div class="mb-3">
                                        <img src="<?php echo $payment['payment_proof']; ?>" alt="Bukti Pembayaran" class="payment-proof-img img-fluid" onclick="openImageModal('<?php echo $payment['payment_proof']; ?>')">
                                        <div class="mt-2">
                                            <small class="text-muted">Klik pada gambar untuk memperbesar</small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> Tidak ada bukti pembayaran yang diupload
                                    </div>
                                <?php endif; ?>
                                
                                <div class="action-buttons mt-4">
                                    <form method="post" action="">
                                        <input type="hidden" name="booking_id" value="<?php echo $payment['id']; ?>">
                                        <input type="hidden" name="field_type" value="<?php echo $payment['field_type']; ?>">
                                        <button type="submit" name="verify" class="btn btn-success">
                                            <i class="fas fa-check-circle mr-2"></i> Verifikasi
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal<?php echo $payment['id']; ?>">
                                        <i class="fas fa-times-circle mr-2"></i> Tolak
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Tolak Pembayaran -->
                <div class="modal fade" id="rejectModal<?php echo $payment['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?php echo $payment['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="rejectModalLabel<?php echo $payment['id']; ?>">Tolak Pembayaran</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" action="">
                                <div class="modal-body">
                                    <input type="hidden" name="booking_id" value="<?php echo $payment['id']; ?>">
                                    <input type="hidden" name="field_type" value="<?php echo $payment['field_type']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="reject_reason">Alasan Penolakan</label>
                                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" name="reject" class="btn btn-danger">Tolak Pembayaran</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Modal untuk menampilkan gambar bukti pembayaran -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Bukti Pembayaran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Bukti Pembayaran" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            $('#imageModal').modal('show');
        }
    </script>
</body>
</html> 