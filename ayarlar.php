<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

// Sadece admin kullanıcıları erişebilir
adminKontrol();

$mesaj = '';
$hata = '';

// Ayar güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['ayarlar'] as $id => $deger) {
            $stmt = $db->prepare("UPDATE ayarlar SET deger = :deger WHERE id = :id");
            $stmt->execute([
                'deger' => $deger,
                'id' => $id
            ]);
        }
        $mesaj = 'Ayarlar başarıyla güncellendi.';
    } catch (PDOException $e) {
        $hata = 'Ayarlar güncellenirken bir hata oluştu: ' . $e->getMessage();
    }
}

// Mevcut ayarları getir
$stmt = $db->query("SELECT * FROM ayarlar ORDER BY id");
$ayarlar = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sistem Ayarları</h1>
    
    <?php if ($mesaj): ?>
        <div class="alert alert-success"><?php echo $mesaj; ?></div>
    <?php endif; ?>
    
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cog me-1"></i>
            Genel Ayarlar
        </div>
        <div class="card-body">
            <form method="POST">
                <?php foreach ($ayarlar as $ayar): ?>
                <div class="mb-3">
                    <label for="ayar_<?php echo $ayar['id']; ?>" class="form-label">
                        <?php echo htmlspecialchars($ayar['aciklama']); ?>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="ayar_<?php echo $ayar['id']; ?>" 
                           name="ayarlar[<?php echo $ayar['id']; ?>]" 
                           value="<?php echo htmlspecialchars($ayar['deger']); ?>">
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Kullanıcı Yönetimi
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Kullanıcı Adı</th>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Son Giriş</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query("SELECT * FROM kullanicilar ORDER BY id");
                        while ($kullanici = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></td>
                            <td><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></td>
                            <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                            <td><?php echo $kullanici['rol']; ?></td>
                            <td>
                                <?php 
                                echo $kullanici['son_giris'] 
                                    ? date('d.m.Y H:i', strtotime($kullanici['son_giris'])) 
                                    : '-';
                                ?>
                            </td>
                            <td>
                                <a href="kullanici_duzenle.php?id=<?php echo $kullanici['id']; ?>" 
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($kullanici['id'] != $_SESSION['kullanici_id']): ?>
                                <button onclick="kullaniciSil(<?php echo $kullanici['id']; ?>)" 
                                        class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <a href="kullanici_ekle.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Yeni Kullanıcı Ekle
            </a>
        </div>
    </div>
</div>

<script>
function kullaniciSil(id) {
    if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
        window.location.href = 'kullanici_sil.php?id=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?> 