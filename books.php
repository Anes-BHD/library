<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();

$search = $_GET['search'] ?? '';
$author_id = $_GET['author'] ?? '';
$genre_id = $_GET['genre'] ?? '';

$books = getAllBooks($search, $author_id, $genre_id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliothèque EPI - Gestion des Livres</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            min-width: 120px;
            white-space: nowrap;
        }
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
        }
        .search-section {
            margin-bottom: 2rem;
        }
        .table-responsive {
            margin-top: 1rem;
        }
        .book-status {
            width: 90px;
        }
        .book-cover {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .book-cover-modal {
            max-width: 200px;
            height: 300px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .table th {
            background-color: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
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
                        <a class="nav-link active" href="books.php">Livres</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="author_management.php">Auteurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="genre_management.php">Genres</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="profile.php" class="btn btn-light me-2">
                        <i class="fas fa-user me-1"></i>Mon Compte
                    </a>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Gestion des Livres</h1>
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-1"></i> Ajouter un Livre
            </button>
            <?php endif; ?>
        </div>

        <div class="card search-section">
            <div class="card-body">
                <h5 class="card-title">Recherche</h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Titre du livre" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="author">
                                <option value="">Tous les auteurs</option>
                                <?php foreach (getAllAuthors() as $author): ?>
                                <option value="<?php echo $author['id']; ?>" <?php echo (isset($_GET['author']) && $_GET['author'] == $author['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($author['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="genre">
                                <option value="">Tous les genres</option>
                                <?php foreach (getAllGenres() as $genre): ?>
                                <option value="<?php echo $genre['id']; ?>" <?php echo (isset($_GET['genre']) && $_GET['genre'] == $genre['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                        </div>
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
                                <th scope="col" width="35%">Titre</th>
                                <th scope="col" width="15%">Auteur</th>
                                <th scope="col" width="15%">Genre</th>
                                <th scope="col" width="10%">ISBN</th>
                                <th scope="col" width="10%">Statut</th>
                                <th scope="col" width="10%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Aucun livre trouvé</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($books as $book): ?>
                            <tr data-book-id="<?php echo $book['id']; ?>">
                                <td><?php echo $book['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $book['cover_image'] ?? 'uploads/books/open-book.png'; ?>" 
                                             alt="Couverture de <?php echo htmlspecialchars($book['title']); ?>" 
                                             class="book-cover me-3">
                                        <div>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                            <?php if (!empty($book['description'])): ?>
                                            <p class="text-muted small mb-0"><?php echo substr(htmlspecialchars($book['description']), 0, 100) . '...'; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($book['author_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($book['genre_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php echo $book['available_copies'] > 0 ? 'bg-success' : 'bg-danger'; ?> book-status">
                                        <?php echo $book['available_copies'] > 0 ? 'Disponible' : 'Emprunté'; ?>
                                    </span>
                                </td>
                                <td class="table-actions text-center">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewBook(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (isAdmin()): ?>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editBook(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBook(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (isAdmin()): ?>
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Ajouter un Livre</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addBookForm" method="POST" action="api/books.php" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Titre</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="author" class="form-label">Auteur</label>
                                <input type="text" class="form-control" id="author" name="author_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="genre" class="form-label">Genre</label>
                                <input type="text" class="form-control" id="genre" name="genre_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="publication_date" class="form-label">Date de publication</label>
                                <input type="date" class="form-control" id="publication_date" name="publication_date">
                            </div>
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cover" class="form-label">Image de couverture</label>
                            <input type="file" class="form-control" id="cover" name="cover" accept="image/*">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="addBookForm" class="btn btn-primary">Ajouter</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                            <img id="bookCover" src="" alt="Couverture du livre" class="book-cover-modal mb-3">
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
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function viewBook(id) {
            $.get(`api/books.php?id=${id}`, function(book) {
                $('#viewBookModal').data('bookId', book.id);
                
                $('#bookTitle').text(book.title);
                $('#bookAuthor').text('Par ' + book.author_name);
                $('#bookGenre').text(book.genre_name);
                $('#bookIsbn').text(book.isbn);
                $('#bookPublicationDate').text(new Date(book.publication_date).toLocaleDateString());
                $('#bookDescription').text(book.description || 'Aucune description disponible');
                $('#bookCover').attr('src', book.cover_image || 'uploads/books/open-book.png');
                
                const statusBadge = book.available_copies > 0 ? 
                    '<span class="badge bg-success">Disponible</span>' : 
                    '<span class="badge bg-danger">Emprunté</span>';
                $('#bookStatus').html(statusBadge);
                
                const borrowButton = $('#borrowButton');
                if (!<?php echo json_encode(isLoggedIn()); ?>) {
                    borrowButton
                        .removeClass('btn-success')
                        .addClass('btn-outline-primary')
                        .prop('disabled', false)
                        .text('Connectez-vous pour emprunter')
                        .off('click')
                        .on('click', function() {
                            window.location.href = 'login.php';
                        });
                } else if (book.available_copies > 0) {
                    borrowButton
                        .removeClass('btn-outline-primary')
                        .addClass('btn-success')
                        .prop('disabled', false)
                        .text('Emprunter')
                        .off('click')
                        .on('click', function() {
                            $.post('api/borrowings.php', { 
                                book_id: book.id
                            }, function(response) {
                                if (response.success) {
                                    const row = $(`tr[data-book-id="${book.id}"]`);
                                    const statusCell = row.find('td:nth-child(6)');
                                    statusCell.html('<span class="badge bg-danger">Emprunté</span>');
                                    
                                    $('#bookStatus').html('<span class="badge bg-danger">Emprunté</span>');
                                    
                                    $('#borrowButton')
                                        .removeClass('btn-success')
                                        .addClass('btn-secondary')
                                        .prop('disabled', true)
                                        .text('Non disponible')
                                        .off('click');
                                    
                                    book.available_copies = 0;
                                    
                                    alert('Livre emprunté avec succès!');
                                } else {
                                    alert('Erreur: ' + response.error);
                                }
                            }).fail(function(xhr) {
                                alert('Erreur lors de l\'emprunt: ' + (xhr.responseJSON?.error || 'Une erreur est survenue'));
                            });
                        });
                } else {
                    borrowButton
                        .removeClass('btn-success')
                        .addClass('btn-secondary')
                        .prop('disabled', true)
                        .text('Non disponible')
                        .off('click');
                }
                
                $('#viewBookModal').modal('show');
            });
        }

        function editBook(id) {
            $.get(`api/books.php?id=${id}`, function(book) {
                $('#editBookForm').data('id', id);
                $('#edit_title').val(book.title);
                $('#edit_isbn').val(book.isbn);
                $('#edit_author').val(book.author_id);
                $('#edit_genre').val(book.genre_id);
                $('#edit_description').val(book.description);
                $('#edit_publication_date').val(book.publication_date);
                $('#edit_quantity').val(book.total_copies);
                $('#currentCover').attr('src', book.cover || '/api/placeholder/150/200');
                
                $('#editBookModal').modal('show');
            });
        }

        function deleteBook(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')) {
                $.ajax({
                    url: `api/books.php?id=${id}`,
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

        $('#addBookForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            $.ajax({
                url: 'api/books.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.error);
                    }
                },
                error: function(xhr) {
                    alert('Erreur lors de l\'ajout du livre: ' + (xhr.responseJSON?.error || 'Une erreur est survenue'));
                }
            });
        });

        $('#borrowButton').on('click', function() {
            const bookId = $('#viewBookModal').data('bookId');
            $.post('api/loans.php', { book_id: bookId }, function() {
                location.reload();
            }).fail(function(xhr) {
                alert('Erreur lors de l\'emprunt: ' + xhr.responseJSON.error);
            });
        });
    </script>
</body>
</html> 