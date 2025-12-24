<?php
session_start();
require '../koneksi.php';

// --- 1. KEAMANAN ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pengajar') {
    header("Location: ../login.php");
    exit();
}

// --- 2. AMBIL DATA PROFIL ---
$user_id_login = $_SESSION['user_id'];
$id_pengajar_db = null;
$nama_pengajar = "Pengajar";
$file_foto_pengajar = null;

$stmt_profil = $koneksi->prepare("SELECT id, nama_lengkap, foto FROM data_pengajar WHERE user_id = ?");
$stmt_profil->bind_param("i", $user_id_login);
$stmt_profil->execute();
$result_profil = $stmt_profil->get_result();

if ($row = $result_profil->fetch_assoc()) {
    $id_pengajar_db = $row['id'];
    $nama_pengajar = $row['nama_lengkap'];
    $file_foto_pengajar = $row['foto'];
    $_SESSION['nama_lengkap'] = $nama_pengajar;
}
$stmt_profil->close();

if (!$id_pengajar_db) die("Error: Data pengajar tidak ditemukan.");

// --- 3. LOGIKA SIMPAN NILAI (POST) ---
$status_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kelas = (int)$_POST['id_kelas'];
    $id_materi = (int)$_POST['id_materi'];
    $semester = (int)$_POST['semester'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    
    // A. CEK VALIDASI JADWAL (Apakah guru ini benar mengajar mapel ini di kelas ini?)
    $stmt_valid = $koneksi->prepare("SELECT id FROM jadwal_pelajaran WHERE id_pengajar = ? AND id_kelas = ? AND id_materi = ?");
    $stmt_valid->bind_param("iii", $id_pengajar_db, $id_kelas, $id_materi);
    $stmt_valid->execute();
    if ($stmt_valid->get_result()->num_rows == 0) {
        // Guru mencoba input nilai di mapel/kelas yang bukan haknya
        die("ERROR: Anda tidak memiliki jadwal mengajar untuk mata pelajaran ini di kelas ini.");
    }
    $stmt_valid->close();

    // B. CEK STATUS KUNCI (Apakah nilai sudah dikunci admin?)
    $stmt_cek = $koneksi->prepare("SELECT status FROM status_kunci_nilai WHERE id_kelas = ? AND semester = ? AND tahun_ajaran = ?");
    $stmt_cek->bind_param("iss", $id_kelas, $semester, $tahun_ajaran);
    $stmt_cek->execute();
    $res_cek = $stmt_cek->get_result()->fetch_assoc();
    $stmt_cek->close();

    if ($res_cek && $res_cek['status'] == 'terkunci') {
        header("Location: pengolahan_nilai.php?id_kelas=$id_kelas&id_materi=$id_materi&semester=$semester&tahun_ajaran=$tahun_ajaran&status=terkunci");
        exit(); 
    }

    // C. PROSES SIMPAN
    $santri_ids = $_POST['santri_id'];
    $id_nilais = $_POST['id_nilai'];
    $nilais = $_POST['nilai'];
    // $catatans sudah dihapus

    $koneksi->begin_transaction();
    try {
        // UPDATE (Menghapus 'catatan')
        $stmt_up = $koneksi->prepare("UPDATE nilai_raport SET nilai=?, id_pengajar=? WHERE id=?");
        
        // INSERT (Menghapus 'catatan')
        $stmt_in = $koneksi->prepare("INSERT INTO nilai_raport (id_santri, id_materi, id_pengajar, nilai, semester, tahun_ajaran) VALUES (?, ?, ?, ?, ?, ?)");

        for ($i = 0; $i < count($santri_ids); $i++) {
            // Catatan dihilangkan dari bind_param
            if (!empty($id_nilais[$i])) {
                $stmt_up->bind_param("sii", $nilais[$i], $id_pengajar_db, $id_nilais[$i]);
                $stmt_up->execute();
            } elseif ($nilais[$i] !== '') { 
                $stmt_in->bind_param("iiisss", $santri_ids[$i], $id_materi, $id_pengajar_db, $nilais[$i], $semester, $tahun_ajaran);
                $stmt_in->execute();
            }
        }
        $koneksi->commit();
        $status_msg = "sukses";
    } catch (Exception $e) {
        $koneksi->rollback();
        $status_msg = "gagal";
    }
    
    header("Location: pengolahan_nilai.php?id_kelas=$id_kelas&id_materi=$id_materi&semester=$semester&tahun_ajaran=$tahun_ajaran&status=$status_msg");
    exit();
}

