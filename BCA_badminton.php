<?php
// Mulai session untuk menyimpan data antar halaman
session_start();
include('db_connect_badminton.php');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    $sql = "SELECT id FROM badminton_booking ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $id = $row['id'];
    } else {
        die("Tidak ada data pemesanan.");
    }
}

// 👉 Save the selected ID to session
$_SESSION['selected_booking_id'] = $id;


// Inisialisasi variabel default
$booking_date = "";
$booking_time = "";
$field_name = "";
$customer_name = "";
$customer_phone = "";
$customer_email = "";
$notes = "";
$show_success = false;

// Jika ID tersedia, ambil data dari database
if (!empty($id)) {
    $stmt = $conn->prepare("SELECT * FROM badminton_booking WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();

        $booking_date = date('j F Y', strtotime($data['booking_date']));
        $booking_time = date('H:i', strtotime($data['booking_time'])) . " - " . date('H:i', strtotime($data['booking_time'] . ' +1 hour'));
        $field_name = $data['field'];
        $customer_name = $data['customer_name'];
        $customer_phone = $data['customer_phone'];
        $customer_email = $data['customer_email'];
        $notes = $data['notes'] ?? '';
        $show_success = false;
    } else {
        echo "Data booking tidak ditemukan.";
    }
}

// Harga dan biaya
$field_price = 100000;
$admin_fee = 2500;

// Dapatkan harga dari database berdasarkan lapangan yang dipilih
if (!empty($field_name)) {
    // Query untuk mendapatkan harga dari database - gunakan hanya field_name
    $price_query = "SELECT price_per_hour FROM fields WHERE field_name = ?";
    $stmt_price = $conn->prepare($price_query);
    if ($stmt_price) {
        $stmt_price->bind_param("s", $field_name);
        $stmt_price->execute();
        $price_result = $stmt_price->get_result();
        
        if ($price_result && $price_result->num_rows > 0) {
            $price_data = $price_result->fetch_assoc();
            $field_price = $price_data['price_per_hour'];
        }
        $stmt_price->close();
    } else {
        // Jika query gagal, coba query langsung
        $direct_query = "SELECT price_per_hour FROM fields WHERE field_name = '$field_name' LIMIT 1";
        $result_direct = $conn->query($direct_query);
        if ($result_direct && $result_direct->num_rows > 0) {
            $direct_data = $result_direct->fetch_assoc();
            $field_price = $direct_data['price_per_hour'];
        }
    }
}

$total_price = $field_price + $admin_fee;

// Cek apakah ada data yang dikirim dari futsal_booking.php
if (isset($_POST['submit_booking']) || isset($_SESSION['booking_data'])) {
    // Ambil data dari POST jika tersedia, atau dari session jika tidak
    if (isset($_POST['submit_booking'])) {
        // Simpan data booking ke session
        $_SESSION['booking_data'] = $_POST;
    }
    
    // Ambil data dari session
    $booking_data = $_SESSION['booking_data'];
    
    // Set variabel dengan data dari booking sebelumnya
    if (isset($booking_data['booking_date'])) $booking_date = $booking_data['booking_date'];
    if (isset($booking_data['booking_time'])) $booking_time = $booking_data['booking_time'];
    if (isset($booking_data['field_name'])) $field_name = $booking_data['field_name'];
    if (isset($booking_data['customer_name'])) $customer_name = $booking_data['customer_name'];
    if (isset($booking_data['customer_phone'])) $customer_phone = $booking_data['customer_phone'];
    if (isset($booking_data['customer_email'])) $customer_email = $booking_data['customer_email'];
    if (isset($booking_data['notes'])) $notes = $booking_data['notes'];
    
    // Dapatkan harga dari database berdasarkan nama lapangan yang diupdate
    if (!empty($field_name)) {
        $price_query = "SELECT price_per_hour FROM fields WHERE field_name = ?";
        $stmt_price = $conn->prepare($price_query);
        if ($stmt_price) {
            $stmt_price->bind_param("s", $field_name);
            $stmt_price->execute();
            $price_result = $stmt_price->get_result();
            
            if ($price_result && $price_result->num_rows > 0) {
                $price_data = $price_result->fetch_assoc();
                $field_price = $price_data['price_per_hour'];
            }
            $stmt_price->close();
        }
    }
    
    // Hitung total pembayaran
    $total_price = $field_price + $admin_fee;
}

