-- İşlem bakiye güncelleme trigger'ı
DROP TRIGGER IF EXISTS islem_bakiye_guncelle;

CREATE TRIGGER islem_bakiye_guncelle 
BEFORE INSERT ON islemler 
FOR EACH ROW 
SET 
    NEW.islem_oncesi_bakiye = (
        SELECT COALESCE(MAX(islem_sonrasi_bakiye), 0) 
        FROM islemler 
        WHERE cari_id = NEW.cari_id
    ),
    NEW.islem_sonrasi_bakiye = (
        SELECT COALESCE(MAX(islem_sonrasi_bakiye), 0) 
        FROM islemler 
        WHERE cari_id = NEW.cari_id
    ) + IF(NEW.islem_turu = 'borc', NEW.tutar, -NEW.tutar); 