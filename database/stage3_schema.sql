-- Beyond Classroom Database Schema
-- Stage 3: Competitive Exam Preparation Module

USE beyond_classroom;

-- Competitive exams table (stores which exams user is preparing for)
CREATE TABLE IF NOT EXISTS competitive_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_name VARCHAR(100) NOT NULL,
    exam_full_name VARCHAR(200) NOT NULL,
    target_date DATE,
    status ENUM('Active', 'Paused', 'Completed') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam topics/subjects for each competitive exam
CREATE TABLE IF NOT EXISTS exam_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    topic_name VARCHAR(150) NOT NULL,
    subject_category VARCHAR(100),
    total_chapters INT DEFAULT 0,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES competitive_exams(id) ON DELETE CASCADE,
    INDEX idx_exam_status (exam_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Study sessions for exam preparation
CREATE TABLE IF NOT EXISTS study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    topic_id INT,
    session_date DATE NOT NULL,
    duration_minutes INT NOT NULL,
    topics_covered TEXT,
    notes TEXT,
    productivity_rating ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES competitive_exams(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES exam_topics(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, session_date),
    INDEX idx_exam_date (exam_id, session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goals for exam preparation (daily/weekly targets)
CREATE TABLE IF NOT EXISTS exam_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    goal_type ENUM('Daily', 'Weekly', 'Monthly') DEFAULT 'Weekly',
    goal_description VARCHAR(255) NOT NULL,
    target_value INT NOT NULL COMMENT 'Hours or topics count',
    current_value INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Active', 'Completed', 'Failed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES competitive_exams(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practice tests/mock tests
CREATE TABLE IF NOT EXISTS practice_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    test_name VARCHAR(200) NOT NULL,
    test_date DATE NOT NULL,
    total_questions INT,
    attempted_questions INT,
    correct_answers INT,
    score DECIMAL(5,2),
    time_taken_minutes INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES competitive_exams(id) ON DELETE CASCADE,
    INDEX idx_user_exam (user_id, exam_id),
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
