<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'Profile';
$currentUser = getCurrentUser();

// Initialize variables
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token.";
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $course = sanitizeInput($_POST['course'] ?? '');
        $semester = sanitizeInput($_POST['semester'] ?? '');

        // Validation
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }

        if (empty($course)) {
            $errors[] = "Course is required.";
        }

        if (empty($semester)) {
            $errors[] = "Semester is required.";
        }

        // Update profile
        if (empty($errors)) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, course = ?, semester = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $course, $semester, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $_SESSION['user_name'] = $full_name;
                setFlashMessage("Profile updated successfully!", "success");
                header("Location: profile.php");
                exit();
            } else {
                $errors[] = "Failed to update profile.";
            }

            $stmt->close();
            closeDBConnection($conn);
        }
    }
    
    // Reload user data
    $currentUser = getCurrentUser();
}

$csrf_token = generateCSRFToken();

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <p>Manage your account information</p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-avatar-section">
                <div class="profile-avatar-large">
                    <?php echo getUserInitials($currentUser['full_name']); ?>
                </div>
                <h2><?php echo htmlspecialchars($currentUser['full_name']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
            </div>

            <form method="POST" action="" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                        <small class="form-hint">Email cannot be changed</small>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    
                    <div class="form-group">
                        <label for="course">Course</label>
                        <select id="course" name="course" required>
                            <option value="">Select your course</option>
                            <option value="B.Tech" <?php echo $currentUser['course'] == 'B.Tech' ? 'selected' : ''; ?>>B.Tech</option>
                            <option value="M.Tech" <?php echo $currentUser['course'] == 'M.Tech' ? 'selected' : ''; ?>>M.Tech</option>
                            <option value="B.Sc" <?php echo $currentUser['course'] == 'B.Sc' ? 'selected' : ''; ?>>B.Sc</option>
                            <option value="M.Sc" <?php echo $currentUser['course'] == 'M.Sc' ? 'selected' : ''; ?>>M.Sc</option>
                            <option value="BCA" <?php echo $currentUser['course'] == 'BCA' ? 'selected' : ''; ?>>BCA</option>
                            <option value="MCA" <?php echo $currentUser['course'] == 'MCA' ? 'selected' : ''; ?>>MCA</option>
                            <option value="Other" <?php echo $currentUser['course'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semester">Current Semester</label>
                        <select id="semester" name="semester" required>
                            <option value="">Select semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $currentUser['semester'] == $i ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>

            <div class="profile-meta">
                <p><i class="fas fa-clock"></i> Member since <?php echo formatDate($currentUser['created_at'], 'd F Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
