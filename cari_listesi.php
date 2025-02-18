<?php
require_once 'config/db.php';
require_once 'models/Cari.php';

$cari = new Cari($db);
$cariler = $cari->listele();

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Cari Listesi</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-table me-1"></i>
                    Cari Hesaplar
                </div>
                <a href="cari_ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Cari Ekle
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" id="cariTable">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>Bakiye</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $cariler->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ad_soyad']); ?></td>
                        <td><?php echo htmlspecialchars($row['telefon'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                        <td class="<?php echo ($row['bakiye'] ?? 0) < 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($row['bakiye'] ?? 0, 2, ',', '.'); ?> ₺
                        </td>
                        <td>
                            <a href="cari_detay.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="cari_duzenle.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="cariSil(<?php echo $row['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#cariTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        }
    });
});

function cariSil(id) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu cariyi silmek istediğinizden emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cari_sil.php?id=' + id;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?> 