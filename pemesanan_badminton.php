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
$field_price = 80000; // Default price
$admin_fee = 2500;

// Dapatkan harga dari database berdasarkan lapangan yang dipilih
if (!empty($field_name)) {
    // Periksa apakah tabel menggunakan field_name atau name
    $check_field_name = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_name'");
    if ($check_field_name->num_rows > 0) {
        $field_col = 'field_name';
    } else {
        $field_col = 'name';
    }
    
    // Cek apakah tabel menggunakan field_type atau type
    $check_field_type = $conn->query("SHOW COLUMNS FROM fields LIKE 'field_type'");
    if ($check_field_type->num_rows > 0) {
        $type_col = 'field_type';
    } else {
        $type_col = 'type';
    }
    
    // Query untuk mendapatkan harga dari database
    $price_query = "SELECT price_per_hour FROM fields WHERE $field_col = ? OR $field_col LIKE ?";
    $stmt_price = $conn->prepare($price_query);
    $field_like = "%$field_name%";
    $stmt_price->bind_param("ss", $field_name, $field_like);
    $stmt_price->execute();
    $price_result = $stmt_price->get_result();
    
    if ($price_result && $price_result->num_rows > 0) {
        $price_data = $price_result->fetch_assoc();
        $field_price = $price_data['price_per_hour'];
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
    
    // Dapatkan harga dari database untuk lapangan yang dipilih
    if (!empty($field_name)) {
        // Query untuk mendapatkan harga dari database
        $price_query = "SELECT price_per_hour FROM fields WHERE $field_col = ? OR $field_col LIKE ?";
        $stmt_price = $conn->prepare($price_query);
        $field_like = "%$field_name%";
        $stmt_price->bind_param("ss", $field_name, $field_like);
        $stmt_price->execute();
        $price_result = $stmt_price->get_result();
        
        if ($price_result && $price_result->num_rows > 0) {
            $price_data = $price_result->fetch_assoc();
            $field_price = $price_data['price_per_hour'];
        }
    }
    
    // Hitung total pembayaran
    $total_price = $field_price + $admin_fee;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Collect form data
  $booking_date = $_POST['bookingDate'];
  $booking_time = $_POST['bookingTime'];
  $field = $_POST['fieldChoice'];
  $customer_name = $_POST['customerName'];
  $customer_phone = $_POST['customerPhone'];
  $customer_email = $_POST['customerEmail'];
  $team_name = $_POST['teamName'];
  $notes = $_POST['notes'];

  // Get user ID from session
  $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
  
  // Prepare the SQL query to insert data with user_id and pending_confirmation status
  $sql = "INSERT INTO badminton_booking (user_id, booking_date, booking_time, field, customer_name, customer_phone, customer_email, team_name, notes, status)
          VALUES ('$user_id', '$booking_date', '$booking_time', '$field', '$customer_name', '$customer_phone', '$customer_email', '$team_name', '$notes', 'pending_confirmation')";

  // Execute the query
  if ($conn->query($sql) === TRUE) {
      echo "Booking successful!";
  } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

// Format harga ke format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
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
      'booking_time' => $booking_time
  ];
  
  // Redirect ke halaman pembayaran sesuai metode yang dipilih

}

