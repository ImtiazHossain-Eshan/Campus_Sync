<?php
require '../auth/auth_check.php';
require '../config/db.php';

$user_id = $_SESSION['user_id'];

// Unread count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND read_at IS NULL");
$count_stmt->execute([$user_id]);
$count = $count_stmt->fetchColumn();

// Latest 5 notifications
$stmt = $pdo->prepare("SELECT n.message, n.read_at, n.created_at, u.name AS sender_name 
                       FROM notifications n
                       JOIN users u ON u.id = n.sender_id
                       WHERE n.receiver_id = ?
                       ORDER BY n.created_at DESC
                       LIMIT 5");
$stmt->execute([$user_id]);
$latest = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'unread_count' => (int)$count,
  'notifications' => $latest
]);