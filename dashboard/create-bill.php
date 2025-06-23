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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $services = $_POST['services'];
    $quantities = $_POST['quantities'];
    $unit_prices = $_POST['unit_prices'];
    $payment_method = $_POST['payment_method'];
    
    // Generate bill number
    $bill_number = 'BILL' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    try {
        $conn->beginTransaction();
        
        // Calculate total
        $total_amount = 0;
        for ($i = 0; $i < count($services); $i++) {
            if (!empty($services[$i])) {
                $total_amount += $quantities[$i] * $unit_prices[$i];
            }
        }
        
        // Insert bill
        $query = "INSERT INTO billing (patient_id, bill_number, total_amount, payment_status, payment_method, bill_date, due_date) 
                  VALUES (:patient_id, :bill_number, :total_amount, 'pending', :payment_method, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':bill_number' => $bill_number,
            ':total_amount' => $total_amount,
            ':payment_method' => $payment_method
        ]);
        
        $billing_id = $conn->lastInsertId();
        
        // Insert bill items
        for ($i = 0; $i < count($services); $i++) {
            if (!empty($services[$i])) {
                $total_price = $quantities[$i] * $unit_prices[$i];
                
                $query = "INSERT INTO billing_items (billing_id, service_name, quantity, unit_price, total_price) 
                          VALUES (:billing_id, :service_name, :quantity, :unit_price, :total_price)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':billing_id' => $billing_id,
                    ':service_name' => $services[$i],
                    ':quantity' => $quantities[$i],
                    ':unit_price' => $unit_prices[$i],
                    ':total_price' => $total_price
                ]);
            }
        }
        
        $conn->commit();
        $success = "Bill created successfully! Bill Number: $bill_number";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating bill: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bill - Hospital Management System</title>
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
                <h1><i class="fas fa-file-invoice-dollar"></i> Create Bill</h1>
                <p>Generate a new bill for patient services</p>
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
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Billing Form</h3>
                        <a href="billing.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Billing
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
                        
                        <form method="POST" id="billingForm">
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
                            
                            <h4>Services & Charges</h4>
                            <div id="servicesContainer">
                                <div class="service-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 1rem;">
                                    <div class="form-group">
                                        <label>Service/Item</label>
                                        <input type="text" name="services[]" placeholder="e.g., Consultation, X-Ray, Medicine" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Quantity</label>
                                        <input type="number" name="quantities[]" value="1" min="1" required onchange="calculateTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Unit Price ($)</label>
                                        <input type="number" name="unit_prices[]" step="0.01" min="0" required onchange="calculateTotal()">
                                    </div>
                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="text" class="total-price" readonly>
                                    </div>
                                    <button type="button" onclick="removeService(this)" class="btn btn-secondary" style="padding: 8px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="button" onclick="addService()" class="btn btn-secondary" style="margin-bottom: 1rem;">
                                <i class="fas fa-plus"></i> Add Service
                            </button>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                                <div class="form-group">
                                    <label for="payment_method">Payment Method</label>
                                    <select id="payment_method" name="payment_method">
                                        <option value="">Select payment method</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Credit/Debit Card</option>
                                        <option value="insurance">Insurance</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                
                                <div style="text-align: right; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                                    <h3>Total Amount: $<span id="grandTotal">0.00</span></h3>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">
                                <i class="fas fa-save"></i> Create Bill
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addService() {
            const container = document.getElementById('servicesContainer');
            const serviceRow = document.createElement('div');
            serviceRow.className = 'service-row';
            serviceRow.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 1rem;';
            serviceRow.innerHTML = `
                <div class="form-group">
                    <input type="text" name="services[]" placeholder="e.g., Consultation, X-Ray, Medicine">
                </div>
                <div class="form-group">
                    <input type="number" name="quantities[]" value="1" min="1" onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <input type="number" name="unit_prices[]" step="0.01" min="0" onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <input type="text" class="total-price" readonly>
                </div>
                <button type="button" onclick="removeService(this)" class="btn btn-secondary" style="padding: 8px;">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(serviceRow);
        }
        
        function removeService(button) {
            button.closest('.service-row').remove();
            calculateTotal();
        }
        
        function calculateTotal() {
            const rows = document.querySelectorAll('.service-row');
            let grandTotal = 0;
            
            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('input[name="quantities[]"]').value) || 0;
                const unitPrice = parseFloat(row.querySelector('input[name="unit_prices[]"]').value) || 0;
                const total = quantity * unitPrice;
                
                row.querySelector('.total-price').value = '$' + total.toFixed(2);
                grandTotal += total;
            });
            
            document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
        }
        
        // Calculate total on page load
        document.addEventListener('DOMContentLoaded', calculateTotal);
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
