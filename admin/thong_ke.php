<?php
/**
 * Thống kê và báo cáo
 * Quản trị viên xem các thống kê tổng quan
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['admin']);

$pageTitle = "Thống kê - Quản trị viên";
$currentUser = getCurrentUser();

// Thống kê tổng quan
$sqlStats = "SELECT 
             (SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active') as active_students,
             (SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active') as active_teachers,
             (SELECT COUNT(*) FROM courses) as total_courses,
             (SELECT COUNT(*) FROM class_courses) as total_classes";
$stats = fetchOne($conn, $sqlStats);

// Đảm bảo $stats không null
if (!$stats) {
    $stats = array(
        'active_students' => 0,
        'active_teachers' => 0,
        'total_courses' => 0,
        'total_classes' => 0
    );
}

// Thống kê theo kỳ học
$sqlSemesterStats = "SELECT s.semester_name, s.semester_code,
                     COUNT(DISTINCT cc.id) as class_count,
                     COUNT(DISTINCT e.id) as enrollment_count,
                     SUM(cc.current_students) as student_count
                     FROM semesters s
                     LEFT JOIN class_courses cc ON s.id = cc.semester_id
                     LEFT JOIN enrollments e ON cc.id = e.class_course_id
                     GROUP BY s.id
                     ORDER BY s.start_date DESC
                     LIMIT 5";
$semesterStats = fetchAll($conn, $sqlSemesterStats);

// Top học phần được đăng ký nhiều nhất
$sqlTopCourses = "SELECT c.course_code, c.course_name, c.credits,
                  COUNT(DISTINCT e.id) as enrollment_count
                  FROM courses c
                  INNER JOIN class_courses cc ON c.id = cc.course_id
                  INNER JOIN enrollments e ON cc.id = e.class_course_id
                  GROUP BY c.id
                  ORDER BY enrollment_count DESC
                  LIMIT 10";
$topCourses = fetchAll($conn, $sqlTopCourses);

// Thống kê sinh viên theo chuyên ngành
$sqlMajorStats = "SELECT major, COUNT(*) as count
                  FROM students
                  GROUP BY major
                  ORDER BY count DESC";
$majorStats = fetchAll($conn, $sqlMajorStats);

// Thống kê giảng viên theo khoa
$sqlDepartmentStats = "SELECT department, COUNT(*) as count
                       FROM teachers
                       GROUP BY department
                       ORDER BY count DESC";
$departmentStats = fetchAll($conn, $sqlDepartmentStats);

// Thống kê điểm trung bình theo học phần
$sqlGradeStats = "SELECT c.course_code, c.course_name,
                  COUNT(g.id) as graded_count,
                  ROUND(AVG(g.total_score), 2) as avg_score,
                  COUNT(CASE WHEN g.total_score >= 5.0 THEN 1 END) as pass_count,
                  COUNT(CASE WHEN g.total_score < 5.0 THEN 1 END) as fail_count
                  FROM courses c
                  INNER JOIN class_courses cc ON c.id = cc.course_id
                  INNER JOIN enrollments e ON cc.id = e.class_course_id
                  INNER JOIN grades g ON e.id = g.enrollment_id
                  WHERE g.total_score IS NOT NULL
                  GROUP BY c.id
                  ORDER BY avg_score DESC
                  LIMIT 10";
$gradeStats = fetchAll($conn, $sqlGradeStats);

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Thống kê và Báo cáo</h1>
    </div>
</div>

<!-- Thống kê tổng quan -->
<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="stat-card">
        <i class="fas fa-user-graduate"></i>
        <h3><?php echo $stats['active_students']; ?></h3>
        <p>Sinh viên hoạt động</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-chalkboard-teacher"></i>
        <h3><?php echo $stats['active_teachers']; ?></h3>
        <p>Giảng viên hoạt động</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-book"></i>
        <h3><?php echo $stats['total_courses']; ?></h3>
        <p>Tổng học phần</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-chalkboard"></i>
        <h3><?php echo $stats['total_classes']; ?></h3>
        <p>Tổng lớp học</p>
    </div>
</div>

<!-- Grid 2 cột -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Thống kê theo kỳ học -->
    <div class="card">
        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
            <i class="fas fa-calendar-alt"></i> Thống kê theo kỳ học
        </h2>
        
        <?php if (count($semesterStats) > 0): ?>
            <div class="table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Kỳ học</th>
                            <th>Số lớp</th>
                            <th>Lượt đăng ký</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semesterStats as $stat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($stat['semester_name']); ?></strong></td>
                                <td><?php echo $stat['class_count']; ?></td>
                                <td><?php echo $stat['enrollment_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 20px;">Chưa có dữ liệu</p>
        <?php endif; ?>
    </div>
    
    <!-- Sinh viên theo chuyên ngành -->
    <div class="card">
        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
            <i class="fas fa-graduation-cap"></i> Sinh viên theo chuyên ngành
        </h2>
        
        <?php if (count($majorStats) > 0): ?>
            <div class="table-container">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Chuyên ngành</th>
                            <th>Số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($majorStats as $stat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($stat['major']); ?></strong></td>
                                <td><?php echo $stat['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 20px;">Chưa có dữ liệu</p>
        <?php endif; ?>
    </div>
</div>

<!-- Top học phần -->
<div class="card" style="margin-bottom: 32px;">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-fire"></i> Top 10 học phần được đăng ký nhiều nhất
    </h2>
    
    <?php if (count($topCourses) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Hạng</th>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Tín chỉ</th>
                        <th>Lượt đăng ký</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topCourses as $course): ?>
                        <tr>
                            <td>
                                <?php 
                                    $medal = '';
                                    if ($rank === 1) $medal = '🥇';
                                    elseif ($rank === 2) $medal = '🥈';
                                    elseif ($rank === 3) $medal = '🥉';
                                    else $medal = $rank;
                                    echo $medal;
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><strong style="color: var(--primary-color);"><?php echo $course['enrollment_count']; ?></strong></td>
                        </tr>
                    <?php $rank++; endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Chưa có dữ liệu đăng ký</p>
    <?php endif; ?>
</div>

<!-- Thống kê điểm -->
<div class="card">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-chart-bar"></i> Thống kê điểm trung bình theo học phần
    </h2>
    
    <?php if (count($gradeStats) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Đã chấm</th>
                        <th>Điểm TB</th>
                        <th>Đậu</th>
                        <th>Rớt</th>
                        <th>Tỷ lệ đậu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gradeStats as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($stat['course_name']); ?></td>
                            <td><?php echo $stat['graded_count']; ?></td>
                            <td>
                                <strong style="color: <?php echo $stat['avg_score'] >= 5.0 ? '#16a34a' : '#dc2626'; ?>;">
                                    <?php echo $stat['avg_score']; ?>
                                </strong>
                            </td>
                            <td style="color: #16a34a; font-weight: 500;"><?php echo $stat['pass_count']; ?></td>
                            <td style="color: #dc2626; font-weight: 500;"><?php echo $stat['fail_count']; ?></td>
                            <td>
                                <?php 
                                    $passRate = $stat['graded_count'] > 0 ? round(($stat['pass_count'] / $stat['graded_count']) * 100, 1) : 0;
                                    $color = $passRate >= 80 ? '#16a34a' : ($passRate >= 60 ? '#f59e0b' : '#dc2626');
                                    echo "<strong style='color: $color;'>{$passRate}%</strong>";
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">Chưa có dữ liệu điểm</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
