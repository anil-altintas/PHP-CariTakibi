-- Mevcut veritabanını sil ve yeniden oluştur
DROP DATABASE IF EXISTS cari_takip;
CREATE DATABASE cari_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE cari_takip;

-- Kullanıcılar tablosu
CREATE TABLE kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'kullanici') NOT NULL DEFAULT 'kullanici',
    son_giris TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Cariler tablosu
CREATE TABLE cariler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100) NOT NULL,
    telefon VARCHAR(20),
    email VARCHAR(100),
    adres TEXT,
    vergi_no VARCHAR(20),
    tc_kimlik VARCHAR(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ekleyen_id INT,
    FOREIGN KEY (ekleyen_id) REFERENCES kullanicilar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- İşlemler tablosu
CREATE TABLE islemler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cari_id INT NOT NULL,
    islem_turu ENUM('borc', 'alacak') NOT NULL,
    tutar DECIMAL(10,2) NOT NULL,
    aciklama TEXT,
    vade_tarihi DATE NULL DEFAULT NULL,
    odeme_durumu ENUM('odenmedi', 'odendi') DEFAULT 'odenmedi',
    odeme_tarihi DATE NULL DEFAULT NULL,
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ekleyen_id INT,
    FOREIGN KEY (cari_id) REFERENCES cariler(id) ON DELETE CASCADE,
    FOREIGN KEY (ekleyen_id) REFERENCES kullanicilar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Ödemeler tablosu
CREATE TABLE odemeler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    islem_id INT NOT NULL,
    tutar DECIMAL(10,2) NOT NULL,
    odeme_turu ENUM('nakit', 'havale', 'eft', 'kredi_karti', 'cek', 'senet') NOT NULL,
    aciklama TEXT,
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ekleyen_id INT,
    FOREIGN KEY (islem_id) REFERENCES islemler(id) ON DELETE CASCADE,
    FOREIGN KEY (ekleyen_id) REFERENCES kullanicilar(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Bildirimler tablosu
CREATE TABLE bildirimler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    cari_id INT NOT NULL,
    islem_id INT NOT NULL,
    mesaj TEXT NOT NULL,
    durum ENUM('okunmadi', 'okundu') DEFAULT 'okunmadi',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (cari_id) REFERENCES cariler(id) ON DELETE CASCADE,
    FOREIGN KEY (islem_id) REFERENCES islemler(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Ayarlar tablosu
CREATE TABLE ayarlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anahtar VARCHAR(50) NOT NULL UNIQUE,
    deger TEXT,
    aciklama TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Örnek admin kullanıcısı (şifre: admin123)
INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, rol) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Kullanıcı', 'admin');

-- Örnek cariler
INSERT INTO cariler (ad_soyad, telefon, email, adres, vergi_no, tc_kimlik, ekleyen_id) VALUES
('Ahmet Yılmaz', '5551234567', 'ahmet@example.com', 'İstanbul', '1234567890', '12345678901', 1),
('Mehmet Demir', '5559876543', 'mehmet@example.com', 'Ankara', '9876543210', '98765432109', 1),
('Ayşe Kaya', '5553334444', 'ayse@example.com', 'İzmir', '4567891230', '45678912345', 1);

-- Örnek işlemler
INSERT INTO islemler (cari_id, islem_turu, tutar, aciklama, vade_tarihi, odeme_durumu, ekleyen_id) VALUES
(1, 'borc', 1500.00, 'Malzeme alımı', '2024-03-15', 'odenmedi', 1),
(2, 'alacak', 2500.00, 'Hizmet bedeli', '2024-03-20', 'odendi', 1),
(3, 'borc', 1000.00, 'Kırtasiye malzemeleri', '2024-03-25', 'odenmedi', 1),
(1, 'alacak', 3000.00, 'Proje ödemesi', '2024-03-30', 'odenmedi', 1);

-- Örnek ödemeler
INSERT INTO odemeler (islem_id, tutar, odeme_turu, aciklama, ekleyen_id) VALUES
(2, 2500.00, 'havale', 'Hizmet bedeli ödemesi', 1);

-- Örnek ayarlar
INSERT INTO ayarlar (anahtar, deger, aciklama) VALUES
('site_baslik', 'Cari Takip Sistemi', 'Site başlığı'),
('firma_adi', 'Örnek Firma Ltd. Şti.', 'Firma adı'),
('firma_adres', 'Örnek Mah. Test Sok. No:1 İstanbul', 'Firma adresi'),
('firma_telefon', '0212 123 45 67', 'Firma telefonu'),
('firma_email', 'info@ornekfirma.com', 'Firma e-posta adresi'),
('firma_vergi_no', '1234567890', 'Firma vergi numarası'),
('firma_vergi_dairesi', 'Örnek Vergi Dairesi', 'Firma vergi dairesi'),
('para_birimi', '₺', 'Para birimi'),
('kdv_orani', '18', 'Varsayılan KDV oranı'),
('vade_uyari_gun', '7', 'Vade tarihi yaklaşan işlemler için kaç gün önceden uyarı verileceği'); 