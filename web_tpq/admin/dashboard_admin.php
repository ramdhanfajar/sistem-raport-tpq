<?php
session_start();
require '../koneksi.php'; // Path naik 1 level

// --- 1. KEAMANAN: Cek apakah sudah login dan rolenya admin ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id_login = $_SESSION['user_id'];
$nama_admin = "Administrator"; 

// --- 2. HITUNG STATISTIK (DATA REAL-TIME) ---
$total_santri = $koneksi->query("SELECT COUNT(*) as total FROM data_santri")->fetch_assoc()['total'];
$total_guru   = $koneksi->query("SELECT COUNT(*) as total FROM data_pengajar")->fetch_assoc()['total'];
$total_kelas  = $koneksi->query("SELECT COUNT(*) as total FROM kelas")->fetch_assoc()['total'];

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Raport TPQ</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* === CSS TEMPLATE UTAMA (SAMA DENGAN TEMPLATE LAIN) === */
        :root {
            --warna-hijau: #00a86b;
            --warna-hijau-muda: #e6f7f0;
            --warna-latar: #f4f7f6;
            --warna-teks: #333333;
            --warna-teks-abu: #555;
            --lebar-sidebar: 280px;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--warna-latar);
            box-sizing: border-box;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }

        /* --- Sidebar & Overlay --- */
        .sidebar { 
            position: fixed; 
            top: 0; 
            left: 0; 
            height: 100%; 
            width: var(--lebar-sidebar); 
            background-color: var(--warna-hijau); 
            color: white; 
            z-index: 1000; 
            transform: translateX(-100%); 
            transition: transform 0.3s ease-out; 
            display: flex; 
            flex-direction: column; 
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar.active { 
            transform: translateX(0); 
        }
        .sidebar-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .sidebar-header .close-btn { font-size: 1.5rem; cursor: pointer; }
        
        .sidebar-nav { list-style: none; padding: 20px 0; margin: 0; flex-grow: 1; overflow-y: auto; }
        .sidebar-nav li a { display: flex; align-items: center; padding: 15px 25px; color: white; text-decoration: none; font-size: 1rem; font-weight: 500; transition: background-color 0.2s; }
        .sidebar-nav li a:hover { background-color: rgba(255, 255, 255, 0.1); }
        .sidebar-nav li.active > a { background-color: var(--warna-latar); color: var(--warna-hijau); border-left: 5px solid white; padding-left: 20px; }
        .sidebar-nav li.active > a i { color: var(--warna-hijau); }
        .sidebar-nav li a i.fa-fw { width: 30px; font-size: 1.2rem; margin-right: 15px; }
        .sidebar-nav li.logout { margin-top: auto; border-top: 1px solid rgba(255, 255, 255, 0.1); }

        /* Dropdown Sidebar */
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .dropdown-toggle .toggle-icon { font-size: 0.8rem; transition: transform 0.3s ease; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); display: none; /* Hidden by default */ }
        .sidebar-nav .submenu.active { display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        .sidebar-nav .dropdown-toggle.active .toggle-icon { transform: rotate(180deg); }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-out, visibility 0s 0.3s linear; }
        .overlay.active { opacity: 1; visibility: visible; transition: opacity 0.3s ease-out; }

        /* --- Header --- */
        .main-content { width: 100%; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--warna-hijau); color: white; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; }
        .header-title { font-size: 0.9rem; font-weight: 500; line-height: 1.3; }
        .header-right .user-profile { display: flex; align-items: center; text-align: right; text-decoration: none; color: white; }
        .user-profile .user-info { display: flex; flex-direction: column; }
        .user-profile span { font-size: 0.8rem; font-weight: 500; }
        .user-profile .icon-wrapper { background-color: white; color: var(--warna-hijau); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-left: 10px; font-size: 1.1rem; }

        /* --- Dashboard Content --- */
        .dashboard-area { padding: 25px 20px; }
        .dashboard-area h1 { color: var(--warna-teks); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s;
        }
        .card:hover { transform: translateY(-5px); }

        /* Kartu Welcome */
        .card-welcome {
            grid-column: 1 / -1; 
            background-color: var(--warna-hijau-muda);
            color: var(--warna-hijau);
            font-weight: 500;
        }
        .card-welcome h2 { margin: 0 0 5px 0; font-size: 1.2rem; }
        .card-welcome p { margin: 0; font-size: 0.9rem; line-height: 1.5; }

        /* Kartu Statistik */
        .card-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-stat .info h3 {
            font-size: 1rem;
            color: var(--warna-teks-abu);
            margin: 0 0 5px 0;
            font-weight: 500;
        }
        .card-stat .info p {
            font-size: 2.5rem;
            color: var(--warna-teks);
            margin: 0;
            font-weight: 700;
        }
        .card-stat .icon {
            font-size: 3rem;
            color: var(--warna-hijau);
            opacity: 0.3;
        }

        /* Header Responsif di HP Kecil */
        @media (max-width: 480px) {
            .header { padding: 12px 15px; }
            .header-logo img { width: 30px; height: 30px; }
            .header-title { font-size: 0.8rem; }
            .user-profile .user-info { display: none; }
        }
    </style>
