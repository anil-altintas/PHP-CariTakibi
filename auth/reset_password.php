<?php
require_once '../config/db.php';

try {
    // admin123 şifresinin hash'i
    $yeni_sifre_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE kullanicilar SET sifre = :sifre WHERE kullanici_adi = 'admin'");
    $sonuc = $stmt->execute(['sifre' => $yeni_sifre_hash]);
    
    if ($sonuc) {
        echo "Admin şifresi başarıyla sıfırlandı!<br>";
        echo "Kullanıcı adı: admin<br>";
        echo "Şifre: admin123<br>";
        echo "<a href='login.php'>Giriş sayfasına git</a>";
    } else {
        echo "Şifre sıfırlanamadı!";
    }
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
} 