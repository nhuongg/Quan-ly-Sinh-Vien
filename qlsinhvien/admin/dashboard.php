<?php
/**
 * Dashboard Quản trị viên
 * Trang chủ cho quản trị viên sau khi đăng nhập
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['admin']);

$pageTitle = "Trang chủ - Quản trị viên";
$currentUser = getCurrentUser();

// Thống kê tổng quan
$sqlStats = "SELECT 
             (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
             (SELECT COUNT(*) FROM users WHERE role = 'teacher') as total_teachers,
             (SELECT COUNT(*) FROM courses) as total_courses,
             (SELECT COUNT(*) FROM class_courses WHERE semester_id IN (SELECT id FROM semesters WHERE is_active = 'yes')) as active_classes";
$stats = fetchOne($conn, $sqlStats);

// Đảm bảo $stats không null
if (!$stats) {
    $stats = array(
        'total_students' => 0,
        'total_teachers' => 0,
        'total_courses' => 0,
        'active_classes' => 0
    );
}

// Lấy kỳ học hiện tại
$sqlCurrentSemester = "SELECT * FROM semesters WHERE is_active = 'yes' LIMIT 1";
$currentSemester = fetchOne($conn, $sqlCurrentSemester);

// Thống kê đăng ký học phần trong kỳ hiện tại
$totalEnrollments = 0;
if ($currentSemester) {
    $sqlEnrollments = "SELECT COUNT(DISTINCT e.id) as count
                       FROM enrollments e
                       INNER JOIN class_courses cc ON e.class_course_id = cc.id
                       WHERE cc.semester_id = {$currentSemester['id']}";
    $enrollResult = fetchOne($conn, $sqlEnrollments);
    $totalEnrollments = $enrollResult ? $enrollResult['count'] : 0;
}

// Người dùng mới nhất
$sqlNewUsers = "SELECT u.*, 
                CASE 
                    WHEN u.role = 'student' THEN s.student_code
                    WHEN u.role = 'teacher' THEN t.teacher_code
                    ELSE 'N/A'
                END as code
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                LEFT JOIN teachers t ON u.id = t.user_id
                ORDER BY u.created_at DESC
                LIMIT 5";
$newUsers = fetchAll($conn, $sqlNewUsers);

// Lớp học có nhiều sinh viên nhất
$sqlTopClasses = "SELECT cc.class_name, c.course_name, cc.current_students, cc.max_students,
                  CONCAT(u.full_name) as teacher_name
                  FROM class_courses cc
                  INNER JOIN courses c ON cc.course_id = c.id
                  INNER JOIN teachers t ON cc.teacher_id = t.id
                  INNER JOIN users u ON t.user_id = u.id
                  WHERE cc.semester_id IN (SELECT id FROM semesters WHERE is_active = 'yes')
                  ORDER BY cc.current_students DESC
                  LIMIT 5";
$topClasses = fetchAll($conn, $sqlTopClasses);

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
        <h1>Xin chào, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h1>
        <p>Quản trị viên hệ thống</p>
    </div>
    <div class="header-actions">
        <div class="user-profile">
            <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
            <small>Quản trị viên</small>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="stat-card">
        <i class="fas fa-user-graduate"></i>
        <h3><?php echo $stats['total_students']; ?></h3>
        <p>Tổng sinh viên</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-chalkboard-teacher"></i>
        <h3><?php echo $stats['total_teachers']; ?></h3>
        <p>Tổng giảng viên</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-book"></i>
        <h3><?php echo $stats['total_courses']; ?></h3>
        <p>Tổng học phần</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-chalkboard"></i>
        <h3><?php echo $stats['active_classes']; ?></h3>
        <p>Lớp đang mở</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-clipboard-list"></i>
        <h3><?php echo $totalEnrollments; ?></h3>
        <p>Lượt đăng ký</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-alt"></i>
        <h3><?php echo $currentSemester ? $currentSemester['semester_code'] : 'N/A'; ?></h3>
        <p>Kỳ học hiện tại</p>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Người dùng mới -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> Người dùng mới nhất</h3>
            <a href="quan_ly_nguoi_dung.php" class="view-all">Xem tất cả →</a>
        </div>
        <div class="card-content">
            <?php if (count($newUsers) > 0): ?>
                <ul>
                    <?php foreach ($newUsers as $user): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-secondary);">
                                    <?php 
                                        $roleText = '';
                                        if ($user['role'] === 'student') $roleText = 'Sinh viên';
                                        elseif ($user['role'] === 'teacher') $roleText = 'Giảng viên';
                                        else $roleText = 'Quản trị viên';
                                        echo $roleText . ' - ' . htmlspecialchars($user['code']);
                                    ?>
                                </small>
                            </div>
                            <span class="time">
                                <?php 
                                    $date = new DateTime($user['created_at']);
                                    echo $date->format('d/m');
                                ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Chưa có người dùng mới.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Lớp học hot nhất -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-fire"></i> Lớp có nhiều SV nhất</h3>
            <a href="thong_ke.php" class="view-all">Xem thống kê →</a>
        </div>
        <div class="card-content">
            <?php if (count($topClasses) > 0): ?>
                <ul>
                    <?php foreach ($topClasses as $class): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($class['course_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($class['class_name']); ?> - 
                                    GV: <?php echo htmlspecialchars($class['teacher_name']); ?>
                                </small>
                            </div>
                            <span class="time">
                                <?php echo $class['current_students']; ?>/<?php echo $class['max_students']; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Chưa có dữ liệu.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Truy cập nhanh -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Truy cập nhanh</h3>
        </div>
        <div class="card-content">
            <ul>
                <li style="border-bottom: 1px solid #f1f5f9; padding: 12px 0;">
                    <a href="quan_ly_nguoi_dung.php" style="text-decoration: none; color: var(--text-primary); display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-users" style="color: var(--primary-color); margin-right: 8px;"></i> Quản lý người dùng</span>
                        <i class="fas fa-chevron-right" style="color: var(--text-secondary);"></i>
                    </a>
                </li>
                <li style="border-bottom: 1px solid #f1f5f9; padding: 12px 0;">
                    <a href="quan_ly_hoc_phan.php" style="text-decoration: none; color: var(--text-primary); display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-book" style="color: var(--primary-color); margin-right: 8px;"></i> Quản lý học phần</span>
                        <i class="fas fa-chevron-right" style="color: var(--text-secondary);"></i>
                    </a>
                </li>
                <li style="border-bottom: 1px solid #f1f5f9; padding: 12px 0;">
                    <a href="thong_ke.php" style="text-decoration: none; color: var(--text-primary); display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-chart-line" style="color: var(--primary-color); margin-right: 8px;"></i> Thống kê báo cáo</span>
                        <i class="fas fa-chevron-right" style="color: var(--text-secondary);"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
