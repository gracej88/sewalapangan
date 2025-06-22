<?php
session_start();
include('db_connect.php');

// Cek apakah user login sebagai admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    $_SESSION['error'] = "Anda harus login sebagai admin terlebih dahulu";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $field_name = $_POST['field_name'] ?? '';
    $field_type = $_POST['field_type'] ?? '';
    $price_per_hour = $_POST['price_per_hour'] ?? 0;
    $status = $_POST['status'] ?? 'available';
    
    // Validasi data
    if (empty($field_name) || empty($field_type) || empty($price_per_hour)) {
        // Redirect kembali dengan pesan error
        header("Location: dashboard.php?error=Semua field wajib diisi");
        exit();
    }
    
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
    
    // Cek apakah ada kolom status di tabel fields
    $check_status = $conn->query("SHOW COLUMNS FROM fields LIKE 'status'");
    if ($check_status->num_rows == 0) {
        // Tambahkan kolom status jika belum ada
        $conn->query("ALTER TABLE fields ADD COLUMN status VARCHAR(20) DEFAULT 'available'");
    }
    
    // Simpan ke database
    $query = "INSERT INTO fields ($field_col, $type_col, price_per_hour, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssds", $field_name, $field_type, $price_per_hour, $status);
    
    if ($stmt->execute()) {
        // Redirect dengan pesan sukses
        header("Location: dashboard.php?success=Lapangan berhasil ditambahkan");
        exit();
    } else {
        // Redirect dengan pesan error
        header("Location: dashboard.php?error=Gagal menyimpan data: " . $stmt->error);
        exit();
    }
} else {
    // Jika bukan method POST, redirect ke dashboard
    header("Location: dashboard.php");
    exit();
}
?> 