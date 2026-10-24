<?php
// public/fix.php
session_start();
require_once __DIR__ . '/../config/db.php';

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// 1. Ensure Alice exists with this password
$stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE LOWER(email) = 'alice@example.com'");
$stmt->execute([':hash' => $hash]);

if ($stmt->rowCount() === 0) {
    $insert = $pdo->prepare("INSERT INTO users (name, email, password) VALUES ('Alice Johnson', 'alice@example.com', :hash)");
    $insert->execute([':hash' => $hash]);
}

// 2. Fetch Alice's user details
$userStmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE LOWER(email) = 'alice@example.com' LIMIT 1");
$userStmt->execute();
$alice = $userStmt->fetch();

// 3. Automatically log Alice into the PHP session
$_SESSION['user_id']    = $alice['user_id'];
$_SESSION['user_name']  = $alice['name'];
$_SESSION['user_email'] = $alice['email'];

// 4. Redirect straight to the dashboard
header("Location: dashboard.html");
exit;
