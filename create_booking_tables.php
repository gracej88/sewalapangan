<?php
session_start();
include('db_connect.php');

// Cek apakah sudah login sebagai admin
if (!isset($_SESSION['admin_id'])) {
    echo "Anda tidak memiliki akses ke halaman ini. Silakan login sebagai admin.";
    exit();
}

// Fungsi untuk membuat tabel booking
function createBookingTable($conn, $table_name) {
    $create_table_query = "CREATE TABLE IF NOT EXISTS $table_name (
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
        status VARCHAR(50) DEFAULT 'booked',
        payment_method VARCHAR(50) DEFAULT NULL,
        payment_date DATETIME DEFAULT NULL,
        payment_proof VARCHAR(255) DEFAULT NULL,
        payment_reference VARCHAR(100) DEFAULT NULL,
        payment_sender VARCHAR(100) DEFAULT NULL,
        payment_bank VARCHAR(100) DEFAULT NULL,
        rejection_reason TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_query)) {
        return true;
    } else {
        echo "Error creating table $table_name: " . $conn->error;
        return false;
    }
}

// Fungsi untuk menambahkan kolom user_id jika belum ada
function addUserIdColumn($conn, $table_name) {
    // Cek apakah kolom user_id sudah ada
    $check_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'user_id'");
    if ($check_column->num_rows == 0) {
        $add_column_query = "ALTER TABLE $table_name ADD COLUMN user_id INT(11) DEFAULT NULL AFTER id";
        if ($conn->query($add_column_query)) {
            echo "Kolom user_id berhasil ditambahkan ke $table_name<br>";
        } else {
            echo "Error menambahkan kolom user_id ke $table_name: " . $conn->error . "<br>";
        }
    } else {
        echo "Kolom user_id sudah ada di tabel $table_name<br>";
    }
}

// Buat tabel booking
$success = true;

// Cek dan buat tabel badminton_booking jika belum ada
$check_badminton = $conn->query("SHOW TABLES LIKE 'badminton_booking'");
if ($check_badminton->num_rows == 0) {
    $result = createBookingTable($conn, 'badminton_booking');
    if ($result) {
        echo "Tabel badminton_booking berhasil dibuat<br>";
    } else {
        $success = false;
    }
} else {
    echo "Tabel badminton_booking sudah ada<br>";
    // Tambahkan kolom user_id jika belum ada
    addUserIdColumn($conn, 'badminton_booking');
}


if ($success) {
    echo "<br>Semua tabel booking berhasil dibuat atau diperbarui!";
    echo "<br><br><a href='dashboard.php'>Kembali ke Dashboard</a>";
} else {
    echo "<br>Terjadi kesalahan dalam membuat beberapa tabel.";
}
?> 