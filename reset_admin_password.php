<?php
include('db_connect.php');

// Data admin yang akan diupdate
$admin_username = 'gilang'; // Sesuaikan dengan username admin yang ada
$new_password = 'gilang213'; // Password baru (sama dengan yang di database)

// Update password admin (tanpa hash)
$query = "UPDATE admins SET password = ? WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $new_password, $admin_username);

if ($stmt->execute()) {
    echo "✅ Password admin berhasil diupdate!<br>";
    echo "Username: <b>$admin_username</b><br>";
    echo "Password: <b>$new_password</b> (tanpa hash)<br>";
    echo "<p style='color:red'>Catatan: Gunakan password ini untuk login.</p>";
} else {
    echo "❌ Gagal mengupdate password: " . $conn->error . "<br>";
}

echo "<br><a href='adminlogin.php' style='background:#1e8449; color:white; padding:10px 15px; text-decoration:none; border-radius:4px;'>Login sebagai Admin</a>";
?> 