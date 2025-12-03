<?php
/**
 * Header template cho các trang trong hệ thống
 * Hiển thị sidebar navigation
 */

if (!isset($pageTitle)) {
    $pageTitle = "Hệ thống Quản lý Sinh viên";
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    header("Location: ../login.php");
    exit();
}

$role = $currentUser['role'];
$fullName = $currentUser['full_name'];

// Định nghĩa menu cho từng role
$menus = [
    'student' => [
        ['url' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Trang chủ'],
        ['url' => 'thoi_khoa_bieu.php', 'icon' => 'fa-calendar-alt', 'text' => 'Thời khóa biểu'],
        ['url' => 'dang_ky_hoc_phan.php', 'icon' => 'fa-book', 'text' => 'Đăng ký học phần'],
        ['url' => 'tra_cuu_diem.php', 'icon' => 'fa-chart-bar', 'text' => 'Tra cứu điểm'],
        ['url' => 'thong_bao.php', 'icon' => 'fa-bell', 'text' => 'Thông báo'],
    ],
    'teacher' => [
        ['url' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Trang chủ'],
        ['url' => 'thoi_khoa_bieu.php', 'icon' => 'fa-calendar-alt', 'text' => 'Thời khóa biểu'],
        ['url' => 'quan_ly_lop.php', 'icon' => 'fa-users', 'text' => 'Quản lý lớp'],
        ['url' => 'nhap_diem.php', 'icon' => 'fa-edit', 'text' => 'Nhập điểm'],
        ['url' => 'gui_thong_bao.php', 'icon' => 'fa-paper-plane', 'text' => 'Gửi thông báo'],
    ],
    'admin' => [
        ['url' => 'dashboard.php', 'icon' => 'fa-home', 'text' => 'Trang chủ'],
        ['url' => 'quan_ly_nguoi_dung.php', 'icon' => 'fa-users', 'text' => 'Quản lý người dùng'],
        ['url' => 'quan_ly_hoc_phan.php', 'icon' => 'fa-book', 'text' => 'Quản lý học phần'],
        ['url' => 'quan_ly_thoi_khoa_bieu.php', 'icon' => 'fa-calendar-week', 'text' => 'Quản lý TKB'],
        ['url' => 'thong_ke.php', 'icon' => 'fa-chart-line', 'text' => 'Thống kê'],
    ]
];

$currentMenu = $menus[$role] ?? [];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="app-body">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="../assets/images/Doublemint.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        
        <nav class="nav-menu">
            <?php foreach ($currentMenu as $menu): ?>
                <a href="<?php echo $menu['url']; ?>" class="<?php echo ($currentPage === $menu['url']) ? 'active' : ''; ?>">
                    <i class="fas <?php echo $menu['icon']; ?>"></i>
                    <?php echo $menu['text']; ?>
                </a>
            <?php endforeach; ?>
            
            <a href="../logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i>
                Đăng xuất
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
