<?php
session_start();
require '../../../koneksi.php'; // Path naik 2 level

// --- 1. KEAMANAN: Hanya Admin yang boleh akses ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

// Ambil ID dari URL
$id_kelas = $_GET['id'] ?? null;
if (!$id_kelas) {
    header("Location: ../data_kelas.php");
    exit();
}

$status_msg = '';

// --- 2. LOGIKA SIMPAN PERUBAHAN (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = $_POST['nama_kelas'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $id_pengajar = !empty($_POST['id_pengajar']) ? $_POST['id_pengajar'] : null;

    // Validasi
    if (empty($nama_kelas) || empty($tahun_ajaran)) {
        $status_msg = 'gagal_kosong';
    } else {
        $koneksi->begin_transaction();
        try {
            $stmt = $koneksi->prepare("UPDATE kelas SET nama_kelas = ?, tahun_ajaran = ?, id_pengajar = ? WHERE id = ?");
            $stmt->bind_param("ssii", $nama_kelas, $tahun_ajaran, $id_pengajar, $id_kelas);
            
            if ($stmt->execute()) {
                $koneksi->commit();
                // Redirect dengan status sukses
                header("Location: edit_kelas.php?id=$id_kelas&status=sukses");
                exit();
            } else {
                throw new Exception("Gagal mengeksekusi query.");
            }
        } catch (Exception $e) {
            $koneksi->rollback();
            $status_msg = 'gagal_db';
        }
    }
}

// --- 3. AMBIL DATA KELAS SAAT INI ---
$stmt = $koneksi->prepare("SELECT * FROM kelas WHERE id = ?");
$stmt->bind_param("i", $id_kelas);
$stmt->execute();
$result = $stmt->get_result();
$data_kelas = $result->fetch_assoc();
$stmt->close();

if (!$data_kelas) {
    echo "Data kelas tidak ditemukan.";
    exit();
}

// --- 4. AMBIL DATA GURU (UNTUK DROPDOWN WALI KELAS) ---
$list_guru = [];
$result_guru = $koneksi->query("SELECT id, nama_lengkap FROM data_pengajar ORDER BY nama_lengkap ASC");
while ($row = $result_guru->fetch_assoc()) {
    $list_guru[] = $row;
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Kelas</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* CSS Template Admin */
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

        /* Sidebar & Overlay */
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
        
        /* Dropdown Sidebar */
        .sidebar-nav li.dropdown { position: relative; }
        .sidebar-nav .dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
        .sidebar-nav .submenu { list-style: none; padding-left: 0; margin: 0; background-color: rgba(0, 0, 0, 0.15); display: none; }
        .sidebar-nav .submenu.active { display: block; }
        .sidebar-nav .submenu li a { padding-left: 65px; font-size: 0.9rem; font-weight: 400; }
        .sidebar-nav .submenu li.active-sub > a { background-color: rgba(255, 255, 255, 0.2); font-weight: 600; }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; visibility: hidden; transition: opacity 0.3s ease-out, visibility 0s 0.3s linear; }
        .overlay.active { opacity: 1; visibility: visible; transition: opacity 0.3s ease-out; }

        /* Main Content */
        .main-content { width: 100%; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: var(--warna-hijau); color: white; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; }
        .header-right span { font-weight: 500; font-size: 0.9rem; }

        /* Dashboard Area */
        .dashboard-area { padding: 25px 40px; }
        .card { background-color: white; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); margin-bottom: 20px; overflow: hidden; max-width: 600px; margin: 0 auto; }
        .card-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; color: var(--warna-hijau); font-size: 1.2rem; }
        .btn-kembali { background-color: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: 0.3s; }
        .btn-kembali:hover { background-color: #5a6268; }
        
        .card-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; color: var(--warna-teks-abu); margin-bottom: 8px; font-size: 0.9rem; }
        .form-group.required label::after { content: ' *'; color: #dc3545; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        .btn-simpan { background-color: var(--warna-hijau); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; width: 100%; margin-top: 10px; transition: 0.3s; }
        .btn-simpan:hover { background-color: #008a5a; }
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
                    <li><a href="data_santri.php">Santri</a></li>
                    <li><a href="data_pengajar.php">Guru</a></li>
                    <li class="active-sub"><a href="data_kelas.php">Kelas</a></li>
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
            <div class="card">
                <div class="card-header">
                    <h3>Edit Kelas</h3>
                    <a href="../data_kelas.php" class="btn-kembali"><i class="fas fa-arrow-left"></i> Kembali</a>
                </div>

                <div class="card-body">
                    <form action="" method="POST" id="form-edit-kelas">
                        <div class="form-group required">
                            <label>Nama Kelas</label>
                            <input type="text" name="nama_kelas" value="<?php echo htmlspecialchars($data_kelas['nama_kelas']); ?>" required>
                        </div>
                        
                        <div class="form-group required">
                            <label>Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" value="<?php echo htmlspecialchars($data_kelas['tahun_ajaran']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Wali Kelas (Opsional)</label>
                            <select name="id_pengajar">
                                <option value="">-- Pilih Wali Kelas --</option>
                                <?php foreach ($list_guru as $g) {
                                    $selected = ($data_kelas['id_pengajar'] == $g['id']) ? 'selected' : '';
                                    echo "<option value='" . $g['id'] . "' $selected>" . $g['nama_lengkap'] . "</option>";
                                } ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle Sidebar
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeBtn = document.getElementById('close-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        function openSidebar() { sidebar.classList.add('active'); overlay.classList.add('active'); }
        function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }
        hamburgerBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        // Toggle Dropdown
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                this.classList.toggle('active');
                submenu.classList.toggle('active');
            });
        });

        // Pop-up Sukses
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            
            if (status === 'sukses') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data kelas telah diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#00a86b'
                }).then(() => {
                    // Bersihkan URL
                    window.history.replaceState(null, null, window.location.pathname + "?id=<?php echo $id_kelas; ?>");
                });
            } else if ("<?php echo $status_msg; ?>" === 'gagal_db') {
                 Swal.fire('Gagal', 'Terjadi kesalahan database.', 'error');
            } else if ("<?php echo $status_msg; ?>" === 'gagal_kosong') {
                 Swal.fire('Peringatan', 'Nama kelas dan tahun ajaran wajib diisi.', 'warning');
            }
        });

        // Loading Animation
        document.getElementById('form-edit-kelas').addEventListener('submit', function() {
             Swal.fire({
                title: 'Menyimpan...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        });
    </script>

</body>
</html>