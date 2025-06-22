<?php
// Start the session to maintain data across pages
session_start();

// Include the database connection file
include('db_connect.php');

// Handle form submission
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
  
  // Ambil user_id dari session
  $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user'] ?? null;

  // Prepare the SQL query to insert data
  $sql = "INSERT INTO badminton_booking (booking_date, booking_time, field, customer_name, customer_phone, customer_email, team_name, notes, status, user_id)
          VALUES ('$booking_date', '$booking_time', '$field', '$customer_name', '$customer_phone', '$customer_email', '$team_name', '$notes', 'pending_confirmation', " . intval($user_id) . ")";

  // Execute the query
  if ($conn->query($sql) === TRUE) {
      echo "Booking successful!";
  } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

// Set default date to today
$today = date('Y-m-d');
$current_month = date('F Y');
$current_month_num = date('n');
$current_year = date('Y');
$days_in_month = date('t');
$first_day_of_month = date('N', strtotime($current_year . '-' . $current_month_num . '-01'));

// Adjust first day to start week on Sunday (1=Monday in date('N'))
$first_day_of_month = $first_day_of_month % 7;
if ($first_day_of_month == 0) $first_day_of_month = 7;

// Ambil data lapangan badminton dari database
$fields = [];

// Periksa struktur tabel untuk menentukan nama kolom yang digunakan
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

// Query yang benar sesuai dengan struktur tabel
$field_query = "SELECT * FROM fields WHERE $type_col = 'badminton'";
$field_result = $conn->query($field_query);

if ($field_result && $field_result->num_rows > 0) {
    while ($row = $field_result->fetch_assoc()) {
        $fields[] = [
            'id' => $row['id'],
            'name' => $row[$field_col] ?? $row['name'],
            'price_per_hour' => $row['price_per_hour'],
            'status' => $row['status'] ?? 'available',
            'surface_type' => $row['surface_type'] ?? 'Unknown'
        ];
    }
}

// Jika tidak ada data lapangan di database, gunakan data default
if (empty($fields)) {
    $fields = [
        ['id' => 'field1', 'name' => 'Lapangan Badminton A', 'type' => 'Lapangan indoor', 'value' => 'Lapangan Badminton A', 'available' => true, 'price_per_hour' => 80000],
        ['id' => 'field2', 'name' => 'Lapangan Badminton B', 'type' => 'Lapangan indoor', 'value' => 'Lapangan Badminton B', 'available' => true, 'price_per_hour' => 80000]
    ];
}

// Ambil slot waktu yang sudah dipesan dari database
$booked_slots = [];
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Query untuk mengambil slot yang sudah dipesan pada tanggal tertentu
$booked_query = "SELECT booking_date, booking_time, field FROM badminton_booking 
                WHERE booking_date = ? AND status NOT IN ('rejected')";
$stmt = $conn->prepare($booked_query);
$stmt->bind_param("s", $selected_date);

// Jika query gagal, tampilkan pesan error
if (!$stmt->execute()) {
    echo "Error: " . $stmt->error;
    $booked_result = null;
} else {
    $booked_result = $stmt->get_result();
}

if ($booked_result && $booked_result->num_rows > 0) {
    while ($row = $booked_result->fetch_assoc()) {
        $field_name = $row['field'];
        $time = date('H:i', strtotime($row['booking_time']));
        
        if (!isset($booked_slots[$field_name])) {
            $booked_slots[$field_name] = [];
        }
        
        $booked_slots[$field_name][] = $time;
    }
}

// Definisikan slot waktu yang tersedia
$time_slots = [
    '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', 
    '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'
];

// Kalau form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap data
    $tanggal = htmlspecialchars($_POST['bookingDate']);
    $waktu = htmlspecialchars($_POST['bookingTime']);
    $lapangan = htmlspecialchars($_POST['fieldChoice']);
    $nama = htmlspecialchars($_POST['customerName']);
    $telepon = htmlspecialchars($_POST['customerPhone']);
    $email = htmlspecialchars($_POST['customerEmail']);
    $tim = htmlspecialchars($_POST['teamName']);
    $catatan = htmlspecialchars($_POST['notes']);

    // Misal: tampilkan notifikasi sukses
    echo "<div class='alert alert-success text-center'>Booking Berhasil untuk $nama pada tanggal $tanggal ($waktu) di $lapangan!</div>";
    
    // In a real application, you would save this to a database here
}

