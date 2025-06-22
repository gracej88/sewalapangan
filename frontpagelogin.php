<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SportField - Sewa Lapangan Olahraga</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://site-assets.fontawesome.com/releases/v6.7.2/css/all.css"
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
        background-color: var(--white);
        color: #333;
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
        background-color: var(--primary-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-weight: bold;
      }

      .navbar {
        background-color: var(--primary-green);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      /* Tambahkan style untuk class active agar tetap putih */
      .nav-link.active {
        color: var(--white) !important;
        font-weight: 600;
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

      .hero-section {
        background: linear-gradient(
          135deg,
          var(--secondary-green) 0%,
          var(--primary-green) 100%
        );
        padding: 70px 0;
        text-align: center;
        color: var(--white);
        border-radius: 0px 0px 20px 20px;
        margin-bottom: 50px;
      }

      .hero-section h2 {
        font-weight: 700;
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: var(--white);
      }

      .hero-section p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
      }

      .sports-section {
        padding: 30px 0;
      }

      .carousel-inner {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      }

      .carousel-caption {
        background-color: rgba(30, 132, 73, 0.8);
        border-radius: 10px;
        padding: 15px;
        bottom: 20px;
      }

      .sport-card {
        background-color: var(--white);
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        height: 100%;
      }

      .sport-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      }

      .sport-icon {
        width: 90px;
        height: 90px;
        background-color: var(--very-light-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: var(--primary-green);
        transition: all 0.3s ease;
      }

      .sport-card:hover .sport-icon {
        background-color: var(--secondary-green);
        color: var(--white);
      }

      .sport-card h4 {
        color: var(--primary-green);
        font-weight: 600;
        margin-bottom: 15px;
      }

      .sport-card .btn {
        margin-top: 15px;
      }

      .features-section {
        padding: 50px 0;
        background-color: var(--very-light-green);
        border-radius: 20px;
        margin: 50px 0;
      }

      .feature-card {
        background-color: var(--white);
        border-radius: 15px;
        padding: 25px 15px;
        text-align: center;
        height: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      }

      .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
      }

      .feature-icon {
        width: 70px;
        height: 70px;
        background-color: var(--light-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        color: var(--primary-green);
      }

      .feature-card h5 {
        color: var(--primary-green);
        font-weight: 600;
        margin-bottom: 10px;
      }

      .field-background {
        background: linear-gradient(
          135deg,
          var(--primary-green) 0%,
          var(--dark-green) 100%
        );
        padding: 70px 0;
        text-align: center;
        color: var(--white);
        border-radius: 20px;
        margin: 50px 0;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
      }

      .field-background h2 {
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--white);
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

      .btn-primary {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
        border-radius: 30px;
        padding: 8px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
      }

      .btn-primary:hover {
        background-color: var(--dark-green);
        border-color: var(--dark-green);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      }

      .btn-light {
        background-color: var(--white);
        color: var(--primary-green);
        border-radius: 30px;
        padding: 8px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
      }

      .btn-light:hover {
        background-color: var(--light-green);
        color: var(--dark-green);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      }

      .section-title {
        color: var(--primary-green);
        font-weight: 700;
        margin-bottom: 40px;
        text-align: center;
        position: relative;
        padding-bottom: 15px;
      }

      .section-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--secondary-green);
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
              <a class="nav-link active" href="#home">Beranda</a>
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
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="frontpage.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
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

    <section class="hero-section">
      <div class="container">
        <h2>Temukan dan Booking Lapangan Olahraga Favorit Anda</h2>
        <p class="lead mt-3">
          Layanan booking lapangan online dengan kemudahan akses dan harga
          terjangkau
        </p>
        <a href="#jenislapangan" class="btn btn-light mt-4">Mulai Booking</a>
      </div>
    </section>

    <div class="container-fluid p-0">
      <div id="carouselExampleIndicators" class="carousel slide">
        <div class="carousel-indicators">
          <button
            type="button"
            data-bs-target="#carouselExampleIndicators"
            data-bs-slide-to="0"
            class="active"
            aria-current="true"
            aria-label="Slide 1"
          ></button>
          <button
            type="button"
            data-bs-target="#carouselExampleIndicators"
            data-bs-slide-to="1"
            aria-label="Slide 2"
          ></button>
          <button
            type="button"
            data-bs-target="#carouselExampleIndicators"
            data-bs-slide-to="2"
            aria-label="Slide 3"
          ></button>
        </div>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img
              src="gambar badmin.jpg"
              class="d-block w-100"
              alt="lapangan badminton"
              style="height: 300px; object-fit: cover"
            />
          </div>
        </div>
        <button
          class="carousel-control-prev"
          type="button"
          data-bs-target="#carouselExampleIndicators"
          data-bs-slide="prev"
        >
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button
          class="carousel-control-next"
          type="button"
          data-bs-target="#carouselExampleIndicators"
          data-bs-slide="next"
        >
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
    <section id="jenislapangan" class="container mt-5">
      <h2 class="section-title">Jenis Olahraga</h2>
      <div class="row g-4">
        <div class="col-md-12">
          <div class="sport-card">
            <div class="sport-icon">
              <i class="fa-solid fa-badminton"></i>
            </div>
            <h4>Bulu Tangkis</h4>
            <p>Lapangan badminton indoor dengan pencahayaan sempurna</p>
            <a href="Badminton_booking.php" class="btn btn-primary"
              >Pesan Lapangan</a
            >
          </div>
        </div>
      </div>
    </section>

    <section class="features-section">
      <div class="container">
        <h2 class="section-title">Keunggulan Kami</h2>
        <div class="row g-4">
          <div class="col-md-3">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-clock fa-2x"></i>
              </div>
              <h5>Jadwal Real Time</h5>
              <p>Lihat ketersediaan lapangan secara real time</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-laptop fa-2x"></i>
              </div>
              <h5>Pemesanan Online</h5>
              <p>Booking lapangan kapan saja dan di mana saja</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-wallet fa-2x"></i>
              </div>
              <h5>Pembayaran Mudah</h5>
              <p>Berbagai metode pembayaran tersedia</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-check-circle fa-2x"></i>
              </div>
              <h5>Konfirmasi Instan</h5>
              <p>Dapatkan konfirmasi booking secara instan</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="field-background">
      <div class="container">
        <h2>FASILITAS DAN KONDISI LAPANGAN</h2>
        <p class="mt-3">
          Kami menyediakan lapangan olahraga dengan kondisi terbaik dan
          fasilitas lengkap untuk kenyamanan Anda
        </p>
        <a href="#" class="btn btn-light mt-3">Lihat Detail</a>
      </div>
    </section>

    <footer style="background-color: #f1f1f1; padding: 60px 20px;">
  <div class="container d-flex flex-column align-items-center justify-content-center text-center">

    <!-- Brand Name -->
    <h2 class="fw-bold mb-3" style="color: #333;">SPORTFIELD</h2>

    <!-- Info Card -->
    <div style="background: white; border-radius: 12px; padding: 30px 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 600px;">
      <p style="color: #555;">
        Platform booking lapangan olahraga terbaik di Indonesia dengan berbagai fasilitas berkualitas dan harga terjangkau.
      </p>

      <div class="mt-4" style="font-size: 16px; color: #333;">
        <p><i class="fas fa-map-marker-alt me-2"></i> Jl. Olahraga No. 123, Medan</p>
        <p><i class="fas fa-phone-alt me-2"></i> 0812-3456-7890</p>
      </div>

      <!-- WA Button -->
      <div class="mt-4">
        <a href="https://wa.me/6281234567890?text=Halo%20admin,%20saya%20ingin%20bertanya%20tentang%20lapangan"
           target="_blank"
           class="btn"
           style="background-color: #25D366; color: white; font-weight: 500; padding: 10px 25px; border-radius: 30px; text-decoration: none; font-size: 16px;">
          💬 Hubungi Kami via WhatsApp
        </a>
      </div>
    </div>

    <!-- Garis dan copyright -->
    <div class="mt-5 text-muted" style="font-size: 14px;">
      <hr style="max-width: 400px; margin: 20px auto;">
      <p>© 2025 SportField. Hak Cipta Dilindungi.</p>
    </div>
  </div>
</footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
  </body>
</html>