<?php
/**
 * Dashboard Giảng viên
 * Trang chủ cho giảng viên sau khi đăng nhập
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['teacher']);

$pageTitle = "Trang chủ - Giảng viên";
$currentUser = getCurrentUser();
$teacherInfo = getTeacherInfo($conn, $currentUser['id']);
$teacherId = getTeacherIdByUserId($conn, $currentUser['id']);

// Lấy kỳ học hiện tại
$sqlCurrentSemester = "SELECT * FROM semesters WHERE is_active = 'yes' LIMIT 1";
$currentSemester = fetchOne($conn, $sqlCurrentSemester);

// Đếm số lớp đang giảng dạy
$classCount = 0;
$studentCount = 0;
if ($currentSemester) {
    $sqlCount = "SELECT COUNT(*) as count, SUM(current_students) as students
                 FROM class_courses 
                 WHERE teacher_id = $teacherId AND semester_id = {$currentSemester['id']}";
    $countResult = fetchOne($conn, $sqlCount);
    $classCount = $countResult['count'] ?? 0;
    $studentCount = $countResult['students'] ?? 0;
}

// Đếm số bài chấm chưa hoàn thành (học phần chưa nhập đủ điểm)
$sqlPendingGrades = "SELECT COUNT(DISTINCT e.id) as count
                     FROM enrollments e
                     INNER JOIN class_courses cc ON e.class_course_id = cc.id
                     LEFT JOIN grades g ON e.id = g.enrollment_id
                     WHERE cc.teacher_id = $teacherId 
                     AND cc.semester_id = {$currentSemester['id']}
                     AND (g.id IS NULL OR g.total_score IS NULL)";
$pendingResult = fetchOne($conn, $sqlPendingGrades);
$pendingGrades = $pendingResult['count'] ?? 0;

// Lấy danh sách lớp đang giảng dạy
$sqlClasses = "SELECT cc.*, c.course_code, c.course_name, c.credits
               FROM class_courses cc
               INNER JOIN courses c ON cc.course_id = c.id
               WHERE cc.teacher_id = $teacherId AND cc.semester_id = {$currentSemester['id']}
               ORDER BY c.course_name";
$classes = fetchAll($conn, $sqlClasses);

// Lấy thông báo gần đây đã gửi
$sqlNotifications = "SELECT * FROM notifications 
                     WHERE sender_id = {$currentUser['id']}
                     ORDER BY created_at DESC
                     LIMIT 5";
$notifications = fetchAll($conn, $sqlNotifications);

include '../includes/header.php';
?>

<!-- Flash Message -->
<?php 
$flash = getFlashMessage();
if ($flash): 
?>
    <div style="background-color: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; 
                color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>; 
                padding: 12px 20px; border-radius: 6px; margin-bottom: 24px; border: 1px solid <?php echo $flash['type'] === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="main-header student-header">
    <div class="welcome-text">
        <h1>Xin chào, <?php echo htmlspecialchars($teacherInfo['full_name']); ?>!</h1>
        <p>Mã giảng viên: <?php echo htmlspecialchars($teacherInfo['teacher_code']); ?> | Khoa: <?php echo htmlspecialchars($teacherInfo['department']); ?></p>
    </div>
    <div class="header-actions">
        <div class="user-profile">
            <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
            <small>Giảng viên</small>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="stat-card">
        <i class="fas fa-chalkboard"></i>
        <h3><?php echo $classCount; ?></h3>
        <p>Lớp giảng dạy</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3><?php echo $studentCount; ?></h3>
        <p>Tổng sinh viên</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-tasks"></i>
        <h3><?php echo $pendingGrades; ?></h3>
        <p>Chưa nhập điểm</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-alt"></i>
        <h3><?php echo $currentSemester ? $currentSemester['semester_code'] : 'N/A'; ?></h3>
        <p>Kỳ học hiện tại</p>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Lớp đang giảng dạy -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chalkboard"></i> Lớp đang giảng dạy</h3>
            <a href="quan_ly_lop.php" class="view-all">Xem tất cả →</a>
        </div>
        <div class="card-content">
            <?php if (count($classes) > 0): ?>
                <ul>
                    <?php foreach ($classes as $class): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($class['course_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($class['class_name']); ?> - 
                                    Phòng: <?php echo htmlspecialchars($class['room']); ?>
                                </small>
                            </div>
                            <span class="time"><?php echo $class['current_students']; ?> SV</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Bạn chưa được phân công giảng dạy lớp nào.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Thông báo đã gửi -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bell"></i> Thông báo đã gửi</h3>
            <a href="gui_thong_bao.php" class="view-all">Gửi mới →</a>
        </div>
        <div class="card-content">
            <?php if (count($notifications) > 0): ?>
                <ul>
                    <?php foreach ($notifications as $notif): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                                <br>
                                <small style="color: var(--text-secondary);">
                                    Gửi đến: <?php 
                                        if ($notif['role_target'] === 'all') echo 'Tất cả';
                                        elseif ($notif['role_target'] === 'student') echo 'Sinh viên';
                                        else echo 'Cá nhân';
                                    ?>
                                </small>
                            </div>
                            <span class="due-date">
                                <?php 
                                    $date = new DateTime($notif['created_at']);
                                    echo $date->format('d/m');
                                ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Chưa có thông báo nào được gửi.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Thông tin cá nhân -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user"></i> Thông tin cá nhân</h3>
        </div>
        <div class="card-content">
            <ul>
                <li>
                    <span style="color: var(--text-secondary);">Email:</span>
                    <strong><?php echo htmlspecialchars($teacherInfo['email']); ?></strong>
                </li>
                <li>
                    <span style="color: var(--text-secondary);">Số điện thoại:</span>
                    <strong><?php echo htmlspecialchars($teacherInfo['phone'] ?? 'Chưa cập nhật'); ?></strong>
                </li>
                <li>
                    <span style="color: var(--text-secondary);">Chức vụ:</span>
                    <strong><?php echo htmlspecialchars($teacherInfo['position']); ?></strong>
                </li>
                <li>
                    <span style="color: var(--text-secondary);">Chuyên môn:</span>
                    <strong><?php echo htmlspecialchars($teacherInfo['specialization']); ?></strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
