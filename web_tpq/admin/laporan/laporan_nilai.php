<?php
session_start();
require '../../koneksi.php';

// --- 1. KEAMANAN ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// --- 2. AMBIL OPSI TAHUN AJARAN (Untuk Filter) ---
// Kita ambil semua tahun ajaran yang pernah ada di tabel nilai
$sql_tahun = "SELECT DISTINCT tahun_ajaran FROM nilai_raport ORDER BY tahun_ajaran DESC";
$result_tahun = $koneksi->query($sql_tahun);
$opsi_tahun = [];
while($t = $result_tahun->fetch_assoc()) {
    $opsi_tahun[] = $t['tahun_ajaran'];
}

// Tangkap pilihan filter dari URL, defaultnya 'semua'
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : 'semua';
$filter_semester = isset($_GET['semester']) ? $_GET['semester'] : 'semua'; // Opsional: Tambahan filter semester

// --- 3. AMBIL DAFTAR MATERI (HEADER TABEL) ---
$sql_materi = "SELECT id, nama_materi FROM materi ORDER BY nama_materi ASC";
$result_materi = $koneksi->query($sql_materi);
$list_materi = [];
if ($result_materi->num_rows > 0) {
    while($m = $result_materi->fetch_assoc()) {
        $list_materi[] = $m;
    }
}

// --- 4. QUERY DATA NILAI DENGAN FILTER ---
$sql = "SELECT 
            ds.id AS id_santri,
            ds.nama_lengkap AS nama_santri,
            k.nama_kelas,
            m.id AS id_materi,
            nr.nilai,
            nr.semester,
            nr.tahun_ajaran
        FROM 
            nilai_raport nr
        JOIN 
            data_santri ds ON nr.id_santri = ds.id
        LEFT JOIN 
            kelas k ON ds.id_kelas = k.id
        JOIN 
            materi m ON nr.id_materi = m.id
        WHERE 1=1"; // Trik agar mudah menyambung AND

// Terapkan Filter Tahun
if ($filter_tahun != 'semua') {
    $tahun_safe = $koneksi->real_escape_string($filter_tahun);
    $sql .= " AND nr.tahun_ajaran = '$tahun_safe'";
}

// Terapkan Filter Semester (Opsional, biar makin rapi)
if ($filter_semester != 'semua') {
    $smt_safe = $koneksi->real_escape_string($filter_semester);
    $sql .= " AND nr.semester = '$smt_safe'";
}

$sql .= " ORDER BY nr.tahun_ajaran DESC, nr.semester DESC, k.nama_kelas ASC, ds.nama_lengkap ASC";

$result = $koneksi->query($sql);

