<?php
session_start(); // Mulai sesi

// Masukkan file koneksi ke database
include('db_connect.php');

// Cek apakah form sudah disubmit
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query untuk mencari user berdasarkan email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set session data jika login berhasil
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['id'] = $user['id'];
            $_SESSION['login_type'] = $user['admin'] ?? 0; // 0 untuk user biasa, 1 untuk admin
            
            // Redirect ke halaman depan setelah login
            header('Location: frontpagelogin.php');
            exit();
        } else {
            $error = "Email atau password salah.";
        }
    } else {
        $error = "Email tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Field Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-blue-100">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-96">
        <h2 class="text-2xl font-bold text-blue-700 text-center mb-6">Login</h2>
        
        <!-- Menampilkan error jika login gagal -->
        <?php if (!empty($error)): ?>
        <div class="mb-4 text-red-500 text-center"><?= $error ?></div>
        <?php endif; ?>

        <!-- Form login -->
        <form action="" method="POST">
            <div class="mb-4">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400"
                />
            </div>
            <div class="mb-6">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400"
                />
            </div>
            <button
                type="submit"
                class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition"
            >
                Log in
            </button>
        </form>
        <div class="text-center mt-4 text-sm">
            Don't have an account?
            <a href="signup.php" class="text-blue-500 hover:underline">Sign up</a>
        </div>
    </div>
</body>
</html>
