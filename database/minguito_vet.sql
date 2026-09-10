CREATE DATABASE IF NOT EXISTS minguito_vet;
USE minguito_vet;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payment_items;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS customers;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- UNIFIED USERS
-- =========================================
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NULL,
    username VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    contact_number VARCHAR(30) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_users_username (username),
    UNIQUE KEY unique_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- APPOINTMENTS
-- =========================================
CREATE TABLE appointments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NULL,
    owner_name VARCHAR(150) NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    service VARCHAR(150) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Pending','Approved','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_appointments_customer_id (customer_id),
    CONSTRAINT fk_appointments_user
        FOREIGN KEY (customer_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- CONTACT MESSAGES
-- =========================================
CREATE TABLE contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- SERVICES
-- =========================================
CREATE TABLE services (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_service_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- MEDICINES
-- =========================================
CREATE TABLE medicines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    expiry_date DATE NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_medicine_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- PAYMENTS
-- =========================================
CREATE TABLE payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(30) NULL,
    reference_number VARCHAR(100) NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
    paid_at DATETIME NULL,
    refunded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_appointment_id (appointment_id),
    CONSTRAINT fk_payments_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- PAYMENT ITEMS
-- =========================================
CREATE TABLE payment_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(30) NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_payment_items_payment_id (payment_id),
    CONSTRAINT fk_payment_items_payment
        FOREIGN KEY (payment_id) REFERENCES payments(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- DEFAULT SERVICES
-- =========================================
INSERT INTO services (name, description, price, status) VALUES
('Consultation & Check-up',
 'Regular examinations to monitor your pet''s overall health.', 0.00, 'Active'),
('Vaccination & Deworming',
 'Preventive healthcare services for pets.', 0.00, 'Active'),
('Surgery & Treatment',
 'Veterinary surgical procedures and treatment.', 0.00, 'Active'),
('Laboratory & Diagnostics',
 'Diagnostic services for accurate assessment.', 0.00, 'Active'),
('Dental Care',
 'Proper dental care for your pet.', 0.00, 'Active'),
('Grooming & Wellness',
 'Grooming and wellness services for pets.', 0.00, 'Active');

-- =========================================
-- LEGACY TABLES KEPT FOR COMPATIBILITY
-- These are intentionally retained, not used by the
-- current authentication/appointment code.
-- =========================================
CREATE TABLE admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE customers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_customer_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
