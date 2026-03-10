<?php
if (!defined('BASE_PATH')) {
    require_once '../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if (isLoggedIn()): 
        $currentUser = getCurrentUser();
    ?>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <i class="fas fa-graduation-cap"></i>
                <span><?php echo SITE_NAME; ?></span>
            </div>
            <div class="nav-menu">
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <div class="nav-dropdown">
                    <a href="#" class="nav-link">
                        <i class="fas fa-graduation-cap"></i> Academics <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?php echo SITE_URL; ?>/modules/academics/subjects.php"><i class="fas fa-book"></i> Subjects</a>
                        <a href="<?php echo SITE_URL; ?>/modules/academics/timetable.php"><i class="fas fa-calendar-alt"></i> Timetable</a>
                        <a href="<?php echo SITE_URL; ?>/modules/academics/assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                        <a href="<?php echo SITE_URL; ?>/modules/academics/exams.php"><i class="fas fa-file-alt"></i> Exams</a>
                    </div>
                </div>
                <div class="nav-dropdown">
                    <a href="#" class="nav-link">
                        <i class="fas fa-trophy"></i> Exam Prep <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?php echo SITE_URL; ?>/modules/exam_prep/my_exams.php"><i class="fas fa-trophy"></i> My Exams</a>
                        <a href="<?php echo SITE_URL; ?>/modules/exam_prep/practice_tests.php"><i class="fas fa-file-alt"></i> Practice Tests</a>
                    </div>
                </div>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
                <div class="nav-user">
                    <div class="user-avatar">
                        <?php echo getUserInitials($currentUser['full_name']); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
    <div class="flash-message flash-<?php echo $flash['type']; ?>">
        <div class="container">
            <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($flash['message']); ?></span>
            <button class="flash-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    </div>
    <?php endif; ?>

    <main class="main-content">
