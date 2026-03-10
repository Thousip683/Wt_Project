-- Test Data for Beyond Classroom
-- Stage 2 & Stage 3: Complete Test Data
-- Run this after creating the tables and registering a user

USE beyond_classroom;

-- Note: Replace user_id = 2 with your actual user ID
-- You can find your user ID by running: SELECT id FROM users WHERE email = 'your@email.com';

-- ============================================
-- STEP 1: DELETE ALL EXISTING DATA FOR USER
-- ============================================
-- This prevents duplicate data issues

-- Delete Stage 3 data first (due to foreign key constraints)
DELETE FROM practice_tests WHERE user_id = 2;
DELETE FROM exam_goals WHERE user_id = 2;
DELETE FROM study_sessions WHERE user_id = 2;
DELETE FROM exam_topics WHERE exam_id IN (SELECT id FROM competitive_exams WHERE user_id = 2);
DELETE FROM competitive_exams WHERE user_id = 2;

-- Delete Stage 2 data
DELETE FROM exams WHERE user_id = 2;
DELETE FROM assignments WHERE user_id = 2;
DELETE FROM timetable WHERE user_id = 2;
DELETE FROM subjects WHERE user_id = 2;

-- ============================================
-- STEP 2: INSERT FRESH DATA
-- ============================================

-- Sample Subjects for Computer Science Engineering
INSERT INTO subjects (user_id, subject_name, subject_code, credits, instructor, color) VALUES
(2, 'Data Structures and Algorithms', 'CS301', 4, 'Dr. Rajesh Kumar', '#2563eb'),
(2, 'Database Management Systems', 'CS302', 3, 'Prof. Priya Sharma', '#10b981'),
(2, 'Operating Systems', 'CS303', 4, 'Dr. Amit Patel', '#f59e0b'),
(2, 'Computer Networks', 'CS304', 3, 'Prof. Sneha Reddy', '#8b5cf6'),
(2, 'Software Engineering', 'CS305', 3, 'Dr. Vikram Singh', '#ec4899'),
(2, 'Web Technologies', 'CS306', 3, 'Prof. Anita Desai', '#14b8a6');

-- Get subject IDs for foreign key references
SET @sub1 = (SELECT id FROM subjects WHERE user_id = 2 AND subject_code = 'CS301' LIMIT 1);
SET @sub2 = (SELECT id FROM subjects WHERE user_id = 2 AND subject_code = 'CS302' LIMIT 1);
SET @sub3 = (SELECT id FROM subjects WHERE user_id = 2 AND subject_code = 'CS303' LIMIT 1);
SET @sub4 = (SELECT id FROM subjects WHERE user_id = 2 AND subject_code = 'CS304' LIMIT 1);
SET @sub5 = (SELECT id FROM subjects WHERE user_id = 2 AND subject_code = 'CS305' LIMIT 1);
SET @sub6 = (SELECT id FROM subjects WHERE user_id = 2 AND subject_code = 'CS306' LIMIT 1);

-- Sample Timetable (Monday to Friday schedule)
INSERT INTO timetable (user_id, subject_id, day_of_week, start_time, end_time, room_number, class_type) VALUES
(2, @sub1, 'Monday', '09:00:00', '10:00:00', 'Room 301', 'Lecture'),
(2, @sub2, 'Monday', '10:15:00', '11:15:00', 'Room 302', 'Lecture'),
(2, @sub3, 'Monday', '11:30:00', '12:30:00', 'Room 303', 'Lecture'),
(2, @sub1, 'Monday', '14:00:00', '17:00:00', 'Lab 1', 'Lab'),
(2, @sub4, 'Tuesday', '09:00:00', '10:00:00', 'Room 304', 'Lecture'),
(2, @sub5, 'Tuesday', '10:15:00', '11:15:00', 'Room 305', 'Lecture'),
(2, @sub6, 'Tuesday', '11:30:00', '12:30:00', 'Room 306', 'Lecture'),
(2, @sub2, 'Tuesday', '14:00:00', '17:00:00', 'Lab 2', 'Lab'),
(2, @sub1, 'Wednesday', '09:00:00', '10:00:00', 'Room 301', 'Tutorial'),
(2, @sub3, 'Wednesday', '10:15:00', '11:15:00', 'Room 303', 'Lecture'),
(2, @sub4, 'Wednesday', '11:30:00', '12:30:00', 'Room 304', 'Tutorial'),
(2, @sub6, 'Wednesday', '14:00:00', '17:00:00', 'Lab 3', 'Lab'),
(2, @sub2, 'Thursday', '09:00:00', '10:00:00', 'Room 302', 'Lecture'),
(2, @sub5, 'Thursday', '10:15:00', '11:15:00', 'Room 305', 'Tutorial'),
(2, @sub6, 'Thursday', '11:30:00', '12:30:00', 'Room 306', 'Lecture'),
(2, @sub3, 'Thursday', '14:00:00', '17:00:00', 'Lab 4', 'Lab'),
(2, @sub4, 'Friday', '09:00:00', '10:00:00', 'Room 304', 'Lecture'),
(2, @sub5, 'Friday', '10:15:00', '11:15:00', 'Room 305', 'Lecture'),
(2, @sub1, 'Friday', '11:30:00', '12:30:00', 'Room 301', 'Lecture');

