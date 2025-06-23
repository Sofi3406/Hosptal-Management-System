USE hospital_management;

-- Insert departments
INSERT INTO departments (name, description) VALUES
('Cardiology', 'Heart and cardiovascular system treatment'),
('Neurology', 'Brain and nervous system treatment'),
('Orthopedics', 'Bone and joint treatment'),
('Pediatrics', 'Children healthcare'),
('Emergency', 'Emergency medical services');

-- Insert admin user
INSERT INTO users (username, email, password, role, first_name, last_name, phone, address) VALUES
('admin', 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Administrator', '1234567890', '123 Hospital St');

-- Insert sample doctors
INSERT INTO users (username, email, password, role, first_name, last_name, phone, address) VALUES
('dr.smith', 'dr.smith@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'John', 'Smith', '1234567891', '456 Medical Ave'),
('dr.johnson', 'dr.johnson@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Sarah', 'Johnson', '1234567892', '789 Health Blvd');

-- Insert staff records for doctors
INSERT INTO staff (user_id, employee_id, department_id, specialization, qualification, experience_years, salary, hire_date) VALUES
(2, 'DOC001', 1, 'Cardiologist', 'MD, FACC', 10, 150000.00, '2020-01-15'),
(3, 'DOC002', 2, 'Neurologist', 'MD, PhD', 8, 140000.00, '2021-03-20');

-- Insert sample patient
INSERT INTO users (username, email, password, role, first_name, last_name, phone, address) VALUES
('patient1', 'patient1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'Alice', 'Brown', '1234567893', '321 Patient St');

INSERT INTO patients (user_id, patient_id, date_of_birth, gender, blood_group, emergency_contact_name, emergency_contact_phone, medical_history, allergies) VALUES
(4, 'PAT001', '1990-05-15', 'female', 'A+', 'Bob Brown', '1234567894', 'No significant medical history', 'None known');

-- Insert sample inventory items
INSERT INTO inventory (item_name, category, quantity, unit_price, supplier, expiry_date, minimum_stock) VALUES
('Paracetamol 500mg', 'Medicine', 1000, 0.50, 'PharmaCorp', '2025-12-31', 100),
('Surgical Gloves', 'Medical Supplies', 500, 2.00, 'MedSupply Inc', '2024-06-30', 50),
('Stethoscope', 'Equipment', 25, 150.00, 'MedEquip Ltd', NULL, 5);
