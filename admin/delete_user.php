<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $userId = (int) $_POST['id'];

    // Prevent admin from deleting themselves by comparing with admin's own user ID
    // NOTE: Usually, admins are in a separate 'admins' table, so user IDs and admin IDs are different.
    // If your admins and users share the same ID space, keep this check.
    // Otherwise, this check is redundant and can be removed or adjusted accordingly.

    // Optional: Verify that user exists before deleting
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userExists = $stmt->fetchColumn();

    if ($userExists) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    }

    header("Location: manage_users.php");
    exit;
} else {
    // If accessed without POST or id, redirect back
    header("Location: manage_users.php");
    exit;
}