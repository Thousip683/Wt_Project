-- Clean up existing data
DELETE FROM study_sessions WHERE user_id = 2;
DELETE FROM exam_goals WHERE user_id = 2;
DELETE FROM practice_tests WHERE user_id = 2;
DELETE FROM exam_topics WHERE exam_id IN (SELECT id FROM competitive_exams WHERE user_id = 2);
DELETE FROM competitive_exams WHERE user_id = 2;

-- Insert basic test data
INSERT INTO competitive_exams (user_id, exam_name, exam_full_name, target_date, status, notes) VALUES
(2, 'JEE Main 2026', 'Joint Entrance Examination Main', '2026-04-08', 'Active', 'Targeting 95+ percentile'),
(2, 'GATE CSE 2027', 'Graduate Aptitude Test in Engineering - Computer Science', '2027-02-06', 'Active', 'Focus on Data Structures and Algorithms'),
(2, 'CAT 2026', 'Common Admission Test', '2026-11-28', 'Active', 'Preparing for MBA');

-- Get exam IDs
SET @jee_id = (SELECT id FROM competitive_exams WHERE user_id = 2 AND exam_name = 'JEE Main 2026');
SET @gate_id = (SELECT id FROM competitive_exams WHERE user_id = 2 AND exam_name = 'GATE CSE 2027');
SET @cat_id = (SELECT id FROM competitive_exams WHERE user_id = 2 AND exam_name = 'CAT 2026');

-- Add some topics
INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage) VALUES
(@jee_id, 'Mechanics', 'Physics', 5, 'High', 'In Progress', 75),
(@jee_id, 'Calculus', 'Mathematics', 4, 'High', 'In Progress', 65),
(@jee_id, 'Organic Chemistry', 'Chemistry', 3, 'Medium', 'Not Started', 0),
(@gate_id, 'Data Structures', 'Programming', 6, 'High', 'In Progress', 80),
(@gate_id, 'Algorithms', 'Programming', 5, 'High', 'In Progress', 70),
(@cat_id, 'Quantitative Aptitude', 'Quant', 8, 'High', 'In Progress', 60);

-- Add study sessions
INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
(2, @jee_id, NULL, CURDATE() - INTERVAL 1 DAY, 120, 'Rotational motion practice', 'Good session', 'High'),
(2, @jee_id, NULL, CURDATE() - INTERVAL 2 DAY, 90, 'Integration problems', 'Need more practice', 'Medium'),
(2, @gate_id, NULL, CURDATE() - INTERVAL 1 DAY, 150, 'Tree algorithms', 'Excellent progress', 'High'),
(2, @cat_id, NULL, CURDATE() - INTERVAL 1 DAY, 75, 'Arithmetic problems', 'Speed improving', 'High');

-- Add goals
INSERT INTO exam_goals (user_id, exam_id, goal_type, goal_description, target_value, current_value, start_date, end_date, status) VALUES
(2, @jee_id, 'Daily', 'Study 5 hours daily', 5, 4, CURDATE(), CURDATE() + INTERVAL 1 DAY, 'Active'),
(2, @jee_id, 'Weekly', 'Complete 4 topics', 4, 2, CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 4 DAY, 'Active'),
(2, @gate_id, 'Daily', 'Code 2 problems daily', 2, 2, CURDATE(), CURDATE() + INTERVAL 1 DAY, 'Completed'),
(2, @cat_id, 'Weekly', 'Solve 3 mock tests', 3, 1, CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 5 DAY, 'Active');

-- Add practice tests
INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_questions, attempted_questions, correct_answers, score, time_taken_minutes, notes) VALUES
(2, @jee_id, 'JEE Mock Test 1', CURDATE() - INTERVAL 5 DAY, 90, 85, 68, 75.56, 180, 'Good first attempt'),
(2, @jee_id, 'Physics Sectional', CURDATE() - INTERVAL 3 DAY, 30, 30, 24, 80.00, 60, 'Physics strong'),
(2, @gate_id, 'GATE Mock Test 1', CURDATE() - INTERVAL 4 DAY, 65, 62, 48, 73.85, 180, 'Need to improve speed'),
(2, @cat_id, 'CAT Mock Test 1', CURDATE() - INTERVAL 2 DAY, 100, 95, 72, 72.00, 120, 'Decent performance');

SELECT 'Test data loaded successfully!' as Status;
