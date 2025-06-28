<?php
require '../auth/auth_check.php';
require '../config/db.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

// ✅ Accepted Friends (MySQL-compatible, mutual)
$acceptedStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON (
    (f.user_id = :uid AND f.friend_id = u.id) OR
    (f.friend_id = :uid AND f.user_id = u.id)
  )
  WHERE f.status = 'accepted'
");
$acceptedStmt->execute(['uid' => $user_id]);
$accepted = $acceptedStmt->fetchAll(PDO::FETCH_ASSOC);

// ⏳ Incoming Friend Requests
$pendingStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON u.id = f.user_id
  WHERE f.friend_id = :uid AND f.status = 'pending'
");
$pendingStmt->execute(['uid' => $user_id]);
$pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// 📤 Sent Friend Requests
$sentStmt = $pdo->prepare("
  SELECT u.id, u.name, u.email, u.profile_pic
  FROM users u
  JOIN friends f ON u.id = f.friend_id
  WHERE f.user_id = :uid AND f.status = 'pending'
");
$sentStmt->execute(['uid' => $user_id]);
$sent = $sentStmt->fetchAll(PDO::FETCH_ASSOC);

// Final JSON Output
echo json_encode([
  'accepted' => $accepted,
  'pending' => $pending,
  'sent' => $sent
]);