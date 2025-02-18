<?php
require_once 'config/db.php';
require_once 'auth/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $db->beginTransaction();

    $islem_id = $_POST['islem_id'] ?? null;
    $tutar = floatval($_POST['tutar'] ?? 0);
    $odeme_turu = $_POST['odeme_turu'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';

    if (!$islem_id || $tutar <= 0 || empty($odeme_turu)) {
        throw new Exception('Geçersiz ödeme bilgileri!');
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

    // Ödemeyi kaydet
    $stmt = $db->prepare("INSERT INTO odemeler (islem_id, tutar, odeme_turu, aciklama, ekleyen_id) 
                         VALUES (:islem_id, :tutar, :odeme_turu, :aciklama, :ekleyen_id)");
    
    $stmt->execute([
        'islem_id' => $islem_id,
        'tutar' => $tutar,
        'odeme_turu' => $odeme_turu,
        'aciklama' => $aciklama,
        'ekleyen_id' => $_SESSION['kullanici_id']
    ]);

    // Toplam ödenen tutarı kontrol et
    $stmt = $db->prepare("SELECT SUM(tutar) as toplam_odenen 
                         FROM odemeler 
                         WHERE islem_id = :islem_id");
    $stmt->execute(['islem_id' => $islem_id]);
    $toplam_odenen = $stmt->fetch()['toplam_odenen'];

    // Eğer toplam ödeme tutarı işlem tutarına eşit veya fazlaysa işlemi ödenmiş olarak işaretle
    if ($toplam_odenen >= $islem['tutar']) {
        $stmt = $db->prepare("UPDATE islemler 
                            SET odeme_durumu = 'odendi', 
                                odeme_tarihi = NOW() 
                            WHERE id = :islem_id");
        $stmt->execute(['islem_id' => $islem_id]);

        // Bildirim ekle
        $bildirim_mesaji = sprintf(
            "%s için %s TL tutarındaki %s ödemesi tamamlandı.",
            $islem['cari_adi'],
            number_format($islem['tutar'], 2, ',', '.'),
            $islem['islem_turu']
        );

        $stmt = $db->prepare("INSERT INTO bildirimler (kullanici_id, cari_id, islem_id, mesaj) 
                            VALUES (:kullanici_id, :cari_id, :islem_id, :mesaj)");
        $stmt->execute([
            'kullanici_id' => $_SESSION['kullanici_id'],
            'cari_id' => $islem['cari_id'],
            'islem_id' => $islem_id,
            'mesaj' => $bildirim_mesaji
        ]);
    }

    $db->commit();
    
    $response = [
        'success' => true,
        'message' => 'Ödeme başarıyla kaydedildi.',
        'odeme_durumu' => $toplam_odenen >= $islem['tutar'] ? 'odendi' : 'odenmedi',
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