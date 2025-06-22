<?php
session_start();
include('db_connect.php');

// Periksa apakah user adalah admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    $_SESSION['error'] = "Anda tidak memiliki akses untuk melakukan tindakan ini";
    header("Location: dashboard.php");
    exit();
}

// Periksa apakah ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $field_id = $_POST['field_id'] ?? 0;
    $price_per_hour = $_POST['price_per_hour'] ?? 0;
    
    // Validasi input
    if (empty($field_id) || !is_numeric($field_id) || $field_id <= 0) {
        $_SESSION['error'] = "ID lapangan tidak valid";
        header("Location: dashboard.php");
        exit();
    }
    
    if (empty($price_per_hour) || !is_numeric($price_per_hour) || $price_per_hour < 0) {
        $_SESSION['error'] = "Harga per jam tidak valid";
        header("Location: dashboard.php");
        exit();
    }
    
    // Update harga lapangan
    $update_query = "UPDATE fields SET price_per_hour = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("di", $price_per_hour, $field_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Harga lapangan berhasil diperbarui";
    } else {
        $_SESSION['error'] = "Gagal memperbarui harga lapangan: " . $stmt->error;
    }
    
    // Redirect kembali ke dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Jika bukan method POST, redirect ke dashboard
    header("Location: dashboard.php");
    exit();
}
?> 