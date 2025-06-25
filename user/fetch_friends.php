<?php
require '../auth/auth_check.php';
require '../config/db.php';

$user_id = $_SESSION['user_id'];

// ✅ Accepted Friends (Mutual)
$acceptedStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  WHERE u.id IN (
    SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'
    INTERSECT
    SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'
  )
");
$acceptedStmt->execute([$user_id, $user_id]);
$accepted = $acceptedStmt->fetchAll(PDO::FETCH_ASSOC);

// ⏳ Incoming Friend Requests
$pendingStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON u.id = f.user_id
  WHERE f.friend_id = ? AND f.status = 'pending'
");
$pendingStmt->execute([$user_id]);
$pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// 📤 Sent Friend Requests
$sentStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON u.id = f.friend_id
  WHERE f.user_id = ? AND f.status = 'pending'
");
$sentStmt->execute([$user_id]);
$sent = $sentStmt->fetchAll(PDO::FETCH_ASSOC);

// Final JSON Output
echo json_encode([
  'accepted' => $accepted,
  'pending' => $pending,
  'sent' => $sent
]);