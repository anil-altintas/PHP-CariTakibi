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

    $odeme_id = $_GET['id'] ?? null;

    if (!$odeme_id) {
        throw new Exception('Geçersiz ödeme ID!');
    }

    // Ödeme bilgilerini al
    $stmt = $db->prepare("SELECT o.*, i.tutar as islem_tutar, i.id as islem_id 
                         FROM odemeler o
                         JOIN islemler i ON o.islem_id = i.id
                         WHERE o.id = :odeme_id");
    $stmt->execute(['odeme_id' => $odeme_id]);
    $odeme = $stmt->fetch();

    if (!$odeme) {
        throw new Exception('Ödeme bulunamadı!');
    }

    // Ödemeyi sil
    $stmt = $db->prepare("DELETE FROM odemeler WHERE id = :odeme_id");
    $stmt->execute(['odeme_id' => $odeme_id]);

    // İşlemin toplam ödenen tutarını kontrol et
    $stmt = $db->prepare("SELECT SUM(tutar) as toplam_odenen 
                         FROM odemeler 
                         WHERE islem_id = :islem_id");
    $stmt->execute(['islem_id' => $odeme['islem_id']]);
    $toplam_odenen = $stmt->fetch()['toplam_odenen'] ?? 0;

    // Eğer toplam ödeme tutarı işlem tutarından azsa, işlemi ödenmedi olarak işaretle
    if ($toplam_odenen < $odeme['islem_tutar']) {
        $stmt = $db->prepare("UPDATE islemler 
                            SET odeme_durumu = 'odenmedi', 
                                odeme_tarihi = NULL 
                            WHERE id = :islem_id");
        $stmt->execute(['islem_id' => $odeme['islem_id']]);
    }

    $db->commit();
    
    $response = [
        'success' => true,
        'message' => 'Ödeme başarıyla silindi.',
        'toplam_odenen' => $toplam_odenen
    ];

} catch (Exception $e) {
    $db->rollBack();
    $response = [
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ];
}

echo json_encode($response); 