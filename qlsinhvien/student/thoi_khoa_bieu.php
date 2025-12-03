<?php
/**
 * Thời khóa biểu
 * Sinh viên xem lịch học trong tuần
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['student']);

$pageTitle = "Thời khóa biểu - Sinh viên";
$currentUser = getCurrentUser();
$studentId = getStudentIdByUserId($conn, $currentUser['id']);

// Lấy kỳ học hiện tại
$sqlCurrentSemester = "SELECT * FROM semesters WHERE is_active = 'yes' ORDER BY start_date DESC LIMIT 1";
$currentSemester = fetchOne($conn, $sqlCurrentSemester);

// Lấy danh sách các môn đã đăng ký trong kỳ hiện tại
$schedule = [];
if ($currentSemester) {
    $sqlSchedule = "SELECT cc.schedule, cc.room, cc.class_name,
                    c.course_code, c.course_name, c.credits,
                    u.full_name as teacher_name,
                    s.semester_name
                    FROM enrollments e
                    INNER JOIN class_courses cc ON e.class_course_id = cc.id
                    INNER JOIN courses c ON cc.course_id = c.id
                    INNER JOIN teachers t ON cc.teacher_id = t.id
                    INNER JOIN users u ON t.user_id = u.id
                    INNER JOIN semesters s ON cc.semester_id = s.id
                    WHERE e.student_id = $studentId 
                    AND cc.semester_id = {$currentSemester['id']}
                    AND e.status IN ('registered', 'studying')
                    ORDER BY c.course_code";
    $schedule = fetchAll($conn, $sqlSchedule);
}

// Phân tích lịch học theo thứ và giờ
$weekSchedule = [
    'Thứ 2' => [],
    'Thứ 3' => [],
    'Thứ 4' => [],
    'Thứ 5' => [],
    'Thứ 6' => [],
    'Thứ 7' => [],
    'Chủ nhật' => []
];

foreach ($schedule as $class) {
    if (empty($class['schedule'])) continue;
    
    // Phân tích chuỗi schedule, ví dụ: "Thứ 2: 7h30-9h30, Thứ 5: 7h30-9h30"
    $scheduleItems = explode(',', $class['schedule']);
    
    foreach ($scheduleItems as $item) {
        $item = trim($item);
        // Tách "Thứ 2: 7h30-9h30"
        if (preg_match('/(Thứ \d|Chủ nhật):\s*(.+)/', $item, $matches)) {
            $day = trim($matches[1]);
            $time = trim($matches[2]);
            
            if (isset($weekSchedule[$day])) {
                $weekSchedule[$day][] = [
                    'time' => $time,
                    'course_code' => $class['course_code'],
                    'course_name' => $class['course_name'],
                    'class_name' => $class['class_name'],
                    'room' => $class['room'],
                    'teacher_name' => $class['teacher_name'],
                    'credits' => $class['credits']
                ];
            }
        }
    }
}

// Sắp xếp các tiết học theo thời gian
foreach ($weekSchedule as $day => $classes) {
    usort($weekSchedule[$day], function($a, $b) {
        // Lấy giờ bắt đầu để so sánh
        preg_match('/(\d+)h/', $a['time'], $timeA);
        preg_match('/(\d+)h/', $b['time'], $timeB);
        $hourA = isset($timeA[1]) ? intval($timeA[1]) : 0;
        $hourB = isset($timeB[1]) ? intval($timeB[1]) : 0;
        return $hourA - $hourB;
    });
}

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1><i class="fas fa-calendar-alt"></i> Thời khóa biểu</h1>
    </div>
</div>

<?php if ($currentSemester): ?>
    <!-- Thông tin kỳ học -->
    <div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 20px; margin-bottom: 8px; color: white;">
                    <?php echo htmlspecialchars($currentSemester['semester_name']); ?>
                </h2>
                <p style="opacity: 0.9; margin: 0;">
                    <i class="fas fa-calendar"></i>
                    <?php 
                        echo date('d/m/Y', strtotime($currentSemester['start_date'])); 
                        echo ' - '; 
                        echo date('d/m/Y', strtotime($currentSemester['end_date'])); 
                    ?>
                </p>
            </div>
            <div style="text-align: right;">
                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Tổng số môn</p>
                <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo count($schedule); ?></p>
            </div>
        </div>
    </div>

    <?php if (count($schedule) > 0): ?>
        <!-- Thời khóa biểu dạng bảng -->
        <div class="card" style="margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
                <i class="fas fa-table"></i> Lịch học trong tuần
            </h2>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr style="background-color: var(--primary-color); color: white;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd; width: 100px;">Thứ</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Lịch học</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekSchedule as $day => $classes): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa; font-weight: 600; vertical-align: top;">
                                    <?php echo $day; ?>
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd;">
                                    <?php if (count($classes) > 0): ?>
                                        <?php foreach ($classes as $class): ?>
                                            <div style="background-color: #eef2ff; padding: 12px; margin-bottom: 8px; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                                    <div>
                                                        <span style="background-color: var(--primary-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                            <?php echo htmlspecialchars($class['time']); ?>
                                                        </span>
                                                        <span style="background-color: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px;">
                                                            <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room']); ?>
                                                        </span>
                                                    </div>
                                                    <span style="background-color: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                        <?php echo $class['credits']; ?> TC
                                                    </span>
                                                </div>
                                                <div>
                                                    <p style="font-weight: 600; font-size: 16px; margin: 0 0 4px 0; color: var(--primary-color);">
                                                        <?php echo htmlspecialchars($class['course_name']); ?>
                                                    </p>
                                                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px;">
                                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($class['course_code']); ?> - 
                                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                                    </p>
                                                    <p style="margin: 4px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                                                        <i class="fas fa-chalkboard-teacher"></i> GV: <?php echo htmlspecialchars($class['teacher_name']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="color: #999; margin: 0; text-align: center; padding: 20px;">
                                            <i class="fas fa-calendar-times"></i> Không có lịch học
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danh sách môn học -->
        <div class="card">
            <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
                <i class="fas fa-list"></i> Danh sách môn học
            </h2>
            
            <div class="table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">STT</th>
                            <th>Mã HP</th>
                            <th>Tên học phần</th>
                            <th>Lớp</th>
                            <th>TC</th>
                            <th>Giảng viên</th>
                            <th>Lịch học</th>
                            <th>Phòng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; foreach ($schedule as $class): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><strong><?php echo htmlspecialchars($class['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo $class['credits']; ?></td>
                                <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                <td>
                                    <small style="color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($class['schedule'] ?? 'Chưa xếp'); ?>
                                    </small>
                                </td>
                                <td>
                                    <span style="background-color: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars($class['room']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <div class="card">
            <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                <i class="fas fa-calendar-times" style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;"></i>
                <h3 style="margin-bottom: 12px;">Chưa có môn học nào</h3>
                <p>Bạn chưa đăng ký môn học nào trong kỳ này.</p>
                <a href="dang_ky_hoc_phan.php" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background-color: var(--primary-color); color: white; border-radius: 6px; text-decoration: none;">
                    <i class="fas fa-plus-circle"></i> Đăng ký học phần
                </a>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card">
        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
            <i class="fas fa-exclamation-triangle" style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;"></i>
            <h3 style="margin-bottom: 12px;">Chưa có kỳ học nào đang hoạt động</h3>
            <p>Hiện tại không có kỳ học nào đang diễn ra. Vui lòng liên hệ phòng đào tạo.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Ghi chú -->
<div class="card" style="background-color: #fffbeb; border-left: 4px solid #f59e0b;">
    <h3 style="margin-bottom: 12px; color: #f59e0b;">
        <i class="fas fa-info-circle"></i> Ghi chú
    </h3>
    <ul style="margin: 0; padding-left: 20px; line-height: 1.8; color: var(--text-secondary);">
        <li>Vui lòng kiểm tra lịch học thường xuyên để không bỏ lỡ buổi học</li>
        <li>Nếu có thay đổi lịch học, giảng viên sẽ thông báo trên hệ thống</li>
        <li>Mọi thắc mắc vui lòng liên hệ giảng viên hoặc phòng đào tạo</li>
    </ul>
</div>

<style>
@media print {
    .main-header, .card:last-child, nav, .sidebar {
        display: none !important;
    }
    
    body {
        font-size: 12pt;
    }
    
    .card {
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

