<?php
session_start();
require '../../koneksi.php'; // Path naik 2 level

// --- 1. KEAMANAN: Cek Login Admin ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$status_msg = '';

// --- 2. LOGIKA POST (Tambah, Aktifkan, Hapus) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. TAMBAH TAHUN AJARAN
    if (isset($_POST['tambah'])) {
        $tahun = $_POST['tahun_ajaran'];
        
        // Cek apakah sudah ada
        $cek = $koneksi->query("SELECT id FROM tahun_ajaran WHERE tahun_ajaran = '$tahun'");
        if ($cek->num_rows > 0) {
            $status_msg = "<div class='alert alert-danger'>Gagal: Tahun ajaran $tahun sudah ada.</div>";
        } else {
            // Masukkan sebagai tidak aktif default-nya
            $stmt = $koneksi->prepare("INSERT INTO tahun_ajaran (tahun_ajaran, status) VALUES (?, 'tidak aktif')");
            $stmt->bind_param("s", $tahun);
            if ($stmt->execute()) {
                $status_msg = "<div class='alert alert-success'>Tahun ajaran berhasil ditambahkan.</div>";
            } else {
                $status_msg = "<div class='alert alert-danger'>Terjadi kesalahan database.</div>";
            }
            $stmt->close();
        }
    }
    
    // B. AKTIFKAN TAHUN AJARAN
    elseif (isset($_POST['aktifkan'])) {
        $id_tahun = $_POST['id'];
        
        $koneksi->begin_transaction();
        try {
            // 1. Set semua jadi 'tidak aktif'
            $koneksi->query("UPDATE tahun_ajaran SET status = 'tidak aktif'");
            
            // 2. Set yang dipilih jadi 'aktif'
            $stmt = $koneksi->prepare("UPDATE tahun_ajaran SET status = 'aktif' WHERE id = ?");
            $stmt->bind_param("i", $id_tahun);
            $stmt->execute();
            
            $koneksi->commit();
            $status_msg = "<div class='alert alert-success'>Tahun ajaran berhasil diaktifkan.</div>";
        } catch (Exception $e) {
            $koneksi->rollback();
            $status_msg = "<div class='alert alert-danger'>Gagal mengaktifkan tahun ajaran.</div>";
        }
    }
    
    // C. HAPUS TAHUN AJARAN
    elseif (isset($_POST['hapus'])) {
        $id_tahun = $_POST['id'];
        
        // Cek apakah ini tahun aktif (jangan dihapus kalau aktif)
        $cek = $koneksi->query("SELECT status FROM tahun_ajaran WHERE id = $id_tahun")->fetch_assoc();
        if ($cek['status'] == 'aktif') {
             $status_msg = "<div class='alert alert-danger'>Gagal: Tidak bisa menghapus tahun ajaran yang sedang AKTIF. Pindahkan status aktif ke tahun lain terlebih dahulu.</div>";
        } else {
            $stmt = $koneksi->prepare("DELETE FROM tahun_ajaran WHERE id = ?");
            $stmt->bind_param("i", $id_tahun);
            if ($stmt->execute()) {
                $status_msg = "<div class='alert alert-success'>Tahun ajaran berhasil dihapus.</div>";
            }
            $stmt->close();
        }
    }
}

