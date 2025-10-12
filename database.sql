-- Create database first
CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

-- Create user and grant privileges AFTER database exists
CREATE USER IF NOT EXISTS 'library_user'@'%' IDENTIFIED BY 'admin12';
GRANT ALL PRIVILEGES ON library_db.* TO 'library_user'@'%';
FLUSH PRIVILEGES;

-- Now create tables
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS authors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    birth_date DATE,
    nationality VARCHAR(50),
    biography TEXT,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS genres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author_id INT,
    genre_id INT,
    isbn VARCHAR(13),
    description TEXT,
    cover VARCHAR(255),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS borrowings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP NOT NULL,
    returned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO users (name, email, password, is_admin) VALUES 
('Admin', 'admin@epi.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

INSERT INTO genres (name, description) VALUES 
('Roman', 'Œuvres de fiction narrative en prose'),
('Science-Fiction', 'Fiction basée sur des découvertes scientifiques et technologiques'),
('Policier', 'Romans mettant en scène des enquêtes criminelles'),
('Fantasy', 'Fiction mettant en scène des éléments surnaturels et magiques');

INSERT INTO authors (name, biography) VALUES 
('Victor Hugo', 'Écrivain français du XIXe siècle, auteur des Misérables'),
('J.K. Rowling', 'Auteure britannique de la série Harry Potter'),
('Agatha Christie', 'Auteure britannique de romans policiers'),
('Jules Verne', 'Écrivain français, pionnier de la science-fiction');