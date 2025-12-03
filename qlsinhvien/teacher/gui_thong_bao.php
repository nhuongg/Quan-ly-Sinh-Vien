<?php
/**
 * Gửi thông báo
 * Giảng viên gửi thông báo cho sinh viên
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['teacher']);

$pageTitle = "Gửi thông báo - Giảng viên";
$currentUser = getCurrentUser();

$error = '';
$success = '';

// Xử lý gửi thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $roleTarget = $_POST['role_target'] ?? 'student';
    
    if (empty($title) || empty($content)) {
        $error = 'Vui lòng nhập đầy đủ tiêu đề và nội dung thông báo.';
    } else {
        $title = escape_string($conn, $title);
        $content = escape_string($conn, $content);
        
        $sqlInsert = "INSERT INTO notifications (sender_id, role_target, title, content) 
                     VALUES ({$currentUser['id']}, '$roleTarget', '$title', '$content')";
        
        if (executeQuery($conn, $sqlInsert)) {
            $success = 'Gửi thông báo thành công!';
            $_POST = array(); // Clear form
        } else {
            $error = 'Có lỗi xảy ra khi gửi thông báo.';
        }
    }
}

// Lấy lịch sử thông báo đã gửi
$sqlHistory = "SELECT * FROM notifications 
               WHERE sender_id = {$currentUser['id']}
               ORDER BY created_at DESC
               LIMIT 10";
$history = fetchAll($conn, $sqlHistory);

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Gửi thông báo</h1>
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

<!-- Form gửi thông báo -->
<div class="card" style="margin-bottom: 32px;">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-paper-plane"></i> Soạn thông báo mới
    </h2>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="role_target">Gửi đến:</label>
            <select id="role_target" name="role_target" required>
                <option value="student">Tất cả sinh viên</option>
                <option value="all">Tất cả người dùng</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="title">Tiêu đề: <span style="color: var(--danger-color);">*</span></label>
            <input type="text" id="title" name="title" placeholder="Nhập tiêu đề thông báo" required 
                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="content">Nội dung: <span style="color: var(--danger-color);">*</span></label>
            <textarea id="content" name="content" placeholder="Nhập nội dung thông báo" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Gửi thông báo
            </button>
        </div>
    </form>
</div>

<!-- Lịch sử thông báo -->
<div class="card">
    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-history"></i> Lịch sử thông báo đã gửi
    </h2>
    
    <?php if (count($history) > 0): ?>
        <div class="table-container">
            <table class="results-table">
                <thead>
                    <tr>
                        <th style="width: 150px;">Thời gian</th>
                        <th style="width: 120px;">Gửi đến</th>
                        <th>Tiêu đề</th>
                        <th>Nội dung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $notif): ?>
                        <tr>
                            <td>
                                <?php 
                                    $date = new DateTime($notif['created_at']);
                                    echo $date->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if ($notif['role_target'] === 'all') echo '<span style="color: var(--primary-color); font-weight: 500;">Tất cả</span>';
                                    elseif ($notif['role_target'] === 'student') echo '<span style="color: #16a34a; font-weight: 500;">Sinh viên</span>';
                                    else echo 'Cá nhân';
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($notif['title']); ?></strong></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($notif['content'], 0, 100))); ?><?php echo strlen($notif['content']) > 100 ? '...' : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
            Chưa có thông báo nào được gửi.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