// Get selected date from GET parameter or use today
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Get selected field from GET parameter or use the first field
$selected_field = isset($_GET['field']) ? $_GET['field'] : ($fields[0]['name'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Badminton</title>
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

      .navbar {
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
        background-color: var(--primary-green);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-weight: bold;
      }

      /* Hero Section */
      .hero-section {
        background: linear-gradient(
          135deg,
          var(--secondary-green) 0%,
          var(--primary-green) 100%
        );
        text-align: center;
        padding: 70px 0;
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
      /* Button Styling */
      .btn-custom-green {
        background-color: var(--primary-green);
        color: var(--white);
        border: none;
        border-radius: 30px;
        box-shadow: 0 4px 10px rgba(30, 132, 73, 0.3);
        transition: all 0.3s ease;
        font-weight: 500;
        padding: 10px 25px;
      }

      .btn-custom-green:hover {
        background-color: var(--dark-green);
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(30, 132, 73, 0.4);
        color: var(--white);
      }

      .btn-custom-green a {
        color: white;
        text-decoration: none;
      }

      .btn-custom-green a:hover {
        color: white;
      }
      .btn-outline-light {
        border-radius: 30px;
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 10px 25px;
      }

      .btn-outline-light:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(255, 255, 255, 0.2);
      }

      /* Feature Icons */
      .feature-icon {
        width: 70px;
        height: 70px;
        background-color: var(--very-light-green);
        border: 2px solid var(--secondary-green);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 15px;
        color: var(--primary-green);
        font-size: 1.5rem;
        transition: all 0.3s ease;
      }

      .feature-icon:hover {
        background-color: var(--primary-green);
        color: var(--white);
        transform: scale(1.1);
      }

      /* Card Styling */
      .custom-card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s ease;
      }
      .card-header-custom h5 {
        color: #ffffff;
        font-weight: bold;
      }
      .custom-card:hover {
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        transform: translateY(-5px);
      }

      .card-header-custom {
        background-color: var(--primary-green);
        color: var(--white);
        border-bottom: none;
        padding: 15px 20px;
      }

      /* Calendar Styling */
      .calendar-day {
        height: 100px;
        transition: all 0.3s;
        position: relative;
        border: 1px solid #dee2e6;
      }

      .calendar-day:hover {
        background-color: var(--very-light-green);
        transform: scale(1.05);
        z-index: 1;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      .calendar-day.active {
        background-color: var(--primary-green);
        color: var(--white);
        border-color: var(--primary-green);
      }
      
      .calendar-day.selectable {
        cursor: pointer;
      }

      /* Time Slot Styling */
      .time-slot {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
        transition: all 0.3s;
        position: relative;
      }

      .time-slot:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
      }

      .time-slot.available {
        border-left: 5px solid var(--primary-green);
        background-color: var(--very-light-green);
        cursor: pointer;
      }

      .time-slot.available:hover {
        background-color: var(--light-green);
      }

      .time-slot.booked {
        border-left: 5px solid #ff6b6b;
        background-color: rgba(0, 0, 0, 0.05);
        opacity: 0.7;
        cursor: not-allowed;
      }

      /* Field Card Styling */
      .field-card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      }

      .field-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      }

      .field-card.selected {
        border-color: var(--primary-green);
        box-shadow: 0 5px 15px rgba(30, 132, 73, 0.3);
      }

      .field-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: rgba(0, 0, 0, 0.05);
        filter: grayscale(80%);
      }

      .field-image {
        height: 180px;
        object-fit: cover;
        transition: all 0.5s ease;
      }

      .field-card:hover .field-image {
        transform: scale(1.05);
      }

      /* Form Styling */
      .form-control:focus {
        border-color: var(--secondary-green);
        box-shadow: 0 0 0 0.25rem rgba(46, 204, 113, 0.25);
      }

      /* Badge Styling */
      .badge-custom {
        background-color: var(--primary-green);
        color: var(--white);
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 20px;
      }

      .badge-price {
        font-size: 1rem;
        padding: 5px 15px;
        border-radius: 20px;
      }

      /* Availability Indicators */
      .availability-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 5px;
      }

      .available-indicator {
        background-color: var(--primary-green);
      }

      .booked-indicator {
        background-color: #ff6b6b;
      }

      /* Footer */
      footer {
        background-color: var(--dark-green);
        color: var(--white);
        padding: 40px 0 20px;
      }

      /* Form Inputs */
      input[type="date"],
      input[type="text"],
      input[type="tel"],
      input[type="email"],
      textarea {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 10px 15px;
        transition: all 0.3s ease;
      }

      input[type="date"]:focus,
      input[type="text"]:focus,
      input[type="tel"]:focus,
      input[type="email"]:focus,
      textarea:focus {
        border-color: var(--secondary-green);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
      }

      /* Radio Buttons */
      .form-check-input:checked {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
      }

      /* Section Titles */
      h2,
      h3,
      h4,
      h5 {
        color: var(--primary-green);
        font-weight: 600;
      }

      /* Step Numbers */
      .step-title {
        color: var(--primary-green);
        font-weight: bold;
      }

      /* Responsive Adjustments */
      @media (max-width: 768px) {
        .calendar-day {
          height: 60px;
        }

        .field-image {
          height: 140px;
        }
      }
      
      /* Selected time slot styling */
      .time-slot.selected {
        background-color: var(--light-green);
        border: 2px solid var(--primary-green);
      }
      
      /* Date badge for booked days */
      .date-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 0.7rem;
        padding: 2px 5px;
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

    <section class="hero-section text-center" id="home">
      <div class="container">
        <h2>Pesan Lapangan Badminton</h2>
        <p class="lead mt-3">
          Platform digital yang memungkinkan Anda melihat jadwal, memesan
          lapangan, dan melakukan pembayaran secara praktis dan real-time.
        </p>
        <div class="d-flex justify-content-center gap-3 mt-4">
          <a href="#book" class="btn btn-custom-green px-3 py-2">
            <i class="fas fa-calendar-check me-2"></i>Pesan Sekarang</a
          >
          <a href="frontpage.php" class="btn btn-outline-light px-3 py-2"
            ><i class="fas fa-info-circle me-2"></i>Lihat Lapangan</a
          >
        </div>
      </div>
    </section>

    <form action="booking_process_badminton.php" method="POST" id="bookingForm">
    <section class="container mt-5 mb-4">
      <div class="row g-4 text-center">
        <div class="col-md-3">
          <div class="d-flex flex-column align-items-center">
            <div class="feature-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <h4>Jadwal Real-time</h4>
            <p class="text-muted">
              Lihat ketersediaan lapangan secara real-time
            </p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="d-flex flex-column align-items-center">
            <div class="feature-icon">
              <i class="fas fa-mobile-alt"></i>
            </div>
            <h4>Pemesanan Online</h4>
            <p class="text-muted">Pesan lapangan kapan saja dan di mana saja</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="d-flex flex-column align-items-center">
            <div class="feature-icon">
              <i class="fas fa-credit-card"></i>
            </div>
            <h4>Pembayaran Mudah</h4>
            <p class="text-muted">Bayar dengan berbagai metode pembayaran</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="d-flex flex-column align-items-center">
            <div class="feature-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <h4>Konfirmasi Instan</h4>
            <p class="text-muted">
              Dapatkan konfirmasi pemesanan secara instan
            </p>
          </div>
        </div>
      </div>
    </section>

    <div class="container my-5">
      <section id="book" class="mb-5">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center mb-4">
            <h2 class="fw-bold">Pesan Lapangan</h2>
            <p class="lead text-muted">
              Pilih tanggal, waktu, dan jenis lapangan yang Anda inginkan
            </p>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-md-12 mb-4">
            <div class="custom-card shadow-sm">
              <div class="card-header card-header-custom">
                <h5 class="mb-0">
                  <i class="fas fa-calendar me-2"></i>Langkah 1: Pilih Tanggal
                </h5>
              </div>
              <div class="card-body p-4">
                <div class="row">
                  <div class="col-md-8 mx-auto">
                    <div class="input-group mb-3">
                      <span class="input-group-text bg-white"
                        ><i class="fas fa-calendar-alt text-success"></i
                      ></span>
                      <input
                        type="date"
                        class="form-control"
                        id="bookingDate"
                        name="bookingDate"
                        min="<?php echo $today; ?>"
                        value="<?php echo $selected_date; ?>"
                      />
                    </div>
                  </div>
                </div>

                <div class="calendar mt-4">
                  <div class="row mb-2">
                    <div class="col text-center">
                      <h4><?php echo $current_month; ?></h4>
                    </div>
                  </div>
                  <div class="row text-center fw-bold">
                    <div class="col">Min</div>
                    <div class="col">Sen</div>
                    <div class="col">Sel</div>
                    <div class="col">Rab</div>
                    <div class="col">Kam</div>
                    <div class="col">Jum</div>
                    <div class="col">Sab</div>
                  </div>
                  
                  <?php
                    // Previous month days (for filling the calendar grid)
                    $prev_month_days = $first_day_of_month - 1;
                    $prev_month_last_day = date('t', strtotime($current_year . '-' . ($current_month_num-1) . '-01'));
                    
                    // Calculate rows needed
                    $total_calendar_cells = $prev_month_days + $days_in_month;
                    $total_rows = ceil($total_calendar_cells / 7);
                    
                    // Calendar day counter
                    $day_counter = 1;
                    $next_month_counter = 1;
                    
                    // Generate calendar rows
                    for ($row = 0; $row < $total_rows; $row++) {
                      echo '<div class="row g-2' . ($row > 0 ? '' : ' mt-1') . '">';
                      
                      // Generate 7 columns for each day of the week
                      for ($col = 0; $col < 7; $col++) {
                        // Previous month days
                        if ($row == 0 && $col < $prev_month_days) {
                          $prev_month_day = $prev_month_last_day - ($prev_month_days - $col - 1);
                          echo '<div class="col calendar-day border text-muted">' . $prev_month_day . '</div>';
                        }
                        // Current month days
                        else if ($day_counter <= $days_in_month) {
                          // Format date string
                          $date_str = $current_year . '-' . sprintf('%02d', $current_month_num) . '-' . sprintf('%02d', $day_counter);
                          
                          // Determine if day is today
                          $is_today = ($date_str == $today);
                          
                          // Determine if day is selected
                          $is_selected = ($date_str == $selected_date);
                          
                          // Determine if day is booked
                          $is_booked = isset($booked_slots[$date_str]) && !empty($booked_slots[$date_str]);
                          
                          // CSS classes for the calendar day
                          $day_classes = 'col calendar-day border';
                          if ($is_today) $day_classes .= ' active';
                          if ($is_selected && !$is_today) $day_classes .= ' bg-success text-white';
                          if ($date_str >= $today) $day_classes .= ' selectable';
                          
                          echo '<div class="' . $day_classes . '" data-date="' . $date_str . '">' . $day_counter;
                          
                          // Add indicator for today
                          if ($is_today) {
                            echo ' <small class="d-block text-white">Hari ini</small>';
                          }
                          
                          // Add booked indicator
                          if ($is_booked) {
                            echo ' <small class="d-block text-' . ($is_today || $is_selected ? 'white' : 'danger') . '">Penuh</small>';
                          }
                          
                          echo '</div>';
                          
                          $day_counter++;
                        }
                        // Next month days
                        else {
                          echo '<div class="col calendar-day border text-muted">' . $next_month_counter . '</div>';
                          $next_month_counter++;
                        }
                      }
                      
                      echo '</div>';
                    }
                  ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="custom-card shadow-sm h-100">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Langkah 2: Pilih Waktu
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <span class="availability-indicator available-indicator"></span>
                            Tersedia
                        </div>
                        <div>
                            <span class="availability-indicator booked-indicator"></span>
                            Sudah Dipesan
                        </div>
                    </div>

                    <div class="time-slots">
                        <?php
                        // Ambil harga lapangan yang dipilih dari array fields
                        $current_price = 80000; // Default price
                        foreach ($fields as $field) {
                            if ($field['name'] == $selected_field) {
                                $current_price = $field['price_per_hour'];
                                break;
                            }
                        }
                        
                        // Cek apakah slot waktu sudah dipesan untuk lapangan yang dipilih
                        $booked_times = isset($booked_slots[$selected_field]) ? $booked_slots[$selected_field] : [];
                        
                        // Tampilkan semua slot waktu
                        foreach ($time_slots as $time) {
                            // Hitung waktu selesai (1 jam dari waktu mulai)
                            $end_time = date('H:i', strtotime($time) + 3600);
                            $time_range = $time . ' - ' . $end_time;
                            
                            // Cek apakah slot ini sudah dipesan
                            $is_booked = in_array($time, $booked_times);
                            $status_class = $is_booked ? 'booked' : 'available';
                            $badge_class = $is_booked ? 'bg-secondary' : 'badge-custom';
                            $status_text = $is_booked ? 'Sudah Dipesan' : 'Tersedia';
                            $disabled = $is_booked ? 'disabled' : '';
                        ?>
                        <div class="time-slot p-3 mb-3 border rounded position-relative <?php echo $status_class; ?>">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="radio" 
                                            name="bookingTime" 
                                            id="time<?php echo str_replace(':', '', $time); ?>" 
                                            value="<?php echo $time; ?>" 
                                            <?php echo $disabled; ?>
                                        >
                                        <label class="form-check-label" for="time<?php echo str_replace(':', '', $time); ?>">
                                            <?php echo $time_range; ?>
                                        </label>
                                    </div>
                                    <span class="badge <?php echo $is_booked ? 'bg-danger' : 'bg-success'; ?> mt-1"><?php echo $status_text; ?></span>
                                </div>
                                <div class="col-md-5 text-end">
                                    <span class="badge <?php echo $badge_class; ?> p-2">Rp <?php echo number_format($current_price, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="custom-card shadow-sm h-100">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">
                        <i class="fas fa-futbol me-2"></i>Langkah 3: Pilih Lapangan
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <?php foreach ($fields as $field): 
                            $is_available = ($field['status'] ?? 'available') == 'available';
                            $disabled = !$is_available ? 'disabled' : '';
                            $checked = ($selected_field == $field['name']) ? 'checked' : '';
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="field-option p-3 border rounded h-100 <?php echo !$is_available ? 'bg-light' : ''; ?>">
                                <h5><?php echo $field['name']; ?></h5>
                                <p class="small text-muted mb-2">Lapangan <?php echo $field['surface_type']; ?></p>
                                <div class="form-check">
                                    <input 
                                        class="form-check-input field-radio" 
                                        type="radio" 
                                        name="fieldChoice" 
                                        id="field<?php echo $field['id']; ?>" 
                                        value="<?php echo $field['name']; ?>" 
                                        <?php echo $disabled . ' ' . $checked; ?> 
                                        data-price="<?php echo $field['price_per_hour']; ?>"
                                    >
                                    <label class="form-check-label" for="field<?php echo $field['id']; ?>">
                                        Pilih Lapangan Ini
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $is_available ? 'Tersedia' : 'Tidak Tersedia'; ?>
                                    </span>
                                    <span class="badge badge-custom">
                                        Rp <?php echo number_format($field['price_per_hour'], 0, ',', '.'); ?>/jam
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
          </div>
          <div class="col-md-12 mb-4"  id="informasiPemesanan">
            <div class="custom-card shadow-sm">
              <div class="card-header card-header-custom">
                <h5 class="mb-0">
                  <i class="fas fa-user me-2"></i>Langkah 4: Informasi Pemesan
                </h5>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="customerName" class="form-label"
                      >Nama Lengkap <span class="text-danger">*</span></label
                    >
                    <input
                      type="text"
                      class="form-control"
                      id="customerName"
                      name="customerName"
                      required
                    />
                  </div>
                  <div class="col
                  <div class="col-md-6">
                    <label for="customerPhone" class="form-label"
                      >Nomor Telepon <span class="text-danger">*</span></label
                    >
                    <input
                      type="tel"
                      class="form-control"
                      id="customerPhone"
                      name="customerPhone"
                      required
                    />
                  </div>
                  <div class="col-md-6">
                    <label for="customerEmail" class="form-label"
                      >Email <span class="text-danger">*</span></label
                    >
                    <input
                      type="email"
                      class="form-control"
                      id="customerEmail"
                      name="customerEmail"
                      required
                    />
                  </div>
                  <div class="col-md-6">
                    <label for="teamName" class="form-label"
                      >Nama Tim (opsional)</label
                    >
                    <input type="text" class="form-control" id="teamName" name="teamName" />
                  </div>
                  <div class="col-12">
                    <label for="notes" class="form-label"
                      >Catatan Tambahan</label
                    >
                    <textarea
                      class="form-control"
                      id="notes"
                      name="notes"
                      rows="3"
                    ></textarea>
                  </div>
                  <div class="col-12 text-center mt-3">
                    <button
                      type="submit"
                      class="btn btn-custom-green px-4 py-2"
                    >
                      Kirim Formulir
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calendar day selection
        const calendarDays = document.querySelectorAll('.calendar-day.selectable');
        const dateInput = document.getElementById('bookingDate');
        
        calendarDays.forEach(day => {
            day.addEventListener('click', function() {
                // Remove selection class from all days
                document.querySelectorAll('.calendar-day').forEach(d => {
                    d.classList.remove('bg-success');
                    d.classList.remove('text-white');
                });
                
                // Skip if today (already has active class)
                if (!this.classList.contains('active')) {
                    // Add selection to clicked day
                    this.classList.add('bg-success');
                    this.classList.add('text-white');
                }
                
                // Set date in input
                const selectedDate = this.getAttribute('data-date');
                dateInput.value = selectedDate;
                
                // Reload page with selected date parameter
                window.location.href = '?date=' + selectedDate;
            });
        });
        
        // Time slot selection
        const timeSlots = document.querySelectorAll('.time-slot.available');
        const timeInput = document.getElementById('bookingTime');
        
        timeSlots.forEach(slot => {
            slot.addEventListener('click', function() {
                // Remove selection from all time slots
                document.querySelectorAll('.time-slot').forEach(s => {
                    s.classList.remove('selected');
                });
                
                // Add selection to clicked time slot
                this.classList.add('selected');
                
                // Set time in hidden input
                const selectedTime = this.getAttribute('data-time');
                timeInput.value = selectedTime;
            });
        });
        
        // Field card selection
        const fieldCards = document.querySelectorAll('.field-card:not(.disabled)');
        
        fieldCards.forEach(card => {
            card.addEventListener('click', function() {
                // Find radio button in this card and check it
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Remove selection from all cards
                document.querySelectorAll('.field-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selection to clicked card
                this.classList.add('selected');
            });
        });
        
        // Form validation before submission
        const bookingForm = document.getElementById('bookingForm');
        
        bookingForm.addEventListener('submit', function(event) {
            // Check if date is selected
            if (!dateInput.value) {
                event.preventDefault();
                alert('Silakan pilih tanggal pemesanan!');
                return false;
            }
            
            // Check if time is selected
            if (!timeInput.value) {
                event.preventDefault();
                alert('Silakan pilih waktu pemesanan!');
                return false;
            }
            
            // Check if field is selected
            const fieldSelected = document.querySelector('input[name="fieldChoice"]:checked');
            if (!fieldSelected) {
                event.preventDefault();
                alert('Silakan pilih lapangan!');
                return false;
            }
            
            // Form is valid, continue submission
            return true;
        });

        // Ambil semua radio button lapangan
        const fieldRadios = document.querySelectorAll('.field-radio');
        
        // Tambahkan event listener untuk perubahan pada radio button lapangan
        fieldRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    // Ambil harga dari atribut data-price
                    const price = this.getAttribute('data-price');
                    const fieldName = this.value;
                    
                    // Perbarui URL dengan lapangan yang dipilih
                    const url = new URL(window.location);
                    url.searchParams.set('field', fieldName);
                    window.history.pushState({}, '', url);
                    
                    // Reload halaman untuk memperbarui status tersedia/tidak
                    location.reload();
                }
            });
        });
    });
    </script>
  </body>
</html>