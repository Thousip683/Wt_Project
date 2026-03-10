<?php
require_once '../config/config.php';
redirectIfLoggedIn();

$pageTitle = 'Register';

// Initialize variables
$errors = [];
$full_name = $email = $course = $semester = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        // Sanitize and validate inputs
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $course = sanitizeInput($_POST['course'] ?? '');
        $semester = sanitizeInput($_POST['semester'] ?? '');

        // Validation
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        } elseif (strlen($full_name) < 3) {
            $errors[] = "Full name must be at least 3 characters.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!validateEmail($email)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($course)) {
            $errors[] = "Course is required.";
        }

        if (empty($semester)) {
            $errors[] = "Semester is required.";
        }

        // Check if email already exists
        if (empty($errors)) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = "Email already registered. Please login.";
            }
            $stmt->close();

            // Insert user if no errors
            if (empty($errors)) {
                $hashed_password = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, course, semester) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $email, $hashed_password, $course, $semester);

                if ($stmt->execute()) {
                    $stmt->close();
                    closeDBConnection($conn);
                    setFlashMessage("Registration successful! Please login.", "success");
                    header("Location: login.php");
                    exit();
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
                $stmt->close();
            }
            closeDBConnection($conn);
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-graduation-cap"></i>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Create your account</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($full_name); ?>" 
                           placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="course">
                        <i class="fas fa-book"></i> Course
                    </label>
                    <select id="course" name="course" required>
                        <option value="">Select your course</option>
                        <option value="B.Tech" <?php echo $course == 'B.Tech' ? 'selected' : ''; ?>>B.Tech</option>
                        <option value="M.Tech" <?php echo $course == 'M.Tech' ? 'selected' : ''; ?>>M.Tech</option>
                        <option value="B.Sc" <?php echo $course == 'B.Sc' ? 'selected' : ''; ?>>B.Sc</option>
                        <option value="M.Sc" <?php echo $course == 'M.Sc' ? 'selected' : ''; ?>>M.Sc</option>
                        <option value="BCA" <?php echo $course == 'BCA' ? 'selected' : ''; ?>>BCA</option>
                        <option value="MCA" <?php echo $course == 'MCA' ? 'selected' : ''; ?>>MCA</option>
                        <option value="Other" <?php echo $course == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester">
                        <i class="fas fa-calendar"></i> Semester
                    </label>
                    <select id="semester" name="semester" required>
                        <option value="">Select semester</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" 
                           placeholder="Create a password (min 6 characters)" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
