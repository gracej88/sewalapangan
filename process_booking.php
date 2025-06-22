<?php
session_start();
include('db_connect.php');

// Cek apakah sudah login (periksa semua kemungkinan variabel session login)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Ambil user_id dari salah satu variabel session yang tersedia
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $field_type = $_POST['field_type'] ?? '';
    $booking_date = $_POST['bookingDate'] ?? '';
    $booking_time = $_POST['bookingTime'] ?? '';
    $field_choice = $_POST['fieldChoice'] ?? '';
    $customer_name = $_POST['customerName'] ?? '';
    $customer_phone = $_POST['customerPhone'] ?? '';
    $customer_email = $_POST['customerEmail'] ?? '';
    $team_name = $_POST['teamName'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $payment_method = $_POST['paymentMethod'] ?? 'transfer';
    
    // Validasi data
    if (empty($field_type) || empty($booking_date) || empty($booking_time) || empty($field_choice) || 
        empty($customer_name) || empty($customer_email)) {
        $_SESSION['booking_error'] = "Semua field wajib diisi";
        header("Location: booking_form.php");
        exit();
    }
    
    // Validasi format waktu booking
    if (strlen($booking_time) <= 5) {
        $booking_time .= ':00'; // Tambahkan detik jika tidak ada
    }
    
    // Cek ketersediaan lapangan
    $booking_table = '';
    switch ($field_type) {
        case 'badminton':
            $booking_table = 'badminton_booking';
            break;
        default:
            $_SESSION['booking_error'] = "Jenis lapangan tidak valid";
            header("Location: booking_form.php");
            exit();
    }
    
    // Cek apakah tabel booking sudah ada, jika belum maka buat tabelnya
    $check_table = $conn->query("SHOW TABLES LIKE '$booking_table'");
    if ($check_table->num_rows == 0) {
        // Buat tabel booking
        $create_table_query = "CREATE TABLE IF NOT EXISTS $booking_table (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) DEFAULT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            field VARCHAR(100) NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20) DEFAULT NULL,
            customer_email VARCHAR(100) NOT NULL,
            team_name VARCHAR(100) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'pending_payment',
            payment_method VARCHAR(50) DEFAULT NULL,
            payment_date DATETIME DEFAULT NULL,
            payment_proof VARCHAR(255) DEFAULT NULL,
            payment_reference VARCHAR(100) DEFAULT NULL,
            payment_sender VARCHAR(100) DEFAULT NULL,
            payment_bank VARCHAR(100) DEFAULT NULL,
            rejection_reason TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($create_table_query);
    }
    
    // Cek apakah tabel fields ada
    $check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
    if ($check_fields->num_rows == 0) {
        // Buat tabel fields jika belum ada
        $create_fields = "CREATE TABLE IF NOT EXISTS fields (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            field_name VARCHAR(100) NOT NULL,
            field_type ENUM('badminton') NOT NULL,
            location VARCHAR(100) DEFAULT NULL,
            price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0,
            image VARCHAR(255) DEFAULT NULL,
            status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($create_fields);
        
        // Tambahkan data dummy
        $fields = [
            ['Lapangan Futsal A', 'futsal', 'Jakarta Selatan', 150000],
            ['Lapangan Futsal B', 'futsal', 'Jakarta Selatan', 175000],
            ['Lapangan Badminton 1', 'badminton', 'Jakarta Pusat', 75000],
            ['Lapangan Badminton 2', 'badminton', 'Jakarta Pusat', 80000],
            ['Lapangan Tennis Court', 'tennis', 'Jakarta Barat', 200000]
        ];
        
        foreach ($fields as $field) {
            $insert_query = "INSERT INTO fields (field_name, field_type, location, price_per_hour) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssd", $field[0], $field[1], $field[2], $field[3]);
            $stmt->execute();
        }
    }
    
    // Cek apakah lapangan tersedia pada tanggal dan waktu tersebut
    $check_query = "SELECT * FROM $booking_table WHERE booking_date = ? AND booking_time = ? AND field = ? AND status NOT IN ('rejected', 'cancelled')";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sss", $booking_date, $booking_time, $field_choice);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['booking_error'] = "Lapangan sudah dipesan pada waktu tersebut. Silakan pilih waktu lain.";
        header("Location: booking_form.php?type=$field_type");
        exit();
    }
    
    // Status booking berdasarkan metode pembayaran
    $booking_status = ($payment_method == 'cash') ? 'pending_confirmation' : 'pending_payment';
    
    // Simpan data booking ke database
    $insert_query = "INSERT INTO $booking_table (user_id, booking_date, booking_time, field, customer_name, customer_phone, 
                    customer_email, team_name, notes, status, payment_method) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issssssssss", $user_id, $booking_date, $booking_time, $field_choice, 
                     $customer_name, $customer_phone, $customer_email, $team_name, $notes, 
                     $booking_status, $payment_method);
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Redirect ke halaman pembayaran
        switch ($payment_method) {
            case 'transfer':
                header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
                break;
            case 'ewallet':
                header("Location: payment_ewallet.php?id=$booking_id&type=$field_type");
                break;
            case 'cash':
                header("Location: payment_cash.php?id=$booking_id&type=$field_type");
                break;
            default:
                header("Location: payment.php?id=$booking_id&type=$field_type");
        }
        exit();
    } else {
        $_SESSION['booking_error'] = "Gagal menyimpan data booking: " . $stmt->error;
        header("Location: booking_form.php?type=$field_type");
        exit();
    }
} else {
    // Jika bukan method POST, redirect ke halaman booking
    header("Location: booking_calendar.php");
    exit();
}
?> 