?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SportField - Ringkasan Pemesanan & Pembayaran</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
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
        background-color: #2e86c1;
      }

      .bg-custom-light {
        background-color: #e8f7e2;
      }

      .btn-custom-green {
        background-color: #2e86c1;
        color: white;
        border: none;
        box-shadow: 0 4px 6px rgba(126, 217, 87, 0.2);
        transition: all 0.3s;
      }

      .btn-custom-green:hover {
        background-color: #2e86c1;
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
        background-color: #aed6f1;
        color: white;
        border-bottom: none;
        padding: 15px 20px;
      }

      footer {
        padding: 50px 0 20px;
        background-color: var(--very-light-blue);
        border-top: 5px solid var(--secondary-blue);
      }

      footer h5 {
        color: var(--primary-blue);
        font-weight: 600;
        margin-bottom: 20px;
        font-size: 1.3rem;
      }

      footer p {
        color: #555;
        margin-bottom: 10px;
      }
      .social-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: var(--white);
        border-radius: 50%;
        color: var(--primary-blue);
        margin: 0 5px;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }

      .social-icon:hover {
        background-color: var(--primary-blue);
        color: var(--white);
        transform: translateY(-3px);
      }

      .copyright {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        font-size: 0.9rem;
        color: #777;
      }

      .booking-number {
        background: linear-gradient(45deg, #2e86c1, #34b5aa);
        color: white;
        padding: 10px 15px;
        border-radius: 10px;
        font-weight: bold;
        letter-spacing: 1px;
      }

      .step-title {
        color: #2e86c1;
        font-weight: bold;
        text-align: center;
      }

      .summary-item {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
      }

      .payment-option {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
        cursor: pointer;
        transition: all 0.3s;
      }

      .payment-option:hover,
      .payment-option.selected {
        border-color: #1e8449;
        background-color: #f8fff5;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      .payment-logo {
        height: 30px;
        object-fit: contain;
      }

      .booking-success {
        text-align: center;
        padding: 30px;
        background-color: #e8f7e2;
        border-radius: 15px;
        margin-top: 20px;
      }

      .success-icon {
        font-size: 5rem;
        color: #aed6f1;
        margin-bottom: 15px;
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
              <li><a class="dropdown-item" href="booking_history.php"><i class="fas fa-history me-2"></i>Riwayat Pemesanan</a></li>
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
          <h2 class="fw-bold">Pesan Lapangan</h2>
          <p class="lead text-muted">Ringkasan pemesanan dan pembayaran</p>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-8 mb-4">
          <div class="custom-card shadow-sm">
            <div class="card-header card-header-custom">
              <h5 class="mb-0">
                <i class="fas fa-receipt me-2"></i>Langkah 5: Ringkasan
                Pemesanan & Pembayaran
              </h5>
            </div>
            <div class="card-body p-4">
              <?php if ($show_success): ?>
              <!-- Tampilkan pesan sukses jika pembayaran berhasil -->
              <div class="booking-success">
                <div class="success-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <h4 class="mb-3">Pemesanan Berhasil!</h4>
                <p class="mb-4">
                  Detail pemesanan telah dikirim ke email Anda: <?php echo htmlspecialchars($customer_email); ?>. 
                  Instruksi pembayaran juga telah dikirimkan.
                </p>
                <a href="frontpage.php" class="btn btn-custom-blue"
                  >Kembali ke Beranda</a
                >
              </div>
              <?php else: ?>
                
              <!-- Tampilkan form pembayaran jika belum dibayar -->
              <div class="booking-number text-center mb-4">
                Nomor Booking: <?php echo htmlspecialchars($id); ?>
              </div>

              <form method="POST" action="konfirmasi_pembayaran_badminton.php">
                <!-- Simpan data dari halaman sebelumnya dalam hidden inputs -->
                <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($booking_date); ?>">
                <input type="hidden" name="booking_time" value="<?php echo htmlspecialchars($booking_time); ?>">
                <input type="hidden" name="field_name" value="<?php echo htmlspecialchars($field_name); ?>">
                <input type="hidden" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>">
                <input type="hidden" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone); ?>">
                <input type="hidden" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>">
                <input type="hidden" name="notes" value="<?php echo htmlspecialchars($notes); ?>">
                
                <h5 class="step-title mt-4 mb-3">Detail Pemesanan</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="summary-item">
                      <div class="text-muted">Tanggal Pemesanan</div>
                      <div class="fw-bold"><?php echo htmlspecialchars($booking_date); ?></div>
                    </div>
                    <div class="summary-item">
                      <div class="text-muted">Waktu</div>
                      <div class="fw-bold"><?php echo htmlspecialchars($booking_time); ?></div>
                    </div>
                    <div class="summary-item">
                      <div class="text-muted">Lapangan</div>
                      <div class="fw-bold"><?php echo htmlspecialchars($field_name); ?></div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="summary-item">
                      <div class="text-muted">Nama Pemesan</div>
                      <div class="fw-bold"><?php echo htmlspecialchars($customer_name); ?></div>
                    </div>
                    <div class="summary-item">
                      <div class="text-muted">Nomor Telepon</div>
                      <div class="fw-bold"><?php echo htmlspecialchars($customer_phone); ?></div>
                    </div>
                    <div class="summary-item">
                      <div class="text-muted">Email</div>
                      <div class="fw-bold"><?php echo htmlspecialchars($customer_email); ?></div>
                    </div>
                  </div>
                </div>

                <div class="summary-item mt-3">
                  <div class="text-muted">Catatan</div>
                  <div><?php echo htmlspecialchars($notes); ?></div>
                </div>

                <h5 class="step-title mt-4 mb-3">Detail Pembayaran</h5>
                <div class="summary-item">
                  <div class="d-flex justify-content-between">
                    <div>Harga Lapangan (1 jam)</div>
                    <div><?php echo formatRupiah($field_price); ?></div>
                  </div>
                </div>
                <div class="summary-item">
                  <div class="d-flex justify-content-between">
                    <div>Biaya Admin</div>
                    <div><?php echo formatRupiah($admin_fee); ?></div>
                  </div>
                </div>
                <div class="summary-item">
                  <div class="d-flex justify-content-between fw-bold">
                    <div>Total Pembayaran</div>
                    <div><?php echo formatRupiah($total_price); ?></div>
                  </div>
                </div>

                <h5 class="step-title mt-4 mb-3">Metode Pembayaran</h5>
                <div class="payment-methods">
                  <div class="payment-option selected">
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        name="paymentMethod"
                        id="BCA"
                        value="BCA"
                        checked
                      />
                      <label
                        class="form-check-label d-flex justify-content-between align-items-center"
                        for="BCA"
                      >
                        <span>BCA</span>
                        <img
                          src="Logo BCA_Biru.png"
                          alt="Bank Transfer"
                          class="payment-logo"
                        />
                      </label>
                    </div>
                  </div>
                </div>

                <div class="mt-4">
                  <button type="submit" name="pay" value="1" class="btn btn-custom-blue btn-lg w-100">
                    <i class="fas fa-credit-card me-2"></i>Bayar Sekarang
                  </button>
                </div>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-4 mb-4">
          <div class="custom-card shadow-sm">
            <div class="card-header card-header-custom">
              <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>Informasi Pembayaran
              </h5>
            </div>
            <div class="card-body p-4">
              <div class="alert bg-custom-light mb-4" role="alert">
                <h6 class="alert-heading">
                  <i class="fas fa-clock me-2"></i>Batas Waktu Pembayaran
                </h6>
                <p class="mb-0 small">
                  Silakan selesaikan pembayaran dalam
                  <span class="fw-bold">1 jam</span> sebelum reservasi
                  dibatalkan secara otomatis.
                </p>
              </div>

              <div class="mb-4">
                <h6 class="step-title">Petunjuk Pembayaran:</h6>
                <ol class="small">
                  <li class="mb-2">
                    Klik tombol "Bayar Sekarang" untuk melanjutkan ke halaman
                    pembayaran.
                  </li>
                  <li class="mb-2">
                    Selesaikan pembayaran melalui metode yang dipilih.
                  </li>
                  <li class="mb-2">
                    Booking Anda akan otomatis terkonfirmasi setelah pembayaran
                    berhasil.
                  </li>
                  <li class="mb-2">
                    E-tiket akan dikirimkan ke email yang terdaftar.
                  </li>
                </ol>
              </div>

              <div class="alert alert-warning small text-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Pembatalan hanya
                dapat dilakukan maksimal 24 jam sebelum waktu pemesanan.
              </div>

              <div class="mt-4 text-center">
                <p class="small text-muted mb-2">
                  Ada pertanyaan? Hubungi kami
                </p>
                <p class="mb-0">
                  <i class="fas fa-phone-alt me-2 text-success"></i
                  >0812-3456-7890
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <footer>
  <div id="kontak" class="container">
    <div class="row">
      <div class="col-lg-4 mb-4">
        <h5>PB SAMUDRA</h5>
        <p>
          Platform booking lapangan olahraga terbaik di Indonesia dengan
          berbagai fasilitas berkualitas dan harga terjangkau.
        </p>
      </div>
      <div class="col-lg-4 mb-4">
        <h5>Kontak Kami</h5>
        <p>
          <i class="fas fa-map-marker-alt me-2"></i> Jalan Letda Sujono Baru 1 no.117, Medan
        </p>
        <p><i class="fas fa-phone-alt me-2"></i> 0823-8740-8888</p>
      </div>
      <div class="col-lg-4 mb-4">
        <h5>Ikuti Kami</h5>
        <div class="mt-3">
          <a href="https://www.instagram.com/samudra.badminton?igsh=ZTFoczZnMmRlcmNn" class="social-icon" target="_blank">
            <i class="fab fa-instagram"></i>
          </a>
          <a href="https://wa.me/6282387408888" class="social-icon" target="_blank">
            <i class="fab fa-whatsapp"></i>
          </a>
        </div>
      </div>
    </div>
    <div class="copyright text-center">
      <p>© 2025 PB SAMUDRA. Hak Cipta Dilindungi.</p>
    </div>
  </div>
</footer>

    <!-- Script Bootstrap untuk menangani dropdown dan lainnya -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Script untuk menangani pemilihan metode pembayaran
      document.addEventListener('DOMContentLoaded', function() {
        const paymentOptions = document.querySelectorAll('.payment-option');
        
        paymentOptions.forEach(option => {
          option.addEventListener('click', function() {
            // Hapus kelas selected dari semua opsi
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Tambahkan kelas selected ke opsi yang diklik
            this.classList.add('selected');
            
            // Pilih radio button di dalam opsi yang diklik
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
          });
        });
      });
    </script>
  </body>
</html>