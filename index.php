<?php
require_once 'config.php';
require_once 'functions.php';

$featuredBooks = getFeaturedBooks();
$recentBooks = getRecentBooks();
$popularAuthors = getPopularAuthors();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque EPI - Accueil</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .hero-section {
            /* Replace background image with gradient */
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
            text-align: center;
        }
        .hero-icon {
            width: 140px;
            height: 140px;
            margin: 0 auto 20px auto;
            background: rgba(255,255,255,0.12);
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius: 16px;
            font-size: 3rem;
        }
        .book-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .book-card:hover {
            transform: translateY(-5px);
        }
        .book-cover {
            height: 300px;
            object-fit: cover;
        }
        .book-cover-icon {
            height: 300px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#fff;
            color:#6c757d;
        }
        .author-card {
            transition: transform 0.3s;
        }
        .author-card:hover {
            transform: translateY(-5px);
        }
        .author-photo {
            height: 200px;
            object-fit: cover;
        }
        .author-photo.icon {
            height:200px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#fff;
            color:#6c757d;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book-open me-2"></i>Bibliothèque EPI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Accueil</a>
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
                    <a href="register.php" class="btn btn-outline-light">Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Bienvenue à la Bibliothèque EPI</h1>
            <p class="lead mb-4">Découvrez notre vaste collection de livres et explorez le monde de la connaissance</p>
            <div class="hero-icon"><i class="fas fa-book-open"></i></div>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form action="books.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-lg me-2" placeholder="Rechercher un livre...">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="container mb-5">
        <h2 class="mb-4">Livres en Vedette</h2>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php foreach ($featuredBooks as $book): ?>
            <div class="col">
                <div class="card book-card h-100">
                    <div class="book-cover-icon card-img-top book-cover" aria-hidden="true"><i class="fas fa-book fa-3x"></i></div>
                     <div class="card-body">
                         <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                         <p class="card-text text-muted">Par <?php echo htmlspecialchars($book['author_name'] ?? ''); ?></p>
                         <button class="btn btn-primary" onclick="viewBook(<?php echo $book['id']; ?>)">Voir les détails</button>
                     </div>
                 </div>
             </div>
             <?php endforeach; ?>
         </div>
     </section>

     <div class="modal fade" id="viewBookModal" tabindex="-1">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header bg-info text-white">
                     <h5 class="modal-title">Détails du Livre</h5>
                     <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                 </div>
                 <div class="modal-body">
                     <div class="row">
                         <div class="col-md-4 text-center">
                            <div id="bookCover" class="book-cover-icon img-fluid mb-3" aria-hidden="true"><i class="fas fa-book fa-3x"></i></div>
                             <div class="d-grid gap-2">
                                 <button class="btn btn-success" type="button" id="borrowButton">Emprunter</button>
                             </div>
                         </div>
                         <div class="col-md-8">
                             <h3 id="bookTitle"></h3>
                             <p class="text-muted" id="bookAuthor"></p>
                             <hr>
                             <p><strong>Genre:</strong> <span id="bookGenre"></span></p>
                             <p><strong>ISBN:</strong> <span id="bookIsbn"></span></p>
                             <p><strong>Date de publication:</strong> <span id="bookPublicationDate"></span></p>
                             <p><strong>Statut:</strong> <span id="bookStatus"></span></p>
                             <p><strong>Description:</strong></p>
                             <p id="bookDescription"></p>
                             <h5 class="mt-4">Historique d'emprunts récents</h5>
                             <table class="table table-sm">
                                 <thead>
                                     <tr>
                                         <th>Utilisateur</th>
                                         <th>Date d'emprunt</th>
                                         <th>Date de retour</th>
                                     </tr>
                                 </thead>
                                 <tbody id="loanHistory">
                                 </tbody>
                             </table>
                         </div>
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                 </div>
             </div>
         </div>
     </div>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
     <script>
         const isLoggedIn = <?php echo json_encode(isLoggedIn()); ?>;
         function viewBook(id) {
             $.get(`api/books.php?id=${id}`, function(book) {
                 $('#bookAuthor').text(book.author_name ? 'Par ' + book.author_name : '');
                 $('#bookTitle').text(book.title);
                 $('#bookGenre').text(book.genre_name);
                 $('#bookIsbn').text(book.isbn);
                 $('#bookPublicationDate').text(book.publication_date ? new Date(book.publication_date).toLocaleDateString() : '-');
                 $('#bookDescription').text(book.description);
-                $('#bookCover').attr('src', book.cover_image || 'uploads/books/open-book.png');
+                // use icon placeholder instead of uploaded image
+                $('#bookCover').html('<i class="fas fa-book fa-3x"></i>');
                 const statusBadge = book.available_copies > 0 ? 
                     '<span class="badge bg-success">Disponible</span>' : 
                     '<span class="badge bg-danger">Emprunté</span>';
                 $('#bookStatus').html(statusBadge);
                 // Borrow button logic
                 if (!isLoggedIn) {
                     $('#borrowButton').removeClass('btn-success').addClass('btn-outline-primary').prop('disabled', false).text('Connectez-vous pour emprunter').off('click').on('click', function() {
                         window.location.href = 'login.php';
                     });
                 } else if (book.available_copies > 0) {
                     $('#borrowButton').removeClass('btn-outline-primary').addClass('btn-success').prop('disabled', false).text('Emprunter').off('click').on('click', function() {
                         $.post('api/borrowings.php', { book_id: book.id, user_id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?> }, function(response) {
                             alert('Livre emprunté avec succès!');
                             location.reload();
                         }).fail(function(xhr) {
                             alert('Erreur lors de l\'emprunt: ' + (xhr.responseJSON?.error || 'Une erreur est survenue'));
                         });
                     });
                 } else {
                     $('#borrowButton').removeClass('btn-success').addClass('btn-secondary').prop('disabled', true).text('Non disponible').off('click');
                 }
                 // Load loan history
                 $.get(`api/loans.php?book_id=${id}`, function(loans) {
                     const tbody = $('#loanHistory');
                     tbody.empty();
                     loans.forEach(loan => {
                         tbody.append(`
                             <tr>
                                 <td>${loan.user_name}</td>
                                 <td>${new Date(loan.loan_date).toLocaleDateString()}</td>
                                 <td>${loan.return_date ? new Date(loan.return_date).toLocaleDateString() : 'En cours'}</td>
                             </tr>
                         `);
                     });
                 });
                 $('#viewBookModal').modal('show');
             });
         }
     </script>

     <section class="container mb-5">
         <h2 class="mb-4">Auteurs Populaires</h2>
         <div class="row row-cols-1 row-cols-md-4 g-4">
             <?php foreach ($popularAuthors as $author): ?>
             <div class="col">
                 <div class="card author-card h-100">
-                    <img src="<?php echo !empty($author['photo']) ? htmlspecialchars($author['photo']) : 'uploads/users/user.png'; ?>" class="card-img-top author-photo" alt="<?php echo htmlspecialchars($author['name'] ?? ''); ?>">
+                    <div class="author-photo icon card-img-top" aria-hidden="true"><i class="fas fa-user fa-3x"></i></div>
                     <div class="card-body">
                         <h5 class="card-title"><?php echo htmlspecialchars($author['name'] ?? ''); ?></h5>
                         <p class="card-text"><?php echo $author['book_count']; ?> livre(s)</p>
                     </div>
                 </div>
             </div>
             <?php endforeach; ?>
         </div>
     </section>

    <footer class="footer mt-5">
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