<?php
/**
 * Dashboard Sinh viên
 * Trang chủ cho sinh viên sau khi đăng nhập
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

// Kiểm tra quyền truy cập
checkAccess(['student']);

$pageTitle = "Trang chủ - Sinh viên";
$currentUser = getCurrentUser();
$studentInfo = getStudentInfo($conn, $currentUser['id']);
$studentId = getStudentIdByUserId($conn, $currentUser['id']);

// Lấy thông tin kỳ học hiện tại
$sqlCurrentSemester = "SELECT * FROM semesters WHERE is_active = 'yes' LIMIT 1";
$currentSemester = fetchOne($conn, $sqlCurrentSemester);

// Đếm số học phần đã đăng ký trong kỳ hiện tại
$registeredCount = 0;
$totalCredits = 0;
if ($currentSemester) {
    $semesterId = $currentSemester['id'];
    $sqlCount = "SELECT COUNT(DISTINCT e.id) as count, SUM(c.credits) as credits
                 FROM enrollments e
                 INNER JOIN class_courses cc ON e.class_course_id = cc.id
                 INNER JOIN courses c ON cc.course_id = c.id
                 WHERE e.student_id = $studentId AND cc.semester_id = $semesterId";
    $countResult = fetchOne($conn, $sqlCount);
    $registeredCount = $countResult['count'] ?? 0;
    $totalCredits = $countResult['credits'] ?? 0;
}

// Lấy danh sách học phần đang học
$sqlCurrentCourses = "SELECT c.course_name, c.credits, cc.class_name, cc.schedule, cc.room,
                      CONCAT(u.full_name) as teacher_name
                      FROM enrollments e
                      INNER JOIN class_courses cc ON e.class_course_id = cc.id
                      INNER JOIN courses c ON cc.course_id = c.id
                      INNER JOIN teachers t ON cc.teacher_id = t.id
                      INNER JOIN users u ON t.user_id = u.id
                      WHERE e.student_id = $studentId AND e.status = 'studying'
                      ORDER BY c.course_name
                      LIMIT 5";
$currentCourses = fetchAll($conn, $sqlCurrentCourses);

// Lấy thông báo mới nhất
$sqlNotifications = "SELECT n.*, u.full_name as sender_name
                     FROM notifications n
                     INNER JOIN users u ON n.sender_id = u.id
                     WHERE (n.receiver_id = {$currentUser['id']} OR n.role_target IN ('all', 'student'))
                     ORDER BY n.created_at DESC
                     LIMIT 5";
$notifications = fetchAll($conn, $sqlNotifications);

// Lấy GPA và tổng tín chỉ
$gpa = $studentInfo['gpa'] ?? 0;
$totalCreditsCompleted = $studentInfo['total_credits'] ?? 0;

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
        <h1>Xin chào, <?php echo htmlspecialchars($studentInfo['full_name']); ?>!</h1>
        <p>Mã sinh viên: <?php echo htmlspecialchars($studentInfo['student_code']); ?> | Lớp: <?php echo htmlspecialchars($studentInfo['class']); ?></p>
    </div>
    <div class="header-actions">
        <div class="user-profile">
            <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
            <small>Sinh viên</small>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="stat-card">
        <i class="fas fa-graduation-cap"></i>
        <h3><?php echo $gpa; ?></h3>
        <p>GPA</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-book"></i>
        <h3><?php echo $registeredCount; ?></h3>
        <p>Học phần kỳ này</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-certificate"></i>
        <h3><?php echo $totalCreditsCompleted; ?></h3>
        <p>Tín chỉ tích lũy</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <h3><?php echo $totalCredits; ?></h3>
        <p>Tín chỉ kỳ này</p>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Học phần đang học -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-book-open"></i> Học phần đang học</h3>
            <a href="dang_ky_hoc_phan.php" class="view-all">Xem tất cả →</a>
        </div>
        <div class="card-content">
            <?php if (count($currentCourses) > 0): ?>
                <ul>
                    <?php foreach ($currentCourses as $course): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($course['class_name']); ?> - 
                                    GV: <?php echo htmlspecialchars($course['teacher_name']); ?>
                                </small>
                            </div>
                            <span class="time"><?php echo $course['credits']; ?> TC</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Bạn chưa đăng ký học phần nào.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Thông báo -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bell"></i> Thông báo mới</h3>
            <a href="thong_bao.php" class="view-all">Xem tất cả →</a>
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
                                    Từ: <?php echo htmlspecialchars($notif['sender_name']); ?>
                                </small>
                            </div>
                            <span class="due-date">
                                <?php 
                                    $date = new DateTime($notif['created_at']);
                                    echo $date->format('d/m/Y');
                                ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Không có thông báo mới.
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
                    <strong><?php echo htmlspecialchars($studentInfo['email']); ?></strong>
                </li>
                <li>
                    <span style="color: var(--text-secondary);">Số điện thoại:</span>
                    <strong><?php echo htmlspecialchars($studentInfo['phone'] ?? 'Chưa cập nhật'); ?></strong>
                </li>
                <li>
                    <span style="color: var(--text-secondary);">Chuyên ngành:</span>
                    <strong><?php echo htmlspecialchars($studentInfo['major']); ?></strong>
                </li>
                <li>
                    <span style="color: var(--text-secondary);">Khóa học:</span>
                    <strong>K<?php echo htmlspecialchars($studentInfo['enrollment_year']); ?></strong>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
