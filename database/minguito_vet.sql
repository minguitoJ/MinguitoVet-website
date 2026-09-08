CREATE DATABASE IF NOT EXISTS minguito_vet;

USE minguito_vet;


-- =========================================
-- APPOINTMENTS
-- =========================================

CREATE TABLE appointments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    owner_name VARCHAR(150) NOT NULL,

    pet_name VARCHAR(100) NOT NULL,

    service VARCHAR(150) NOT NULL,

    appointment_date DATE NOT NULL,

    appointment_time TIME NOT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

);


-- =========================================
-- ADMIN USERS
-- =========================================

CREATE TABLE admins (

    id INT AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(100) UNIQUE NOT NULL,

    password VARCHAR(255) NOT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

);


-- =========================================
-- CONTACT MESSAGES
-- =========================================

CREATE TABLE contact_messages (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL,

    subject VARCHAR(200) NOT NULL,

    message TEXT NOT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

);


-- =========================================
-- SERVICES
-- =========================================

CREATE TABLE services (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,

    description TEXT,

    status ENUM(
        'Active',
        'Inactive'
    ) DEFAULT 'Active',

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP

);


-- =========================================
-- DEFAULT SERVICES
-- =========================================

INSERT INTO services
(name, description)
VALUES

(
    'Consultation & Check-up',
    'Regular examinations to monitor your pet''s overall health.'
),

(
    'Vaccination & Deworming',
    'Preventive healthcare services for pets.'
),

(
    'Surgery & Treatment',
    'Veterinary surgical procedures and treatment.'
),

(
    'Laboratory & Diagnostics',
    'Diagnostic services for accurate assessment.'
),

(
    'Dental Care',
    'Proper dental care for your pet.'
),

(
    'Grooming & Wellness',
    'Grooming and wellness services for pets.'
);

/* =========================================================
   MINGUITO VETERINARY CLINIC
   CUSTOMER LOGIN / REGISTER / BOOKING SETUP
========================================================= */

USE minguito_vet;


/* =========================================================
   1. CREATE CUSTOMERS TABLE
========================================================= */

CREATE TABLE IF NOT EXISTS customers (

    id INT UNSIGNED NOT NULL AUTO_INCREMENT,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL,

    contact_number VARCHAR(30) NOT NULL,

    password VARCHAR(255) NOT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY unique_customer_email (email)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


/* =========================================================
   2. ADD CUSTOMER ID TO APPOINTMENTS
========================================================= */

ALTER TABLE appointments
ADD COLUMN customer_id INT UNSIGNED NULL
AFTER id;


/* =========================================================
   3. ADD FOREIGN KEY
========================================================= */

ALTER TABLE appointments
ADD CONSTRAINT fk_appointments_customer
FOREIGN KEY (customer_id)
REFERENCES customers(id)
ON DELETE CASCADE
ON UPDATE CASCADE;


/* =========================================================
   4. INDEX FOR FASTER CUSTOMER APPOINTMENT LOOKUPS
========================================================= */

CREATE INDEX idx_appointments_customer_id
ON appointments(customer_id);