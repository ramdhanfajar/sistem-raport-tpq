<?php
session_start();
require '../../../koneksi.php';

// 1. KEAMANAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$id_santri = $_GET['id'] ?? null;
if (!$id_santri) {
    header("Location: data_santri.php");
    exit();
}

$status_msg = '';

// 2. LOGIKA UPDATE (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_santri_post = $_POST['id_santri'];
    $user_id_post = $_POST['user_id'];
    
    // Data Akun
    $email = $_POST['email'];
    $password_baru = $_POST['password']; // Kosongkan jika tidak ingin ubah
    
    // Data Diri
    $nama_lengkap = $_POST['nama_lengkap'];
    $nis = $_POST['nis'];
    $id_kelas = !empty($_POST['id_kelas']) ? $_POST['id_kelas'] : null;
    $nik = $_POST['nik'];
    $no_kk = $_POST['no_kk'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $alamat = $_POST['alamat'];
    $no_hp = $_POST['no_hp'];
    
    // Data Ortu
    $nama_ayah = $_POST['nama_ayah'];
    $pekerjaan_ayah = $_POST['pekerjaan_ayah'];
    $nama_ibu = $_POST['nama_ibu'];
    $pekerjaan_ibu = $_POST['pekerjaan_ibu'];
    $gaji_per_bulan = $_POST['gaji_per_bulan'];

    $koneksi->begin_transaction();
    try {
        // A. Update Tabel Users
        if (!empty($password_baru)) {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_user = $koneksi->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt_user->bind_param("ssi", $email, $hashed_password, $user_id_post);
        } else {
            $stmt_user = $koneksi->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt_user->bind_param("si", $email, $user_id_post);
        }
        $stmt_user->execute();
        $stmt_user->close();

        // B. Update Tabel Data Santri
        $stmt_santri = $koneksi->prepare(
            "UPDATE data_santri SET 
                nama_lengkap=?, nis=?, id_kelas=?, nik=?, no_kk=?, jenis_kelamin=?, 
                tempat_lahir=?, tanggal_lahir=?, alamat=?, no_hp=?, 
                nama_ayah=?, pekerjaan_ayah=?, nama_ibu=?, pekerjaan_ibu=?, gaji_per_bulan=?
             WHERE id=?"
        );
        $stmt_santri->bind_param(
            "ssissssssssssssi", 
            $nama_lengkap, $nis, $id_kelas, $nik, $no_kk, $jenis_kelamin, 
            $tempat_lahir, $tanggal_lahir, $alamat, $no_hp, 
            $nama_ayah, $pekerjaan_ayah, $nama_ibu, $pekerjaan_ibu, $gaji_per_bulan, 
            $id_santri_post
        );
        $stmt_santri->execute();
        $stmt_santri->close();

        $koneksi->commit();
        $status_msg = "<div class='alert alert-success'>Data santri berhasil diperbarui.</div>";

    } catch (Exception $e) {
        $koneksi->rollback();
        $status_msg = "<div class='alert alert-danger'>Gagal update: " . $e->getMessage() . "</div>";
    }
}

// 3. AMBIL DATA LAMA (GET)
$stmt = $koneksi->prepare("SELECT s.*, u.email FROM data_santri s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $id_santri);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "Data santri tidak ditemukan.";
    exit();
}

