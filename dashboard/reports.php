<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin'); // Only admin can access reports

$database = new Database();
$conn = $database->getConnection();

// Get report data
$reports = [];

// Monthly patient registrations
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
          FROM patients 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$reports['patient_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly revenue
$query = "SELECT DATE_FORMAT(bill_date, '%Y-%m') as month, SUM(total_amount) as revenue 
          FROM billing 
          WHERE bill_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
          ORDER BY month DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$reports['monthly_revenue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Appointment statistics
$query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->execute();
$reports['appointment_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Department wise patient count
$query = "SELECT d.name as department, COUNT(a.id) as patient_count
          FROM departments d
          LEFT JOIN staff s ON d.id = s.department_id
          LEFT JOIN appointments a ON s.id = a.doctor_id
          GROUP BY d.id, d.name
          ORDER BY patient_count DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$reports['department_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Hospital Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                <p>Hospital performance reports and statistics</p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li><a href="billing.php"><i class="fas fa-receipt"></i> Billing</a></li>
                    <li><a href="staff.php"><i class="fas fa-user-md"></i> Staff</a></li>
                    <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-chart-line"></i> Monthly Revenue</h3>
                        </div>
                        <div style="padding: 2rem;">
                            <canvas id="revenueChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-chart-pie"></i> Appointment Status</h3>
                        </div>
                        <div style="padding: 2rem;">
                            <canvas id="appointmentChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-users"></i> Patient Registrations</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>New Patients</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports['patient_registrations'] as $row): ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($row['month'] . '-01')); ?></td>
                                    <td><?php echo $row['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-hospital"></i> Department Statistics</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Appointments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports['department_stats'] as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td><?php echo $row['patient_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode($reports['monthly_revenue']); ?>;
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue ($)',
                    data: revenueData.map(item => item.revenue),
                    borderColor: '#2c5aa0',
                    backgroundColor: 'rgba(44, 90, 160, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Appointment Status Chart
        const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
        const appointmentData = <?php echo json_encode($reports['appointment_stats']); ?>;
        
        new Chart(appointmentCtx, {
            type: 'doughnut',
            data: {
                labels: appointmentData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
                datasets: [{
                    data: appointmentData.map(item => item.count),
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#dc3545',
                        '#ffc107'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
