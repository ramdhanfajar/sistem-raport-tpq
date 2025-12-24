<?php
session_start();
require '../../../koneksi.php'; // Path naik 2 level ke root

// --- 1. KEAMANAN: Cek Login Admin ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

// --- 2. PROSES HAPUS ---
if (isset($_GET['id'])) {
    $id_kelas = $_GET['id'];

    // Siapkan Query Hapus
    $stmt = $koneksi->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id_kelas);

    try {
        if ($stmt->execute()) {
            // JIKA SUKSES: Redirect kembali dengan status 'hapus_sukses'
            header("Location: ../data_kelas.php?status=hapus_sukses");
        } else {
            throw new Exception("Gagal mengeksekusi query hapus.");
        }
    } catch (Exception $e) {
        // JIKA GAGAL (Biasanya karena ada data santri/jadwal yang terkait)
        // Redirect kembali dengan status 'hapus_gagal'
        $error_msg = urlencode("Gagal menghapus! Pastikan tidak ada Santri atau Jadwal yang terhubung dengan kelas ini.");
        header("Location: data_kelas.php?status=hapus_gagal&msg=" . $error_msg);
    }

    $stmt->close();
} else {
    // Jika tidak ada ID, kembalikan saja
    header("Location: data_kelas.php");
}

$koneksi->close();
?>