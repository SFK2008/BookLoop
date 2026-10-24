<?php
/**
 * BookLoop - 100 Entries Database & PDF Seeder
 */

require_once __DIR__ . '/config/db.php';

// Target directory where actual PDF files will be stored
$uploadDir = __DIR__ . '/public/uploads/notes/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. Ensure we have at least a user to associate books with
$userStmt = $pdo->query("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 10");
$userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($userIds)) {
    // Insert fallback test users if the table is empty
    $pdo->exec("
        INSERT INTO users (name, email, password) VALUES 
        ('Alice Johnson', 'alice@example.com', '\$2y\$10\$e0MYzXyjpJS7Pd0RVvHwHe1z2.qK1uG8eG8uV3h4q8z5X8y5X8y5X'),
        ('Bob Smith', 'bob@example.com', '\$2y\$10\$e0MYzXyjpJS7Pd0RVvHwHe1z2.qK1uG8eG8uV3h4q8z5X8y5X8y5X')
    ");
    $userStmt = $pdo->query("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 10");
    $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
}

// 2. Helper function to generate a real, valid, single-page PDF binary
function generateValidPdf(string $title, string $author, string $genre, int $index): string {
    $clean = fn($str) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], substr($str, 0, 75));
    
    $stream = "BT\n"
            . "/F1 18 Tf 50 720 Td (" . $clean("BookLoop Notes: " . $title) . ") Tj\n"
            . "/F1 12 Tf 0 -30 Td (" . $clean("Author: " . $author) . ") Tj\n"
            . "0 -20 Td (" . $clean("Genre: " . $genre) . ") Tj\n"
            . "0 -20 Td (" . $clean("Document ID: BL-NOTE-" . str_pad((string)$index, 4, '0', STR_PAD_LEFT)) . ") Tj\n"
            . "0 -35 Td (Core Concepts & Study Guide:) Tj\n"
            . "0 -22 Td (" . $clean("- Comprehensive summary and key takeaways from chapters 1 through 10.") . ") Tj\n"
            . "0 -18 Td (" . $clean("- Detailed annotations, margin insights, and discussion talking points.") . ") Tj\n"
            . "0 -18 Td (" . $clean("- Verified for peer-to-peer exchange on the BookLoop platform.") . ") Tj\n"
            . "ET";
    $streamLen = strlen($stream);

    $pdf = "%PDF-1.4\n";
    $offsets = [];

    $offsets[1] = strlen($pdf);
    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

    $offsets[2] = strlen($pdf);
    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

    $offsets[3] = strlen($pdf);
    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";

    $offsets[4] = strlen($pdf);
    $pdf .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    $offsets[5] = strlen($pdf);
    $pdf .= "5 0 obj\n<< /Length $streamLen >>\nstream\n" . $stream . "\nendstream\nendobj\n";

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

