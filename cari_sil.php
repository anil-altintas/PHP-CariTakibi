<?php
require_once 'config/db.php';
require_once 'models/Cari.php';

if (isset($_GET['id'])) {
    $cari = new Cari($db);
    
    if ($cari->sil($_GET['id'])) {
        header("Location: cari_listesi.php?mesaj=silindi");
        exit();
    } else {
        header("Location: cari_listesi.php?mesaj=hata");
        exit();
    }
} else {
    header("Location: cari_listesi.php");
    exit();
} 