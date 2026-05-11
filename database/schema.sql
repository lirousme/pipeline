CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    text TEXT NOT NULL,
    home INT NOT NULL,
    gap INT NULL,
    disponibilidade DATETIME NULL,
    CONSTRAINT fk_problem_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conditionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_father_problem INT NOT NULL,
    id_next_problem INT NOT NULL,
    text TEXT NOT NULL,
    CONSTRAINT fk_conditional_father FOREIGN KEY (id_father_problem) REFERENCES problems(id) ON DELETE CASCADE,
    CONSTRAINT fk_conditional_next FOREIGN KEY (id_next_problem) REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
