# Cari Takip Sistemi

Modern ve kullanıcı dostu arayüzü ile işletmenizin cari hesap takibini kolaylaştıran web tabanlı bir yönetim sistemi.

## Özellikler

### Genel Özellikler
- 🔐 Güvenli kullanıcı girişi ve yetkilendirme sistemi
- 📱 Responsive tasarım (Mobil uyumlu)
- 🌙 Modern ve kullanıcı dostu arayüz
- 📊 Detaylı raporlama ve analiz
- 🔔 Gerçek zamanlı bildirim sistemi

### Cari İşlemleri
- ➕ Yeni cari hesap oluşturma
- 📝 Cari hesap bilgilerini düzenleme
- 📋 Detaylı cari hesap listesi
- 🔍 Gelişmiş arama ve filtreleme
- 📊 Cari bazlı borç/alacak takibi

### Finansal İşlemler
- 💰 Borç/Alacak işlemleri
- 💳 Çoklu ödeme yöntemi (Nakit, Havale/EFT, Kredi Kartı, Çek, Senet)
- 📅 Vade takibi
- 🧾 Ödeme geçmişi
- 📈 Finansal istatistikler

### Raporlama
- 📊 Genel durum özeti
- 📈 Aylık borç/alacak grafikleri
- 📉 İşlem hacmi analizi
- 🏢 Cari bazlı raporlar
- 📅 Tarih bazlı filtreleme

### Bildirim Sistemi
- 🔔 Vade yaklaşan işlemler için bildirimler
- ✉️ Ödeme bildirimleri
- 📢 Sistem bildirimleri
- 👁️ Okundu/Okunmadı takibi

## Teknik Özellikler

- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5
- JavaScript (ES6+)
- PDO Database bağlantısı
- Prepared Statements ile güvenli SQL sorguları
- SweetAlert2 entegrasyonu
- Font Awesome ikonları
- Chart.js grafikleri
- DataTables entegrasyonu

## Kurulum

1. Dosyaları web sunucunuza yükleyin
2. `database/schema.sql` dosyasını veritabanınıza import edin
3. `config/database.php` dosyasında veritabanı bağlantı bilgilerinizi güncelleyin
4. database/trigger.sql dosyasını da veritabanında çalıştırmanız gerekmektedir
5. Varsayılan giriş bilgileri:
   - Kullanıcı adı: admin
   - Şifre: admin123

## Güvenlik

- 🔒 Şifreler güvenli bir şekilde hash'lenerek saklanır
- 🛡️ SQL Injection koruması
- 🔐 Session bazlı kullanıcı doğrulama
- 🚫 XSS koruması
- 📝 İşlem logları

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web sunucusu
- mod_rewrite modülü (Apache için)

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakınız.

## Katkıda Bulunma

1. Bu depoyu fork edin
2. Yeni bir özellik dalı oluşturun (`git checkout -b yeni-ozellik`)
3. Değişikliklerinizi commit edin (`git commit -am 'Yeni özellik: XYZ'`)
4. Dalınıza push yapın (`git push origin yeni-ozellik`)
5. Bir Pull Request oluşturun

## İletişim

Sorularınız ve önerileriniz için Issues bölümünü kullanabilirsiniz.

