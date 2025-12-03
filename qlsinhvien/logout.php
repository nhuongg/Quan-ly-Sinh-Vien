<?php
/**
 * Đăng xuất người dùng
 */

require_once 'config/auth.php';

// Đăng xuất
logoutUser();

// Chuyển về trang đăng nhập
header("Location: login.php");
exit();
?>
