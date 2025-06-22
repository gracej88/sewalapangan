<?php
session_start();
include('db_connect.php');

// Cek apakah sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $booking_id = $_POST['booking_id'] ?? 0;
    $field_type = $_POST['field_type'] ?? '';
    $sender_name = $_POST['sender_name'] ?? '';
    $sender_bank = $_POST['sender_bank'] ?? '';
    $transfer_date = $_POST['transfer_date'] ?? '';
    
    // Validasi data
    if (empty($booking_id) || empty($field_type) || empty($sender_name) || empty($sender_bank) || empty($transfer_date)) {
        $_SESSION['payment_error'] = "Semua field wajib diisi";
        header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
        exit();
    }
    
    // Tentukan tabel booking berdasarkan jenis lapangan
    $booking_table = '';
    switch ($field_type) {
        case 'badminton':
            $booking_table = 'badminton_booking';
            break;
        default:
            $_SESSION['payment_error'] = "Jenis lapangan tidak valid";
            header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
            exit();
    }
    
    // Cek apakah data booking ada
    $check_query = "SELECT * FROM $booking_table WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $_SESSION['payment_error'] = "Data booking tidak ditemukan";
        header("Location: booking_history.php");
        exit();
    }
    
    // Upload bukti pembayaran
    $proof_image = '';
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/payment_proofs/';
        
        // Buat direktori jika belum ada
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Ambil info file
        $file_name = $_FILES['payment_proof']['name'];
        $file_tmp = $_FILES['payment_proof']['tmp_name'];
        $file_size = $_FILES['payment_proof']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi tipe file
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['payment_error'] = "Format file tidak valid. Hanya JPG, PNG, atau PDF yang diperbolehkan.";
            header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
            exit();
        }
        
        // Validasi ukuran file (max 2MB)
        if ($file_size > 2097152) {
            $_SESSION['payment_error'] = "Ukuran file terlalu besar. Maksimal 2MB.";
            header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
            exit();
        }
        
        // Buat nama file unik
        $new_file_name = $booking_id . '_' . date('YmdHis') . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;
        
        // Upload file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $proof_image = $upload_path;
        } else {
            $_SESSION['payment_error'] = "Gagal upload file bukti pembayaran.";
            header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
            exit();
        }
    } else {
        $_SESSION['payment_error'] = "File bukti pembayaran wajib diupload.";
        header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
        exit();
    }
    
    // Update status booking dan tambahkan data pembayaran
    $update_query = "UPDATE $booking_table SET 
                     status = 'pending_confirmation', 
                     payment_proof = ?, 
                     payment_date = ?, 
                     payment_sender = ?, 
                     payment_bank = ? 
                     WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $proof_image, $transfer_date, $sender_name, $sender_bank, $booking_id);
    
    if ($stmt->execute()) {
        $_SESSION['payment_success'] = "Bukti pembayaran berhasil diupload. Status booking Anda akan diperbarui setelah diverifikasi oleh admin.";
        header("Location: booking_history.php");
        exit();
    } else {
        $_SESSION['payment_error'] = "Gagal menyimpan data pembayaran: " . $stmt->error;
        header("Location: payment_transfer.php?id=$booking_id&type=$field_type");
        exit();
    }
} else {
    // Jika bukan method POST, redirect ke halaman booking history
    header("Location: booking_history.php");
    exit();
}
?> 