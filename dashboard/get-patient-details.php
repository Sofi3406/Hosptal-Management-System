<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAdminOrDoctor();

$database = new Database();
$conn = $database->getConnection();

if (isset($_GET['id'])) {
    $patient_id = $_GET['id'];
    
    // Check if user can access this patient
    if (!$auth->canAccessPatient($patient_id)) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $query = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.address 
              FROM patients p 
              LEFT JOIN users u ON p.user_id = u.id 
              WHERE p.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($patient) {
        echo json_encode(['success' => true, 'patient' => $patient]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Patient ID not provided']);
}
?>
