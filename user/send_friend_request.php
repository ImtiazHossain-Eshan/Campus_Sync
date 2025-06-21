<?php
require '../auth/auth_check.php';
require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$friend_id = $data['friend_id'];
$user_id = $_SESSION['user_id'];

// Prevent sending to self
if ($user_id == $friend_id) exit;

// Check if already exists
$stmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
$stmt->execute([$user_id, $friend_id]);
if ($stmt->fetch()) exit;

$insert = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
$insert->execute([$user_id, $friend_id]);

echo json_encode(['status' => 'request_sent']);

// Send notification to friend
$notify = $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$notify->execute([$user_id, $friend_id, "You received a friend request from $user_id"]);

$senderStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$senderStmt->execute([$user_id]);
$sender = $senderStmt->fetch();
$sender_name = $sender ? $sender['name'] : 'Someone';

$notify = $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$notify->execute([$user_id, $friend_id, "$sender_name sent you a friend request"]);