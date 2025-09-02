<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("
                SELECT b.*, u.name as user_name, bk.title as book_title 
                FROM borrowings b 
                JOIN users u ON b.user_id = u.id 
                JOIN books bk ON b.book_id = bk.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($borrowing) {
                echo json_encode($borrowing);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Borrowing not found']);
            }
        } else {
            if (isAdmin()) {
                $stmt = $pdo->query("
                    SELECT b.*, u.name as user_name, bk.title as book_title 
                    FROM borrowings b 
                    JOIN users u ON b.user_id = u.id 
                    JOIN books bk ON b.book_id = bk.id 
                    ORDER BY b.borrow_date DESC
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT b.*, u.name as user_name, bk.title as book_title 
                    FROM borrowings b 
                    JOIN users u ON b.user_id = u.id 
                    JOIN books bk ON b.book_id = bk.id 
                    WHERE b.user_id = ? 
                    ORDER BY b.borrow_date DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        $user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? null;
        $book_id = $_POST['book_id'] ?? null;
        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+14 days')); // 2 weeks loan period

        if (!$user_id || !$book_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and Book ID are required']);
            exit;
        }

        try {
            if (!isBookAvailable($book_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Book is not available for borrowing']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT id FROM borrowings 
                WHERE user_id = ? AND book_id = ? AND status = 'borrowed'
            ");
            $stmt->execute([$user_id, $book_id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'You have already borrowed this book']);
                exit;
            }

            $pdo->beginTransaction();

            // Insert borrowing record
            $stmt = $pdo->prepare("
                INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status) 
                VALUES (?, ?, ?, ?, 'borrowed')
            ");
            $stmt->execute([$user_id, $book_id, $borrow_date, $due_date]);

            // Update book availability
            $stmt = $pdo->prepare("
                UPDATE books 
                SET available_copies = available_copies - 1 
                WHERE id = ?
            ");
            $stmt->execute([$book_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Book borrowed successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to borrow book: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }
        parse_str(file_get_contents("php://input"), $_PUT);
        $id = $_PUT['id'] ?? null;
        $status = $_PUT['status'] ?? '';
        $return_date = $_PUT['return_date'] ?? date('Y-m-d');

        if (!$id || empty($status)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID and status are required']);
            exit;
        }

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Get the borrowing record
            $stmt = $pdo->prepare("SELECT book_id, status FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$borrowing) {
                throw new Exception('Borrowing record not found');
            }

            // Update borrowing record
            $stmt = $pdo->prepare("
                UPDATE borrowings 
                SET status = ?, return_date = ? 
                WHERE id = ?
            ");
            $stmt->execute([$status, $return_date, $id]);

            // If returning the book, update book availability
            if ($status === 'returned' && $borrowing['status'] !== 'returned') {
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$borrowing['book_id']]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Borrowing updated successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update borrowing: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Borrowing ID is required']);
            exit;
        }
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Get the borrowing record
            $stmt = $pdo->prepare("SELECT book_id, status FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$borrowing) {
                throw new Exception('Borrowing record not found');
            }

            // Delete the borrowing record
            $stmt = $pdo->prepare("DELETE FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);

            // If the book was borrowed, update book availability
            if ($borrowing['status'] === 'borrowed') {
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$borrowing['book_id']]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Borrowing deleted successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete borrowing: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 