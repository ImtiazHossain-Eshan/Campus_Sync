<?php
require '../auth/auth_check.php';
require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$friend_id = $data['friend_id'];
$user_id = $_SESSION['user_id'];

// Accept request
$stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ?");
$stmt->execute([$friend_id, $user_id]);

// Insert reverse row to make mutual
$check = $pdo->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
$check->execute([$user_id, $friend_id]);
if (!$check->fetch()) {
    $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')")
        ->execute([$user_id, $friend_id]);
}

echo json_encode(['status' => 'accepted']);

// Notify sender about acceptance
$receiverStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$receiverStmt->execute([$user_id]);
$receiver = $receiverStmt->fetch();
$receiver_name = $receiver ? $receiver['name'] : 'Someone';

$notify = $pdo->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$notify->execute([$user_id, $friend_id, "$receiver_name accepted your friend request"]);