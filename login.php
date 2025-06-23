<?php
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
$error = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'patient':
            header("Location: patient-dashboard.php");
            break;
        case 'admin':
        case 'doctor':
        case 'nurse':
        case 'receptionist':
            header("Location: dashboard/index.php");
            break;
        default:
            header("Location: unauthorized.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if ($auth->login($username, $password)) {
            // Redirect based on role
            switch ($_SESSION['role']) {
                case 'patient':
                    header("Location: patient-dashboard.php");
                    break;
                case 'admin':
                case 'doctor':
                case 'nurse':
                case 'receptionist':
                    header("Location: dashboard/index.php");
                    break;
                default:
                    header("Location: unauthorized.php");
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-hospital"></i>
                <h2>Hospital Login</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>

            <div style="text-align: center; margin-top: 1rem;">
                <a href="forgot-password.php" style="color: #2c5aa0; text-decoration: none;">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="index.html" style="color: #2c5aa0; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
            
            <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px; font-size: 0.9rem;">
                <strong>Demo Credentials:</strong><br>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;">
                    <div>
                        <strong>Admin:</strong><br>
                        Username: admin<br>
                        Password: password
                    </div>
                    <div>
                        <strong>Doctor:</strong><br>
                        Username: dr.smith<br>
                        Password: password
                    </div>
                </div>
                <div style="margin-top: 1rem; padding: 0.5rem; background: #e8f5e8; border-radius: 3px;">
                    <strong>Patient Portal:</strong><br>
                    Username: patient1<br>
                    Password: password<br>
                    <small><i class="fas fa-info-circle"></i> Use the Patient Portal link for patient access</small>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
