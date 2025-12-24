<?php
session_start();
require '../../../koneksi.php'; // Path naik 2 level

// 1. KEAMANAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

$id_guru = $_GET['id'] ?? null;
if (!$id_guru) {
    header("Location: data_pengajar.php");
    exit();
}

$status_msg = '';

// 2. LOGIKA UPDATE (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_guru_post = $_POST['id_guru'];
    $user_id_post = $_POST['user_id'];
    
    // Data Akun
    $email = $_POST['email'];
    $password_baru = $_POST['password'];
    
    // Data Profil
    $nama_lengkap = $_POST['nama_lengkap'];
    $nip = $_POST['nip'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $no_telepon = $_POST['no_telepon'];
    $alamat = $_POST['alamat'];
    $nik = $_POST['nik'];
    $no_kk = $_POST['no_kk'];
    $riwayat_pendidikan = $_POST['riwayat_pendidikan'];

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

        // B. Update Tabel Data Pengajar
        $stmt_guru = $koneksi->prepare(
            "UPDATE data_pengajar SET 
                nama_lengkap=?, nip=?, no_telepon=?, alamat=?, 
                jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, 
                nik=?, no_kk=?, riwayat_pendidikan=?
             WHERE id=?"
        );
        $stmt_guru->bind_param(
            "ssssssssssi", 
            $nama_lengkap, $nip, $no_telepon, $alamat, 
            $jenis_kelamin, $tempat_lahir, $tanggal_lahir, 
            $nik, $no_kk, $riwayat_pendidikan, 
            $id_guru_post
        );
        $stmt_guru->execute();
        $stmt_guru->close();

        $koneksi->commit();
        // Redirect dengan status sukses
        header("Location: ../data_pengajar.php?id=$id_guru&status=sukses");
        exit();

    } catch (Exception $e) {
        $koneksi->rollback();
        $status_msg = "Gagal update: " . $e->getMessage();
        header("Location: ../data_guru.php?id=$id_guru&status=gagal&msg=" . urlencode($status_msg));
        exit();
    }
}

// 3. AMBIL DATA LAMA (GET)
$stmt = $koneksi->prepare("SELECT dp.*, u.email FROM data_pengajar dp JOIN users u ON dp.user_id = u.id WHERE dp.id = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data guru tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Guru</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Menggunakan CSS yang sama dengan tambah_guru.php agar konsisten */
        :root { --warna-hijau: #00a86b; --warna-hijau-muda: #e6f7f0; --warna-latar: #f4f7f6; --warna-teks: #333; --warna-teks-abu: #555; --lebar-sidebar: 280px; }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--warna-latar); box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }
        
        /* Sidebar & Header (Copy dari file lain jika perlu tampilan full, disini saya fokus ke konten utama) */
        .main-content { width: 100%; min-height: 100vh; padding: 20px; display: flex; justify-content: center; }
        
        .card { background-color: white; border-radius: 15px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.07); margin-bottom: 20px; overflow: hidden; width: 100%; max-width: 800px; }
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
    </style>
</head>
<body>

    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h3>Edit Data Guru</h3>
                <a href="../data_pengajar.php" class="btn-kembali"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            <div class="card-body">
                
                <form action="" method="POST" id="form-edit-guru">
                    <input type="hidden" name="id_guru" value="<?php echo $data['id']; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $data['user_id']; ?>">

                    <div class="form-grid">
                        <div class="section-title">1. Akun Login</div>
                        <div class="form-group required">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru (Opsional)</label>
                            <input type="password" name="password" placeholder="Biarkan kosong jika tidak diubah">
                        </div>

                        <div class="section-title">2. Biodata Guru</div>
                        <div class="form-group required">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($data['nama_lengkap']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" value="<?php echo htmlspecialchars($data['nip']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin">
                                <option value="Laki-laki" <?php echo ($data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php echo ($data['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($data['no_telepon']); ?>">
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
                            <label>NIK</label>
                            <input type="text" name="nik" value="<?php echo htmlspecialchars($data['nik']); ?>">
                        </div>
                        <div class="form-group">
                            <label>No. KK</label>
                            <input type="text" name="no_kk" value="<?php echo htmlspecialchars($data['no_kk']); ?>">
                        </div>
                        <div class="form-group form-full">
                            <label>Alamat</label>
                            <textarea name="alamat"><?php echo htmlspecialchars($data['alamat']); ?></textarea>
                        </div>
                        <div class="form-group form-full">
                            <label>Riwayat Pendidikan</label>
                            <textarea name="riwayat_pendidikan"><?php echo htmlspecialchars($data['riwayat_pendidikan']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Pop-up Sukses/Gagal
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const msg = urlParams.get('msg');

            if (status === 'sukses') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data guru telah diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#00a86b'
                }).then(() => {
                    // Opsional: Redirect ke data_pengajar.php setelah sukses
                    // window.location.href = 'data_pengajar.php';
                    // Atau bersihkan URL:
                    window.history.replaceState(null, null, window.location.pathname + '?id=<?php echo $id_guru; ?>');
                });
            } else if (status === 'gagal') {
                Swal.fire({
                    title: 'Gagal!',
                    text: msg || 'Terjadi kesalahan saat menyimpan.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        });

        // Loading
        document.getElementById('form-edit-guru').addEventListener('submit', function() {
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