<?php
include '../auth/auth_check.php';
include '../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE receiver_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2>Notifications</h2>
<ul>
    <?php foreach ($notes as $note): ?>
        <li><?= htmlspecialchars($note['message']) ?> <em>(<?= $note['created_at'] ?>)</em></li>
    <?php endforeach; ?>
</ul>

<?php include '../includes/footer.php'; ?>