<?php
include '../auth/auth_check.php';
include '../config/db.php';

$stmt = $pdo->prepare("SELECT day, start_time, end_time FROM routines WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$routines = $stmt->fetchAll(PDO::FETCH_ASSOC);

$summary = [];

foreach ($routines as $r) {
    $start = strtotime($r['start_time']);
    $end = strtotime($r['end_time']);
    $duration = ($end - $start) / 3600;

    if (!isset($summary[$r['day']])) $summary[$r['day']] = 0;
    $summary[$r['day']] += $duration;
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2>Weekly Analytics</h2>
<table border="1">
    <tr><th>Day</th><th>Total Class Hours</th></tr>
    <?php foreach ($summary as $day => $hours): ?>
        <tr><td><?= $day ?></td><td><?= $hours ?> hrs</td></tr>
    <?php endforeach; ?>
</table>

<?php include '../includes/footer.php'; ?>