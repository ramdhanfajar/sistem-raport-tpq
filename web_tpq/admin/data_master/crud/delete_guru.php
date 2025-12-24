<?php
session_start();
require '../../../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../login.php");
    exit();
}

// Ambil ID dari parameter GET
$id_guru = $_GET['id'] ?? null;

// Jika ID tidak ada, kembalikan ke halaman data
if (!$id_guru) {
    header("Location: ../data_pengajar.php");
    exit();
}

// --- PROSES HAPUS (JIKA DIKONFIRMASI) ---
$hapus_status = '';

// Ambil user_id dulu sebelum dihapus
$stmt = $koneksi->prepare("SELECT user_id FROM data_pengajar WHERE id = ?");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    
    // Hapus dari tabel users (Cascade akan menghapus data_pengajar)
    $stmt_del = $koneksi->prepare("DELETE FROM users WHERE id = ?");
    $stmt_del->bind_param("i", $user_id);
    
    if ($stmt_del->execute()) {
        // Redirect ke data_pengajar dengan pesan sukses
        header("Location: ../data_pengajar.php?status=hapus_sukses");
        exit();
    } else {
        // Redirect dengan pesan gagal
        header("Location: ../data_pengajar.php?status=hapus_gagal");
        exit();
    }
    $stmt_del->close();
} else {
    // Data tidak ditemukan
    header("Location:../ data_pengajar.php?status=tidak_ditemukan");
    exit();
}

$stmt->close();
$koneksi->close();
?>