// Jika ada data POST untuk proses pembayaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay'])) {
    // Ambil metode pembayaran yang dipilih
    $payment_method = $_POST['paymentMethod'] ?? 'BCA';
    
    // Simpan data booking dan metode pembayaran ke session
    $_SESSION['payment_info'] = [
        'booking_id' => $id,
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'total_price' => $total_price,
        'field_name' => $field_name,
        'booking_date' => $booking_date,
        'booking_time' => $booking_time,
        'file' =>  $file
    ];
    
    // Redirect ke halaman pembayaran sesuai metode yang dipilih
    switch ($payment_method) {
        case 'BCA':
            header("Location: BCA.php");
            exit;
        case 'OVO':
            header("Location: OVO.html");
            exit;
        case 'QRIS':
            header("Location: QRIS.html");
            exit;
        default:
            header("Location: BCA.php");
            exit;
    }
}

// Format harga ke format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay'])) {
  // Pastikan semua variabel sudah diset
  $team_name = $_POST['team_name'] ?? ''; // Atau ambil dari sesi jika sebelumnya disimpan
  $file = ''; // Default jika tidak ada

  if (isset($_FILES['paymentProof']) && $_FILES['paymentProof']['error'] === 0) {
      $upload_dir = 'uploads/';
      $file_name = basename($_FILES['paymentProof']['name']);
      $target_file = $upload_dir . $file_name;
      if (move_uploaded_file($_FILES['paymentProof']['tmp_name'], $target_file)) {
          $file = $target_file;
      }
  }

  // Simpan ke database
  $stmt = $conn->prepare("INSERT INTO bukti_bayar (booking_date, booking_time, field, customer_name, customer_phone, customer_email, team_name, notes, status, file)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'booked', ?)");
  $stmt->bind_param("sssssssss", $booking_date, $booking_time, $field_name, $customer_name, $customer_phone, $customer_email, $team_name, $notes, $file);
  $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SportField - Pembayaran BCA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    :root {
        --primary-blue: #1e4e84;
        --secondary-blue: #2e86c1;
        --light-blue: #aed6f1;
        --very-light-blue:rgb(213, 238, 247);
        --dark-blue:  #00008B;
        --white: #ffffff;
      }

    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-dark);
      background-color: var(--white);
    }
    
    .bg-custom-green {
      background-color: #1e4e84;
    }

    .bg-custom-light {
      background-color: #e8f7e2;
    }

    .btn-custom-green {
      background-color:  #2e86c1;
      color: white;
      border: none;
      box-shadow: 0 4px 6px rgb(213, 238, 247);
      transition: all 0.3s;
    }

    .btn-custom-green:hover {
      background-color:  #2e86c1;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(126, 217, 87, 0.3);
    }

    .navbar {
        background-color: var(--primary-blue);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .navbar-brand {
        font-weight: 700;
        font-size: 1.6rem;
        color: var(--white);
        letter-spacing: 1px;
      }

      .nav-link {
        color: var(--white);
        font-weight: 500;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
      }

      .nav-link:hover {
        color: var(--light-blue);
        transform: translateY(-2px);
      }
      
      /* Tambahkan style untuk class active agar tetap putih */
      .nav-link.active {
        color: var(--white) !important;
        font-weight: 600;
      }
      
      /* Tambahan style untuk dropdown user */
      .user-dropdown .dropdown-toggle::after {
        display: none;
      }
      
      .user-dropdown .dropdown-toggle {
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        background-color: rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
      }
      
      .user-dropdown .dropdown-toggle:hover {
        background-color: rgba(255, 255, 255, 0.3);
      }
      
      .user-avatar {
        width: 32px;
        height: 32px;
        background-color: var(--primary-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-weight: bold;
      }

    .custom-card {
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      border: none;
      transition: all 0.3s;
    }

    .card-header-custom {
      background-color: #2e86c1;
      color: white;
      border-bottom: none;
      padding: 15px 20px;
    }

    footer {
      padding: 50px 0 20px;
      background-color: var(--very-light-blue);
      border-top: 5px solid var(--secondary-blue);
    }

    .bank-details {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
    }

    .payment-logo {
      height: 50px;
      margin-bottom: 20px;
    }

    .countdown {
      font-size: 1.5rem;
      font-weight: bold;
      color: #dc3545;
    }

    .copy-button {
      cursor: pointer;
      color: #1e8449;
      transition: all 0.3s;
    }

    .copy-button:hover {
      color: #186a3b;
    }

    .payment-steps {
      counter-reset: step-counter;
      list-style-type: none;
      padding-left: 0;
    }

    .payment-steps li {
      counter-increment: step-counter;
      margin-bottom: 15px;
      padding-left: 45px;
      position: relative;
    }

    .payment-steps li::before {
      content: counter(step-counter);
      background-color: #1e8449;
      color: white;
      font-weight: bold;
      font-size: 0.9rem;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      position: absolute;
      left: 0;
      top: 0;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
      <div class="container">
        <a class="navbar-brand" href="#">
          <i class="fa-solid fa-futbol me-2"></i>
          SportField
        </a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav mx-auto">
            <li class="nav-item">
              <a class="nav-link active" href="frontpage.php">Beranda</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#jenislapangan">Pesan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#carouselExampleIndicators">Lapangan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#kontak">Kontak</a>
            </li>
          </ul>
          
          <?php if(isset($_SESSION['user'])): ?>
          <!-- Jika sudah login -->
          <div class="user-dropdown dropdown">
            <button class="btn dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="user-avatar">
                <i class="fas fa-user"></i>
              </div>
              <span class="d-none d-md-inline">
                <?php 
                  // Perbaikan: Cek apakah user adalah array
                  if(is_array($_SESSION['user'])) {
                    echo $_SESSION['user']['name'] ?? 'User';
                  } else {
                    echo $_SESSION['user'];
                  }
                ?>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <!-- <li><a class="dropdown-item" href="booking_history.php"><i class="fas fa-history me-2"></i>Riwayat Pemesanan</a></li> -->
              <!-- <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>Profil Saya</a></li> -->
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </div>
          <?php else: ?>
          <!-- Jika belum login -->
          <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-outline-light">Masuk</a>
            <a href="signup.php" class="btn btn-light">Daftar</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </nav>

  <div class="container my-5">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center mb-4">
        <h2 class="fw-bold">Pembayaran BCA</h2>
        <p class="lead text-muted">Silahkan selesaikan pembayaran Anda</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8 mb-4">
        <div class="custom-card shadow-sm">
          <div class="card-header card-header-custom">
            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Detail Pembayaran BCA</h5>
          </div>
          <div class="card-body p-4">
            <div class="text-center mb-4">
              <img src="Logo BCA_Biru.png" alt="BCA" class="payment-logo">
              <h5>Transfer Bank BCA</h5>
              <p>Selesaikan pembayaran Anda dengan transfer bank BCA</p>
              <div class="alert alert-warning">
                <i class="fas fa-clock me-2"></i>Batas waktu pembayaran: <span class="countdown" id="countdown">59:59</span>
              </div>
            </div>

            <div class="bank-details mb-4">
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Nominal Transfer</div>
                <div class="col-md-8 fw-bold" id="nominal"><?php echo formatRupiah($total_price); ?></div>
              </div>
              <hr>
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Nama Rekening</div>
                <div class="col-md-8">Suwandy</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Nomor Rekening</div>
                <div class="col-md-8 d-flex align-items-center">
                  <span class="fw-bold me-2" id="rekening">8000789136</span>
                  <span class="copy-button" onclick="copyToClipboard('rekening')">
                    <i class="far fa-copy"></i> Salin
                  </span>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h6 class="mb-3">Cara Pembayaran:</h6>
              <ul class="payment-steps">
                <li>Buka aplikasi BCA Mobile atau Internet Banking BCA</li>
                <li>Pilih menu Transfer > Ke Rekening BCA</li>
                <li>Masukkan nomor rekening <strong>8000789136</strong> (Suwandy)</li>
                <li>Masukkan nominal transfer sesuai dengan total pembayaran</li>
                <li>Periksa kembali detail transaksi dan konfirmasi pembayaran</li>
                <li>Simpan bukti pembayaran Anda</li>
              </ul>
            </div>

            <div class="mb-4">
              <h6 class="mb-3">Konfirmasi Pembayaran:</h6>
              <form id="paymentConfirmForm" action="upload_payment_proof.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="booking_type" value="badminton">
                <div class="mb-3">
                  <label for="paymentProof" class="form-label">Upload Bukti Pembayaran</label>
                  <input type="file" class="form-control" id="paymentProof" name="paymentProof" accept="image/*" required>
                  <small class="text-muted">Upload gambar bukti transfer (JPG, PNG, maksimal 2MB)</small>
                </div>
                <button type="submit" class="btn btn-custom-green w-100">Konfirmasi Pembayaran</button>
              </form>
            </div>

            <div class="text-center mt-4">
              <a href="frontpage.php" class="btn btn-outline-secondary">Kembali ke Beranda</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mb-4">
        <div class="custom-card shadow-sm">
          <div class="card-header card-header-custom">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Booking</h5>
          </div>
          <div class="card-body p-4">
            <div class="mb-3 pb-3 border-bottom">
              <div class="text-muted mb-1">Nomor Booking</div>
              <div class="fw-bold"><?php echo htmlspecialchars($id); ?></div>
            </div>
            <div class="mb-3 pb-3 border-bottom">
              <div class="text-muted mb-1">Lapangan</div>
              <div class="fw-bold"><?php echo htmlspecialchars($field_name); ?></div>
            </div>
            <div class="mb-3 pb-3 border-bottom">
              <div class="text-muted mb-1">Tanggal & Waktu</div>
              <div class="fw-bold"><?php echo htmlspecialchars($booking_date); ?>, <?php echo htmlspecialchars($booking_time); ?></div>
            </div>
            <div class="mb-3 pb-3 border-bottom">
              <div class="text-muted mb-1">Pemesan</div>
              <div class="fw-bold"><?php echo htmlspecialchars($customer_name); ?></div>
            </div>
            <div class="mb-3">
              <div class="text-muted mb-1">Total Pembayaran</div>
              <div class="fw-bold text-success" id="totalPayment"><?php echo formatRupiah($total_price); ?></div>
            </div>

            <div class="alert alert-info mt-4 small" role="alert">
              <i class="fas fa-info-circle me-2"></i>
              Detail booking boleh dikirim melalui nomor Whatsapp dibawah ini.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Fungsi untuk menyalin ke clipboard
    function copyToClipboard(elementId) {
      const text = document.getElementById(elementId).innerText;
      navigator.clipboard.writeText(text).then(() => {
        alert('Tersalin ke clipboard!');
      });
    }

    // Countdown timer
    function startCountdown() {
      let minutes = 59;
      let seconds = 59;
      
      const timer = setInterval(() => {
        if (seconds === 0) {
          if (minutes === 0) {
            clearInterval(timer);
            alert('Waktu pembayaran habis. Silakan lakukan pemesanan ulang.');
            window.location.href = 'badminton_booking.php';
            return;
          }
          minutes--;
          seconds = 59;
        } else {
          seconds--;
        }
        
        document.getElementById('countdown').textContent = 
          `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
      }, 1000);
    }

    // Mulai countdown saat halaman dimuat
    window.onload = function() {
      startCountdown();
    }
  </script>
</body>
</html>