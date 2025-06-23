<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin'); // Only admin can access inventory

$database = new Database();
$conn = $database->getConnection();

// Get all inventory items
$query = "SELECT * FROM inventory ORDER BY item_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Hospital Management System</title>
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
                <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
                <p>Manage hospital inventory and supplies</p>
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
                    <li><a href="inventory.php" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-boxes"></i> Inventory Items (<?php echo count($inventory_items); ?>)</h3>
                        <button class="btn btn-primary" onclick="alert('Add Item feature coming soon!')">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Supplier</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory_items)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666;">No inventory items found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($inventory_items as $item): ?>
                            <?php 
                                $status = 'normal';
                                if ($item['quantity'] <= $item['minimum_stock']) {
                                    $status = 'low_stock';
                                }
                                if ($item['expiry_date'] && strtotime($item['expiry_date']) < strtotime('+30 days')) {
                                    $status = 'expiring';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>
                                    <?php echo $item['quantity']; ?>
                                    <?php if ($item['quantity'] <= $item['minimum_stock']): ?>
                                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;" title="Low Stock"></i>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                                <td>
                                    <?php if ($item['expiry_date']): ?>
                                        <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?>
                                        <?php if (strtotime($item['expiry_date']) < strtotime('+30 days')): ?>
                                            <i class="fas fa-clock" style="color: #ffc107;" title="Expiring Soon"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status == 'low_stock'): ?>
                                        <span class="status cancelled">Low Stock</span>
                                    <?php elseif ($status == 'expiring'): ?>
                                        <span class="status scheduled">Expiring</span>
                                    <?php else: ?>
                                        <span class="status completed">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="alert('Update stock - Item: <?php echo htmlspecialchars($item['item_name']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="alert('Reorder - Item: <?php echo htmlspecialchars($item['item_name']); ?>')">
                                        <i class="fas fa-shopping-cart"></i>
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