// --- 4. AMBIL DATA UNTUK TAMPILAN (GET) ---
$selected_kelas = $_GET['id_kelas'] ?? null;
$selected_materi = $_GET['id_materi'] ?? null;
$selected_smt = $_GET['semester'] ?? null;
$selected_thn = $_GET['tahun_ajaran'] ?? null;

// CEK STATUS KUNCI (Untuk Tampilan)
$is_locked = false;
if ($selected_kelas && $selected_smt && $selected_thn) {
    $stmt_l = $koneksi->prepare("SELECT status FROM status_kunci_nilai WHERE id_kelas=? AND semester=? AND tahun_ajaran=?");
    $stmt_l->bind_param("iss", $selected_kelas, $selected_smt, $selected_thn);
    $stmt_l->execute();
    $res_l = $stmt_l->get_result()->fetch_assoc();
    if ($res_l && $res_l['status'] == 'terkunci') $is_locked = true;
    $stmt_l->close();
}

// --- QUERY KELAS (Hanya Kelas yang DIAJAR Guru ini) ---
$list_kelas = [];
$sql_kelas = "SELECT DISTINCT k.id, k.nama_kelas, k.tahun_ajaran 
              FROM kelas k 
              JOIN jadwal_pelajaran jp ON k.id = jp.id_kelas
              WHERE jp.id_pengajar = ?
              ORDER BY k.tahun_ajaran DESC, k.nama_kelas ASC";
$stmt_k = $koneksi->prepare($sql_kelas);
$stmt_k->bind_param("i", $id_pengajar_db);
$stmt_k->execute();
$res_k = $stmt_k->get_result();
while($row = $res_k->fetch_assoc()) $list_kelas[] = $row;
$stmt_k->close();

// --- QUERY MATERI (Hanya Materi yang DIAJAR Guru ini di Kelas Terpilih) ---
$list_materi = [];
if ($selected_kelas) {
    $sql_materi = "SELECT m.id, m.nama_materi 
                   FROM materi m 
                   JOIN jadwal_pelajaran jp ON m.id = jp.id_materi
                   WHERE jp.id_pengajar = ? AND jp.id_kelas = ?
                   ORDER BY m.nama_materi ASC";
    $stmt_m = $koneksi->prepare($sql_materi);
    $stmt_m->bind_param("ii", $id_pengajar_db, $selected_kelas);
    $stmt_m->execute();
    $res_m = $stmt_m->get_result();
    while($row = $res_m->fetch_assoc()) $list_materi[] = $row;
    $stmt_m->close();
}

