CREATE DATABASE IF NOT EXISTS barangay_system;
USE barangay_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'resident') DEFAULT 'resident',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,   -- Added this
    doc_type VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,             -- Added this
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin Password is 'admin123'
INSERT INTO users (username, password, role) 
VALUES ('admin', '$2y$10$wS2/i.fO4.k.U/yXg6.U0.qZ5.U0.qZ5.U0.qZ5.U0.qZ5', 'admin');