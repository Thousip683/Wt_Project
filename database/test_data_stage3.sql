-- Test data for Stage 3: Competitive Exam Preparation
-- Run this after loading stage3_schema.sql

-- Insert competitive exams (2 exams for testing)
INSERT INTO competitive_exams (user_id, exam_name, exam_type, target_date, status, notes) VALUES
(1, 'JEE Main 2024', 'JEE', '2024-04-06', 'Active', 'Focusing on Physics and Mathematics. Need to improve problem-solving speed.'),
(1, 'GATE CSE 2025', 'GATE', '2025-02-05', 'Active', 'Starting preparation early. Strong in programming, need to work on theory subjects.');

-- Get the exam IDs (assuming they are 1 and 2)
-- For JEE Main (exam_id = 1)
INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage, notes) VALUES
(1, 'Mechanics', 'Physics', 8, 'High', 'In Progress', 60, 'Completed kinematics and laws of motion. Working on rotational dynamics.'),
(1, 'Thermodynamics', 'Physics', 4, 'High', 'In Progress', 45, 'Understanding heat engines and entropy concepts.'),
(1, 'Electromagnetism', 'Physics', 6, 'High', 'Not Started', 0, 'Scheduled to start next week.'),
(1, 'Calculus', 'Mathematics', 7, 'High', 'In Progress', 75, 'Strong in differentiation and integration. Need to practice more problems.'),
(1, 'Algebra', 'Mathematics', 5, 'Medium', 'In Progress', 50, 'Working on complex numbers and quadratic equations.'),
(1, 'Coordinate Geometry', 'Mathematics', 4, 'Medium', 'In Progress', 40, 'Understanding conics and straight lines.'),
(1, 'Organic Chemistry', 'Chemistry', 8, 'High', 'Not Started', 0, 'Will start after completing physical chemistry.'),
(1, 'Physical Chemistry', 'Chemistry', 6, 'High', 'In Progress', 35, 'Completed mole concept. Working on chemical equilibrium.');

-- For GATE CSE (exam_id = 2)
INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage, notes) VALUES
(2, 'Data Structures', 'Core CS', 10, 'High', 'In Progress', 80, 'Strong in arrays, linked lists, trees. Need to practice graph algorithms more.'),
(2, 'Algorithms', 'Core CS', 8, 'High', 'In Progress', 70, 'Good understanding of sorting and searching. Working on dynamic programming.'),
(2, 'Operating Systems', 'Core CS', 7, 'High', 'In Progress', 55, 'Completed process management. Studying memory management.'),
(2, 'Computer Networks', 'Core CS', 6, 'High', 'Not Started', 0, 'Planning to start after OS.'),
(2, 'Database Systems', 'Core CS', 5, 'Medium', 'In Progress', 60, 'Strong in SQL and normalization. Need to practice transaction concepts.'),
(2, 'Theory of Computation', 'Theory', 4, 'Medium', 'Not Started', 0, 'Scheduled for next month.'),
(2, 'Discrete Mathematics', 'Mathematics', 6, 'High', 'In Progress', 65, 'Good at graph theory and combinatorics.');

-- Study sessions for JEE Main
INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
(1, 1, 1, '2024-01-15', 120, 'Rotational motion - angular velocity, angular acceleration, moment of inertia', 'Good session. Understood the basics. Need more practice problems.', 'High'),
(1, 1, 1, '2024-01-16', 90, 'Rotational dynamics - torque, conservation of angular momentum', 'Productive. Solved 15 problems from the textbook.', 'High'),
(1, 1, 4, '2024-01-17', 150, 'Integration techniques - substitution, partial fractions, integration by parts', 'Long but effective session. Covered 3 chapters worth of material.', 'Medium'),
(1, 1, 5, '2024-01-18', 60, 'Complex numbers - operations, De Moivres theorem', 'Short session due to other commitments. Still made progress.', 'Medium'),
(1, 1, 2, '2024-01-19', 105, 'First and second laws of thermodynamics. Heat engines efficiency.', 'Great understanding achieved. Did numerical problems.', 'High'),
(1, 1, 8, '2024-01-20', 120, 'Mole concept, molarity, molality, percentage composition', 'Chemistry session went well. Basics are clear now.', 'High'),
(1, 1, 4, '2024-01-21', 180, 'Definite integrals, areas under curves, applications', 'Intensive practice session. Solved 25+ problems.', 'High');

-- Study sessions for GATE CSE
INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
(1, 2, 9, '2024-01-15', 150, 'Advanced tree traversals, AVL trees, B-trees', 'Excellent session. Implemented AVL tree from scratch.', 'High'),
(1, 2, 10, '2024-01-16', 120, 'Dynamic programming - knapsack, longest common subsequence', 'Challenging but rewarding. DP is starting to make sense.', 'Medium'),
(1, 2, 11, '2024-01-17', 90, 'Process synchronization, semaphores, monitors', 'Good theoretical understanding. Need to practice more problems.', 'High'),
(1, 2, 13, '2024-01-18', 135, 'Normalization - 1NF, 2NF, 3NF, BCNF. Practiced 10 problems', 'Database normalization is now clear. Did good practice.', 'High'),
(1, 2, 15, '2024-01-19', 100, 'Graph theory - shortest paths, spanning trees', 'Implemented Dijkstra and Prims algorithm.', 'High'),
(1, 2, 9, '2024-01-20', 75, 'Graph algorithms - DFS, BFS, topological sort', 'Short but focused session. Implemented all three algorithms.', 'Medium');

