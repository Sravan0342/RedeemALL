-- Run on the DB VM:  sudo mysql
-- Adds the login table and a demo user (username: demo, password: demo123)
USE REDEEMALL;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, display_name, password_hash) VALUES
('demo', 'Demo User', '$2y$10$9YN5YAT2ZZjj3D8TI3jHY.lbWHD85nqVxlO4YC3DVxeivwxGj3ewa');

SELECT id, username, display_name FROM users;
