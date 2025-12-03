<?php
/**
 * File tự động tạo database
 * Truy cập: http://localhost/qlsinhvien/auto_setup_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 phút

echo "<h1>🔧 Tự động cài đặt Database</h1>";
echo "<hr>";

// Kết nối MySQL (không chọn database)
$servername = "localhost";
$username = "root";
$password = "081205";
$port = 3306;

$conn = new mysqli($servername, $username, $password, "", $port);

if ($conn->connect_error) {
    die("<p style='color: red;'>❌ Không thể kết nối MySQL: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>✅ Kết nối MySQL thành công!</p>";

// Đọc file SQL
$sqlFile = __DIR__ . '/database/schema.sql';

if (!file_exists($sqlFile)) {
    die("<p style='color: red;'>❌ Không tìm thấy file: database/schema.sql</p>");
}

echo "<p style='color: green;'>✅ Tìm thấy file schema.sql</p>";

$sqlContent = file_get_contents($sqlFile);

if (empty($sqlContent)) {
    die("<p style='color: red;'>❌ File SQL rỗng!</p>");
}

echo "<p style='color: green;'>✅ Đọc file SQL thành công (" . strlen($sqlContent) . " bytes)</p>";

echo "<h3>Đang thực thi SQL...</h3>";
echo "<p style='color: orange;'>⏳ Vui lòng đợi, quá trình có thể mất 10-30 giây...</p>";

// Thực thi từng câu lệnh SQL
$conn->multi_query($sqlContent);

// Đợi tất cả query hoàn thành
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

// Kiểm tra lỗi
if ($conn->error) {
    echo "<p style='color: red;'>❌ Lỗi SQL: " . $conn->error . "</p>";
} else {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ TẠO DATABASE THÀNH CÔNG!</p>";
}

$conn->close();

// Kết nối lại để kiểm tra
echo "<hr>";
echo "<h3>Kiểm tra kết quả...</h3>";

$conn = new mysqli($servername, $username, $password, "qlSinhVien", $port);

if ($conn->connect_error) {
    die("<p style='color: red;'>❌ Không thể kết nối database qlsinhvien</p>");
}

$conn->set_charset("utf8mb4");

// Kiểm tra các bảng
$tables = array('users', 'students', 'teachers', 'courses', 'class_courses', 'enrollments', 'grades', 'semesters', 'notifications');

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Bảng</th><th>Số dòng</th><th>Trạng thái</th></tr>";

$allOk = true;
foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td style='text-align: center;'>$count</td>";
        echo "<td style='color: green; text-align: center;'>✓</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='text-align: center;'>-</td>";
        echo "<td style='color: red; text-align: center;'>✗</td>";
        echo "</tr>";
        $allOk = false;
    }
}

echo "</table>";

echo "<hr>";

if ($allOk) {
    echo "<h2 style='color: green;'>🎉 HOÀN TẤT CÀI ĐẶT!</h2>";
    echo "<p style='font-size: 16px;'>Database đã được tạo thành công với dữ liệu mẫu.</p>";
    
    echo "<h3>Tài khoản để đăng nhập:</h3>";
    echo "<ul style='font-size: 15px;'>";
    echo "<li><strong>Admin:</strong> admin@qlsv.com / admin123</li>";
    echo "<li><strong>Giảng viên:</strong> gv1@qlsv.com / gv123</li>";
    echo "<li><strong>Sinh viên:</strong> sv1@qlsv.com / sv123</li>";
    echo "</ul>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='login.php' style='padding: 15px 30px; background: #1274e3; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;'>🚀 Đăng nhập ngay</a>";
    echo "</p>";
    
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='check_login.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 6px;'>Kiểm tra lại hệ thống</a>";
    echo "</p>";
} else {
    echo "<h2 style='color: red;'>⚠️ CÓ LỖI XẢY RA</h2>";
    echo "<p>Một số bảng không được tạo thành công. Vui lòng:</p>";
    echo "<ol>";
    echo "<li>Chụp màn hình trang này</li>";
    echo "<li>Gửi cho tôi để được hỗ trợ</li>";
    echo "</ol>";
}

$conn->close();
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 900px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1, h2, h3 {
    color: #333;
}
table {
    width: 100%;
    background: white;
    margin: 20px 0;
}
</style>

