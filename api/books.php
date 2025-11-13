<?php
require_once '../config.php';
require_once '../functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get book details
        if (isset($_GET['id'])) {
            $book = getBookById($_GET['id']);
            if ($book) {
                echo json_encode($book);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Book not found']);
            }
        } else {
            // Get all books
            $books = getAllBooks();
            echo json_encode($books);
        }
        break;

    case 'POST':
        // Add new book
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }

        // Get form data
        $title = $_POST['title'] ?? '';
        $author_name = $_POST['author_name'] ?? '';
        $genre_name = $_POST['genre_name'] ?? '';
        $isbn = $_POST['isbn'] ?? '';
        $description = $_POST['description'] ?? '';
        $publication_date = $_POST['publication_date'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);

        // Validate required fields
        if (empty($title) || empty($author_name) || empty($genre_name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title, author, and genre are required']);
            exit;
        }

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Check if author exists, if not create it
            $stmt = $pdo->prepare("SELECT id FROM authors WHERE name = ?");
            $stmt->execute([$author_name]);
            $author = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$author) {
                $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                $stmt->execute([$author_name]);
                $author_id = $pdo->lastInsertId();
            } else {
                $author_id = $author['id'];
            }

            // Check if genre exists, if not create it
            $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = ?");
            $stmt->execute([$genre_name]);
            $genre = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$genre) {
                $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
                $stmt->execute([$genre_name]);
                $genre_id = $pdo->lastInsertId();
            } else {
                $genre_id = $genre['id'];
            }

            // Handle cover image upload
            // No default image — use icons on frontend when cover is null
            $cover_image = null;
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $uploaded_image = uploadBookCover($_FILES['cover']);
                if ($uploaded_image) {
                    $cover_image = $uploaded_image;
                }
            }

            // Insert book
            // Some databases use 'cover' column name; use 'cover' for compatibility
            $stmt = $pdo->prepare("INSERT INTO books (title, author_id, genre_id, isbn, description, cover, publication_date, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $title,
                $author_id,
                $genre_id,
                $isbn,
                $description,
                $cover_image,
                $publication_date,
                $quantity,
                $quantity
            ]);

            $book_id = $pdo->lastInsertId();

            // Commit transaction
            $pdo->commit();

            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Book added successfully',
                'book_id' => $book_id
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add book: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update book
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }

        parse_str(file_get_contents("php://input"), $_PUT);
        $id = $_PUT['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID is required']);
            exit;
        }

        // Update book logic here
        break;

    case 'DELETE':
        // Delete book
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Book ID is required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete book: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}