<?php
session_start();
require '../koneksi.php'; // Path naik 1 level

// --- 1. KEAMANAN: Cek apakah sudah login dan rolenya ADMIN ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id_login = $_SESSION['user_id'];
$nama_admin = "Administrator"; 

// // Cek apakah ada data profil admin (Opsional, jika tabel data_admin ada)
// // Jika tidak ada tabel data_admin, nama diambil dari session email/default
// $stmt_profil = $koneksi->prepare("SELECT nama_lengkap FROM data_admin WHERE user_id = ?");
// if ($stmt_profil) {
//     $stmt_profil->bind_param("i", $user_id_login);
//     $stmt_profil->execute();
//     $res_profil = $stmt_profil->get_result();
//     if ($row = $res_profil->fetch_assoc()) {
//         $nama_admin = $row['nama_lengkap'];
//     }
//     $stmt_profil->close();
// } else {
//     // Fallback jika tabel data_admin belum dibuat
//     if(isset($_SESSION['email'])) $nama_admin = $_SESSION['email'];
// }

$status_msg_php = '';

// --- 2. LOGIKA GANTI PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if ($new_password !== $confirm_password) {
        $status_msg_php = 'gagal_konfirmasi';
    } else {
        // Ambil password lama dari database
        $stmt_check = $koneksi->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_check->bind_param("i", $user_id_login);
        $stmt_check->execute();
        $user_data = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        // Verifikasi password lama
        if ($user_data && password_verify($old_password, $user_data['password'])) {
            // Hash password baru
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update ke database
            $stmt_update = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_hashed_password, $user_id_login);
            
            if ($stmt_update->execute()) {
                $status_msg_php = 'sukses';
            } else {
                $status_msg_php = 'gagal_db';
            }
            $stmt_update->close();
        } else {
            $status_msg_php = 'gagal_lama';
        }
    }
    
    // Redirect agar tidak resubmit saat refresh
    header("Location: ganti_pw.php?status=" . $status_msg_php);
    exit();
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* === CSS TEMPLATE ADMIN (Sama dengan file lain) === */
        :root { --warna-hijau: #00a86b; --warna-hijau-muda: #e6f7f0; --warna-latar: #f4f7f6; --warna-teks: #333; --warna-teks-abu: #555; --lebar-sidebar: 280px; }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--warna-latar); box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }

        /* Sidebar & Overlay */
        .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: var(--lebar-sidebar); background-color: var(--warna-hijau); color: white; z-index: 1000; transform: translateX(-100%); transition: transform 0.3s ease-out; display: flex; flex-direction: column; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
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
        
        /* Dropdown */
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); display: none; }
        .sidebar-nav .submenu.active { display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        .sidebar-nav .submenu li.active-sub > a { background-color: rgba(255, 255, 255, 0.2); font-weight: 600; }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-out, visibility 0s 0.3s linear; }
        .overlay.active { opacity: 1; visibility: visible; transition: opacity 0.3s ease-out; }

        /* Header & Content */
        .main-content { width: 100%; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--warna-hijau); color: white; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; }
        .header-right .user-profile { display: flex; align-items: center; text-align: right; text-decoration: none; color: white; }
        .user-profile .icon-wrapper { background-color: white; color: var(--warna-hijau); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-left: 10px; font-size: 1.1rem; }

        /* Form Style */
        .dashboard-area { padding: 25px 20px; }
        .dashboard-area h1 { color: var(--warna-teks); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; }
        
        .card-form { max-width: 500px; margin: 0 auto; background-color: white; border-radius: 15px; box-shadow: 0 6px 15px rgba(0,0,0,0.07); padding: 30px; }
        .card-form h3 { text-align: center; font-size: 1.5rem; font-weight: 600; color: var(--warna-hijau); margin-top: 0; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 1rem; font-weight: 600; color: var(--warna-teks-abu); margin-bottom: 8px; }
        .password-wrapper { position: relative; }
        .form-group input { width: 100%; padding: 12px 45px 12px 20px; font-family: 'Poppins', sans-serif; font-size: 1rem; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; outline: none; }
        .toggle-password { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #888; cursor: pointer; font-size: 1.1rem; }
        
        .form-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px; }
        .btn { padding: 12px; font-size: 1rem; font-weight: 600; border-radius: 8px; text-align: center; text-decoration: none; cursor: pointer; border: none; transition: all 0.3s ease; font-family: 'Poppins', sans-serif; }
        .btn-batal { background-color: #6c757d; color: white; }
        .btn-batal:hover { background-color: #5a6268; }
        .btn-update { background-color: var(--warna-hijau); color: white; }
        .btn-update:hover { background-color: #008a5a; }

        @media (max-width: 480px) {
            .user-info { display: none; }
            .header { padding: 12px 15px; }
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
            <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
            
            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle"><span><i class="fas fa-database fa-fw"></i> Master Data</span> <i class="fas fa-chevron-down toggle-icon"></i></a>
                <ul class="submenu">
                    <li><a href="data_master/data_santri.php">Santri</a></li>
                    <li><a href="data_master/data_pengajar.php">Guru</a></li>
                    <li><a href="data_master/data_kelas.php">Kelas</a></li>
                    <li><a href="data_master/tahun_ajaran.php">Tahun Ajaran</a></li>
                    <li><a href="data_master/jadwal_pelajaran.php">Jadwal Pelajaran</a></li>
                </ul>
            </li>
            
            <li><a href="pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a></li>

            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle"><span><i class="fas fa-file-alt fa-fw"></i> Laporan</span> <i class="fas fa-chevron-down toggle-icon"></i></a>
                <ul class="submenu">
                    <li><a href="laporan/laporan_guru.php">Laporan Daftar Guru</a></li>
                    <li><a href="laporan/laporan_santri.php">Laporan Daftar Santri</a></li>
                    <li><a href="laporan/laporan_nilai.php">Laporan Daftar Nilai</a></li>
                </ul>
            </li>
            
            <li class="active"><a href="ganti_pw.php"><i class="fas fa-lock fa-fw"></i> Ganti Password</a></li>
            <li class="logout"><a href="../logout.php" id="btn-logout"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="overlay" id="overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><i class="fas fa-bars"></i></button>
                <div class="header-logo"><img src="../img/logo1.png" alt="Logo"></div>
                <div class="header-title">Sistem Raport<br>Taman Pendidikan Al-Qur'an</div>
            </div>
            <div class="header-right">
                <a href="#" class="user-profile">
                    <div class="user-info"><span><?php echo htmlspecialchars($nama_admin); ?></span></div>
                    <div class="icon-wrapper"><i class="fas fa-user-shield"></i></div>
                </a>
            </div>
        </header>

        <main class="dashboard-area">
            <h1>Ganti Password</h1>

            <div class="card-form">
                <h3>Ubah Kata Sandi Admin</h3>
                
                <form action="ganti_pw.php" method="POST" id="change-password-form">
                    <div class="form-group">
                        <label for="old_password">Password Lama</label>
                        <div class="password-wrapper">
                            <input type="password" id="old_password" name="old_password" required>
                            <i class="fas fa-eye-slash toggle-password"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" required>
                            <i class="fas fa-eye-slash toggle-password"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="fas fa-eye-slash toggle-password"></i>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <a href="dashboard_admin.php" class="btn btn-batal">Batal</a>
                        <button type="submit" class="btn btn-update">Update</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // 1. Sidebar & Dropdown
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeBtn = document.getElementById('close-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function openSidebar() { sidebar.classList.add('active'); overlay.classList.add('active'); }
        function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }

        hamburgerBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                this.classList.toggle('active');
                submenu.classList.toggle('active');
            });
        });

        // 2. Show/Hide Password
        document.querySelectorAll('.toggle-password').forEach(function(icon) {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                } else {
                    input.type = 'password';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                }
            });
        });
        
        // 3. Validasi & Submit Form
        const changePasswordForm = document.getElementById('change-password-form');
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass !== confirmPass) {
                Swal.fire('Gagal', 'Konfirmasi password tidak cocok!', 'error');
                return; 
            }
            if (newPass.length < 6) {
                 Swal.fire('Peringatan', 'Password baru minimal 6 karakter.', 'warning');
                 return;
            }
            
            Swal.fire({ title: 'Memperbarui...', icon: 'info', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            changePasswordForm.submit();
        });
        
        // 4. Pop-up Status
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'sukses') {
                Swal.fire('Berhasil!', 'Password telah diubah.', 'success');
            } else if (status === 'gagal_konfirmasi') {
                Swal.fire('Gagal', 'Password tidak cocok.', 'error');
            } else if (status === 'gagal_lama') {
                Swal.fire('Gagal!', 'Password lama salah.', 'error');
            } else if (status === 'gagal_db') {
                 Swal.fire('Error', 'Kesalahan sistem.', 'error');
            }
        });
        
        // 5. Logout Confirmation
        const btnLogout = document.getElementById('btn-logout');
        if(btnLogout) {
            btnLogout.addEventListener('click', function(e) {
                e.preventDefault(); 
                Swal.fire({
                    title: 'Yakin keluar?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00a86b', cancelButtonColor: '#d33', confirmButtonText: 'Ya', cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) { window.location.href = btnLogout.href; }
                });
            });
        }
    </script>

</body>
</html>