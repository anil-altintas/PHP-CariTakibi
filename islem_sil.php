<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $db->beginTransaction();

    $islem_id = $_GET['id'] ?? null;

    if (!$islem_id) {
        throw new Exception('Geçersiz işlem ID!');
    }

    // İşlem bilgilerini al
    $stmt = $db->prepare("SELECT i.*, c.ad_soyad as cari_adi 
                         FROM islemler i 
                         JOIN cariler c ON i.cari_id = c.id 
                         WHERE i.id = :islem_id");
    $stmt->execute(['islem_id' => $islem_id]);
    $islem = $stmt->fetch();

    if (!$islem) {
        throw new Exception('İşlem bulunamadı!');
    }

    // Ödemeleri sil
    $stmt = $db->prepare("DELETE FROM odemeler WHERE islem_id = :islem_id");
    $stmt->execute(['islem_id' => $islem_id]);

    // Bildirimleri sil
    $stmt = $db->prepare("DELETE FROM bildirimler WHERE islem_id = :islem_id");
    $stmt->execute(['islem_id' => $islem_id]);

    // İşlemi sil
    $stmt = $db->prepare("DELETE FROM islemler WHERE id = :islem_id");
    $stmt->execute(['islem_id' => $islem_id]);

    $db->commit();
    
    $response = [
        'success' => true,
        'message' => 'İşlem başarıyla silindi.'
    ];

} catch (Exception $e) {
    $db->rollBack();
    $response = [
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ];
}

echo json_encode($response); 