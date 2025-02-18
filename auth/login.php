<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['kullanici_id'])) {
    header('Location: ../index.php');
    exit();
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = trim($_POST['sifre'] ?? '');

    if (!empty($kullanici_adi) && !empty($sifre)) {
        try {
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = :kullanici_adi");
            $stmt->execute(['kullanici_adi' => $kullanici_adi]);
            $kullanici = $stmt->fetch();

            if ($kullanici) {
                if (password_verify($sifre, $kullanici['sifre'])) {
                    $_SESSION['kullanici_id'] = $kullanici['id'];
                    $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
                    $_SESSION['rol'] = $kullanici['rol'];

                    // Son giriş tarihini güncelle
                    $stmt = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = :id");
                    $stmt->execute(['id' => $kullanici['id']]);

                    header('Location: ../index.php');
                    exit();
                } else {
                    $hata = 'Şifre hatalı!';
                }
            } else {
                $hata = 'Kullanıcı bulunamadı!';
            }
        } catch (PDOException $e) {
            $hata = 'Veritabanı hatası: ' . $e->getMessage();
        }
    } else {
        $hata = 'Kullanıcı adı ve şifre gereklidir!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Cari Takip Sistemi</title>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-title {
            color: #333;
            font-weight: 600;
        }
        .btn-primary {
            padding: 10px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4">Cari Takip Sistemi</h3>
                <?php if ($hata): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $hata; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="kullanici_adi" class="form-label">
                            <i class="fas fa-user me-2"></i>Kullanıcı Adı
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="kullanici_adi" 
                               name="kullanici_adi" 
                               value="<?php echo htmlspecialchars($kullanici_adi ?? ''); ?>"
                               required>
                        <div class="invalid-feedback">Kullanıcı adı gereklidir.</div>
                    </div>
                    <div class="mb-4">
                        <label for="sifre" class="form-label">
                            <i class="fas fa-lock me-2"></i>Şifre
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="sifre" 
                               name="sifre" 
                               required>
                        <div class="invalid-feedback">Şifre gereklidir.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="text-center mt-3 text-muted">
            <small>Varsayılan giriş bilgileri:</small><br>
            <small>Kullanıcı adı: admin</small><br>
            <small>Şifre: admin123</small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form doğrulama
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 