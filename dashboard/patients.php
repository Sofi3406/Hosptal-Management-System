<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdminOrDoctor(); // Only admin and doctors can view patients

$database = new Database();
$conn = $database->getConnection();

// Handle patient deletion (ADMIN ONLY)
if (isset($_GET['delete']) && $_SESSION['role'] == 'admin') {
    $patient_id = $_GET['delete'];
    try {
        $conn->beginTransaction();
        
        // Get user_id first
        $query = "SELECT user_id FROM patients WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($patient) {
            // Delete patient record
            $query = "DELETE FROM patients WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $patient_id]);
            
            // Delete user record
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $patient['user_id']]);
            
            $conn->commit();
            $success = "Patient deleted successfully";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error deleting patient: " . $e->getMessage();
    }
}

// Get patients based on role
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if ($_SESSION['role'] == 'doctor') {
    // Doctors can only see their patients (those with appointments)
    $where_clause = "WHERE p.id IN (
        SELECT DISTINCT a.patient_id FROM appointments a 
        JOIN staff s ON a.doctor_id = s.id 
        WHERE s.user_id = :doctor_id
    )";
    $params[':doctor_id'] = $_SESSION['user_id'];
    
    if ($search) {
        $where_clause .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR p.patient_id LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
} else {
    // Admin can see all patients
    if ($search) {
        $where_clause = "WHERE (u.first_name LIKE :search OR u.last_name LIKE :search OR p.patient_id LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
}

$query = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.username
          FROM patients p 
          LEFT JOIN users u ON p.user_id = u.id 
          $where_clause
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - Hospital Management System</title>
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
                <h1><i class="fas fa-users"></i> Patient Management</h1>
                <p>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <strong>Admin View:</strong> Manage all patient records and information
                    <?php else: ?>
                        <strong>Doctor View:</strong> View your assigned patients only
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php" class="active"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
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
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            <i class="fas fa-users"></i> 
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                All Patients (<?php echo count($patients); ?>)
                            <?php else: ?>
                                Your Patients (<?php echo count($patients); ?>)
                            <?php endif; ?>
                        </h3>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <form method="GET" style="display: flex; gap: 0.5rem;">
                                <input type="text" name="search" placeholder="Search patients..." 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="submit" class="btn btn-secondary" style="padding: 8px 12px;">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search): ?>
                                    <a href="patients.php" class="btn btn-secondary" style="padding: 8px 12px;">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="add-patient.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Patient
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Gender</th>
                                <th>Blood Group</th>
                                <th>Phone</th>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th>Username</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="<?php echo $_SESSION['role'] == 'admin' ? '8' : '7'; ?>" style="text-align: center; color: #666;">
                                    <?php echo $search ? "No patients found matching '$search'" : "No patients found"; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($patient['patient_id']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?><br>
                                    <small><?php echo htmlspecialchars($patient['email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></td>
                                <td><?php echo ucfirst($patient['gender']); ?></td>
                                <td><?php echo htmlspecialchars($patient['blood_group']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <td><code><?php echo htmlspecialchars($patient['username']); ?></code></td>
                                <?php endif; ?>
                                <td>
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="viewPatient(<?php echo $patient['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="schedule-appointment.php?patient_id=<?php echo $patient['id']; ?>" 
                                       class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-calendar-plus"></i>
                                    </a>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <button class="btn" style="padding: 5px 10px; font-size: 12px; background: #dc3545; color: white;" 
                                            onclick="deletePatient(<?php echo $patient['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Details Modal -->
    <div id="patientModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; max-width: 600px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3><i class="fas fa-user"></i> Patient Details</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="patientDetails"></div>
        </div>
    </div>

    <script>
        function viewPatient(patientId) {
            fetch(`get-patient-details.php?id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const patient = data.patient;
                        document.getElementById('patientDetails').innerHTML = `
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div><strong>Patient ID:</strong> ${patient.patient_id}</div>
                                <div><strong>Name:</strong> ${patient.first_name} ${patient.last_name}</div>
                                <div><strong>Email:</strong> ${patient.email}</div>
                                <div><strong>Phone:</strong> ${patient.phone}</div>
                                <div><strong>Date of Birth:</strong> ${new Date(patient.date_of_birth).toLocaleDateString()}</div>
                                <div><strong>Gender:</strong> ${patient.gender}</div>
                                <div><strong>Blood Group:</strong> ${patient.blood_group}</div>
                                <div><strong>Emergency Contact:</strong> ${patient.emergency_contact_name}</div>
                                <div style="grid-column: 1 / -1;"><strong>Address:</strong> ${patient.address}</div>
                                <div style="grid-column: 1 / -1;"><strong>Medical History:</strong> ${patient.medical_history || 'None'}</div>
                                <div style="grid-column: 1 / -1;"><strong>Allergies:</strong> ${patient.allergies || 'None'}</div>
                            </div>
                        `;
                        document.getElementById('patientModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error loading patient details');
                });
        }
        
        function closeModal() {
            document.getElementById('patientModal').style.display = 'none';
        }
        
        function deletePatient(patientId) {
            if (confirm('Are you sure you want to delete this patient? This will also delete their login account and cannot be undone.')) {
                window.location.href = `patients.php?delete=${patientId}`;
            }
        }
        
        document.getElementById('patientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
