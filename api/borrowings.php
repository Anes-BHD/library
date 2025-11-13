<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Detect columns present in borrowings table
$colsStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
$colsStmt->execute([DB_NAME, 'borrowings']);
$borrowCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

// Determine a date column to use for ordering and inserts
$dateCandidates = ['borrow_date', 'borrowed_at', 'loan_date', 'created_at'];
$orderCol = null;
foreach ($dateCandidates as $c) {
    if (in_array($c, $borrowCols)) { $orderCol = $c; break; }
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("\n                SELECT b.*, u.name as user_name, bk.title as book_title \n                FROM borrowings b \n                JOIN users u ON b.user_id = u.id \n                JOIN books bk ON b.book_id = bk.id \n                WHERE b.id = ?\n            ");
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
                if ($orderCol) {
                    $stmt = $pdo->query("\n                    SELECT b.*, u.name as user_name, bk.title as book_title \n                    FROM borrowings b \n                    JOIN users u ON b.user_id = u.id \n                    JOIN books bk ON b.book_id = bk.id \n                    ORDER BY b.$orderCol DESC\n                ");
                } else {
                    $stmt = $pdo->query("\n                    SELECT b.*, u.name as user_name, bk.title as book_title \n                    FROM borrowings b \n                    JOIN users u ON b.user_id = u.id \n                    JOIN books bk ON b.book_id = bk.id \n                    ORDER BY b.id DESC\n                ");
                }
            } else {
                if ($orderCol) {
                    $stmt = $pdo->prepare("\n                    SELECT b.*, u.name as user_name, bk.title as book_title \n                    FROM borrowings b \n                    JOIN users u ON b.user_id = u.id \n                    JOIN books bk ON b.book_id = bk.id \n                    WHERE b.user_id = ? \n                    ORDER BY b.$orderCol DESC\n                ");
                } else {
                    $stmt = $pdo->prepare("\n                    SELECT b.*, u.name as user_name, bk.title as book_title \n                    FROM borrowings b \n                    JOIN users u ON b.user_id = u.id \n                    JOIN books bk ON b.book_id = bk.id \n                    WHERE b.user_id = ? \n                    ORDER BY b.id DESC\n                ");
                }
                $stmt->execute([$_SESSION['user_id']]);
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        $user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? null;
        $book_id = $_POST['book_id'] ?? null;
        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+14 days'));

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

            $stmt = $pdo->prepare("\n                SELECT id FROM borrowings \n                WHERE user_id = ? AND book_id = ? AND status = 'borrowed'\n            ");
            $stmt->execute([$user_id, $book_id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'You have already borrowed this book']);
                exit;
            }

            $pdo->beginTransaction();

            // Build INSERT dynamically based on available columns
            $fields = ['user_id', 'book_id'];
            $values = [$user_id, $book_id];

            // status column
            if (in_array('status', $borrowCols)) {
                $fields[] = 'status';
                $values[] = 'borrowed';
            }
            // borrow date variations
            foreach (['borrow_date','borrowed_at','loan_date'] as $dcol) {
                if (in_array($dcol, $borrowCols)) {
                    $fields[] = $dcol;
                    $values[] = $borrow_date;
                    break;
                }
            }
            // due date
            if (in_array('due_date', $borrowCols)) {
                $fields[] = 'due_date';
                $values[] = $due_date;
            }

            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $fieldList = implode(', ', $fields);

            $stmt = $pdo->prepare("INSERT INTO borrowings ($fieldList) VALUES ($placeholders)");
            $stmt->execute($values);

            // Update book availability if column exists
            if (in_array('available_copies', (array)$pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DB_NAME."' AND TABLE_NAME = 'books'")->fetchAll(PDO::FETCH_COLUMN))) {
                $stmt = $pdo->prepare("\n                UPDATE books \n                SET available_copies = available_copies - 1 \n                WHERE id = ?\n            ");
                $stmt->execute([$book_id]);
            }

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
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT book_id, status FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$borrowing) {
                throw new Exception('Borrowing record not found');
            }

            // Update borrowing record
            // Include return_date column if exists, otherwise just update status
            if (in_array('return_date', $borrowCols)) {
                $stmt = $pdo->prepare("\n                UPDATE borrowings \n                SET status = ?, return_date = ? \n                WHERE id = ?\n            ");
                $stmt->execute([$status, $return_date, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE borrowings SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
            }

            // If returning the book, update book availability
            if ($status === 'returned' && $borrowing['status'] !== 'returned') {
                if (in_array('available_copies', (array)$pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DB_NAME."' AND TABLE_NAME = 'books'")->fetchAll(PDO::FETCH_COLUMN))) {
                    $stmt = $pdo->prepare("\n                    UPDATE books \n                    SET available_copies = available_copies + 1 \n                    WHERE id = ?\n                ");
                    $stmt->execute([$borrowing['book_id']]);
                }
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
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT book_id, status FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$borrowing) {
                throw new Exception('Borrowing record not found');
            }

            $stmt = $pdo->prepare("DELETE FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);

            if ($borrowing['status'] === 'borrowed') {
                if (in_array('available_copies', (array)$pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DB_NAME."' AND TABLE_NAME = 'books'")->fetchAll(PDO::FETCH_COLUMN))) {
                    $stmt = $pdo->prepare("\n                    UPDATE books \n                    SET available_copies = available_copies + 1 \n                    WHERE id = ?\n                ");
                    $stmt->execute([$borrowing['book_id']]);
                }
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