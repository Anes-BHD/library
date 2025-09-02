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
            $genre = getGenreById($_GET['id']);
            if ($genre) {
                echo json_encode($genre);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Genre not found']);
            }
        } else {
            $genres = getAllGenres();
            echo json_encode($genres);
        }
        break;

    case 'POST':
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO genres (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            echo json_encode(['success' => true, 'message' => 'Genre added']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add genre: ' . $e->getMessage()]);
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
        $name = $_PUT['name'] ?? '';
        $description = $_PUT['description'] ?? '';
        if (!$id || empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID and name are required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE genres SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            echo json_encode(['success' => true, 'message' => 'Genre updated']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update genre: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'Genre ID is required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM genres WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Genre deleted']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete genre: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 