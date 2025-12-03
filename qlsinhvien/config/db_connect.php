<?php
// Thông tin kết nối database
$servername = "localhost";
$username = "root";
$password = "081205";
$dbname = "qlsinhvien";
$port = 3306;

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Thiết lập charset UTF-8
$conn->set_charset("utf8mb4");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Hàm để ngắt kết nối
function closeConnection($connection) {
    if ($connection) {
        $connection->close();
    }
}

// Hàm escape string để tránh SQL injection
function escape_string($conn, $string) {
    return $conn->real_escape_string($string);
}

// Hàm thực thi query và trả về kết quả
function executeQuery($conn, $sql) {
    $result = $conn->query($sql);
    return $result;
}

// Hàm lấy một dòng dữ liệu
function fetchOne($conn, $sql) {
    $result = executeQuery($conn, $sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Hàm lấy tất cả dữ liệu
function fetchAll($conn, $sql) {
    $result = executeQuery($conn, $sql);
    $data = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Hàm đếm số dòng
function countRows($conn, $sql) {
    $result = executeQuery($conn, $sql);
    if ($result) {
        return $result->num_rows;
    }
    return 0;
}
?>
