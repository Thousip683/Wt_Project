<?php
require_once '../config/config.php';
redirectIfLoggedIn();

$pageTitle = 'Login';

// Initialize variables
$errors = [];
$email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        // Sanitize inputs
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($email)) {
            $errors[] = "Email is required.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        }

        // Authenticate user
        if (empty($errors)) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (verifyPassword($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    $stmt->close();
                    closeDBConnection($conn);
                    
                    setFlashMessage("Welcome back, " . $user['full_name'] . "!", "success");
                    header("Location: " . SITE_URL . "/dashboard.php");
                    exit();
                } else {
                    $errors[] = "Invalid email or password.";
                }
            } else {
                $errors[] = "Invalid email or password.";
            }
            
            $stmt->close();
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
                <p>Login to your account</p>
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
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="Enter your email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
