<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Get patients
$query = "SELECT p.id, p.patient_id, u.first_name, u.last_name 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY u.first_name, u.last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get doctors
$query = "SELECT s.id, u.first_name, u.last_name, s.specialization, d.name as department
          FROM staff s 
          JOIN users u ON s.user_id = u.id 
          LEFT JOIN departments d ON s.department_id = d.id
          WHERE u.role = 'doctor'
          ORDER BY u.first_name, u.last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);
    
    try {
        // Check if doctor is available at that time
        $query = "SELECT COUNT(*) as count FROM appointments 
                  WHERE doctor_id = :doctor_id 
                  AND appointment_date = :appointment_date 
                  AND appointment_time = :appointment_time 
                  AND status != 'cancelled'";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':doctor_id' => $doctor_id,
            ':appointment_date' => $appointment_date,
            ':appointment_time' => $appointment_time
        ]);
        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conflict['count'] > 0) {
            $error = "Doctor is not available at this time. Please choose a different time.";
        } else {
            // Schedule appointment
            $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 
                      VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, 'scheduled')";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':doctor_id' => $doctor_id,
                ':appointment_date' => $appointment_date,
                ':appointment_time' => $appointment_time,
                ':reason' => $reason
            ]);
            
            $success = "Appointment scheduled successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error scheduling appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment - Hospital Management System</title>
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../logout.php" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-calendar-plus"></i> Schedule Appointment</h1>
                <p>Book a new appointment for a patient</p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php" class="active"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li><a href="billing.php"><i class="fas fa-receipt"></i> Billing</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="staff.php"><i class="fas fa-user-md"></i> Staff</a></li>
                    <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-calendar-plus"></i> Appointment Booking Form</h3>
                        <a href="appointments.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Appointments
                        </a>
                    </div>
                    
                    <div style="padding: 2rem;">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" style="max-width: 600px;">
                            <div class="form-group">
                                <label for="patient_id">Select Patient *</label>
                                <select id="patient_id" name="patient_id" required>
                                    <option value="">Choose a patient</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['patient_id'] . ' - ' . $patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctor_id">Select Doctor *</label>
                                <select id="doctor_id" name="doctor_id" required>
                                    <option value="">Choose a doctor</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                            <?php if ($doctor['specialization']): ?>
                                                - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                            <?php endif; ?>
                                            <?php if ($doctor['department']): ?>
                                                (<?php echo htmlspecialchars($doctor['department']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date *</label>
                                <input type="date" id="appointment_date" name="appointment_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="appointment_time">Appointment Time *</label>
                                <select id="appointment_time" name="appointment_time" required>
                                    <option value="">Select time</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="09:30:00">9:30 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="10:30:00">10:30 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="11:30:00">11:30 AM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="14:30:00">2:30 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="15:30:00">3:30 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                    <option value="16:30:00">4:30 PM</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason">Reason for Visit</label>
                                <textarea id="reason" name="reason" rows="4" placeholder="Describe the reason for this appointment..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-calendar-check"></i> Schedule Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
