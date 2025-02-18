           
        <footer class="py-4 bg-light mt-auto border-top">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">
                        <span class="fw-bold">Cari Takip</span> &copy; <?php echo date('Y'); ?>
                        <span class="mx-1">·</span>
                        <a href="#" class="text-decoration-none text-muted">Gizlilik</a>
                        <span class="mx-1">·</span>
                        <a href="#" class="text-decoration-none text-muted">Kullanım Şartları</a>
                    </div>
                    <div class="text-muted">
                        <span class="me-2">Sürüm 1.0.0</span>
                        <span class="mx-1">·</span>
                        <a href="https://github.com/anil-altintas" target="_blank" class="text-decoration-none text-muted">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap ve diğer JS dosyaları -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/scripts.js"></script>

<!-- Sayfa yüklendiğinde çalışacak script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // URL'den mesaj parametresini al
    const urlParams = new URLSearchParams(window.location.search);
    const mesaj = urlParams.get('mesaj');
    
    // Mesaj varsa SweetAlert ile göster
    if (mesaj) {
        let alertData = {
            title: 'Başarılı!',
            icon: 'success'
        };
        
        switch(mesaj) {
            case 'islem_eklendi':
                alertData.text = 'İşlem başarıyla eklendi!';
                break;
            case 'islem_silindi':
                alertData.text = 'İşlem başarıyla silindi!';
                break;
            case 'odeme_eklendi':
                alertData.text = 'Ödeme başarıyla kaydedildi!';
                break;
            case 'odeme_silindi':
                alertData.text = 'Ödeme başarıyla silindi!';
                break;
            case 'cari_guncellendi':
                alertData.text = 'Cari bilgileri güncellendi!';
                break;
            default:
                return; // Bilinmeyen mesaj türü
        }
        
        Swal.fire(alertData);
        
        // Mesajı URL'den kaldır
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>
</body>
</html> 