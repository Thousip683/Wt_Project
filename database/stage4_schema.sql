-- Beyond Classroom Database Schema
-- Stage 4: Career / Course Learning Module

USE beyond_classroom;

-- Courses table (online courses being tracked)
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    platform ENUM('Coursera', 'Udemy', 'YouTube', 'edX', 'LinkedIn Learning', 'Other') DEFAULT 'Other',
    category ENUM('AI', 'Machine Learning', 'Data Science', 'Web Development', 'DSA', 'DevOps', 'Cybersecurity', 'Other') DEFAULT 'Other',
    instructor VARCHAR(150),
    total_hours DECIMAL(6,2) DEFAULT 0,
    hours_completed DECIMAL(6,2) DEFAULT 0,
    start_date DATE,
    target_date DATE,
    status ENUM('Not Started', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Not Started',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    course_url VARCHAR(500),
    notes TEXT,
    color VARCHAR(7) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course topics / sections within each course
CREATE TABLE IF NOT EXISTS course_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    topic_name VARCHAR(200) NOT NULL,
    section_number INT DEFAULT 1,
    duration_minutes INT DEFAULT 0,
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_status (course_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learning sessions (daily logged learning time)
CREATE TABLE IF NOT EXISTS learning_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    topic_id INT,
    session_date DATE NOT NULL,
    duration_minutes INT NOT NULL,
    topics_covered TEXT,
    notes TEXT,
    productivity_rating ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES course_topics(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, session_date),
    INDEX idx_course_date (course_id, session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Skills being developed
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    category ENUM('Programming Language', 'Framework', 'Tool', 'Concept', 'Soft Skill', 'Other') DEFAULT 'Other',
    proficiency ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Beginner',
    course_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    INDEX idx_user_proficiency (user_id, proficiency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects built during learning
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    description TEXT,
    tech_stack VARCHAR(500),
    github_url VARCHAR(500),
    live_url VARCHAR(500),
    course_id INT,
    status ENUM('Planning', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Planning',
    start_date DATE,
    completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Career learning goals (daily/weekly/monthly)
CREATE TABLE IF NOT EXISTS career_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_type ENUM('Daily', 'Weekly', 'Monthly') DEFAULT 'Weekly',
    goal_description VARCHAR(255) NOT NULL,
    target_value INT NOT NULL COMMENT 'Hours or topics count',
    current_value INT DEFAULT 0,
    unit ENUM('Hours', 'Topics', 'Courses', 'Projects') DEFAULT 'Hours',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Active', 'Completed', 'Failed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
