<?php
include '../auth/auth_check.php';
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST['phone']);
    $social = trim($_POST['social']);
    $stmt = $pdo->prepare("UPDATE users SET phone = ?, social = ? WHERE id = ?");
    $stmt->execute([$phone, $social, $_SESSION['user_id']]);
    header("Location: profile.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="max-w-3xl mx-auto mt-10 px-6">
  <div class="bg-white p-8 rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-blue-700 mb-6">Edit Profile</h2>
    <form method="post" class="space-y-6">

      <div>
        <label class="block text-gray-700 mb-1" for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>

      <div>
        <label class="block text-gray-700 mb-1" for="social">Social ID (Facebook, etc.)</label>
        <input type="text" id="social" name="social" value="<?= htmlspecialchars($user['social'] ?? '') ?>"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400" />
      </div>

      <div class="text-right">
        <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-semibold shadow-md">
          Update
        </button>
      </div>
    </form>
  </div>
</main>

<?php include '../includes/footer.php'; ?>