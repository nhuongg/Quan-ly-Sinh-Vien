<?php
/**
 * Trang đăng nhập
 * Cho phép người dùng đăng nhập vào hệ thống
 */

require_once 'config/db_connect.php';
require_once 'config/auth.php';

// Nếu đã đăng nhập rồi thì chuyển hướng về dashboard tương ứng
if (isLoggedIn()) {
    $role = getCurrentRole();
    switch ($role) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'teacher':
            header("Location: teacher/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
    }
    exit();
}

$error = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Tên đăng nhập và mật khẩu không được để trống';
    } else {
        // Tìm user trong database
        $email = escape_string($conn, $email);
        $password = escape_string($conn, $password);
        
        $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password' AND status = 'active'";
        $user = fetchOne($conn, $sql);
        
        if ($user) {
            // Đăng nhập thành công
            loginUser($user['id'], $user['email'], $user['role'], $user['full_name']);
            
            // Chuyển hướng theo role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'teacher':
                    header("Location: teacher/dashboard.php");
                    break;
                case 'student':
                    header("Location: student/dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Sinh viên</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <img src="assets/images/logo-doublemint.png" alt="Logo" class="logo" onerror="this.style.display='none'">
        <h1>Đăng nhập</h1>
        
        <?php if (!empty($error)): ?>
            <div style="background-color: #fee; color: #c33; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fcc;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>
            
            <button type="submit" class="auth-button">Đăng nhập</button>
        </form>
        
        <div class="auth-link" style="margin-top: 20px;">
            <a href="index.php">← Quay về trang chủ</a>
        </div>
    </div>
</body>
</html>
