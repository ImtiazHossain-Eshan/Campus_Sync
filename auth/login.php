<?php
session_start();
require '../config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Please enter a valid email.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../user/dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - CampusSync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-100 min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl">
    <h2 class="text-3xl font-bold text-center text-blue-700 mb-6">Welcome Back</h2>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
        <input type="email" id="email" name="email" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"/>
      </div>
      
      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
        <input type="password" id="password" name="password" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"/>
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
        Login
      </button>
    </form>

    <p class="mt-6 text-sm text-center text-gray-500">
      Don't have an account?
      <a href="register.php" class="text-purple-600 hover:underline font-medium">Register here</a>
    </p>
  </div>

</body>
</html>