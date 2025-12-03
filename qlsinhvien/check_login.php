<?php
/**
 * File kiểm tra lỗi đăng nhập
 * Truy cập: http://localhost/qlsinhvien/check_login.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Kiểm tra lỗi đăng nhập</h1>";
echo "<hr>";

// Test kết nối database
echo "<h3>1. Test kết nối database...</h3>";

$servername = "localhost";
$username = "root";
$password = "081205";
$dbname = "qlSinhVien";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Lỗi kết nối: " . $conn->connect_error . "</p>";
    echo "<p><strong>GIẢI PHÁP:</strong></p>";
    echo "<ul>";
    echo "<li>Kiểm tra MySQL đã bật trong XAMPP chưa</li>";
    echo "<li>Kiểm tra mật khẩu MySQL (hiện đang dùng: 081205)</li>";
    echo "</ul>";
    exit();
}

echo "<p style='color: green;'>✅ Kết nối database thành công!</p>";

$conn->set_charset("utf8mb4");

// Test bảng users
echo "<h3>2. Kiểm tra bảng users...</h3>";

$sql = "SELECT * FROM users LIMIT 5";
$result = $conn->query($sql);

if (!$result) {
    echo "<p style='color: red;'>❌ Lỗi: Bảng 'users' không tồn tại!</p>";
    echo "<p><strong>GIẢI PHÁP:</strong> Import file database/schema.sql vào phpMyAdmin</p>";
    exit();
}

$userCount = $result->num_rows;
echo "<p style='color: green;'>✅ Bảng users tồn tại, có $userCount người dùng (hiển thị 5 đầu tiên)</p>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin-top: 10px;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Email</th><th>Role</th><th>Họ tên</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test thử đăng nhập với tài khoản mẫu
echo "<h3>3. Test đăng nhập với tài khoản admin...</h3>";

$testEmail = "admin@qlsv.com";
$testPassword = "admin";

$sql = "SELECT * FROM users WHERE email = '$testEmail' AND password = '$testPassword' AND status = 'active'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ Đăng nhập TEST thành công!</p>";
    echo "<p>Thông tin user:</p>";
    echo "<ul>";
    echo "<li>Email: " . $user['email'] . "</li>";
    echo "<li>Họ tên: " . $user['full_name'] . "</li>";
    echo "<li>Role: " . $user['role'] . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Không tìm thấy user hoặc đăng nhập thất bại!</p>";
    echo "<p>SQL: $sql</p>";
}

// Kiểm tra file auth.php
echo "<h3>4. Kiểm tra file auth.php...</h3>";

if (file_exists('config/auth.php')) {
    echo "<p style='color: green;'>✅ File config/auth.php tồn tại</p>";
    
    // Test require
    try {
        require_once 'config/auth.php';
        echo "<p style='color: green;'>✅ Require config/auth.php thành công</p>";
        
        // Test các function
        if (function_exists('isLoggedIn')) {
            echo "<p style='color: green;'>✅ Function isLoggedIn() tồn tại</p>";
        } else {
            echo "<p style='color: red;'>❌ Function isLoggedIn() không tồn tại</p>";
        }
        
        if (function_exists('loginUser')) {
            echo "<p style='color: green;'>✅ Function loginUser() tồn tại</p>";
        } else {
            echo "<p style='color: red;'>❌ Function loginUser() không tồn tại</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Lỗi khi require: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ File config/auth.php không tồn tại!</p>";
}

// Kiểm tra session
echo "<h3>5. Kiểm tra session...</h3>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p style='color: green;'>✅ Session đã được khởi tạo</p>";
} else {
    echo "<p style='color: green;'>✅ Session đã active từ trước</p>";
}

echo "<hr>";
echo "<h2 style='color: green;'>🎯 KẾT LUẬN</h2>";
echo "<p>Nếu tất cả đều hiển thị dấu ✅, hệ thống đăng nhập đã hoạt động.</p>";
echo "<p><a href='login.php' style='padding: 10px 20px; background: #1274e3; color: white; text-decoration: none; border-radius: 6px;'>Thử đăng nhập ngay</a></p>";

echo "<hr>";
echo "<h3>📝 Ghi chú:</h3>";
echo "<p>Nếu vẫn không đăng nhập được, hãy:</p>";
echo "<ol>";
echo "<li>Chụp màn hình trang này</li>";
echo "<li>Chụp màn hình lỗi khi đăng nhập</li>";
echo "<li>Gửi cho tôi để hỗ trợ</li>";
echo "</ol>";

$conn->close();
?>

