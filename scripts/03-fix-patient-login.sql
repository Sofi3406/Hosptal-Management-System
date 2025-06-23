-- Fix patient login credentials
USE hospital_management;

-- First, let's see what patients exist
SELECT u.username, u.email, p.patient_id, u.first_name, u.last_name 
FROM patients p 
JOIN users u ON p.user_id = u.id;

-- Update the existing patient1 user with the correct password
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'patient1';

-- If patient1 doesn't exist, let's create it
INSERT IGNORE INTO users (username, email, password, role, first_name, last_name, phone, address) VALUES
('patient1', 'patient1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'Alice', 'Brown', '1234567893', '321 Patient St');

-- Get the user_id for patient1
SET @patient_user_id = (SELECT id FROM users WHERE username = 'patient1');

-- Insert or update patient record
INSERT INTO patients (user_id, patient_id, date_of_birth, gender, blood_group, emergency_contact_name, emergency_contact_phone, medical_history, allergies) VALUES
(@patient_user_id, 'PAT001', '1990-05-15', 'female', 'A+', 'Bob Brown', '1234567894', 'No significant medical history', 'None known')
ON DUPLICATE KEY UPDATE
patient_id = 'PAT001',
date_of_birth = '1990-05-15',
gender = 'female',
blood_group = 'A+',
emergency_contact_name = 'Bob Brown',
emergency_contact_phone = '1234567894';

-- Verify the patient login credentials
SELECT 'Patient login verification:' as info;
SELECT u.username, u.email, p.patient_id, u.first_name, u.last_name,
       CASE WHEN u.password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
            THEN 'Password hash matches' 
            ELSE 'Password hash does NOT match' 
       END as password_status
FROM patients p 
JOIN users u ON p.user_id = u.id 
WHERE u.username = 'patient1';
