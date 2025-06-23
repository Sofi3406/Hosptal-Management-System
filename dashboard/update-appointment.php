<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    try {
        $query = "UPDATE appointments SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':id' => $appointment_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Appointment status updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating appointment: ' . $e->getMessage()]);
    }
    exit;
}
?>
