<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

$mesaj = '';
$hata = '';

// Tüm bildirimleri okundu olarak işaretle
if (isset($_GET['tumu_okundu'])) {
    try {
        $stmt = $db->prepare("UPDATE bildirimler SET durum = 'okundu' WHERE kullanici_id = :kullanici_id");
        $stmt->execute(['kullanici_id' => $_SESSION['kullanici_id']]);
        $mesaj = 'Tüm bildirimler okundu olarak işaretlendi.';
    } catch (PDOException $e) {
        $hata = 'Bildirimler güncellenirken bir hata oluştu: ' . $e->getMessage();
    }
}

// Bildirimleri getir
try {
    $stmt = $db->prepare("SELECT b.*, c.ad_soyad as cari_adi, 
                                DATE_FORMAT(b.olusturma_tarihi, '%d.%m.%Y %H:%i') as tarih_format
                         FROM bildirimler b 
                         LEFT JOIN cariler c ON b.cari_id = c.id 
                         WHERE b.kullanici_id = :kullanici_id 
                         ORDER BY b.olusturma_tarihi DESC");
    $stmt->execute(['kullanici_id' => $_SESSION['kullanici_id']]);
    $bildirimler = $stmt->fetchAll();
} catch (PDOException $e) {
    $hata = 'Bildirimler alınırken bir hata oluştu: ' . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4">Bildirimler</h1>
        <?php if (!empty($bildirimler)): ?>
        <a href="?tumu_okundu=1" class="btn btn-primary">
            <i class="fas fa-check-double me-2"></i>Tümünü Okundu İşaretle
        </a>
        <?php endif; ?>
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

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bell me-1"></i>
            Bildirim Listesi
        </div>
        <div class="card-body">
            <?php if (!empty($bildirimler)): ?>
                <div class="list-group">
                    <?php foreach ($bildirimler as $bildirim): ?>
                        <a href="cari_detay.php?id=<?php echo $bildirim['cari_id']; ?>" 
                           class="list-group-item list-group-item-action mb-2 <?php echo $bildirim['durum'] === 'okunmadi' ? 'active' : ''; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($bildirim['cari_adi']); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($bildirim['mesaj']); ?></p>
                                </div>
                                <small class="text-nowrap ms-3">
                                    <?php echo date('d.m.Y H:i', strtotime($bildirim['olusturma_tarihi'])); ?>
                                    <?php if ($bildirim['durum'] === 'okunmadi'): ?>
                                        <span class="badge bg-danger ms-2">Yeni</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                    <p class="mb-0">Henüz bildiriminiz bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 