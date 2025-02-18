<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

$mesaj = '';
$hata = '';

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
            $stmt = $db->prepare("INSERT INTO cariler (ad_soyad, telefon, email, adres, vergi_no, tc_kimlik, ekleyen_id) 
                                VALUES (:ad_soyad, :telefon, :email, :adres, :vergi_no, :tc_kimlik, :ekleyen_id)");
            
            $sonuc = $stmt->execute([
                'ad_soyad' => $ad_soyad,
                'telefon' => $telefon,
                'email' => $email,
                'adres' => $adres,
                'vergi_no' => $vergi_no,
                'tc_kimlik' => $tc_kimlik,
                'ekleyen_id' => $_SESSION['kullanici_id']
            ]);

            if ($sonuc) {
                $mesaj = 'Cari başarıyla eklendi!';
                // Formu temizle
                $ad_soyad = $telefon = $email = $adres = $vergi_no = $tc_kimlik = '';
            } else {
                $hata = 'Cari eklenirken bir hata oluştu!';
            }
        } catch (PDOException $e) {
            $hata = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Yeni Cari Ekle</h1>
    
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
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i>
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
                               value="<?php echo htmlspecialchars($ad_soyad ?? ''); ?>"
                               required>
                        <div class="invalid-feedback">Ad Soyad alanı zorunludur!</div>
                    </div>
                    <div class="col-md-6">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="telefon" 
                               name="telefon"
                               value="<?php echo htmlspecialchars($telefon ?? ''); ?>"
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
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz!</div>
                    </div>
                    <div class="col-md-6">
                        <label for="vergi_no" class="form-label">Vergi No</label>
                        <input type="text" 
                               class="form-control" 
                               id="vergi_no" 
                               name="vergi_no"
                               value="<?php echo htmlspecialchars($vergi_no ?? ''); ?>"
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
                               value="<?php echo htmlspecialchars($tc_kimlik ?? ''); ?>"
                               pattern="[0-9]{11}">
                        <div class="invalid-feedback">T.C. Kimlik numarası 11 haneli olmalıdır!</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea class="form-control" 
                              id="adres" 
                              name="adres" 
                              rows="3"><?php echo htmlspecialchars($adres ?? ''); ?></textarea>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                    <a href="cari_listesi.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>İptal
                    </a>
                </div>
            </form>
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