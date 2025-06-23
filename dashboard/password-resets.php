<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin'); // Only admin can view password resets

$database = new Database();
$conn = $database->getConnection();

// Handle manual password reset by admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_user_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (!empty($new_password) && strlen($new_password) >= 6) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':password' => $hashed_password, ':user_id' => $user_id]);
            
            $success = "Password reset successfully for user ID: $user_id";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    } else {
        $error = "Password must be at least 6 characters long";
    }
}

// Get all password reset requests
$query = "SELECT prt.*, u.username, u.email, u.first_name, u.last_name, u.role
          FROM password_reset_tokens prt
          JOIN users u ON prt.user_id = u.id
          ORDER BY prt.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$reset_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users for manual reset
$query = "SELECT id, username, email, first_name, last_name, role FROM users WHERE is_active = 1 ORDER BY first_name, last_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Management - Hospital Management System</title>
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Admin)</span>
                <a href="../logout.php" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-key"></i> Password Management</h1>
                <p><strong>Admin Only:</strong> Manage user passwords and reset requests</p>
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
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
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

                <!-- Manual Password Reset -->
                <div class="table-container" style="margin-bottom: 2rem;">
                    <div class="table-header">
                        <h3><i class="fas fa-user-cog"></i> Manual Password Reset</h3>
                    </div>
                    <div style="padding: 2rem;">
                        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label for="user_id">Select User</label>
                                <select id="user_id" name="user_id" required>
                                    <option value="">Choose a user</option>
                                    <?php foreach ($all_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ') - ' . ucfirst($user['role'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Minimum 6 characters">
                            </div>
                            <button type="submit" name="reset_user_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Password Reset Requests -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-history"></i> Password Reset Requests (<?php echo count($reset_requests); ?>)</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Requested</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Token</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reset_requests)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #666;">No password reset requests found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($reset_requests as $request): ?>
                            <?php 
                                $is_expired = strtotime($request['expires_at']) < time();
                                $status = $request['used'] ? 'used' : ($is_expired ? 'expired' : 'active');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($request['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td><?php echo ucfirst($request['role']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($request['expires_at'])); ?></td>
                                <td>
                                    <span class="status <?php echo $status; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <small style="font-family: monospace;"><?php echo substr($request['token'], 0, 16); ?>...</small>
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
