<?php
require_once __DIR__ . '/../config/database.php';

class Cari {
    private $conn;
    private $table_name = "cariler";

    public $id;
    public $ad_soyad;
    public $telefon;
    public $email;
    public $adres;
    public $bakiye;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getToplamBorc() {
        $query = "SELECT SUM(tutar) as toplam FROM islemler WHERE islem_turu = 'borc'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['toplam'] ?? 0;
    }

    public function getToplamAlacak() {
        $query = "SELECT SUM(tutar) as toplam FROM islemler WHERE islem_turu = 'alacak'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['toplam'] ?? 0;
    }

    public function getToplamCariSayisi() {
        $query = "SELECT COUNT(*) as toplam FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['toplam'];
    }

    public function getSonIslemler($limit = 5) {
        $query = "SELECT i.*, c.ad_soyad as cari_adi 
                 FROM islemler i 
                 LEFT JOIN cariler c ON i.cari_id = c.id 
                 ORDER BY i.tarih DESC 
                 LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Yeni cari ekleme
    public function ekle($ad_soyad, $telefon, $email, $adres) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (ad_soyad, telefon, email, adres) 
                 VALUES (:ad_soyad, :telefon, :email, :adres)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':ad_soyad', $ad_soyad);
        $stmt->bindParam(':telefon', $telefon);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':adres', $adres);
        
        return $stmt->execute();
    }

    // Tüm carileri listeleme
    public function listele() {
        $query = "SELECT c.*, 
                 (SELECT SUM(CASE WHEN islem_turu = 'borc' THEN tutar ELSE -tutar END) 
                  FROM islemler WHERE cari_id = c.id) as bakiye 
                 FROM " . $this->table_name . " c 
                 ORDER BY c.ad_soyad";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Cari güncelleme
    public function guncelle($id, $ad_soyad, $telefon, $email, $adres) {
        $query = "UPDATE " . $this->table_name . " 
                 SET ad_soyad = :ad_soyad, 
                     telefon = :telefon, 
                     email = :email, 
                     adres = :adres 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':ad_soyad', $ad_soyad);
        $stmt->bindParam(':telefon', $telefon);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':adres', $adres);
        
        return $stmt->execute();
    }

    // Cari silme
    public function sil($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Tek bir cariyi getirme
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 