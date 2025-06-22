<?php
session_start();
include('db_connect.php');

// Cek apakah user login sebagai admin
if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    $_SESSION['error'] = "Anda harus login sebagai admin terlebih dahulu";
    header("Location: login.php");
    exit();
}

// Ambil ID dan tabel booking dari parameter URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$table_name = isset($_GET['table']) ? $_GET['table'] : '';

// Validasi tabel booking
$valid_tables = ['badminton_booking'];
if (!in_array($table_name, $valid_tables)) {
    $_SESSION['error'] = "Tabel booking tidak valid";
    header("Location: dashboard.php");
    exit();
}

// Cek apakah booking ada
$query = "SELECT * FROM $table_name WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Booking tidak ditemukan";
    header("Location: dashboard.php");
    exit();
}

// Ambil data booking
$booking = $result->fetch_assoc();

// Cek apakah booking masih dalam status pending
if ($booking['status'] != 'pending') {
    $_SESSION['error'] = "Booking ini sudah diverifikasi atau dibatalkan";
    header("Location: dashboard.php");
    exit();
}

// Verifikasi booking
$update_query = "UPDATE $table_name SET status = 'confirmed' WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    // Perbarui status lapangan jika perlu
    $field_name = $booking['field'];
    $check_field = $conn->query("SELECT * FROM fields WHERE name = '$field_name' OR field_name = '$field_name'");
    
    if ($check_field && $check_field->num_rows > 0) {
        $field = $check_field->fetch_assoc();
        
        // Jika lapangan tersedia, update status menjadi reserved
        if ($field['status'] == 'available') {
            $field_id = $field['id'];
            $conn->query("UPDATE fields SET status = 'reserved' WHERE id = $field_id");
        }
    }
    
    $_SESSION['success'] = "Booking berhasil diverifikasi";
} else {
    $_SESSION['error'] = "Gagal memverifikasi booking: " . $conn->error;
}

// Redirect kembali ke dashboard
header("Location: dashboard.php");
exit();
?> 