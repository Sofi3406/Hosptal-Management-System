-- Create password reset tokens table for Hospital Management System
USE hospital_management;

-- Drop table if it exists (for clean setup)
DROP TABLE IF EXISTS password_reset_tokens;

-- Create the password reset tokens table
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Verify the table was created
SELECT 'Password reset table created successfully!' as status;
DESCRIBE password_reset_tokens;
