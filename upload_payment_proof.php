<?php
session_start();
include('db_connect.php');

// Cek apakah ada data yang disubmit
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['error'] = "Metode request tidak valid";
    header("Location: frontpage.php");
    exit();
}

// Ambil data dari form
$booking_id = $_POST['booking_id'] ?? '';
$booking_type = $_POST['booking_type'] ?? '';

// Validasi data
if (empty($booking_id) || empty($booking_type)) {
    $_SESSION['error'] = "Data booking tidak lengkap";
    header("Location: frontpage.php");
    exit();
}

// Validasi tipe booking
$valid_types = ['badminton'];
if (!in_array($booking_type, $valid_types)) {
    $_SESSION['error'] = "Tipe booking tidak valid";
    header("Location: frontpage.php");
    exit();
}

// Tentukan tabel berdasarkan tipe booking
$booking_table = $booking_type . '_booking';

// Cek apakah booking ada
$check_query = "SELECT * FROM $booking_table WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $booking_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Data booking tidak ditemukan";
    header("Location: frontpage.php");
    exit();
}

$booking_data = $result->fetch_assoc();

// Proses upload file
$upload_dir = 'uploads/payment_proof/';

// Buat direktori jika belum ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$payment_proof_path = '';

if (isset($_FILES['paymentProof']) && $_FILES['paymentProof']['error'] === 0) {
    // Validasi ukuran file (maksimal 2MB)
    if ($_FILES['paymentProof']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Ukuran file terlalu besar (maksimal 2MB)";
        header("Location: BCA_$booking_type.php?id=$booking_id");
        exit();
    }
    
    // Validasi tipe file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES['paymentProof']['type'], $allowed_types)) {
        $_SESSION['error'] = "Tipe file tidak didukung (hanya JPG, PNG)";
        header("Location: BCA_$booking_type.php?id=$booking_id");
        exit();
    }
    
    // Generate nama file unik
    $file_extension = pathinfo($_FILES['paymentProof']['name'], PATHINFO_EXTENSION);
    $file_name = $booking_type . '_' . $booking_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $file_name;
    
    // Pindahkan file yang diupload
    if (move_uploaded_file($_FILES['paymentProof']['tmp_name'], $target_file)) {
        $payment_proof_path = $target_file;
    } else {
        $_SESSION['error'] = "Gagal mengupload file";
        header("Location: BCA_$booking_type.php?id=$booking_id");
        exit();
    }
} else {
    $_SESSION['error'] = "Bukti pembayaran diperlukan";
    header("Location: BCA_$booking_type.php?id=$booking_id");
    exit();
}

// Update data booking dengan informasi pembayaran
$payment_method = $_POST['payment_method'] ?? 'BCA';

$update_query = "UPDATE $booking_table SET 
                payment_proof = ?,
                payment_date = NOW(), 
                payment_method = ?,
                status = 'pending_confirmation' 
                WHERE id = ?";

$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ssi", 
                        $payment_proof_path, 
                        $payment_method, 
                        $booking_id);

if ($update_stmt->execute()) {
    $_SESSION['success'] = "Bukti pembayaran berhasil diupload. Tim kami akan memverifikasi pembayaran Anda segera.";
    
    // Kirim email notifikasi ke admin (opsional, bisa diimplementasikan nanti)
    
    header("Location: order_success.php?id=$booking_id&type=$booking_type");
    exit();
} else {
    $_SESSION['error'] = "Gagal menyimpan data pembayaran: " . $conn->error;
    header("Location: BCA_$booking_type.php?id=$booking_id");
    exit();
}
?> 