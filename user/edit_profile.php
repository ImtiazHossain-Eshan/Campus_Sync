<?php
include '../auth/auth_check.php';
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST['phone']);
    $social = trim($_POST['social']);
    $stmt = $pdo->prepare("UPDATE users SET phone = ?, social = ? WHERE id = ?");
    $stmt->execute([$phone, $social, $_SESSION['user_id']]);
    header("Location: profile.php");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2>Edit Profile</h2>
<form method="post">
    Phone Number: <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"><br>
    Social ID (Facebook, etc.): <input type="text" name="social" value="<?= htmlspecialchars($user['social'] ?? '') ?>"><br>
    <button type="submit">Update</button>
</form>

<?php include '../includes/footer.php'; ?>