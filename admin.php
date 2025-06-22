<?php
// admin.php
session_start();
include 'connect.php'; // file koneksi ke database

// Autentikasi admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit();
}

// Fungsi CRUD
if (isset($_POST['aksi'])) {
    if ($_POST['aksi'] == "tambah_pengguna") {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        mysqli_query($conn, "INSERT INTO pengguna (nama, email) VALUES ('$nama', '$email')");
    } elseif ($_POST['aksi'] == "hapus_pengguna") {
        $id = $_POST['id'];
        mysqli_query($conn, "DELETE FROM pengguna WHERE id=$id");
    } elseif ($_POST['aksi'] == "tambah_booking") {
        $nama = $_POST['nama'];
        $lapangan = $_POST['lapangan'];
        $tanggal = $_POST['tanggal'];
        $waktu = $_POST['waktu'];
        mysqli_query($conn, "INSERT INTO booking (nama, lapangan, tanggal, waktu, status) VALUES ('$nama', '$lapangan', '$tanggal', '$waktu', 'Terkonfirmasi')");
    } elseif ($_POST['aksi'] == "hapus_booking") {
        $id = $_POST['id'];
        mysqli_query($conn, "DELETE FROM booking WHERE id=$id");
    }
}

$pengguna = mysqli_query($conn, "SELECT * FROM pengguna");
$booking = mysqli_query($conn, "SELECT * FROM booking");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin - SportField</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.2/css/all.css">
  <style>
    .sidebar { background-color: #1e8449; min-height: 100vh; color: white; }
    .sidebar a { color: white; padding: 10px 20px; display: block; text-decoration: none; }
    .sidebar a:hover { background-color: #145a32; }
    .content { padding: 20px; }
  </style>
</head>
<body>
<nav class="navbar navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Admin - SportField</a>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 sidebar py-4">
      <a href="#dashboard">Dashboard</a>
      <a href="#bookings">Booking</a>
      <a href="#users">Pengguna</a>
      <a href="logout.php">Keluar</a>
    </div>
    <div class="col-md-10 content">
      <h3 id="dashboard">Dashboard</h3>
      <div class="row g-3">
        <div class="col-md-4"><div class="card text-white bg-primary p-3">Total Booking: <?= mysqli_num_rows($booking); ?></div></div>
        <div class="col-md-4"><div class="card text-white bg-secondary p-3">Total Pengguna: <?= mysqli_num_rows($pengguna); ?></div></div>
      </div>

      <h4 id="bookings" class="mt-5">Data Booking</h4>
      <form method="post" class="row g-2">
        <input type="hidden" name="aksi" value="tambah_booking">
        <div class="col-md-2"><input type="text" name="nama" class="form-control" placeholder="Nama" required></div>
        <div class="col-md-2"><input type="text" name="lapangan" class="form-control" placeholder="Lapangan" required></div>
        <div class="col-md-2"><input type="date" name="tanggal" class="form-control" required></div>
        <div class="col-md-2"><input type="text" name="waktu" class="form-control" placeholder="Waktu" required></div>
        <div class="col-md-2"><button type="submit" class="btn btn-success">Tambah</button></div>
      </form>
      <table class="table table-bordered mt-3">
        <thead><tr><th>Nama</th><th>Lapangan</th><th>Tanggal</th><th>Waktu</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php while ($b = mysqli_fetch_assoc($booking)): ?>
          <tr>
            <td><?= $b['nama']; ?></td>
            <td><?= $b['lapangan']; ?></td>
            <td><?= $b['tanggal']; ?></td>
            <td><?= $b['waktu']; ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="aksi" value="hapus_booking">
                <input type="hidden" name="id" value="<?= $b['id']; ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>

      <h4 id="users" class="mt-5">Data Pengguna</h4>
      <form method="post" class="row g-2">
        <input type="hidden" name="aksi" value="tambah_pengguna">
        <div class="col-md-3"><input type="text" name="nama" class="form-control" placeholder="Nama" required></div>
        <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
        <div class="col-md-2"><button type="submit" class="btn btn-success">Tambah</button></div>
      </form>
      <table class="table table-bordered mt-3">
        <thead><tr><th>Nama</th><th>Email</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($pengguna)): ?>
          <tr>
            <td><?= $p['nama']; ?></td>
            <td><?= $p['email']; ?></td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="aksi" value="hapus_pengguna">
                <input type="hidden" name="id" value="<?= $p['id']; ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
