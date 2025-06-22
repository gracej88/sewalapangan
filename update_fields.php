<?php
session_start();
include('db_connect.php');

// Cek parameter format output
$output_json = isset($_GET['json']);

// Jika output bukan JSON, set header untuk HTML
if (!$output_json) {
    header('Content-Type: text/html');
} else {
    // Set header untuk JSON
    header('Content-Type: application/json');
}

// Cek apakah tabel fields sudah ada
$check_fields = $conn->query("SHOW TABLES LIKE 'fields'");
if ($check_fields->num_rows == 0) {
    // Buat tabel fields
    $create_fields = "CREATE TABLE IF NOT EXISTS fields (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        field_name VARCHAR(100) NOT NULL,
        field_type ENUM('badminton') NOT NULL,
        location VARCHAR(100) DEFAULT NULL,
        price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_fields)) {
        // Tabel berhasil dibuat, tambahkan data
        $data_created = true;
        $message = "Tabel fields berhasil dibuat";
    } else {
        $message = "Gagal membuat tabel fields: " . $conn->error;
        if ($output_json) {
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
} else {
    // Tabel sudah ada, cek struktur kolom
    $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    $field_name_exists = $check_field_name->num_rows > 0;
    
    $check_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'name'");
    $name_exists = $check_name->num_rows > 0;
    
    // Pilih nama kolom yang benar
    if ($field_name_exists) {
        $field_name_column = 'field_name';
        $message = "Menggunakan kolom 'field_name'";
    } elseif ($name_exists) {
        $field_name_column = 'name';
        $message = "Menggunakan kolom 'name'";
    } else {
        // Tambahkan kolom field_name jika tidak ada
        $add_column = $conn->query("ALTER TABLE fields ADD COLUMN field_name VARCHAR(100) NOT NULL AFTER id");
        $field_name_column = 'field_name';
        $message = "Menambahkan kolom 'field_name' ke tabel";
    }
    
    // Cek jumlah data
    $count_result = $conn->query("SELECT COUNT(*) AS total FROM fields");
    $count_data = $count_result->fetch_assoc();
    
    if ($count_data['total'] == 0) {
        // Tabel kosong, tambahkan data
        $data_created = true;
        $message .= " | Tabel kosong, akan menambahkan data";
    } else {
        $message .= " | Tabel sudah berisi {$count_data['total']} data";
    }
}

// Tambahkan data jika perlu
if (isset($data_created) && $data_created) {
    // Data lapangan untuk dimasukkan
    $fields_data = [
        ['Lapangan Futsal A', 'futsal', 'Jakarta Selatan', 150000],
        ['Lapangan Futsal B', 'futsal', 'Jakarta Selatan', 175000],
        ['Lapangan Futsal C', 'futsal', 'Jakarta Selatan', 200000],
        ['Lapangan_B', 'badminton', 'Jakarta Pusat', 75000],
        ['Lapangan_C', 'badminton', 'Jakarta Pusat', 80000],
        ['Lapangan Tennis A', 'tennis', 'Jakarta Barat', 200000],
        ['Lapangan Tennis B', 'tennis', 'Jakarta Barat', 225000]
    ];
    
    // Buat prepared statement
    $insert_stmt = $conn->prepare("INSERT INTO fields (field_name, field_type, location, price_per_hour) VALUES (?, ?, ?, ?)");
    
    if (!$insert_stmt) {
        $message = "Gagal mempersiapkan statement: " . $conn->error;
        if ($output_json) {
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
    
    // Masukkan data satu per satu
    $success_count = 0;
    foreach ($fields_data as $field) {
        $insert_stmt->bind_param("sssd", $field[0], $field[1], $field[2], $field[3]);
        if ($insert_stmt->execute()) {
            $success_count++;
        }
    }
    
    // Tutup statement
    $insert_stmt->close();
    
    $message = "Berhasil menambahkan $success_count data lapangan";
    if ($output_json) {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'total_data' => $success_count
        ]);
        exit;
    }
} else {
    // Update harga untuk lapangan yang sudah ada
    $fields_to_update = [
        ['Lapangan Futsal A', 150000],
        ['Lapangan_B', 75000],
        ['Lapangan_C', 80000],
        ['Lapangan Tennis A', 200000]
    ];
    
    // Gunakan nama kolom yang sudah dideteksi
    $update_stmt = $conn->prepare("UPDATE fields SET price_per_hour = ? WHERE $field_name_column = ?");
    
    if (!$update_stmt) {
        $message = "Gagal mempersiapkan statement update: " . $conn->error;
        if ($output_json) {
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
    
    $updated_count = 0;
    foreach ($fields_to_update as $field) {
        $update_stmt->bind_param("ds", $field[1], $field[0]);
        if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
            $updated_count++;
        }
    }
    
    $update_stmt->close();
    
    $message = "Berhasil update $updated_count harga lapangan";
    if ($output_json) {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updated' => $updated_count
        ]);
        exit;
    }
}

// Jika bukan output JSON, tampilkan halaman HTML
if (!$output_json):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Data Lapangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4>Update Data Lapangan</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
                
                <h5 class="mt-4">Data Lapangan Saat Ini:</h5>
                <?php
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
                        Tidak ada data lapangan.
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="update_status.php" class="btn btn-warning me-2">Update Status Booking</a>
                    <a href="check_fields.php" class="btn btn-info me-2">Cek Data Lapangan</a>
                    <a href="booking_history.php" class="btn btn-primary">Kembali ke Riwayat Booking</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?> 