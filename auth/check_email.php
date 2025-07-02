<?php
require '../config/db.php';

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $exists = $stmt->fetchColumn();
    echo $exists ? "taken" : "available";
}
?>