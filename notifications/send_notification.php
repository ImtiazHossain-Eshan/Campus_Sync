<?php
include '../auth/auth_check.php';
include '../config/db.php';

$receiver_id = $_POST['receiver_id'];
$message = htmlspecialchars($_POST['message']);

$stmt = $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);

echo "Notification sent.";
?>