// --- 5. LOGIKA PIVOT ARRAY ---
$rekap_nilai = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // KUNCI UNIK: Gabungan ID Santri + Tahun + Semester
        // Supaya jika filter "Semua", Budi (2023) dan Budi (2024) menjadi 2 baris berbeda
        $unique_key = $row['id_santri'] . '_' . $row['tahun_ajaran'] . '_' . $row['semester'];
        
        if (!isset($rekap_nilai[$unique_key])) {
            $rekap_nilai[$unique_key] = [
                'nama' => $row['nama_santri'],
                'kelas' => $row['nama_kelas'],
                'tahun' => $row['tahun_ajaran'],
                'semester' => $row['semester'],
                'nilai_mapel' => []
            ];
        }
        
        $rekap_nilai[$unique_key]['nilai_mapel'][$row['id_materi']] = $row['nilai'];
    }
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Nilai - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS SAMA SEPERTI SEBELUMNYA */
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
        .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: var(--lebar-sidebar); background-color: var(--warna-hijau); color: white; z-index: 1000; transform: translateX(-100%); transition: transform 0.3s ease-out; display: flex; flex-direction: column; }
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
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .dropdown-toggle .toggle-icon { font-size: 0.8rem; transition: transform 0.3s ease; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; display: none; }
        .sidebar-nav .submenu.active { max-height: 500px; display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        .sidebar-nav .submenu li a:hover { background-color: rgba(255, 255, 255, 0.05); }
        .sidebar-nav .dropdown-toggle.active .toggle-icon { transform: rotate(180deg); }
        .sidebar-nav .submenu li.active-sub > a { background-color: rgba(255, 255, 255, 0.2); font-weight: 600; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-out, visibility 0s 0.3s linear; }
        .overlay.active { opacity: 1; visibility: visible; transition: opacity 0.3s ease-out; }
        .main-content { width: 100%; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--warna-hijau); color: white; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; }
        .header-title { font-size: 0.9rem; font-weight: 500; line-height: 1.3; }
        .header-right .user-profile { display: flex; align-items: center; text-align: right; text-decoration: none; color: white; }
        .user-profile .user-info { display: flex; flex-direction: column; }
        .user-profile span { font-size: 0.8rem; font-weight: 500; }
        .user-profile .icon-wrapper { background-color: white; color: var(--warna-hijau); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-left: 10px; font-size: 1.1rem; }
        .dashboard-area { padding: 25px 20px; }
        .dashboard-area h1 { color: var(--warna-teks); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; }
        .card-table { background-color: white; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); overflow: hidden; }
        
        /* CSS BARU UNTUK HEADER FILTER */
        .card-header { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 15px; }
        
        .filter-form { display: flex; gap: 10px; align-items: center; }
        .filter-select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.9rem; color: #333; outline: none; }
        .filter-select:focus { border-color: var(--warna-hijau); }
        
        .btn-cetak { background-color: #0d6efd; color: white; text-decoration: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: background-color 0.3s; }
        .btn-cetak:hover { background-color: #0b5ed7; }
        .btn-cetak i { margin-right: 5px; }
        
        .card-body { padding: 0; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f0f0f0; color: var(--warna-teks-abu); white-space: nowrap; }
        .data-table th { background-color: #f9f9f9; color: var(--warna-teks); font-weight: 600; text-align: center; border: 1px solid #eee; }
        .data-table td { text-align: center; border: 1px solid #eee; }
        .data-table td:nth-child(2) { text-align: left; }
        
        .data-table tbody tr:hover { background-color: var(--warna-hijau-muda); }
        .no-data { text-align: center; padding: 40px; color: var(--warna-teks-abu); font-style: italic; }
        
        @media print {
            body { background-color: white; font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: black; }
            .sidebar, .header, .hamburger-btn, .user-profile, .filter-form { display: none !important; } /* Sembunyikan Filter saat Print */
            .main-content { width: 100% !important; padding: 0 !important; margin: 0 !important; }
            .dashboard-area { padding: 0 !important; }
            .dashboard-area::before { content: "Laporan Daftar Nilai - TPQ Daarul Hikmah"; display: block; text-align: center; font-size: 1.5rem; font-weight: bold; margin-bottom: 10px; }
            .print-info::after { 
                content: "Tahun Ajaran: <?php echo ($filter_tahun == 'semua') ? 'Semua' : $filter_tahun; ?> | Semester: <?php echo ucfirst($filter_semester); ?>"; 
                display: block; text-align: center; margin-bottom: 20px; font-size: 1rem; 
            }
            h1 { display: none; }
            .card-table { box-shadow: none !important; border: 1px solid #000 !important; border-radius: 0 !important; }
            .data-table, .data-table th, .data-table td { border: 1px solid #000 !important; font-size: 10pt !important; color: #000 !important; white-space: normal; padding: 5px; }
            .data-table th { background-color: #eee !important; }
            .card-header { display: none; } /* Tombol cetak juga hilang */
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
            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    <span><i class="fas fa-database fa-fw"></i> Master Data</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </a>
                <ul class="submenu">
                    <li><a href="../data_master/data_santri.php">Santri</a></li>
                    <li><a href="../data_master/data_pengajar.php">Guru</a></li>
                    <li><a href="../data_master/data_kelas.php">Kelas</a></li>
                    <li><a href="../data_master/tahun_ajaran.php">Tahun Ajaran</a></li>
                    <li><a href="../data_master/jadwal_pelajaran.php">Jadwal Pelajaran</a></li>
                </ul>
            </li>
            <li><a href="../pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a></li>
            <li class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle active">
                    <span><i class="fas fa-file-alt fa-fw"></i> Laporan</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </a>
                <ul class="submenu active">
                    <li><a href="laporan_guru.php">Laporan Daftar Guru</a></li>
                    <li><a href="laporan_santri.php">Laporan Daftar Santri</a></li>
                    <li class="active-sub"><a href="laporan_nilai.php">Laporan Daftar Nilai</a></li>
                </ul>
            </li>
            <li><a href="#"><i class="fas fa-lock fa-fw"></i> Ganti Password</a></li>
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
                    <div class="user-info"><span><?php echo htmlspecialchars($_SESSION['email']); ?></span></div>
                    <div class="icon-wrapper"><i class="fas fa-user-shield"></i></div>
                </a>
            </div>
        </header>

        <main class="dashboard-area">
            <h1>Laporan Daftar Nilai</h1>
            <div class="print-info"></div>

            <div class="card-table">
                <div class="card-header">
                    <form method="GET" action="" class="filter-form">
                        <select name="tahun" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?php if($filter_tahun == 'semua') echo 'selected'; ?>>- Semua Tahun Ajaran -</option>
                            <?php foreach ($opsi_tahun as $thn): ?>
                                <option value="<?php echo $thn; ?>" <?php if($filter_tahun == $thn) echo 'selected'; ?>>
                                    <?php echo $thn; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="semester" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?php if($filter_semester == 'semua') echo 'selected'; ?>>- Semua Semester -</option>
                            <option value="ganjil" <?php if($filter_semester == 'ganjil') echo 'selected'; ?>>Ganjil</option>
                            <option value="genap" <?php if($filter_semester == 'genap') echo 'selected'; ?>>Genap</option>
                        </select>
                    </form>

                    <a href="javascript:window.print()" class="btn-cetak">
                        <i class="fas fa-print"></i> Cetak
                    </a>
                </div>

                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th rowspan="2" width="5%">No.</th>
                                <th rowspan="2" width="20%">Nama Santri</th>
                                <th rowspan="2">Kelas</th>
                                <th rowspan="2">Th. Ajaran</th>
                                <th rowspan="2">Smt</th>
                                <th colspan="<?php echo count($list_materi); ?>">Mata Pelajaran</th>
                                <th rowspan="2">Rata-rata</th>
                            </tr>
                            <tr>
                                <?php foreach ($list_materi as $materi): ?>
                                    <th><?php echo htmlspecialchars($materi['nama_materi']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rekap_nilai)): ?>
                                <tr>
                                    <td colspan="<?php echo 6 + count($list_materi); ?>" class="no-data">
                                        Data tidak ditemukan untuk filter ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($rekap_nilai as $siswa): ?>
                                <tr>
                                    <td><?php echo $no++; ?>.</td>
                                    <td><?php echo htmlspecialchars($siswa['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($siswa['kelas'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($siswa['tahun'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($siswa['semester'] ?? '-'); ?></td>
                                    
                                    <?php 
                                        $total_nilai = 0;
                                        $jumlah_mapel_diisi = 0;
                                        
                                        foreach ($list_materi as $materi) {
                                            $id_mapel = $materi['id'];
                                            $nilai = isset($siswa['nilai_mapel'][$id_mapel]) ? $siswa['nilai_mapel'][$id_mapel] : '-';
                                            
                                            if (is_numeric($nilai)) {
                                                $total_nilai += $nilai;
                                                $jumlah_mapel_diisi++;
                                            }
                                            
                                            echo "<td>" . htmlspecialchars($nilai) . "</td>";
                                        }

                                        $rata_rata = ($jumlah_mapel_diisi > 0) ? round($total_nilai / $jumlah_mapel_diisi, 1) : '-';
                                    ?>
                                    
                                    <td style="font-weight:bold;"><?php echo $rata_rata; ?></td>
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
                let isAlreadyActive = this.classList.contains('active');
                document.querySelectorAll('.submenu.active').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.dropdown-toggle.active').forEach(t => t.classList.remove('active'));
                if (!isAlreadyActive) {
                    this.classList.add('active');
                    submenu.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>