<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();

$genres = getAllGenres();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque EPI - Gestion des Genres</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        .table-actions {
            min-width: 100px;
        }
        .search-section {
            margin-bottom: 2rem;
        }
        .table-responsive {
            margin-top: 1rem;
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
                        <a class="nav-link active" href="genre_management.php">Genres</a>
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

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Gestion des Genres</h1>
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGenreModal">
                <i class="fas fa-plus me-1"></i> Ajouter un Genre
            </button>
            <?php endif; ?>
        </div>

        <!-- Search Section -->
        <div class="card search-section">
            <div class="card-body">
                <h5 class="card-title">Recherche</h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="search" placeholder="Nom du genre" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Genres Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Nombre de livres</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genres as $genre): ?>
                            <tr>
                                <td><?php echo $genre['id']; ?></td>
                                <td><?php echo htmlspecialchars((string)$genre['name']); ?></td>
                                <td><?php echo htmlspecialchars((string)$genre['description']); ?></td>
                                <td><?php echo isset($genre['book_count']) ? $genre['book_count'] : 0; ?></td>
                                <td class="table-actions">
                                    <?php if (isAdmin()): ?>
                                    <button class="btn btn-sm btn-warning" onclick="editGenre(<?php echo $genre['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteGenre(<?php echo $genre['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Genre Modal -->
    <?php if (isAdmin()): ?>
    <div class="modal fade" id="addGenreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Ajouter un Genre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addGenreForm" method="POST" action="api/genres.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="addGenreForm" class="btn btn-primary">Ajouter</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Genre Modal -->
    <?php if (isAdmin()): ?>
    <div class="modal fade" id="editGenreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Modifier le Genre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editGenreForm" method="POST" action="api/genres.php">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="editGenreForm" class="btn btn-warning">Modifier</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Bibliothèque EPI</h5>
                    <p>Votre source de connaissances et de divertissement à Sousse.</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5>Liens</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="index.php" class="text-dark">Accueil</a></li>
                        <li><a href="books.php" class="text-dark">Livres</a></li>
                        <li><a href="author_management.php" class="text-dark">Auteurs</a></li>
                        <li><a href="genre_management.php" class="text-dark">Genres</a></li>
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
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Function to edit genre
        function editGenre(id) {
            $.get(`api/genres.php?id=${id}`, function(genre) {
                $('#edit_id').val(genre.id);
                $('#edit_name').val(genre.name);
                $('#edit_description').val(genre.description);
                $('#editGenreModal').modal('show');
            });
        }

        // Function to delete genre
        function deleteGenre(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce genre ?')) {
                $.ajax({
                    url: `api/genres.php?id=${id}`,
                    method: 'DELETE',
                    success: function() {
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Erreur lors de la suppression: ' + xhr.responseJSON.error);
                    }
                });
            }
        }

        // Handle form submissions
        $('#addGenreForm').on('submit', function(e) {
            e.preventDefault();
            $.post($(this).attr('action'), $(this).serialize(), function() {
                location.reload();
            }).fail(function(xhr) {
                alert('Erreur: ' + xhr.responseJSON.error);
            });
        });

        $('#editGenreForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: 'PUT',
                data: $(this).serialize(),
                success: function() {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Erreur: ' + xhr.responseJSON.error);
                }
            });
        });
    </script>
</body>
</html>