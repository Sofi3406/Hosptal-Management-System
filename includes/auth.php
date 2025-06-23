<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, email, password, role, first_name, last_name, is_active 
                  FROM users WHERE username = :username AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                return true;
            }
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: ../login.php");
            exit();
        }
    }
    
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header("Location: ../unauthorized.php");
            exit();
        }
    }
    
    public function requireAdminOrDoctor() {
        $this->requireLogin();
        if (!in_array($_SESSION['role'], ['admin', 'doctor'])) {
            header("Location: ../unauthorized.php");
            exit();
        }
    }
    
    public function canAccessPatient($patient_id) {
        if ($this->hasRole('admin')) {
            return true; // Admin can access all patients
        }
        
        if ($this->hasRole('doctor')) {
            // Check if doctor has appointments with this patient
            $query = "SELECT COUNT(*) as count FROM appointments a 
                      JOIN staff s ON a.doctor_id = s.id 
                      WHERE s.user_id = :user_id AND a.patient_id = :patient_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':user_id' => $_SESSION['user_id'], ':patient_id' => $patient_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        }
        
        if ($this->hasRole('patient')) {
            // Patient can only access their own data
            $query = "SELECT COUNT(*) as count FROM patients p 
                      WHERE p.user_id = :user_id AND p.id = :patient_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':user_id' => $_SESSION['user_id'], ':patient_id' => $patient_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        }
        
        return false;
    }
}
?>
