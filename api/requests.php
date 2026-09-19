<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// --- GET REQUEST: FETCH DASHBOARD REQUESTS ---[cite: 1]
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT r.exchange_id, r.status, r.request_date, b.title, u.name as requester_name 
        FROM requests r
        JOIN books b ON r.book_id = b.book_id
        JOIN users u ON r.requester_id = u.user_id
        WHERE r.owner_id = :owner_id ORDER BY r.exchange_id DESC
    ");
    $stmt->execute([':owner_id' => $userId]);
    $requests = $stmt->fetchAll();

    echo json_encode(["status" => "success", "data" => $requests]);
    exit;
}

// --- POST REQUEST: CREATE OR UPDATE REQUEST ---[cite: 1]
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? 'create';

    // Send New Request[cite: 1]
    if ($action === 'create') {
        $bookId = (int)($input['book_id'] ?? 0);

        // Fetch book owner[cite: 1]
        $bookStmt = $pdo->prepare("SELECT user_id FROM books WHERE book_id = :book_id");
        $bookStmt->execute([':book_id' => $bookId]);
        $book = $bookStmt->fetch();

        if (!$book) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Book not found."]);
            exit;
        }

        if ($book['user_id'] === $userId) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "You cannot request your own book."]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO requests (book_id, requester_id, owner_id, status) VALUES (:book_id, :requester_id, :owner_id, 'Pending')");
        $stmt->execute([
            ':book_id' => $bookId,
            ':requester_id' => $userId,
            ':owner_id' => $book['user_id']
        ]);

        echo json_encode(["status" => "success", "message" => "Exchange request submitted!"]);
        exit;
    }

    // Accept or Reject Request[cite: 1]
    if ($action === 'update_status') {
        $requestId = (int)($input['exchange_id'] ?? 0);
        $newStatus = $input['status'] ?? 'Pending'; // Approved or Rejected[cite: 1]

        $stmt = $pdo->prepare("UPDATE requests SET status = :status WHERE exchange_id = :exchange_id AND owner_id = :owner_id");
        $stmt->execute([
            ':status' => $newStatus,
            ':exchange_id' => $requestId,
            ':owner_id' => $userId
        ]);

        echo json_encode(["status" => "success", "message" => "Request updated to " . htmlspecialchars($newStatus)]);
        exit;
    }
}
?>