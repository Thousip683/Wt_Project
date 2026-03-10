<?php
/**
 * Common Functions
 * Beyond Classroom Platform
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/auth/login.php");
        exit();
    }
}

/**
 * Redirect to dashboard if already logged in
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: " . SITE_URL . "/dashboard.php");
        exit();
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id, full_name, email, course, semester, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user = $result->fetch_assoc();
    
    $stmt->close();
    closeDBConnection($conn);
    
    return $user;
}

/**
 * Format date
 */
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Get user initials for avatar
 */
function getUserInitials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Calculate academic workload
 * Returns: 'Low', 'Medium', or 'High'
 */
function calculateWorkload($user_id) {
    $conn = getDBConnection();
    
    // Count pending assignments due in next 7 days
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending_assignments 
        FROM assignments 
        WHERE user_id = ? 
        AND status != 'Completed' 
        AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_assignments = $result->fetch_assoc()['pending_assignments'];
    $stmt->close();
    
    // Count upcoming exams in next 14 days
    $stmt = $conn->prepare("
        SELECT COUNT(*) as upcoming_exams 
        FROM exams 
        WHERE user_id = ? 
        AND status = 'Upcoming' 
        AND exam_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $upcoming_exams = $result->fetch_assoc()['upcoming_exams'];
    $stmt->close();
    
    closeDBConnection($conn);
    
    // Calculate workload score
    $score = ($pending_assignments * 2) + ($upcoming_exams * 3);
    
    if ($score >= 10) {
        return 'High';
    } elseif ($score >= 5) {
        return 'Medium';
    } else {
        return 'Low';
    }
}

/**
 * Get academic statistics for dashboard
 */
function getAcademicStats($user_id) {
    $conn = getDBConnection();
    
    // Total subjects
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_subjects = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Pending assignments
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM assignments WHERE user_id = ? AND status != 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pending_assignments = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Upcoming exams
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM exams WHERE user_id = ? AND status = 'Upcoming'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $upcoming_exams = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Timetable classes today
    $today = date('l'); // e.g., "Monday"
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM timetable WHERE user_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $classes_today = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    closeDBConnection($conn);
    
    return [
        'subjects' => $total_subjects,
        'pending_assignments' => $pending_assignments,
        'upcoming_exams' => $upcoming_exams,
        'classes_today' => $classes_today,
        'workload' => calculateWorkload($user_id)
    ];
}

/**
 * Get career/course learning statistics for dashboard
 * Returns zeroes gracefully if Stage 4 tables have not been imported yet.
 */
function getCareerStats($user_id) {
    $default = [
        'in_progress_courses' => 0,
        'completed_courses'   => 0,
        'weekly_hours'        => 0,
        'projects'            => 0,
        'active_goals'        => 0
    ];

    try {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE user_id = ? AND status = 'In Progress'");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $in_progress = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE user_id = ? AND status = 'Completed'");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $completed = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

        $stmt = $conn->prepare("SELECT COALESCE(SUM(duration_minutes),0) as total_mins FROM learning_sessions WHERE user_id = ? AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $week_mins = $stmt->get_result()->fetch_assoc()['total_mins']; $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE user_id = ?");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $projects = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM career_goals WHERE user_id = ? AND status = 'Active'");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $active_goals = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

        closeDBConnection($conn);

        return [
            'in_progress_courses' => $in_progress,
            'completed_courses'   => $completed,
            'weekly_hours'        => round($week_mins / 60, 1),
            'projects'            => $projects,
            'active_goals'        => $active_goals
        ];
    } catch (Exception $e) {
        // Stage 4 tables not yet imported — return safe defaults
        return $default;
    }
}
?>
