<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin'); // ONLY ADMIN can add patients

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
    $medical_history = trim($_POST['medical_history']);
    $allergies = trim($_POST['allergies']);
    
    // Generate unique patient ID
    $patient_id = 'PAT' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Generate secure username and password
    $username = strtolower($first_name . $last_name . rand(1000, 9999));
    $password = 'Patient@' . rand(1000, 9999); // Secure default password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Check if email already exists
        $query = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->execute([':email' => $email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing['count'] > 0) {
            $error = "Email already exists in the system";
        } else {
            $conn->beginTransaction();
            
            // Insert into users table
            $query = "INSERT INTO users (username, email, password, role, first_name, last_name, phone, address) 
                      VALUES (:username, :email, :password, 'patient', :first_name, :last_name, :phone, :address)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':phone' => $phone,
                ':address' => $address
            ]);
            
            $user_id = $conn->lastInsertId();
            
            // Insert into patients table
            $query = "INSERT INTO patients (user_id, patient_id, date_of_birth, gender, blood_group, 
                      emergency_contact_name, emergency_contact_phone, medical_history, allergies) 
                      VALUES (:user_id, :patient_id, :date_of_birth, :gender, :blood_group, 
                      :emergency_contact_name, :emergency_contact_phone, :medical_history, :allergies)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':patient_id' => $patient_id,
                ':date_of_birth' => $date_of_birth,
                ':gender' => $gender,
                ':blood_group' => $blood_group,
                ':emergency_contact_name' => $emergency_contact_name,
                ':emergency_contact_phone' => $emergency_contact_phone,
                ':medical_history' => $medical_history,
                ':allergies' => $allergies
            ]);
            
            $conn->commit();
            $success = "Patient added successfully!<br><strong>Patient ID:</strong> $patient_id<br><strong>Username:</strong> $username<br><strong>Password:</strong> $password<br><br><em>Please provide these credentials to the patient for their portal access.</em>";
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error adding patient: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient - Hospital Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-hospital"></i>
                <h1>MediCare Hospital</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                <a href="../logout.php" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-user-plus"></i> Add New Patient</h1>
                <p><strong>Admin Only:</strong> Register a new patient in the system</p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php" class="active"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li><a href="billing.php"><i class="fas fa-receipt"></i> Billing</a></li>
                    <li><a href="staff.php"><i class="fas fa-user-md"></i> Staff</a></li>
                    <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-user-plus"></i> Patient Registration Form</h3>
                        <a href="patients.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Patients
                        </a>
                    </div>
                    
                    <div style="padding: 2rem;">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div>
                                <h4><i class="fas fa-user"></i> Personal Information</h4>
                                
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" rows="3"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth *</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender">Gender *</label>
                                    <select id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <h4><i class="fas fa-heartbeat"></i> Medical Information</h4>
                                
                                <div class="form-group">
                                    <label for="blood_group">Blood Group</label>
                                    <select id="blood_group" name="blood_group">
                                        <option value="">Select Blood Group</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_contact_name">Emergency Contact Name *</label>
                                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_contact_phone">Emergency Contact Phone *</label>
                                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="medical_history">Medical History</label>
                                    <textarea id="medical_history" name="medical_history" rows="4" placeholder="Previous surgeries, chronic conditions, etc."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="allergies">Allergies</label>
                                    <textarea id="allergies" name="allergies" rows="3" placeholder="Drug allergies, food allergies, etc."></textarea>
                                </div>
                                
                                <div style="background: #e3f2fd; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                                    <small><i class="fas fa-info-circle"></i> <strong>Note:</strong> A secure username and password will be automatically generated for this patient's portal access.</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                                    <i class="fas fa-save"></i> Register Patient
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
