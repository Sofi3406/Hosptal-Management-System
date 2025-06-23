<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total patients
$query = "SELECT COUNT(*) as count FROM patients";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total appointments today
$query = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['appointments_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total doctors
$query = "SELECT COUNT(*) as count FROM staff s JOIN users u ON s.user_id = u.id WHERE u.role = 'doctor'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['doctors'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total revenue this month
$query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM billing WHERE MONTH(bill_date) = MONTH(CURDATE()) AND YEAR(bill_date) = YEAR(CURDATE())";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Recent appointments
$query = "SELECT a.*, p.patient_id, u1.first_name as patient_first, u1.last_name as patient_last,
                 u2.first_name as doctor_first, u2.last_name as doctor_last
          FROM appointments a
          JOIN patients p ON a.patient_id = p.id
          JOIN users u1 ON p.user_id = u1.id
          JOIN staff s ON a.doctor_id = s.id
          JOIN users u2 ON s.user_id = u2.id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hospital Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-hospital"></i>
                <h1>SofiCare Hospital</h1>
            </div>
            <nav class="nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../includes/auth.php?action=logout" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p>Hospital Management System - <?php echo ucfirst($_SESSION['role']); ?> Panel</p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li><a href="billing.php"><i class="fas fa-receipt"></i> Billing</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="staff.php"><i class="fas fa-user-md"></i> Staff</a></li>
                    <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="password-resets.php"><i class="fas fa-key"></i> Password Management</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card patients">
                        <i class="fas fa-users"></i>
                        <h3><?php echo number_format($stats['patients']); ?></h3>
                        <p>Total Patients</p>
                    </div>
                    <div class="stat-card appointments">
                        <i class="fas fa-calendar-check"></i>
                        <h3><?php echo number_format($stats['appointments_today']); ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                    <div class="stat-card doctors">
                        <i class="fas fa-user-md"></i>
                        <h3><?php echo number_format($stats['doctors']); ?></h3>
                        <p>Total Doctors</p>
                    </div>
                    <div class="stat-card revenue">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>$<?php echo number_format($stats['revenue'], 2); ?></h3>
                        <p>Monthly Revenue</p>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-clock"></i> Recent Appointments</h3>
                        <a href="appointments.php" class="btn btn-primary">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_appointments)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">No appointments found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($appointment['patient_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td>
                                    <span class="status <?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
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

    <script src="../assets/js/main.js"></script>
</body>
</html>
