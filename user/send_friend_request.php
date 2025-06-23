<?php
require '../auth/auth_check.php';
require '../config/db.php';

// Expect JSON input with friend_id
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['friend_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$friend_id = intval($data['friend_id']);
$user_id = $_SESSION['user_id'];

// Prevent sending to self
if ($user_id === $friend_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot send friend request to yourself.']);
    exit;
}

// Optional: check if friend_id exists in users table
$stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtCheckUser->execute([$friend_id]);
if (!$stmtCheckUser->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit;
}

// Check if a friendship record already exists in either direction
$stmt = $pdo->prepare("
    SELECT * FROM friends 
    WHERE (user_id = ? AND friend_id = ?)
       OR (user_id = ? AND friend_id = ?)
");
$stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
if ($stmt->fetch()) {
    // You may choose to respond with a message indicating already requested or already friends
    http_response_code(409);
    echo json_encode(['error' => 'Friend request already sent or you are already friends.']);
    exit;
}

// Insert friend request as pending
$insert = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
$insert->execute([$user_id, $friend_id]);

// Fetch sender name
$senderStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$senderStmt->execute([$user_id]);
$sender = $senderStmt->fetch();
$sender_name = $sender && $sender['name'] ? $sender['name'] : 'Someone';

// Insert a single notification: "<sender_name> sent you a friend request"
$notify = $pdo->prepare("
    INSERT INTO notifications (sender_id, receiver_id, message)
    VALUES (?, ?, ?)
");
$message = "$sender_name sent you a friend request";
$notify->execute([$user_id, $friend_id, $message]);

echo json_encode(['status' => 'request_sent']);
exit;