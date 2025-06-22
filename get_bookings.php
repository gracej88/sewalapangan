<?php
include('db_connect.php');
header('Content-Type: application/json');

$type = isset($_GET['type']) ? $_GET['type'] : '';
$bookings = [];

if (!empty($type)) {
    $table = '';
    
    switch ($type) {
        case 'badminton':
            $table = 'badminton_booking';
            break;
        default:
            echo json_encode(['error' => 'Tipe booking tidak valid']);
            exit;
    }
    
    $query = "SELECT id, booking_date, booking_time, field, customer_name, status FROM $table ORDER BY booking_date DESC, booking_time DESC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
}

echo json_encode($bookings);
?> 