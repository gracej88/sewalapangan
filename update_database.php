<?php
session_start();
include('db_connect.php');

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
        return false;
    }
}

// Fungsi untuk menambahkan kolom user_id jika belum ada
function addUserIdColumn($conn, $table_name) {
    // Cek apakah kolom user_id sudah ada
    $check_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'user_id'");
    if ($check_column->num_rows == 0) {
        $add_column_query = "ALTER TABLE $table_name ADD COLUMN user_id INT(11) DEFAULT NULL AFTER id";
        return $conn->query($add_column_query);
    }
    return true;
}

// Fungsi untuk menambahkan data lapangan dummy jika tabel kosong
function addSampleFields($conn) {
    // Cek dulu apakah ada data di tabel fields
    $check_data = $conn->query("SELECT COUNT(*) as count FROM fields");
    $row = $check_data->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Tambahkan data dummy untuk setiap jenis lapangan
        $fields = [
            ['Lapangan Futsal A', 'futsal', 'Jakarta Selatan', 150000],
            ['Lapangan Futsal B', 'futsal', 'Jakarta Selatan', 175000],
            ['Lapangan Badminton 1', 'badminton', 'Jakarta Pusat', 75000],
            ['Lapangan Badminton 2', 'badminton', 'Jakarta Pusat', 80000],
            ['Lapangan Tennis Court', 'tennis', 'Jakarta Barat', 200000]
        ];
        
        // Cek apakah tabel menggunakan field_name atau name
        $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
        if ($check_field_name->num_rows > 0) {
            $field_col = 'field_name';
        } else {
            $field_col = 'name';
        }
        
        // Cek apakah tabel menggunakan field_type atau type
        $check_field_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_type'");
        if ($check_field_type->num_rows > 0) {
            $type_col = 'field_type';
        } else {
            $type_col = 'type';
        }
        
        // Insert data
        foreach ($fields as $field) {
            $insert_query = "INSERT INTO fields ($field_col, $type_col, location, price_per_hour) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssd", $field[0], $field[1], $field[2], $field[3]);
            $stmt->execute();
        }
        
        return true;
    }
    
    return false;
}

// Buat/update tabel secara otomatis saat halaman dikunjungi
$messages = [];
$success = true;

// Cek tabel fields
$check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_fields->num_rows == 0) {
    // Buat tabel fields dengan fleksibilitas nama kolom
    // Beberapa sistem mungkin menggunakan name dan type, yang lain mungkin field_name dan field_type
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
    
    if ($conn->query($create_fields)) {
        $messages[] = "Tabel fields berhasil dibuat.";
        // Tambahkan data dummy untuk fields
        addSampleFields($conn);
    } else {
        $messages[] = "Error membuat tabel fields: " . $conn->error;
        $success = false;
    }
} else {
    // Cek apakah struktur tabel fields sudah benar dan memiliki data
    $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    if ($check_field_name->num_rows == 0) {
        // Coba cek apakah menggunakan 'name' sebagai gantinya
        $check_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'name'");
        
        if ($check_name->num_rows == 0) {
            // Jika tidak ada kolom field_name atau name, tambahkan kolom field_name
            $add_field_name = "ALTER TABLE fields ADD COLUMN field_name VARCHAR(100) NOT NULL AFTER id";
            if ($conn->query($add_field_name)) {
                $messages[] = "Kolom field_name berhasil ditambahkan ke tabel fields.";
            } else {
                $messages[] = "Error menambahkan kolom field_name: " . $conn->error;
                $success = false;
            }
        }
    }
    
    // Cek apakah ada data di fields
    addSampleFields($conn);
}

// Cek dan buat tabel badminton_booking jika belum ada
$check_badminton = $conn->query("SHOW TABLES LIKE 'badminton_booking'");
if ($check_badminton->num_rows == 0) {
    $result = createBookingTable($conn, 'badminton_booking');
    if ($result) {
        $messages[] = "Tabel badminton_booking berhasil dibuat.";
    } else {
        $messages[] = "Error membuat tabel badminton_booking: " . $conn->error;
        $success = false;
    }
} else {
    // Tambahkan kolom user_id jika belum ada
    if (addUserIdColumn($conn, 'badminton_booking')) {
        $messages[] = "Tabel badminton_booking diperbarui.";
    } else {
        $messages[] = "Error memperbarui tabel badminton_booking: " . $conn->error;
        $success = false;
    }
}

// Tampilkan halaman
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Update Database</h4>
            </div>
            <div class="card-body">';

if ($success) {
    echo '<div class="alert alert-success">Database berhasil diperbarui!</div>';
} else {
    echo '<div class="alert alert-danger">Terjadi beberapa kesalahan:</div>
          <ul>';
    foreach ($messages as $message) {
        echo '<li>' . $message . '</li>';
    }
    echo '</ul>';
}

echo '      <div class="mt-4">
                <a href="frontpage.php" class="btn btn-primary">Kembali ke Halaman Utama</a>
                <a href="booking_history.php" class="btn btn-success">Lihat Riwayat Booking</a>
            </div>
        </div>
    </div>
</body>
</html>';
?> 