<?php
require '../auth/auth_check.php';
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $user_id = $_SESSION['user_id'];

    // Ensure the notification belongs to this user
    $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$notification_id, $user_id]);

    // Redirect back to the notifications page
    header("Location: view_notifications.php");
    exit;
} else {
    // Invalid request, redirect with error
    header("Location: view_notifications.php?error=invalid");
    exit;
}