<?php
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_start();
}

require_once 'config.php';

/**
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is an admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}


function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}


function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Get user by email
 * @param string $email
 * @return array|false
 */
function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get user by ID
 * @param int $id
 * @return array|false
 */
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all books from the database
 * @return array
 */
function getAllBooks($search = '', $author_id = '', $genre_id = '') {
    global $pdo;
    $query = "
        SELECT b.*, a.name as author_name, g.name as genre_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN genres g ON b.genre_id = g.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (b.title LIKE ? OR a.name LIKE ? OR g.name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }
    
    if (!empty($author_id)) {
        $query .= " AND b.author_id = ?";
        $params[] = $author_id;
    }
    
    if (!empty($genre_id)) {
        $query .= " AND b.genre_id = ?";
        $params[] = $genre_id;
    }
    
    $query .= " ORDER BY b.title";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get book by ID
 * @param int $id
 * @return array|false
 */
function getBookById($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name, g.name as genre_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN genres g ON b.genre_id = g.id
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all authors from the database
 * @return array
 */
function getAllAuthors($search = '', $nationality = '') {
    global $pdo;
    $query = "
        SELECT a.*, COUNT(b.id) as total_books 
        FROM authors a 
        LEFT JOIN books b ON a.id = b.author_id 
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND a.name LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($nationality)) {
        $query .= " AND a.nationality = ?";
        $params[] = $nationality;
    }
    
    $query .= " GROUP BY a.id ORDER BY a.name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($authors as &$author) {
        if (!isset($author['total_books'])) $author['total_books'] = 0;
    }
    return $authors;
}

/**
 * Get author by ID
 * @param int $id
 * @return array|false
 */
function getAuthorById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all genres from the database
 * @return array
 */
function getAllGenres() {
    global $pdo;
    $stmt = $pdo->query("SELECT g.*, COUNT(b.id) as book_count FROM genres g LEFT JOIN books b ON g.id = b.genre_id GROUP BY g.id ORDER BY g.name");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($genres as &$genre) {
        if ($genre['name'] === null) $genre['name'] = '';
        if ($genre['description'] === null) $genre['description'] = '';
        if (!isset($genre['book_count'])) $genre['book_count'] = 0;
    }
    return $genres;
}

/**
 * Get genre by ID
 * @param int $id
 * @return array|false
 */
function getGenreById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM genres WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get books by author ID
 * @param int $authorId
 * @return array
 */
function getBooksByAuthor($authorId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name, g.name as genre_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN genres g ON b.genre_id = g.id
        WHERE b.author_id = ?
        ORDER BY b.title
    ");
    $stmt->execute([$authorId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get books by genre ID
 * @param int $genreId
 * @return array
 */
function getBooksByGenre($genreId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name, g.name as genre_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN genres g ON b.genre_id = g.id
        WHERE b.genre_id = ?
        ORDER BY b.title
    ");
    $stmt->execute([$genreId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Search books by title, author or genre
 * @param string $query
 * @return array
 */
function searchBooks($query) {
    global $pdo;
    $search = "%$query%";
    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name, g.name as genre_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN genres g ON b.genre_id = g.id
        WHERE b.title LIKE ? OR a.name LIKE ? OR g.name LIKE ?
        ORDER BY b.title
    ");
    $stmt->execute([$search, $search, $search]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Format date to display
 * @param string $date
 * @return string
 */
function formatDate($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

/**
 * Upload book cover image
 * @param array $file
 * @return string|null
 */
function uploadBookCover($file) {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $targetDir = UPLOAD_DIR . "/books/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        return null;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $targetFile;
    }
    
    return null;
}

/**
 * Get active loans for a user
 * @param int $userId
 * @return array
 */
function getUserLoans($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, bk.title as book_title
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ? AND b.status = 'borrowed'
        ORDER BY b.borrow_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if a book is available for loan
 * @param int $bookId
 * @return bool
 */
function isBookAvailable($bookId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result && $result['available_copies'] > 0;
}

/**
 * Get featured books
 * @param int $limit
 * @return array
 */
function getFeaturedBooks($limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        WHERE b.is_featured = 1
        ORDER BY b.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get recent books
 * @param int $limit
 * @return array
 */
function getRecentBooks($limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        ORDER BY b.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get popular authors
 * @param int $limit
 * @return array
 */
function getPopularAuthors($limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT a.*, COUNT(b.id) as book_count
        FROM authors a
        LEFT JOIN books b ON a.id = b.author_id
        GROUP BY a.id
        ORDER BY book_count DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Helper functions
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function setError($message) {
    $_SESSION['error'] = $message;
}

function getError() {
    $error = $_SESSION['error'] ?? null;
    unset($_SESSION['error']);
    return $error;
}

function setSuccess($message) {
    $_SESSION['success'] = $message;
}

function getSuccess() {
    $success = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    return $success;
}
?>