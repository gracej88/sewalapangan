<?php
// Mulai session untuk menyimpan data antar halaman
session_start();

// Include the database connection file
include('db_connect_badminton.php');

// Inisialisasi variabel dengan nilai default
$booking_number = "BDM-" . date('Ymd') . sprintf('%02d', rand(1, 99));
$booking_date = date('j F Y', strtotime('+1 day'));
$booking_time = "08:00 - 09:00";
$field_name = "Lapangan Badminton 2";
$customer_name = "";
$customer_phone = "";
$customer_email = "";
$notes = "";
$show_success = false;

// Harga dan biaya
$field_price = 100000;
$admin_fee = 2500;
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
    
    // Harga sesuai dengan lapangan yang dipilih (contoh)
    if (strpos($field_name, 'Futsal') !== false) {
        $field_price = 150000; // Harga lapangan futsal
    } elseif (strpos($field_name, 'Badminton') !== false) {
        $field_price = 100000; // Harga lapangan badminton
    } elseif (strpos($field_name, 'Basket') !== false) {
        $field_price = 200000; // Harga lapangan basket
    }
    
    // Hitung total pembayaran
    $total_price = $field_price + $admin_fee;
}

// Jika ada data POST untuk proses pembayaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay'])) {
    // Proses pembayaran
    $payment_method = $_POST['paymentMethod'] ?? 'bankTransfer';
    
    // Simpan data pembayaran ke database (simulasi)
    // ... kode untuk menyimpan ke database ...
    
    // Tampilkan halaman sukses
    $show_success = true;
    
    // Jika pembayaran sukses, hapus data session
    if ($show_success) {
        // Simpan email untuk ditampilkan di halaman sukses
        $confirmation_email = $customer_email;
        
        // Reset form jika pembayaran berhasil
        // session_unset(); // Uncomment jika ingin menghapus semua data session
    }
}

// Format harga ke format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
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
        --primary-green: #1e8449;
        --secondary-green: #2ecc71;
        --light-green: #abebc6;
        --very-light-green: #e8f8f5;
        --dark-green: #186a3b;
        --white: #ffffff;
      }

      body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        color: var(--text-dark);
        background-color: var(--white);
      }
      .bg-custom-green {
        background-color: #1e8449;
      }

      .bg-custom-light {
        background-color: #e8f7e2;
      }

      .btn-custom-green {
        background-color: #1e8449;
        color: white;
        border: none;
        box-shadow: 0 4px 6px rgba(126, 217, 87, 0.2);
        transition: all 0.3s;
      }

      .btn-custom-green:hover {
        background-color: #1e8449;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(126, 217, 87, 0.3);
      }

      .navbar-custom {
        background-color: var(--primary-green);
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
        color: var(--light-green);
        transform: translateY(-2px);
      }

      .custom-card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s;
      }

      .card-header-custom {
        background-color: #1e8449;
        color: white;
        border-bottom: none;
        padding: 15px 20px;
      }

      footer {
        padding: 50px 0 20px;
        background-color: var(--very-light-green);
        border-top: 5px solid var(--secondary-green);
      }

      footer h5 {
        color: var(--primary-green);
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
        color: var(--primary-green);
        margin: 0 5px;
        font-size: 1.2rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }

      .social-icon:hover {
        background-color: var(--primary-green);
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
        background: linear-gradient(45deg, #7ed957, #34b5aa);
        color: white;
        padding: 10px 15px;
        border-radius: 10px;
        font-weight: bold;
        letter-spacing: 1px;
      }

      .step-title {
        color: #1e8449;
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
        color: #1e8449;
        margin-bottom: 15px;
      }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
      <div class="container">
        <a class="navbar-brand" href="#"
          ><i class="fas fa-futbol me-2"></i>SportField</a
        >
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav mx-auto gap-5">
            <li class="nav-item">
              <a class="nav-link active" href="index.php">Beranda</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="futsal_booking.php">Pesan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#fields">Lapangan</a>
            </li>
          </ul>
            </li>
          </ul>
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
                  Detail pemesanan telah dikirim ke email Anda: <?php echo htmlspecialchars($confirmation_email); ?>. 
                  Instruksi pembayaran juga telah dikirimkan.
                </p>
                <a href="index.php" class="btn btn-custom-green"
                  >Kembali ke Beranda</a
                >
              </div>
              <?php else: ?>
              <!-- Tampilkan form pembayaran jika belum dibayar -->
              <div class="booking-number text-center mb-4">
                Nomor Booking: <?php echo htmlspecialchars($booking_number); ?>
              </div>

              <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
                        id="bankTransfer"
                        value="bankTransfer"
                        checked
                      />
                      <label
                        class="form-check-label d-flex justify-content-between align-items-center"
                        for="bankTransfer"
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
                  <div class="payment-option">
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        name="paymentMethod"
                        id="ewallet"
                        value="ewallet"
                      />
                      <label
                        class="form-check-label d-flex justify-content-between align-items-center"
                        for="ewallet"
                      >
                        <span>OVO</span>
                        <img
                          src="images.jpg"
                          alt="E-Wallet"
                          class="payment-logo"
                        />
                      </label>
                    </div>
                  </div>
                  <div class="payment-option">
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="radio"
                        name="paymentMethod"
                        id="virtualAccount"
                        value="virtualAccount"
                      />
                      <label
                        class="form-check-label d-flex justify-content-between align-items-center"
                        for="virtualAccount"
                      >
                        <span>QRIS</span>
                        <img
                          src="images.png"
                          alt="Virtual Account"
                          class="payment-logo"
                        />
                      </label>
                    </div>
                  </div>
                </div>

                <div class="mt-4">
                  <button type="submit" name="pay" value="1" class="btn btn-custom-green btn-lg w-100">
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
            <h5>SPORTFIELD</h5>
            <p>
              Platform booking lapangan olahraga terbaik di Indonesia dengan
              berbagai fasilitas berkualitas dan harga terjangkau.
            </p>
          </div>
          <div class="col-lg-4 mb-4">
            <h5>Kontak Kami</h5>
            <p>
              <i class="fas fa-map-marker-alt me-2"></i> Jl. Olahraga No. 123,
              Jakarta
            </p>
            <p><i class="fas fa-phone-alt me-2"></i> 0812-3456-7890</p>
            <p><i class="fas fa-envelope me-2"></i> info@sportfield.com</p>
          </div>
          <div class="col-lg-4 mb-4">
            <h5>Ikuti Kami</h5>
            <div class="mt-3">
              <a href="#" class="social-icon"
                ><i class="fab fa-facebook-f"></i
              ></a>
              <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
              <a href="#" class="social-icon"
                ><i class="fab fa-instagram"></i
              ></a>
              <a href="#" class="social-icon"
                ><i class="fab fa-whatsapp"></i
              ></a>
            </div>
          </div>
        </div>
        <div class="copyright text-center">
          <p>© 2025 SportField. Hak Cipta Dilindungi.</p>
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