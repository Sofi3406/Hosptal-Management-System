<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin'); // Only admin can access staff management

$database = new Database();
$conn = $database->getConnection();

// Get all staff with their user information
$query = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.role, d.name as department_name
          FROM staff s 
          JOIN users u ON s.user_id = u.id 
          LEFT JOIN departments d ON s.department_id = d.id
          ORDER BY u.first_name, u.last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Hospital Management System</title>
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
                <h1><i class="fas fa-user-md"></i> Staff Management</h1>
                <p>Manage hospital staff and their information</p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li><a href="billing.php"><i class="fas fa-receipt"></i> Billing</a></li>
                    <li><a href="staff.php" class="active"><i class="fas fa-user-md"></i> Staff</a></li>
                    <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-user-md"></i> All Staff Members (<?php echo count($staff_members); ?>)</h3>
                        <button class="btn btn-primary" onclick="alert('Add Staff feature coming soon!')">
                            <i class="fas fa-plus"></i> Add Staff
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($staff_members)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666;">No staff members found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($staff['employee_id']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?><br>
                                    <small><?php echo htmlspecialchars($staff['email']); ?></small>
                                </td>
                                <td>
                                    <span class="status <?php echo $staff['role']; ?>">
                                        <?php echo ucfirst($staff['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($staff['department_name'] ?: 'Not Assigned'); ?></td>
                                <td><?php echo htmlspecialchars($staff['specialization'] ?: 'N/A'); ?></td>
                                <td><?php echo $staff['experience_years'] ? $staff['experience_years'] . ' years' : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="alert('View staff details - ID: <?php echo $staff['id']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="alert('Edit staff - ID: <?php echo $staff['id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
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
