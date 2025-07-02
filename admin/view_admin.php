<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: manage_admins.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch();

if (!$admin) {
    echo "Admin not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Admin | CampusSync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
  <a href="manage_admins.php" class="mb-4 inline-block text-sm text-blue-600 hover:underline">← Back</a>
  <div class="bg-white p-6 rounded shadow max-w-xl mx-auto">
    <h2 class="text-2xl font-bold text-purple-700 mb-4">Admin Details</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($admin['name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?></p>
    <p><strong>Created At:</strong> <?= $admin['created_at'] ?></p>
  </div>
</body>
</html>