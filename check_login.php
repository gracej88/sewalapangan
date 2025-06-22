<?php
session_start();
include('db_connect.php');

// Tampilkan header
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Login - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Status Login</h4>
            </div>
            <div class="card-body">';

// Tampilkan info session
echo '<h5>Session Data:</h5>';
echo '<pre>' . print_r($_SESSION, true) . '</pre>';

// Cek apakah user login
echo '<h5>Login Status:</h5>';
if (isset($_SESSION['user_id']) || isset($_SESSION['id']) || isset($_SESSION['user'])) {
    echo '<div class="alert alert-success">User terdeteksi login</div>';
    
    // Ambil user_id dari session yang tersedia
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? 0;
    echo '<p>User ID yang terdeteksi: ' . $user_id . '</p>';
    
    // Cek tabel users
    $check_users = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_users->num_rows > 0) {
        echo '<div class="alert alert-info">Tabel users ditemukan dalam database</div>';
        
        // Cek data user berdasarkan ID
        $user_query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            echo '<h5>Data User dari Database:</h5>';
            echo '<table class="table table-bordered">';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            foreach ($user_data as $key => $value) {
                if ($key != 'password') {  // Jangan tampilkan password
                    echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            }
            echo '</table>';
        } else {
            echo '<div class="alert alert-warning">User dengan ID ' . $user_id . ' tidak ditemukan di database</div>';
        }
        
        // Tampilkan struktur tabel users
        echo '<h5>Struktur Tabel Users:</h5>';
        $structure_query = "DESCRIBE users";
        $structure_result = $conn->query($structure_query);
        
        if ($structure_result) {
            echo '<table class="table table-bordered">';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            while ($row = $structure_result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['Field'] . '</td>';
                echo '<td>' . $row['Type'] . '</td>';
                echo '<td>' . $row['Null'] . '</td>';
                echo '<td>' . $row['Key'] . '</td>';
                echo '<td>' . $row['Default'] . '</td>';
                echo '<td>' . $row['Extra'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } else {
        echo '<div class="alert alert-danger">Tabel users tidak ditemukan dalam database</div>';
    }
} else {
    echo '<div class="alert alert-danger">User tidak terdeteksi login</div>';
}

// Cek tabel booking
echo '<h5>Tabel Booking:</h5>';
$booking_tables = ['badminton_booking'];
foreach ($booking_tables as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table->num_rows > 0) {
        echo '<div class="alert alert-info">Tabel ' . $table . ' ditemukan</div>';
        
        // Tampilkan struktur tabel
        echo '<h6>Struktur Tabel ' . $table . ':</h6>';
        $structure_query = "DESCRIBE $table";
        $structure_result = $conn->query($structure_query);
        
        if ($structure_result) {
            echo '<table class="table table-bordered">';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            while ($row = $structure_result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['Field'] . '</td>';
                echo '<td>' . $row['Type'] . '</td>';
                echo '<td>' . $row['Null'] . '</td>';
                echo '<td>' . $row['Key'] . '</td>';
                echo '<td>' . $row['Default'] . '</td>';
                echo '<td>' . $row['Extra'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // Tampilkan data booking (jika ada)
        $booking_query = "SELECT * FROM $table LIMIT 5";
        $booking_result = $conn->query($booking_query);
        
        if ($booking_result && $booking_result->num_rows > 0) {
            echo '<h6>Data ' . $table . ' (5 teratas):</h6>';
            echo '<table class="table table-bordered">';
            // Header
            echo '<tr>';
            $fields = $booking_result->fetch_fields();
            foreach ($fields as $field) {
                echo '<th>' . $field->name . '</th>';
            }
            echo '</tr>';
            
            // Data rows
            $booking_result->data_seek(0);
            while ($row = $booking_result->fetch_assoc()) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . $value . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>Tidak ada data booking di tabel ' . $table . '</p>';
        }
    } else {
        echo '<div class="alert alert-warning">Tabel ' . $table . ' tidak ditemukan</div>';
    }
}

// Cek tabel fields
echo '<h5>Tabel Fields:</h5>';
$check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_fields->num_rows > 0) {
    echo '<div class="alert alert-info">Tabel fields ditemukan</div>';
    
    // Tampilkan struktur tabel
    echo '<h6>Struktur Tabel fields:</h6>';
    $structure_query = "DESCRIBE fields";
    $structure_result = $conn->query($structure_query);
    
    if ($structure_result) {
        echo '<table class="table table-bordered">';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        while ($row = $structure_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['Field'] . '</td>';
            echo '<td>' . $row['Type'] . '</td>';
            echo '<td>' . $row['Null'] . '</td>';
            echo '<td>' . $row['Key'] . '</td>';
            echo '<td>' . $row['Default'] . '</td>';
            echo '<td>' . $row['Extra'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // Tampilkan data fields
    $fields_query = "SELECT * FROM fields";
    $fields_result = $conn->query($fields_query);
    
    if ($fields_result && $fields_result->num_rows > 0) {
        echo '<h6>Data fields:</h6>';
        echo '<table class="table table-bordered">';
        // Header
        echo '<tr>';
        $fields = $fields_result->fetch_fields();
        foreach ($fields as $field) {
            echo '<th>' . $field->name . '</th>';
        }
        echo '</tr>';
        
        // Data rows
        $fields_result->data_seek(0);
        while ($row = $fields_result->fetch_assoc()) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . $value . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>Tidak ada data di tabel fields</p>';
    }
} else {
    echo '<div class="alert alert-warning">Tabel fields tidak ditemukan</div>';
}

echo '      <div class="mt-4">
                <a href="update_database.php" class="btn btn-primary">Update Database</a>
                <a href="frontpage.php" class="btn btn-secondary">Kembali ke Halaman Utama</a>
                <a href="booking_history.php" class="btn btn-success">Lihat Riwayat Booking</a>
            </div>
        </div>
    </div>
</body>
</html>';
?> 