-- Sample Assignments (mix of pending, in progress, and completed)
INSERT INTO assignments (user_id, subject_id, title, description, due_date, status, priority, marks) VALUES
-- Urgent assignments (due soon)
(2, @sub1, 'Implement Binary Search Tree', 'Create BST with insert, delete, search operations in C++', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'In Progress', 'High', 50),
(2, @sub2, 'SQL Queries Assignment', 'Write complex JOIN queries and stored procedures', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Pending', 'High', 40),
(2, @sub3, 'Process Scheduling Algorithms', 'Implement FCFS, SJF, and Round Robin scheduling', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Pending', 'Medium', 60),
(2, @sub4, 'Network Protocol Analysis', 'Analyze TCP/IP packet flow using Wireshark', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Pending', 'Medium', 45),
(2, @sub5, 'Software Testing Report', 'Write test cases for library management system', DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Pending', 'Medium', 35),
(2, @sub6, 'Responsive Website Project', 'Create a responsive portfolio website using HTML, CSS, JS', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Pending', 'Low', 100),
(2, @sub1, 'Graph Algorithms Implementation', 'Implement BFS, DFS, Dijkstra algorithm', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Pending', 'Low', 50),

(2, @sub2, 'ER Diagram Design', 'Design ER diagram for e-commerce database', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Completed', 'Medium', 30),
(2, @sub3, 'Memory Management Report', 'Study paging and segmentation techniques', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Completed', 'High', 40),

(2, @sub4, 'OSI Model Presentation', 'Prepare presentation on OSI 7-layer model', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Pending', 'High', 20);

-- Sample Exams (upcoming and completed)
INSERT INTO exams (user_id, subject_id, exam_name, exam_type, exam_date, exam_time, duration, total_marks, obtained_marks, syllabus, room_number, status) VALUES
-- Upcoming exams (next few weeks)
(2, @sub1, 'DSA Mid-term Exam', 'Mid-term', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00', 180, 100, NULL, 'Arrays, Linked Lists, Stacks, Queues, Trees (BST, AVL)', 'Exam Hall A', 'Upcoming'),
(2, @sub2, 'DBMS Quiz 2', 'Quiz', DATE_ADD(CURDATE(), INTERVAL 8 DAY), '09:00:00', 60, 30, NULL, 'SQL Joins, Subqueries, Normalization', 'Room 302', 'Upcoming'),
(2, @sub3, 'OS Practical Exam', 'Practical', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '14:00:00', 120, 50, NULL, 'Process scheduling, Memory management programs', 'Lab 4', 'Upcoming'),
(2, @sub4, 'Networks End-term', 'End-term', DATE_ADD(CURDATE(), INTERVAL 30 DAY), '10:00:00', 180, 100, NULL, 'All topics: OSI Model, TCP/IP, Routing, Network Security', 'Exam Hall B', 'Upcoming'),
(2, @sub5, 'Software Engineering Mid-term', 'Mid-term', DATE_ADD(CURDATE(), INTERVAL 15 DAY), '14:00:00', 120, 75, NULL, 'SDLC, Agile, Testing, UML Diagrams', 'Room 305', 'Upcoming'),

(2, @sub1, 'DSA Quiz 1', 'Quiz', DATE_SUB(CURDATE(), INTERVAL 15 DAY), '11:00:00', 60, 25, 22, 'Time complexity, Recursion, Basic data structures', 'Room 301', 'Completed'),
(2, @sub2, 'DBMS Mid-term', 'Mid-term', DATE_SUB(CURDATE(), INTERVAL 20 DAY), '10:00:00', 120, 100, 85, 'ER diagrams, Relational model, SQL basics', 'Exam Hall A', 'Completed'),
(2, @sub3, 'OS Quiz 1', 'Quiz', DATE_SUB(CURDATE(), INTERVAL 10 DAY), '09:00:00', 45, 20, 18, 'Introduction to OS, Process concepts', 'Room 303', 'Completed'),
(2, @sub6, 'Web Tech Practical', 'Practical', DATE_SUB(CURDATE(), INTERVAL 7 DAY), '14:00:00', 180, 50, 45, 'HTML forms, CSS layouts, JavaScript DOM', 'Lab 3', 'Completed');

-- Verification queries (run these to check the data)
-- SELECT COUNT(*) as total_subjects FROM subjects WHERE user_id = 1;
-- SELECT COUNT(*) as total_classes FROM timetable WHERE user_id = 1;
-- SELECT COUNT(*) as total_assignments FROM assignments WHERE user_id = 1;
-- SELECT COUNT(*) as total_exams FROM exams WHERE user_id = 1;

-- ============================================
-- STAGE 3: COMPETITIVE EXAM PREPARATION DATA
-- ============================================

-- Competitive Exams (3 different exams for comprehensive testing)
INSERT INTO competitive_exams (user_id, exam_name, exam_full_name, target_date, status, notes) VALUES
(2, 'JEE Main 2026', 'Joint Entrance Examination Main', '2026-04-08', 'Active', 'Targeting 95+ percentile. Strong in Mathematics and Physics. Need to improve Chemistry organic section.'),
(2, 'GATE CSE 2027', 'Graduate Aptitude Test in Engineering - Computer Science', '2027-02-06', 'Active', 'Starting early preparation. Focus on Data Structures, Algorithms, and Operating Systems first.'),
(2, 'CAT 2026', 'Common Admission Test', '2026-11-28', 'Active', 'Preparing for MBA entrance. Need to balance with engineering studies.');

-- Get the exam IDs for foreign key references
SET @jee_exam_id = (SELECT id FROM competitive_exams WHERE user_id = 2 AND exam_name = 'JEE Main 2026' LIMIT 1);
SET @gate_exam_id = (SELECT id FROM competitive_exams WHERE user_id = 2 AND exam_name = 'GATE CSE 2027' LIMIT 1);
SET @cat_exam_id = (SELECT id FROM competitive_exams WHERE user_id = 2 AND exam_name = 'CAT 2026' LIMIT 1);

-- Topics for JEE Main
INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage) VALUES
-- Physics topics
(@jee_exam_id, 'Mechanics - Kinematics', 'Physics', 3, 'High', 'Completed', 100),
(@jee_exam_id, 'Mechanics - Laws of Motion', 'Physics', 2, 'High', 'Completed', 100),
(@jee_exam_id, 'Mechanics - Work, Energy & Power', 'Physics', 2, 'High', 'In Progress', 85),
(@jee_exam_id, 'Rotational Motion', 'Physics', 3, 'High', 'In Progress', 65),
(@jee_exam_id, 'Gravitation', 'Physics', 2, 'Medium', 'In Progress', 40),
(@jee_exam_id, 'Thermodynamics', 'Physics', 4, 'High', 'In Progress', 55),
(@jee_exam_id, 'Electrostatics', 'Physics', 3, 'High', 'Not Started', 0),
(@jee_exam_id, 'Current Electricity', 'Physics', 3, 'High', 'Not Started', 0),
(@jee_exam_id, 'Magnetism', 'Physics', 3, 'High', 'Not Started', 0),
(@jee_exam_id, 'Optics', 'Physics', 3, 'Medium', 'Not Started', 0),

-- Mathematics topics
(@jee_exam_id, 'Algebra - Complex Numbers', 'Mathematics', 1, 'High', 'Completed', 100),
(@jee_exam_id, 'Algebra - Quadratic Equations', 'Mathematics', 1, 'High', 'Completed', 100),
(@jee_exam_id, 'Trigonometry', 'Mathematics', 3, 'High', 'In Progress', 80),
(@jee_exam_id, 'Calculus - Limits & Continuity', 'Mathematics', 2, 'High', 'Completed', 100),
(@jee_exam_id, 'Calculus - Differentiation', 'Mathematics', 3, 'High', 'Completed', 95),
(@jee_exam_id, 'Calculus - Integration', 'Mathematics', 4, 'High', 'In Progress', 75),
(@jee_exam_id, 'Coordinate Geometry - Straight Lines', 'Mathematics', 2, 'Medium', 'In Progress', 60),
(@jee_exam_id, 'Coordinate Geometry - Circles', 'Mathematics', 2, 'Medium', 'In Progress', 45),
(@jee_exam_id, 'Probability & Statistics', 'Mathematics', 2, 'Medium', 'Not Started', 0),
(@jee_exam_id, 'Vectors & 3D Geometry', 'Mathematics', 3, 'Medium', 'Not Started', 0),

-- Chemistry topics
(@jee_exam_id, 'Physical Chemistry - Mole Concept', 'Chemistry', 1, 'High', 'Completed', 100),
(@jee_exam_id, 'Chemical Equilibrium', 'Chemistry', 2, 'High', 'In Progress', 70),
(@jee_exam_id, 'Thermodynamics (Chemistry)', 'Chemistry', 2, 'High', 'In Progress', 50),
(@jee_exam_id, 'Electrochemistry', 'Chemistry', 2, 'Medium', 'Not Started', 0),
(@jee_exam_id, 'Organic Chemistry - Basic Concepts', 'Chemistry', 2, 'High', 'In Progress', 40),
(@jee_exam_id, 'Organic Chemistry - Reactions', 'Chemistry', 4, 'High', 'Not Started', 0),
(@jee_exam_id, 'Inorganic Chemistry - Periodic Table', 'Chemistry', 2, 'Medium', 'In Progress', 65),
(@jee_exam_id, 'Coordination Compounds', 'Chemistry', 2, 'Medium', 'Not Started', 0);

-- Topics for GATE CSE
INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage) VALUES
(@gate_exam_id, 'Data Structures - Arrays & Strings', 'Programming & DS', 2, 'High', 'Completed', 100),
(@gate_exam_id, 'Data Structures - Linked Lists', 'Programming & DS', 2, 'High', 'Completed', 100),
(@gate_exam_id, 'Data Structures - Stacks & Queues', 'Programming & DS', 2, 'High', 'Completed', 95),
(@gate_exam_id, 'Data Structures - Trees', 'Programming & DS', 4, 'High', 'In Progress', 80),
(@gate_exam_id, 'Data Structures - Graphs', 'Programming & DS', 3, 'High', 'In Progress', 70),
(@gate_exam_id, 'Data Structures - Hashing', 'Programming & DS', 2, 'High', 'In Progress', 65),
(@gate_exam_id, 'Algorithms - Sorting', 'Algorithms', 2, 'High', 'Completed', 100),
(@gate_exam_id, 'Algorithms - Searching', 'Algorithms', 2, 'High', 'Completed', 100),
(@gate_exam_id, 'Algorithms - Divide & Conquer', 'Algorithms', 2, 'High', 'In Progress', 85),
(@gate_exam_id, 'Algorithms - Dynamic Programming', 'Algorithms', 3, 'High', 'In Progress', 60),
(@gate_exam_id, 'Algorithms - Greedy', 'Algorithms', 2, 'Medium', 'In Progress', 50),
(@gate_exam_id, 'Algorithms - Graph Algorithms', 'Algorithms', 3, 'High', 'In Progress', 70),
(@gate_exam_id, 'Operating Systems - Process Management', 'Operating Systems', 3, 'High', 'In Progress', 75),
(@gate_exam_id, 'Operating Systems - Memory Management', 'Operating Systems', 3, 'High', 'In Progress', 60),
(@gate_exam_id, 'Operating Systems - Synchronization', 'Operating Systems', 2, 'High', 'In Progress', 55),
(@gate_exam_id, 'Operating Systems - Deadlocks', 'Operating Systems', 2, 'Medium', 'Not Started', 0),
(@gate_exam_id, 'DBMS - ER Model & Relational Model', 'Databases', 2, 'High', 'In Progress', 80),
(@gate_exam_id, 'DBMS - SQL', 'Databases', 2, 'High', 'In Progress', 85),
(@gate_exam_id, 'DBMS - Normalization', 'Databases', 2, 'High', 'In Progress', 75),
(@gate_exam_id, 'DBMS - Transactions', 'Databases', 2, 'Medium', 'In Progress', 45),
(@gate_exam_id, 'Computer Networks - Layers', 'Networks', 2, 'Medium', 'Not Started', 0),
(@gate_exam_id, 'Computer Networks - Protocols', 'Networks', 3, 'Medium', 'Not Started', 0),
(@gate_exam_id, 'Theory of Computation - Regular Languages', 'TOC', 2, 'Medium', 'Not Started', 0),
(@gate_exam_id, 'Discrete Mathematics - Sets & Relations', 'Mathematics', 2, 'High', 'In Progress', 70),
(@gate_exam_id, 'Discrete Mathematics - Graph Theory', 'Mathematics', 2, 'High', 'In Progress', 75);

-- Topics for CAT
INSERT INTO exam_topics (exam_id, topic_name, subject_category, total_chapters, priority, status, progress_percentage) VALUES
(@cat_exam_id, 'Quantitative Aptitude - Arithmetic', 'Quant', 5, 'High', 'In Progress', 60),
(@cat_exam_id, 'Quantitative Aptitude - Algebra', 'Quant', 3, 'High', 'In Progress', 50),
(@cat_exam_id, 'Quantitative Aptitude - Geometry', 'Quant', 4, 'Medium', 'In Progress', 40),
(@cat_exam_id, 'Quantitative Aptitude - Number Systems', 'Quant', 2, 'High', 'In Progress', 70),
(@cat_exam_id, 'Verbal Ability - Reading Comprehension', 'Verbal', 3, 'High', 'In Progress', 65),
(@cat_exam_id, 'Verbal Ability - Para Jumbles', 'Verbal', 1, 'Medium', 'In Progress', 55),
(@cat_exam_id, 'Verbal Ability - Grammar', 'Verbal', 2, 'Medium', 'In Progress', 50),
(@cat_exam_id, 'Logical Reasoning - Arrangements', 'LRDI', 2, 'High', 'In Progress', 60),
(@cat_exam_id, 'Data Interpretation - Tables & Charts', 'LRDI', 2, 'High', 'In Progress', 70),
(@cat_exam_id, 'Data Interpretation - Caselets', 'LRDI', 2, 'Medium', 'In Progress', 45);

-- Study Sessions for JEE Main
INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
-- Recent sessions (last 2 weeks)
(2, @jee_exam_id, NULL, '2026-02-08', 150, 'Rotational dynamics: torque calculations, moment of inertia for composite bodies', 'Very productive session. Solved 20 problems. Concepts becoming clearer.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-08', 120, 'Integration by parts, integration by substitution, special integrals', 'Good practice session. Covered multiple integration techniques.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-07', 135, 'Thermodynamic processes: isothermal, adiabatic, cyclic. Carnot engine', 'Excellent understanding achieved. Did 15 numerical problems.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-07', 90, 'Trigonometric equations: general solutions, principal values', 'Productive but felt tired in the end. Need better breaks.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-06', 180, 'Angular momentum conservation, collision problems in rotational motion', 'Long session but very rewarding. Breakthrough in understanding!', 'High'),
(2, @jee_exam_id, NULL, '2026-02-06', 105, 'Chemical equilibrium: Le Chateliers principle applications, Kc and Kp', 'Chemistry session went well. Numerical solving is improving.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-05', 120, 'Energy conservation in multiple scenarios, power calculations', 'Good revision session. Cleared previous doubts.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-05', 150, 'Coordinate geometry: distance formula applications, section formula', 'Geometry problems are tricky. Need more practice.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-04', 165, 'Definite integrals, areas under curves, properties of definite integrals', 'Intensive practice. Solved 30+ problems. Feeling confident!', 'High'),
(2, @jee_exam_id, NULL, '2026-02-04', 90, 'Gravitation: gravitational field, potential, escape velocity', 'Shorter session but covered important concepts.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-03', 100, 'Chemical equilibrium constant calculations, equilibrium problems', 'Regular practice session. Calculations getting faster.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-03', 120, 'Heat engines efficiency, refrigerators and heat pumps', 'Thermodynamics starting to make sense now!', 'High'),
(2, @jee_exam_id, NULL, '2026-02-02', 95, 'Rotational kinetic energy, combined translation-rotation motion', 'Good session despite some distractions. Made progress.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-02', 110, 'Trigonometric identities: sum, product, multiple angle formulas', 'Identities practice. Need to memorize better.', 'High'),
(2, @jee_exam_id, NULL, '2026-02-01', 130, 'Organic nomenclature: IUPAC rules, isomerism types', 'Organic chemistry is challenging but improving gradually.', 'Medium');

-- Study Sessions for GATE CSE
INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
(2, @gate_exam_id, NULL, '2026-02-08', 140, 'Trees: AVL tree rotations, height-balanced trees implementation', 'Implemented AVL tree from scratch. Great learning experience!', 'High'),
(2, @gate_exam_id, NULL, '2026-02-08', 100, 'Dynamic programming: 0/1 knapsack, longest common subsequence', 'DP is clicking! Solved 10 classic problems.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-07', 135, 'Dijkstras algorithm, Bellman-Ford, Floyd-Warshall for shortest paths', 'Graph algorithms session. Implemented all three.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-07', 120, 'Process scheduling: FCFS, SJF, Round Robin with examples', 'OS concepts are interesting. Solved many numerical problems.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-06', 105, 'Graph representations: adjacency matrix vs list. BFS and DFS coding', 'Coded both traversals. Understanding when to use which.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-06', 150, 'Paging: page tables, TLB, page faults. Solved 20 problems', 'Memory management is complex but fascinating!', 'High'),
(2, @gate_exam_id, NULL, '2026-02-05', 125, 'SQL: complex joins, nested queries, group by and having clauses', 'SQL practice session. Very strong in queries now.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-05', 95, 'Dynamic programming: matrix chain multiplication, optimal BST', 'Advanced DP problems. Challenging but solved 5 problems.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-04', 110, 'Hashing: collision resolution - chaining, open addressing', 'Hash table implementations practiced.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-04', 130, 'Process synchronization: producer-consumer, readers-writers problems', 'Synchronization is tricky. Need more practice.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-03', 100, 'Binary trees: all traversals, height, diameter calculations', 'Tree problems practice session. Solved 15 problems.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-03', 120, 'Normalization: 1NF to BCNF with examples, dependency analysis', 'DBMS theory session. Normalization becoming clearer.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-02', 115, 'Graph algorithms: topological sort, strongly connected components', 'Advanced graph concepts. Implemented algorithms.', 'High'),
(2, @gate_exam_id, NULL, '2026-02-02', 90, 'Discrete math: relations, types of relations, equivalence classes', 'Math session. Theoretical but important.', 'Medium');

-- Study Sessions for CAT
INSERT INTO study_sessions (user_id, exam_id, topic_id, session_date, duration_minutes, topics_covered, notes, productivity_rating) VALUES
(2, @cat_exam_id, NULL, '2026-02-08', 90, 'Profit and loss: successive discounts, marked price problems', 'CAT quant practice. Speed is improving.', 'High'),
(2, @cat_exam_id, NULL, '2026-02-07', 75, 'Reading comprehension: 3 passages, inference and tone questions', 'Good RC practice. Got 80% correct.', 'High'),
(2, @cat_exam_id, NULL, '2026-02-06', 85, 'Data interpretation: bar charts, pie charts, multiple graphs', 'DI practice session. Calculation speed increased.', 'High'),
(2, @cat_exam_id, NULL, '2026-02-05', 60, 'Number systems: divisibility rules, remainders, last digit problems', 'Short but focused session. Cleared doubts.', 'High'),
(2, @cat_exam_id, NULL, '2026-02-04', 70, 'Logical reasoning: seating arrangements, linear and circular', 'LR is fun! Enjoying these puzzles.', 'High');

-- Goals for JEE Main
INSERT INTO exam_goals (user_id, exam_id, goal_type, goal_description, target_value, current_value, start_date, end_date, status) VALUES
(2, @jee_exam_id, 'Daily', 'Study 5 hours for JEE daily', 5, 4, '2026-02-08', '2026-02-09', 'Active'),
(2, @jee_exam_id, 'Daily', 'Solve 30 practice problems', 30, 25, '2026-02-08', '2026-02-09', 'Active'),
(2, @jee_exam_id, 'Weekly', 'Complete 4 topics this week', 4, 2, '2026-02-03', '2026-02-09', 'Active'),
(2, @jee_exam_id, 'Weekly', 'Solve 200 problems this week', 200, 145, '2026-02-03', '2026-02-09', 'Active'),
(2, @jee_exam_id, 'Weekly', 'Study 35 hours this week', 35, 28, '2026-02-03', '2026-02-09', 'Active'),
(2, @jee_exam_id, 'Monthly', 'Complete Physics syllabus 60%', 60, 45, '2026-02-01', '2026-02-28', 'Active'),
(2, @jee_exam_id, 'Monthly', 'Complete Mathematics syllabus 70%', 70, 60, '2026-02-01', '2026-02-28', 'Active'),
(2, @jee_exam_id, 'Monthly', 'Take 4 full-length mock tests', 4, 2, '2026-02-01', '2026-02-28', 'Active'),
(2, @jee_exam_id, 'Weekly', 'Revise completed topics 1 hour daily', 7, 5, '2026-02-03', '2026-02-09', 'Active'),
(2, @jee_exam_id, 'Daily', 'Chemistry practice 1.5 hours', 90, 90, '2026-02-07', '2026-02-08', 'Active');

-- Goals for GATE CSE
INSERT INTO exam_goals (user_id, exam_id, goal_type, goal_description, target_value, current_value, start_date, end_date, status) VALUES
(2, @gate_exam_id, 'Daily', 'Study GATE topics 3 hours daily', 3, 2, '2026-02-08', '2026-02-09', 'Active'),
(2, @gate_exam_id, 'Weekly', 'Complete 3 topics per week', 3, 2, '2026-02-03', '2026-02-09', 'Active'),
(2, @gate_exam_id, 'Weekly', 'Code 10 algorithms this week', 10, 7, '2026-02-03', '2026-02-09', 'Active'),
(2, @gate_exam_id, 'Monthly', 'Complete DS and Algo syllabus', 100, 75, '2026-02-01', '2026-02-28', 'Active'),
(2, @gate_exam_id, 'Monthly', 'Solve 100 previous year questions', 100, 42, '2026-02-01', '2026-02-28', 'Active'),
(2, @gate_exam_id, 'Weekly', 'Practice 30 GATE questions', 30, 30, '2026-02-03', '2026-02-09', 'Active');

-- Goals for CAT
INSERT INTO exam_goals (user_id, exam_id, goal_type, goal_description, target_value, current_value, start_date, end_date, status) VALUES
(2, @cat_exam_id, 'Daily', 'CAT practice 1.5 hours', 90, 75, '2026-02-08', '2026-02-09', 'Active'),
(2, @cat_exam_id, 'Weekly', 'Solve 3 mock sectionals', 3, 2, '2026-02-03', '2026-02-09', 'Active'),
(2, @cat_exam_id, 'Weekly', 'Read 10 RC passages', 10, 6, '2026-02-03', '2026-02-09', 'Active'),
(2, @cat_exam_id, 'Monthly', 'Complete Quant fundamentals', 100, 55, '2026-02-01', '2026-02-28', 'Active');

-- Practice Tests for JEE Main
INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_marks, obtained_marks, percentage, time_taken_minutes, accuracy_percentage, analysis_notes) VALUES
(2, @jee_exam_id, 'JEE Main Mock Test - 1 (Full Length)', '2026-01-15', 300, 180, 60.00, 180, 68.50),
(2, @jee_exam_id, 'Physics Topic Test - Mechanics', '2026-01-18', 100, 75, 75.00, 60, 82.00),
(2, @jee_exam_id, 'Mathematics Full Test - Calculus & Algebra', '2026-01-22', 120, 98, 81.67, 75, 87.00),
(2, @jee_exam_id, 'Chemistry Test - Physical Chemistry', '2026-01-25', 100, 62, 62.00, 60, 70.00),
(2, @jee_exam_id, 'JEE Main Mock Test - 2 (Full Length)', '2026-01-29', 300, 198, 66.00, 175, 72.50),
(2, @jee_exam_id, 'JEE Advanced Pattern Mock - 1', '2026-02-01', 180, 95, 52.78, 180, 61.00),
(2, @jee_exam_id, 'Mathematics Topic Test - Coordinate Geometry', '2026-02-04', 80, 58, 72.50, 60, 78.00),
(2, @jee_exam_id, 'JEE Main Mock Test - 3 (Full Length)', '2026-02-07', 300, 207, 69.00, 178, 75.00);

-- Practice Tests for GATE CSE
INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_marks, obtained_marks, percentage, time_taken_minutes, accuracy_percentage, analysis_notes) VALUES
(2, @gate_exam_id, 'GATE Mock Test - 1 (Subject-wise)', '2026-01-20', 100, 54, 54.00, 180, 62.00),
(2, @gate_exam_id, 'Data Structures Full Test', '2026-01-23', 50, 43, 86.00, 60, 90.00),
(2, @gate_exam_id, 'Algorithms Topic Test', '2026-01-27', 60, 48, 80.00, 75, 85.00),
(2, @gate_exam_id, 'Operating Systems Full Test', '2026-01-30', 50, 32, 64.00, 60, 72.00),
(2, @gate_exam_id, 'GATE Mock Test - 2 (Subject-wise)', '2026-02-03', 100, 63, 63.00, 175, 68.00),
(2, @gate_exam_id, 'Database Systems Full Test', '2026-02-06', 50, 41, 82.00, 60, 86.00);

-- Practice Tests for CAT
INSERT INTO practice_tests (user_id, exam_id, test_name, test_date, total_marks, obtained_marks, percentage, time_taken_minutes, accuracy_percentage, analysis_notes) VALUES
(2, @cat_exam_id, 'CAT Mock Test - 1 (Full)', '2026-01-25', 100, 45, 45.00, 120, 60.00),
(2, @cat_exam_id, 'Quantitative Aptitude Sectional', '2026-01-28', 34, 20, 58.82, 40, 68.00),
(2, @cat_exam_id, 'Verbal Ability Sectional', '2026-02-01', 34, 22, 64.71, 40, 72.00),
(2, @cat_exam_id, 'LRDI Sectional Test', '2026-02-05', 32, 21, 65.62, 40, 75.00),
(2, @cat_exam_id, 'CAT Mock Test - 2 (Full)', '2026-02-08', 100, 56, 56.00, 118, 67.00);

-- Summary query
SELECT 
    'Subjects' as Category, COUNT(*) as Count FROM subjects WHERE user_id = 2
UNION ALL
SELECT 'Timetable Classes', COUNT(*) FROM timetable WHERE user_id = 2
UNION ALL
SELECT 'Assignments', COUNT(*) FROM assignments WHERE user_id = 2
UNION ALL
SELECT 'College Exams', COUNT(*) FROM exams WHERE user_id = 2
UNION ALL
SELECT '--- COMPETITIVE EXAM PREP ---', 0
UNION ALL
SELECT 'Competitive Exams', COUNT(*) FROM competitive_exams WHERE user_id = 2
UNION ALL
SELECT 'Exam Topics', COUNT(*) FROM exam_topics WHERE user_id IN (SELECT id FROM competitive_exams WHERE user_id = 2)
UNION ALL
SELECT 'Study Sessions', COUNT(*) FROM study_sessions WHERE user_id = 2
UNION ALL
SELECT 'Goals', COUNT(*) FROM exam_goals WHERE user_id = 2
UNION ALL
SELECT 'Practice Tests', COUNT(*) FROM practice_tests WHERE user_id = 2;

