-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Des 2025 pada 11.20
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tpq_raport`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_pengajar`
--

CREATE TABLE `data_pengajar` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `no_kk` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `riwayat_pendidikan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `data_pengajar`
--

INSERT INTO `data_pengajar` (`id`, `user_id`, `nama_lengkap`, `nip`, `no_telepon`, `alamat`, `nik`, `no_kk`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `no_hp`, `riwayat_pendidikan`, `foto`) VALUES
(1, 2, 'Asep budiono, S.Pd', 'NIP001', '', '', '29292929299', '0928272827287', 'Laki-laki', '', '0000-00-00', '', '', 'pengajar_1_1762779035_qrcode_258946772_15aa28a50083ae58e73d73546c15fa0b.png'),
(3, 13, 'nigrum nur hasanah S.pd', 'NIP002', '', '', '', '', 'Laki-laki', '', '0000-00-00', NULL, '', NULL),
(4, 15, 'Supardi S.pd', 'NIP003', '', '', '', '', 'Laki-laki', '', '0000-00-00', NULL, '', NULL),
(5, 16, 'Oktavia nur S.pd', 'NIP004', '', '', '', '', 'Laki-laki', '', '0000-00-00', NULL, '', NULL),
(6, 40, 'Ramdhan fajar Prasetyo S.Kom', 'NIP005', '', '', '', '', 'Laki-laki', '', '0000-00-00', NULL, '', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_santri`
--

CREATE TABLE `data_santri` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nis` varchar(50) DEFAULT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `no_telepon_wali` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `no_kk` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `pekerjaan_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `pekerjaan_ibu` varchar(100) DEFAULT NULL,
  `gaji_per_bulan` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `data_santri`
--

