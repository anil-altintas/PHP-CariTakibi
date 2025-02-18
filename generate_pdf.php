<?php
require_once 'config/database.php';
require_once 'models/Cari.php';
require_once 'models/Islem.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$database = new Database();
$db = $database->getConnection();

$cari = new Cari();
$islem = new Islem();

if(isset($_GET['id'])) {
    $cari->id = $_GET['id'];
    $cari->getir();
} else {
    header("Location: index.php");
    exit;
}

// PDF oluşturma ayarları
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

// PDF içeriği
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cari Ekstre - ' . $cari->ad_soyad . '</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { margin-top: 20px; text-align: right; }
        .red { color: red; }
        .green { color: green; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cari Ekstre</h1>
        <p>' . date('d.m.Y') . '</p>
    </div>

    <div class="info">
        <h3>Cari Bilgileri</h3>
        <p><strong>Ad Soyad:</strong> ' . $cari->ad_soyad . '</p>
        <p><strong>Telefon:</strong> ' . $cari->telefon . '</p>
        <p><strong>E-posta:</strong> ' . $cari->email . '</p>
        <p><strong>Adres:</strong> ' . $cari->adres . '</p>
        <p><strong>Güncel Bakiye:</strong> <span class="' . ($cari->bakiye < 0 ? 'red' : 'green') . '">' . 
        number_format($cari->bakiye, 2, ',', '.') . ' ₺</span></p>
    </div>

    <h3>İşlem Geçmişi</h3>
    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                <th>İşlem Türü</th>
                <th>Tutar</th>
                <th>Bakiye</th>
                <th>Vade</th>
                <th>Açıklama</th>
            </tr>
        </thead>
        <tbody>';

$stmt = $islem->cariIslemleriListele($cari->id);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $html .= '<tr>
        <td>' . date('d.m.Y H:i', strtotime($row['olusturma_tarihi'])) . '</td>
        <td>' . ($row['islem_turu'] == 'borc' ? 'Borç' : 'Alacak') . '</td>
        <td>' . number_format($row['tutar'], 2, ',', '.') . ' ₺</td>
        <td class="' . ($row['guncel_bakiye'] < 0 ? 'red' : 'green') . '">' . 
        number_format($row['guncel_bakiye'], 2, ',', '.') . ' ₺</td>
        <td>' . ($row['vade_tarihi'] ? date('d.m.Y', strtotime($row['vade_tarihi'])) : '-') . '</td>
        <td>' . ($row['aciklama'] ?: '-') . '</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// PDF'i indir
$dompdf->stream("cari_ekstre_" . $cari->id . ".pdf", array("Attachment" => true));
?> 