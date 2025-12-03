<?php
/**
 * Nhập điểm cho sinh viên
 * Giảng viên nhập và cập nhật điểm
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['teacher']);

$pageTitle = "Nhập điểm - Giảng viên";
$currentUser = getCurrentUser();
$teacherId = getTeacherIdByUserId($conn, $currentUser['id']);

$error = '';
$success = '';

// Kiểm tra xem có đang nhập điểm cho sinh viên cụ thể không
$enrollmentId = intval($_GET['enrollment_id'] ?? 0);
$studentInfo = null;

if ($enrollmentId > 0) {
    // Lấy thông tin sinh viên và điểm hiện tại
    $sqlStudent = "SELECT s.student_code, u.full_name, s.class as student_class,
                   e.id as enrollment_id, e.class_course_id,
                   cc.class_name, c.course_code, c.course_name, c.credits, sem.semester_name,
                   g.assignment_score, g.midterm_score, g.final_score, g.total_score, g.letter_grade
                   FROM enrollments e
                   INNER JOIN students s ON e.student_id = s.id
                   INNER JOIN users u ON s.user_id = u.id
                   INNER JOIN class_courses cc ON e.class_course_id = cc.id
                   INNER JOIN courses c ON cc.course_id = c.id
                   INNER JOIN semesters sem ON cc.semester_id = sem.id
                   LEFT JOIN grades g ON e.id = g.enrollment_id
                   WHERE e.id = $enrollmentId AND cc.teacher_id = $teacherId";
    $studentInfo = fetchOne($conn, $sqlStudent);
    
    if (!$studentInfo) {
        $error = "Không tìm thấy thông tin sinh viên hoặc bạn không có quyền nhập điểm cho sinh viên này.";
        $enrollmentId = 0;
    }
}

// Xử lý lưu điểm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    $enrollmentId = intval($_POST['enrollment_id']);
    $assignment = floatval($_POST['assignment_score'] ?? 0);
    $midterm = floatval($_POST['midterm_score'] ?? 0);
    $final = floatval($_POST['final_score'] ?? 0);
    $classId = intval($_POST['class_id']);
    
    // Validate điểm (0-10)
    if ($assignment < 0 || $assignment > 10 || $midterm < 0 || $midterm > 10 || $final < 0 || $final > 10) {
        $error = "Điểm không hợp lệ! Điểm phải từ 0 đến 10.";
    } else {
        // Tính điểm tổng kết (BT 30% + GK 20% + CK 50%)
        $totalScore = ($assignment * 0.3) + ($midterm * 0.2) + ($final * 0.5);
        
        // Xác định xếp loại
        $letterGrade = '';
        if ($totalScore >= 9.0) $letterGrade = 'A+';
        elseif ($totalScore >= 8.5) $letterGrade = 'A';
        elseif ($totalScore >= 8.0) $letterGrade = 'B+';
        elseif ($totalScore >= 7.0) $letterGrade = 'B';
        elseif ($totalScore >= 6.5) $letterGrade = 'C+';
        elseif ($totalScore >= 5.5) $letterGrade = 'C';
        elseif ($totalScore >= 5.0) $letterGrade = 'D+';
        elseif ($totalScore >= 4.0) $letterGrade = 'D';
        else $letterGrade = 'F';
        
        // Kiểm tra xem đã có điểm chưa
        $sqlCheck = "SELECT * FROM grades WHERE enrollment_id = $enrollmentId";
        $existingGrade = fetchOne($conn, $sqlCheck);
        
        if ($existingGrade) {
            // Cập nhật
            $sqlUpdate = "UPDATE grades SET 
                         assignment_score = $assignment,
                         midterm_score = $midterm,
                         final_score = $final,
                         total_score = $totalScore,
                         letter_grade = '$letterGrade'
                         WHERE enrollment_id = $enrollmentId";
            executeQuery($conn, $sqlUpdate);
        } else {
            // Thêm mới
            $sqlInsert = "INSERT INTO grades (enrollment_id, assignment_score, midterm_score, final_score, total_score, letter_grade)
                         VALUES ($enrollmentId, $assignment, $midterm, $final, $totalScore, '$letterGrade')";
            executeQuery($conn, $sqlInsert);
        }
        
        $success = "Đã lưu điểm thành công!";
        
        // Redirect về danh sách sau 1 giây
        header("refresh:1;url=nhap_diem.php?class_id=$classId");
    }
}

// Lấy lớp được chọn
$selectedClassId = intval($_GET['class_id'] ?? 0);

// Lấy danh sách lớp của giảng viên
$sqlClasses = "SELECT cc.*, c.course_code, c.course_name, s.semester_name
               FROM class_courses cc
               INNER JOIN courses c ON cc.course_id = c.id
               INNER JOIN semesters s ON cc.semester_id = s.id
               WHERE cc.teacher_id = $teacherId
               ORDER BY s.start_date DESC, c.course_name";
$classes = fetchAll($conn, $sqlClasses);

// Nếu chưa chọn lớp và có lớp thì chọn lớp đầu tiên
if ($selectedClassId === 0 && count($classes) > 0) {
    $selectedClassId = $classes[0]['id'];
}

// Lấy thông tin lớp và danh sách sinh viên
$classInfo = null;
$students = [];

if ($selectedClassId > 0) {
    $sqlClassInfo = "SELECT cc.*, c.course_code, c.course_name, c.credits, s.semester_name
                     FROM class_courses cc
                     INNER JOIN courses c ON cc.course_id = c.id
                     INNER JOIN semesters s ON cc.semester_id = s.id
                     WHERE cc.id = $selectedClassId AND cc.teacher_id = $teacherId";
    $classInfo = fetchOne($conn, $sqlClassInfo);
    
    if ($classInfo) {
        $sqlStudents = "SELECT s.student_code, u.full_name, s.class as student_class,
                        e.id as enrollment_id,
                        g.assignment_score, g.midterm_score, g.final_score, g.total_score, g.letter_grade
                        FROM enrollments e
                        INNER JOIN students s ON e.student_id = s.id
                        INNER JOIN users u ON s.user_id = u.id
                        LEFT JOIN grades g ON e.id = g.enrollment_id
                        WHERE e.class_course_id = $selectedClassId
                        ORDER BY s.student_code";
        $students = fetchAll($conn, $sqlStudents);
    }
}

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1><?php echo $enrollmentId > 0 ? 'Nhập điểm sinh viên' : 'Danh sách sinh viên'; ?></h1>
    </div>
    <?php if ($enrollmentId > 0 && $studentInfo): ?>
        <a href="nhap_diem.php?class_id=<?php echo $studentInfo['class_course_id']; ?>" 
           style="padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 6px; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    <?php endif; ?>
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

<?php if ($enrollmentId === 0): ?>
<!-- Filter Section - Only show when not editing -->
<div class="card" style="margin-bottom: 24px;">
    <div class="filter-section">
        <div class="filter-group">
            <label for="class">Chọn lớp học phần:</label>
            <select id="class" name="class" onchange="window.location.href='nhap_diem.php?class_id=' + this.value">
                <?php foreach ($classes as $cls): ?>
                    <option value="<?php echo $cls['id']; ?>" <?php echo ($cls['id'] == $selectedClassId) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cls['semester_name'] . ' - ' . $cls['course_name'] . ' - ' . $cls['class_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($enrollmentId > 0 && $studentInfo): ?>
    <!-- Form nhập điểm cho 1 sinh viên -->
    <div class="card" style="margin-bottom: 24px; background-color: #eef2ff;">
        <h2 style="font-size: 18px; margin-bottom: 12px; color: var(--primary-color);">
            Thông tin môn học
        </h2>
        <p style="color: var(--text-secondary); line-height: 1.8;">
            <strong>Môn học:</strong> <?php echo htmlspecialchars($studentInfo['course_name']); ?> (<?php echo htmlspecialchars($studentInfo['course_code']); ?>)<br>
            <strong>Lớp:</strong> <?php echo htmlspecialchars($studentInfo['class_name']); ?> | 
            <strong>Kỳ:</strong> <?php echo htmlspecialchars($studentInfo['semester_name']); ?> | 
            <strong>Tín chỉ:</strong> <?php echo $studentInfo['credits']; ?>
        </p>
    </div>
    
    <div class="card">
        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
            <i class="fas fa-edit"></i> Nhập điểm sinh viên
        </h2>
        
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 4px;">Mã sinh viên</p>
                    <p style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($studentInfo['student_code']); ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 4px;">Họ và tên</p>
                    <p style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($studentInfo['full_name']); ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 4px;">Lớp sinh hoạt</p>
                    <p style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($studentInfo['student_class']); ?></p>
                </div>
                <div>
                    <p style="color: var(--text-secondary); margin-bottom: 4px;">Điểm hiện tại</p>
                    <p style="font-weight: 600; font-size: 16px;">
                        <?php 
                            if ($studentInfo['total_score'] !== null) {
                                $color = $studentInfo['total_score'] >= 5.0 ? '#16a34a' : '#dc2626';
                                echo "<span style='color: $color;'>" . number_format($studentInfo['total_score'], 2) . " (" . htmlspecialchars($studentInfo['letter_grade']) . ")</span>";
                            } else {
                                echo '<span style="color: #999;">Chưa có điểm</span>';
                            }
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="enrollment_id" value="<?php echo $studentInfo['enrollment_id']; ?>">
            <input type="hidden" name="class_id" value="<?php echo $studentInfo['class_course_id']; ?>">
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                        Điểm Bài tập (0-10) <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="10" name="assignment_score" 
                           value="<?php echo $studentInfo['assignment_score'] ?? ''; ?>" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px; font-size: 16px;">
                    <small style="color: var(--text-secondary); display: block; margin-top: 4px;">Trọng số: 30%</small>
                </div>
                
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                        Điểm Giữa kỳ (0-10) <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="10" name="midterm_score" 
                           value="<?php echo $studentInfo['midterm_score'] ?? ''; ?>" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px; font-size: 16px;">
                    <small style="color: var(--text-secondary); display: block; margin-top: 4px;">Trọng số: 20%</small>
                </div>
                
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                        Điểm Cuối kỳ (0-10) <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="10" name="final_score" 
                           value="<?php echo $studentInfo['final_score'] ?? ''; ?>" required
                           style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 6px; font-size: 16px;">
                    <small style="color: var(--text-secondary); display: block; margin-top: 4px;">Trọng số: 50%</small>
                </div>
            </div>
            
            <div style="background-color: #fff3cd; padding: 16px; border-radius: 6px; margin-bottom: 24px; border: 1px solid #ffeaa7;">
                <strong><i class="fas fa-info-circle"></i> Lưu ý:</strong>
                <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
                    <li>Điểm nhập phải từ 0 đến 10</li>
                    <li>Điểm tổng kết = Điểm BT × 30% + Điểm GK × 20% + Điểm CK × 50%</li>
                    <li>Xếp loại được tính tự động dựa trên điểm tổng kết</li>
                </ul>
            </div>
            
            <div style="text-align: right;">
                <a href="nhap_diem.php?class_id=<?php echo $studentInfo['class_course_id']; ?>" 
                   style="padding: 12px 24px; background-color: #6c757d; color: white; border-radius: 6px; text-decoration: none; margin-right: 12px; display: inline-block;">
                    <i class="fas fa-times"></i> Hủy
                </a>
                <button type="submit" name="save_grade" 
                        style="padding: 12px 32px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 500;">
                    <i class="fas fa-save"></i> Lưu điểm
                </button>
            </div>
        </form>
    </div>
    
<?php elseif ($classInfo): ?>
    <!-- Danh sách sinh viên với nút nhập điểm -->
    <div class="card" style="margin-bottom: 24px; background-color: #eef2ff;">
        <h2 style="font-size: 18px; margin-bottom: 12px; color: var(--primary-color);">
            <?php echo htmlspecialchars($classInfo['course_name']); ?> - <?php echo htmlspecialchars($classInfo['class_name']); ?>
        </h2>
        <p style="color: var(--text-secondary);">
            <strong>Kỳ học:</strong> <?php echo htmlspecialchars($classInfo['semester_name']); ?> | 
            <strong>Mã HP:</strong> <?php echo htmlspecialchars($classInfo['course_code']); ?> | 
            <strong>Tín chỉ:</strong> <?php echo $classInfo['credits']; ?> | 
            <strong>Số SV:</strong> <?php echo count($students); ?>
        </p>
    </div>
    
    <?php if (count($students) > 0): ?>
        <div class="card">
            <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
                <i class="fas fa-list"></i> Danh sách sinh viên
            </h2>
            
            <div class="table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">STT</th>
                            <th>Mã SV</th>
                            <th>Họ và tên</th>
                            <th>Lớp</th>
                            <th style="width: 100px;">Điểm BT<br><small>(0-10)</small></th>
                            <th style="width: 100px;">Điểm GK<br><small>(0-10)</small></th>
                            <th style="width: 100px;">Điểm CK<br><small>(0-10)</small></th>
                            <th style="width: 100px;">Điểm TK<br><small>(Tự động)</small></th>
                            <th style="width: 80px;">Xếp loại</th>
                            <th style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $stt = 1; foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $stt++; ?></td>
                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['student_class']); ?></td>
                                <td>
                                    <?php 
                                        echo $student['assignment_score'] !== null 
                                            ? number_format($student['assignment_score'], 2)
                                            : '<span style="color: #999;">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        echo $student['midterm_score'] !== null 
                                            ? number_format($student['midterm_score'], 2)
                                            : '<span style="color: #999;">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        echo $student['final_score'] !== null 
                                            ? number_format($student['final_score'], 2)
                                            : '<span style="color: #999;">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if ($student['total_score'] !== null) {
                                            $color = $student['total_score'] >= 5.0 ? '#16a34a' : '#dc2626';
                                            echo "<strong style='color: $color;'>" . number_format($student['total_score'], 2) . "</strong>";
                                        } else {
                                            echo '<span style="color: #999;">-</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        if ($student['letter_grade']) {
                                            echo "<strong>" . htmlspecialchars($student['letter_grade']) . "</strong>";
                                        } else {
                                            echo '<span style="color: #999;">-</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <a href="nhap_diem.php?enrollment_id=<?php echo $student['enrollment_id']; ?>" 
                                       style="padding: 8px 16px; background-color: var(--primary-color); color: white; border-radius: 4px; text-decoration: none; display: inline-block; font-size: 14px;">
                                        <i class="fas fa-edit"></i> Nhập điểm
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
                Chưa có sinh viên nào đăng ký lớp này.
            </p>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <div class="card">
        <p style="color: var(--text-secondary); text-align: center; padding: 60px 20px;">
            <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
            Vui lòng chọn lớp học phần để xem danh sách sinh viên.
        </p>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
