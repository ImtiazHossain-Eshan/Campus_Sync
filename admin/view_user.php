<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<p class='text-center text-red-600 mt-10'>User not found.</p>";
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="max-w-5xl mx-auto px-4 py-10">
  <div class="bg-white shadow-xl rounded-3xl overflow-hidden">
    <div class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 p-8 text-white relative">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <?php if (!empty($user['profile_pic'])): ?>
            <img src="../<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture"
              class="w-24 h-24 rounded-full border-4 border-white shadow-md object-cover" />
          <?php else: ?>
            <div class="w-24 h-24 flex items-center justify-center rounded-full border-4 border-white bg-white text-blue-600 font-bold text-2xl shadow-md">
              <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
          <?php endif; ?>
          <div>
            <h2 class="text-3xl font-bold"><?= htmlspecialchars($user['name']) ?></h2>
            <p class="text-sm opacity-90"><?= htmlspecialchars($user['email']) ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="p-8 grid gap-6 sm:grid-cols-2 text-gray-700 bg-gray-50">
      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500 mb-1">Phone</p>
        <p class="text-lg font-semibold"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500 mb-1">University</p>
        <p class="text-lg font-semibold"><?= htmlspecialchars($user['university'] ?? 'N/A') ?></p>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500 mb-1">Semester</p>
        <p class="text-lg font-semibold"><?= htmlspecialchars($user['semester'] ?? 'N/A') ?></p>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500 mb-1">Gender</p>
        <p class="text-lg font-semibold"><?= htmlspecialchars($user['gender'] ?? 'N/A') ?></p>
      </div>

      <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <p class="text-sm text-gray-500 mb-1">Department</p>
        <p class="text-lg font-semibold"><?= htmlspecialchars($user['department'] ?? 'N/A') ?></p>
      </div>

      <?php if (!empty($user['social'])): ?>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
          <p class="text-sm text-gray-500 mb-1">Social</p>
          <a href="<?= htmlspecialchars($user['social']) ?>" target="_blank" class="text-lg font-semibold text-blue-600 hover:underline">
            <?= htmlspecialchars($user['social']) ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>