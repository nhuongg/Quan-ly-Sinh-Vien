<?php
/**
 * Trang thông báo không có quyền truy cập
 */
require_once 'config/auth.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy cập bị từ chối</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <h1 style="color: var(--danger-color);">
            <i class="fas fa-exclamation-triangle"></i> Truy cập bị từ chối
        </h1>
        <p style="margin: 20px 0; color: var(--text-secondary);">
            Bạn không có quyền truy cập vào trang này.
        </p>
        
        <?php if (isLoggedIn()): ?>
            <a href="<?php 
                switch(getCurrentRole()) {
                    case 'admin': echo 'admin/dashboard.php'; break;
                    case 'teacher': echo 'teacher/dashboard.php'; break;
                    case 'student': echo 'student/dashboard.php'; break;
                    default: echo 'index.php';
                }
            ?>" class="auth-button" style="display: inline-block; text-decoration: none;">
                Quay về trang chủ
            </a>
        <?php else: ?>
            <a href="login.php" class="auth-button" style="display: inline-block; text-decoration: none;">
                Đăng nhập
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
