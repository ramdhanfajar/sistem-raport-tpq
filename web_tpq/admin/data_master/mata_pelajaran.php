<?php
session_start();
require '../../koneksi.php'; // Path naik 2 level

// 1. KEAMANAN ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$status_msg = '';

// 2. LOGIKA TAMBAH JADWAL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $id_pengajar = $_POST['id_pengajar'];
    $id_kelas = $_POST['id_kelas'];
    $id_materi = $_POST['id_materi'];
    $tahun_ajaran = $_POST['tahun_ajaran'];

    // Cek duplikasi
    $cek = $koneksi->query("SELECT id FROM jadwal_pelajaran WHERE id_pengajar='$id_pengajar' AND id_kelas='$id_kelas' AND id_materi='$id_materi' AND tahun_ajaran='$tahun_ajaran'");
    
    if ($cek->num_rows > 0) {
        $status_msg = "<div class='alert alert-danger'>Gagal: Jadwal tersebut sudah ada.</div>";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO jadwal_pelajaran (id_pengajar, id_kelas, id_materi, tahun_ajaran) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $id_pengajar, $id_kelas, $id_materi, $tahun_ajaran);
        if ($stmt->execute()) {
            $status_msg = "<div class='alert alert-success'>Jadwal berhasil ditambahkan.</div>";
        }
        $stmt->close();
    }
}

// 3. LOGIKA HAPUS JADWAL
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $koneksi->query("DELETE FROM jadwal_pelajaran WHERE id = $id_hapus");
    header("Location: jadwal_pelajaran.php");
    exit();
}

// 4. AMBIL DATA UNTUK DROPDOWN & TABEL
$list_guru = $koneksi->query("SELECT id, nama_lengkap FROM data_pengajar ORDER BY nama_lengkap ASC");
$list_kelas = $koneksi->query("SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC");
$list_materi = $koneksi->query("SELECT id, nama_materi FROM materi ORDER BY nama_materi ASC");

// Ambil Data Jadwal (Join 3 Tabel)
$data_jadwal = [];
$sql_jadwal = "SELECT jp.id, dp.nama_lengkap, k.nama_kelas, m.nama_materi, jp.tahun_ajaran 
               FROM jadwal_pelajaran jp
               JOIN data_pengajar dp ON jp.id_pengajar = dp.id
               JOIN kelas k ON jp.id_kelas = k.id
               JOIN materi m ON jp.id_materi = m.id
               ORDER BY k.nama_kelas ASC, m.nama_materi ASC";
$res_jadwal = $koneksi->query($sql_jadwal);
while($row = $res_jadwal->fetch_assoc()) {
    $data_jadwal[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelajaran - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* CSS TEMPLATE FINAL (Sama dengan file admin lain) */
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
        
        .card { background-color: white; padding: 25px; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); margin-bottom: 20px; overflow: hidden; }
        
        /* Form Grid */
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 15px; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: repeat(4, 1fr); align-items: end; } }
        
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: var(--warna-teks-abu); font-size: 0.9rem; margin-bottom: 5px; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background-color: white; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        .btn-simpan { background-color: var(--warna-hijau); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; width: 100%; font-family: 'Poppins', sans-serif; }
        .btn-simpan:hover { background-color: #008a5a; }
        .btn-simpan i { margin-right: 5px; }

        /* Tabel */
        .card-table { padding: 0; }
        .card-body { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-top: 10px; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
        .data-table th { background-color: #f9f9f9; color: var(--warna-teks); font-weight: 600; }
        .data-table tbody tr:hover { background-color: var(--warna-hijau-muda); }
        
        .alert { padding: 15px; border-radius: 8px; font-weight: 500; margin-bottom: 20px; }
        .alert-success { background-color: var(--warna-hijau-muda); color: var(--warna-hijau); }
        .alert-danger { background-color: #f8d7da; color: #721c24; }

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
            <img src="../../img/logo1.png" alt="Logo">
            <i class="fas fa-arrow-left close-btn" id="close-btn"></i>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../dashboard_admin.php"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
            
            <li class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle active">
                    <span><i class="fas fa-database fa-fw"></i> Master Data</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </a>
                <ul class="submenu active">
                    <li><a href="data_santri.php">Santri</a></li>
                    <li><a href="data_pengajar.php">Guru</a></li>
                    <li><a href="data_kelas.php">Kelas</a></li>
                    <li><a href="tahun_ajaran.php">Tahun Ajaran</a></li>
                    <li class="active-sub"><a href="jadwal_pelajaran.php">Jadwal Pelajaran</a></li>
                </ul>
            </li>
            
            <li><a href="../pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a></li>

            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    <span><i class="fas fa-file-alt fa-fw"></i> Laporan</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </a>
                <ul class="submenu">
                    <li><a href="../laporan/laporan_guru.php">Laporan Daftar Guru</a></li>
                    <li><a href="../laporan/laporan_santri.php">Laporan Daftar Santri</a></li>
                    <li><a href="../laporan/laporan_nilai.php">Laporan Daftar Nilai</a></li>
                </ul>
            </li>
            
            <li><a href="../ganti_pw.php"><i class="fas fa-lock fa-fw"></i> Ganti Password</a></li>
            <li class="logout"><a href="../../logout.php" id="btn-logout"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
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
            <h1>Jadwal Pelajaran</h1>
            <?php echo $status_msg; ?>

            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Tambah Penugasan Guru</h3>
                <form method="POST" class="form-grid">
                    <div class="form-group">
                        <label>Pilih Guru</label>
                        <select name="id_pengajar" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($list_guru as $g): ?>
                                <option value="<?php echo $g['id']; ?>"><?php echo $g['nama_lengkap']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Kelas</label>
                        <select name="id_kelas" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($list_kelas as $k): ?>
                                <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kelas'] . " (" . $k['tahun_ajaran'] . ")"; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Mata Pelajaran</label>
                        <select name="id_materi" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php foreach($list_materi as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['nama_materi']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" placeholder="2025/2026" required>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="tambah" class="btn-simpan"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </form>
            </div>

            <div class="card card-table">
                <div class="card-body">
                    <h3 style="margin: 20px;">Daftar Penugasan Mengajar</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Guru</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Tahun Ajaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data_jadwal)): ?>
                                <tr><td colspan="6" class="no-data" style="text-align:center; padding:30px;">Belum ada jadwal yang ditambahkan.</td></tr>
                            <?php else: ?>
                                <?php $no=1; foreach($data_jadwal as $j): ?>
                                <tr>
                                    <td><?php echo $no++; ?>.</td>
                                    <td><?php echo htmlspecialchars($j['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($j['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($j['nama_materi']); ?></td>
                                    <td><?php echo htmlspecialchars($j['tahun_ajaran']); ?></td>
                                    <td>
                                        <a href="#" onclick="konfirmasiHapus(<?php echo $j['id']; ?>)" style="color:#dc3545; text-decoration:none; font-weight:600;">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </a>
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
        // Sidebar & Dropdown
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
        
        // Logout
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
                    if (result.isConfirmed) { window.location.href = btnLogout.href; }
                });
            });
        }

        // Hapus Jadwal
        function konfirmasiHapus(id) {
            Swal.fire({
                title: 'Hapus Jadwal?',
                text: "Data penugasan ini akan dihapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'jadwal_pelajaran.php?hapus=' + id;
                }
            });
        }
    </script>

</body>
</html>