<?php
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? (bool)$_POST['is_admin'] : false;
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } else {
        $existingUser = getUserByEmail($email);
        if ($existingUser) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
            
            try {
                $stmt->execute([$name, $email, $hashedPassword, $is_admin]);
                $success = "Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.";
            } catch (PDOException $e) {
                $error = "Une erreur est survenue lors de la création du compte.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque EPI - Inscription</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .register-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/library-bg.jpg');
            background-size: cover;
            background-position: center;
            min-height: calc(100vh - 56px);
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .register-card {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
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
                        <a href="profile.php" class="btn btn-light me-2">
                            <i class="fas fa-user me-1"></i>Mon Compte
                        </a>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-light me-2">Connexion</a>
                        <a href="register.php" class="btn btn-outline-light active">Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Register Section -->
    <section class="register-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card register-card">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4">Inscription</h2>
                            
                            <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-success">Se connecter</a>
                                </div>
                            </div>
                            <?php else: ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom complet</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Type de compte</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_admin" id="userRole" value="0" checked>
                                        <label class="form-check-label" for="userRole">
                                            Utilisateur
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_admin" id="adminRole" value="1">
                                        <label class="form-check-label" for="adminRole">
                                            Administrateur
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Créer un compte
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">Vous avez déjà un compte ?</p>
                                <a href="login.php" class="btn btn-link">Se connecter</a>
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