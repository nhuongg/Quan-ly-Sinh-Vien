<?php
/**
 * Trang chủ công khai
 * Giới thiệu về hệ thống
 */
require_once 'config/auth.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Sinh viên</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="public-nav">
        <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="assets/images/Doublemint.png" alt="Logo" onerror="this.style.display='none'">
            <span>Hệ thống Quản lý Sinh viên</span>
        </a>
            <div class="nav-links">
                <a href="#about">Giới thiệu</a>
                <a href="#features">Tính năng</a>
                <a href="#contact">Liên hệ</a>
            </div>
            <div class="nav-auth">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php 
                        switch(getCurrentRole()) {
                            case 'admin': echo 'admin/dashboard.php'; break;
                            case 'teacher': echo 'teacher/dashboard.php'; break;
                            case 'student': echo 'student/dashboard.php'; break;
                        }
                    ?>" class="btn btn-login">Vào hệ thống</a>
                    <a href="logout.php" class="btn btn-register">Đăng xuất</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Chào mừng đến với Hệ thống Quản lý Sinh viên</h1>
            <p>Giải pháp toàn diện cho việc quản lý thông tin sinh viên, giảng viên và hoạt động học tập</p>
            <a href="login.php" class="btn btn-register btn-large">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
            </a>
        </div>
    </section>

    <!-- Main Content -->
    <main class="public-main">
        <!-- About Section -->
        <section id="about" class="info-section">
            <h2>Giới thiệu</h2>
            <p style="text-align: center; color: var(--text-secondary); max-width: 800px; margin: 0 auto 32px;">
                Hệ thống Quản lý Sinh viên là một nền tảng quản lý toàn diện, giúp các trường đại học, cao đẳng 
                quản lý hiệu quả thông tin sinh viên, giảng viên, học phần và các hoạt động học tập.
            </p>
        </section>

        <!-- Features Section -->
        <section id="features" class="info-section">
            <h2>Tính năng nổi bật</h2>
            <div class="info-grid">
                <div class="info-card">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Dành cho Sinh viên</h3>
                    <p>Đăng ký học phần, tra cứu điểm, xem thông báo và quản lý thông tin cá nhân</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Dành cho Giảng viên</h3>
                    <p>Quản lý lớp học, nhập điểm, gửi thông báo và theo dõi tiến độ học tập</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-user-shield"></i>
                    <h3>Dành cho Quản trị viên</h3>
                    <p>Quản lý người dùng, phân quyền, theo dõi hệ thống và báo cáo thống kê</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-book"></i>
                    <h3>Quản lý Học phần</h3>
                    <p>Quản lý thông tin học phần, lớp học, lịch học và danh sách sinh viên</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Thống kê Báo cáo</h3>
                    <p>Theo dõi và thống kê kết quả học tập, tỷ lệ đăng ký học phần</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-bell"></i>
                    <h3>Thông báo</h3>
                    <p>Hệ thống thông báo tức thời cho tất cả các đối tượng sử dụng</p>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="info-section">
            <h2>Liên hệ</h2>
            <div style="text-align: center; color: var(--text-secondary);">
                <p><i class="fas fa-envelope"></i> Email: contact@qlsv.edu.vn</p>
                <p><i class="fas fa-phone"></i> Hotline: 1900-xxxx</p>
                <p><i class="fas fa-map-marker-alt"></i> Địa chỉ: Hà Nội, Việt Nam</p>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="public-footer">
        <p>&copy; 2025 Hệ thống Quản lý Sinh viên. All rights reserved.</p>
        <p>Phát triển bởi Nguyen Van Huong</p>
    </footer>
</body>
</html>
