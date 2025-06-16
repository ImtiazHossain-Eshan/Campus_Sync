<?php
include '../auth/auth_check.php';
include '../config/db.php';

// Get group members (for now assume all users in the system)
$members = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Load everyone's routine
$routines = [];
foreach ($members as $member) {
    $stmt = $pdo->prepare("SELECT * FROM routines WHERE user_id = ?");
    $stmt->execute([$member['id']]);
    $routines[$member['name']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2>Group Routines</h2>
<?php foreach ($routines as $user => $routineList): ?>
    <h3><?= htmlspecialchars($user) ?></h3>
    <ul>
        <?php foreach ($routineList as $r): ?>
            <li><?= htmlspecialchars($r['day']) ?>: <?= $r['start_time'] ?> - <?= $r['end_time'] ?> (<?= htmlspecialchars($r['course']) ?>)</li>
        <?php endforeach; ?>
    </ul>
<?php endforeach; ?>

<p><a href="../routines/check_freetime.php">Check Free Time Match</a></p>

<?php include '../includes/footer.php'; ?>