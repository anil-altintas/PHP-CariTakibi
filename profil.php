<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

$mesaj = '';
$hata = '';

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = :id");
$stmt->execute(['id' => $_SESSION['kullanici_id']]);
$kullanici = $stmt->fetch();

// İstatistikleri al
$stmt = $db->prepare("SELECT 
    COUNT(*) as toplam_islem,
    SUM(CASE WHEN islem_turu = 'borc' THEN 1 ELSE 0 END) as borc_islem,
    SUM(CASE WHEN islem_turu = 'alacak' THEN 1 ELSE 0 END) as alacak_islem
    FROM islemler 
    WHERE ekleyen_id = :kullanici_id");
$stmt->execute(['kullanici_id' => $_SESSION['kullanici_id']]);
$istatistikler = $stmt->fetch();

// Son işlemleri al
$stmt = $db->prepare("SELECT i.*, c.ad_soyad as cari_adi 
                     FROM islemler i 
                     JOIN cariler c ON i.cari_id = c.id 
                     WHERE i.ekleyen_id = :kullanici_id 
                     ORDER BY i.tarih DESC 
                     LIMIT 5");
$stmt->execute(['kullanici_id' => $_SESSION['kullanici_id']]);
$son_islemler = $stmt->fetchAll();

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_soyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mevcut_sifre = trim($_POST['mevcut_sifre'] ?? '');
    $yeni_sifre = trim($_POST['yeni_sifre'] ?? '');
    $yeni_sifre_tekrar = trim($_POST['yeni_sifre_tekrar'] ?? '');

    try {
        $db->beginTransaction();

        // Şifre değişikliği kontrolü
        if (!empty($mevcut_sifre)) {
            if (!password_verify($mevcut_sifre, $kullanici['sifre'])) {
                throw new Exception('Mevcut şifre hatalı!');
            }

            if (empty($yeni_sifre) || empty($yeni_sifre_tekrar)) {
                throw new Exception('Yeni şifre ve tekrarı gereklidir!');
            }

            if ($yeni_sifre !== $yeni_sifre_tekrar) {
                throw new Exception('Yeni şifreler eşleşmiyor!');
            }

            if (strlen($yeni_sifre) < 6) {
                throw new Exception('Şifre en az 6 karakter olmalıdır!');
            }

            $sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE kullanicilar SET sifre = :sifre WHERE id = :id");
            $stmt->execute([
                'sifre' => $sifre_hash,
                'id' => $_SESSION['kullanici_id']
            ]);
        }

        // Diğer bilgileri güncelle
        $stmt = $db->prepare("UPDATE kullanicilar SET ad_soyad = :ad_soyad, email = :email WHERE id = :id");
        $stmt->execute([
            'ad_soyad' => $ad_soyad,
            'email' => $email,
            'id' => $_SESSION['kullanici_id']
        ]);

        $db->commit();
        $mesaj = 'Profil bilgileri başarıyla güncellendi.';

        // Kullanıcı bilgilerini yeniden al
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['kullanici_id']]);
        $kullanici = $stmt->fetch();

    } catch (Exception $e) {
        $db->rollBack();
        $hata = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Profil</h1>
    
    <?php if ($mesaj): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $mesaj; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($hata): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $hata; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profil Bilgileri -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Profil Bilgileri
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="kullanici_adi" 
                                       value="<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?>" 
                                       disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="rol" class="form-label">Rol</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="rol" 
                                       value="<?php echo $kullanici['rol'] === 'admin' ? 'Yönetici' : 'Kullanıcı'; ?>" 
                                       disabled>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="ad_soyad" class="form-label">Ad Soyad</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ad_soyad" 
                                       name="ad_soyad" 
                                       value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($kullanici['email']); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <hr>
                        <h5>Şifre Değiştir</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="mevcut_sifre" class="form-label">Mevcut Şifre</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="mevcut_sifre" 
                                       name="mevcut_sifre">
                            </div>
                            <div class="col-md-4">
                                <label for="yeni_sifre" class="form-label">Yeni Şifre</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="yeni_sifre" 
                                       name="yeni_sifre">
                            </div>
                            <div class="col-md-4">
                                <label for="yeni_sifre_tekrar" class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="yeni_sifre_tekrar" 
                                       name="yeni_sifre_tekrar">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- İstatistikler -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    İstatistikler
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <h5><?php echo $istatistikler['toplam_islem']; ?></h5>
                            <small class="text-muted">Toplam İşlem</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h5 class="text-danger"><?php echo $istatistikler['borc_islem']; ?></h5>
                            <small class="text-muted">Borç İşlemi</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h5 class="text-success"><?php echo $istatistikler['alacak_islem']; ?></h5>
                            <small class="text-muted">Alacak İşlemi</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <p class="mb-1">Son Giriş</p>
                        <h6><?php echo date('d.m.Y H:i', strtotime($kullanici['son_giris'])); ?></h6>
                    </div>
                </div>
            </div>
            
            <!-- Son İşlemler -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Son İşlemler
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($son_islemler as $islem): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($islem['cari_adi']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($islem['tarih'])); ?>
                                    </small>
                                </div>
                                <div class="text-<?php echo $islem['islem_turu'] === 'borc' ? 'danger' : 'success'; ?>">
                                    <?php echo number_format($islem['tutar'], 2, ',', '.'); ?> ₺
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<?php include 'includes/footer.php'; ?> 