<?php
session_start();
include('db_connect.php');

// Ambil data lapangan dari database
$fields_query = "SELECT * FROM fields ORDER BY field_type, field_name";
$fields_result = $conn->query($fields_query);

// Hitung jumlah lapangan per jenis
$badminton_count = 0;
if ($fields_result && $fields_result->num_rows > 0) {
    while ($field = $fields_result->fetch_assoc()) {
        if ($field['field_type'] == 'badminton') $badminton_count++;
    }
    
    // Reset pointer result
    $fields_result->data_seek(0);
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

      
      /* baru */
            .hero-section {
        background: linear-gradient(to bottom right, var(--light-blue), var(--light-blue));
        padding: 100px 20px;
        text-align: center;
        color: var(--white); /* semua teks default putih */
        font-family: 'Poppins', sans-serif;
        border-radius: 20px;
      }

      .hero-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 20px;
        color: var(--white);
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3); /* bayangan biar pop out */
      }

      .hero-title span {
        color: var(--light-blue);
      }

      .hero-subtitle {
        font-size: 1.25rem;
        font-weight: 400;
        max-width: 650px;
        margin: 0 auto;
        color: var(--primary-blue); /* lebih terang */
      }

      .hero-slogan {
        font-style: italic;
        font-weight: 500;
        margin-top: 30px;
        font-size: 1.1rem;
        color: var(--white);
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
      }

            .hero-slogan:hover {
        color: var(--secondary-blue);
        transform: scale(1.05);
        transition: all 0.3s ease-in-out;
      }


      /* baru */
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
        background-color: var(--white);
        color: #333;
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

      .hero-section {
        background: linear-gradient(
          135deg,
          var(--light-blue) 0%,
          var(--light-blue) 100%
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

      .carousel-image {
      height: 500px;
      object-fit: cover;
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
        background-color: var(--very-light-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: var(--primary-blue);
        transition: all 0.3s ease;
      }

      .sport-card:hover .sport-icon {
        background-color: var(--secondary-blue);
        color: var(--white);
      }

      .sport-card h4 {
        color: var(--primary-blue);
        font-weight: 600;
        margin-bottom: 15px;
      }

      .sport-card .btn {
        margin-top: 15px;
      }

      .features-section {
        padding: 50px 0;
        background-color: var(--very-light-blue);
        border-radius: 20px;
        margin: 50px 0;
      }

      .feature-card {
        background-color: var(--white);
        border-radius: 15px;
        padding: 25px 15px;
        text-align: center;
        
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      }

      .feature-card p {
        color: #333;
        font-size: 0.95rem;
        margin-top: 10px;
      }


      .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
      }

      .feature-icon {
        width: 70px;
        height: 70px;
        background-color: var(--light-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        color: var(--primary-blue);
      }

      .feature-card h5 {
        color: var(--primary-blue);
        font-weight: 600;
        margin-bottom: 10px;
      }

      .field-background {
        background: linear-gradient(
          135deg,
          var(--primary-blue) 0%,
          var(--dark-blue) 100%
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

      .btn-primary {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
        border-radius: 30px;
        padding: 8px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
      }

      .btn-primary:hover {
        background-color: var(--dark-blue);
        border-color: var(--dark-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      }

      .btn-light {
        background-color: var(--white);
        color: var(--primary-blue);
        border-radius: 30px;
        padding: 8px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
      }

      .btn-light:hover {
        background-color: var(--light-blue);
        color: var(--dark-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      }

      .section-title {
        color: var(--primary-blue);
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
        background-color: var(--secondary-blue);
      }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg sticky-top">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
          <img src="uploads/payment_proof/logo-removebg-preview.png" alt="PB Samudra Logo" style="height: 40px;">
          <span class="fw-bold text-white">PB Samudra</span>
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
              <a class="nav-link" href="#lapangan">Fasilitas</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#kontak">Kontak</a>
            </li>
          </ul>
          
          <!-- Jika belum login -->
          <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-outline-light">Masuk</a>
            <a href="signup.php" class="btn btn-light">Daftar</a>
          </div>
        </div>
      </div>
    </nav>

    <section class="hero-section">
      <div class="container">
        <h2 class="hero-title">Selamat Datang di PB Samudra</h2>
          <p class="lead mt-3 hero-subtitle">
            Solusi terbaik untuk booking lapangan bulutangkis — cepat, mudah, dan terpercaya.
          </p>
        <p class="hero-slogan">🏸 Ayunkan raketmu, taklukkan hari ini!</p>
      </div>
    </section>


      <div class="container-fluid px-0">
      <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0"
            class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"
            aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"
            aria-label="Slide 3"></button>
        </div>

        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="uploads/payment_proof/Banner.png" class="d-block w-100 carousel-image" alt="Lapangan 1">
          </div>
          <div class="carousel-item">
            <img src="uploads/payment_proof/lapangan2.jfif" class="d-block w-100 carousel-image" alt="Lapangan 2">
          </div>
          <div class="carousel-item">
            <img src="uploads/payment_proof/lapangan3.jfif" class="d-block w-100 carousel-image" alt="Lapangan 3">
          </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
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
            <a href="#" class="btn btn-primary"
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

    <section id="lapangan" class="field-background">
      <div class="container">
        <h2>FASILITAS</h2>
        <p class="mt-3">
         Kami menyediakan lapangan olahraga dengan kondisi terbaik dan
        fasilitas lengkap untuk kenyamanan Anda
        </p>
        <div class="row mt-5 g-4">
  <div class="col-md-6 col-lg-3">
    <div class="feature-card h-100">
      <div class="feature-icon">
        <i class="fas fa-shower fa-lg"></i>
      </div>
      <h5>Ruang Ganti</h5>
      <p>Tempat bersih dan nyaman untuk berganti pakaian sebelum dan sesudah bermain.</p>
    </div>
  </div>

  <div class="col-md-6 col-lg-3">
    <div class="feature-card h-100">
      <div class="feature-icon">
        <i class="fas fa-utensils fa-lg"></i>
      </div>
      <h5>Kantin</h5>
      <p>Menyediakan makanan dan minuman segar untuk mengisi energi para pemain dan pengunjung.</p>
    </div>
  </div>

  <div class="col-md-6 col-lg-3">
    <div class="feature-card h-100">
      <div class="feature-icon">
        <i class="fas fa-futbol fa-lg"></i>
      </div>
      <h5>4 Lapangan</h5>
      <p>Terdiri dari 2 lapangan karpet dan 2 lapangan semen yang siap digunakan kapan saja.</p>
    </div>
  </div>

  <div class="col-md-6 col-lg-3">
    <div class="feature-card h-100">
      <div class="feature-icon">
        <i class="fas fa-parking fa-lg"></i>
      </div>
      <h5>Parkir & CCTV</h5>
      <p>Area parkir luas dan aman, diawasi CCTV untuk kenyamanan semua pengunjung.</p>
    </div>
  </div>
</div>



       
    </section>


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


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
  </body>
</html>