-- Goals for JEE Main
INSERT INTO exam_goals (user_id, exam_id, goal_type, target_description, target_value, current_value, start_date, end_date, status) VALUES
(1, 1, 'Daily', 'Study 4 hours daily', 4, 3, '2024-01-15', '2024-01-22', 'In Progress'),
(1, 1, 'Weekly', 'Complete 5 topics per week', 5, 3, '2024-01-15', '2024-01-21', 'In Progress'),
(1, 1, 'Weekly', 'Solve 100 practice problems', 100, 65, '2024-01-15', '2024-01-21', 'In Progress'),
(1, 1, 'Monthly', 'Complete 20 topics in January', 20, 8, '2024-01-01', '2024-01-31', 'In Progress'),
(1, 1, 'Daily', 'Revise previous days topics', 1, 1, '2024-01-20', '2024-01-21', 'Completed');

-- Goals for GATE CSE
INSERT INTO exam_goals (user_id, exam_id, goal_type, target_description, target_value, current_value, start_date, end_date, status) VALUES
(1, 2, 'Daily', 'Study 3 hours daily', 3, 2, '2024-01-15', '2024-01-22', 'In Progress'),
(1, 2, 'Weekly', 'Complete 3 major topics per week', 3, 2, '2024-01-15', '2024-01-21', 'In Progress'),
(1, 2, 'Weekly', 'Implement 5 algorithms in code', 5, 4, '2024-01-15', '2024-01-21', 'In Progress'),
(1, 2, 'Monthly', 'Finish core subjects by month end', 4, 1, '2024-01-01', '2024-01-31', 'In Progress');

-- Practice tests for JEE Main
INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_marks, obtained_marks, percentage, time_taken_minutes, accuracy_percentage, analysis_notes) VALUES
(1, 1, 'JEE Mock Test - 1 (Full Length)', '2024-01-10', 300, 195, 65.00, 180, 72.50, 'First mock test. Good performance in Mathematics (80%). Physics was average (60%). Chemistry needs improvement (55%). Time management was good - completed with 10 minutes to spare. Need to work on organic chemistry and electromagnetism.'),
(1, 1, 'Physics Chapter Test - Mechanics', '2024-01-14', 100, 72, 72.00, 60, 80.00, 'Dedicated mechanics test. Strong in rotational motion and laws of motion. Made silly mistakes in kinematics which brought down accuracy. Overall satisfied with performance.'),
(1, 1, 'Mathematics Part Test - Calculus', '2024-01-17', 120, 96, 80.00, 75, 85.00, 'Excellent performance in calculus! Integration techniques are now strong. Made 2-3 calculation errors, otherwise would have scored 90+. Very happy with this result.'),
(1, 1, 'JEE Mock Test - 2 (Full Length)', '2024-01-20', 300, 216, 72.00, 175, 76.00, 'Significant improvement from Mock 1! Mathematics improved to 85%, Physics to 70%. Chemistry still at 60% - this is my focus area. Better time management this time. Accuracy also improved. Keep up the momentum!');

-- Practice tests for GATE CSE
INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_marks, obtained_marks, percentage, time_taken_minutes, accuracy_percentage, analysis_notes) VALUES
(1, 2, 'GATE Mock Test - 1', '2024-01-12', 100, 58, 58.00, 180, 65.00, 'First GATE mock. Data Structures and Algorithms were strong (75%). OS and Networks were weak (40%). Need to complete these subjects first before attempting full mocks. Time pressure was real - barely finished. Focus: Complete pending subjects, improve speed.'),
(1, 2, 'Data Structures Topic Test', '2024-01-15', 50, 42, 84.00, 60, 88.00, 'Excellent performance! Strong grasp of trees, graphs, and hashing. Made one mistake in AVL tree rotation. Very happy with this score. Data structures preparation is solid.'),
(1, 2, 'Algorithms Full Test', '2024-01-18', 60, 45, 75.00, 75, 80.00, 'Good performance in algorithms. DP questions were handled well. Got stuck on one graph problem which took too much time. Overall satisfied. Need to practice more graph algorithms under time pressure.'),
(1, 2, 'GATE Mock Test - 2', '2024-01-21', 100, 67, 67.00, 175, 72.00, 'Improved from Mock 1! Core CS subjects are getting stronger. Still weak in theory subjects. Better time management - finished 5 minutes early. Next mock target: 75%. Action items: Complete remaining topics, focus on theory of computation and compiler design.');
