<?php
session_start();
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data
    $booking_date = $_POST['bookingDate'] ?? '';
    $booking_time = $_POST['bookingTime'] ?? '';
    $field = $_POST['fieldChoice'] ?? '';
    $customer_name = $_POST['customerName'] ?? '';
    $customer_phone = $_POST['customerPhone'] ?? '';
    $customer_email = $_POST['customerEmail'] ?? '';
    $team_name = $_POST['teamName'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Ambil user_id dari session jika tersedia
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? null;

    // Validasi
    if (!$booking_date || !$booking_time || !$field || !$customer_name || !$customer_phone || !$customer_email) {
        die("❗ Semua isian wajib diisi.");
    }

    // Query insert
    $stmt = $conn->prepare("INSERT INTO badminton_booking 
        (booking_date, booking_time, field, customer_name, customer_phone, customer_email, team_name, notes, status, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending_confirmation', ?)");

    if (!$stmt) {
        die("❌ Prepare gagal: " . $conn->error);
    }

    $stmt->bind_param("ssssssssi", $booking_date, $booking_time, $field, $customer_name, $customer_phone, $customer_email, $team_name, $notes, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Booking berhasil. Data sudah masuk database.";
    } else {
        $_SESSION['error'] = "❌ Gagal menyimpan ke database: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

header("Location: pemesanan_badminton_done.php");
    
?>
