-- Beyond Classroom Database Schema
-- Stage 1: User Authentication and Basic Profile

-- Create database
CREATE DATABASE IF NOT EXISTS beyond_classroom;
USE beyond_classroom;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_course_semester (course, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample user for testing (password: test123)
-- This will only insert if the email doesn't already exist
INSERT IGNORE INTO users (full_name, email, password, course, semester) VALUES
('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'B.Tech', 3);

-- Note: The password hash above is for 'test123'
-- You can use this account for testing:
-- Email: test@example.com
-- Password: test123