// --- 3. AMBIL DATA UNTUK TABEL ---
$list_tahun = [];
$result = $koneksi->query("SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
while ($row = $result->fetch_assoc()) {
    $list_tahun[] = $row;
}
$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Tahun Ajaran - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Style dasar sama dengan halaman admin lain */
        :root { --warna-hijau: #00a86b; --warna-hijau-muda: #e6f7f0; --warna-latar: #f4f7f6; --warna-teks: #333333; --warna-teks-abu: #555; --lebar-sidebar: 280px; }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--warna-latar); box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }

        /* Sidebar & Header */
        .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: var(--lebar-sidebar); background-color: var(--warna-hijau); color: white; z-index: 1000; transform: translateX(-100%); transition: transform 0.3s ease-out; display: flex; flex-direction: column; }
        .sidebar.active { transform: translateX(0); }
        .sidebar-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .sidebar-header .close-btn { font-size: 1.5rem; cursor: pointer; }
        .sidebar-nav { list-style: none; padding: 20px 0; margin: 0; flex-grow: 1; }
        .sidebar-nav li a { display: flex; align-items: center; padding: 15px 25px; color: white; text-decoration: none; font-size: 1rem; font-weight: 500; transition: background-color 0.2s; }
        .sidebar-nav li a:hover { background-color: rgba(255, 255, 255, 0.1); }
        .sidebar-nav li.active > a { background-color: var(--warna-latar); color: var(--warna-hijau); border-left: 5px solid white; padding-left: 20px; }
        .sidebar-nav li.active > a i { color: var(--warna-hijau); }
        .sidebar-nav li a i.fa-fw { width: 30px; font-size: 1.2rem; margin-right: 15px; }
        .sidebar-nav li.logout { margin-top: auto; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); display: none; }
        .sidebar-nav .submenu.active { display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        .sidebar-nav .submenu li.active-sub > a { background-color: rgba(255, 255, 255, 0.2); font-weight: 600; }

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

        /* Content */
        .dashboard-area { padding: 25px 20px; }
        .dashboard-area h1 { color: var(--warna-teks); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; }
        
        .card { background-color: white; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); margin-bottom: 20px; overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; color: var(--warna-hijau); font-size: 1.2rem; }
        
        .card-body { padding: 20px; overflow-x: auto; }
        
        /* Form Tambah */
        .form-inline { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; flex-grow: 1; }
        .form-group label { font-weight: 600; color: var(--warna-teks-abu); font-size: 0.9rem; margin-bottom: 5px; }
        .form-group input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; }
        .btn-simpan { background-color: var(--warna-hijau); color: white; padding: 10px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; height: 42px; }
        .btn-simpan:hover { background-color: #008a5a; }

        /* Tabel */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
        .data-table th { background-color: #f9f9f9; color: var(--warna-teks); font-weight: 600; }
        .data-table tbody tr:hover { background-color: var(--warna-hijau-muda); }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .badge-aktif { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .badge-non { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        
        .btn-aksi { padding: 6px 12px; border-radius: 5px; color: white; text-decoration: none; font-size: 0.85rem; border: none; cursor: pointer; margin-right: 5px; display: inline-block; }
        .btn-aktifkan { background-color: #0d6efd; }
        .btn-hapus { background-color: #dc3545; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background-color: var(--warna-hijau-muda); color: var(--warna-hijau); }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../img/logo1.png" alt="Logo">
            <i class="fas fa-arrow-left close-btn" id="close-btn"></i>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../dashboard_admin.php"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
            
            <li class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle active"><span><i class="fas fa-database fa-fw"></i> Master Data</span> <i class="fas fa-chevron-down toggle-icon"></i></a>
                <ul class="submenu active">
                    <li><a href="data_santri.php">Santri</a></li>
                    <li><a href="data_pengajar.php">Guru</a></li>
                    <li><a href="data_kelas.php">Kelas</a></li>
                    <li class="active-sub"><a href="tahun_ajar.php">Tahun Ajaran</a></li>
                    <li><a href="jadwal_pelajaran.php">Jadwal Pelajaran</a></li>
                </ul>
            </li>
            
            <li><a href="../pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a></li>

            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle"><span><i class="fas fa-file-alt fa-fw"></i> Laporan</span> <i class="fas fa-chevron-down toggle-icon"></i></a>
                <ul class="submenu">
                    <li><a href="../laporan/laporan_guru.php">Laporan Daftar Guru</a></li>
                    <li><a href="../laporan/laporan_santri.php">Laporan Daftar Santri</a></li>
                    <li><a href="../laporan/laporan_nilai.php">Laporan Daftar Nilai</a></li>
                </ul>
            </li>
            
            <li><a href="../ganti_pw.php"><i class="fas fa-lock fa-fw"></i> Ganti Password</a></li>
            <li class="logout"><a href="../../logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="overlay" id="overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><i class="fas fa-bars"></i></button>
                <div class="header-logo"><img src="../../img/logo1.png" alt="Logo"></div>
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
            <h1>Master Tahun Ajaran</h1>
            <?php echo $status_msg; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Tambah Tahun Ajaran Baru</h3>
                </div>
                <div class="card-body">
                    <form action="" method="POST" class="form-inline">
                        <div class="form-group">
                            <label>Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" placeholder="Contoh: 2025/2026" required>
                        </div>
                        <button type="submit" name="tambah" class="btn-simpan"><i class="fas fa-plus"></i> Tambah</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Daftar Tahun Ajaran</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tahun Ajaran</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_tahun)): ?>
                                <tr><td colspan="4" style="text-align:center; padding:20px;">Belum ada data.</td></tr>
                            <?php else: ?>
                                <?php $no=1; foreach ($list_tahun as $ta): ?>
                                <tr>
                                    <td><?php echo $no++; ?>.</td>
                                    <td><?php echo htmlspecialchars($ta['tahun_ajaran']); ?></td>
                                    <td>
                                        <?php if ($ta['status'] == 'aktif'): ?>
                                            <span class="badge badge-aktif"><i class="fas fa-check-circle"></i> Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-non">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $ta['id']; ?>">
                                            
                                            <?php if ($ta['status'] == 'tidak aktif'): ?>
                                                <button type="submit" name="aktifkan" class="btn-aksi btn-aktifkan" title="Aktifkan">
                                                    <i class="fas fa-power-off"></i> Aktifkan
                                                </button>
                                            <?php endif; ?>

                                            <button type="submit" name="hapus" class="btn-aksi btn-hapus" onclick="return confirm('Yakin hapus <?php echo $ta['tahun_ajaran']; ?>?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Sidebar & Dropdown Logic (Sama seperti template lain)
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
    </script>
</body>
</html>