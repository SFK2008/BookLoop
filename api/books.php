<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// --- GET REQUEST: SEARCH & FILTER LISTINGS ---
if ($method === 'GET') {
    $keyword = trim($_GET['keyword'] ?? '');
    $genre = trim($_GET['genre'] ?? 'all');
    $condition = trim($_GET['condition'] ?? 'all');

    // Base SQL query fetching books alongside linked note details
    $sql = "SELECT b.*, u.name as owner_name, n.note_id, n.file_name 
            FROM books b 
            JOIN users u ON b.user_id = u.user_id 
            LEFT JOIN notes n ON b.book_id = n.book_id 
            WHERE 1=1";

    $params = [];

    // Apply Keyword Search (Title or Author)
    if (!empty($keyword)) {
        $sql .= " AND (LOWER(b.title) LIKE :keyword OR LOWER(b.author) LIKE :keyword)";
        $params[':keyword'] = '%' . strtolower($keyword) . '%';
    }

    // Apply Genre Filter[cite: 1]
    if ($genre !== 'all' && !empty($genre)) {
        $sql .= " AND b.genre = :genre";
        $params[':genre'] = $genre;
    }

    // Apply Condition Filter[cite: 1]
    if ($condition !== 'all' && !empty($condition)) {
        $sql .= " AND b.condition = :condition";
        $params[':condition'] = $condition;
    }

    $sql .= " ORDER BY b.book_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

    echo json_encode(["status" => "success", "data" => $books]);
    exit;
}

// --- POST REQUEST: ADD NEW BOOK LISTING ---[cite: 1]
if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Please login."]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $title = trim($input['title'] ?? '');
    $author = trim($input['author'] ?? '');
    $genre = trim($input['genre'] ?? '');
    $condition = trim($input['condition'] ?? '');

    if (empty($title) || empty($author) || empty($genre) || empty($condition)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All book details are required."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO books (user_id, title, author, genre, condition, status) VALUES (:user_id, :title, :author, :genre, :condition, 'Available')");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':title' => $title,
        ':author' => $author,
        ':genre' => $genre,
        ':condition' => $condition
    ]);

    echo json_encode(["status" => "success", "message" => "Book listed successfully!"]);
    exit;
}
?>