// 3. Dataset of 100 unique real-world titles, authors, and genres
$rawBooks = [
    // Technology & CS (20)
    ['Clean Code', 'Robert C. Martin', 'Technology'],
    ['The Pragmatic Programmer', 'David Thomas', 'Technology'],
    ['Design Patterns', 'Erich Gamma', 'Technology'],
    ['Introduction to Algorithms', 'Thomas H. Cormen', 'Technology'],
    ['Designing Data-Intensive Applications', 'Martin Kleppmann', 'Technology'],
    ['Refactoring', 'Martin Fowler', 'Technology'],
    ['You Don’t Know JS Yet', 'Kyle Simpson', 'Technology'],
    ['Structure and Interpretation of Computer Programs', 'Harold Abelson', 'Technology'],
    ['The Mythical Man-Month', 'Fred Brooks', 'Technology'],
    ['Code Complete', 'Steve McConnell', 'Technology'],
    ['Cracking the Coding Interview', 'Gayle Laakmann McDowell', 'Technology'],
    ['Modern Operating Systems', 'Andrew S. Tanenbaum', 'Technology'],
    ['Computer Networks', 'Andrew S. Tanenbaum', 'Technology'],
    ['Compilers: Principles, Techniques, and Tools', 'Alfred V. Aho', 'Technology'],
    ['Artificial Intelligence: A Modern Approach', 'Stuart Russell', 'Technology'],
    ['Grokking Algorithms', 'Aditya Bhargava', 'Technology'],
    ['Clean Architecture', 'Robert C. Martin', 'Technology'],
    ['Fluent Python', 'Luciano Ramalho', 'Technology'],
    ['Rust Programming Language', 'Steve Klabnik', 'Technology'],
    ['Database System Concepts', 'Abraham Silberschatz', 'Technology'],

    // Fiction & Classics (20)
    ['Dune', 'Frank Herbert', 'Fiction'],
    ['1984', 'George Orwell', 'Fiction'],
    ['To Kill a Mockingbird', 'Harper Lee', 'Fiction'],
    ['The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction'],
    ['Brave New World', 'Aldous Huxley', 'Fiction'],
    ['The Catcher in the Rye', 'J.D. Salinger', 'Fiction'],
    ['Crime and Punishment', 'Fyodor Dostoevsky', 'Fiction'],
    ['Fahrenheit 451', 'Ray Bradbury', 'Fiction'],
    ['One Hundred Years of Solitude', 'Gabriel Garcia Marquez', 'Fiction'],
    ['The Brothers Karamazov', 'Fyodor Dostoevsky', 'Fiction'],
    ['The Hobbit', 'J.R.R. Tolkien', 'Fiction'],
    ['The Lord of the Rings', 'J.R.R. Tolkien', 'Fiction'],
    ['Jane Eyre', 'Charlotte Bronte', 'Fiction'],
    ['Wuthering Heights', 'Emily Bronte', 'Fiction'],
    ['The Alchemist', 'Paulo Coelho', 'Fiction'],
    ['Frankenstein', 'Mary Shelley', 'Fiction'],
    ['The Picture of Dorian Gray', 'Oscar Wilde', 'Fiction'],
    ['Animal Farm', 'George Orwell', 'Fiction'],
    ['The Metamorphosis', 'Franz Kafka', 'Fiction'],
    ['Slaughterhouse-Five', 'Kurt Vonnegut', 'Fiction'],

    // Science & Nature (20)
    ['A Brief History of Time', 'Stephen Hawking', 'Science'],
    ['Cosmos', 'Carl Sagan', 'Science'],
    ['The Selfish Gene', 'Richard Dawkins', 'Science'],
    ['Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Science'],
    ['The Origin of Species', 'Charles Darwin', 'Science'],
    ['Astrophysics for People in a Hurry', 'Neil deGrasse Tyson', 'Science'],
    ['The Elegant Universe', 'Brian Greene', 'Science'],
    ['Six Easy Pieces', 'Richard Feynman', 'Science'],
    ['Silent Spring', 'Rachel Carson', 'Science'],
    ['The Gene: An Intimate History', 'Siddhartha Mukherjee', 'Science'],
    ['The Emperor of All Maladies', 'Siddhartha Mukherjee', 'Science'],
    ['Guns, Germs, and Steel', 'Jared Diamond', 'Science'],
    ['The Structure of Scientific Revolutions', 'Thomas S. Kuhn', 'Science'],
    ['What If?', 'Randall Munroe', 'Science'],
    ['Seven Brief Lessons on Physics', 'Carlo Rovelli', 'Science'],
    ['The Double Helix', 'James D. Watson', 'Science'],
    ['Chaos: Making a New Science', 'James Gleick', 'Science'],
    ['The Order of Time', 'Carlo Rovelli', 'Science'],
    ['Thinking in Systems', 'Donella H. Meadows', 'Science'],
    ['The Demon-Haunted World', 'Carl Sagan', 'Science'],

    // Philosophy & Psychology (20)
    ['Meditations', 'Marcus Aurelius', 'Philosophy'],
    ['The Republic', 'Plato', 'Philosophy'],
    ['Beyond Good and Evil', 'Friedrich Nietzsche', 'Philosophy'],
    ['Critique of Pure Reason', 'Immanuel Kant', 'Philosophy'],
    ['Man’s Search for Meaning', 'Viktor E. Frankl', 'Philosophy'],
    ['The Stranger', 'Albert Camus', 'Philosophy'],
    ['The Myth of Sisyphus', 'Albert Camus', 'Philosophy'],
    ['Thinking, Fast and Slow', 'Daniel Kahneman', 'Philosophy'],
    ['Nicomachean Ethics', 'Aristotle', 'Philosophy'],
    ['Letters from a Stoic', 'Seneca', 'Philosophy'],
    ['Being and Time', 'Martin Heidegger', 'Philosophy'],
    ['The Social Contract', 'Jean-Jacques Rousseau', 'Philosophy'],
    ['Walden', 'Henry David Thoreau', 'Philosophy'],
    ['Thus Spoke Zarathustra', 'Friedrich Nietzsche', 'Philosophy'],
    ['The Prince', 'Niccolo Machiavelli', 'Philosophy'],
    ['The Art of War', 'Sun Tzu', 'Philosophy'],
    ['Tractatus Logico-Philosophicus', 'Ludwig Wittgenstein', 'Philosophy'],
    ['Enchiridion', 'Epictetus', 'Philosophy'],
    ['Civilization and Its Discontents', 'Sigmund Freud', 'Philosophy'],
    ['The Interpretation of Dreams', 'Sigmund Freud', 'Philosophy'],

    // Business, Economics & Self-Development (20)
    ['Atomic Habits', 'James Clear', 'Business'],
    ['The Lean Startup', 'Eric Ries', 'Business'],
    ['Zero to One', 'Peter Thiel', 'Business'],
    ['Good to Great', 'Jim Collins', 'Business'],
    ['The Psychology of Money', 'Morgan Housel', 'Business'],
    ['Deep Work', 'Cal Newport', 'Business'],
    ['Rich Dad Poor Dad', 'Robert T. Kiyosaki', 'Business'],
    ['Principles: Life and Work', 'Ray Dalio', 'Business'],
    ['The Intelligent Investor', 'Benjamin Graham', 'Business'],
    ['Outliers: The Story of Success', 'Malcolm Gladwell', 'Business'],
    ['Hooked', 'Nir Eyal', 'Business'],
    ['Start with Why', 'Simon Sinek', 'Business'],
    ['Thinking in Bets', 'Annie Duke', 'Business'],
    ['The Hard Thing About Hard Things', 'Ben Horowitz', 'Business'],
    ['Never Split the Difference', 'Chris Voss', 'Business'],
    ['Rework', 'Jason Fried', 'Business'],
    ['Essentialism', 'Greg McKeown', 'Business'],
    ['Freakonomics', 'Steven D. Levitt', 'Business'],
    ['Show Your Work!', 'Austin Kleon', 'Business'],
    ['Drive: The Surprising Truth About What Motivates Us', 'Daniel H. Pink', 'Business']
];

