<?php
include 'db_connect.php';

// Pastikan request dari method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $booking_id = $_POST['booking_id'] ?? 0;
    $booking_table = $_POST['booking_table'] ?? '';
    $new_status = $_POST['new_status'] ?? 'pending_confirmation';
    
    // Validasi
    if (empty($booking_id) || empty($booking_table)) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
        exit;
    }
    
    // Pastikan tabel yang valid
    $valid_tables = ['badminton_booking'];
    if (!in_array($booking_table, $valid_tables)) {
        echo json_encode(['success' => false, 'message' => 'Tabel tidak valid']);
        exit;
    }
    
    // Update status booking
    $update_query = "UPDATE $booking_table SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        // Redirect kembali ke halaman sebelumnya
        header("Location: create_payment_test.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    // Jika bukan method POST, kembali ke halaman awal
    header("Location: create_payment_test.php");
    exit;
}
?> 