<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Handle payment update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $bill_id = $_POST['bill_id'];
    $paid_amount = $_POST['paid_amount'];
    
    try {
        // Get current bill details
        $query = "SELECT total_amount FROM billing WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $bill_id]);
        $bill = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bill) {
            $total_amount = $bill['total_amount'];
            $payment_status = 'pending';
            
            if ($paid_amount >= $total_amount) {
                $payment_status = 'paid';
            } elseif ($paid_amount > 0) {
                $payment_status = 'partial';
            }
            
            $query = "UPDATE billing SET paid_amount = :paid_amount, payment_status = :payment_status WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':paid_amount' => $paid_amount,
                ':payment_status' => $payment_status,
                ':id' => $bill_id
            ]);
            
            $success = "Payment updated successfully";
        }
    } catch (Exception $e) {
        $error = "Error updating payment: " . $e->getMessage();
    }
}

// Get all billing records with search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR p.patient_id LIKE :search OR b.bill_number LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "b.payment_status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "SELECT b.*, p.patient_id, u.first_name, u.last_name
          FROM billing b
          JOIN patients p ON b.patient_id = p.id
          JOIN users u ON p.user_id = u.id
          $where_clause
          ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Hospital Management System</title>
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
                <h1><i class="fas fa-receipt"></i> Billing Management</h1>
                <p>Manage patient billing and payments</p>
            </div>
        </div>

        <nav class="dashboard-nav">
            <div class="container">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="patients.php"><i class="fas fa-users"></i> Patients</a></li>
                    <li><a href="appointments.php"><i class="fas fa-calendar"></i> Appointments</a></li>
                    <li><a href="billing.php" class="active"><i class="fas fa-receipt"></i> Billing</a></li>
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
                        <h3><i class="fas fa-file-invoice-dollar"></i> All Bills (<?php echo count($bills); ?>)</h3>
                        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <form method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <input type="text" name="search" placeholder="Search bills..." 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="partial" <?php echo $status_filter == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                    <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                </select>
                                <button type="submit" class="btn btn-secondary" style="padding: 8px 12px;">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search || $status_filter): ?>
                                    <a href="billing.php" class="btn btn-secondary" style="padding: 8px 12px;">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                            <a href="create-bill.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Bill
                            </a>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Bill Number</th>
                                <th>Patient</th>
                                <th>Bill Date</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bills)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666;">No billing records found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($bills as $bill): ?>
                            <?php $balance = $bill['total_amount'] - $bill['paid_amount']; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bill['bill_number']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($bill['first_name'] . ' ' . $bill['last_name']); ?><br>
                                    <small><?php echo htmlspecialchars($bill['patient_id']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($bill['bill_date'])); ?></td>
                                <td>$<?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td>$<?php echo number_format($bill['paid_amount'], 2); ?></td>
                                <td>$<?php echo number_format($balance, 2); ?></td>
                                <td>
                                    <span class="status <?php echo $bill['payment_status']; ?>">
                                        <?php echo ucfirst($bill['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="viewBill(<?php echo $bill['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($bill['payment_status'] != 'paid'): ?>
                                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="updatePayment(<?php echo $bill['id']; ?>, <?php echo $bill['total_amount']; ?>, <?php echo $bill['paid_amount']; ?>)">
                                        <i class="fas fa-dollar-sign"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn" style="padding: 5px 10px; font-size: 12px; background: #28a745; color: white;" 
                                            onclick="printBill(<?php echo $bill['id']; ?>)">
                                        <i class="fas fa-print"></i>
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

    <!-- Payment Update Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; max-width: 400px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3><i class="fas fa-dollar-sign"></i> Update Payment</h3>
                <button onclick="closePaymentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" id="paymentForm">
                <input type="hidden" name="update_payment" value="1">
                <input type="hidden" name="bill_id" id="billId">
                <div class="form-group">
                    <label>Total Amount: $<span id="totalAmount"></span></label>
                </div>
                <div class="form-group">
                    <label>Current Paid: $<span id="currentPaid"></span></label>
                </div>
                <div class="form-group">
                    <label for="paid_amount">New Paid Amount *</label>
                    <input type="number" id="paid_amount" name="paid_amount" step="0.01" min="0" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Payment
                </button>
            </form>
        </div>
    </div>

    <script>
        function viewBill(billId) {
            // This would typically open a detailed bill view
            alert('View bill details feature - Bill ID: ' + billId);
        }
        
        function updatePayment(billId, totalAmount, currentPaid) {
            document.getElementById('billId').value = billId;
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
            document.getElementById('currentPaid').textContent = currentPaid.toFixed(2);
            document.getElementById('paid_amount').value = currentPaid;
            document.getElementById('paymentModal').style.display = 'block';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        function printBill(billId) {
            // Open bill in new window for printing
            window.open(`print-bill.php?id=${billId}`, '_blank');
        }
        
        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
