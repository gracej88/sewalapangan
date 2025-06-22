<?php
include 'db_connect.php';

// Tampilkan header HTML dasar
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Status Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Status Pembayaran Booking</h2>';

// Periksa tabel fields
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">Data Lapangan (Fields)</h3>
    </div>
    <div class="card-body">';

// Cek apakah tabel fields ada
$check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_fields->num_rows > 0) {
    // Cek struktur kolom
    $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    $field_name_exists = $check_field_name->num_rows > 0;
    
    $check_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'name'");
    $name_exists = $check_name->num_rows > 0;
    
    if ($field_name_exists) {
        echo '<div class="alert alert-success">Kolom \'field_name\' ditemukan di tabel fields.</div>';
        $field_name_column = 'field_name';
    } elseif ($name_exists) {
        echo '<div class="alert alert-success">Kolom \'name\' ditemukan di tabel fields.</div>';
        $field_name_column = 'name';
    } else {
        echo '<div class="alert alert-danger">Kolom \'field_name\' atau \'name\' tidak ditemukan di tabel fields!</div>';
        $field_name_column = '';
    }
    
    // Tampilkan data fields
    $fields_query = "SELECT * FROM fields";
    $fields_result = $conn->query($fields_query);
    
    if ($fields_result->num_rows > 0) {
        echo '<table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lapangan</th>
                        <th>Jenis</th>
                        <th>Lokasi</th>
                        <th>Harga/Jam</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $fields_result->fetch_assoc()) {
            echo '<tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . ($field_name_exists ? $row['field_name'] : ($name_exists ? $row['name'] : 'N/A')) . '</td>
                    <td>' . ($row['field_type'] ?? 'N/A') . '</td>
                    <td>' . ($row['location'] ?? 'N/A') . '</td>
                    <td>Rp ' . number_format($row['price_per_hour'] ?? 0, 0, ',', '.') . '</td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-warning">Tidak ada data lapangan dalam tabel fields.</div>
              <a href="update_fields.php" class="btn btn-primary">Tambah Data Lapangan</a>';
    }
} else {
    echo '<div class="alert alert-danger">Tabel fields tidak ditemukan di database.</div>
          <a href="update_fields.php" class="btn btn-primary">Buat Tabel Fields</a>';
}

echo '</div></div>';

// Cek status booking di badminton_booking
$badminton_query = "SELECT * FROM badminton_booking ORDER BY id DESC";
$badminton_result = $conn->query($badminton_query);

echo '<h3 class="mt-4">Badminton Booking</h3>';
if ($badminton_result && $badminton_result->num_rows > 0) {
    echo '<table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Lapangan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>';
    
    while ($row = $badminton_result->fetch_assoc()) {
        echo '<tr>
                <td>' . $row['id'] . '</td>
                <td>' . $row['field'] . '</td>
                <td>' . $row['booking_date'] . ' ' . $row['booking_time'] . '</td>
                <td><span class="badge bg-' . ($row['status'] == 'pending_confirmation' ? 'warning' : 'primary') . '">' . $row['status'] . '</span></td>
                <td>
                    <form method="post" action="change_status.php" style="display:inline;">
                        <input type="hidden" name="booking_id" value="' . $row['id'] . '">
                        <input type="hidden" name="booking_table" value="badminton_booking">
                        <input type="hidden" name="new_status" value="pending_confirmation">
                        <button type="submit" class="btn btn-sm btn-warning">Set Pending Confirmation</button>
                    </form>
                </td>
            </tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info">Tidak ada data booking badminton.</div>';
}

echo '<div class="mt-4">
    <a href="admin_verify_payment.php" class="btn btn-success me-2">Pergi ke Halaman Verifikasi Admin</a>
    <a href="booking_history.php" class="btn btn-primary me-2">Lihat Riwayat Booking</a>
    <a href="update_fields.php" class="btn btn-warning">Update Data Lapangan</a>
</div>';

echo '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?> 