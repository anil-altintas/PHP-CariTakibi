<?php
require_once 'config/db.php';
require_once 'models/Cari.php';

$cari = new Cari($db);

// Son 6 ayın verilerini al
$aylar = array();
$borclar = array();
$alacaklar = array();

for ($i = 5; $i >= 0; $i--) {
    $ay = date('Y-m', strtotime("-$i months"));
    $aylar[] = date('F Y', strtotime("-$i months"));
    
    $query = "SELECT 
        SUM(CASE WHEN islem_turu = 'borc' THEN tutar ELSE 0 END) as borc,
        SUM(CASE WHEN islem_turu = 'alacak' THEN tutar ELSE 0 END) as alacak
        FROM islemler 
        WHERE DATE_FORMAT(tarih, '%Y-%m') = :ay";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['ay' => $ay]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $borclar[] = $row['borc'] ?? 0;
    $alacaklar[] = $row['alacak'] ?? 0;
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Raporlar</h1>
    
    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Son 6 Ay Borç/Alacak Grafiği
                </div>
                <div class="card-body">
                    <canvas id="aylikGrafik" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    En Çok İşlem Yapılan Cariler
                </div>
                <div class="card-body">
                    <canvas id="cariPie" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    Aylık Özet Rapor
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Ay</th>
                                <th>Toplam Borç</th>
                                <th>Toplam Alacak</th>
                                <th>Net Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < 6; $i++): ?>
                            <tr>
                                <td><?php echo $aylar[$i]; ?></td>
                                <td class="text-danger">
                                    <?php echo number_format($borclar[$i], 2, ',', '.'); ?> ₺
                                </td>
                                <td class="text-success">
                                    <?php echo number_format($alacaklar[$i], 2, ',', '.'); ?> ₺
                                </td>
                                <td class="<?php echo ($alacaklar[$i] - $borclar[$i] >= 0) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format($alacaklar[$i] - $borclar[$i], 2, ',', '.'); ?> ₺
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Aylık Borç/Alacak Grafiği
var ctx = document.getElementById('aylikGrafik').getContext('2d');
var aylikGrafik = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($aylar); ?>,
        datasets: [{
            label: 'Borçlar',
            data: <?php echo json_encode($borclar); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            fill: true
        },
        {
            label: 'Alacaklar',
            data: <?php echo json_encode($alacaklar); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// En Çok İşlem Yapılan Cariler Pasta Grafiği
<?php
$query = "SELECT c.ad_soyad, COUNT(*) as islem_sayisi 
          FROM islemler i 
          JOIN cariler c ON i.cari_id = c.id 
          GROUP BY i.cari_id 
          ORDER BY islem_sayisi DESC 
          LIMIT 5";
$stmt = $db->query($query);
$cariData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cariLabels = array_column($cariData, 'ad_soyad');
$cariValues = array_column($cariData, 'islem_sayisi');
?>

var ctx2 = document.getElementById('cariPie').getContext('2d');
var cariPie = new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($cariLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($cariValues); ?>,
            backgroundColor: [
                '#0d6efd',
                '#6610f2',
                '#6f42c1',
                '#d63384',
                '#dc3545'
            ]
        }]
    }
});
</script>

<?php include 'includes/footer.php'; ?> 