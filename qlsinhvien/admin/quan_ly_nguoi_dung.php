<?php
/**
 * Quản lý người dùng
 * Quản trị viên quản lý tài khoản và phân quyền
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['admin']);

$pageTitle = "Quản lý người dùng - Quản trị viên";
$currentUser = getCurrentUser();

$error = '';
$success = '';

// Xử lý thêm người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Additional fields
    $code = trim($_POST['code'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $enrollmentYear = intval($_POST['enrollment_year'] ?? date('Y'));
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    
    if (empty($email) || empty($password) || empty($fullName) || empty($role)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
    } else {
        // Kiểm tra email đã tồn tại
        $email = escape_string($conn, $email);
        $sqlCheck = "SELECT * FROM users WHERE email = '$email'";
        if (fetchOne($conn, $sqlCheck)) {
            $error = 'Email này đã được sử dụng.';
        } else {
            // Thêm user
            $password = escape_string($conn, $password);
            $fullName = escape_string($conn, $fullName);
            $phone = escape_string($conn, $phone);
            $address = escape_string($conn, $address);
            
            $sqlInsertUser = "INSERT INTO users (email, password, role, full_name, phone, address) 
                             VALUES ('$email', '$password', '$role', '$fullName', '$phone', '$address')";
            
            if (executeQuery($conn, $sqlInsertUser)) {
                $userId = $conn->insert_id;
                
                // Thêm thông tin bổ sung theo role
                if ($role === 'student' && !empty($code)) {
                    $code = escape_string($conn, $code);
                    $class = escape_string($conn, $class);
                    $major = escape_string($conn, $major);
                    $sqlStudent = "INSERT INTO students (user_id, student_code, class, major, enrollment_year) 
                                  VALUES ($userId, '$code', '$class', '$major', $enrollmentYear)";
                    executeQuery($conn, $sqlStudent);
                } elseif ($role === 'teacher' && !empty($code)) {
                    $code = escape_string($conn, $code);
                    $department = escape_string($conn, $department);
                    $position = escape_string($conn, $position);
                    $specialization = escape_string($conn, $specialization);
                    $sqlTeacher = "INSERT INTO teachers (user_id, teacher_code, department, position, specialization) 
                                  VALUES ($userId, '$code', '$department', '$position', '$specialization')";
                    executeQuery($conn, $sqlTeacher);
                }
                
                $success = 'Thêm người dùng thành công!';
            } else {
                $error = 'Có lỗi xảy ra khi thêm người dùng.';
            }
        }
    }
}

// Xử lý xóa người dùng
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    if ($deleteId !== $currentUser['id']) {
        $sqlDelete = "DELETE FROM users WHERE id = $deleteId";
        if (executeQuery($conn, $sqlDelete)) {
            $success = 'Xóa người dùng thành công!';
        } else {
            $error = 'Có lỗi xảy ra khi xóa người dùng.';
        }
    } else {
        $error = 'Không thể xóa tài khoản của chính mình.';
    }
}

// Xử lý thay đổi trạng thái
if (isset($_GET['toggle_status'])) {
    $userId = intval($_GET['toggle_status']);
    $sqlToggle = "UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = $userId";
    executeQuery($conn, $sqlToggle);
    header("Location: quan_ly_nguoi_dung.php");
    exit();
}

// Filter
$roleFilter = $_GET['role_filter'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

// Lấy danh sách người dùng
$sqlUsers = "SELECT u.*,
             CASE 
                 WHEN u.role = 'student' THEN s.student_code
                 WHEN u.role = 'teacher' THEN t.teacher_code
                 ELSE NULL
             END as code,
             CASE 
                 WHEN u.role = 'student' THEN s.class
                 WHEN u.role = 'teacher' THEN t.department
                 ELSE NULL
             END as extra_info
             FROM users u
             LEFT JOIN students s ON u.id = s.user_id
             LEFT JOIN teachers t ON u.id = t.user_id
             WHERE 1=1";

if ($roleFilter !== 'all') {
    $sqlUsers .= " AND u.role = '" . escape_string($conn, $roleFilter) . "'";
}

if (!empty($searchQuery)) {
    $searchQuery = escape_string($conn, $searchQuery);
    $sqlUsers .= " AND (u.full_name LIKE '%$searchQuery%' OR u.email LIKE '%$searchQuery%')";
}

$sqlUsers .= " ORDER BY u.created_at DESC";
$users = fetchAll($conn, $sqlUsers);

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Quản lý người dùng</h1>
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

<!-- Filter và Search -->
<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="">
        <div class="filter-section">
            <div class="filter-group">
                <label for="role_filter">Lọc theo vai trò:</label>
                <select id="role_filter" name="role_filter" onchange="this.form.submit()">
                    <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Sinh viên</option>
                    <option value="teacher" <?php echo $roleFilter === 'teacher' ? 'selected' : ''; ?>>Giảng viên</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Tìm kiếm:</label>
                <input type="text" id="search" name="search" placeholder="Tên hoặc email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            
            <button type="submit" style="padding: 8px 16px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-search"></i> Tìm
            </button>
        </div>
    </form>
</div>

<!-- Nút thêm người dùng -->
<div style="margin-bottom: 16px; text-align: left;">
    <button onclick="document.getElementById('addUserModal').style.display='block'" 
            style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
        <i class="fas fa-plus"></i> Thêm người dùng
    </button>
</div>

<!-- Danh sách người dùng -->
<div class="card">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-users"></i> Danh sách người dùng (<?php echo count($users); ?>)
    </h2>
    
    <?php if (count($users) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Thông tin</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['code'] ?? '-'); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php 
                                    $roleText = '';
                                    $roleColor = '';
                                    if ($user['role'] === 'student') {
                                        $roleText = 'Sinh viên';
                                        $roleColor = '#3b82f6';
                                    } elseif ($user['role'] === 'teacher') {
                                        $roleText = 'Giảng viên';
                                        $roleColor = '#10b981';
                                    } else {
                                        $roleText = 'Quản trị viên';
                                        $roleColor = '#ef4444';
                                    }
                                    echo "<span style='color: $roleColor; font-weight: 500;'>$roleText</span>";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['extra_info'] ?? '-'); ?></td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span style="color: #16a34a; font-weight: 500;">Hoạt động</span>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-weight: 500;">Không hoạt động</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] !== $currentUser['id']): ?>
                                    <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                       style="color: <?php echo $user['status'] === 'active' ? '#16a34a' : '#999'; ?>; text-decoration: none; margin-right: 16px; font-size: 1.7em;"
                                       title="<?php echo $user['status'] === 'active' ? 'Tắt tài khoản' : 'Bật tài khoản'; ?>">
                                        <i class="fas <?php echo $user['status'] === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $user['id']; ?>" 
                                       onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')"
                                       style="color: var(--danger-color); text-decoration: none; font-size: 1.7em;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            Không tìm thấy người dùng nào.
        </p>
    <?php endif; ?>
</div>

<!-- Modal thêm người dùng -->
<div id="addUserModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; color: var(--primary-color);">
                <i class="fas fa-user-plus"></i> Thêm người dùng mới
            </h2>
            <span onclick="document.getElementById('addUserModal').style.display='none'" 
                  style="font-size: 28px; cursor: pointer; color: #999;">&times;</span>
        </div>
        
        <form method="POST" action="" id="addUserForm">
            <div class="form-group">
                <label>Vai trò: <span style="color: var(--danger-color);">*</span></label>
                <select name="role" id="roleSelect" required onchange="toggleRoleFields()">
                    <option value="">Chọn vai trò</option>
                    <option value="student">Sinh viên</option>
                    <option value="teacher">Giảng viên</option>
                    <option value="admin">Quản trị viên</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Email: <span style="color: var(--danger-color);">*</span></label>
                <input type="email" name="email" required placeholder="email@example.com">
            </div>
            
            <div class="form-group">
                <label>Mật khẩu: <span style="color: var(--danger-color);">*</span></label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu">
            </div>
            
            <div class="form-group">
                <label>Họ và tên: <span style="color: var(--danger-color);">*</span></label>
                <input type="text" name="full_name" required placeholder="Nguyễn Văn A">
            </div>
            
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="text" name="phone" placeholder="0987654321">
            </div>
            
            <div class="form-group">
                <label>Địa chỉ:</label>
                <input type="text" name="address" placeholder="Hà Nội">
            </div>
            
            <!-- Student fields -->
            <div id="studentFields" style="display: none;">
                <div class="form-group">
                    <label>Mã sinh viên: <span style="color: var(--danger-color);">*</span></label>
                    <input type="text" name="code" placeholder="SV2024001">
                </div>
                <div class="form-group">
                    <label>Lớp:</label>
                    <input type="text" name="class" placeholder="CNTT01-K16">
                </div>
                <div class="form-group">
                    <label>Chuyên ngành:</label>
                    <input type="text" name="major" placeholder="Công nghệ Thông tin">
                </div>
                <div class="form-group">
                    <label>Năm nhập học:</label>
                    <input type="number" name="enrollment_year" value="<?php echo date('Y'); ?>">
                </div>
            </div>
            
            <!-- Teacher fields -->
            <div id="teacherFields" style="display: none;">
                <div class="form-group">
                    <label>Mã giảng viên: <span style="color: var(--danger-color);">*</span></label>
                    <input type="text" name="code" placeholder="GV001">
                </div>
                <div class="form-group">
                    <label>Khoa:</label>
                    <input type="text" name="department" placeholder="Khoa Công nghệ Thông tin">
                </div>
                <div class="form-group">
                    <label>Chức vụ:</label>
                    <input type="text" name="position" placeholder="Giảng viên">
                </div>
                <div class="form-group">
                    <label>Chuyên môn:</label>
                    <input type="text" name="specialization" placeholder="Lập trình Web">
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 24px;">
                <button type="button" onclick="document.getElementById('addUserModal').style.display='none'" 
                        style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;">
                    Hủy
                </button>
                <button type="submit" name="add_user" 
                        style="padding: 10px 20px; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRoleFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('studentFields').style.display = role === 'student' ? 'block' : 'none';
    document.getElementById('teacherFields').style.display = role === 'teacher' ? 'block' : 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
