<?php
require '../auth/auth_check.php';
require '../config/db.php';

$query = $_GET['query'] ?? '';
$user_id = $_SESSION['user_id'];
$like = "%$query%";

// Get up to 10 matching users (excluding current user)
$stmt = $pdo->prepare("
    SELECT id, name, email, profile_pic FROM users
    WHERE (name LIKE ? OR email LIKE ?)
      AND id != ?
    LIMIT 10
");
$stmt->execute([$like, $like, $user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing relationships
$relationStmt = $pdo->prepare("
    SELECT user_id, friend_id, status FROM friends
    WHERE (user_id = :uid OR friend_id = :uid)
");
$relationStmt->execute(['uid' => $user_id]);
$relations = $relationStmt->fetchAll(PDO::FETCH_ASSOC);

// Build map of relationships
$relationMap = [];
foreach ($relations as $rel) {
    $otherId = $rel['user_id'] == $user_id ? $rel['friend_id'] : $rel['user_id'];
    $relationMap[$otherId] = $rel['status']; // 'pending' or 'accepted'
}

// Add status info to users
foreach ($users as &$user) {
    $uid = $user['id'];
    if (isset($relationMap[$uid])) {
        $user['relation_status'] = $relationMap[$uid]; // pending or accepted
    } else {
        $user['relation_status'] = 'none';
    }
}

echo json_encode($users);