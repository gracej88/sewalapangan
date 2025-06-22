<?php
session_start();
include('db_connect.php');

// Set header untuk output sebagai JSON
header('Content-Type: application/json');

// Coba update status di tabel badminton_booking
$badminton_result = $conn->query("UPDATE badminton_booking SET status='pending_confirmation' WHERE status='booked'");
$badminton_count = $conn->affected_rows;

// Siapkan response
$response = [
    'success' => true,
    'message' => 'Status berhasil diupdate',
    'updated' => [
        'badminton' => $badminton_count,
    ],
    'total' => $badminton_count
];

// Output response sebagai JSON
echo json_encode($response);
?> 