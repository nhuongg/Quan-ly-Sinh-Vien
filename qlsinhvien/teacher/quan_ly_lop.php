<?php
/**
 * Quản lý lớp học phần
 * Giảng viên xem danh sách lớp và sinh viên
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['teacher']);

$pageTitle = "Quản lý lớp - Giảng viên";
$currentUser = getCurrentUser();
$teacherId = getTeacherIdByUserId($conn, $currentUser['id']);

// Lấy kỳ học hiện tại
$sqlCurrentSemester = "SELECT * FROM semesters WHERE is_active = 'yes' LIMIT 1";
$currentSemester = fetchOne($conn, $sqlCurrentSemester);

// Lấy tất cả kỳ học
$sqlSemesters = "SELECT * FROM semesters ORDER BY start_date DESC";
$semesters = fetchAll($conn, $sqlSemesters);

// Lấy kỳ học được chọn
$selectedSemesterId = intval($_GET['semester_id'] ?? ($currentSemester['id'] ?? 0));

// Lấy lớp được chọn
$selectedClassId = intval($_GET['class_id'] ?? 0);

// Lấy danh sách lớp của giảng viên
$classes = [];
if ($selectedSemesterId > 0) {
    $sqlClasses = "SELECT cc.*, c.course_code, c.course_name, c.credits
                   FROM class_courses cc
                   INNER JOIN courses c ON cc.course_id = c.id
                   WHERE cc.teacher_id = $teacherId AND cc.semester_id = $selectedSemesterId
                   ORDER BY c.course_name";
    $classes = fetchAll($conn, $sqlClasses);
    
    // Nếu chưa chọn lớp và có lớp thì chọn lớp đầu tiên
    if ($selectedClassId === 0 && count($classes) > 0) {
        $selectedClassId = $classes[0]['id'];
    }
}

// Lấy danh sách sinh viên trong lớp
$students = [];
$classInfo = null;
if ($selectedClassId > 0) {
    // Lấy thông tin lớp
    $sqlClassInfo = "SELECT cc.*, c.course_code, c.course_name, c.credits
                     FROM class_courses cc
                     INNER JOIN courses c ON cc.course_id = c.id
                     WHERE cc.id = $selectedClassId";
    $classInfo = fetchOne($conn, $sqlClassInfo);
    
    // Lấy danh sách sinh viên
    $sqlStudents = "SELECT s.student_code, u.full_name, u.email, u.phone, s.class as student_class,
                    e.id as enrollment_id, e.enrollment_date,
                    g.assignment_score, g.midterm_score, g.final_score, g.total_score, g.letter_grade
                    FROM enrollments e
                    INNER JOIN students s ON e.student_id = s.id
                    INNER JOIN users u ON s.user_id = u.id
                    LEFT JOIN grades g ON e.id = g.enrollment_id
                    WHERE e.class_course_id = $selectedClassId
                    ORDER BY s.student_code";
    $students = fetchAll($conn, $sqlStudents);
}

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Quản lý lớp học phần</h1>
    </div>
</div>

<!-- Filter Section -->
<div class="card" style="margin-bottom: 24px;">
    <div class="filter-section">
        <div class="filter-group">
            <label for="semester">Kỳ học:</label>
            <select id="semester" name="semester" onchange="window.location.href='quan_ly_lop.php?semester_id=' + this.value">
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem['id']; ?>" <?php echo ($sem['id'] == $selectedSemesterId) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['semester_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if (count($classes) > 0): ?>
            <div class="filter-group">
                <label for="class">Lớp học phần:</label>
                <select id="class" name="class" onchange="window.location.href='quan_ly_lop.php?semester_id=<?php echo $selectedSemesterId; ?>&class_id=' + this.value">
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?php echo $cls['id']; ?>" <?php echo ($cls['id'] == $selectedClassId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cls['course_name'] . ' - ' . $cls['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($classInfo): ?>
    <!-- Thông tin lớp -->
    <div class="card" style="margin-bottom: 24px; background-color: #f8fafc;">
        <h2 style="font-size: 20px; margin-bottom: 16px; color: var(--primary-color);">
            <i class="fas fa-info-circle"></i> Thông tin lớp học
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div>
                <span style="color: var(--text-secondary); font-size: 14px;">Mã học phần:</span>
                <br>
                <strong><?php echo htmlspecialchars($classInfo['course_code']); ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 14px;">Tên học phần:</span>
                <br>
                <strong><?php echo htmlspecialchars($classInfo['course_name']); ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 14px;">Lớp:</span>
                <br>
                <strong><?php echo htmlspecialchars($classInfo['class_name']); ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 14px;">Số tín chỉ:</span>
                <br>
                <strong><?php echo $classInfo['credits']; ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 14px;">Phòng:</span>
                <br>
                <strong><?php echo htmlspecialchars($classInfo['room']); ?></strong>
            </div>
            <div>
                <span style="color: var(--text-secondary); font-size: 14px;">Số lượng:</span>
                <br>
                <strong><?php echo $classInfo['current_students']; ?>/<?php echo $classInfo['max_students']; ?></strong>
            </div>
        </div>
        <div style="margin-top: 16px;">
            <span style="color: var(--text-secondary); font-size: 14px;">Lịch học:</span>
            <br>
            <strong><?php echo htmlspecialchars($classInfo['schedule']); ?></strong>
        </div>
    </div>
    
    <!-- Danh sách sinh viên -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 20px; color: var(--primary-color);">
                <i class="fas fa-users"></i> Danh sách sinh viên (<?php echo count($students); ?>)
            </h2>
            <a href="nhap_diem.php?class_id=<?php echo $selectedClassId; ?>" class="btn-outline-primary">
                <i class="fas fa-edit"></i> Nhập điểm
            </a>
        </div>
        
        <?php if (count($students) > 0): ?>
            <div class="table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã SV</th>
                            <th>Họ và tên</th>
                            <th>Lớp</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Điểm BT</th>
                            <th>Điểm GK</th>
                            <th>Điểm CK</th>
                            <th>Điểm TK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['student_class']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                                <td>
                                    <?php echo $student['assignment_score'] !== null ? number_format($student['assignment_score'], 1) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $student['midterm_score'] !== null ? number_format($student['midterm_score'], 1) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $student['final_score'] !== null ? number_format($student['final_score'], 1) : '-'; ?>
                                </td>
                                <td>
                                    <?php 
                                        if ($student['total_score'] !== null) {
                                            $color = $student['total_score'] >= 5.0 ? '#16a34a' : '#dc2626';
                                            echo "<strong style='color: $color;'>" . number_format($student['total_score'], 2) . "</strong>";
                                        } else {
                                            echo '-';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Nút xuất Excel -->
            <div style="margin-top: 24px; text-align: right;">
                <button onclick="window.print()" style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; margin-right: 8px;">
                    <i class="fas fa-print"></i> In danh sách
                </button>
            </div>
        <?php else: ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
                Chưa có sinh viên nào đăng ký lớp này.
            </p>
        <?php endif; ?>
    </div>
    
<?php elseif (count($classes) === 0): ?>
    <div class="card">
        <p style="color: var(--text-secondary); text-align: center; padding: 60px 20px;">
            <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
            Bạn chưa được phân công giảng dạy lớp nào trong kỳ này.
        </p>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
