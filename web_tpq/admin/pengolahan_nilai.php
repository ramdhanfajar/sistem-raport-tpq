<?php
session_start();
require '../koneksi.php'; // Path naik 1 level (asumsi file ini ada di folder admin/)

// --- 1. KEAMANAN: Hanya Admin yang boleh akses ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id_login = $_SESSION['user_id'];
$nama_admin = $_SESSION['email']; // Atau ambil nama dari tabel data_admin jika ada

$status_msg = '';

// --- 2. LOGIKA KUNCI / BUKA KUNCI (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kelas = $_POST['id_kelas'];
    $semester = $_POST['semester'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $aksi = $_POST['aksi']; // 'kunci' atau 'buka'

    $status_baru = ($aksi == 'kunci') ? 'terkunci' : 'terbuka';

    // Gunakan INSERT ... ON DUPLICATE KEY UPDATE
    // Pastikan tabel 'status_kunci_nilai' sudah dibuat di database!
    $stmt = $koneksi->prepare("INSERT INTO status_kunci_nilai (id_kelas, semester, tahun_ajaran, status) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE status = ?");
    $stmt->bind_param("issss", $id_kelas, $semester, $tahun_ajaran, $status_baru, $status_baru);
    
    if ($stmt->execute()) {
        // Redirect agar tidak resubmit saat refresh
        header("Location: pengolahan_nilai.php?id_kelas=$id_kelas&semester=$semester&tahun_ajaran=$tahun_ajaran&status=sukses_update&mode=$status_baru");
        exit();
    } else {
        $status_msg = "gagal_update";
    }
    $stmt->close();
}

// --- 3. DATA FILTER ---
$list_kelas = [];
$res_kelas = $koneksi->query("SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC");
while ($row = $res_kelas->fetch_assoc()) $list_kelas[] = $row;

// Ambil filter dari URL atau default
$selected_kelas = $_GET['id_kelas'] ?? ($list_kelas[0]['id'] ?? null);
$selected_semester = $_GET['semester'] ?? '1';
$selected_tahun = $_GET['tahun_ajaran'] ?? ''; 

// Jika tahun ajaran kosong, ambil otomatis dari kelas yang dipilih
if ($selected_kelas && empty($selected_tahun)) {
    foreach($list_kelas as $k) {
        if ($k['id'] == $selected_kelas) {
            $selected_tahun = $k['tahun_ajaran'];
            break;
        }
    }
}

// --- 4. CEK STATUS KUNCI SAAT INI ---
$status_kunci = 'terbuka'; // Default
if ($selected_kelas && $selected_semester && $selected_tahun) {
    $stmt_cek = $koneksi->prepare("SELECT status FROM status_kunci_nilai WHERE id_kelas = ? AND semester = ? AND tahun_ajaran = ?");
    $stmt_cek->bind_param("iss", $selected_kelas, $selected_semester, $selected_tahun);
    $stmt_cek->execute();
    $res_cek = $stmt_cek->get_result();
    if ($row_cek = $res_cek->fetch_assoc()) {
        $status_kunci = $row_cek['status'];
    }
    $stmt_cek->close();
}

