<?php
// Start session so $_SESSION['user_id'] persists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/db.php';

// Parse JSON payload or standard POST
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

// --- ACTION 1: REGISTER USER ---
if ($action === 'register') {
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please enter a valid email address."]);
        exit;
    }

    // Check if email already exists (case-insensitive)
    $check = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Email address already registered."]);
        exit;
    }

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
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}

// --- ACTION 2: LOGIN USER ---
if ($action === 'login') {
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email and password are required."]);
        exit;
    }

    // Case-insensitive email search
    $stmt = $pdo->prepare("SELECT user_id, name, email, password FROM users WHERE LOWER(email) = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Store user identifier inside server session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        echo json_encode([
            "status" => "success",
            "message" => "Login successful!",
            "user" => [
                "id" => $user['user_id'], 
                "name" => $user['name'], 
                "email" => $user['email']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    }
    exit;
}

// --- ACTION 3: LOGOUT ---
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(["status" => "success", "message" => "Logged out successfully."]);
    exit;
}

// Fallback if no valid action matched
http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid action specified."]);
exit;
