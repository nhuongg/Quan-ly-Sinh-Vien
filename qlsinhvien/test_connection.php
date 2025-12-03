<?php
/**
 * File test kết nối database
 * Truy cập: http://localhost/qlsinhvien/test_connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Kết Nối Database</h1>";
echo "<hr>";

// Thông tin kết nối
$servername = "localhost";
$username = "root";
$password = "081205";
$dbname = "qlSinhVien";
$port = 3306;

echo "<h3>1. Thông tin kết nối:</h3>";
echo "<ul>";
echo "<li>Server: $servername</li>";
echo "<li>Username: $username</li>";
echo "<li>Password: " . str_repeat('*', strlen($password)) . "</li>";
echo "<li>Database: $dbname</li>";
echo "<li>Port: $port</li>";
echo "</ul>";

echo "<h3>2. Thử kết nối...</h3>";

// Thử kết nối
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Kiểm tra kết nối
if ($conn->connect_error) {
    echo "<p style='color: red; font-weight: bold;'>❌ KẾT NỐI THẤT BẠI!</p>";
    echo "<p>Lỗi: " . $conn->connect_error . "</p>";
    echo "<p>Code: " . $conn->connect_errno . "</p>";
    echo "<hr>";
    echo "<h3>Cách khắc phục:</h3>";
    echo "<ol>";
    echo "<li>Kiểm tra MySQL đã bật trong XAMPP chưa</li>";
    echo "<li>Kiểm tra mật khẩu MySQL có đúng là '081205' không</li>";
    echo "<li>Kiểm tra database 'qlSinhVien' đã được tạo chưa</li>";
    echo "</ol>";
    exit();
}

echo "<p style='color: green; font-weight: bold;'>✅ KẾT NỐI THÀNH CÔNG!</p>";

// Thiết lập charset
$conn->set_charset("utf8mb4");

echo "<h3>3. Kiểm tra database...</h3>";

// Kiểm tra các bảng
$tables = array('users', 'students', 'teachers', 'courses', 'class_courses', 'enrollments', 'grades', 'semesters', 'notifications');

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Bảng</th><th>Số dòng</th><th>Trạng thái</th></tr>";

$allOk = true;
foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>$count</td>";
        echo "<td style='color: green;'>✓ OK</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>-</td>";
        echo "<td style='color: red;'>✗ KHÔNG TỒN TẠI</td>";
        echo "</tr>";
        $allOk = false;
    }
}

echo "</table>";

if (!$allOk) {
    echo "<hr>";
    echo "<h3 style='color: red;'>⚠️ CÓ BẢNG KHÔNG TỒN TẠI!</h3>";
    echo "<p>Hãy import lại file <strong>database/schema.sql</strong> vào phpMyAdmin</p>";
} else {
    echo "<hr>";
    echo "<h3 style='color: green;'>🎉 TẤT CẢ ĐỀU OK!</h3>";
    echo "<p>Hệ thống đã sẵn sàng sử dụng!</p>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #1274e3; color: white; text-decoration: none; border-radius: 6px;'>Đi đến Trang chủ</a></p>";
    echo "<p><a href='login.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 6px;'>Đăng nhập</a></p>";
}

$conn->close();
?>

