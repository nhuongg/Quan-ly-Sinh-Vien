<?php
/**
 * Quản lý học phần và lớp học
 * Quản trị viên quản lý học phần, lớp học, phân công giảng viên
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['admin']);

$pageTitle = "Quản lý học phần - Quản trị viên";
$currentUser = getCurrentUser();

$error = '';
$success = '';

// Xử lý thêm học phần
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $courseCode = trim($_POST['course_code'] ?? '');
    $courseName = trim($_POST['course_name'] ?? '');
    $credits = intval($_POST['credits'] ?? 0);
    $maxStudents = intval($_POST['max_students'] ?? 50);
    $description = trim($_POST['description'] ?? '');
    
    if (empty($courseCode) || empty($courseName) || $credits <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin học phần.';
    } else {
        $courseCode = escape_string($conn, $courseCode);
        $courseName = escape_string($conn, $courseName);
        $description = escape_string($conn, $description);
        
        $sqlInsert = "INSERT INTO courses (course_code, course_name, credits, max_students, description) 
                     VALUES ('$courseCode', '$courseName', $credits, $maxStudents, '$description')";
        
        if (executeQuery($conn, $sqlInsert)) {
            $success = 'Thêm học phần thành công!';
        } else {
            $error = 'Mã học phần đã tồn tại hoặc có lỗi xảy ra.';
        }
    }
}

// Xử lý xóa học phần
if (isset($_GET['delete_course'])) {
    $courseId = intval($_GET['delete_course']);
    $sqlDelete = "DELETE FROM courses WHERE id = $courseId";
    if (executeQuery($conn, $sqlDelete)) {
        $success = 'Xóa học phần thành công!';
    } else {
        $error = 'Không thể xóa học phần đã có lớp học.';
    }
}

// Xử lý cập nhật học phần
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_course'])) {
    $courseId = intval($_POST['course_id'] ?? 0);
    $courseCode = trim($_POST['course_code'] ?? '');
    $courseName = trim($_POST['course_name'] ?? '');
    $credits = intval($_POST['credits'] ?? 0);
    $maxStudents = intval($_POST['max_students'] ?? 50);
    $description = trim($_POST['description'] ?? '');
    
    // Debug: Log các giá trị nhận được
    error_log("Edit course POST data: courseId=$courseId, courseCode=$courseCode, courseName=$courseName, credits=$credits");
    
    if ($courseId <= 0 || empty($courseCode) || empty($courseName) || $credits <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin học phần.';
    } else {
        $courseCode = escape_string($conn, $courseCode);
        $courseName = escape_string($conn, $courseName);
        $description = escape_string($conn, $description);
        
        // Kiểm tra mã học phần trùng (trừ học phần đang sửa)
        $sqlCheck = "SELECT id FROM courses WHERE course_code = '$courseCode' AND id != $courseId";
        $existing = fetchOne($conn, $sqlCheck);
        if ($existing) {
            $error = 'Mã học phần đã tồn tại.';
        } else {
            $sqlUpdate = "UPDATE courses 
                         SET course_code = '$courseCode', 
                             course_name = '$courseName', 
                             credits = $credits, 
                             max_students = $maxStudents, 
                             description = '$description'
                         WHERE id = $courseId";
            
            if (executeQuery($conn, $sqlUpdate)) {
                $success = 'Cập nhật học phần thành công!';
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật học phần.';
            }
        }
    }
}

// Xử lý thêm lớp học phần
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $courseId = intval($_POST['course_id'] ?? 0);
    $teacherId = intval($_POST['teacher_id'] ?? 0);
    $semesterId = intval($_POST['semester_id'] ?? 0);
    $className = trim($_POST['class_name'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $maxStudents = intval($_POST['max_students_class'] ?? 50);
    
    if ($courseId <= 0 || $teacherId <= 0 || $semesterId <= 0 || empty($className)) {
        $error = 'Vui lòng nhập đầy đủ thông tin lớp học.';
    } else {
        $className = escape_string($conn, $className);
        $schedule = escape_string($conn, $schedule);
        $room = escape_string($conn, $room);
        
        $sqlInsert = "INSERT INTO class_courses (course_id, teacher_id, semester_id, class_name, schedule, room, max_students) 
                     VALUES ($courseId, $teacherId, $semesterId, '$className', '$schedule', '$room', $maxStudents)";
        
        if (executeQuery($conn, $sqlInsert)) {
            $success = 'Thêm lớp học phần thành công!';
        } else {
            $error = 'Có lỗi xảy ra khi thêm lớp học.';
        }
    }
}

// Xử lý xóa lớp học
if (isset($_GET['delete_class'])) {
    $classId = intval($_GET['delete_class']);
    $sqlDelete = "DELETE FROM class_courses WHERE id = $classId";
    if (executeQuery($conn, $sqlDelete)) {
        $success = 'Xóa lớp học thành công!';
    } else {
        $error = 'Không thể xóa lớp học đã có sinh viên đăng ký.';
    }
}

// Xử lý cập nhật lớp học phần
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class'])) {
    $classId = intval($_POST['class_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $teacherId = intval($_POST['teacher_id'] ?? 0);
    $semesterId = intval($_POST['semester_id'] ?? 0);
    $className = trim($_POST['class_name'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $maxStudents = intval($_POST['max_students_class'] ?? 50);
    
    // Debug: Log các giá trị nhận được
    error_log("Edit class POST data: classId=$classId, courseId=$courseId, teacherId=$teacherId, semesterId=$semesterId, className=$className");
    
    if ($classId <= 0 || $courseId <= 0 || $teacherId <= 0 || $semesterId <= 0 || empty($className)) {
        $error = 'Vui lòng nhập đầy đủ thông tin lớp học.';
    } else {
        $className = escape_string($conn, $className);
        $schedule = escape_string($conn, $schedule);
        $room = escape_string($conn, $room);
        
        $sqlUpdate = "UPDATE class_courses 
                     SET course_id = $courseId, 
                         teacher_id = $teacherId, 
                         semester_id = $semesterId, 
                         class_name = '$className', 
                         schedule = '$schedule', 
                         room = '$room', 
                         max_students = $maxStudents
                     WHERE id = $classId";
        
        if (executeQuery($conn, $sqlUpdate)) {
            $success = 'Cập nhật lớp học phần thành công!';
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật lớp học.';
        }
    }
}

// Xử lý tìm kiếm học phần
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterCredits = isset($_GET['credits']) ? intval($_GET['credits']) : 0;

// Lấy danh sách học phần
$sqlCourses = "SELECT c.*, 
               (SELECT COUNT(*) FROM class_courses WHERE course_id = c.id) as class_count
               FROM courses c
               WHERE 1=1";

// Thêm điều kiện tìm kiếm
if (!empty($searchKeyword)) {
    $searchKeywordEscaped = escape_string($conn, $searchKeyword);
    $sqlCourses .= " AND (c.course_code LIKE '%$searchKeywordEscaped%' 
                    OR c.course_name LIKE '%$searchKeywordEscaped%'
                    OR c.description LIKE '%$searchKeywordEscaped%')";
}

// Thêm điều kiện lọc theo số tín chỉ
if ($filterCredits > 0) {
    $sqlCourses .= " AND c.credits = $filterCredits";
}

$sqlCourses .= " ORDER BY c.course_code";
$courses = fetchAll($conn, $sqlCourses);

// Lấy danh sách giảng viên
$sqlTeachers = "SELECT t.id, t.teacher_code, u.full_name 
                FROM teachers t
                INNER JOIN users u ON t.user_id = u.id
                WHERE u.status = 'active'
                ORDER BY t.teacher_code";
$teachers = fetchAll($conn, $sqlTeachers);

// Lấy danh sách kỳ học
$sqlSemesters = "SELECT * FROM semesters ORDER BY start_date DESC";
$semesters = fetchAll($conn, $sqlSemesters);

// Lấy danh sách lớp học phần
$sqlClasses = "SELECT cc.*, c.course_code, c.course_name, 
               CONCAT(u.full_name) as teacher_name, s.semester_name
               FROM class_courses cc
               INNER JOIN courses c ON cc.course_id = c.id
               INNER JOIN teachers t ON cc.teacher_id = t.id
               INNER JOIN users u ON t.user_id = u.id
               INNER JOIN semesters s ON cc.semester_id = s.id
               ORDER BY s.start_date DESC, c.course_code";
$classes = fetchAll($conn, $sqlClasses);

// Lấy thông tin học phần để chỉnh sửa
$editCourse = null;
if (isset($_GET['edit_course'])) {
    $courseId = intval($_GET['edit_course']);
    $editCourse = fetchOne($conn, "SELECT * FROM courses WHERE id = $courseId");
}

// Lấy thông tin lớp học để chỉnh sửa
$editClass = null;
if (isset($_GET['edit_class'])) {
    $classId = intval($_GET['edit_class']);
    $editClass = fetchOne($conn, "SELECT * FROM class_courses WHERE id = $classId");
}

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Quản lý học phần</h1>
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

<!-- Search and Filter Section -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="" id="searchForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 12px; align-items: end;">
            <!-- Tìm kiếm theo từ khóa -->
            <div class="form-group" style="margin-bottom: 0;">
                <label style="display: block; margin-bottom: 6px; color: var(--text-primary); font-weight: 500; font-size: 14px;">
                    <i class="fas fa-search"></i> Tìm kiếm
                </label>
                <input type="text" 
                       name="search" 
                       id="searchInput"
                       value="<?php echo htmlspecialchars($searchKeyword); ?>" 
                       placeholder="Mã HP, tên học phần, mô tả..."
                       style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
            </div>
            
            <!-- Lọc theo số tín chỉ -->
            <div class="form-group" style="margin-bottom: 0;">
                <label style="display: block; margin-bottom: 6px; color: var(--text-primary); font-weight: 500; font-size: 14px;">
                    <i class="fas fa-filter"></i> Số tín chỉ
                </label>
                <select name="credits" 
                        id="creditsFilter"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background-color: white;">
                    <option value="0">Tất cả</option>
                    <option value="1" <?php echo $filterCredits == 1 ? 'selected' : ''; ?>>1 tín chỉ</option>
                    <option value="2" <?php echo $filterCredits == 2 ? 'selected' : ''; ?>>2 tín chỉ</option>
                    <option value="3" <?php echo $filterCredits == 3 ? 'selected' : ''; ?>>3 tín chỉ</option>
                    <option value="4" <?php echo $filterCredits == 4 ? 'selected' : ''; ?>>4 tín chỉ</option>
                    <option value="5" <?php echo $filterCredits == 5 ? 'selected' : ''; ?>>5 tín chỉ</option>
                </select>
            </div>
            
            <!-- Nút tìm kiếm -->
            <button type="submit" 
                    style="padding: 10px 24px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; height: 42px;">
                <i class="fas fa-search"></i> Tìm
            </button>
            
            <!-- Nút reset -->
            <?php if (!empty($searchKeyword) || $filterCredits > 0): ?>
            <a href="quan_ly_hoc_phan.php" 
               style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; height: 42px; display: flex; align-items: center; text-decoration: none;">
                <i class="fas fa-redo"></i> Reset
            </a>
            <?php endif; ?>
        </div>
    </form>
    
    <?php if (!empty($searchKeyword) || $filterCredits > 0): ?>
    <div style="margin-top: 16px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid var(--primary-color); border-radius: 4px;">
        <i class="fas fa-info-circle"></i> 
        <strong>Kết quả tìm kiếm:</strong> 
        Tìm thấy <strong><?php echo count($courses); ?></strong> học phần
        <?php if (!empty($searchKeyword)): ?>
            với từ khóa "<strong><?php echo htmlspecialchars($searchKeyword); ?></strong>"
        <?php endif; ?>
        <?php if ($filterCredits > 0): ?>
            có <strong><?php echo $filterCredits; ?></strong> tín chỉ
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Buttons -->
<div style="margin-bottom: 16px; display: flex; gap: 12px;">
    <button onclick="document.getElementById('addCourseModal').style.display='block'" 
            style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
        <i class="fas fa-plus"></i> Thêm học phần
    </button>
</div>

<!-- Danh sách học phần -->
<div class="card" style="margin-bottom: 32px;">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-book"></i> Danh sách học phần (<?php echo count($courses); ?>)
    </h2>
    
    <?php if (count($courses) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Tín chỉ</th>
                        <th>Số lớp</th>
                        <th>Mô tả</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo $course['class_count']; ?></td>
                            <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)); ?><?php echo strlen($course['description']) > 50 ? '...' : ''; ?></td>
                            <td>
                                <a href="#" 
                                   class="edit-course-btn"
                                   data-course-id="<?php echo $course['id']; ?>"
                                   data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                                   data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                                   data-credits="<?php echo $course['credits']; ?>"
                                   data-max-students="<?php echo $course['max_students']; ?>"
                                   data-description="<?php echo htmlspecialchars($course['description'] ?? ''); ?>"
                                   style="color: var(--primary-color); text-decoration: none; margin-right: 12px;"
                                   title="Sửa học phần">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete_course=<?php echo $course['id']; ?>" 
                                   onclick="return confirm('Bạn có chắc muốn xóa học phần này?')"
                                   style="color: var(--danger-color); text-decoration: none;"
                                   title="Xóa học phần">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            <?php if (!empty($searchKeyword) || $filterCredits > 0): ?>
                <i class="fas fa-search"></i><br><br>
                Không tìm thấy học phần nào phù hợp với tiêu chí tìm kiếm.<br>
                <a href="quan_ly_hoc_phan.php" style="color: var(--primary-color); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Xem tất cả học phần
                </a>
            <?php else: ?>
                Chưa có học phần nào.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- Nút thêm lớp học -->
<div style="margin-bottom: 16px; display: flex; gap: 12px;">
    <button onclick="document.getElementById('addClassModal').style.display='block'" 
            style="padding: 10px 20px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
        <i class="fas fa-plus"></i> Thêm lớp học
    </button>
</div>
<!-- Danh sách lớp học phần -->
<div class="card">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-chalkboard"></i> Danh sách lớp học phần (<?php echo count($classes); ?>)
    </h2>
    
    <?php if (count($classes) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Kỳ học</th>
                        <th>Mã HP</th>
                        <th>Tên học phần</th>
                        <th>Lớp</th>
                        <th>Giảng viên</th>
                        <th>Lịch học</th>
                        <th>Phòng</th>
                        <th>SL</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['semester_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['course_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($class['course_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['schedule']); ?></td>
                            <td><?php echo htmlspecialchars($class['room']); ?></td>
                            <td><?php echo $class['current_students']; ?>/<?php echo $class['max_students']; ?></td>
                            <td>
                                <a href="#" 
                                   class="edit-class-btn"
                                   data-class-id="<?php echo $class['id']; ?>"
                                   data-course-id="<?php echo $class['course_id']; ?>"
                                   data-teacher-id="<?php echo $class['teacher_id']; ?>"
                                   data-semester-id="<?php echo $class['semester_id']; ?>"
                                   data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>"
                                   data-schedule="<?php echo htmlspecialchars($class['schedule'] ?? ''); ?>"
                                   data-room="<?php echo htmlspecialchars($class['room'] ?? ''); ?>"
                                   data-max-students="<?php echo $class['max_students']; ?>"
                                   style="color: var(--primary-color); text-decoration: none; margin-right: 12px;"
                                   title="Sửa lớp học">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete_class=<?php echo $class['id']; ?>" 
                                   onclick="return confirm('Bạn có chắc muốn xóa lớp này?')"
                                   style="color: var(--danger-color); text-decoration: none;"
                                   title="Xóa lớp học">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            Chưa có lớp học nào.
        </p>
    <?php endif; ?>
</div>

<!-- Modal thêm học phần -->
<div id="addCourseModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; color: var(--primary-color);">
                <i class="fas fa-book"></i> Thêm học phần mới
            </h2>
            <span onclick="document.getElementById('addCourseModal').style.display='none'" 
                  style="font-size: 28px; cursor: pointer; color: #999;">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Mã học phần: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="course_code" required placeholder="IT101">
            </div>
            
            <div class="form-group">
                <label>Tên học phần: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="course_name" required placeholder="Lập trình căn bản">
            </div>
            
            <div class="form-group">
                <label>Số tín chỉ: <span style="color: var(--danger-color);">*</span></label>
                <input type="number" name="credits" required min="1" max="10" value="3">
            </div>
            
            <div class="form-group">
                <label>Số sinh viên tối đa:</label>
                <input type="number" name="max_students" min="10" max="200" value="50">
            </div>
            
            <div class="form-group">
                <label>Mô tả:</label>
                <textarea name="description" placeholder="Mô tả học phần..."></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('addCourseModal').style.display='none'" 
                        style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;">
                    Hủy
                </button>
                <button type="submit" name="add_course" 
                        style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal thêm lớp học -->
<div id="addClassModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; color: var(--primary-color);">
                <i class="fas fa-chalkboard"></i> Thêm lớp học phần
            </h2>
            <span onclick="document.getElementById('addClassModal').style.display='none'" 
                  style="font-size: 28px; cursor: pointer; color: #999;">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Học phần: <span style="color: var(--danger-color);">*</span></label>
                <select name="course_id" required>
                    <option value="">Chọn học phần</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Giảng viên: <span style="color: var(--danger-color);">*</span></label>
                <select name="teacher_id" required>
                    <option value="">Chọn giảng viên</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>">
                            <?php echo htmlspecialchars($teacher['teacher_code'] . ' - ' . $teacher['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Kỳ học: <span style="color: var(--danger-color);">*</span></label>
                <select name="semester_id" required>
                    <option value="">Chọn kỳ học</option>
                    <?php foreach ($semesters as $semester): ?>
                        <option value="<?php echo $semester['id']; ?>" <?php echo $semester['is_active'] === 'yes' ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($semester['semester_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tên lớp: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="class_name" required placeholder="IT101-01">
            </div>
            
            <div class="form-group">
                <label>Lịch học:</label>
                <input type="text" name="schedule" placeholder="Thứ 2: 7h30-9h30, Thứ 5: 7h30-9h30">
            </div>
            
            <div class="form-group">
                <label>Phòng học:</label>
                <input type="text" name="room" placeholder="A101">
            </div>
            
            <div class="form-group">
                <label>Số sinh viên tối đa:</label>
                <input type="number" name="max_students_class" min="10" max="200" value="50">
            </div>
            
            <div style="text-align: right; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('addClassModal').style.display='none'" 
                        style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;">
                    Hủy
                </button>
                <button type="submit" name="add_class" 
                        style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal sửa học phần -->
<div id="editCourseModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; color: var(--primary-color);">
                <i class="fas fa-edit"></i> Sửa học phần
            </h2>
            <span onclick="document.getElementById('editCourseModal').style.display='none'" 
                  style="font-size: 28px; cursor: pointer; color: #999;">&times;</span>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="course_id" id="edit_course_id">
            
            <div class="form-group">
                <label>Mã học phần: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="course_code" id="edit_course_code" required placeholder="IT101">
            </div>
            
            <div class="form-group">
                <label>Tên học phần: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="course_name" id="edit_course_name" required placeholder="Lập trình căn bản">
            </div>
            
            <div class="form-group">
                <label>Số tín chỉ: <span style="color: var(--danger-color);">*</span></label>
                <input type="number" name="credits" id="edit_credits" required min="1" max="10">
            </div>
            
            <div class="form-group">
                <label>Số sinh viên tối đa:</label>
                <input type="number" name="max_students" id="edit_max_students" min="10" max="200" value="50">
            </div>
            
            <div class="form-group">
                <label>Mô tả:</label>
                <textarea name="description" id="edit_description" placeholder="Mô tả học phần..."></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('editCourseModal').style.display='none'" 
                        style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;">
                    Hủy
                </button>
                <button type="submit" name="edit_course" 
                        style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal sửa lớp học -->
<div id="editClassModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; color: var(--primary-color);">
                <i class="fas fa-edit"></i> Sửa lớp học phần
            </h2>
            <span onclick="document.getElementById('editClassModal').style.display='none'" 
                  style="font-size: 28px; cursor: pointer; color: #999;">&times;</span>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="class_id" id="edit_class_id">
            
            <div class="form-group">
                <label>Học phần: <span style="color: var(--danger-color);">*</span></label>
                <select name="course_id" id="edit_class_course_id" required>
                    <option value="">Chọn học phần</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Giảng viên: <span style="color: var(--danger-color);">*</span></label>
                <select name="teacher_id" id="edit_class_teacher_id" required>
                    <option value="">Chọn giảng viên</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>">
                            <?php echo htmlspecialchars($teacher['teacher_code'] . ' - ' . $teacher['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Kỳ học: <span style="color: var(--danger-color);">*</span></label>
                <select name="semester_id" id="edit_class_semester_id" required>
                    <option value="">Chọn kỳ học</option>
                    <?php foreach ($semesters as $semester): ?>
                        <option value="<?php echo $semester['id']; ?>">
                            <?php echo htmlspecialchars($semester['semester_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tên lớp: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="class_name" id="edit_class_name" required placeholder="IT101-01">
            </div>
            
            <div class="form-group">
                <label>Lịch học:</label>
                <input type="text" name="schedule" id="edit_class_schedule" placeholder="Thứ 2: 7h30-9h30, Thứ 5: 7h30-9h30">
            </div>
            
            <div class="form-group">
                <label>Phòng học:</label>
                <input type="text" name="room" id="edit_class_room" placeholder="A101">
            </div>
            
            <div class="form-group">
                <label>Số sinh viên tối đa:</label>
                <input type="number" name="max_students_class" id="edit_class_max_students" min="10" max="200" value="50">
            </div>
            
            <div style="text-align: right; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('editClassModal').style.display='none'" 
                        style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;">
                    Hủy
                </button>
                <button type="submit" name="edit_class" 
                        style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript cho tìm kiếm -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    
    // Auto focus vào ô tìm kiếm khi trang load
    if (searchInput && searchInput.value === '') {
        searchInput.focus();
    }
    
    // Hỗ trợ phím tắt Ctrl+F hoặc Cmd+F để focus vào tìm kiếm
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });
    
    // Thêm hiệu ứng loading khi submit form
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            const submitBtn = searchForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tìm...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Đếm số kết quả tìm kiếm
    const courseRows = document.querySelectorAll('.results-table tbody tr');
    if (courseRows.length > 0) {
        console.log('Tìm thấy ' + courseRows.length + ' học phần');
    }
    
    // Highlight từ khóa tìm kiếm trong kết quả
    const searchKeyword = '<?php echo addslashes($searchKeyword); ?>';
    if (searchKeyword.length > 0) {
        highlightKeyword(searchKeyword);
    }
    
    // Event listeners cho nút sửa học phần
    document.querySelectorAll('.edit-course-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openEditCourseModal(this);
        });
    });
    
    // Event listeners cho nút sửa lớp học
    document.querySelectorAll('.edit-class-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openEditClassModal(this);
        });
    });
    
    // Validation và submit form sửa học phần
    const editCourseForm = document.querySelector('#editCourseModal form');
    if (editCourseForm) {
        editCourseForm.addEventListener('submit', function(e) {
            const courseIdInput = document.getElementById('edit_course_id');
            const courseCodeInput = document.getElementById('edit_course_code');
            const courseNameInput = document.getElementById('edit_course_name');
            const creditsInput = document.getElementById('edit_credits');
            
            if (!courseIdInput || !courseCodeInput || !courseNameInput || !creditsInput) {
                console.error('Form inputs not found');
                return;
            }
            
            const courseId = courseIdInput.value.trim();
            const courseCode = courseCodeInput.value.trim();
            const courseName = courseNameInput.value.trim();
            const credits = creditsInput.value.trim();
            
            console.log('Submitting edit course form:', {courseId, courseCode, courseName, credits});
            
            // Kiểm tra validation - courseId phải là số > 0
            if (!courseId || isNaN(courseId) || parseInt(courseId) <= 0) {
                e.preventDefault();
                alert('Lỗi: Không tìm thấy ID học phần. Vui lòng làm mới trang và thử lại.');
                return false;
            }
            
            // Kiểm tra các trường bắt buộc
            if (!courseCode || courseCode === '') {
                e.preventDefault();
                alert('Vui lòng nhập mã học phần.');
                courseCodeInput.focus();
                return false;
            }
            
            if (!courseName || courseName === '') {
                e.preventDefault();
                alert('Vui lòng nhập tên học phần.');
                courseNameInput.focus();
                return false;
            }
            
            if (!credits || credits === '' || isNaN(credits) || parseInt(credits) <= 0) {
                e.preventDefault();
                alert('Vui lòng nhập số tín chỉ hợp lệ (lớn hơn 0).');
                creditsInput.focus();
                return false;
            }
        });
    }
    
    // Validation và submit form sửa lớp học
    const editClassForm = document.querySelector('#editClassModal form');
    if (editClassForm) {
        editClassForm.addEventListener('submit', function(e) {
            const classId = document.getElementById('edit_class_id').value;
            const courseId = document.getElementById('edit_class_course_id').value;
            const teacherId = document.getElementById('edit_class_teacher_id').value;
            const semesterId = document.getElementById('edit_class_semester_id').value;
            const className = document.getElementById('edit_class_name').value;
            
            console.log('Submitting edit class form:', {classId, courseId, teacherId, semesterId, className});
            
            if (!classId || !courseId || !teacherId || !semesterId || !className) {
                e.preventDefault();
                alert('Vui lòng nhập đầy đủ thông tin lớp học.');
                return false;
            }
        });
    }
    
    // Tự động mở modal sửa học phần nếu có tham số edit_course
    <?php if ($editCourse): ?>
    const editCourseData = <?php echo json_encode($editCourse); ?>;
    openEditCourseModal(editCourseData);
    <?php endif; ?>
    
    // Tự động mở modal sửa lớp học nếu có tham số edit_class
    <?php if ($editClass): ?>
    const editClassData = <?php echo json_encode($editClass); ?>;
    openEditClassModal(editClassData);
    <?php endif; ?>
});

// Hàm highlight từ khóa tìm kiếm
function highlightKeyword(keyword) {
    if (!keyword) return;
    
    const rows = document.querySelectorAll('.results-table tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            const text = cell.innerHTML;
            const regex = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            const highlighted = text.replace(regex, '<mark style="background-color: #fff59d; padding: 2px 4px; border-radius: 2px;">$1</mark>');
            if (highlighted !== text) {
                cell.innerHTML = highlighted;
            }
        });
    });
}

// Hàm xóa form tìm kiếm
function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('creditsFilter').value = '0';
    document.getElementById('searchForm').submit();
}

// Hàm decode HTML entities
function decodeHtml(html) {
    if (!html) return '';
    const txt = document.createElement('textarea');
    txt.innerHTML = html;
    return txt.value;
}

// Hàm set giá trị cho select và đảm bảo option tồn tại
function setSelectValue(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    // Tìm option với value tương ứng
    const option = Array.from(select.options).find(opt => opt.value == value);
    if (option) {
        select.value = value;
    } else {
        console.warn(`Option with value "${value}" not found in select "${selectId}"`);
    }
}

// Hàm mở modal sửa học phần
function openEditCourseModal(course) {
    let courseId, courseCode, courseName, credits, maxStudents, description;
    
    if (typeof course === 'object' && course !== null) {
        // Nhận object trực tiếp (từ URL parameter)
        courseId = course.id || '';
        courseCode = course.course_code || '';
        courseName = course.course_name || '';
        credits = course.credits || '';
        maxStudents = course.max_students || 50;
        description = course.description || '';
    } else if (course && course.getAttribute) {
        // Nhận element button (từ data attributes)
        const btn = course;
        // Sử dụng getAttribute để đảm bảo lấy đúng giá trị
        courseId = btn.getAttribute('data-course-id') || '';
        courseCode = decodeHtml(btn.getAttribute('data-course-code') || '');
        courseName = decodeHtml(btn.getAttribute('data-course-name') || '');
        // Đảm bảo credits là số
        const creditsAttr = btn.getAttribute('data-credits');
        credits = creditsAttr && creditsAttr !== '' ? parseInt(creditsAttr) : '';
        // Đảm bảo maxStudents là số
        const maxStudentsAttr = btn.getAttribute('data-max-students');
        maxStudents = maxStudentsAttr && maxStudentsAttr !== '' ? parseInt(maxStudentsAttr) : 50;
        description = decodeHtml(btn.getAttribute('data-description') || '');
    } else {
        console.error('Invalid course data provided to openEditCourseModal');
        return;
    }
    
    // Đảm bảo courseId là số
    if (courseId) {
        courseId = parseInt(courseId) || courseId;
    }
    
    // Set giá trị vào form - đảm bảo tất cả các trường đều được set
    const courseIdInput = document.getElementById('edit_course_id');
    const courseCodeInput = document.getElementById('edit_course_code');
    const courseNameInput = document.getElementById('edit_course_name');
    const creditsInput = document.getElementById('edit_credits');
    const maxStudentsInput = document.getElementById('edit_max_students');
    const descriptionInput = document.getElementById('edit_description');
    
    // Kiểm tra xem các input có tồn tại không
    if (!courseIdInput || !courseCodeInput || !courseNameInput || !creditsInput || !maxStudentsInput || !descriptionInput) {
        console.error('Form inputs not found in edit course modal');
        alert('Có lỗi xảy ra khi mở form sửa học phần. Vui lòng thử lại.');
        return;
    }
    
    // Set giá trị
    courseIdInput.value = courseId ? String(courseId) : '';
    courseCodeInput.value = courseCode || '';
    courseNameInput.value = courseName || '';
    creditsInput.value = credits ? String(credits) : '';
    maxStudentsInput.value = maxStudents ? String(maxStudents) : '50';
    descriptionInput.value = description || '';
    
    // Debug log để kiểm tra
    console.log('Edit course data:', {
        courseId, 
        courseCode, 
        courseName, 
        credits, 
        maxStudents, 
        description,
        'Form values after setting': {
            courseId: courseIdInput.value,
            courseCode: courseCodeInput.value,
            courseName: courseNameInput.value,
            credits: creditsInput.value,
            maxStudents: maxStudentsInput.value,
            description: descriptionInput.value
        }
    });
    
    // Hiển thị modal
    const modal = document.getElementById('editCourseModal');
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error('Edit course modal not found');
    }
}

// Hàm mở modal sửa lớp học
function openEditClassModal(classData) {
    let classId, courseId, teacherId, semesterId, className, schedule, room, maxStudents;
    
    if (typeof classData === 'object') {
        // Nhận object trực tiếp (từ URL parameter)
        classId = classData.id;
        courseId = classData.course_id || '';
        teacherId = classData.teacher_id || '';
        semesterId = classData.semester_id || '';
        className = classData.class_name || '';
        schedule = classData.schedule || '';
        room = classData.room || '';
        maxStudents = classData.max_students || 50;
    } else {
        // Nhận element button (từ data attributes)
        const btn = classData;
        classId = btn.dataset.classId || '';
        courseId = btn.dataset.courseId || '';
        teacherId = btn.dataset.teacherId || '';
        semesterId = btn.dataset.semesterId || '';
        className = decodeHtml(btn.dataset.className || '');
        schedule = decodeHtml(btn.dataset.schedule || '');
        room = decodeHtml(btn.dataset.room || '');
        maxStudents = btn.dataset.maxStudents || 50;
    }
    
    // Set giá trị vào form - đảm bảo các giá trị số là số
    document.getElementById('edit_class_id').value = classId;
    setSelectValue('edit_class_course_id', courseId);
    setSelectValue('edit_class_teacher_id', teacherId);
    setSelectValue('edit_class_semester_id', semesterId);
    document.getElementById('edit_class_name').value = className;
    document.getElementById('edit_class_schedule').value = schedule;
    document.getElementById('edit_class_room').value = room;
    document.getElementById('edit_class_max_students').value = maxStudents || 50;
    
    // Debug log để kiểm tra
    console.log('Edit class data:', {classId, courseId, teacherId, semesterId, className, schedule, room, maxStudents});
    
    // Kiểm tra xem các giá trị có được set đúng không
    setTimeout(() => {
        console.log('Form values after setting:', {
            classId: document.getElementById('edit_class_id').value,
            courseId: document.getElementById('edit_class_course_id').value,
            teacherId: document.getElementById('edit_class_teacher_id').value,
            semesterId: document.getElementById('edit_class_semester_id').value,
            className: document.getElementById('edit_class_name').value
        });
    }, 100);
    
    document.getElementById('editClassModal').style.display = 'block';
}

// Đóng modal khi click ra ngoài
window.onclick = function(event) {
    const editCourseModal = document.getElementById('editCourseModal');
    const editClassModal = document.getElementById('editClassModal');
    const addCourseModal = document.getElementById('addCourseModal');
    const addClassModal = document.getElementById('addClassModal');
    
    if (event.target == editCourseModal) {
        editCourseModal.style.display = 'none';
    }
    if (event.target == editClassModal) {
        editClassModal.style.display = 'none';
    }
    if (event.target == addCourseModal) {
        addCourseModal.style.display = 'none';
    }
    if (event.target == addClassModal) {
        addClassModal.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
