<?php
session_start();
include('db_connect.php');

// Ambil parameter tipe lapangan dari URL
$field_type = isset($_GET['type']) ? $_GET['type'] : '';

// Cek apakah tipe lapangan valid
if (empty($field_type) || !in_array($field_type, ['badminton'])) {
    echo json_encode(['error' => 'Tipe lapangan tidak valid']);
    exit;
}

// Cek kolom dalam tabel fields
function checkFieldColumns($conn) {
    $result = $conn->query("SHOW COLUMNS FROM fields");
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

// Cek apakah tabel fields ada
$check_table = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_table->num_rows == 0) {
    echo json_encode(['error' => 'Tabel fields tidak ditemukan']);
    exit;
}

// Ambil data lapangan berdasarkan jenis
$field_columns = checkFieldColumns($conn);
$name_column = in_array('field_name', $field_columns) ? 'field_name' : 'name';
$type_column = in_array('field_type', $field_columns) ? 'field_type' : 'type';

// Query untuk mengambil data lapangan berdasarkan tipe
$query = "SELECT * FROM fields WHERE $type_column = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $field_type);
$stmt->execute();
$result = $stmt->get_result();

$fields = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Konsistenkan nama kolom
        $field = [
            'id' => $row['id'],
            'name' => isset($row[$name_column]) ? $row[$name_column] : $row['name'],
            'type' => isset($row[$type_column]) ? $row[$type_column] : $row['type'],
            'location' => $row['location'] ?? '',
            'price_per_hour' => $row['price_per_hour'],
            'status' => $row['status'] ?? 'available'
        ];
        
        // Fungsi untuk cek apakah lapangan sedang digunakan pada saat ini
        $field_check = isFieldCurrentlyOccupied($field['name'], $conn);
        if ($field_check['occupied']) {
            $field['status'] = 'occupied';
            $field['occupied_until'] = $field_check['end_time'];
        }
        
        $fields[] = $field;
    }
}

// Fungsi untuk cek apakah lapangan sedang digunakan
function isFieldCurrentlyOccupied($field_name, $conn) {
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Tabel booking sesuai dengan jenis lapangan
    $booking_tables = ['badminton_booking'];
    
    foreach ($booking_tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            $query = "SELECT * FROM $table WHERE field = ? AND booking_date = ? AND status = 'confirmed'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $field_name, $current_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Tambahkan 2 jam ke booking_time untuk mendapatkan waktu akhir (asumsi booking 2 jam)
                    $booking_time = strtotime($row['booking_time']);
                    $end_time = date('H:i:s', strtotime('+2 hours', $booking_time));
                    
                    // Cek apakah waktu saat ini berada di antara waktu booking dan waktu akhir
                    if ($current_time >= $row['booking_time'] && $current_time <= $end_time) {
                        return ['occupied' => true, 'end_time' => $end_time];
                    }
                }
            }
        }
    }
    
    return ['occupied' => false];
}

// Mengembalikan data dalam format JSON
header('Content-Type: application/json');
echo json_encode($fields);
?> 