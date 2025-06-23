<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$auth = new Auth();
$auth->requireRole('patient'); // ONLY PATIENTS can access this

$database = new Database();
$conn = $database->getConnection();

// Get patient information
$query = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.address 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          WHERE u.id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$patient_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient_info) {
    header("Location: login.php");
    exit();
}

// Get patient's appointments
$query = "SELECT a.*, u.first_name as doctor_first, u.last_name as doctor_last, 
                 d.name as department, s.specialization
          FROM appointments a
          JOIN staff s ON a.doctor_id = s.id
          JOIN users u ON s.user_id = u.id
          LEFT JOIN departments d ON s.department_id = d.id
          WHERE a.patient_id = :patient_id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $conn->prepare($query);
$stmt->execute([':patient_id' => $patient_info['id']]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's bills
$query = "SELECT b.*, bi.service_name, bi.quantity, bi.unit_price, bi.total_price
          FROM billing b
          LEFT JOIN billing_items bi ON b.id = bi.billing_id
          WHERE b.patient_id = :patient_id
          ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([':patient_id' => $patient_info['id']]);
$billing_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group billing items by bill
$bills = [];
foreach ($billing_records as $record) {
    $bill_id = $record['id'];
    if (!isset($bills[$bill_id])) {
        $bills[$bill_id] = [
            'id' => $record['id'],
            'bill_number' => $record['bill_number'],
            'total_amount' => $record['total_amount'],
            'paid_amount' => $record['paid_amount'],
            'payment_status' => $record['payment_status'],
            'bill_date' => $record['bill_date'],
            'items' => []
        ];
    }
    if ($record['service_name']) {
        $bills[$bill_id]['items'][] = [
            'service_name' => $record['service_name'],
            'quantity' => $record['quantity'],
            'unit_price' => $record['unit_price'],
            'total_price' => $record['total_price']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Hospital Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <span>Welcome, <?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?> (Patient)</span>
                <a href="logout.php" class="btn-login">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><i class="fas fa-user-injured"></i> My Patient Dashboard</h1>
                <p>Your personal health information and medical records</p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="container">
                <!-- Patient Info Card -->
                <div class="table-container" style="margin-bottom: 2rem;">
                    <div class="table-header">
                        <h3><i class="fas fa-id-card"></i> My Information</h3>
                        <span class="status completed">Patient ID: <?php echo htmlspecialchars($patient_info['patient_id']); ?></span>
                    </div>
                    <div style="padding: 2rem; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4><i class="fas fa-user"></i> Personal Details</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient_info['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($patient_info['address']); ?></p>
                        </div>
                        <div>
                            <h4><i class="fas fa-heartbeat"></i> Medical Details</h4>
                            <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($patient_info['date_of_birth'])); ?></p>
                            <p><strong>Age:</strong> <?php echo date_diff(date_create($patient_info['date_of_birth']), date_create('today'))->y; ?> years</p>
                            <p><strong>Gender:</strong> <?php echo ucfirst($patient_info['gender']); ?></p>
                            <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient_info['blood_group']); ?></p>
                        </div>
                        <div>
                            <h4><i class="fas fa-phone"></i> Emergency Contact</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($patient_info['emergency_contact_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient_info['emergency_contact_phone']); ?></p>
                            <br>
                            <p><strong>Allergies:</strong> <?php echo htmlspecialchars($patient_info['allergies'] ?: 'None reported'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Appointments -->
                <div class="table-container" style="margin-bottom: 2rem;">
                    <div class="table-header">
                        <h3><i class="fas fa-calendar-check"></i> My Appointments</h3>
                        <span><?php echo count($appointments); ?> total appointments</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #666;">No appointments scheduled</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <strong>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($appointment['specialization'] ?: 'General Practice'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['department'] ?: 'General'); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                                </td>
                                <td>
                                    <span class="status <?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['reason'] ?: 'Regular checkup'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Billing -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> My Bills</h3>
                        <span><?php echo count($bills); ?> billing records</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Bill Number</th>
                                <th>Date</th>
                                <th>Services</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bills)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #666;">No billing records found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($bills as $bill): ?>
                            <?php $balance = $bill['total_amount'] - $bill['paid_amount']; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bill['bill_number']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($bill['bill_date'])); ?></td>
                                <td>
                                    <?php foreach ($bill['items'] as $item): ?>
                                        <small><?php echo htmlspecialchars($item['service_name']); ?> (<?php echo $item['quantity']; ?>)<br></small>
                                    <?php endforeach; ?>
                                </td>
                                <td>$<?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td>$<?php echo number_format($bill['paid_amount'], 2); ?></td>
                                <td>$<?php echo number_format($balance, 2); ?></td>
                                <td>
                                    <span class="status <?php echo $bill['payment_status']; ?>">
                                        <?php echo ucfirst($bill['payment_status']); ?>
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

    <script src="assets/js/main.js"></script>
</body>
</html>
