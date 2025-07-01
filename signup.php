<?php
if (isset($_POST['name'], $_POST['email'], $_POST['password'])) {
    include('db_connect.php');
    
    // Ambil data dari form
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Query untuk memasukkan data ke tabel users
    $sql = "INSERT INTO users (name, email, password, admin) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();
    
    // Redirect ke halaman login setelah signup berhasil
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Sign Up - Field Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="flex items-center justify-center min-h-screen bg-blue-100">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-96">
      <h2 class="text-2xl font-bold text-blue-700 text-center mb-6">
        Sign Up
      </h2>
      <!-- Form Sign Up -->
      <?php if (!empty($error)): ?>
      <div class="mb-4 text-red-500 text-center"><?= $error ?></div>
      <?php endif; ?>
      <form action="" method="POST">
        <div class="mb-4">
          <label>Nama</label>
          <input
            type="text"
            name="name"
            required
            class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400"
          />
        </div>
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
          Sign Up
        </button>
      </form>
      <div class="text-center mt-4 text-sm">
        Sudah punya akun?
        <a href="login.php" class="text-blue-500 hover:underline">Login</a>
      </div>
    </div>
  </body>
</html>
