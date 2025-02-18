<?php
require_once 'config/db.php';
require_once 'models/Cari.php';
require_once 'auth/auth_check.php';

$cari = new Cari($db);

// İstatistikleri al
$toplamBorc = $cari->getToplamBorc();
$toplamAlacak = $cari->getToplamAlacak();
$toplamCariSayisi = $cari->getToplamCariSayisi();
$sonIslemler = $cari->getSonIslemler(5);

// Okunmamış bildirim sayısını al
$bildirimSayisi = getOkunmamisBildirimSayisi($db);

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4">Gösterge Paneli</h1>
        <?php if ($bildirimSayisi > 0): ?>
        <a href="bildirimler.php" class="btn btn-warning">
            <i class="fas fa-bell"></i> Bildirimler
            <span class="badge bg-danger"><?php echo $bildirimSayisi; ?></span>
        </a>
        <?php endif; ?>
    </div>
    
    <!-- İstatistik Kartları -->
    <div class="row mt-4">
        <div class="col-xl-4 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4>Toplam Borç</h4>
                    <h2><?php echo number_format($toplamBorc, 2, ',', '.'); ?> ₺</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4>Toplam Alacak</h4>
                    <h2><?php echo number_format($toplamAlacak, 2, ',', '.'); ?> ₺</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4>Toplam Cari Sayısı</h4>
                    <h2><?php echo $toplamCariSayisi; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Borç/Alacak Dağılımı
                </div>
                <div class="card-body">
                    <canvas id="borcAlacakPie" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Son 6 Ay İşlem Hacmi
                </div>
                <div class="card-body">
                    <canvas id="islemHacmiBar" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Son İşlemler Tablosu -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Son İşlemler
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Cari Adı</th>
                        <th>İşlem Türü</th>
                        <th>Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sonIslemler as $islem): ?>
                    <tr>
                        <td><?php echo date('d.m.Y', strtotime($islem['tarih'])); ?></td>
                        <td><?php echo $islem['cari_adi']; ?></td>
                        <td><?php echo $islem['islem_turu']; ?></td>
                        <td><?php echo number_format($islem['tutar'], 2, ',', '.'); ?> ₺</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Borç/Alacak Pasta Grafiği
var ctx = document.getElementById('borcAlacakPie').getContext('2d');
var borcAlacakPie = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Borç', 'Alacak'],
        datasets: [{
            data: [<?php echo $toplamBorc; ?>, <?php echo $toplamAlacak; ?>],
            backgroundColor: ['#dc3545', '#28a745']
        }]
    }
});

// Son 6 Ay İşlem Hacmi Bar Grafiği
<?php
$aylar = array();
$islemHacmi = array();

for ($i = 5; $i >= 0; $i--) {
    $ay = date('Y-m', strtotime("-$i months"));
    $aylar[] = date('F Y', strtotime("-$i months"));
    
    $query = "SELECT SUM(tutar) as toplam FROM islemler WHERE DATE_FORMAT(tarih, '%Y-%m') = :ay";
    $stmt = $db->prepare($query);
    $stmt->execute(['ay' => $ay]);
    $row = $stmt->fetch();
    $islemHacmi[] = $row['toplam'] ?? 0;
}
?>

var ctx2 = document.getElementById('islemHacmiBar').getContext('2d');
var islemHacmiBar = new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($aylar); ?>,
        datasets: [{
            label: 'İşlem Hacmi',
            data: <?php echo json_encode($islemHacmi); ?>,
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?> 