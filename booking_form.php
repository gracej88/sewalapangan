<?php
session_start();
include('db_connect.php');

// Cek apakah sudah login (periksa semua kemungkinan variabel session login)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['user'])) {
    // Redirect ke halaman login
    header("Location: login.php?redirect=booking_form.php" . (isset($_GET['type']) ? "?type=" . $_GET['type'] : ""));
    exit();
}

// Ambil user_id dari salah satu variabel session yang tersedia
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? 0;

// Ambil parameter dari URL atau set default
$field_type = isset($_GET['type']) ? $_GET['type'] : 'futsal';
$field_name = isset($_GET['field']) ? $_GET['field'] : '';
$booking_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$booking_time = isset($_GET['time']) ? $_GET['time'] : date('H:00:00', strtotime('+1 hour'));

// Cek struktur tabel fields (apakah menggunakan field_name atau name)
$check_field_name = false;
$check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_fields->num_rows > 0) {
    $check_column = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    $check_field_name = $check_column->num_rows > 0;
}

$field_name_column = "field_name";
$field_type_column = "field_type";

// Cek kolom name sebagai alternatif
if (!$check_field_name) {
    $check_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'name'");
    if ($check_name->num_rows > 0) {
        $field_name_column = "name";
    }
}

// Cek kolom type sebagai alternatif
$check_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_type'");
if ($check_type->num_rows == 0) {
    $check_alt_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'type'");
    if ($check_alt_type->num_rows > 0) {
        $field_type_column = "type";
    }
}

// Ambil data lapangan berdasarkan tipe
$fields_query = "SELECT * FROM fields WHERE $field_type_column = ?";
$stmt = $conn->prepare($fields_query);
$stmt->bind_param("s", $field_type);
$stmt->execute();
$fields_result = $stmt->get_result();
$fields = [];
while ($row = $fields_result->fetch_assoc()) {
    $fields[] = $row;
}

// Jika field_name tidak diberikan, gunakan yang pertama (jika ada)
if (empty($field_name) && !empty($fields)) {
    $field_name = $fields[0][$field_name_column];
}

// Ambil harga per jam untuk field yang dipilih
$price_per_hour = 0;
foreach ($fields as $field) {
    if ($field[$field_name_column] == $field_name) {
        $price_per_hour = $field['price_per_hour'];
        break;
    }
}

// Ambil data user yang sedang login
$user_data = [
    'name' => '',
    'email' => '',
    'phone' => ''
];

// Cek apakah tabel users tersedia
$check_users = $conn->query("SHOW TABLES LIKE 'users'");
$users_exists = $check_users->num_rows > 0;