// --- AMBIL DATA NILAI SISWA ---
$data_siswa_nilai = [];
if ($selected_kelas && $selected_materi && $selected_smt && $selected_thn) {
    // Menghapus nr.catatan dari query
    $sql_n = "SELECT ds.id, ds.nis, ds.nama_lengkap, nr.id AS id_nilai, nr.nilai
              FROM data_santri ds
              LEFT JOIN nilai_raport nr ON ds.id = nr.id_santri AND nr.id_materi = ? AND nr.semester = ? AND nr.tahun_ajaran = ?
              WHERE ds.id_kelas = ?
              ORDER BY ds.nama_lengkap ASC";
    $stmt_n = $koneksi->prepare($sql_n);
    $stmt_n->bind_param("issi", $selected_materi, $selected_smt, $selected_thn, $selected_kelas);
    $stmt_n->execute();
    $res_n = $stmt_n->get_result();
    while($row = $res_n->fetch_assoc()) $data_siswa_nilai[] = $row;
    $stmt_n->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai - <?php echo htmlspecialchars($nama_pengajar); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* (CSS Template Final - Sama dengan file lain) */
        :root { --warna-hijau: #00a86b; --warna-hijau-muda: #e6f7f0; --warna-latar: #f4f7f6; --warna-teks: #333; --lebar-sidebar: 280px; }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background: var(--warna-latar); }
        .main-content { padding: 20px; margin-left: 0; }
        .dashboard-area { max-width: 1200px; margin: 0 auto; }
        
        /* Sidebar & Header */
        .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: var(--lebar-sidebar); background: var(--warna-hijau); color: white; z-index: 1000; transform: translateX(-100%); transition: 0.3s; display: flex; flex-direction: column; }
        .sidebar.active { transform: translateX(0); }
        .sidebar-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .sidebar-header .close-btn { font-size: 1.5rem; cursor: pointer; }
        .sidebar-nav { list-style: none; padding: 20px 0; margin: 0; flex-grow: 1; }
        .sidebar-nav li a { display: flex; align-items: center; padding: 15px 25px; color: white; text-decoration: none; font-size: 1rem; font-weight: 500; transition: 0.2s; }
        .sidebar-nav li a:hover { background: rgba(255,255,255,0.1); }
        .sidebar-nav li.active a { background: var(--warna-latar); color: var(--warna-hijau); border-left: 5px solid white; padding-left: 20px; }
        .sidebar-nav li.active i { color: var(--warna-hijau); }
        .sidebar-nav li a i { width: 30px; margin-right: 15px; }
        .sidebar-nav li.logout { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; opacity: 0; visibility: hidden; transition: 0.3s; }
        .overlay.active { opacity: 1; visibility: visible; }
        
        .header { display: flex; justify-content: space-between; padding: 15px 20px; background: var(--warna-hijau); color: white; position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; }
        .hamburger-btn { font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer; margin-right: 15px; }
        .header-logo img { width: 35px; border-radius: 50%; margin-right: 10px; }
        .header-right .user-profile { display: flex; align-items: center; color: white; text-decoration: none; }
        .user-profile .icon-wrapper { background: white; color: var(--warna-hijau); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-left: 10px; overflow: hidden; }
        .icon-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        .card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .filter-form { display: grid; grid-template-columns: 1fr; gap: 15px; }
        @media(min-width: 768px) { .filter-form { grid-template-columns: 1fr 1fr 1fr 1fr auto; align-items: end; } }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; }
        select, input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-tampil { background: var(--warna-hijau); color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; white-space: nowrap; }
        .data-table th { background: #f9f9f9; }
        .input-nilai { width: 60px; text-align: center; padding: 5px; border: 1px solid #ccc; border-radius: 5px; }
        /* CSS untuk input-catatan dihapus */
        
        .alert-locked { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 10px; border: 1px solid #ffeeba; }
        input:disabled { background: #e9ecef; color: #6c757d; cursor: not-allowed; }
        
        .btn-simpan { background: #0d6efd; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; float: right; margin-top: 10px; }
        .btn-simpan:hover { background: #0b5ed7; }

        @media (max-width: 480px) { .user-info { display: none; } }
    </style>
</head>
<body>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../img/logo1.png" alt="Logo">
            <i class="fas fa-arrow-left close-btn" id="close-btn"></i>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard_pengajar.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="biodata_pengajar.php"><i class="fas fa-user"></i> Biodata</a></li>
            <li class="active"><a href="pengolahan_nilai.php"><i class="fas fa-edit"></i> Pengolahan Nilai</a></li>
            <li><a href="wali_kelas.php"><i class="fas fa-users-cog"></i> Wali Kelas</a></li>
            <li><a href="ganti_password.php"><i class="fas fa-lock"></i> Ganti Password</a></li>
            <li class="logout"><a href="../logout.php" id="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="overlay" id="overlay"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="hamburger-btn" id="hamburger-btn"><i class="fas fa-bars"></i></button>
                <div class="header-logo"><img src="../img/logo1.png" alt="Logo"></div>
                <div class="header-title">Sistem Raport TPQ</div>
            </div>
            <div class="header-right">
                <a href="biodata_pengajar.php" class="user-profile">
                    <div class="user-info"><span><?php echo htmlspecialchars($nama_pengajar); ?></span></div>
                    <div class="icon-wrapper">
                        <?php if (!empty($file_foto_pengajar)): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($file_foto_pengajar); ?>" alt="Foto">
                        <?php else: ?>
                            <i class="fas fa-user-tie"></i>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </header>

        <main class="dashboard-area">
            <h1>Input Nilai Santri</h1>

            <?php if ($is_locked): ?>
                <div class="alert-locked">
                    <i class="fas fa-lock"></i> 
                    Nilai untuk kelas dan semester ini sudah DIKUNCI oleh Admin. Anda tidak dapat mengubah data.
                </div>
            <?php endif; ?>

            <div class="card">
                <form action="" method="GET" class="filter-form" id="form-filter">
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="id_kelas" id="id_kelas" required onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($list_kelas as $k): ?>
                                <option value="<?php echo $k['id']; ?>" <?php if ($k['id'] == $selected_kelas) echo 'selected'; ?>>
                                    <?php echo $k['nama_kelas'] . " (" . $k['tahun_ajaran'] . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Mata Pelajaran</label>
                        <select name="id_materi" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php if (empty($list_materi)): ?>
                                <option disabled>Pilih kelas dulu atau Anda belum ditugaskan di kelas ini</option>
                            <?php else: ?>
                                <?php foreach ($list_materi as $m): ?>
                                    <option value="<?php echo $m['id']; ?>" <?php if ($m['id'] == $selected_materi) echo 'selected'; ?>>
                                        <?php echo $m['nama_materi']; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <option value="1" <?php if($selected_smt=='1') echo 'selected'; ?>>Ganjil</option>
                            <option value="2" <?php if($selected_smt=='2') echo 'selected'; ?>>Genap</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" id="tahun_ajaran" readonly>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-tampil"><i class="fas fa-search"></i> Tampilkan</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($data_siswa_nilai)): ?>
            <div class="card">
                <form action="" method="POST" id="form-nilai">
                    <input type="hidden" name="id_kelas" value="<?php echo $selected_kelas; ?>">
                    <input type="hidden" name="id_materi" value="<?php echo $selected_materi; ?>">
                    <input type="hidden" name="semester" value="<?php echo $selected_smt; ?>">
                    <input type="hidden" name="tahun_ajaran" value="<?php echo $selected_thn; ?>">
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Santri</th>
                                    <th>Nilai (0-100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach($data_siswa_nilai as $s): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($s['nama_lengkap']); ?></td>
                                    <td>
                                        <input type="number" name="nilai[]" class="input-nilai" min="0" max="100" 
                                               value="<?php echo htmlspecialchars($s['nilai']); ?>"
                                               <?php echo $is_locked ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="hidden" name="santri_id[]" value="<?php echo $s['id']; ?>">
                                        <input type="hidden" name="id_nilai[]" value="<?php echo $s['id_nilai']; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!$is_locked): ?>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan Nilai</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        // Sidebar Toggle
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeBtn = document.getElementById('close-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        function openSidebar() { sidebar.classList.add('active'); overlay.classList.add('active'); }
        function closeSidebar() { sidebar.classList.remove('active'); overlay.classList.remove('active'); }
        hamburgerBtn.addEventListener('click', openSidebar);
        closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
        
        // Auto-fill Tahun Ajaran
        const selectKelas = document.getElementById('id_kelas');
        const inputTahun = document.getElementById('tahun_ajaran');
        
        function updateTahun() {
            if (selectKelas.selectedIndex > 0) {
                const text = selectKelas.options[selectKelas.selectedIndex].text;
                // Mengambil tahun ajaran dari format "Nama Kelas (Tahun Ajaran)"
                const match = text.match(/\(([^)]+)\)/);
                if (match) inputTahun.value = match[1];
            } else {
                inputTahun.value = ''; // Kosongkan jika tidak ada kelas terpilih
            }
        }
        selectKelas.addEventListener('change', updateTahun);
        // Panggil saat halaman dimuat jika sudah ada kelas yang terpilih (dari GET)
        updateTahun(); 

        // Pop-up Status
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'sukses') {
            Swal.fire('Berhasil', 'Nilai tersimpan', 'success');
        } else if (urlParams.get('status') === 'terkunci') {
            Swal.fire('Terkunci', 'Data tidak bisa diubah karena dikunci admin', 'warning');
        } else if (urlParams.get('status') === 'gagal') {
            Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan data', 'error');
        }

        // Logout Confirmation
        const btnLogout = document.getElementById('btn-logout');
        if(btnLogout) {
            btnLogout.addEventListener('click', function(e) {
                e.preventDefault(); 
                Swal.fire({ title: 'Yakin keluar?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#00a86b', confirmButtonText: 'Ya' })
                .then((result) => { if (result.isConfirmed) window.location.href = btnLogout.href; });
            });
        }
        
        // Validasi Form
        const formNilai = document.getElementById('form-nilai');
        if(formNilai) {
            formNilai.addEventListener('submit', function(e) {
                e.preventDefault();
                let valid = true;
                document.querySelectorAll('.input-nilai').forEach(el => {
                    const nilai = el.value;
                    // Hanya validasi jika nilai diisi (tidak kosong)
                    if(nilai !== '' && (nilai < 0 || nilai > 100 || isNaN(nilai))) {
                        valid = false;
                    }
                });
                
                if(!valid) {
                    Swal.fire('Error', 'Nilai harus antara 0 - 100 dan berupa angka', 'error');
                } else {
                    Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
                    this.submit();
                }
            });
        }
    </script>

</body>
</html>