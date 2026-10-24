<?php
// public/quick_login.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Handle 1-Click Login
if (isset($_GET['login_as'])) {
    $userId = (int)$_GET['login_as'];
    $stmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        header("Location: dashboard.html");
        exit;
    }
}

// Handle Password Reset to "123456"
$msg = "";
if (isset($_POST['reset_alice'])) {
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = :hash, email = 'alice@example.com' WHERE LOWER(email) = 'alice@example.com'")
        ->execute([':hash' => $hash]);
    $msg = "Alice's password has been set to: <strong>123456</strong> (lowercase: alice@example.com)";
}

// Fetch all users currently in database
$users = $pdo->query("SELECT user_id, name, email FROM users ORDER BY user_id ASC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BookLoop - Quick Login & Account Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 650px;">

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 p-4 mb-4">
        <h3 class="fw-bold mb-1">Instant 1-Click Login</h3>
        <p class="text-secondary small mb-3">Click below to log in directly into the dashboard without entering passwords:</p>

        <div class="list-group">
            <?php foreach ($users as $u): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <strong class="d-block"><?= htmlspecialchars($u['name']) ?></strong>
                        <span class="text-muted small"><?= htmlspecialchars($u['email']) ?> (ID: <?= $u['user_id'] ?>)</span>
                    </div>
                    <a href="quick_login.php?login_as=<?= $u['user_id'] ?>" class="btn btn-success fw-bold px-3">
                        Log In Directly →
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4">
        <h4 class="fw-bold mb-2">Want to use the regular Login Form?</h4>
        <p class="text-secondary small mb-3">Reset Alice's password to an easy known password:</p>
        <form method="POST">
            <button type="submit" name="reset_alice" class="btn btn-outline-primary w-100 py-2">
                Set Alice's Password to: 123456
            </button>
        </form>
        <div class="mt-3 text-center">
            <a href="login.html" class="small text-secondary">← Back to Login Page</a>
        </div>
    </div>

</div>
</body>
</html>
