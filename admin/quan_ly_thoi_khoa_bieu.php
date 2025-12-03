<?php
/**
 * Quản lý thời khóa biểu - Admin
 * Xem tổng quan thời khóa biểu của tất cả lớp học
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['admin']);

$pageTitle = "Quản lý thời khóa biểu - Quản trị viên";
$currentUser = getCurrentUser();

$error = '';
$success = '';

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

// Xử lý cập nhật lịch học
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $classId = intval($_POST['class_id'] ?? 0);
    $schedule = trim($_POST['schedule'] ?? '');
    $room = trim($_POST['room'] ?? '');
    
    if ($classId > 0) {
        $schedule = escape_string($conn, $schedule);
        $room = escape_string($conn, $room);
        
        $sqlUpdate = "UPDATE class_courses SET schedule = '$schedule', room = '$room' WHERE id = $classId";
        
        if (executeQuery($conn, $sqlUpdate)) {
            $success = 'Cập nhật lịch học thành công!';
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật lịch học.';
        }
    }
}

// Lấy danh sách lớp học trong kỳ
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

if ($currentSemester) {
    $sqlClasses = "SELECT cc.*, c.course_code, c.course_name, c.credits,
                   u.full_name as teacher_name,
                   (SELECT COUNT(*) FROM enrollments WHERE class_course_id = cc.id) as enrolled_count
                   FROM class_courses cc
                   INNER JOIN courses c ON cc.course_id = c.id
                   INNER JOIN teachers t ON cc.teacher_id = t.id
                   INNER JOIN users u ON t.user_id = u.id
                   WHERE cc.semester_id = $selectedSemesterId
                   ORDER BY c.course_code, cc.class_name";
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
                        'teacher_name' => $class['teacher_name'],
                        'credits' => $class['credits'],
                        'enrolled_count' => $class['enrolled_count'],
                        'max_students' => $class['max_students'],
                        'id' => $class['id']
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
        <h1><i class="fas fa-calendar-week"></i> Quản lý thời khóa biểu</h1>
    </div>
</div>

<!-- Messages -->
<?php if ($error): ?>
    <div style="background-color: #fee; color: #c33; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fcc;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

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
        <a href="quan_ly_hoc_phan.php" style="margin-left: auto; padding: 8px 16px; background-color: var(--primary-color); color: white; border-radius: 6px; text-decoration: none; font-size: 14px;">
            <i class="fas fa-edit"></i> Sửa lịch học
        </a>
    </form>
</div>

<?php if ($currentSemester): ?>
    <!-- Thống kê -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="stat-icon" style="background-color: rgba(255,255,255,0.2);">
                <i class="fas fa-chalkboard" style="color: white;"></i>
            </div>
            <div class="stat-details">
                <p class="stat-title" style="color: rgba(255,255,255,0.9);">Tổng lớp học</p>
                <h3 class="stat-value" style="color: white;"><?php echo count($classes); ?></h3>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-icon" style="background-color: rgba(255,255,255,0.2);">
                <i class="fas fa-calendar-check" style="color: white;"></i>
            </div>
            <div class="stat-details">
                <p class="stat-title" style="color: rgba(255,255,255,0.9);">Đã xếp lịch</p>
                <h3 class="stat-value" style="color: white;">
                    <?php 
                        $scheduled = array_filter($classes, function($c) { return !empty($c['schedule']); });
                        echo count($scheduled); 
                    ?>
                </h3>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="stat-icon" style="background-color: rgba(255,255,255,0.2);">
                <i class="fas fa-calendar-times" style="color: white;"></i>
            </div>
            <div class="stat-details">
                <p class="stat-title" style="color: rgba(255,255,255,0.9);">Chưa xếp lịch</p>
                <h3 class="stat-value" style="color: white;">
                    <?php echo count($classes) - count($scheduled); ?>
                </h3>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-icon" style="background-color: rgba(255,255,255,0.2);">
                <i class="fas fa-door-open" style="color: white;"></i>
            </div>
            <div class="stat-details">
                <p class="stat-title" style="color: rgba(255,255,255,0.9);">Phòng học sử dụng</p>
                <h3 class="stat-value" style="color: white;">
                    <?php 
                        $rooms = array_unique(array_filter(array_column($classes, 'room')));
                        echo count($rooms); 
                    ?>
                </h3>
            </div>
        </div>
    </div>

    <!-- Thời khóa biểu dạng bảng -->
    <div class="card" style="margin-bottom: 24px;">
        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
            <i class="fas fa-table"></i> Thời khóa biểu tuần
        </h2>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                <thead>
                    <tr style="background-color: var(--primary-color); color: white;">
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd; width: 100px;">Thứ</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Lịch học</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weekSchedule as $day => $dayClasses): ?>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa; font-weight: 600; vertical-align: top;">
                                <?php echo $day; ?>
                            </td>
                            <td style="padding: 12px; border: 1px solid #ddd;">
                                <?php if (count($dayClasses) > 0): ?>
                                    <div style="display: grid; gap: 8px;">
                                        <?php foreach ($dayClasses as $class): ?>
                                            <div style="background-color: #f0f9ff; padding: 12px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                                    <div>
                                                        <span style="background-color: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                            <?php echo htmlspecialchars($class['time']); ?>
                                                        </span>
                                                        <span style="background-color: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px;">
                                                            <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room'] ?: 'Chưa xếp'); ?>
                                                        </span>
                                                        <span style="background-color: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px;">
                                                            <?php echo $class['credits']; ?> TC
                                                        </span>
                                                    </div>
                                                    <span style="background-color: #6366f1; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                        <?php echo $class['enrolled_count']; ?>/<?php echo $class['max_students']; ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <p style="font-weight: 600; font-size: 15px; margin: 0 0 4px 0; color: #1e40af;">
                                                        <?php echo htmlspecialchars($class['course_name']); ?>
                                                    </p>
                                                    <p style="margin: 0; color: var(--text-secondary); font-size: 13px;">
                                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($class['course_code']); ?> - 
                                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                                    </p>
                                                    <p style="margin: 4px 0 0 0; color: var(--text-secondary); font-size: 13px;">
                                                        <i class="fas fa-chalkboard-teacher"></i> GV: <?php echo htmlspecialchars($class['teacher_name']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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

    <!-- Danh sách tất cả lớp học -->
    <div class="card">
        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
            <i class="fas fa-list"></i> Danh sách tất cả lớp học
        </h2>
        
        <?php if (count($classes) > 0): ?>
            <div class="table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">STT</th>
                            <th>Mã HP</th>
                            <th>Tên học phần</th>
                            <th>Lớp</th>
                            <th>Giảng viên</th>
                            <th>TC</th>
                            <th>Lịch học</th>
                            <th>Phòng</th>
                            <th>SL</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; foreach ($classes as $class): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><strong><?php echo htmlspecialchars($class['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                <td><?php echo $class['credits']; ?></td>
                                <td>
                                    <small style="color: <?php echo empty($class['schedule']) ? '#ef4444' : 'var(--text-secondary)'; ?>;">
                                        <?php echo empty($class['schedule']) ? 'Chưa xếp' : htmlspecialchars($class['schedule']); ?>
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
                                <td>
                                    <button onclick="editSchedule(<?php echo $class['id']; ?>, '<?php echo addslashes($class['schedule']); ?>', '<?php echo addslashes($class['room']); ?>', '<?php echo addslashes($class['course_name']); ?>', '<?php echo addslashes($class['class_name']); ?>')" 
                                            style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 16px;" 
                                            title="Sửa lịch">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
                Chưa có lớp học nào trong kỳ này.
            </p>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="card">
        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
            <i class="fas fa-exclamation-triangle" style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;"></i>
            <h3 style="margin-bottom: 12px;">Không tìm thấy kỳ học</h3>
            <p>Vui lòng tạo kỳ học trước khi quản lý thời khóa biểu.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Modal sửa lịch -->
<div id="editScheduleModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; color: var(--primary-color);" id="modalTitle">
                <i class="fas fa-edit"></i> Sửa lịch học
            </h2>
            <span onclick="document.getElementById('editScheduleModal').style.display='none'" 
                  style="font-size: 28px; cursor: pointer; color: #999;">&times;</span>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="class_id" id="edit_class_id">
            
            <div class="form-group">
                <label>Lịch học: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="schedule" id="edit_schedule" placeholder="Thứ 2: 7h30-9h30, Thứ 5: 7h30-9h30" required>
                <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                    Định dạng: Thứ 2: 7h30-9h30, Thứ 5: 13h30-15h30
                </small>
            </div>
            
            <div class="form-group">
                <label>Phòng học: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="room" id="edit_room" placeholder="A101" required>
            </div>
            
            <div style="text-align: right; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('editScheduleModal').style.display='none'" 
                        style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;">
                    Hủy
                </button>
                <button type="submit" name="update_schedule" 
                        style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editSchedule(classId, schedule, room, courseName, className) {
    document.getElementById('edit_class_id').value = classId;
    document.getElementById('edit_schedule').value = schedule;
    document.getElementById('edit_room').value = room;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa lịch: ' + courseName + ' - ' + className;
    document.getElementById('editScheduleModal').style.display = 'block';
}

// Đóng modal khi click bên ngoài
window.onclick = function(event) {
    var modal = document.getElementById('editScheduleModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php include '../includes/footer.php'; ?>

