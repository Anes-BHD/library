<?php
require_once 'functions.php';

requireLogin();

$error = '';
$success = '';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$loans = getUserLoans($userId);

if (isset($_GET['return_loan']) && isset($_GET['loan_id'])) {
    $loanId = $_GET['loan_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM borrowings WHERE id = ? AND user_id = ?");
    $stmt->execute([$loanId, $userId]);
    $loan = $stmt->fetch();
    
    if ($loan) {
        $stmt = $pdo->prepare("UPDATE borrowings SET status = 'returned', return_date = NOW() WHERE id = ?");
        $stmt->execute([$loanId]);
        
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt->execute([$loan['book_id']]);
        
        header("Location: profile.php?success=returned");
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Tous les champs sont requis.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Le mot de passe actuel est incorrect.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        $success = "Votre mot de passe a été mis à jour avec succès.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque EPI - Mon Profil</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .profile-section {
            padding: 2rem 0;
        }
        .profile-card {
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            background-color: #f8f9fa;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .loan-card {
            transition: transform 0.3s;
        }
        .loan-card:hover {
            transform: translateY(-5px);
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-book-open me-2"></i>Bibliothèque EPI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Livres</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="author_management.php">Auteurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="genre_management.php">Genres</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="btn btn-light me-2 active">
                            <i class="fas fa-user me-1"></i>Mon Compte
                        </a>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-light me-2">Connexion</a>
                        <a href="register.php" class="btn btn-outline-light">Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- User Information -->
                <div class="col-md-4 mb-4">
                    <div class="card profile-card">
                        <div class="profile-header text-center">
                            <img src="<?php echo !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'uploads/users/user.png'; ?>" alt="Photo de profil" class="profile-avatar mb-3">
                            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                            <p class="text-muted">
                                <?php echo $user['is_admin'] ? 'Administrateur' : 'Utilisateur'; ?>
                            </p>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title mb-3">Informations personnelles</h5>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Membre depuis:</strong> <?php echo formatDate($user['created_at']); ?></p>
                            
                            <hr>
                            
                            <h5 class="card-title mb-3">Changer le mot de passe</h5>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary w-100">
                                    <i class="fas fa-key me-2"></i>Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Current Loans -->
                <div class="col-md-8">
                    <div class="card profile-card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                <i class="fas fa-book-reader me-2"></i>Mes emprunts en cours
                            </h4>
                            
                            <?php if (empty($loans)): ?>
                            <div class="alert alert-info">
                                Vous n'avez aucun emprunt en cours.
                            </div>
                            <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <?php foreach ($loans as $loan): ?>
                                <div class="col">
                                    <div class="card loan-card h-100">
                                        <div class="row g-0">
                                            <div class="col-4">
                                                <img src="<?php echo !empty($loan['cover_image']) ? htmlspecialchars($loan['cover_image']) : 'uploads/books/open-book.png'; ?>"
                                                     class="img-fluid rounded-start h-100"
                                                     alt="<?php echo htmlspecialchars($loan['book_title']); ?>"
                                                     style="object-fit: cover;">
                                            </div>
                                            <div class="col-8">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($loan['book_title']); ?></h5>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            Emprunté le: <?php echo formatDate($loan['borrow_date']); ?><br>
                                                            À retourner le: <?php echo formatDate($loan['return_date']); ?>
                                                        </small>
                                                    </p>
                                                    <a href="profile.php?return_loan=1&loan_id=<?php echo $loan['id']; ?>" 
                                                       class="btn btn-sm btn-primary"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir retourner ce livre ?')">
                                                        <i class="fas fa-undo"></i> Retourner
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Bibliothèque EPI</h5>
                    <p>Votre source de connaissances et de divertissement à Sousse.</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Accueil</a></li>
                        <li><a href="books.php" class="text-white">Livres</a></li>
                        <li><a href="author_management.php" class="text-white">Auteurs</a></li>
                        <li><a href="genre_management.php" class="text-white">Genres</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <address>
                        <p>École Polytechnique Internationale Sousse</p>
                        <p>Email: bibliotheque@epi.tn</p>
                        <p>Tél: +216 73 123 456</p>
                    </address>
                </div>
            </div>
            <hr class="bg-white">
            <div class="text-center">
                <p>&copy; 2024-2025 Bibliothèque EPI. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 