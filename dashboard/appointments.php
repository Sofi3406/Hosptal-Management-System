<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Get all appointments with search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u1.first_name LIKE :search OR u1.last_name LIKE :search OR p.patient_id LIKE :search OR u2.first_name LIKE :search OR u2.last_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "a.appointment_date = :date";
    $params[':date'] = $date_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "SELECT a.*, p.patient_id, u1.first_name as patient_first, u1.last_name as patient_last,
                 u2.first_name as doctor_first, u2.last_name as doctor_last, d.name as department
          FROM appointments a
          JOIN patients p ON a.patient_id = p.id
          JOIN users u1 ON p.user_id = u1.id
          JOIN staff s ON a.doctor_id = s.id
          JOIN users u2 ON s.user_id = u2.id
          LEFT JOIN departments d ON s.department_id = d.id
          $where_clause
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Hospital Management System</title>
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
                <h1><i class="fas fa-calendar"></i> Appointment Management</h1>
                <p>Manage patient appointments and schedules</p>
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
                        <h3><i class="fas fa-calendar-check"></i> All Appointments (<?php echo count($appointments); ?>)</h3>
                        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <form method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <input type="text" name="search" placeholder="Search appointments..." 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="">All Status</option>
                                    <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="no_show" <?php echo $status_filter == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                </select>
                                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" 
                                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="submit" class="btn btn-secondary" style="padding: 8px 12px;">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search || $status_filter || $date_filter): ?>
                                    <a href="appointments.php" class="btn btn-secondary" style="padding: 8px 12px;">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                            <a href="schedule-appointment.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Schedule Appointment
                            </a>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #666;">No appointments found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($appointment['patient_id']); ?></small>
                                </td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?><br>
                                    <small><?php echo htmlspecialchars($appointment['department'] ?: 'General'); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td>
                                    <select onchange="updateStatus(<?php echo $appointment['id']; ?>, this.value)" 
                                            class="status <?php echo $appointment['status']; ?>" 
                                            style="border: none; background: transparent; font-weight: 600;">
                                        <option value="scheduled" <?php echo $appointment['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="no_show" <?php echo $appointment['status'] == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                    </select>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="create-bill.php?patient_id=<?php echo $appointment['patient_id']; ?>" 
                                       class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </a>
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

    <script>
        function updateStatus(appointmentId, status) {
            fetch('update-appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `appointment_id=${appointmentId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the visual status
                    const select = event.target;
                    select.className = `status ${status}`;
                    showAlert('Appointment status updated successfully', 'success');
                } else {
                    showAlert('Error updating appointment status', 'error');
                }
            })
            .catch(error => {
                showAlert('Error updating appointment status', 'error');
            });
        }
        
        function viewAppointment(appointmentId) {
            alert('View appointment details feature - Appointment ID: ' + appointmentId);
        }
        
        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
