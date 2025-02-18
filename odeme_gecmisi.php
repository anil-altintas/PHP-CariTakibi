<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $islem_id = $_GET['islem_id'] ?? null;

    if (!$islem_id) {
        throw new Exception('Geçersiz işlem ID!');
    }

    // Ödeme geçmişini al
    $stmt = $db->prepare("SELECT o.*, k.ad_soyad as ekleyen_kullanici,
                         DATE_FORMAT(o.tarih, '%d.%m.%Y %H:%i') as tarih_format
                         FROM odemeler o
                         LEFT JOIN kullanicilar k ON o.ekleyen_id = k.id
                         WHERE o.islem_id = :islem_id
                         ORDER BY o.tarih DESC");
    
    $stmt->execute(['islem_id' => $islem_id]);
    $odemeler = $stmt->fetchAll();

    // Ödeme türlerini Türkçeleştir
    $odeme_turleri = [
        'nakit' => 'Nakit',
        'havale' => 'Havale/EFT',
        'kredi_karti' => 'Kredi Kartı',
        'cek' => 'Çek',
        'senet' => 'Senet',
        'diger' => 'Diğer'
    ];

    // Ödeme verilerini düzenle
    $odemeler = array_map(function($odeme) use ($odeme_turleri) {
        return [
            'id' => $odeme['id'],
            'tarih' => $odeme['tarih_format'],
            'tutar' => $odeme['tutar'],
            'odeme_turu' => $odeme_turleri[$odeme['odeme_turu']] ?? $odeme['odeme_turu'],
            'aciklama' => $odeme['aciklama'],
            'ekleyen' => $odeme['ekleyen_kullanici']
        ];
    }, $odemeler);

    echo json_encode([
        'success' => true,
        'odemeler' => $odemeler
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 