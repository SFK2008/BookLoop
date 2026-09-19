<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

// Parse raw JSON payload or standard POST payload
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// --- ACTION 1: REGISTER USER ---
if ($action === 'register') {
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    // Securely hash password using standard BCRYPT hashing
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword
        ]);

        echo json_encode(["status" => "success", "message" => "Registration successful! You can now login."]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email address already registered."]);
    }
    exit;
}

// --- ACTION 2: LOGIN USER ---
if ($action === 'login') {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    $stmt = $pdo->prepare("SELECT user_id, name, email, password FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Store user identifier inside server session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];

        echo json_encode([
            "status" => "success",
            "message" => "Login successful!",
            "user" => ["id" => $user['user_id'], "name" => $user['name'], "email" => $user['email']]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    }
    exit;
}

// --- ACTION 3: LOGOUT ---
if ($action === 'logout') {
    session_destroy();
    echo json_encode(["status" => "success", "message" => "Logged out successfully."]);
    exit;
}
?>