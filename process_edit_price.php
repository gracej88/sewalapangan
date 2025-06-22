<?php
session_start();
include('db_connect.php');

// Cek apakah user sudah login sebagai admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header("Location: adminlogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $field_id = $_POST['field_id'] ?? 0;
    $new_price = $_POST['new_price'] ?? 0;
    
    // Validasi data
    if (empty($field_id) || empty($new_price) || !is_numeric($new_price)) {
        // Redirect kembali dengan pesan error
        header("Location: dashboard.php?error=Data harga tidak valid");
        exit();
    }
    
    // Update harga di database
    $query = "UPDATE fields SET price_per_hour = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("di", $new_price, $field_id);
    
    if ($stmt->execute()) {
        // Redirect dengan pesan sukses
        header("Location: dashboard.php?success=Harga lapangan berhasil diupdate");
        exit();
    } else {
        // Redirect dengan pesan error
        header("Location: dashboard.php?error=Gagal mengupdate harga: " . $stmt->error);
        exit();
    }
} else {
    // Jika bukan method POST, redirect ke dashboard
    header("Location: dashboard.php");
    exit();
}
?> 