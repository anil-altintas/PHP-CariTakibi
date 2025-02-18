<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['kullanici_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Okunmamış bildirimleri getir
require_once 'config/db.php';
$bildirimler = [];
$bildirim_sayisi = 0;

try {
    $stmt = $db->prepare("SELECT b.*, c.ad_soyad as cari_adi 
                         FROM bildirimler b 
                         LEFT JOIN cariler c ON b.cari_id = c.id 
                         WHERE b.kullanici_id = :kullanici_id 
                         AND b.durum = 'okunmadi' 
                         ORDER BY b.olusturma_tarihi DESC 
                         LIMIT 5");
    $stmt->execute(['kullanici_id' => $_SESSION['kullanici_id']]);
    $bildirimler = $stmt->fetchAll();
    $bildirim_sayisi = count($bildirimler);
} catch (PDOException $e) {
    // Hata durumunda sessizce devam et
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Takip Sistemi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 60px;
            --primary-color: #ffffff;
            --secondary-color: #f8f9fa;
            --text-color: #2c3e50;
            --border-color: #e9ecef;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--text-color);
        }
        
        /* Topbar Styles */
        .topbar {
            height: var(--topbar-height);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 99;
            background: var(--primary-color);
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            padding: 0 20px;
            transition: all 0.3s;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            padding: 0;
            background: var(--primary-color);
            border-right: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .sidebar.collapsed {
            margin-left: calc(-1 * var(--sidebar-width));
        }
        
        .topbar.expanded {
            left: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 20px;
            min-height: calc(100vh - var(--topbar-height));
            transition: all 0.3s;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .sidebar-header {
            padding: 20px;
            background: var(--secondary-color);
            border-bottom: 1px solid var(--border-color);
        }
        
        .nav-link {
            padding: 12px 20px;
            color: var(--text-color) !important;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: var(--secondary-color);
            border-left-color: #0d6efd;
        }
        
        .nav-link.active {
            background: var(--secondary-color);
            border-left-color: #0d6efd;
            font-weight: 600;
        }
        
        /* Card Styles */
        .card {
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
        }
        
        /* Table Styles */
        .table th {
            font-weight: 600;
            background-color: var(--secondary-color);
        }
        
        /* Notification Styles */
        .notification-dropdown {
            min-width: 300px;
            padding: 0;
            border: 1px solid var(--border-color);
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            background-color: var(--secondary-color);
        }
        
        .notification-item.unread {
            background-color: #e8f4fe;
        }
        
        .notification-item .time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* Toggle Button */
        #sidebarToggle {
            color: var(--text-color);
            padding: 0;
            font-size: 1.2rem;
        }
        
        #sidebarToggle:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <nav class="topbar">
        <div class="d-flex align-items-center justify-content-between h-100">
            <div>
                <button class="btn btn-link" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="d-flex align-items-center">
                <!-- Bildirimler -->
                <div class="dropdown me-3">
                    <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fa-lg text-muted"></i>
                        <?php if ($bildirim_sayisi > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $bildirim_sayisi; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <h6 class="dropdown-header">Bildirimler</h6>
                        <?php if ($bildirim_sayisi > 0): ?>
                            <?php foreach ($bildirimler as $bildirim): ?>
                            <a class="dropdown-item notification-item unread" href="cari_detay.php?id=<?php echo $bildirim['cari_id']; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted time">
                                            <?php echo date('d.m.Y H:i', strtotime($bildirim['olusturma_tarihi'])); ?>
                                        </div>
                                        <div><?php echo $bildirim['mesaj']; ?></div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            <a class="dropdown-item text-center small text-gray-500" href="bildirimler.php">Tüm Bildirimleri Göster</a>
                        <?php else: ?>
                            <div class="dropdown-item text-center">Yeni bildirim yok</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Kullanıcı Menüsü -->
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg me-2"></i>
                        <?php echo $_SESSION['kullanici_adi']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">Cari Takip</h4>
        </div>
        
        <!-- Menü -->
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2"></i> Ana Sayfa
                </a>
            </li>
            <li class="nav-item">
                <a href="cari_ekle.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cari_ekle.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus me-2"></i> Cari Ekle
                </a>
            </li>
            <li class="nav-item">
                <a href="cari_listesi.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cari_listesi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> Cari Listesi
                </a>
            </li>
            <li class="nav-item">
                <a href="raporlar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'raporlar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> Raporlar
                </a>
            </li>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <li class="nav-item">
                <a href="ayarlar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ayarlar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog me-2"></i> Ayarlar
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <!-- Ana İçerik -->
    <div class="main-content">
    
    <!-- Sidebar Toggle Script -->
    <script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.topbar').classList.toggle('expanded');
        document.querySelector('.main-content').classList.toggle('expanded');
    });
    </script>
</body>
</html>
              