// --- 5. AMBIL REKAP NILAI (Total Data Masuk) ---
$total_data_nilai = 0;
if ($selected_kelas) {
    $stmt_rekap = $koneksi->prepare("SELECT COUNT(*) as total FROM nilai_raport n 
                                     JOIN data_santri s ON n.id_santri = s.id 
                                     WHERE s.id_kelas = ? AND n.semester = ? AND n.tahun_ajaran = ?");
    $stmt_rekap->bind_param("iss", $selected_kelas, $selected_semester, $selected_tahun);
    $stmt_rekap->execute();
    $total_data_nilai = $stmt_rekap->get_result()->fetch_assoc()['total'];
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunci Nilai - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* === CSS TEMPLATE FINAL (NON-RESPONSIVE/MOBILE LAYOUT) === */
        :root {
            --warna-hijau: #00a86b;
            --warna-hijau-muda: #e6f7f0;
            --warna-latar: #f4f7f6;
            --warna-teks: #333333;
            --warna-teks-abu: #555;
            --lebar-sidebar: 280px;
        }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--warna-latar); box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }

        /* Sidebar */
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
        
        /* Dropdown Sidebar */
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); display: none; }
        .sidebar-nav .submenu.active { display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        
        /* Overlay */
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-out, visibility 0s 0.3s linear; }
        .overlay.active { opacity: 1; visibility: visible; transition: opacity 0.3s ease-out; }

        /* Header & Main */
        .main-content { width: 100%; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--warna-hijau); color: white; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; }
        .header-right { display: flex; align-items: center; gap: 10px; }
        .user-profile { color: white; text-decoration: none; display: flex; align-items: center; }
        .user-profile .icon-wrapper { background-color: white; color: var(--warna-hijau); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-left: 10px; }

        /* Dashboard Content */
        .dashboard-area { padding: 25px 20px; }
        .dashboard-area h1 { color: var(--warna-teks); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; }
        
        .card { background-color: white; padding: 25px; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); margin-bottom: 20px; }

        /* Form Filter */
        .filter-form { display: grid; grid-template-columns: 1fr; gap: 15px; }
        @media (min-width: 768px) { .filter-form { grid-template-columns: repeat(4, 1fr); align-items: end; } }
        
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: var(--warna-teks-abu); font-size: 0.9rem; margin-bottom: 8px; }
        .form-group select, .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background-color: white; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        .btn-filter { background-color: var(--warna-hijau); color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; font-family: 'Poppins', sans-serif; }
        .btn-filter:hover { background-color: #008a5a; }

        /* Status Box */
        .status-box { text-align: center; padding: 40px 20px; border-radius: 10px; margin-top: 20px; border: 2px dashed #ddd; transition: all 0.3s; }
        .status-terbuka { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .status-terkunci { background-color: #f8d7da; color: #842029; border-color: #f5c2c7; }
        
        .status-icon { font-size: 4rem; margin-bottom: 15px; display: block; }
        .status-box h2 { margin: 10px 0; font-size: 1.5rem; }
        .status-box p { margin: 0 0 25px 0; font-size: 1rem; opacity: 0.9; }
        
        .btn-kunci, .btn-buka { padding: 12px 25px; border: none; border-radius: 30px; cursor: pointer; font-size: 1rem; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; font-family: 'Poppins', sans-serif; }
        .btn-kunci { background-color: #dc3545; color: white; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }
        .btn-kunci:hover { background-color: #bb2d3b; transform: translateY(-2px); }
        .btn-buka { background-color: #0d6efd; color: white; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }
        .btn-buka:hover { background-color: #0b5ed7; transform: translateY(-2px); }
        
        /* HP Kecil */
        @media (max-width: 480px) {
            .user-info { display: none; }
            .header { padding: 12px 15px; }
            .header-logo img { width: 30px; height: 30px; }
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
            
            <li class="active"><a href="pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a></li>

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
            
            <li><a href="ganti_pw.php"><i class="fas fa-lock fa-fw"></i> Ganti Password</a></li>
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
                    <div class="user-info"><span>Halo, Admin</span></div>
                    <div class="icon-wrapper"><i class="fas fa-user-shield"></i></div>
                </a>
            </div>
        </header>

        <main class="dashboard-area">
            <h1>Kunci Nilai Raport</h1>

            <div class="card">
                <form method="GET" class="filter-form" id="filter-form">
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="id_kelas" required onchange="this.form.submit()">
                            <?php foreach($list_kelas as $k): ?>
                                <option value="<?php echo $k['id']; ?>" <?php echo ($k['id'] == $selected_kelas) ? 'selected' : ''; ?>>
                                    <?php echo $k['nama_kelas'] . " (" . $k['tahun_ajaran'] . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <option value="1" <?php echo ($selected_semester == '1') ? 'selected' : ''; ?>>Ganjil</option>
                            <option value="2" <?php echo ($selected_semester == '2') ? 'selected' : ''; ?>>Genap</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" value="<?php echo htmlspecialchars($selected_tahun); ?>" readonly style="background-color: #eee;">
                    </div>
                    <button type="submit" class="btn-filter"><i class="fas fa-sync"></i> Cek Status</button>
                </form>
            </div>

            <?php if ($selected_kelas): ?>
            <div class="card">
                <h3>Status Pengisian Nilai</h3>
                <p style="text-align: center; margin-bottom: 10px;">Jumlah Data Nilai Masuk: <strong style="color: var(--warna-hijau); font-size: 1.2rem;"><?php echo $total_data_nilai; ?></strong></p>
                
                <div class="status-box <?php echo ($status_kunci == 'terkunci') ? 'status-terkunci' : 'status-terbuka'; ?>">
                    <i class="status-icon fas <?php echo ($status_kunci == 'terkunci') ? 'fa-lock' : 'fa-lock-open'; ?>"></i>
                    <h2>Status: <?php echo strtoupper($status_kunci); ?></h2>
                    <p>
                        <?php if($status_kunci == 'terbuka'): ?>
                            Pengajar <b>BISA</b> menginput dan mengedit nilai pada sistem.
                        <?php else: ?>
                            Pengajar <b>TIDAK BISA</b> menginput atau mengedit nilai (Terkunci).
                        <?php endif; ?>
                    </p>

                    <form method="POST" id="form-kunci">
                        <input type="hidden" name="id_kelas" value="<?php echo $selected_kelas; ?>">
                        <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                        <input type="hidden" name="tahun_ajaran" value="<?php echo $selected_tahun; ?>">
                        
                        <?php if ($status_kunci == 'terbuka'): ?>
                            <button type="submit" name="aksi" value="kunci" class="btn-kunci">
                                <i class="fas fa-lock"></i> KUNCI NILAI
                            </button>
                        <?php else: ?>
                            <button type="submit" name="aksi" value="buka" class="btn-buka">
                                <i class="fas fa-lock-open"></i> BUKA KUNCI
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        // Sidebar Logic
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeBtn = document.getElementById('close-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        function openSidebar() { sidebar.classList.add('active'); overlay.classList.add('active'); }
        function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }
        hamburgerBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        // Dropdown Logic
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                this.classList.toggle('active');
                submenu.classList.toggle('active');
            });
        });
        
        // Pop-up Sukses Status Update
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const mode = urlParams.get('mode');

            if (status === 'sukses_update') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: `Status nilai berhasil diubah menjadi: ${mode.toUpperCase()}`,
                    icon: 'success',
                    confirmButtonColor: '#00a86b'
                }).then(() => {
                    // Hapus parameter URL agar bersih
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?id_kelas=" + urlParams.get('id_kelas') + "&semester=" + urlParams.get('semester') + "&tahun_ajaran=" + urlParams.get('tahun_ajaran');
                    window.history.pushState({path:newUrl},'',newUrl);
                });
            }
        });

        // Konfirmasi Tombol Kunci/Buka
        const formKunci = document.getElementById('form-kunci');
        if (formKunci) {
            formKunci.addEventListener('submit', function(e) {
                e.preventDefault(); // Tahan submit
                const btn = e.submitter;
                const aksi = btn.value;
                
                let titleText = aksi === 'kunci' ? 'Kunci Nilai?' : 'Buka Kunci?';
                let bodyText = aksi === 'kunci' 
                    ? 'Guru tidak akan bisa menginput atau mengedit nilai lagi.' 
                    : 'Guru akan bisa menginput dan mengubah nilai kembali.';
                let confirmColor = aksi === 'kunci' ? '#dc3545' : '#0d6efd';

                Swal.fire({
                    title: titleText,
                    text: bodyText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: confirmColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tambahkan input hidden untuk 'aksi' karena submitter tidak terkirim via submit()
                        let input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'aksi';
                        input.value = aksi;
                        formKunci.appendChild(input);
                        formKunci.submit();
                    }
                });
            });
        }

        // Logout Logic
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