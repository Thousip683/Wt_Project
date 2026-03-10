-- Beyond Classroom - Stage 4 Test Data
-- Uses variables so auto-increment IDs are always consistent

USE beyond_classroom;

-- ============================================================
-- COURSES
-- ============================================================
INSERT INTO courses (user_id, course_name, platform, category, instructor, total_hours, hours_completed, start_date, target_date, status, progress_percentage, course_url, notes, color) VALUES
(2, 'Machine Learning A-Z', 'Udemy', 'Machine Learning', 'Kirill Eremenko', 44.5, 18.0, '2026-01-10', '2026-04-30', 'In Progress', 40.45, 'https://udemy.com', 'Great course', '#6366f1'),
(2, 'CS50 Python', 'edX', 'AI', 'David Malan', 10.0, 10.0, '2025-12-01', '2025-12-31', 'Completed', 100.00, 'https://cs50.harvard.edu', 'Completed.', '#10b981'),
(2, 'React & Node.js Full Stack', 'Udemy', 'Web Development', 'Maximilian Schwarzmüller', 65.0, 5.5, '2026-03-01', '2026-07-01', 'In Progress', 8.46, 'https://udemy.com', 'Just started', '#f59e0b'),
(2, 'Deep Learning Specialization', 'Coursera', 'AI', 'Andrew Ng', 80.0, 0.0, '2026-05-01', '2026-09-30', 'Not Started', 0.00, 'https://coursera.org', 'Planned', '#3b82f6');

-- Store course IDs for use below
SET @ml_course  = LAST_INSERT_ID() - 3;
SET @cs50       = LAST_INSERT_ID() - 2;
SET @react      = LAST_INSERT_ID() - 1;

-- ============================================================
-- COURSE TOPICS
-- ============================================================
INSERT INTO course_topics (course_id, topic_name, section_number, duration_minutes, status, progress_percentage, notes) VALUES
(@ml_course, 'Data Preprocessing',        1, 60, 'Completed',   100.00, 'Feature scaling, encoding'),
(@ml_course, 'Simple Linear Regression',  2, 45, 'Completed',   100.00, 'OLS method'),
(@ml_course, 'Multiple Linear Regression',3, 50, 'Completed',   100.00, 'Backward elimination'),
(@ml_course, 'Polynomial Regression',     4, 40, 'Completed',   100.00, 'Degree selection'),
(@ml_course, 'Logistic Regression',       5, 55, 'In Progress',  60.00, 'On confusion matrix section'),
(@ml_course, 'K-Nearest Neighbors',       6, 35, 'Not Started',   0.00, NULL),
(@ml_course, 'Support Vector Machine',    7, 50, 'Not Started',   0.00, NULL),
(@ml_course, 'Naive Bayes',               8, 40, 'Not Started',   0.00, NULL);

-- Store topic ID of logistic regression topic
SET @lr_topic = LAST_INSERT_ID() - 3;
SET @t1 = @lr_topic - 4;  -- Data Preprocessing topic id

-- ============================================================
-- LEARNING SESSIONS
-- ============================================================
INSERT INTO learning_sessions (user_id, course_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
(2, @ml_course, @t1,       '2026-03-01', 120, 'Data Preprocessing',        'Good session', 'High'),
(2, @ml_course, @t1+1,     '2026-03-02', 90,  'Simple Linear Regression',  'OLS derivation', 'High'),
(2, @ml_course, @t1+2,     '2026-03-03', 60,  'Multiple Linear Regression','Quick session', 'Medium'),
(2, @ml_course, @t1+3,     '2026-03-04', 75,  'Polynomial Regression',     'Degree selection', 'High'),
(2, @react,     NULL,       '2026-03-05', 45,  'React hooks intro',         'useState/useEffect', 'Medium'),
(2, @ml_course, @lr_topic, '2026-03-06', 90,  'Logistic Regression pt 1',  'Sigmoid function', 'High'),
(2, @react,     NULL,       '2026-03-07', 60,  'React components',          'Component lifecycle', 'Medium'),
(2, @ml_course, @lr_topic, '2026-03-08', 45,  'Logistic Regression pt 2',  'Confusion matrix', 'Low');

-- ============================================================
-- SKILLS
-- ============================================================
INSERT INTO skills (user_id, skill_name, category, proficiency, course_id, notes) VALUES
(2, 'Python',      'Programming Language', 'Advanced',     @ml_course, 'Used in ML A-Z'),
(2, 'scikit-learn','Framework',            'Intermediate', @ml_course, 'Regression & classification'),
(2, 'NumPy',       'Framework',            'Intermediate', @ml_course, 'Array ops'),
(2, 'Pandas',      'Framework',            'Intermediate', NULL,       'Data manipulation'),
(2, 'JavaScript',  'Programming Language', 'Intermediate', @react,     'ES6+'),
(2, 'React',       'Framework',            'Beginner',     @react,     'Just started'),
(2, 'MySQL',       'Tool',                 'Intermediate', NULL,       'Queries, joins, indexing'),
(2, 'Git',         'Tool',                 'Intermediate', NULL,       'Version control');

-- ============================================================
-- PROJECTS
-- ============================================================
INSERT INTO projects (user_id, project_name, description, tech_stack, github_url, live_url, course_id, status, start_date, completion_date) VALUES
(2, 'Salary Predictor',           'Salary prediction using Linear Regression', 'Python, scikit-learn, matplotlib', 'https://github.com/user/salary-predictor', NULL, @ml_course, 'Completed',  '2026-02-10', '2026-02-15'),
(2, 'Customer Churn Classifier',  'Predicts customer churn using classification', 'Python, scikit-learn, pandas', 'https://github.com/user/churn-classifier', NULL, @ml_course, 'In Progress','2026-03-05', NULL),
(2, 'Portfolio Website',          'Personal portfolio with React', 'React, CSS Modules', 'https://github.com/user/portfolio', 'https://user.github.io', @react, 'Planning', '2026-03-10', NULL);

-- ============================================================
-- CAREER GOALS
-- ============================================================
INSERT INTO career_goals (user_id, goal_type, goal_description, target_value, current_value, unit, start_date, end_date, status) VALUES
(2, 'Daily',   'Study at least 1.5 hours each day',    2,  1,  'Hours',    '2026-03-10', '2026-03-10', 'Active'),
(2, 'Weekly',  'Complete 10 hours of learning',        10, 6,  'Hours',    '2026-03-09', '2026-03-15', 'Active'),
(2, 'Weekly',  'Finish 3 ML A-Z topics this week',     3,  1,  'Topics',   '2026-03-09', '2026-03-15', 'Active'),
(2, 'Monthly', 'Complete ML A-Z course by month end',  1,  0,  'Courses',  '2026-03-01', '2026-03-31', 'Active'),
(2, 'Monthly', 'Build 2 projects this month',          2,  1,  'Projects', '2026-03-01', '2026-03-31', 'Active'),
(2, 'Weekly',  'Study React for 5 hours this week',    5,  2,  'Hours',    '2026-03-09', '2026-03-15', 'Active');
