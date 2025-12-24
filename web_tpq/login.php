<?php
// Mulai session untuk cek apakah user sudah login
session_start();

// Jika sudah login, langsung arahkan ke dashboard sesuai role
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: dashboard_admin.php"); break;
        case 'pengajar': header("Location: dashboard_pengajar.php"); break;
        case 'santri': header("Location: dashboard_santri.php"); break;
    }
    exit();
}

// Ambil pesan error dari URL (jika ada)
$error_msg = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'wrongpass') {
        $error_msg = 'Password salah, silakan coba lagi.';
    } elseif ($_GET['error'] == 'notfound') {
        $error_msg = 'Email tidak terdaftar.';
    } elseif ($_GET['error'] == 'unknownrole') {
        $error_msg = 'Role pengguna tidak dikenal.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Raport TPQ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Gaya CSS yang Diperbarui */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        :root {
            --primary-color: #00a86b; /* Hijau Utama */
            --secondary-color: #f7f7f7; /* Latar Belakang Terang */
            --text-color: #333;
            --white-color: #ffffff;
            --error-color: #cc0000;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: url('img/bg.jpg') no-repeat center center fixed;
            background-size: cover;  /* agar memenuhi layar */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .mobile-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            background-color: var(--white-color);
            border-radius: 25px; /* Tambahkan border-radius */
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* Box Shadow lebih tebal */
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: auto; /* Agar tidak memaksakan 100vh jika konten sedikit */
            transition: all 0.3s ease;
        }
        
        /* Untuk tampilan desktop yang lebih baik */
        @media (min-width: 768px) {
            .mobile-container {
                min-height: 550px; 
            }
        }

        .header-content {
            text-align: center;
            padding: 40px 30px 20px 30px;
            background-color: var(--primary-color); /* Header menggunakan warna utama */
            color: var(--white-color);
            position: relative;
        }
        
        /* Efek Wave (Gelombang) */
        .header-content:before {
            content: "";
            position: absolute;
            bottom: -50px; /* Taruh di bawah header */
            left: 0;
            width: 100%;
            height: 50px;
            background-color: var(--primary-color);
            border-radius: 0 0 50% 50% / 0 0 100% 100%; /* Membuat efek cekung di bawah */
            transform: scaleX(1.5); /* Meregangkan wave agar lebih elegan */
        }
        
        .header-content h1 {
            font-size: 2em;
            font-weight: 700;
            margin: 0;
        }
        .header-content p {
            font-size: 1em;
            font-weight: 400;
            margin: 5px 0 0 0;
            line-height: 1.3;
        }
        
        .logo-wrapper {
            background-color: var(--white-color);
            width: 120px;
            height: 120px;
            margin: 25px auto 10px auto;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10; /* Pastikan di atas wave */
        }
        .header-content .logo {
            width: 80px; /* Ukuran logo lebih kecil di dalam wrapper */
            height: 80px;
            object-fit: contain;
        }

        .login-container {
            background-color: var(--white-color); /* Ubah ke warna putih agar kontras dengan header */
            color: var(--text-color);
            padding: 60px 30px 40px 30px; /* Padding lebih besar di atas untuk transisi wave */
            text-align: center;
            flex-grow: 1;
        }
        .login-container h2 {
            font-size: 1.8em;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 30px;
            color: var(--primary-color);
        }
        .input-group {
             position: relative;
             margin-bottom: 25px;
        }
        .login-form input,
        .login-form button {
            width: 100%;
            padding: 15px;
            border-radius: 10px; /* Border radius lebih modern */
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box; 
        }
        .login-form input {
            background-color: var(--secondary-color);
            border: 1px solid #ddd;
            color: var(--text-color);
            padding-left: 45px; /* Ruang untuk ikon */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .login-form input:focus {
             border-color: var(--primary-color);
             box-shadow: 0 0 5px rgba(0, 168, 107, 0.5);
             outline: none;
        }
        .login-form .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.1em;
        }
        .login-form input::placeholder {
            color: #aaa;
            opacity: 1;
        }
        .login-form button {
            background-color: var(--primary-color);
            color: var(--white-color);
            border: none;
            font-weight: 600;
            margin-top: 10px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 168, 107, 0.4);
            transition: background-color 0.3s ease, transform 0.1s ease;
        }
        .login-form button:hover {
            background-color: #008f5a; /* Warna sedikit lebih gelap saat hover */
            transform: translateY(-2px);
        }
        .forgot-password {
            display: block;
            text-align: right;
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.9em;
            margin-top: -10px;
            margin-bottom: 25px;
            transition: color 0.3s ease;
        }
        .forgot-password:hover {
             color: var(--primary-color);
        }
        /* Ini untuk pesan error */
        .error-message {
            background-color: #ffe6e6;
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.9em;
            text-align: center;
            border: 1px solid var(--error-color);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        
        <header class="header-content">
            <h1>Sistem Raport TPQ</h1>
            <p>Taman Pendidikan Al-Qur'an Darul Hikmah</p>
            
            <div class="logo-wrapper">
                 <img src="img/logo1.png" alt="Logo TPQ Darul Hikmah" class="logo">
            </div>
        </header>

        <main class="login-container">
            <h2>Masuk</h2>
            
            <form action="proses_login.php" method="POST" class="login-form">
                
                <?php if (!empty($error_msg)): ?>
                    <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Masukan email" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Kata sandi" required>
                </div>
                
                <a href="#" class="forgot-password">Lupa sandi?</a>
                
                <button type="submit">Masuk</button>
            </form>
        </main>

    </div>
</body>
</html>