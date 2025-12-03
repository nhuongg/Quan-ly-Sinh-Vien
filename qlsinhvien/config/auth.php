<?php
/**
 * File xử lý Authentication và Authorization
 * Quản lý phiên đăng nhập và phân quyền người dùng
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Lấy thông tin user hiện tại
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return array(
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? ''
        );
    }
    return null;
}

/**
 * Lấy role của user hiện tại
 */
function getCurrentRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Kiểm tra role có phải là admin không
 */
function isAdmin() {
    return getCurrentRole() === 'admin';
}

/**
 * Kiểm tra role có phải là teacher không
 */
function isTeacher() {
    return getCurrentRole() === 'teacher';
}

/**
 * Kiểm tra role có phải là student không
 */
function isStudent() {
    return getCurrentRole() === 'student';
}

/**
 * Kiểm tra quyền truy cập
 * @param array $allowedRoles - Mảng các role được phép
 * @param string $redirectUrl - URL chuyển hướng nếu không có quyền
 */
function checkAccess($allowedRoles = [], $redirectUrl = '../access_denied.php') {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
    
    if (!empty($allowedRoles) && !in_array(getCurrentRole(), $allowedRoles)) {
        header("Location: " . $redirectUrl);
        exit();
    }
}

/**
 * Đăng nhập người dùng
 */
function loginUser($userId, $email, $role, $fullName) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['login_time'] = time();
}

/**
 * Đăng xuất người dùng
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Lấy thông tin sinh viên từ user_id
 */
function getStudentInfo($conn, $userId) {
    $sql = "SELECT s.*, u.email, u.full_name, u.phone, u.address 
            FROM students s 
            INNER JOIN users u ON s.user_id = u.id 
            WHERE s.user_id = " . intval($userId);
    return fetchOne($conn, $sql);
}

/**
 * Lấy thông tin giảng viên từ user_id
 */
function getTeacherInfo($conn, $userId) {
    $sql = "SELECT t.*, u.email, u.full_name, u.phone, u.address 
            FROM teachers t 
            INNER JOIN users u ON t.user_id = u.id 
            WHERE t.user_id = " . intval($userId);
    return fetchOne($conn, $sql);
}

/**
 * Lấy student_id từ user_id
 */
function getStudentIdByUserId($conn, $userId) {
    $sql = "SELECT id FROM students WHERE user_id = " . intval($userId);
    $result = fetchOne($conn, $sql);
    return $result ? $result['id'] : null;
}

/**
 * Lấy teacher_id từ user_id
 */
function getTeacherIdByUserId($conn, $userId) {
    $sql = "SELECT id FROM teachers WHERE user_id = " . intval($userId);
    $result = fetchOne($conn, $sql);
    return $result ? $result['id'] : null;
}

/**
 * Tạo thông báo flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type; // success, error, warning, info
    $_SESSION['flash_message'] = $message;
}

/**
 * Lấy và xóa flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = array(
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        );
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>
