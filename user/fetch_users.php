<?php
require '../auth/auth_check.php';
require '../config/db.php';

$query = $_GET['query'] ?? '';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, name, email FROM users
    WHERE (name LIKE ? OR email LIKE ?)
      AND id != ?
    LIMIT 10
");
$like = "%$query%";
$stmt->execute([$like, $like, $user_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));