</head>
<body>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../img/logo1.png" alt="Logo">
            <i class="fas fa-arrow-left close-btn" id="close-btn"></i>
        </div>
        <ul class="sidebar-nav">
            <li class="active">
                <a href="dashboard_admin.php"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a>
            </li>
            
            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    <span><i class="fas fa-database fa-fw"></i> Master Data</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </a>
                <ul class="submenu">
                    <li><a href="data_master/data_santri.php">Santri</a></li>
                    <li><a href="data_master/data_pengajar.php">Guru</a></li>
                    <li><a href="data_master/data_kelas.php">Kelas</a></li>
                    <li><a href="data_master/tahun_ajaran.php">Tahun Ajaran</a></li>
                    <li><a href="data_master/jadwal_pelajaran.php">Jadwal Pelajaran</a></li>
                </ul>
            </li>
            
            <li>
                <a href="pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a>
            </li>

            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    <span><i class="fas fa-file-alt fa-fw"></i> Laporan</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </a>
                <ul class="submenu">
                    <li><a href="laporan/laporan_guru.php">Laporan Daftar Guru</a></li>
                    <li><a href="laporan/laporan_santri.php">Laporan Daftar Santri</a></li>
                    <li><a href="laporan/laporan_nilai.php">Laporan Daftar Nilai</a></li>
                </ul>
            </li>
            
            <li>
                <a href="ganti_pw.php"><i class="fas fa-lock fa-fw"></i> Ganti Password</a>
            </li>
            <li class="logout">
                <a href="../logout.php" id="btn-logout"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div class="overlay" id="overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-logo">
                    <img src="../img/logo1.png" alt="Logo">
                </div>
                <div class="header-title">
                    Sistem Raport<br>Taman Pendidikan Al-Qur'an
                </div>
            </div>
            <div class="header-right">
                <a href="#" class="user-profile">
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($nama_admin); ?></span>
                    </div>
                    <div class="icon-wrapper">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </a>
            </div>
        </header>

        <main class="dashboard-area">
            <h1>Dashboard Admin</h1>

            <div class="dashboard-grid">
                <div class="card card-welcome">
                    <h2>Selamat Datang, Admin!</h2>
                    <p>Anda login sebagai Administrator di Sistem Raport Taman Pendidikan Al-Qur'an.</p>
                </div>

                <div class="card card-stat">
                    <div class="info">
                        <h3>Jumlah Santri</h3>
                        <p><?php echo $total_santri; ?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="card card-stat">
                    <div class="info">
                        <h3>Jumlah Guru</h3>
                        <p><?php echo $total_guru; ?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>

                <div class="card card-stat">
                    <div class="info">
                        <h3>Jumlah Kelas</h3>
                        <p><?php echo $total_kelas; ?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-school"></i>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // 1. Script Sidebar
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeBtn = document.getElementById('close-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        function openSidebar() { sidebar.classList.add('active'); overlay.classList.add('active'); }
        function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }
        hamburgerBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        // 2. Script Dropdown Sidebar
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                let icon = this.querySelector('.toggle-icon');
                
                // Toggle class active
                this.classList.toggle('active');
                submenu.classList.toggle('active');
            });
        });
        
        // 3. Script Pop-up Logout
        const btnLogout = document.getElementById('btn-logout');
        if(btnLogout) {
            btnLogout.addEventListener('click', function(e) {
                e.preventDefault(); 
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda akan keluar dari sesi ini.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#00a86b',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, keluar!',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = btnLogout.href; 
                    }
                });
            });
        }
    </script>

</body>
</html>