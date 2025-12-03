<?php
/**
 * Trang thông báo cho sinh viên
 * Hiển thị các thông báo từ hệ thống, giảng viên
 */

require_once '../config/db_connect.php';
require_once '../config/auth.php';

checkAccess(['student']);

$pageTitle = "Thông báo - Sinh viên";
$currentUser = getCurrentUser();

// Đánh dấu thông báo đã đọc
if (isset($_GET['read_id'])) {
    $notifId = intval($_GET['read_id']);
    $sqlUpdate = "UPDATE notifications SET is_read = 'yes' 
                  WHERE id = $notifId AND (receiver_id = {$currentUser['id']} OR role_target IN ('all', 'student'))";
    executeQuery($conn, $sqlUpdate);
    header("Location: thong_bao.php");
    exit();
}

// Lấy danh sách thông báo
$sqlNotifications = "SELECT n.*, u.full_name as sender_name, u.role as sender_role
                     FROM notifications n
                     INNER JOIN users u ON n.sender_id = u.id
                     WHERE (n.receiver_id = {$currentUser['id']} OR n.role_target IN ('all', 'student'))
                     ORDER BY n.created_at DESC";
$notifications = fetchAll($conn, $sqlNotifications);

// Đếm thông báo chưa đọc
$sqlUnreadCount = "SELECT COUNT(*) as count FROM notifications 
                   WHERE (receiver_id = {$currentUser['id']} OR role_target IN ('all', 'student'))
                   AND is_read = 'no'";
$unreadResult = fetchOne($conn, $sqlUnreadCount);
$unreadCount = $unreadResult['count'];

include '../includes/header.php';
?>

<!-- Header -->
<div class="main-header">
    <div class="page-title">
        <h1>Thông báo</h1>
        <p style="color: var(--text-secondary); margin-top: 15px;">
            Bạn có <strong style="color: var(--danger-color);"><?php echo $unreadCount; ?></strong> thông báo chưa đọc
        </p>
    </div>
</div>

<!-- Danh sách thông báo -->
<div class="card">
    <?php if (count($notifications) > 0): ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item" style="<?php echo $notif['is_read'] === 'no' ? 'background-color:rgb(229, 239, 245);' : ''; ?>">
                    <div class="icon">
                        <i class="fas <?php echo $notif['sender_role'] === 'admin' ? 'fa-user-shield' : 'fa-chalkboard-teacher'; ?>"></i>
                    </div>
                    <div class="content">
                        <div class="title">
                            <?php echo htmlspecialchars($notif['title']); ?>
                            <?php if ($notif['is_read'] === 'no'): ?>
                                <span class="status">
                                    <span class="new-badge">MỚI</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p style="margin: 8px 0; color: var(--text-primary);">
                            <?php echo nl2br(htmlspecialchars($notif['content'])); ?>
                        </p>
                        <div class="meta">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($notif['sender_name']); ?> • 
                            <i class="fas fa-clock"></i> 
                            <?php 
                                $date = new DateTime($notif['created_at']);
                                echo $date->format('d/m/Y H:i');
                            ?>
                            <?php if ($notif['is_read'] === 'no'): ?>
                                • <a href="?read_id=<?php echo $notif['id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                    <i class="fas fa-check"></i> Đánh dấu đã đọc
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 60px 20px;">
            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
            Chưa có thông báo nào.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