INSERT INTO `data_santri` (`id`, `user_id`, `id_kelas`, `nama_lengkap`, `nis`, `nama_wali`, `no_telepon_wali`, `alamat`, `nik`, `no_kk`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `no_hp`, `nama_ayah`, `pekerjaan_ayah`, `nama_ibu`, `pekerjaan_ibu`, `gaji_per_bulan`, `foto`) VALUES
(8, 17, 1, 'Yanto basna', '001', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(9, 18, 1, 'Yanti hawa', '002', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(10, 19, 1, 'Ahmad Susilo ', '003', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(11, 20, 1, 'Nur Sopyan', '004', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(12, 21, 1, 'Hana Nafisa', '005', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(13, 22, 1, 'Amelia Nur Hayati', '006', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(14, 23, 1, 'Fatiyatul Hasanah', '007', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(15, 24, 1, 'Ramdhan Fajar', '008', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(18, 27, 1, 'Prasetyo Fajar', '009', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(19, 28, 1, 'Rifky Fernanda', '0010', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(20, 29, 2, 'Fathiyatul Amelia', '0001', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(21, 30, 2, 'Nafisa Hana', '0002', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(22, 31, 2, 'Hardoyono', '0003', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(23, 32, 2, 'kasino Uno', '0004', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(25, 34, 2, 'Indra Subandono', '0005', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(26, 35, 2, 'Tuti Nur Fadilah', '0006', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(27, 36, 2, 'Finililah Khasanah', '0007', NULL, NULL, '', '', '', 'Perempuan', '', '0000-00-00', '', '', '', '', '', '', NULL),
(28, 37, 2, 'Reza Nofandi', '0008', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(29, 38, 2, 'Fahrezi Arlan', '0009', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL),
(30, 39, 2, 'Riski Rido', '00010', NULL, NULL, '', '', '', 'Laki-laki', '', '0000-00-00', '', '', '', '', '', '', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_pelajaran`
--

CREATE TABLE `jadwal_pelajaran` (
  `id` int(11) NOT NULL,
  `id_pengajar` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `id_materi` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal_pelajaran`
--

INSERT INTO `jadwal_pelajaran` (`id`, `id_pengajar`, `id_kelas`, `id_materi`, `tahun_ajaran`) VALUES
(7, 3, 1, 3, '2025/2026'),
(8, 3, 2, 2, '2025/2026'),
(9, 1, 1, 1, '2025/2026'),
(10, 5, 1, 2, '2025/2026'),
(11, 4, 2, 3, '2025/2026'),
(12, 5, 2, 1, '2025/2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `id_pengajar` int(11) DEFAULT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `id_pengajar`, `nama_kelas`, `tahun_ajaran`) VALUES
(1, 1, '1 MDA', '2025/2026'),
(2, 3, '2 MDA', '2025/2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `materi`
--

CREATE TABLE `materi` (
  `id` int(11) NOT NULL,
  `kode_materi` varchar(20) NOT NULL,
  `nama_materi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `materi`
--

INSERT INTO `materi` (`id`, `kode_materi`, `nama_materi`) VALUES
(1, 'M01', 'Al-Quran'),
(2, 'M02', 'Hafalan'),
(3, 'M03', 'Fiqih');

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_raport`
--

CREATE TABLE `nilai_raport` (
  `id` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `id_materi` int(11) NOT NULL,
  `id_pengajar` int(11) DEFAULT NULL,
  `nilai` varchar(10) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `semester` int(1) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `nilai_raport`
--

INSERT INTO `nilai_raport` (`id`, `id_santri`, `id_materi`, `id_pengajar`, `nilai`, `catatan`, `semester`, `tahun_ajaran`) VALUES
(14, 15, 3, 3, '80', 'Baik', 1, '2025/2026'),
(15, 19, 3, 3, '70', 'Perlu peningkatan', 1, '2025/2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `status_kunci_nilai`
--

CREATE TABLE `status_kunci_nilai` (
  `id` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `semester` int(1) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `status` enum('terbuka','terkunci') DEFAULT 'terbuka',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `status_kunci_nilai`
--

INSERT INTO `status_kunci_nilai` (`id`, `id_kelas`, `semester`, `tahun_ajaran`, `status`, `created_at`) VALUES
(1, 1, 1, '2025/2026', 'terbuka', '2025-11-19 14:10:02'),
(2, 1, 2, '2025/2026', 'terkunci', '2025-11-19 14:17:57'),
(7, 2, 1, '2025/2026', 'terbuka', '2025-11-23 18:02:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `status` enum('aktif','tidak aktif') NOT NULL DEFAULT 'tidak aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tahun_ajaran`
--

INSERT INTO `tahun_ajaran` (`id`, `tahun_ajaran`, `status`) VALUES
(3, '2024/2025', 'tidak aktif'),
(4, '2025/2026', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pengajar','santri') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin@tpq.com', '$2y$10$syvzf2wHwFWkFJ8ueFwftePkyohNJAdc//ez/iFIpEEP4k49aidGq', 'admin', '2025-11-02 05:20:12'),
(2, 'asep@tpq.com', '$2y$10$F/4/z.qwcsc.vQCCvFLYnuUIw8S6e09Im8/o8aha2pOYic3cQlVFe', 'pengajar', '2025-11-02 05:20:12'),
(13, 'ningrum@tpq.com', '$2y$10$xWzfp782Tg9thSI2uuhpdeYdvjSAky8bxlZSNpMQSpWw0rKaRCOzO', 'pengajar', '2025-11-19 14:06:44'),
(15, 'supardi@tpq.com', '$2y$10$2WTE7/jcyMPcsCTV.FhcTu7zaFXHRBEjD9uPfU0wJX/p9SZ83lxLy', 'pengajar', '2025-11-29 14:52:08'),
(16, 'oktavia@tpq.com', '$2y$10$3GgZJT5wbWj9TrD4wMtqwOVaOz86pXinXQ5XMiwn6UhHDedCRmO6C', 'pengajar', '2025-11-29 14:54:22'),
(17, 'yanto@tpq.com', '$2y$10$e6hG7WVWqb/.E8eHu29gcuXZEPWwwJhGwrMZkB0tlbWex1on7N5ny', 'santri', '2025-11-29 15:08:57'),
(18, 'yanti@tpq.com', '$2y$10$dJI/M8mH2jBygAYIu/aiUeVhzbVkDNKXNL7zL6nanSAb2WdBqGZl2', 'santri', '2025-11-29 15:09:59'),
(19, 'susilo@tpq.com', '$2y$10$YrzeT0SeRQcL0Z41tptoDuOEhpd1JQ77fkrhoz/yUAgLoz.S4Wq5S', 'santri', '2025-11-29 15:10:35'),
(20, 'sopyan@tpq.com', '$2y$10$pxlMwtFcA/R6IYi9/XNnQ.po2dYIofUa7IAUGAYXQcjRIlCwwTkH2', 'santri', '2025-11-29 15:11:07'),
(21, 'hana@tpq.com', '$2y$10$R3G9goCdzk8VN6gARinBfenKXcwVRR/BfYy.GQ1IEjWhFgSDJlVMO', 'santri', '2025-11-29 15:12:13'),
(22, 'Amelia@tpq.com', '$2y$10$n7zppFSTafcTlh9WNNGz6OiZjz4dXM2qulKH1ufUCuGjdrYuMVulS', 'santri', '2025-11-29 15:12:51'),
(23, 'fatiya@tpq.com', '$2y$10$LkmbyxFtfQ5MitXFO3jBveK4tGrQ/nERTxm6JFBfqslfTkXwqFv1S', 'santri', '2025-11-29 15:13:33'),
(24, 'ramdhan@tpq.com', '$2y$10$3jIl6U//A23/aBBnpJKewOwM1kYC5yyC3u.Vh0/bhZm1igfsn0FU6', 'santri', '2025-11-29 15:15:15'),
(27, 'tio@tpq.com', '$2y$10$9uGlPUvRmQkSk8YK2e6sm.0f3A.cF5K5MfmhpDwP3vwd3/pBK6z0O', 'santri', '2025-11-29 15:17:06'),
(28, 'rifky@tpq.com', '$2y$10$U2mGdA1aSG9WKF9KRRL34.UIoryF5u8TW/9K2.tHFJTA1vAFprBly', 'santri', '2025-11-29 15:17:30'),
(29, 'fatia@tpq.com', '$2y$10$bqL2Pm8r1s1Og8TP1BI.7ez61Hh4D5Kf4PzArXfVmOamzL720/Tfu', 'santri', '2025-11-29 15:18:57'),
(30, 'nafi@tpq.com', '$2y$10$CGFX/HgnP/3zK1lCC0JIJey6keYgVonYBA3gWevd62ft3C2Pna8C6', 'santri', '2025-11-29 15:19:33'),
(31, 'dono@tpq.com', '$2y$10$zxizAT0n1XXzQwNj7g5HPehVWPHwY1aUOh.7U.KH7vjku.CaATNhG', 'santri', '2025-11-29 15:20:09'),
(32, 'kasino@tpq.com', '$2y$10$iaazD8ZYgLdY7CmCSa5pV.EGmRVN1GzqQi.1XYMr5GZpHAKsNBzjK', 'santri', '2025-11-29 15:20:38'),
(34, 'indra@tpq.com', '$2y$10$vc/KVcFStuYLJGn.2qXl7.9WtxAKTaTOf1BoEXl48dXHfYqJ.NhbK', 'santri', '2025-11-29 15:21:31'),
(35, 'tuti@tpq.com', '$2y$10$l8S4CuV/ts659sIplt53KuOjyPkIGNdsOZU9qbh0go9DASFW2ZVsO', 'santri', '2025-11-29 15:22:15'),
(36, 'fidinilah@tpq.com', '$2y$10$RYDcH3fqPrInVq1fkPABzO2l1Fe/BsheHDjAHWH49KzH.VWTlpoy6', 'santri', '2025-11-29 15:22:43'),
(37, 'reza@tpq.com', '$2y$10$p2bHhwBR9sYNASOf/KY2zemElNSMrpuGmZ1o3w/GhoKGnKOWsYxiO', 'santri', '2025-11-29 15:23:24'),
(38, 'arlan@tpq.com', '$2y$10$b1DMHTNeImNeWLD4a.3sG.PxKBOE4od7guxNh5EX8YBeqyVfNX0hu', 'santri', '2025-11-29 15:24:23'),
(39, 'rido@tpq.com', '$2y$10$UtzDodu0taVZKguxDoI45.STyureBtk4ExksB2RjvL7UCtBzO7WSG', 'santri', '2025-11-29 15:24:56'),
(40, 'ramdhan1@tpq.com', '$2y$10$1GjhiH/5a4obcX79ROyQLuI9x6wxx4ee/e8VWe5REIW7Wd7ViiVc2', 'pengajar', '2025-12-16 07:04:48');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `data_pengajar`
--
ALTER TABLE `data_pengajar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- Indeks untuk tabel `data_santri`
--
ALTER TABLE `data_santri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indeks untuk tabel `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengajar` (`id_pengajar`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_materi` (`id_materi`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengajar` (`id_pengajar`);

--
-- Indeks untuk tabel `materi`
--
ALTER TABLE `materi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_materi` (`kode_materi`);

--
-- Indeks untuk tabel `nilai_raport`
--
ALTER TABLE `nilai_raport`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_santri` (`id_santri`),
  ADD KEY `id_materi` (`id_materi`),
  ADD KEY `id_pengajar` (`id_pengajar`);

--
-- Indeks untuk tabel `status_kunci_nilai`
--
ALTER TABLE `status_kunci_nilai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lock` (`id_kelas`,`semester`,`tahun_ajaran`);

--
-- Indeks untuk tabel `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tahun_ajaran` (`tahun_ajaran`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `data_pengajar`
--
ALTER TABLE `data_pengajar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `data_santri`
--
ALTER TABLE `data_santri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `materi`
--
ALTER TABLE `materi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `nilai_raport`
--
ALTER TABLE `nilai_raport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `status_kunci_nilai`
--
ALTER TABLE `status_kunci_nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `data_pengajar`
--
ALTER TABLE `data_pengajar`
  ADD CONSTRAINT `data_pengajar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `data_santri`
--
ALTER TABLE `data_santri`
  ADD CONSTRAINT `data_santri_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `data_santri_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `jadwal_pelajaran`
--
ALTER TABLE `jadwal_pelajaran`
  ADD CONSTRAINT `jadwal_pelajaran_ibfk_1` FOREIGN KEY (`id_pengajar`) REFERENCES `data_pengajar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_pelajaran_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_pelajaran_ibfk_3` FOREIGN KEY (`id_materi`) REFERENCES `materi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`id_pengajar`) REFERENCES `data_pengajar` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `nilai_raport`
--
ALTER TABLE `nilai_raport`
  ADD CONSTRAINT `nilai_raport_ibfk_1` FOREIGN KEY (`id_santri`) REFERENCES `data_santri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_raport_ibfk_2` FOREIGN KEY (`id_materi`) REFERENCES `materi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_raport_ibfk_3` FOREIGN KEY (`id_pengajar`) REFERENCES `data_pengajar` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `status_kunci_nilai`
--
ALTER TABLE `status_kunci_nilai`
  ADD CONSTRAINT `status_kunci_nilai_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