// 4. AMBIL LIST KELAS
$list_kelas = [];
$res_kelas = $koneksi->query("SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC");
while($row = $res_kelas->fetch_assoc()) {
    $list_kelas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Santri</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Menggunakan style konsisten dengan halaman admin lainnya */
        :root { --warna-hijau: #00a86b; --warna-hijau-muda: #e6f7f0; --warna-latar: #f4f7f6; --warna-teks: #333333; --warna-teks-abu: #555; --lebar-sidebar: 280px; }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--warna-latar); box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }

        /* Sidebar & Header */
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
        
        /* Dropdown Sidebar */
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); display: none; }
        .sidebar-nav .submenu.active { display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        .sidebar-nav .submenu li.active-sub > a { background-color: rgba(255, 255, 255, 0.2); font-weight: 600; }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-out, visibility 0s 0.3s linear; }
        .overlay.active { opacity: 1; visibility: visible; transition: opacity 0.3s ease-out; }

        .main-content { width: 100%; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--warna-hijau); color: white; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; }
        .header-right span { font-weight: 500; font-size: 0.9rem; }

        /* Form Area */
        .dashboard-area { padding: 25px 20px; }
        .card { background-color: white; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); margin-bottom: 20px; overflow: hidden; max-width: 800px; margin: 0 auto 20px auto; }
        .card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; color: var(--warna-hijau); font-size: 1.2rem; }
        
        .btn-kembali { background-color: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: 0.3s; }
        .btn-kembali:hover { background-color: #5a6268; }
        
        .card-body { padding: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 15px; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr; gap: 20px; } }
        
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; font-weight: 600; color: var(--warna-teks-abu); margin-bottom: 8px; font-size: 0.9rem; }
        .form-group.required label::after { content: ' *'; color: #dc3545; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-full { grid-column: 1 / -1; }
        .section-title { grid-column: 1 / -1; color: var(--warna-hijau); border-bottom: 2px solid var(--warna-hijau-muda); padding-bottom: 10px; margin-top: 10px; margin-bottom: 10px; font-size: 1.1rem; font-weight: 600; }
        
        .form-footer { padding-top: 20px; margin-top: 20px; border-top: 1px solid #f0f0f0; text-align: right; }
        .btn-simpan { background-color: var(--warna-hijau); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; width: auto; transition: 0.3s; }
        .btn-simpan:hover { background-color: #008a5a; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background-color: var(--warna-hijau-muda); color: var(--warna-hijau); }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../../../img/logo1.png" alt="Logo">
            <i class="fas fa-arrow-left close-btn" id="close-btn"></i>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../dashboard_admin.php"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
            
            <li class="nav-item dropdown active">
                <a href="#" class="dropdown-toggle active"><span><i class="fas fa-database fa-fw"></i> Master Data</span> <i class="fas fa-chevron-down toggle-icon"></i></a>
                <ul class="submenu active">
                    <li class="active-sub"><a href="data_santri.php">Santri</a></li>
                    <li><a href="data_pengajar.php">Guru</a></li>
                    <li><a href="data_kelas.php">Kelas</a></li>
                    <li><a href="tahun_ajaran.php">Tahun Ajaran</a></li>
                </ul>
            </li>
            
            <li><a href="../../pengolahan_nilai.php"><i class="fas fa-chart-bar fa-fw"></i> Pengolahan Nilai</a></li>

            <li class="nav-item dropdown">
                <a href="#" class="dropdown-toggle"><span><i class="fas fa-file-alt fa-fw"></i> Laporan</span> <i class="fas fa-chevron-down toggle-icon"></i></a>
                <ul class="submenu">
                    <li><a href="../../laporan/laporan_guru.php">Laporan Daftar Guru</a></li>
                    <li><a href="../../laporan/laporan_santri.php">Laporan Daftar Santri</a></li>
                    <li><a href="../../laporan/laporan_nilai.php">Laporan Daftar Nilai</a></li>
                </ul>
            </li>
            
            <li><a href="../../ganti_pw.php"><i class="fas fa-lock fa-fw"></i> Ganti Password</a></li>
            <li class="logout"><a href="../../../logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="overlay" id="overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><i class="fas fa-bars"></i></button>
                <div class="header-logo"><img src="../../../img/logo1.png" alt="Logo"></div>
                <div class="header-title">Sistem Raport<br>Taman Pendidikan Al-Qur'an</div>
            </div>
            <div class="header-right"><span>Administrator</span></div>
        </header>

        <main class="dashboard-area">
            <form action="" method="POST">
                <input type="hidden" name="id_santri" value="<?php echo $data['id']; ?>">
                <input type="hidden" name="user_id" value="<?php echo $data['user_id']; ?>">

                <div class="card">
                    <div class="card-header">
                        <h3>Edit Santri: <?php echo htmlspecialchars($data['nama_lengkap']); ?></h3>
                        <a href="../data_santri.php" class="btn-kembali"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                    
                    <div class="card-body">
                        <?php echo $status_msg; ?>

                        <div class="form-grid">
                            <div class="section-title">1. Akun Login</div>
                            <div class="form-group required">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Password Baru (Opsional)</label>
                                <input type="password" name="password" placeholder="Isi jika ingin ganti password">
                            </div>

                            <div class="section-title">2. Data Diri</div>
                            <div class="form-group required">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($data['nama_lengkap']); ?>" required>
                            </div>
                            <div class="form-group required">
                                <label>NIS</label>
                                <input type="text" name="nis" value="<?php echo htmlspecialchars($data['nis']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Kelas</label>
                                <select name="id_kelas">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($list_kelas as $k): ?>
                                        <option value="<?php echo $k['id']; ?>" <?php echo ($data['id_kelas'] == $k['id']) ? 'selected' : ''; ?>>
                                            <?php echo $k['nama_kelas'] . " (" . $k['tahun_ajaran'] . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="jenis_kelamin">
                                    <option value="Laki-laki" <?php echo ($data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php echo ($data['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NIK</label>
                                <input type="text" name="nik" value="<?php echo htmlspecialchars($data['nik']); ?>">
                            </div>
                            <div class="form-group">
                                <label>No. KK</label>
                                <input type="text" name="no_kk" value="<?php echo htmlspecialchars($data['no_kk']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($data['tempat_lahir']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($data['tanggal_lahir']); ?>">
                            </div>
                            <div class="form-group">
                                <label>No. HP</label>
                                <input type="text" name="no_hp" value="<?php echo htmlspecialchars($data['no_hp']); ?>">
                            </div>
                            <div class="form-group form-full">
                                <label>Alamat</label>
                                <textarea name="alamat"><?php echo htmlspecialchars($data['alamat']); ?></textarea>
                            </div>

                            <div class="section-title">3. Data Orang Tua</div>
                            <div class="form-group">
                                <label>Nama Ayah</label>
                                <input type="text" name="nama_ayah" value="<?php echo htmlspecialchars($data['nama_ayah']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Pekerjaan Ayah</label>
                                <input type="text" name="pekerjaan_ayah" value="<?php echo htmlspecialchars($data['pekerjaan_ayah']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nama Ibu</label>
                                <input type="text" name="nama_ibu" value="<?php echo htmlspecialchars($data['nama_ibu']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Pekerjaan Ibu</label>
                                <input type="text" name="pekerjaan_ibu" value="<?php echo htmlspecialchars($data['pekerjaan_ibu']); ?>">
                            </div>
                            <div class="form-group form-full">
                                <label>Gaji Per Bulan</label>
                                <input type="text" name="gaji_per_bulan" value="<?php echo htmlspecialchars($data['gaji_per_bulan']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </form>
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
                this.classList.toggle('active');
                submenu.classList.toggle('active');
            });
        });
    </script>
</body>
</html>