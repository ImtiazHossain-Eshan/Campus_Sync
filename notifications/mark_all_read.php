<?php
require '../auth/auth_check.php';
require '../config/db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE receiver_id = ? AND read_at IS NULL");
$stmt->execute([$user_id]);

header("Location: view_notifications.php");
exit;