<?php
/**
 * Thời khóa biểu - Giáo viên
 * Giáo viên xem lịch giảng dạy của mình
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['teacher']);

$pageTitle = "Thời khóa biểu - Giáo viên";
$currentUser = getCurrentUser();
$teacherId = getTeacherIdByUserId($conn, $currentUser['id']);

// Lấy kỳ học được chọn (mặc định là kỳ hiện tại)
$selectedSemesterId = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;

// Lấy danh sách kỳ học
$sqlSemesters = "SELECT * FROM semesters ORDER BY start_date DESC";
$semesters = fetchAll($conn, $sqlSemesters);

// Nếu chưa chọn kỳ, lấy kỳ hiện tại
if ($selectedSemesterId == 0 && count($semesters) > 0) {
    foreach ($semesters as $sem) {
        if ($sem['is_active'] === 'yes') {
            $selectedSemesterId = $sem['id'];
            break;
        }
    }
    if ($selectedSemesterId == 0) {
        $selectedSemesterId = $semesters[0]['id'];
    }
}

$currentSemester = null;
if ($selectedSemesterId > 0) {
    $sqlCurrentSem = "SELECT * FROM semesters WHERE id = $selectedSemesterId";
    $currentSemester = fetchOne($conn, $sqlCurrentSem);
}

// Lấy danh sách lớp giảng dạy
$classes = [];
$weekSchedule = [
    'Thứ 2' => [],
    'Thứ 3' => [],
    'Thứ 4' => [],
    'Thứ 5' => [],
    'Thứ 6' => [],
    'Thứ 7' => [],
    'Chủ nhật' => []
];

if ($currentSemester && $teacherId) {
    $sqlClasses = "SELECT cc.*, c.course_code, c.course_name, c.credits,
                   s.semester_name,
                   (SELECT COUNT(*) FROM enrollments WHERE class_course_id = cc.id) as enrolled_count
                   FROM class_courses cc
                   INNER JOIN courses c ON cc.course_id = c.id
                   INNER JOIN semesters s ON cc.semester_id = s.id
                   WHERE cc.teacher_id = $teacherId 
                   AND cc.semester_id = $selectedSemesterId
                   ORDER BY c.course_code";
    $classes = fetchAll($conn, $sqlClasses);
    
    // Phân tích lịch học theo thứ
    foreach ($classes as $class) {
        if (empty($class['schedule'])) continue;
        
        $scheduleItems = explode(',', $class['schedule']);
        
        foreach ($scheduleItems as $item) {
            $item = trim($item);
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
                        'credits' => $class['credits'],
                        'enrolled_count' => $class['enrolled_count'],
                        'max_students' => $class['max_students']
                    ];
                }
            }
        }
    }
    
    // Sắp xếp theo thời gian
    foreach ($weekSchedule as $day => $dayClasses) {
        usort($weekSchedule[$day], function($a, $b) {
            preg_match('/(\d+)h/', $a['time'], $timeA);
            preg_match('/(\d+)h/', $b['time'], $timeB);
            $hourA = isset($timeA[1]) ? intval($timeA[1]) : 0;
            $hourB = isset($timeB[1]) ? intval($timeB[1]) : 0;
            return $hourA - $hourB;
        });
    }
}

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1><i class="fas fa-calendar-alt"></i> Thời khóa biểu giảng dạy</h1>
    </div>
</div>

<!-- Chọn kỳ học -->
<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="" style="display: flex; gap: 16px; align-items: center;">
        <label style="font-weight: 600; color: var(--primary-color);">
            <i class="fas fa-calendar"></i> Chọn kỳ học:
        </label>
        <select name="semester_id" onchange="this.form.submit()" style="padding: 8px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
            <?php foreach ($semesters as $semester): ?>
                <option value="<?php echo $semester['id']; ?>" <?php echo $semester['id'] == $selectedSemesterId ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($semester['semester_name']); ?>
                    <?php echo $semester['is_active'] === 'yes' ? ' (Đang hoạt động)' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
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
                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Tổng số lớp</p>
                <p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo count($classes); ?></p>
            </div>
        </div>
    </div>

    <?php if (count($classes) > 0): ?>
        <!-- Thời khóa biểu tuần -->
        <div class="card" style="margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
                <i class="fas fa-table"></i> Lịch giảng dạy trong tuần
            </h2>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr style="background-color: var(--primary-color); color: white;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd; width: 100px;">Thứ</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Lịch giảng dạy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekSchedule as $day => $dayClasses): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa; font-weight: 600; vertical-align: top;">
                                    <?php echo $day; ?>
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd;">
                                    <?php if (count($dayClasses) > 0): ?>
                                        <?php foreach ($dayClasses as $class): ?>
                                            <div style="background-color: #ecfdf5; padding: 12px; margin-bottom: 8px; border-radius: 8px; border-left: 4px solid #10b981;">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                                    <div>
                                                        <span style="background-color: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                            <?php echo htmlspecialchars($class['time']); ?>
                                                        </span>
                                                        <span style="background-color: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px;">
                                                            <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room']); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span style="background-color: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                            <?php echo $class['credits']; ?> TC
                                                        </span>
                                                        <span style="background-color: #6366f1; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">
                                                            <?php echo $class['enrolled_count']; ?>/<?php echo $class['max_students']; ?> SV
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p style="font-weight: 600; font-size: 16px; margin: 0 0 4px 0; color: #059669;">
                                                        <?php echo htmlspecialchars($class['course_name']); ?>
                                                    </p>
                                                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px;">
                                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($class['course_code']); ?> - 
                                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="color: #999; margin: 0; text-align: center; padding: 20px;">
                                            <i class="fas fa-calendar-times"></i> Không có lịch dạy
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danh sách lớp giảng dạy -->
        <div class="card">
            <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
                <i class="fas fa-list"></i> Danh sách lớp giảng dạy
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
                            <th>Lịch học</th>
                            <th>Phòng</th>
                            <th>SL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; foreach ($classes as $class): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><strong><?php echo htmlspecialchars($class['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo $class['credits']; ?></td>
                                <td>
                                    <small style="color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($class['schedule'] ?? 'Chưa xếp'); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($class['room'])): ?>
                                        <span style="background-color: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo htmlspecialchars($class['room']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $class['enrolled_count']; ?>/<?php echo $class['max_students']; ?></td>
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
                <h3 style="margin-bottom: 12px;">Chưa có lớp giảng dạy</h3>
                <p>Bạn chưa được phân công giảng dạy lớp nào trong kỳ này.</p>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card">
        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
            <i class="fas fa-exclamation-triangle" style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;"></i>
            <h3 style="margin-bottom: 12px;">Chưa có kỳ học nào đang hoạt động</h3>
            <p>Hiện tại không có kỳ học nào đang diễn ra.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Ghi chú -->
<div class="card" style="background-color: #ecfdf5; border-left: 4px solid #10b981;">
    <h3 style="margin-bottom: 12px; color: #10b981;">
        <i class="fas fa-info-circle"></i> Ghi chú dành cho giảng viên
    </h3>
    <ul style="margin: 0; padding-left: 20px; line-height: 1.8; color: var(--text-secondary);">
        <li>Vui lòng có mặt đúng giờ và kiểm tra phòng học trước khi lên lớp</li>
        <li>Nếu cần thay đổi lịch học, vui lòng liên hệ phòng đào tạo</li>
        <li>Có thể in thời khóa biểu bằng cách sử dụng chức năng in của trình duyệt</li>
        <li>Kiểm tra danh sách sinh viên đăng ký để chuẩn bị bài giảng phù hợp</li>
    </ul>
</div>

<style>
@media print {
    .main-header, .card:last-child, nav, .sidebar, form {
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

