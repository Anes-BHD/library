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
            $user = getUserById($_GET['id']);
            if ($user) {
                unset($user['password']);
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
            }
        } else {
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden - Admin access required']);
                exit;
            }
            $stmt = $pdo->query("SELECT id, name, email, is_admin, created_at FROM users ORDER BY name");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden - Admin access required']);
            exit;
        }
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;

        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, email and password are required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $is_admin]);
            echo json_encode(['success' => true, 'message' => 'User added']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add user: ' . $e->getMessage()]);
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
        $email = $_PUT['email'] ?? '';
        $is_admin = isset($_PUT['is_admin']) ? 1 : 0;
        $password = $_PUT['password'] ?? '';

        if (!$id || empty($name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID, name and email are required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hashed_password, $is_admin, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$name, $email, $is_admin, $id]);
            }
            echo json_encode(['success' => true, 'message' => 'User updated']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user: ' . $e->getMessage()]);
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
            echo json_encode(['error' => 'User ID is required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'User deleted']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 