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
        // List authors or get one by id
        if (isset($_GET['id'])) {
            $author = getAuthorById($_GET['id']);
            if ($author) {
                echo json_encode($author);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Author not found']);
            }
        } else {
            $authors = getAllAuthors();
            echo json_encode($authors);
        }
        break;

    case 'POST':
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $birth_date = $_POST['birth_date'] ?? null;
        $nationality = $_POST['nationality'] ?? null;
        $biography = $_POST['biography'] ?? '';
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = uploadBookCover($_FILES['photo']);
        }
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }
        try {
            if ($id) {
                // Edit author
                $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$existing) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Author not found']);
                    exit;
                }
                if (!$photo) $photo = $existing['photo'];
                $stmt = $pdo->prepare("UPDATE authors SET name=?, birth_date=?, nationality=?, biography=?, photo=? WHERE id=?");
                $stmt->execute([$name, $birth_date, $nationality, $biography, $photo, $id]);
                echo json_encode(['success' => true, 'message' => 'Author updated']);
            } else {
                // Add author
                $stmt = $pdo->prepare("INSERT INTO authors (name, birth_date, nationality, biography, photo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $birth_date, $nationality, $biography, $photo]);
                echo json_encode(['success' => true, 'message' => 'Author added']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save author: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Author ID is required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Author deleted']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete author: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 