<?php
session_start();
require '../config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Invalid email address.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hashedPassword]);
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Email already exists or error occurred.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - CampusSync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-100 to-blue-50 min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl">
    <h2 class="text-3xl font-bold text-center text-blue-700 mb-6">Create Your Account</h2>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label for="name" class="block text-sm font-semibold text-gray-700">Full Name</label>
        <input type="text" id="name" name="name" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"/>
      </div>
      
      <div>
        <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
        <input type="email" id="email" name="email" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"/>
      </div>
      
      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
        <input type="password" id="password" name="password" required minlength="6" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"/>
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
        Register
      </button>
    </form>

    <p class="mt-6 text-sm text-center text-gray-500">
      Already have an account?
      <a href="login.php" class="text-blue-600 hover:underline font-medium">Login here</a>
    </p>
  </div>

</body>
</html>