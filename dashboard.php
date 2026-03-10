<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'Dashboard';
$currentUser = getCurrentUser();

// Get academic statistics
$stats = getAcademicStats($_SESSION['user_id']);

// Get career learning statistics
$career_stats = getCareerStats($_SESSION['user_id']);

// Get recent assignments
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT a.*, s.subject_name, s.color 
    FROM assignments a 
    JOIN subjects s ON a.subject_id = s.id 
    WHERE a.user_id = ? AND a.status != 'Completed' 
    ORDER BY a.due_date ASC LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get upcoming exams
$stmt = $conn->prepare("
    SELECT e.*, s.subject_name, s.color 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.user_id = ? AND e.status = 'Upcoming' 
    ORDER BY e.exam_date ASC LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$upcoming_exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get today's classes
$today = date('l');
$stmt = $conn->prepare("
    SELECT t.*, s.subject_name, s.color 
    FROM timetable t 
    JOIN subjects s ON t.subject_id = s.id 
    WHERE t.user_id = ? AND t.day_of_week = ? 
    ORDER BY t.start_time ASC
");
$stmt->bind_param("is", $_SESSION['user_id'], $today);
$stmt->execute();
$todays_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get in-progress courses for dashboard widget
$stmt = $conn->prepare("
    SELECT id, course_name, platform, color, progress_percentage, target_date 
    FROM courses 
    WHERE user_id = ? AND status = 'In Progress' 
    ORDER BY updated_at DESC LIMIT 3
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$inprogress_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeDBConnection($conn);

include 'includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?>! 👋</h1>
        <p class="subtitle">Here's your academic overview for today</p>
    </div>

    <!-- Workload Indicator -->
    <?php 
    $workload = $stats['workload'];
    $workload_colors = [
        'Low' => '#10b981',
        'Medium' => '#f59e0b',
        'High' => '#ef4444'
    ];
    ?>
    <div class="workload-banner" style="background: linear-gradient(135deg, <?php echo $workload_colors[$workload]; ?>20, <?php echo $workload_colors[$workload]; ?>40);">
        <div class="workload-content">
            <i class="fas fa-chart-line"></i>
            <div>
                <h3>Current Workload: <span style="color: <?php echo $workload_colors[$workload]; ?>"><?php echo $workload; ?></span></h3>
                <p>Based on upcoming assignments and exams</p>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3>Subjects</h3>
                <p class="stat-value"><?php echo $stats['subjects']; ?></p>
                <p class="stat-label">Total subjects</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3>Assignments</h3>
                <p class="stat-value"><?php echo $stats['pending_assignments']; ?></p>
                <p class="stat-label">Pending assignments</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Exams</h3>
                <p class="stat-value"><?php echo $stats['upcoming_exams']; ?></p>
                <p class="stat-label">Upcoming exams</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Today's Classes</h3>
                <p class="stat-value"><?php echo $stats['classes_today']; ?></p>
                <p class="stat-label">Classes scheduled</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #6366f1;">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="stat-content">
                <h3>Courses Active</h3>
                <p class="stat-value"><?php echo $career_stats['in_progress_courses']; ?></p>
                <p class="stat-label"><?php echo number_format($career_stats['weekly_hours'],1); ?>h this week</p>
            </div>
        </div>
    </div>

    <div class="quick-links">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="links-grid">
            <a href="modules/academics/subjects.php" class="quick-link">
                <i class="fas fa-book"></i>
                <span>Manage Subjects</span>
            </a>
            <a href="modules/academics/timetable.php" class="quick-link">
                <i class="fas fa-calendar-alt"></i>
                <span>View Timetable</span>
            </a>
            <a href="modules/academics/assignments.php" class="quick-link">
                <i class="fas fa-tasks"></i>
                <span>Track Assignments</span>
            </a>
            <a href="modules/academics/exams.php" class="quick-link">
                <i class="fas fa-file-alt"></i>
                <span>Exam Schedule</span>
            </a>
            <a href="modules/career_learning/my_courses.php" class="quick-link">
                <i class="fas fa-play-circle"></i>
                <span>My Courses</span>
            </a>
            <a href="modules/career_learning/skills.php" class="quick-link">
                <i class="fas fa-star"></i>
                <span>Skills Tracker</span>
            </a>
            <a href="modules/career_learning/projects.php" class="quick-link">
                <i class="fas fa-code-branch"></i>
                <span>My Projects</span>
            </a>
            <a href="modules/career_learning/goals.php" class="quick-link">
                <i class="fas fa-bullseye"></i>
                <span>Career Goals</span>
            </a>
        </div>
    </div>

    <!-- Today's Schedule & Upcoming Tasks -->
    <div class="info-section">
        <!-- Today's Classes -->
        <div class="info-card">
            <div class="info-header">
                <i class="fas fa-calendar-day"></i>
                <h2>Today's Classes (<?php echo $today; ?>)</h2>
            </div>
            <div class="info-content">
                <?php if (empty($todays_classes)): ?>
                    <p class="text-muted">No classes scheduled for today 🎉</p>
                <?php else: ?>
                    <div class="class-list">
                        <?php foreach ($todays_classes as $class): ?>
                        <div class="class-item" style="border-left: 3px solid <?php echo $class['color']; ?>">
                            <div class="class-time"><?php echo date('g:i A', strtotime($class['start_time'])); ?> - <?php echo date('g:i A', strtotime($class['end_time'])); ?></div>
                            <div class="class-name"><?php echo htmlspecialchars($class['subject_name']); ?></div>
                            <div class="class-details">
                                <span class="badge"><?php echo $class['class_type']; ?></span>
                                <?php if ($class['room_number']): ?>
                                <span><i class="fas fa-door-open"></i> <?php echo $class['room_number']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Assignments -->
        <div class="info-card">
            <div class="info-header">
                <i class="fas fa-tasks"></i>
                <h2>Upcoming Assignments</h2>
            </div>
            <div class="info-content">
                <?php if (empty($recent_assignments)): ?>
                    <p class="text-muted">No pending assignments 👍</p>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach ($recent_assignments as $assignment): 
                            $days_left = floor((strtotime($assignment['due_date']) - time()) / 86400);
                            $is_urgent = $days_left <= 2;
                        ?>
                        <div class="task-item" style="border-left: 3px solid <?php echo $assignment['color']; ?>">
                            <div class="task-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            <div class="task-meta">
                                <span class="task-subject"><?php echo htmlspecialchars($assignment['subject_name']); ?></span>
                                <span class="task-due <?php echo $is_urgent ? 'urgent' : ''; ?>">
                                    <i class="fas fa-clock"></i> <?php echo $days_left >= 0 ? $days_left . ' days' : 'Overdue'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="modules/academics/assignments.php" class="view-all-link">View all assignments <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Exams -->
    <?php if (!empty($upcoming_exams)): ?>
    <div class="info-card" style="margin-top: 20px;">
        <div class="info-header">
            <i class="fas fa-file-alt"></i>
            <h2>Upcoming Exams</h2>
        </div>
        <div class="info-content">
            <div class="exam-list">
                <?php foreach ($upcoming_exams as $exam): 
                    $days_until = floor((strtotime($exam['exam_date']) - time()) / 86400);
                ?>
                <div class="exam-item" style="border-left: 3px solid <?php echo $exam['color']; ?>">
                    <div class="exam-name"><?php echo htmlspecialchars($exam['exam_name']); ?></div>
                    <div class="exam-subject"><?php echo htmlspecialchars($exam['subject_name']); ?></div>
                    <div class="exam-date">
                        <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($exam['exam_date'])); ?>
                        <?php if ($days_until <= 7): ?>
                        <span class="exam-soon"><?php echo $days_until; ?> days</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="modules/academics/exams.php" class="view-all-link">View all exams <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- In-Progress Courses Widget -->
    <?php if (!empty($inprogress_courses)): ?>
    <div class="info-card" style="margin-top: 20px;">
        <div class="info-header">
            <i class="fas fa-laptop-code"></i>
            <h2>In-Progress Courses</h2>
        </div>
        <div class="info-content">
            <div class="course-widget-list">
                <?php foreach ($inprogress_courses as $c): 
                    $days_left = $c['target_date'] ? floor((strtotime($c['target_date']) - time()) / 86400) : null;
                ?>
                <div class="course-widget-item">
                    <div class="cwi-header">
                        <span class="cwi-name" style="border-left:3px solid <?php echo $c['color']; ?>;padding-left:8px"><?php echo htmlspecialchars($c['course_name']); ?></span>
                        <span class="cwi-platform"><?php echo $c['platform']; ?></span>
                        <span class="cwi-pct"><?php echo number_format($c['progress_percentage'],0); ?>%</span>
                    </div>
                    <div class="progress-bar-bg" style="margin:6px 0 4px">
                        <div class="progress-bar-fill" style="width:<?php echo $c['progress_percentage']; ?>%;background:<?php echo $c['color']; ?>"></div>
                    </div>
                    <?php if ($days_left !== null): ?>
                    <span class="cwi-deadline"><?php echo $days_left >= 0 ? $days_left . ' days left' : 'Target date passed'; ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="modules/career_learning/my_courses.php" class="view-all-link">View all courses <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.workload-banner {
    padding: 20px 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border: 2px solid rgba(0,0,0,0.05);
}

.workload-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.workload-content i {
    font-size: 32px;
    opacity: 0.8;
}

.workload-content h3 {
    margin: 0 0 5px 0;
    font-size: 20px;
}

.workload-content p {
    margin: 0;
    opacity: 0.8;
}

.quick-links {
    margin: 30px 0;
}

.quick-links h2 {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.quick-link {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: var(--text-primary);
    box-shadow: var(--shadow);
    transition: all 0.3s;
}

.quick-link:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    color: var(--primary-color);
}

.quick-link i {
    font-size: 28px;
    margin-bottom: 10px;
    display: block;
}

.class-list, .task-list, .exam-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.class-item, .task-item, .exam-item {
    background: var(--light-color);
    padding: 12px;
    border-radius: 6px;
}

.class-time, .task-title, .exam-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.class-name, .exam-subject {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 8px;
}

.class-details, .task-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
}

.task-subject {
    color: var(--text-secondary);
}

.task-due {
    margin-left: auto;
    font-weight: 600;
}

.task-due.urgent {
    color: var(--danger-color);
}

.exam-date {
    font-size: 14px;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.exam-soon {
    background: var(--warning-color);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: auto;
}

.view-all-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 15px;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.view-all-link:hover {
    text-decoration: underline;
}

.course-widget-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.course-widget-item {
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.course-widget-item:last-child {
    border-bottom: none;
}

.cwi-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.cwi-name {
    font-weight: 600;
    font-size: 14px;
    flex: 1;
}

.cwi-platform {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #64748b;
    font-weight: 600;
}

.cwi-pct {
    font-size: 13px;
    font-weight: 700;
    color: var(--primary-color);
    min-width: 36px;
    text-align: right;
}

.cwi-deadline {
    font-size: 12px;
    color: var(--text-secondary);
}

.progress-bar-bg {
    background: #f1f5f9;
    border-radius: 6px;
    height: 6px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.4s ease;
}
</style>

<?php include 'includes/footer.php'; ?>
