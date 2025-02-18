<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

$mesaj = '';
$hata = '';
$cari = null;
$islemler = [];

if (isset($_GET['id'])) {
    $cari_id = (int)$_GET['id'];
    
    try {
        // Cari bilgilerini al
        $stmt = $db->prepare("SELECT c.*, 
                                    k.ad_soyad as ekleyen_kullanici,
                                    (SELECT SUM(CASE WHEN islem_turu = 'borc' THEN tutar ELSE -tutar END) 
                                     FROM islemler 
                                     WHERE cari_id = c.id) as bakiye
                             FROM cariler c
                             LEFT JOIN kullanicilar k ON c.ekleyen_id = k.id
                             WHERE c.id = :id");
        $stmt->execute(['id' => $cari_id]);
        $cari = $stmt->fetch();

        if (!$cari) {
            die("Cari bulunamadı!");
        }

        // Cari işlemlerini al
        $stmt = $db->prepare("SELECT i.*, k.ad_soyad as ekleyen_kullanici,
                                    (SELECT GROUP_CONCAT(
                                        CONCAT(
                                            '{',
                                            '\"id\":', o.id, ',',
                                            '\"tutar\":', o.tutar, ',',
                                            '\"odeme_turu\":\"', o.odeme_turu, '\",',
                                            '\"aciklama\":\"', IFNULL(o.aciklama, ''), '\",',
                                            '\"tarih\":\"', o.tarih, '\"',
                                            '}'
                                        )
                                    )
                                    FROM odemeler o 
                                    WHERE o.islem_id = i.id) as odemeler
                             FROM islemler i
                             LEFT JOIN kullanicilar k ON i.ekleyen_id = k.id
                             WHERE i.cari_id = :cari_id
                             ORDER BY i.tarih DESC");
        $stmt->execute(['cari_id' => $cari_id]);
        $islemler = $stmt->fetchAll();

        // Ödemeleri JSON formatından PHP array'e çevir
        foreach ($islemler as &$islem) {
            if ($islem['odemeler']) {
                $odemeler_json = '[' . $islem['odemeler'] . ']';
                $islem['odemeler'] = json_decode($odemeler_json, true) ?: [];
            } else {
                $islem['odemeler'] = [];
            }
        }
        unset($islem); // Referansı kaldır

        // İstatistikleri hesapla
        $toplam_borc = 0;
        $toplam_alacak = 0;
        $odenmemis_borc = 0;
        $odenmemis_alacak = 0;

        foreach ($islemler as $islem) {
            $odenen_tutar = 0;
            foreach ($islem['odemeler'] as $odeme) {
                $odenen_tutar += $odeme['tutar'] ?? 0;
            }
            $kalan_tutar = ($islem['tutar'] ?? 0) - $odenen_tutar;

            if ($islem['islem_turu'] === 'borc') {
                $toplam_borc += $islem['tutar'] ?? 0;
                $odenmemis_borc += $kalan_tutar;
            } else {
                $toplam_alacak += $islem['tutar'] ?? 0;
                $odenmemis_alacak += $kalan_tutar;
            }
        }

        $istatistikler = [
            'toplam_borc' => $toplam_borc,
            'toplam_alacak' => $toplam_alacak,
            'odenmemis_borc' => $odenmemis_borc,
            'odenmemis_alacak' => $odenmemis_alacak
        ];

        // Son ödemeleri al
        $stmt = $db->prepare("SELECT o.*, i.islem_turu, k.ad_soyad as ekleyen_kullanici
                             FROM odemeler o
                             JOIN islemler i ON o.islem_id = i.id
                             LEFT JOIN kullanicilar k ON o.ekleyen_id = k.id
                             WHERE i.cari_id = :cari_id
                             ORDER BY o.tarih DESC
                             LIMIT 5");
        $stmt->execute(['cari_id' => $cari_id]);
        $son_odemeler = $stmt->fetchAll();

        // Vade durumunu kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) as geciken_islem
                             FROM islemler
                             WHERE cari_id = :cari_id
                             AND odeme_durumu = 'odenmedi'
                             AND vade_tarihi < CURDATE()");
        $stmt->execute(['cari_id' => $cari_id]);
        $geciken_islem = $stmt->fetch()['geciken_islem'];

    } catch (PDOException $e) {
        $hata = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Yeni işlem ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['islem_ekle'])) {
    $islem_turu = $_POST['islem_turu'];
    $tutar = (float)$_POST['tutar'];
    $aciklama = trim($_POST['aciklama']);
    $vade_tarihi = !empty($_POST['vade_tarihi']) ? $_POST['vade_tarihi'] : null;
    $cari_id = (int)$_POST['cari_id'];

    if ($tutar <= 0) {
        $hata = 'Tutar sıfırdan büyük olmalıdır!';
    } elseif ($cari_id <= 0) {
        $hata = 'Geçersiz cari hesap!';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO islemler (cari_id, islem_turu, tutar, aciklama, vade_tarihi, ekleyen_id) 
                                VALUES (:cari_id, :islem_turu, :tutar, :aciklama, :vade_tarihi, :ekleyen_id)");
            
            $sonuc = $stmt->execute([
                'cari_id' => $cari_id,
                'islem_turu' => $islem_turu,
                'tutar' => $tutar,
                'aciklama' => $aciklama,
                'vade_tarihi' => $vade_tarihi,
                'ekleyen_id' => $_SESSION['kullanici_id']
            ]);

            if ($sonuc) {
                $mesaj = 'İşlem başarıyla eklendi!';
                header("Location: cari_detay.php?id=$cari_id&mesaj=islem_eklendi");
                exit();
            } else {
                $hata = 'İşlem eklenirken bir hata oluştu!';
            }
        } catch (PDOException $e) {
            $hata = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4">Cari Detay</h1>
        <div>
            <a href="cari_duzenle.php?id=<?php echo $cari_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Düzenle
            </a>
            <a href="cari_listesi.php" class="btn btn-secondary">
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
    <div class="row">
        <!-- Cari Bilgileri -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    Cari Bilgileri
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Ad Soyad:</th>
                            <td><?php echo htmlspecialchars($cari['ad_soyad']); ?></td>
                        </tr>
                        <tr>
                            <th>Telefon:</th>
                            <td>
                                <?php if ($cari['telefon']): ?>
                                <a href="tel:<?php echo $cari['telefon']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($cari['telefon']); ?>
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>E-posta:</th>
                            <td>
                                <?php if ($cari['email']): ?>
                                <a href="mailto:<?php echo $cari['email']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($cari['email']); ?>
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Vergi No:</th>
                            <td><?php echo htmlspecialchars($cari['vergi_no']) ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>T.C. Kimlik No:</th>
                            <td><?php echo htmlspecialchars($cari['tc_kimlik']) ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Adres:</th>
                            <td><?php echo nl2br(htmlspecialchars($cari['adres'])) ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Ekleyen:</th>
                            <td><?php echo htmlspecialchars($cari['ekleyen_kullanici']); ?></td>
                        </tr>
                        <tr>
                            <th>Kayıt Tarihi:</th>
                            <td><?php echo date('d.m.Y H:i', strtotime($cari['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="col-xl-8">
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Toplam İşlem
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo count($islemler ?? []); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calculator fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Toplam Alacak
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($toplam_alacak, 2, ',', '.'); ?> ₺
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card border-left-danger h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Toplam Borç
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($toplam_borc, 2, ',', '.'); ?> ₺
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card border-left-info h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Bakiye
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold <?php echo ($cari['bakiye'] ?? 0) < 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo number_format($cari['bakiye'] ?? 0, 2, ',', '.'); ?> ₺
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ödeme Durumu -->
            <div class="row">
                <div class="col-xl-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clock me-1"></i>
                            Ödeme Durumu
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <h6 class="text-danger">Ödenmemiş Borç</h6>
                                    <h4><?php echo number_format($odenmemis_borc, 2, ',', '.'); ?> ₺</h4>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-success">Ödenmemiş Alacak</h6>
                                    <h4><?php echo number_format($odenmemis_alacak, 2, ',', '.'); ?> ₺</h4>
                                </div>
                            </div>
                            <?php if ($geciken_islem > 0): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $geciken_islem; ?> adet vadesi geçmiş işlem bulunmaktadır.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Son Ödemeler -->
                <div class="col-xl-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-money-bill me-1"></i>
                            Son Ödemeler
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($son_odemeler as $odeme): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-muted">
                                                <?php echo date('d.m.Y H:i', strtotime($odeme['tarih'])); ?>
                                            </div>
                                            <div><?php echo $odeme['odeme_turu']; ?></div>
                                        </div>
                                        <div class="text-<?php echo $odeme['islem_turu'] === 'borc' ? 'danger' : 'success'; ?>">
                                            <?php echo number_format($odeme['tutar'] ?? 0, 2, ',', '.'); ?> ₺
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($son_odemeler)): ?>
                                <div class="list-group-item text-center text-muted">
                                    Henüz ödeme kaydı bulunmuyor.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- İşlem Listesi -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-list me-1"></i>
                    İşlem Geçmişi
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#islemEkleModal">
                    <i class="fas fa-plus me-2"></i>Yeni İşlem
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="islemlerTable">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>İşlem Türü</th>
                            <th>Tutar</th>
                            <th>Vade Tarihi</th>
                            <th>İşlem Öncesi Bakiye</th>
                            <th>İşlem Sonrası Bakiye</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($islemler as $islem): 
                            $islem_tutari = $islem['islem_turu'] === 'borc' ? $islem['tutar'] : -$islem['tutar'];
                            $islem_oncesi_bakiye = $islem['islem_oncesi_bakiye'] ?? 0;
                            $islem_sonrasi_bakiye = $islem['islem_sonrasi_bakiye'] ?? ($islem_oncesi_bakiye + $islem_tutari);
                        ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i', strtotime($islem['tarih'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $islem['islem_turu'] === 'borc' ? 'danger' : 'success'; ?>">
                                    <?php echo $islem['islem_turu'] === 'borc' ? 'Borç' : 'Alacak'; ?>
                                </span>
                            </td>
                            <td class="text-<?php echo $islem['islem_turu'] === 'borc' ? 'danger' : 'success'; ?>">
                                <?php echo number_format($islem['tutar'], 2, ',', '.'); ?> ₺
                            </td>
                            <td>
                                <?php echo $islem['vade_tarihi'] ? date('d.m.Y', strtotime($islem['vade_tarihi'])) : '-'; ?>
                            </td>
                            <td class="text-muted">
                                <?php echo number_format($islem_oncesi_bakiye, 2, ',', '.'); ?> ₺
                            </td>
                            <td class="text-muted">
                                <?php echo number_format($islem_sonrasi_bakiye, 2, ',', '.'); ?> ₺
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $islem['odeme_durumu'] === 'odendi' ? 'success' : 'warning'; ?>">
                                    <?php echo $islem['odeme_durumu'] === 'odendi' ? 'Ödendi' : 'Bekliyor'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($islem_sonrasi_bakiye > $islem_oncesi_bakiye): ?>
                                <button type="button" 
                                        class="btn btn-success btn-sm"
                                        onclick="odemeAl(<?php echo $islem['id']; ?>)">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                                <?php endif; ?>
                                <button type="button" 
                                        class="btn btn-info btn-sm"
                                        onclick="odemeGecmisiniGoster(<?php echo $islem['id']; ?>)">
                                    <i class="fas fa-history"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-danger btn-sm"
                                        onclick="islemSil(<?php echo $islem['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- İşlem Ekleme Modal -->
<div class="modal fade" id="islemEkleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni İşlem Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="islemForm" method="POST">
                    <input type="hidden" name="islem_ekle" value="1">
                    <input type="hidden" name="cari_id" value="<?php echo $cari_id; ?>">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Mevcut Bakiye:</span>
                            <strong class="<?php echo ($cari['bakiye'] ?? 0) < 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo number_format($cari['bakiye'] ?? 0, 2, ',', '.'); ?> ₺
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span>İşlem Sonrası Bakiye:</span>
                            <strong id="yeniBakiye">0,00 ₺</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="islem_turu" class="form-label">İşlem Türü</label>
                        <select class="form-select" id="islem_turu" name="islem_turu" required>
                            <option value="borc">Borç</option>
                            <option value="alacak">Alacak</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tutar" class="form-label">Tutar</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="tutar" 
                                   name="tutar" 
                                   step="0.01" 
                                   min="0.01" 
                                   required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="vade_tarihi" class="form-label">Vade Tarihi (İsteğe Bağlı)</label>
                        <input type="date" 
                               class="form-control" 
                               id="vade_tarihi" 
                               name="vade_tarihi">
                    </div>
                    <div class="mb-3">
                        <label for="aciklama" class="form-label">Açıklama</label>
                        <textarea class="form-control" 
                                 id="aciklama" 
                                 name="aciklama" 
                                 rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" form="islemForm" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Ödeme Geçmişi Modal -->
<div class="modal fade" id="odemeGecmisiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Geçmişi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="odemeGecmisiTable">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Ödeme Türü</th>
                                <th>Açıklama</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="odemeGecmisiBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Yükleniyor...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Ödeme Modal -->
<div class="modal fade" id="odemeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme Al</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="odemeForm">
                    <input type="hidden" id="islem_id" name="islem_id">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Kalan Tutar:</span>
                            <strong id="kalanTutar">0,00 ₺</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span>Ödeme Sonrası Kalan:</span>
                            <strong id="odemeSonrasiKalan">0,00 ₺</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odeme_tarihi" class="form-label">Ödeme Tarihi</label>
                        <input type="date" 
                               class="form-control" 
                               id="odeme_tarihi" 
                               name="odeme_tarihi" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="odeme_tutar" class="form-label">Tutar</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="odeme_tutar" 
                                   name="tutar" 
                                   step="0.01" 
                                   min="0.01" 
                                   required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odeme_turu" class="form-label">Ödeme Türü</label>
                        <select class="form-select" id="odeme_turu" name="odeme_turu" required>
                            <option value="">Seçiniz</option>
                            <option value="nakit">Nakit</option>
                            <option value="havale">Havale/EFT</option>
                            <option value="kredi_karti">Kredi Kartı</option>
                            <option value="cek">Çek</option>
                            <option value="senet">Senet</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odeme_aciklama" class="form-label">Açıklama</label>
                        <textarea class="form-control" 
                                 id="odeme_aciklama" 
                                 name="aciklama" 
                                 rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" form="odemeForm" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>Ödemeyi Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Form doğrulama
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Para formatı
function formatPara(tutar) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(tutar);
}

// Tarih formatı
function formatTarih(tarih) {
    return new Date(tarih).toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// İşlem silme
async function islemSil(islem_id) {
    const result = await Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu işlem geri alınamaz!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('islem_sil.php?id=' + islem_id);
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    title: 'Başarılı!',
                    text: data.message,
                    icon: 'success'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire({
                title: 'Hata!',
                text: error.message || 'Bir hata oluştu.',
                icon: 'error'
            });
        }
    }
}

// Mevcut bakiye değişkeni
const mevcutBakiye = <?php echo $cari['bakiye'] ?? 0; ?>;

// İşlem tutarı değiştiğinde bakiyeyi güncelle
document.getElementById('tutar')?.addEventListener('input', function(e) {
    const tutar = parseFloat(e.target.value) || 0;
    const islemTuru = document.getElementById('islem_turu').value;
    let yeniBakiye = mevcutBakiye;
    
    if (islemTuru === 'borc') {
        yeniBakiye += tutar;
    } else {
        yeniBakiye -= tutar;
    }
    
    document.getElementById('yeniBakiye').textContent = formatPara(yeniBakiye);
    document.getElementById('yeniBakiye').className = yeniBakiye < 0 ? 'text-danger' : 'text-success';
});

// İşlem türü değiştiğinde bakiyeyi güncelle
document.getElementById('islem_turu')?.addEventListener('change', function(e) {
    const tutar = parseFloat(document.getElementById('tutar').value) || 0;
    const islemTuru = e.target.value;
    let yeniBakiye = mevcutBakiye;
    
    if (islemTuru === 'borc') {
        yeniBakiye += tutar;
    } else {
        yeniBakiye -= tutar;
    }
    
    document.getElementById('yeniBakiye').textContent = formatPara(yeniBakiye);
    document.getElementById('yeniBakiye').className = yeniBakiye < 0 ? 'text-danger' : 'text-success';
});

// Ödeme tutarı değiştiğinde kalan tutarı güncelle
document.getElementById('odeme_tutar')?.addEventListener('input', function(e) {
    const odemeTutari = parseFloat(e.target.value) || 0;
    const kalanTutar = parseFloat(document.getElementById('kalanTutar').getAttribute('data-tutar')) || 0;
    const odemeSonrasi = kalanTutar - odemeTutari;
    
    document.getElementById('odemeSonrasiKalan').textContent = formatPara(odemeSonrasi);
    document.getElementById('odemeSonrasiKalan').className = odemeSonrasi > 0 ? 'text-danger' : 'text-success';
});

// Ödeme modalı açıldığında kalan tutarı güncelle
function odemeAl(islem_id) {
    const modal = new bootstrap.Modal(document.getElementById('odemeModal'));
    document.getElementById('islem_id').value = islem_id;
    
    // İlgili işlemin satırından bilgileri al
    const islemSatiri = document.querySelector(`tr[data-islem-id="${islem_id}"]`);
    const kalanTutar = parseFloat(islemSatiri.querySelector('.kalan-tutar').getAttribute('data-tutar'));
    
    // Kalan tutarı göster
    document.getElementById('kalanTutar').textContent = formatPara(kalanTutar);
    document.getElementById('kalanTutar').setAttribute('data-tutar', kalanTutar);
    document.getElementById('odeme_tutar').value = kalanTutar;
    document.getElementById('odeme_tutar').max = kalanTutar;
    
    // Ödeme sonrası kalan tutarı sıfırla
    document.getElementById('odemeSonrasiKalan').textContent = '0,00 ₺';
    document.getElementById('odemeSonrasiKalan').className = 'text-success';
    
    modal.show();
}

// Ödeme kaydetme
async function odemeyiKaydet(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch('odeme_kaydet.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('odemeModal'));
            modal.hide();
            form.reset();
            
            Swal.fire({
                title: 'Başarılı!',
                text: data.message,
                icon: 'success'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        Swal.fire({
            title: 'Hata!',
            text: error.message || 'Bir hata oluştu.',
            icon: 'error'
        });
    }
}

// Ödeme geçmişi görüntüleme
async function odemeGecmisiniGoster(islem_id) {
    try {
        const response = await fetch('odeme_gecmisi.php?islem_id=' + islem_id);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }

        const odemeler = data.odemeler;
        let html = '';
        
        if (odemeler.length > 0) {
            odemeler.forEach(odeme => {
                html += `
                <tr>
                    <td>${odeme.tarih}</td>
                    <td class="text-end">${formatPara(odeme.tutar)}</td>
                    <td>${odeme.odeme_turu}</td>
                    <td>${odeme.aciklama || '-'}</td>
                    <td>
                        <button type="button" 
                                class="btn btn-danger btn-sm"
                                onclick="odemeSil(${odeme.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    Henüz ödeme kaydı bulunmuyor.
                </td>
            </tr>`;
        }
        
        document.getElementById('odemeGecmisiBody').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('odemeGecmisiModal'));
        modal.show();
    } catch (error) {
        Swal.fire({
            title: 'Hata!',
            text: error.message || 'Ödeme geçmişi yüklenirken bir hata oluştu.',
            icon: 'error'
        });
    }
}

// Ödeme silme
async function odemeSil(odeme_id) {
    const result = await Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu ödeme kaydı geri alınamaz!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('odeme_sil.php?id=' + odeme_id);
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    title: 'Başarılı!',
                    text: data.message,
                    icon: 'success'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire({
                title: 'Hata!',
                text: error.message || 'Bir hata oluştu.',
                icon: 'error'
            });
        }
    }
}

// Form olayları
document.getElementById('odemeForm')?.addEventListener('submit', odemeyiKaydet);
document.getElementById('islemForm')?.addEventListener('submit', function(event) {
    event.preventDefault();
    const form = event.target;
    form.submit();
});
</script>

<?php include 'includes/footer.php'; ?> 