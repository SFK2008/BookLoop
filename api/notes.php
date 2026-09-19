<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    
    if ($bookId <= 0 || empty($_FILES['note_file']['name'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please select a book and valid file."]);
        exit;
    }

    $file = $_FILES['note_file'];
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Security check: restrict upload file formats
    $allowedExtensions = ['pdf', 'txt'];
    if (!in_array($fileExt, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid format. Only PDF and TXT files are allowed."]);
        exit;
    }

    // Generate safe unique filename to avoid overwrites
    $safeFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
    $targetPath = '../uploads/' . $safeFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $stmt = $pdo->prepare("INSERT INTO notes (book_id, uploaded_by, file_name, file_type) VALUES (:book_id, :uploaded_by, :file_name, :file_type)");
        $stmt->execute([
            ':book_id' => $bookId,
            ':uploaded_by' => $_SESSION['user_id'],
            ':file_name' => $safeFileName,
            ':file_type' => $fileExt
        ]);

        echo json_encode(["status" => "success", "message" => "Digital note uploaded successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to save uploaded file."]);
    }
    exit;
}
?>