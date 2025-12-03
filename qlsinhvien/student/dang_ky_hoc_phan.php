<?php
/**
 * Đăng ký học phần
 * Sinh viên đăng ký các học phần còn trống
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['student']);

$pageTitle = "Đăng ký học phần - Sinh viên";
$currentUser = getCurrentUser();
$studentInfo = getStudentInfo($conn, $currentUser['id']);
$studentId = getStudentIdByUserId($conn, $currentUser['id']);

// Lấy kỳ học hiện tại
$sqlCurrentSemester = "SELECT * FROM semesters WHERE is_active = 'yes' LIMIT 1";
$currentSemester = fetchOne($conn, $sqlCurrentSemester);

$error = '';
$success = '';

// Xử lý đăng ký học phần
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $classCourseId = intval($_POST['class_course_id'] ?? 0);
    
    if ($classCourseId > 0 && $currentSemester) {
        // Kiểm tra học phần còn chỗ trống không
        $sqlCheck = "SELECT cc.*, c.credits, c.course_name
                     FROM class_courses cc
                     INNER JOIN courses c ON cc.course_id = c.id
                     WHERE cc.id = $classCourseId";
        $classCourse = fetchOne($conn, $sqlCheck);
        
        if ($classCourse) {
            // Kiểm tra đã đăng ký chưa
            $sqlCheckEnrolled = "SELECT * FROM enrollments 
                                WHERE student_id = $studentId AND class_course_id = $classCourseId";
            $alreadyEnrolled = fetchOne($conn, $sqlCheckEnrolled);
            
            if ($alreadyEnrolled) {
                $error = "Bạn đã đăng ký học phần này rồi.";
            } elseif ($classCourse['current_students'] >= $classCourse['max_students']) {
                $error = "Học phần đã đầy, không thể đăng ký.";
            } else {
                // Kiểm tra tổng tín chỉ (tối đa 24 tín chỉ/kỳ)
                $sqlTotalCredits = "SELECT SUM(c.credits) as total
                                   FROM enrollments e
                                   INNER JOIN class_courses cc ON e.class_course_id = cc.id
                                   INNER JOIN courses c ON cc.course_id = c.id
                                   WHERE e.student_id = $studentId AND cc.semester_id = {$currentSemester['id']}";
                $totalResult = fetchOne($conn, $sqlTotalCredits);
                $currentTotalCredits = $totalResult['total'] ?? 0;
                
                if (($currentTotalCredits + $classCourse['credits']) > 24) {
                    $error = "Vượt quá số tín chỉ cho phép (tối đa 24 tín chỉ/kỳ). Bạn đang có $currentTotalCredits tín chỉ.";
                } else {
                    // Thực hiện đăng ký
                    $sqlInsert = "INSERT INTO enrollments (student_id, class_course_id, status) 
                                 VALUES ($studentId, $classCourseId, 'studying')";
                    if (executeQuery($conn, $sqlInsert)) {
                        // Cập nhật số lượng sinh viên
                        $sqlUpdate = "UPDATE class_courses 
                                     SET current_students = current_students + 1,
                                         status = CASE 
                                             WHEN current_students + 1 >= max_students THEN 'full'
                                             ELSE 'open'
                                         END
                                     WHERE id = $classCourseId";
                        executeQuery($conn, $sqlUpdate);
                        
                        $success = "Đăng ký học phần thành công!";
                    } else {
                        $error = "Có lỗi xảy ra khi đăng ký học phần.";
                    }
                }
            }
        }
    }
}

// Xử lý hủy đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister'])) {
    $enrollmentId = intval($_POST['enrollment_id'] ?? 0);
    
    if ($enrollmentId > 0) {
        // Lấy thông tin enrollment
        $sqlEnrollment = "SELECT * FROM enrollments WHERE id = $enrollmentId AND student_id = $studentId";
        $enrollment = fetchOne($conn, $sqlEnrollment);
        
        if ($enrollment) {
            // Xóa đăng ký
            $sqlDelete = "DELETE FROM enrollments WHERE id = $enrollmentId";
            if (executeQuery($conn, $sqlDelete)) {
                // Giảm số lượng sinh viên
                $sqlUpdate = "UPDATE class_courses 
                             SET current_students = current_students - 1,
                                 status = CASE 
                                     WHEN current_students - 1 < max_students THEN 'open'
                                     ELSE status
                                 END
                             WHERE id = {$enrollment['class_course_id']}";
                executeQuery($conn, $sqlUpdate);
                
                $success = "Hủy đăng ký học phần thành công!";
            } else {
                $error = "Có lỗi xảy ra khi hủy đăng ký.";
            }
        }
    }
}

// Lấy danh sách học phần đã đăng ký
$sqlEnrolled = "SELECT e.id as enrollment_id, c.course_code, c.course_name, c.credits, 
                cc.class_name, cc.schedule, cc.room, cc.status,
                CONCAT(u.full_name) as teacher_name
                FROM enrollments e
                INNER JOIN class_courses cc ON e.class_course_id = cc.id
                INNER JOIN courses c ON cc.course_id = c.id
                INNER JOIN teachers t ON cc.teacher_id = t.id
                INNER JOIN users u ON t.user_id = u.id
                WHERE e.student_id = $studentId AND cc.semester_id = {$currentSemester['id']}
                ORDER BY c.course_name";
$enrolledCourses = fetchAll($conn, $sqlEnrolled);

// Lấy danh sách học phần có thể đăng ký (chưa đăng ký)
$sqlAvailable = "SELECT cc.id, c.course_code, c.course_name, c.credits, 
                 cc.class_name, cc.schedule, cc.room, cc.current_students, cc.max_students, cc.status,
                 CONCAT(u.full_name) as teacher_name
                 FROM class_courses cc
                 INNER JOIN courses c ON cc.course_id = c.id
                 INNER JOIN teachers t ON cc.teacher_id = t.id
                 INNER JOIN users u ON t.user_id = u.id
                 WHERE cc.semester_id = {$currentSemester['id']}
                 AND cc.id NOT IN (
                     SELECT class_course_id FROM enrollments WHERE student_id = $studentId
                 )
                 ORDER BY c.course_name";
$availableCourses = fetchAll($conn, $sqlAvailable);

// Tính tổng tín chỉ đã đăng ký
$sqlTotalCredits = "SELECT SUM(c.credits) as total
                   FROM enrollments e
                   INNER JOIN class_courses cc ON e.class_course_id = cc.id
                   INNER JOIN courses c ON cc.course_id = c.id
                   WHERE e.student_id = $studentId AND cc.semester_id = {$currentSemester['id']}";
$totalResult = fetchOne($conn, $sqlTotalCredits);
$totalCreditsRegistered = $totalResult['total'] ?? 0;

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Đăng ký học phần</h1>
        <p style="color: var(--text-secondary); margin-top: 8px;">
            Kỳ học: <strong><?php echo htmlspecialchars($currentSemester['semester_name']); ?></strong>
        </p>
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

<!-- Thống kê -->
<div style="background-color: #eef2ff; padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid var(--primary-color);">
    <div style="display: flex; gap: 48px;">
        <div>
            <span style="color: var(--text-secondary); font-size: 14px;">Số học phần đã đăng ký:</span>
            <strong style="font-size: 20px; color: var(--primary-color); margin-left: 8px;"><?php echo count($enrolledCourses); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-secondary); font-size: 14px;">Tổng tín chỉ:</span>
            <strong style="font-size: 20px; color: var(--primary-color); margin-left: 8px;"><?php echo $totalCreditsRegistered; ?> / 24</strong>
        </div>
    </div>
</div>

<!-- Học phần đã đăng ký -->
<div class="card" style="margin-bottom: 32px;">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-check-circle"></i> Học phần đã đăng ký
    </h2>
    
    <?php if (count($enrolledCourses) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Lớp</th>
                        <th>Tín chỉ</th>
                        <th>Giảng viên</th>
                        <th>Lịch học</th>
                        <th>Phòng</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrolledCourses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($course['course_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['class_name']); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['schedule']); ?></td>
                            <td><?php echo htmlspecialchars($course['room']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn hủy đăng ký học phần này?');">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $course['enrollment_id']; ?>">
                                    <button type="submit" name="unregister" style="padding: 6px 12px; background-color: var(--danger-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                        <i class="fas fa-times"></i> Hủy
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            Bạn chưa đăng ký học phần nào trong kỳ này.
        </p>
    <?php endif; ?>
</div>

<!-- Học phần có thể đăng ký -->
<div class="card">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-book"></i> Danh sách học phần có thể đăng ký
    </h2>
    
    <?php if (count($availableCourses) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Lớp</th>
                        <th>Tín chỉ</th>
                        <th>Giảng viên</th>
                        <th>Lịch học</th>
                        <th>Phòng</th>
                        <th>Số lượng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableCourses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($course['course_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['class_name']); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['schedule']); ?></td>
                            <td><?php echo htmlspecialchars($course['room']); ?></td>
                            <td><?php echo $course['current_students']; ?>/<?php echo $course['max_students']; ?></td>
                            <td>
                                <?php if ($course['status'] === 'full'): ?>
                                    <span style="color: #dc2626; font-weight: 500;">Đã đầy</span>
                                <?php elseif ($course['status'] === 'open'): ?>
                                    <span style="color: #16a34a; font-weight: 500;">Còn chỗ</span>
                                <?php else: ?>
                                    <span style="color: #64748b; font-weight: 500;">Đã đóng</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($course['status'] === 'open'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="class_course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="register" style="padding: 6px 12px; background-color: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                            <i class="fas fa-plus"></i> Đăng ký
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled style="padding: 6px 12px; background-color: #ccc; color: #666; border: none; border-radius: 4px; cursor: not-allowed; font-size: 13px;">
                                        Không thể đăng ký
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            Không còn học phần nào có thể đăng ký.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
