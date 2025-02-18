<?php
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Admin yetkisi gerektiren sayfalar için kontrol
function adminKontrol() {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header('Location: index.php?hata=yetki');
        exit();
    }
}

// Kullanıcı bilgilerini getir
function getKullaniciBilgileri($db) {
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['kullanici_id']]);
    return $stmt->fetch();
}

// Okunmamış bildirimleri getir
function getOkunmamisBildirimSayisi($db) {
    $stmt = $db->prepare("SELECT COUNT(*) as sayi FROM bildirimler WHERE kullanici_id = :kullanici_id AND durum = 'okunmadi'");
    $stmt->execute(['kullanici_id' => $_SESSION['kullanici_id']]);
    $sonuc = $stmt->fetch();
    return $sonuc['sayi'];
} 