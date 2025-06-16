<?php
include '../auth/auth_check.php';
include '../config/db.php';

$day = date("l"); // current day like "Monday"
$allUsers = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Collect all users' occupied slots
$busy = [];
foreach ($allUsers as $user) {
    $stmt = $pdo->prepare("SELECT start_time, end_time FROM routines WHERE user_id = ? AND day = ?");
    $stmt->execute([$user['id'], $day]);
    $busy[$user['name']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Dummy logic: just say who has no class right now
$now = date("H:i");
$free = [];

foreach ($busy as $name => $times) {
    $available = true;
    foreach ($times as $slot) {
        if ($now >= $slot['start_time'] && $now <= $slot['end_time']) {
            $available = false;
            break;
        }
    }
    if ($available) $free[] = $name;
}

?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2>People Free Right Now (<?= $day ?> <?= $now ?>)</h2>
<ul>
    <?php foreach ($free as $person): ?>
        <li><?= htmlspecialchars($person) ?></li>
    <?php endforeach; ?>
</ul>

<?php include '../includes/footer.php'; ?>