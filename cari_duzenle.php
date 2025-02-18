<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

$mesaj = '';
$hata = '';
$cari = null;

if (isset($_GET['id'])) {
    $cari_id = (int)$_GET['id'];
    
    try {
        // Cari bilgilerini al
        $stmt = $db->prepare("SELECT * FROM cariler WHERE id = :id");
        $stmt->execute(['id' => $cari_id]);
        $cari = $stmt->fetch();

        if (!$cari) {
            die("Cari bulunamadı!");
        }

        // POST işlemi kontrolü
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ad_soyad = trim($_POST['ad_soyad'] ?? '');
            $telefon = trim($_POST['telefon'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $adres = trim($_POST['adres'] ?? '');
            $vergi_no = trim($_POST['vergi_no'] ?? '');
            $tc_kimlik = trim($_POST['tc_kimlik'] ?? '');

            if (empty($ad_soyad)) {
                $hata = 'Ad Soyad alanı zorunludur!';
            } else {
                try {
                    $stmt = $db->prepare("UPDATE cariler 
                                        SET ad_soyad = :ad_soyad,
                                            telefon = :telefon,
                                            email = :email,
                                            adres = :adres,
                                            vergi_no = :vergi_no,
                                            tc_kimlik = :tc_kimlik
                                        WHERE id = :id");
                    
                    $sonuc = $stmt->execute([
                        'ad_soyad' => $ad_soyad,
                        'telefon' => $telefon,
                        'email' => $email,
                        'adres' => $adres,
                        'vergi_no' => $vergi_no,
                        'tc_kimlik' => $tc_kimlik,
                        'id' => $cari_id
                    ]);

                    if ($sonuc) {
                        $mesaj = 'Cari bilgileri başarıyla güncellendi!';
                        // Güncel bilgileri al
                        $stmt = $db->prepare("SELECT * FROM cariler WHERE id = :id");
                        $stmt->execute(['id' => $cari_id]);
                        $cari = $stmt->fetch();
                    } else {
                        $hata = 'Cari güncellenirken bir hata oluştu!';
                    }
                } catch (PDOException $e) {
                    $hata = 'Veritabanı hatası: ' . $e->getMessage();
                }
            }
        }
    } catch (PDOException $e) {
        $hata = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4">Cari Düzenle</h1>
        <div>
            <a href="cari_detay.php?id=<?php echo $cari_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </div>
    </div>
    
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
    
    <?php if ($cari): ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            Cari Bilgileri
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="ad_soyad" class="form-label">Ad Soyad *</label>
                        <input type="text" 
                               class="form-control" 
                               id="ad_soyad" 
                               name="ad_soyad" 
                               value="<?php echo htmlspecialchars($cari['ad_soyad']); ?>"
                               required>
                        <div class="invalid-feedback">Ad Soyad alanı zorunludur!</div>
                    </div>
                    <div class="col-md-6">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="telefon" 
                               name="telefon"
                               value="<?php echo htmlspecialchars($cari['telefon']); ?>"
                               pattern="[0-9]{10,11}">
                        <div class="invalid-feedback">Geçerli bir telefon numarası giriniz!</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?php echo htmlspecialchars($cari['email']); ?>">
                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz!</div>
                    </div>
                    <div class="col-md-6">
                        <label for="vergi_no" class="form-label">Vergi No</label>
                        <input type="text" 
                               class="form-control" 
                               id="vergi_no" 
                               name="vergi_no"
                               value="<?php echo htmlspecialchars($cari['vergi_no']); ?>"
                               pattern="[0-9]{10}">
                        <div class="invalid-feedback">Vergi numarası 10 haneli olmalıdır!</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="tc_kimlik" class="form-label">T.C. Kimlik No</label>
                        <input type="text" 
                               class="form-control" 
                               id="tc_kimlik" 
                               name="tc_kimlik"
                               value="<?php echo htmlspecialchars($cari['tc_kimlik']); ?>"
                               pattern="[0-9]{11}">
                        <div class="invalid-feedback">T.C. Kimlik numarası 11 haneli olmalıdır!</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea class="form-control" 
                              id="adres" 
                              name="adres" 
                              rows="3"><?php echo htmlspecialchars($cari['adres']); ?></textarea>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                    </button>
                    <a href="cari_detay.php?id=<?php echo $cari_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
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

// Telefon numarası formatı
document.getElementById('telefon').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) {
        value = value.slice(0, 11);
    }
    e.target.value = value;
});

// TC Kimlik numarası formatı
document.getElementById('tc_kimlik').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) {
        value = value.slice(0, 11);
    }
    e.target.value = value;
});

// Vergi numarası formatı
document.getElementById('vergi_no').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    e.target.value = value;
});
</script>

<?php include 'includes/footer.php'; ?> 