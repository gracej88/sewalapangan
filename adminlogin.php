<?php
session_start(); // Mulai sesi

// Masukkan file koneksi ke database
include('db_connect.php');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ? AND login_type = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['login_type'] = $user['login_type'];
            
            // Also set general user session variables for compatibility
            $_SESSION['id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect to admin dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = 'Password salah';
        }
    } else {
        $error_message = 'Email tidak ditemukan atau bukan akun admin';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-green-100">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-96">
        <h2 class="text-2xl font-bold text-green-700 text-center mb-6">Login Admin</h2>
        
        <!-- Menampilkan error jika login gagal -->
        <?php if (!empty($error_message)): ?>
        <div class="mb-4 text-red-500 text-center"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Form login -->
        <form action="" method="POST">
            <div class="mb-4">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-400"
                />
            </div>
            <div class="mb-6">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-400"
                />
            </div>
            <button
                type="submit"
                class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition"
            >
                Log in
            </button>
        </form>
        <div class="text-center mt-4 text-sm">
            <a href="frontpage.php" class="text-green-500 hover:underline">Kembali ke halaman utama</a>
        </div>
        <div class="text-center mt-2 text-sm">
            <a href="create_admin.php" class="text-blue-500 hover:underline">Buat Akun Admin Baru</a>
        </div>
    </div>
</body>
</html>
