<?php
require_once __DIR__ . '/../config/database.php';

class Islem {
    private $conn;
    private $table_name = "islemler";

    public $id;
    public $cari_id;
    public $islem_turu;
    public $tutar;
    public $onceki_bakiye;
    public $guncel_bakiye;
    public $vade_tarihi;
    public $aciklama;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Yeni işlem ekleme
    public function ekle() {
        // Önce mevcut bakiyeyi al
        $query = "SELECT bakiye FROM cariler WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->cari_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->onceki_bakiye = $row['bakiye'];
        
        // Yeni bakiyeyi hesapla
        if($this->islem_turu == 'borc') {
            $this->guncel_bakiye = $this->onceki_bakiye + $this->tutar;
        } else {
            $this->guncel_bakiye = $this->onceki_bakiye - $this->tutar;
        }

        // İşlemi kaydet
        $query = "INSERT INTO " . $this->table_name . "
                SET cari_id=:cari_id, islem_turu=:islem_turu,
                    tutar=:tutar, onceki_bakiye=:onceki_bakiye,
                    guncel_bakiye=:guncel_bakiye, vade_tarihi=:vade_tarihi,
                    aciklama=:aciklama";

        $stmt = $this->conn->prepare($query);

        $this->cari_id = htmlspecialchars(strip_tags($this->cari_id));
        $this->islem_turu = htmlspecialchars(strip_tags($this->islem_turu));
        $this->tutar = htmlspecialchars(strip_tags($this->tutar));
        $this->vade_tarihi = htmlspecialchars(strip_tags($this->vade_tarihi));
        $this->aciklama = htmlspecialchars(strip_tags($this->aciklama));

        $stmt->bindParam(":cari_id", $this->cari_id);
        $stmt->bindParam(":islem_turu", $this->islem_turu);
        $stmt->bindParam(":tutar", $this->tutar);
        $stmt->bindParam(":onceki_bakiye", $this->onceki_bakiye);
        $stmt->bindParam(":guncel_bakiye", $this->guncel_bakiye);
        $stmt->bindParam(":vade_tarihi", $this->vade_tarihi);
        $stmt->bindParam(":aciklama", $this->aciklama);

        // İşlem başarılıysa cari bakiyesini güncelle
        if($stmt->execute()) {
            $query = "UPDATE cariler SET bakiye = :bakiye WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":bakiye", $this->guncel_bakiye);
            $stmt->bindParam(":id", $this->cari_id);
            
            if($stmt->execute()) {
                return true;
            }
        }
        return false;
    }

    // Cari için işlemleri listeleme
    public function cariIslemleriListele($cari_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE cari_id = ? ORDER BY olusturma_tarihi DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cari_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Vadesi yaklaşan işlemleri getirme
    public function vadesiYaklasanlar() {
        $query = "SELECT i.*, c.ad_soyad 
                 FROM " . $this->table_name . " i
                 INNER JOIN cariler c ON i.cari_id = c.id
                 WHERE i.vade_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY i.vade_tarihi ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?> 