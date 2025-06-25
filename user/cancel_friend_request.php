<?php
require '../auth/auth_check.php';
require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$friend_id = $data['friend_id'];
$user_id = $_SESSION['user_id'];

// Delete the pending request
$stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
$stmt->execute([$user_id, $friend_id]);

echo json_encode(['status' => 'cancelled']);