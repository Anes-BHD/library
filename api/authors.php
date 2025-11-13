<?php
require_once '../config.php';
require_once '../functions.php';

// Detect AJAX requests
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    header('Content-Type: application/json');
}

if (!isLoggedIn()) {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
    } else {
        header('Location: ../login.php');
    }
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List authors or get one by id
        if (isset($_GET['id'])) {
            $author = getAuthorById($_GET['id']);
            if ($author) {
                if ($isAjax) echo json_encode($author);
            } else {
                if ($isAjax) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Author not found']);
                } else {
                    header('Location: ../author_management.php');
                }
            }
        } else {
            $authors = getAllAuthors();
            if ($isAjax) echo json_encode($authors);
        }
        break;

    case 'POST':
        if (!isAdmin()) {
            if ($isAjax) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden - Admin access required']);
            } else {
                header('Location: ../author_management.php');
            }
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
            if ($isAjax) {
                http_response_code(400);
                echo json_encode(['error' => 'Name is required']);
            } else {
                header('Location: ../author_management.php');
            }
            exit;
        }
        try {
            if ($id) {
                // Edit author
                $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
                $stmt->execute([$id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$existing) {
                    if ($isAjax) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Author not found']);
                    } else {
                        header('Location: ../author_management.php');
                    }
                    exit;
                }
                if (!$photo) $photo = $existing['photo'];
                $stmt = $pdo->prepare("UPDATE authors SET name=?, birth_date=?, nationality=?, biography=?, photo=? WHERE id=?");
                $stmt->execute([$name, $birth_date, $nationality, $biography, $photo, $id]);
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => 'Author updated']);
                } else {
                    header('Location: ../author_management.php');
                }
            } else {
                // Add author
                $stmt = $pdo->prepare("INSERT INTO authors (name, birth_date, nationality, biography, photo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $birth_date, $nationality, $biography, $photo]);
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => 'Author added']);
                } else {
                    // After successful form submit, redirect back to authors management page
                    header('Location: ../author_management.php');
                }
            }
        } catch (Exception $e) {
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save author: ' . $e->getMessage()]);
            } else {
                header('Location: ../author_management.php');
            }
        }
        break;

    case 'DELETE':
        if (!isAdmin()) {
            if ($isAjax) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden - Admin access required']);
            } else {
                header('Location: ../author_management.php');
            }
            exit;
        }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            if ($isAjax) {
                http_response_code(400);
                echo json_encode(['error' => 'Author ID is required']);
            } else {
                header('Location: ../author_management.php');
            }
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
            $stmt->execute([$id]);
            if ($isAjax) echo json_encode(['success' => true, 'message' => 'Author deleted']);
        } catch (Exception $e) {
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete author: ' . $e->getMessage()]);
            } else {
                header('Location: ../author_management.php');
            }
        }
        break;

    default:
        if ($isAjax) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        } else {
            header('Location: ../author_management.php');
        }
        break;
}