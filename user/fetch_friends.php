<?php
require '../auth/auth_check.php';
require '../config/db.php';

$user_id = $_SESSION['user_id'];

// Accepted Friends
$acceptedStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON u.id = f.friend_id
  WHERE f.user_id = ? AND f.status = 'accepted'
");
$acceptedStmt->execute([$user_id]);
$accepted = $acceptedStmt->fetchAll(PDO::FETCH_ASSOC);

// Pending Requests
$pendingStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON u.id = f.user_id
  WHERE f.friend_id = ? AND f.status = 'pending'
");
$pendingStmt->execute([$user_id]);
$pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'accepted' => $accepted,
  'pending' => $pending
]);