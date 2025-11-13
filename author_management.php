<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
requireLogin();

$authors = getAllAuthors();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Auteurs - Bibliothèque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .author-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        .author-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #fff;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#6c757d;
        }
        .table-actions {
            min-width: 120px;
        }
    </style>
</head>
<body>
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
                        <a class="nav-link active" href="author_management.php">Auteurs</a>
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

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Auteurs</h1>
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAuthorModal">
                <i class="fas fa-plus me-2"></i>Ajouter un Auteur
            </button>
            <?php endif; ?>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3" method="GET" action="">
                    <div class="col-md-4">
                        <label for="searchName" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="searchName" name="search" placeholder="Rechercher par nom..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="searchNationality" class="form-label">Nationalité</label>
                        <select class="form-select" id="searchNationality" name="nationality">
                            <option value="">Toutes les nationalités</option>
                            <option value="fr" <?php echo (isset($_GET['nationality']) && $_GET['nationality'] === 'fr') ? 'selected' : ''; ?>>Française</option>
                            <option value="us" <?php echo (isset($_GET['nationality']) && $_GET['nationality'] === 'us') ? 'selected' : ''; ?>>Américaine</option>
                            <option value="uk" <?php echo (isset($_GET['nationality']) && $_GET['nationality'] === 'uk') ? 'selected' : ''; ?>>Britannique</option>
                            <option value="ru" <?php echo (isset($_GET['nationality']) && $_GET['nationality'] === 'ru') ? 'selected' : ''; ?>>Russe</option>
                            <option value="other" <?php echo (isset($_GET['nationality']) && $_GET['nationality'] === 'other') ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>Rechercher
                        </button>
                        <a href="author_management.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" width="5%">#</th>
                                <th scope="col" width="10%">Photo</th>
                                <th scope="col" width="20%">Nom</th>
                                <th scope="col" width="15%">Date de naissance</th>
                                <th scope="col" width="30%">Biographie</th>
                                <th scope="col" width="10%">Livres</th>
                                <?php if (isAdmin()): ?>
                                <th scope="col" width="10%" class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="authorsTableBody">
                            <?php if (empty($authors)): ?>
                            <tr>
                                <td colspan="<?php echo isAdmin() ? '7' : '6'; ?>" class="text-center">Aucun auteur trouvé</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($authors as $author): ?>
                            <tr>
                                <td><?php echo $author['id']; ?></td>
                                <td>
                                    <div class="author-icon" aria-hidden="true"><i class="fas fa-user"></i></div>
                                </td>
                                <td><?php echo htmlspecialchars($author['name']); ?></td>
                                <td><?php echo $author['birth_date'] ? date('d/m/Y', strtotime($author['birth_date'])) : '-'; ?></td>
                                <td><?php echo substr(htmlspecialchars($author['biography'] ?? ''), 0, 100) . '...'; ?></td>
                                <td><?php echo $author['total_books'] ?? 0; ?></td>
                                <?php if (isAdmin()): ?>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewAuthor(<?php echo $author['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editAuthor(<?php echo $author['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAuthor(<?php echo $author['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAuthorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un nouvel auteur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAuthorForm" method="POST" action="api/authors.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="authorName" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="authorName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="authorBirthdate" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="authorBirthdate" name="birth_date">
                        </div>
                        <div class="mb-3">
                            <label for="authorNationality" class="form-label">Nationalité</label>
                            <select class="form-select" id="authorNationality" name="nationality">
                                <option value="">Sélectionnez une nationalité</option>
                                <option value="fr">Française</option>
                                <option value="us">Américaine</option>
                                <option value="uk">Britannique</option>
                                <option value="ru">Russe</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="authorPhoto" class="form-label">Photo</label>
                            <input type="file" class="form-control" id="authorPhoto" name="photo" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="authorBiography" class="form-label">Biographie</label>
                            <textarea class="form-control" id="authorBiography" name="biography" rows="5"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="addAuthorForm" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAuthorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'auteur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAuthorForm" method="POST" action="api/authors.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="editAuthorId">
                        <div class="mb-3">
                            <label for="editAuthorName" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="editAuthorName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAuthorBirthdate" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="editAuthorBirthdate" name="birth_date">
                        </div>
                        <div class="mb-3">
                            <label for="editAuthorNationality" class="form-label">Nationalité</label>
                            <select class="form-select" id="editAuthorNationality" name="nationality">
                                <option value="">Sélectionnez une nationalité</option>
                                <option value="fr">Française</option>
                                <option value="us">Américaine</option>
                                <option value="uk">Britannique</option>
                                <option value="ru">Russe</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editAuthorPhoto" class="form-label">Photo</label>
                            <input type="file" class="form-control" id="editAuthorPhoto" name="photo" accept="image/*">
                            <div class="mt-2">
                                <div id="currentAuthorPhoto" class="author-icon" aria-hidden="true"></div>
                                <small class="text-muted ms-2">Photo actuelle</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editAuthorBiography" class="form-label">Biographie</label>
                            <textarea class="form-control" id="editAuthorBiography" name="biography" rows="5"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="editAuthorForm" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAuthorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet auteur ?</p>
                    <p class="text-danger">Cette action est irréversible et supprimera également tous les livres associés à cet auteur.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Bibliothèque EPI Sousse</h5>
                    <p>Application de gestion de bibliothèque développée pour le cours de Développement web avancé (PHP).</p>
                </div>
                <div class="col-md-3">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Accueil</a></li>
                        <li><a href="books.php" class="text-white">Livres</a></li>
                        <li><a href="author_management.php" class="text-white">Auteurs</a></li>
                        <li><a href="genre_management.php" class="text-white">Genres</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <address>
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>EPI Sousse, Tunisie</p>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i>contact@biblio.com</p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i>(+216) 73 123 456</p>
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2025 Bibliothèque EPI Sousse. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const isAdmin = <?php echo json_encode(isAdmin()); ?>;

        // Utility to escape HTML in JS when inserting into DOM
        function escapeHtml(unsafe) {
            return String(unsafe || '').replace(/[&<>"'`=\/]/g, function (s) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                    '`': '&#96;',
                    '=': '&#61;',
                    '/': '&#47;'
                })[s];
            });
        }

        // Refresh authors table by fetching from API and rebuilding tbody
        function refreshAuthors() {
            $.get('api/authors.php', function(data) {
                const tbody = $('#authorsTableBody');
                tbody.empty();
                if (!data || data.length === 0) {
                    const colspan = isAdmin ? 7 : 6;
                    tbody.append(`<tr><td colspan="${colspan}" class="text-center">Aucun auteur trouvé</td></tr>`);
                    return;
                }
                data.forEach(function(author) {
                    const id = author.id || '';
                    const name = escapeHtml(author.name || '');
                    const birth = author.birth_date ? new Date(author.birth_date).toLocaleDateString() : '-';
                    const bio = escapeHtml((author.biography || '').substring(0, 100)) + '...';
                    const books = author.total_books || 0;
                    const photoHtml = `<div class="author-icon" aria-hidden="true"><i class="fas fa-user"></i></div>`;
                    let actions = '';
                    if (isAdmin) {
                        actions = `
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewAuthor(${id})"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editAuthor(${id})"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAuthor(${id})"><i class="fas fa-trash"></i></button>
                            </td>`;
                    }
                    const row = `
                        <tr>
                            <td>${id}</td>
                            <td>${photoHtml}</td>
                            <td>${name}</td>
                            <td>${birth}</td>
                            <td>${bio}</td>
                            <td>${books}</td>
                            ${actions}
                        </tr>`;
                    tbody.append(row);
                });
            }).fail(function() {
                console.error('Failed to reload authors');
            });
        }

        // AJAX submit for addAuthorForm
        $('#addAuthorForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const fd = new FormData(form);
            $.ajax({
                url: 'api/authors.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(resp) {
                    $('#addAuthorModal').modal('hide');
                    // reset form
                    form.reset();
                    refreshAuthors();
                },
                error: function(xhr) {
                    alert('Erreur lors de l\'enregistrement: ' + (xhr.responseJSON?.error || 'Une erreur est survenue'));
                }
            });
        });

        // AJAX submit for editAuthorForm
        $('#editAuthorForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const fd = new FormData(form);
            $.ajax({
                url: 'api/authors.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(resp) {
                    $('#editAuthorModal').modal('hide');
                    refreshAuthors();
                },
                error: function(xhr) {
                    alert('Erreur lors de la mise à jour: ' + (xhr.responseJSON?.error || 'Une erreur est survenue'));
                }
            });
        });

        // Ensure actions still work after refresh
        window.viewAuthor = viewAuthor;
        window.editAuthor = editAuthor;
        window.deleteAuthor = deleteAuthor;

        // Initial load
        $(document).ready(function() {
            refreshAuthors();
        });
    </script>
</body>
</html>