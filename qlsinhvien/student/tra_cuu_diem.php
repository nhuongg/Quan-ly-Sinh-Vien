<?php
/**
 * Tra cứu điểm học tập
 * Sinh viên xem điểm các học phần đã đăng ký
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['student']);

$pageTitle = "Tra cứu điểm - Sinh viên";
$currentUser = getCurrentUser();
$studentInfo = getStudentInfo($conn, $currentUser['id']);
$studentId = getStudentIdByUserId($conn, $currentUser['id']);

// Lấy danh sách kỳ học
$sqlSemesters = "SELECT * FROM semesters ORDER BY start_date DESC";
$semesters = fetchAll($conn, $sqlSemesters);

// Lấy kỳ học được chọn (mặc định là kỳ hiện tại)
$selectedSemesterId = intval($_GET['semester_id'] ?? 0);
if ($selectedSemesterId === 0 && count($semesters) > 0) {
    // Tìm kỳ học active, nếu không có thì lấy kỳ gần nhất
    foreach ($semesters as $sem) {
        if ($sem['is_active'] === 'yes') {
            $selectedSemesterId = $sem['id'];
            break;
        }
    }
    if ($selectedSemesterId === 0) {
        $selectedSemesterId = $semesters[0]['id'];
    }
}

// Lấy điểm các học phần trong kỳ
$grades = [];
$totalScore = 0;
$totalCredits = 0;
$completedCourses = 0;

if ($selectedSemesterId > 0) {
    $sqlGrades = "SELECT c.course_code, c.course_name, c.credits, cc.class_name,
                  g.assignment_score, g.midterm_score, g.final_score, g.total_score, g.letter_grade,
                  CONCAT(u.full_name) as teacher_name
                  FROM enrollments e
                  INNER JOIN class_courses cc ON e.class_course_id = cc.id
                  INNER JOIN courses c ON cc.course_id = c.id
                  INNER JOIN teachers t ON cc.teacher_id = t.id
                  INNER JOIN users u ON t.user_id = u.id
                  LEFT JOIN grades g ON e.id = g.enrollment_id
                  WHERE e.student_id = $studentId AND cc.semester_id = $selectedSemesterId
                  ORDER BY c.course_name";
    $grades = fetchAll($conn, $sqlGrades);
    
    // Tính điểm trung bình kỳ
    foreach ($grades as $grade) {
        if ($grade['total_score'] !== null) {
            $totalScore += $grade['total_score'] * $grade['credits'];
            $totalCredits += $grade['credits'];
            $completedCourses++;
        }
    }
}

$semesterGPA = $totalCredits > 0 ? round($totalScore / $totalCredits, 2) : 0;

// Hàm chuyển đổi điểm số sang xếp loại
function getLetterGrade($score) {
    if ($score === null) return '';
    if ($score >= 9.0) return 'A+';
    if ($score >= 8.5) return 'A';
    if ($score >= 8.0) return 'B+';
    if ($score >= 7.0) return 'B';
    if ($score >= 6.5) return 'C+';
    if ($score >= 5.5) return 'C';
    if ($score >= 5.0) return 'D+';
    if ($score >= 4.0) return 'D';
    return 'F';
}

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Tra cứu điểm học tập</h1>
    </div>
</div>

<!-- Filter Section -->
<div class="card" style="margin-bottom: 24px;">
    <div class="filter-section">
        <div class="filter-group">
            <label for="semester">Chọn kỳ học:</label>
            <select id="semester" name="semester" onchange="window.location.href='tra_cuu_diem.php?semester_id=' + this.value">
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem['id']; ?>" <?php echo ($sem['id'] == $selectedSemesterId) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['semester_name']); ?>
                        <?php echo ($sem['is_active'] === 'yes') ? '(Hiện tại)' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Thông tin sinh viên -->
<div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 24px; border: 1px solid var(--border-color);">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div>
            <span style="color: var(--text-secondary); font-size: 14px;">Họ và tên:</span>
            <br>
            <strong style="font-size: 16px;"><?php echo htmlspecialchars($studentInfo['full_name']); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-secondary); font-size: 14px;">Mã sinh viên:</span>
            <br>
            <strong style="font-size: 16px;"><?php echo htmlspecialchars($studentInfo['student_code']); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-secondary); font-size: 14px;">Lớp:</span>
            <br>
            <strong style="font-size: 16px;"><?php echo htmlspecialchars($studentInfo['class']); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-secondary); font-size: 14px;">GPA tích lũy:</span>
            <br>
            <strong style="font-size: 16px; color: var(--primary-color);"><?php echo $studentInfo['gpa']; ?></strong>
        </div>
    </div>
</div>

<!-- Bảng điểm -->
<div class="card">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-chart-bar"></i> Bảng điểm chi tiết
    </h2>
    
    <?php if (count($grades) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Lớp</th>
                        <th>Tín chỉ</th>
                        <th>Giảng viên</th>
                        <th>Điểm BT</th>
                        <th>Điểm GK</th>
                        <th>Điểm CK</th>
                        <th>Điểm TK</th>
                        <th>Xếp loại</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['course_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($grade['course_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($grade['class_name']); ?></td>
                            <td><?php echo $grade['credits']; ?></td>
                            <td><?php echo htmlspecialchars($grade['teacher_name']); ?></td>
                            <td>
                                <?php 
                                    echo $grade['assignment_score'] !== null 
                                        ? number_format($grade['assignment_score'], 1) 
                                        : '<span style="color: #999;">-</span>'; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                    echo $grade['midterm_score'] !== null 
                                        ? number_format($grade['midterm_score'], 1) 
                                        : '<span style="color: #999;">-</span>'; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                    echo $grade['final_score'] !== null 
                                        ? number_format($grade['final_score'], 1) 
                                        : '<span style="color: #999;">-</span>'; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if ($grade['total_score'] !== null) {
                                        $score = number_format($grade['total_score'], 2);
                                        $color = $grade['total_score'] >= 5.0 ? '#16a34a' : '#dc2626';
                                        echo "<strong style='color: $color;'>$score</strong>";
                                    } else {
                                        echo '<span style="color: #999;">Chưa có</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if ($grade['letter_grade']) {
                                        $letterGrade = $grade['letter_grade'];
                                        $bgColor = '#16a34a';
                                        if (in_array($letterGrade, ['D+', 'D', 'F'])) {
                                            $bgColor = '#dc2626';
                                        } elseif (in_array($letterGrade, ['C+', 'C'])) {
                                            $bgColor = '#f59e0b';
                                        }
                                        echo "<span style='background-color: $bgColor; color: white; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px;'>$letterGrade</span>";
                                    } else {
                                        echo '<span style="color: #999;">-</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tổng kết -->
        <div class="summary-section">
            <div class="summary-item">
                <span>Tổng số học phần</span>
                <strong class="text-based"><?php echo count($grades); ?></strong>
            </div>
            <div class="summary-item">
                <span>Đã có điểm</span>
                <strong class="text-based"><?php echo $completedCourses; ?></strong>
            </div>
            <div class="summary-item">
                <span>Tổng tín chỉ</span>
                <strong class="text-based"><?php echo $totalCredits; ?></strong>
            </div>
            <div class="summary-item">
                <span>GPA kỳ này</span>
                <strong><?php echo $semesterGPA; ?></strong>
            </div>
        </div>
        
        <!-- Nút in/xuất PDF -->
        <div style="margin-top: 24px; text-align: right;">
            <button onclick="window.print()" style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                <i class="fas fa-print"></i> In bảng điểm
            </button>
        </div>
        
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            Bạn chưa đăng ký học phần nào trong kỳ này.
        </p>
    <?php endif; ?>
</div>

<!-- Chú thích -->
<div class="card" style="margin-top: 24px;">
    <h3 style="font-size: 16px; margin-bottom: 12px;">Chú thích:</h3>
    <ul style="color: var(--text-secondary); font-size: 14px; line-height: 1.8;">
        <li><strong>BT:</strong> Điểm bài tập</li>
        <li><strong>GK:</strong> Điểm thi giữa kỳ</li>
        <li><strong>CK:</strong> Điểm thi cuối kỳ</li>
        <li><strong>TK:</strong> Điểm tổng kết (Điểm BT × 0.3 + Điểm GK × 0.2 + Điểm CK × 0.5)</li>
        <li><strong>Thang điểm:</strong> A+ (9.0-10), A (8.5-8.9), B+ (8.0-8.4), B (7.0-7.9), C+ (6.5-6.9), C (5.5-6.4), D+ (5.0-5.4), D (4.0-4.9), F (&lt;4.0)</li>
    </ul>
</div>

<?php include '../includes/footer.php'; ?>
