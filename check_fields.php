<?php
session_start();
include('db_connect.php');

// Cek apakah tabel fields ada
$has_fields = $conn->query("SHOW TABLES LIKE 'fields'")->num_rows > 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Data Lapangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Data Lapangan</h2>
        
        <?php if (!$has_fields): ?>
            <div class="alert alert-warning">
                Tabel fields belum ada di database.
                <a href="update_fields.php" class="btn btn-primary btn-sm ms-3">Buat Tabel Fields</a>
            </div>
        <?php else: ?>
            <?php
            // Cek struktur kolom
            $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
            $field_name_exists = $check_field_name->num_rows > 0;
            
            $check_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'name'");
            $name_exists = $check_name->num_rows > 0;
            
            // Tampilkan info struktur
            if ($field_name_exists) {
                echo "<div class='alert alert-info'>Kolom 'field_name' ditemukan.</div>";
                $field_name_column = 'field_name';
            } elseif ($name_exists) {
                echo "<div class='alert alert-info'>Kolom 'name' ditemukan.</div>";
                $field_name_column = 'name';
            } else {
                echo "<div class='alert alert-danger'>Kolom 'field_name' dan 'name' tidak ditemukan!</div>";
                $field_name_column = '';
            }
            
            // Ambil data fields
            $fields_query = "SELECT * FROM fields";
            $fields_result = $conn->query($fields_query);
            
            if ($fields_result->num_rows > 0):
            ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lapangan</th>
                        <th>Jenis</th>
                        <th>Lokasi</th>
                        <th>Harga Per Jam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $fields_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php 
                            if ($field_name_exists) {
                                echo $row['field_name'];
                            } elseif ($name_exists) {
                                echo $row['name'];
                            } else {
                                echo "[Tidak ada kolom nama]";
                            }
                        ?></td>
                        <td><?php echo $row['field_type'] ?? ''; ?></td>
                        <td><?php echo $row['location'] ?? ''; ?></td>
                        <td>Rp <?php echo number_format($row['price_per_hour'] ?? 0, 0, ',', '.'); ?></td>
                        <td><?php echo $row['status'] ?? ''; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="alert alert-warning">
                    Tabel fields sudah ada tetapi tidak ada data.
                    <a href="update_fields.php" class="btn btn-primary btn-sm ms-3">Tambah Data Lapangan</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="update_status.php" class="btn btn-warning me-2">Update Status Booking</a>
            <a href="update_fields.php" class="btn btn-success me-2">Update Data Lapangan</a>
            <a href="booking_history.php" class="btn btn-primary me-2">Kembali ke Riwayat Booking</a>
        </div>
    </div>
</body>
</html> 