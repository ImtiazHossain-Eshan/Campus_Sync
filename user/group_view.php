<?php
require_once '../config/db.php';
require_once '../auth/auth_check.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT g.id, g.name FROM groups g
JOIN group_members gm ON g.id = gm.group_id
WHERE gm.user_id = ?");
$stmt->execute([$user_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Your Groups</h2>
<?php foreach ($groups as $group): ?>
  <div class="p-4 bg-white rounded-lg shadow mb-4">
    <h3 class="text-xl font-semibold text-blue-700"><?= htmlspecialchars($group['name']) ?></h3>
    <a href="view_group_routine.php?group_id=<?= $group['id'] ?>" class="text-sm text-blue-500 underline">View Routine</a>
  </div>
<?php endforeach; ?>
