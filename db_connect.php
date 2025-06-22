<?php
$host = 'localhost'; // Nama host (misal localhost)
$username = 'root'; // Username database
$password = ''; // Password database
$dbname = 'field_booking'; // Nama database yang sudah dibuat

// Membuat koneksi
$conn = new mysqli($host, $username, $password, $dbname);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
