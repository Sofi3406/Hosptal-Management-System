# Hosptal-Management-System

A comprehensive web-based Hospital Management System built with PHP, MySQL, HTML, CSS, and JavaScript. This system provides complete healthcare management functionality for hospitals, clinics, and medical facilities.
## 📋 Table of Contents

- [Features](#-features)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [User Roles](#-user-roles)
- [Default Credentials](#-default-credentials)
- [File Structure](#-file-structure)
- [Security Features](#-security-features)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 👨‍⚕️ Patient Management
- Patient registration and profile management
- Medical history tracking
- Emergency contact information
- Blood group and allergy management
- Patient portal for self-service access

### 📅 Appointment System
- Online appointment scheduling
- Doctor availability management
- Appointment status tracking (Scheduled, Completed, Cancelled, No Show)
- Real-time appointment updates
- Conflict detection for double bookings

### 💰 Billing & Finance
- Comprehensive billing system
- Multiple payment methods support
- Payment tracking (Pending, Partial, Paid)
- Invoice generation and printing
- Revenue reporting

### 👥 Staff Management
- Employee registration and management
- Role-based access control
- Department assignment
- Qualification and experience tracking
- Staff scheduling

### 📦 Inventory Management
- Medical supplies tracking
- Stock level monitoring
- Expiry date alerts
- Low stock notifications
- Supplier management

### 📊 Reports & Analytics
- Patient registration trends
- Revenue analytics
- Appointment statistics
- Department-wise reports
- Interactive charts and graphs

### 🔐 Security Features
- Secure user authentication
- Password reset functionality
- Role-based permissions
- Session management
- Data encryption

## 🖥 System Requirements

### Server Requirements:
- PHP: 7.4 or higher
- MySQL: 5.7 or higher (or MariaDB 10.2+)
- Web Server: Apache 2.4+ or Nginx 1.18+
- Memory: Minimum 512MB RAM
- Storage: Minimum 100MB free space

### PHP Extensions Required:
- PDO
- PDO_MySQL
- Session
- JSON
- OpenSSL (for password hashing)

### Browser Compatibility:
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## 🚀 Installation

### Step 1: Download the System
\`\`\`bash
# Clone the repository or download the ZIP file
git clone https://github.com/yourusername/hospital-management-system.git
cd hospital-management-system
\`\`\`

### Step 2: Web Server Setup
1. Copy all files to your web server directory:
   - XAMPP/WAMP: htdocs/hospital-management/
   - LAMP: /var/www/html/hospital-management/

2. Ensure proper file permissions:
\`\`\`bash
chmod 755 -R /path/to/hospital-management/
chmod 644 config/database.php
\`\`\`

### Step 3: Database Configuration
1. Edit config/database.php:
\`\`\`php
private $host = 'localhost';        // Your database host
private $db_name = 'hospital_management';  // Database name
private $username = 'your_username';       // Database username
private $password = 'your_password';       // Database password
\`\`\`

### Method 1: Automatic Setup (Recommended)
1. Open your browser and navigate to: http://localhost/hospital-management/setup-check.php
2. Follow the setup verification steps
3. Run the SQL scripts in order:
   - scripts/01-create-database.sql
   - scripts/02-insert-sample-data.sql
   - scripts/03-fix-patient-login.sql
   - scripts/04-password-reset-table.sql

### Method 2: Manual Setup
1. Create a new MySQL database:
\`\`\`sql
CREATE DATABASE hospital_management;
\`\`\`

2. Import the SQL files in order:
\`\`\`bash
mysql -u username -p hospital_management < scripts/01-create-database.sql
mysql -u username -p hospital_management < scripts/02-insert-sample-data.sql
mysql -u username -p hospital_management < scripts/03-fix-patient-login.sql
mysql -u username -p hospital_management < scripts/04-password-reset-table.sql
\`\`\`

### Database Tables Created:
- users - User authentication and basic info
- patients - Patient-specific information
- staff - Hospital staff details
- departments - Hospital departments
- appointments - Appointment scheduling
- billing - Billing and payment records
- billing_items - Individual billing items
- inventory - Medical supplies inventory
- password_reset_tokens - Password reset functionality

## ⚙️ Configuration

### Environment Setup
1. Development Environment:
   - Enable error reporting in PHP
   - Set display_errors = On in php.ini

2. Production Environment:
   - Disable error reporting
   - Enable HTTPS
   - Set secure session cookies
   - Configure proper backup procedures

### Security Configuration
1. Change default passwords immediately
2. Configure SSL/TLS certificates
3. Set up regular database backups
4. Configure firewall rules
5. Enable PHP security extensions

## 📖 Usage

### Accessing the System
1. Main Website: http://localhost/hospital-management/
2. Staff Login: http://localhost/hospital-management/login.php
3. Patient Portal: http://localhost/hospital-management/patient-portal.php

### First Time Setup
1. Login as admin using default credentials
2. Change the default admin password
3. Add hospital departments
4. Register doctors and staff
5. Configure system settings

### Daily Operations
1. Reception: Register new patients, schedule appointments
2. Doctors: View patient records, update medical history
3. Billing: Generate bills, process payments
4. Admin: Monitor system, generate reports

## 👤 User Roles

### 🔴 Administrator
- Full system access
- User management
- System configuration
- Reports and analytics
- Staff management
- Inventory control

### 👨‍⚕️ Doctor
- Patient records access
- Appointment management
- Medical history updates
- Prescription management
- Limited billing access

### 👩‍⚕️ Nurse
- Patient care records
- Appointment assistance
- Basic patient information
- Inventory usage tracking

### 🏥 Receptionist
- Patient registration
- Appointment scheduling
- Basic billing operations
- Visitor management

### 🤒 Patient
- Personal health records
- Appointment viewing
- Bill status checking
- Profile management

## 🔑 Default Credentials

### Staff Login (`login.php`):
| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| Admin | admin | password | Full System Access |
| Doctor | dr.smith | password | Medical Records & Appointments |
| Doctor | dr.johnson | password | Medical Records & Appointments |

### Patient Portal (`patient-portal.php`):
| Role | Username | Password | Patient ID |
|------|----------|----------|------------|
| Patient | patient1 | password | PAT001 |

### Demo Email Addresses:
- Admin: admin@hospital.com
- Doctor: dr.smith@hospital.com, dr.johnson@hospital.com
- Patient: patient1@email.com

> ⚠️ Security Warning: Change all default passwords immediately after installation!



## 📁 File Structure
\`\`\`
hospital-management-system/
├── 📁 assets/
│   ├── 📁 css/
│   │   └── style.css              # Main stylesheet
│   └── 📁 js/
│       └── main.js                # JavaScript functionality
├── 📁 config/
│   └── database.php               # Database configuration
├── 📁 dashboard/                  # Staff dashboard pages
│   ├── index.php                  # Main dashboard
│   ├── patients.php               # Patient management
│   ├── add-patient.php            # Add new patient
│   ├── appointments.php           # Appointment management
│   ├── schedule-appointment.php   # Schedule new appointment
│   ├── billing.php                # Billing management
│   ├── create-bill.php            # Create new bill
│   ├── staff.php                  # Staff management
│   ├── inventory.php              # Inventory management
│   ├── reports.php                # Reports and analytics
│   ├── password-resets.php        # Password management
│   ├── get-patient-details.php    # Patient details API
│   └── update-appointment.php     # Appointment update API
├── 📁 includes/
│   └── auth.php                   # Authentication system
├── 📁 scripts/                    # Database setup scripts
│   ├── 01-create-database.sql     # Database and tables creation
│   ├── 02-insert-sample-data.sql  # Sample data insertion
│   ├── 03-fix-patient-login.sql   # Patient login fix
│   ├── 04-password-reset-table.sql # Password reset table
│   └── 05-create-password-reset-table.sql
├── index.html                     # Main website homepage
├── login.php                      # Staff login page
├── patient-portal.php             # Patient portal login
├── patient-dashboard.php          # Patient dashboard
├── forgot-password.php            # Password reset request
├── reset-password.php             # Password reset form
├── logout.php                     # Logout functionality
├── unauthorized.php               # Access denied page
├── setup-check.php                # Installation verification
├── fix-password-reset.php         # Password reset fix utility
└── README.md                      # This file
\`\`\`