<?php
session_start();
require '../../../koneksi.php';

// 1. KEAMANAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_santri = $_GET['id'];

    // 2. AMBIL user_id DULU
    // Kita harus menghapus dari tabel 'users' agar semua data terkait ikut terhapus (Cascade)
    $stmt_get = $koneksi->prepare("SELECT user_id FROM data_santri WHERE id = ?");
    $stmt_get->bind_param("i", $id_santri);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        
        // 3. HAPUS USER (Ini akan memicu ON DELETE CASCADE)
        // Data santri dan nilai raport akan otomatis terhapus
        $stmt_del = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->bind_param("i", $user_id);
        
        if ($stmt_del->execute()) {
            // Sukses
            echo "<script>alert('Data santri berhasil dihapus.'); window.location='../data_santri.php';</script>";
        } else {
            // Gagal
            echo "<script>alert('Gagal menghapus data.'); window.location='../data_santri.php';</script>";
        }
        $stmt_del->close();
    } else {
        echo "<script>alert('Data santri tidak ditemukan.'); window.location='../data_santri.php';</script>";
    }
    $stmt_get->close();

} else {
    header("Location: ../data_santri.php");
}
$koneksi->close();
?>