<?php
session_start();
include('db_connect.php');

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data booking dari database
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    header("Location: booking_calendar.php");
    exit();
}

$booking_id = $_GET['id'];
$field_type = $_GET['type'];

$booking_table = '';
switch ($field_type) {
    case 'badminton':
        $booking_table = 'badminton_booking';
        break;
    default:
        header("Location: booking_calendar.php");
        exit();
}

// Ambil data booking
$query = "SELECT b.*, f.price_per_hour FROM $booking_table b 
          JOIN fields f ON b.field = f.field_name 
          WHERE b.id = ? AND f.field_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $booking_id, $field_type);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: booking_calendar.php");
    exit();
}

$booking_data = $result->fetch_assoc();
$price = $booking_data['price_per_hour'];
$payment_deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Transfer - SportField</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
        }
        .payment-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .payment-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .bank-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px dashed #ccc;
        }
        .alert-warning {
            font-size: 14px;
        }
        .countdown {
            font-size: 20px;
            font-weight: bold;
            color: #dc3545;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="payment-header text-center">
                <h3><i class="fas fa-money-check-alt mr-2"></i> Instruksi Pembayaran</h3>
                <p class="mb-0">Transfer Bank</p>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-clock mr-2"></i> Harap selesaikan pembayaran sebelum:
                <div class="countdown" id="countdown"><?php echo $payment_deadline; ?></div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Detail Pesanan:</h5>
                    <table class="table table-sm">
                        <tr>
                            <td>Lapangan</td>
                            <td>: <?php echo $booking_data['field']; ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal</td>
                            <td>: <?php echo date('d-m-Y', strtotime($booking_data['booking_date'])); ?></td>
                        </tr>
                        <tr>
                            <td>Waktu</td>
                            <td>: <?php echo date('H:i', strtotime($booking_data['booking_time'])); ?> WIB</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Total Pembayaran:</h5>
                    <div class="h3 text-primary">Rp. <?php echo number_format($price, 0, ',', '.'); ?></div>
                </div>
            </div>
            
            <div class="bank-details">
                <h5><i class="fas fa-university mr-2"></i> Transfer ke Rekening Berikut:</h5>
                <div class="row mt-3">
                    <div class="col-md-4 text-center mb-3">
                        <img src="https://upload.wikimedia.org/wikipedia/id/thumb/5/55/BNI_logo.svg/200px-BNI_logo.svg.png" alt="BNI" style="height: 40px;">
                        <p class="mt-2 mb-0"><strong>Bank BNI</strong></p>
                        <p class="mb-0">1234567890</p>
                        <p class="mb-0">a.n. SPORTFIELD</p>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/Bank_Mandiri_logo_2016.svg/200px-Bank_Mandiri_logo_2016.svg.png" alt="Mandiri" style="height: 40px;">
                        <p class="mt-2 mb-0"><strong>Bank Mandiri</strong></p>
                        <p class="mb-0">0987654321</p>
                        <p class="mb-0">a.n. SPORTFIELD</p>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/200px-Bank_Central_Asia.svg.png" alt="BCA" style="height: 40px;">
                        <p class="mt-2 mb-0"><strong>Bank BCA</strong></p>
                        <p class="mb-0">1122334455</p>
                        <p class="mb-0">a.n. SPORTFIELD</p>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle mr-2"></i> Petunjuk Pembayaran:</h5>
                <ol>
                    <li>Transfer tepat sesuai nominal di atas.</li>
                    <li>Gunakan ATM/Internet Banking/Mobile Banking untuk transfer.</li>
                    <li>Simpan bukti pembayaran Anda.</li>
                    <li>Upload bukti pembayaran dengan klik tombol "Upload Bukti Transfer" di bawah.</li>
                    <li>Pembayaran akan diverifikasi dalam 1x24 jam.</li>
                </ol>
            </div>
            
            <div class="action-buttons">
                <a href="booking_history.php" class="btn btn-outline-secondary"><i class="fas fa-history mr-2"></i> Lihat Riwayat Booking</a>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal"><i class="fas fa-upload mr-2"></i> Upload Bukti Transfer</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Upload Bukti Transfer -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Bukti Transfer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="process_payment_proof.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <input type="hidden" name="field_type" value="<?php echo $field_type; ?>">
                        
                        <div class="form-group">
                            <label for="payment_proof">Foto Bukti Transfer</label>
                            <input type="file" class="form-control-file" id="payment_proof" name="payment_proof" required>
                            <small class="form-text text-muted">Format file: JPG, PNG, atau PDF. Ukuran maksimal: 2MB</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="sender_name">Nama Pengirim</label>
                            <input type="text" class="form-control" id="sender_name" name="sender_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="sender_bank">Bank Pengirim</label>
                            <select class="form-control" id="sender_bank" name="sender_bank" required>
                                <option value="">-- Pilih Bank --</option>
                                <option value="BCA">BCA</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="BNI">BNI</option>
                                <option value="BRI">BRI</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="transfer_date">Tanggal Transfer</label>
                            <input type="date" class="form-control" id="transfer_date" name="transfer_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script untuk countdown timer
        const deadlineStr = "<?php echo $payment_deadline; ?>";
        const deadline = new Date(deadlineStr).getTime();
        
        const countdownTimer = setInterval(function() {
            const now = new Date().getTime();
            const distance = deadline - now;
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("countdown").innerHTML = hours + ":" + minutes + ":" + seconds;
            
            if (distance < 0) {
                clearInterval(countdownTimer);
                document.getElementById("countdown").innerHTML = "WAKTU HABIS";
            }
        }, 1000);
    </script>
</body>
</html> 