if ($users_exists) {
    // Ambil data user dari database
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        if ($user_result && $user_result->num_rows > 0) {
            $user_db = $user_result->fetch_assoc();
            $user_data['name'] = $user_db['name'] ?? $user_db['username'] ?? '';
            $user_data['email'] = $user_db['email'] ?? '';
            $user_data['phone'] = $user_db['phone'] ?? '';
        }
    }
} else {
    // Jika tidak ada tabel users, gunakan data dari session
    $user_data['name'] = $_SESSION['name'] ?? $_SESSION['username'] ?? '';
    $user_data['email'] = $_SESSION['email'] ?? '';
    $user_data['phone'] = $_SESSION['phone'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Booking - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v5.15.4/css/all.css">
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
            color: #333;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--secondary-green) 0%, var(--primary-green) 100%);
            padding: 40px 0;
            color: white;
            margin-bottom: 30px;
        }
        
        .booking-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .booking-summary {
            background: var(--very-light-green);
            border-radius: 10px;
            padding: 20px;
        }
        
        .field-image {
            height: 200px;
            background-color: #f8f9fa;
            background-position: center;
            background-size: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .price-tag {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-green);
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Form Booking</h2>
                <div>
                    <a href="booking_calendar.php?type=<?php echo $field_type; ?>" class="btn btn-outline-light">
                        <i class="fas fa-calendar-alt me-2"></i>Kembali ke Kalender
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="booking-form mb-4">
                    <h4 class="mb-4">Detail Pemesanan</h4>
                    
                    <form action="process_booking.php" method="post">
                        <input type="hidden" name="field_type" value="<?php echo $field_type; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bookingDate" class="form-label">Tanggal Booking</label>
                                <input type="date" class="form-control" id="bookingDate" name="bookingDate" value="<?php echo $booking_date; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="bookingTime" class="form-label">Waktu Booking</label>
                                <input type="time" class="form-control" id="bookingTime" name="bookingTime" value="<?php echo substr($booking_time, 0, 5); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fieldChoice" class="form-label">Pilih Lapangan</label>
                            <select class="form-select" id="fieldChoice" name="fieldChoice" required>
                                <option value="">Pilih Lapangan...</option>
                                <?php if($fields_result->num_rows > 0): 
                                    $fields_result->data_seek(0); // Reset pointer result
                                    while($field = $fields_result->fetch_assoc()): ?>
                                        <option value="<?php echo $field[$field_name_column]; ?>" 
                                            <?php echo ($field[$field_name_column] == $field_name) ? 'selected' : ''; ?>
                                            data-price="<?php echo $field['price_per_hour']; ?>">
                                            <?php echo $field[$field_name_column]; ?> - Rp <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>/jam
                                        </option>
                                    <?php endwhile;
                                endif; ?>
                            </select>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Informasi Pemesan</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customerName" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="customerName" name="customerName" value="<?php echo $user_data['name'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="customerPhone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="customerPhone" name="customerPhone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customerEmail" name="customerEmail" value="<?php echo $user_data['email'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="teamName" class="form-label">Nama Tim (opsional)</label>
                            <input type="text" class="form-control" id="teamName" name="teamName">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan Tambahan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Metode Pembayaran</h5>
                        
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="methodTransfer" value="transfer" checked>
                                <label class="form-check-label" for="methodTransfer">
                                    <i class="fas fa-university me-2"></i>Transfer Bank
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="methodEwallet" value="ewallet">
                                <label class="form-check-label" for="methodEwallet">
                                    <i class="fas fa-wallet me-2"></i>E-Wallet
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="methodCash" value="cash">
                                <label class="form-check-label" for="methodCash">
                                    <i class="fas fa-money-bill-wave me-2"></i>Bayar di Tempat
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Booking Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="booking-summary sticky-top" style="top: 20px;">
                    <h4 class="mb-3">Ringkasan Booking</h4>
                    
                    <?php if($fields_result->num_rows > 0): 
                        $fields_result->data_seek(0); // Reset pointer result
                        $selected_field = null;
                        while($field = $fields_result->fetch_assoc()) {
                            if($field[$field_name_column] == $field_name) {
                                $selected_field = $field;
                                break;
                            }
                        }
                        
                        if($selected_field && !empty($selected_field['image'])): ?>
                            <div class="field-image" style="background-image: url('<?php echo $selected_field['image']; ?>')"></div>
                        <?php endif; 
                    endif; ?>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Jenis Lapangan</small>
                        <span class="fs-5 text-capitalize"><?php echo $field_type; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Nama Lapangan</small>
                        <span class="fs-5" id="summaryFieldName"><?php echo $field_name; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Tanggal & Waktu</small>
                        <span class="fs-5" id="summaryDateTime">
                            <?php 
                                $formatted_date = date('d F Y', strtotime($booking_date));
                                $formatted_time = date('H:i', strtotime($booking_time)); 
                                echo $formatted_date . ', ' . $formatted_time; 
                            ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Harga per jam</span>
                        <span class="price-tag" id="summaryPrice">Rp <?php echo number_format($price_per_hour, 0, ',', '.'); ?></span>
                    </div>
                    
                    <div class="alert alert-info mb-0 mt-3">
                        <i class="fas fa-info-circle me-2"></i>Booking akan dikonfirmasi setelah pembayaran berhasil.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update summary when form changes
        document.getElementById('fieldChoice').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('summaryFieldName').textContent = selectedOption.text.split('-')[0].trim();
            document.getElementById('summaryPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(selectedOption.dataset.price);
        });
        
        document.getElementById('bookingDate').addEventListener('change', updateDateTime);
        document.getElementById('bookingTime').addEventListener('change', updateDateTime);
        
        function updateDateTime() {
            const bookingDate = document.getElementById('bookingDate').value;
            const bookingTime = document.getElementById('bookingTime').value;
            
            if (bookingDate && bookingTime) {
                const date = new Date(bookingDate + 'T' + bookingTime);
                const options = { day: 'numeric', month: 'long', year: 'numeric' };
                const dateString = date.toLocaleDateString('id-ID', options);
                const timeString = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                
                document.getElementById('summaryDateTime').textContent = dateString + ', ' + timeString;
            }
        }
    </script>
</body>
</html> 