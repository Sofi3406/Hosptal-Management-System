<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$database = new Database();
$conn = $database->getConnection();

$error = '';
$patient_info = null;

// Handle patient login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if ($auth->login($username, $password)) {
            if ($_SESSION['role'] == 'patient') {
                // Get patient information
                $query = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone 
                          FROM patients p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE u.id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->execute([':user_id' => $_SESSION['user_id']]);
                $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Access denied. This portal is for patients only.';
                $auth->logout();
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Get patient appointments if logged in
$appointments = [];
if ($patient_info) {
    $query = "SELECT a.*, u.first_name as doctor_first, u.last_name as doctor_last, d.name as department
              FROM appointments a
              JOIN staff s ON a.doctor_id = s.id
              JOIN users u ON s.user_id = u.id
              LEFT JOIN departments d ON s.department_id = d.id
              WHERE a.patient_id = :patient_id
              ORDER BY a.appointment_date DESC, a.appointment_time DESC
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute([':patient_id' => $patient_info['id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php if (!$patient_info): ?>
    <!-- Login Form -->
    <div class="login-container">
        <form class="login-form" method="POST">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem;">
                <i class="fas fa-user-injured"></i>
                <h2>Patient Portal</h2>
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
            
            <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Login to Portal
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
                <strong>Demo Patient Login:</strong><br>
                Username: patient1<br>
                Password: password
            </div>
        </form>
    </div>
    <?php else: ?>
    <!-- Patient Dashboard -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-hospital"></i>
                <h1>MediCare Hospital</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></span>
                <a href="logout.php" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-user-injured"></i> Patient Portal</h1>
                <p>Your personal health information and appointments</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container">
                <!-- Patient Info Card -->
                <div class="table-container" style="margin-bottom: 2rem;">
                    <div class="table-header">
                        <h3><i class="fas fa-id-card"></i> Your Information</h3>
                    </div>
                    <div style="padding: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient_info['patient_id']); ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient_info['phone']); ?></p>
                        </div>
                        <div>
                            <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($patient_info['date_of_birth'])); ?></p>
                            <p><strong>Gender:</strong> <?php echo ucfirst($patient_info['gender']); ?></p>
                            <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient_info['blood_group']); ?></p>
                            <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($patient_info['emergency_contact_name']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Appointments -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-calendar-check"></i> Your Appointments</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #666;">No appointments found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['department'] ?: 'General'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td>
                                    <span class="status <?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>
