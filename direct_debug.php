<?php
include('db_connect.php');

$protected = true; // Set ke false jika ingin diakses oleh siapa saja

// Password sederhana untuk melindungi halaman ini
$debug_password = 'sportfield123';

$authenticated = false;
if (isset($_POST['debug_password']) && $_POST['debug_password'] === $debug_password) {
    $authenticated = true;
    setcookie('debug_auth', md5($debug_password), time() + 3600, '/'); // Cookie bertahan 1 jam
} elseif (isset($_COOKIE['debug_auth']) && $_COOKIE['debug_auth'] === md5($debug_password)) {
    $authenticated = true;
}

// Jika halaman dilindungi dan belum terautentikasi, tampilkan form login
if ($protected && !$authenticated) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Debug Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Debug Login</h4>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="debug_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="debug_password" name="debug_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Header HTML untuk halaman debug
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Langsung - SportField</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Direct Debug (Tanpa Login)</h4>
                <div>
                    <a href="update_database.php" class="btn btn-warning btn-sm">Update Database</a>
                    <a href="frontpage.php" class="btn btn-secondary btn-sm">Kembali ke Beranda</a>
                </div>
            </div>
            <div class="card-body">';

// Cek struktur database
echo '<h5>Tabel dalam Database:</h5>';
$tables_query = $conn->query("SHOW TABLES");
echo '<ul>';
$available_tables = [];
while ($table = $tables_query->fetch_row()) {
    echo '<li>' . $table[0] . '</li>';
    $available_tables[] = $table[0];
}
echo '</ul>';

// Lihat semua data booking dari semua tabel
$booking_tables = ['badminton_booking'];

foreach ($booking_tables as $table) {
    echo '<h5>Data Booking: ' . ucfirst(str_replace('_booking', '', $table)) . '</h5>';
    
    if (in_array($table, $available_tables)) {
        $query = "SELECT * FROM $table ORDER BY created_at DESC LIMIT 10";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-sm">';
            echo '<thead><tr>';
            
            // Tampilkan header tabel
            $fields = $result->fetch_fields();
            foreach ($fields as $field) {
                echo '<th>' . $field->name . '</th>';
            }
            echo '</tr></thead><tbody>';
            
            // Tampilkan data
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                foreach ($row as $key => $value) {
                    echo '<td>' . $value . '</td>';
                }
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>';
            
            // Tampilkan jumlah total booking
            $count_query = $conn->query("SELECT COUNT(*) as total FROM $table");
            $count_result = $count_query->fetch_assoc();
            echo '<p>Total: ' . $count_result['total'] . ' booking</p>';
        } else {
            echo '<div class="alert alert-warning">Tidak ada data booking di tabel ' . $table . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Tabel ' . $table . ' tidak ditemukan dalam database</div>';
    }
    
    echo '<hr>';
}

// Tampilkan struktur tabel fields
echo '<h5>Struktur Tabel Fields:</h5>';
if (in_array('fields', $available_tables)) {
    $structure_query = "DESCRIBE fields";
    $structure_result = $conn->query($structure_query);
    
    if ($structure_result) {
        echo '<table class="table table-bordered table-sm">';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        while ($row = $structure_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['Field'] . '</td>';
            echo '<td>' . $row['Type'] . '</td>';
            echo '<td>' . $row['Null'] . '</td>';
            echo '<td>' . $row['Key'] . '</td>';
            echo '<td>' . $row['Default'] . '</td>';
            echo '<td>' . $row['Extra'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Tampilkan data fields
        echo '<h6>Data Lapangan:</h6>';
        $fields_query = "SELECT * FROM fields";
        $fields_result = $conn->query($fields_query);
        
        if ($fields_result && $fields_result->num_rows > 0) {
            echo '<table class="table table-bordered table-sm">';
            echo '<tr>';
            $fields = $fields_result->fetch_fields();
            foreach ($fields as $field) {
                echo '<th>' . $field->name . '</th>';
            }
            echo '</tr>';
            
            while ($row = $fields_result->fetch_assoc()) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . $value . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>Tidak ada data di tabel fields</p>';
        }
    }
} else {
    echo '<div class="alert alert-danger">Tabel fields tidak ditemukan dalam database</div>';
}

// Tampilkan struktur tabel users
echo '<h5>Struktur Tabel Users:</h5>';
if (in_array('users', $available_tables)) {
    $structure_query = "DESCRIBE users";
    $structure_result = $conn->query($structure_query);
    
    if ($structure_result) {
        echo '<table class="table table-bordered table-sm">';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        while ($row = $structure_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['Field'] . '</td>';
            echo '<td>' . $row['Type'] . '</td>';
            echo '<td>' . $row['Null'] . '</td>';
            echo '<td>' . $row['Key'] . '</td>';
            echo '<td>' . $row['Default'] . '</td>';
            echo '<td>' . $row['Extra'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Tampilkan jumlah users
        $count_query = $conn->query("SELECT COUNT(*) as total FROM users");
        $count_result = $count_query->fetch_assoc();
        echo '<p>Total: ' . $count_result['total'] . ' users</p>';
    }
} else {
    echo '<div class="alert alert-danger">Tabel users tidak ditemukan dalam database</div>';
}

// Tutup HTML
echo '      </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?> 