$conditions = ['New', 'Like New', 'Good', 'Fair'];
$statuses = ['Available', 'Available', 'Available', 'In Exchange'];

echo "Starting seeding process for 100 books and PDFs...\n";

$pdo->beginTransaction();

try {
    $bookStmt = $pdo->prepare('
        INSERT INTO books (user_id, title, author, genre, "condition", status)
        VALUES (:user_id, :title, :author, :genre, :condition, :status)
        RETURNING book_id
    ');

    $noteStmt = $pdo->prepare('
        INSERT INTO notes (book_id, uploaded_by, file_name, file_type, upload_date)
        VALUES (:book_id, :uploaded_by, :file_name, :file_type, CURRENT_DATE)
    ');

    $insertedCount = 0;

    foreach ($rawBooks as $index => $bookData) {
        $assignedUser = $userIds[$index % count($userIds)];
        $title = $bookData[0];
        $author = $bookData[1];
        $genre = $bookData[2];
        $condition = $conditions[array_rand($conditions)];
        $status = $statuses[array_rand($statuses)];

        // 1. Insert Book Record
        $bookStmt->execute([
            ':user_id'   => $assignedUser,
            ':title'     => $title,
            ':author'    => $author,
            ':genre'     => $genre,
            ':condition' => $condition,
            ':status'    => $status
        ]);
        $bookId = $bookStmt->fetchColumn();

        // 2. Generate Real PDF on Disk
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($title));
        $fileName = "notes_book_{$bookId}_{$slug}.pdf";
        $filePath = $uploadDir . $fileName;

        $pdfBinary = generateValidPdf($title, $author, $genre, $index + 1);
        file_put_contents($filePath, $pdfBinary);

        // 3. Insert Matching Note Record
        $noteStmt->execute([
            ':book_id'     => $bookId,
            ':uploaded_by' => $assignedUser,
            ':file_name'   => $fileName,
            ':file_type'   => 'application/pdf'
        ]);

        $insertedCount++;
        echo "[$insertedCount/100] Created '{$title}' -> PDF: {$fileName}\n";
    }

    $pdo->commit();
    echo "\nSuccess! Inserted 100 books and generated 100 valid PDF files in {$uploadDir}\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\nError during seeding: " . $e->getMessage() . "\n";
    exit(1);
}
