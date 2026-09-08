<?php

session_start();

/* =========================================================
   CUSTOMER LOGIN REQUIRED
========================================================= */

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php?redirect=appointment.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   ONLY ACCEPT POST REQUESTS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../appointment.php");
    exit;
}


/* =========================================================
   GET LOGGED-IN CUSTOMER
========================================================= */

$customerId = (int) $_SESSION['customer_id'];


/* =========================================================
   FORM DATA
========================================================= */

$ownerName = trim($_POST['owner_name'] ?? '');
$petName = trim($_POST['pet_name'] ?? '');
$petType = trim($_POST['pet_type'] ?? '');
$service = trim($_POST['service'] ?? '');
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '');


/* =========================================================
   VALID SERVICES

   These must match the services shown on services.php
   and appointment.php.
========================================================= */

$allowedServices = [
    'General Checkup',
    'Vaccination',
    'Preventive Care',
    'Dental Care',
    'Surgery & Treatment',
    'Laboratory & Diagnostics',
    'Grooming & Wellness'
];


/* =========================================================
   REQUIRED FIELD VALIDATION
========================================================= */

if (
    $ownerName === '' ||
    $petName === '' ||
    $petType === '' ||
    $service === '' ||
    $appointmentDate === '' ||
    $appointmentTime === ''
) {
    header("Location: ../appointment.php?error=missing");
    exit;
}


/* =========================================================
   PET TYPE VALIDATION
========================================================= */

if (!in_array($petType, ['Dog', 'Cat'], true)) {
    header("Location: ../appointment.php?error=pet_type");
    exit;
}


/* =========================================================
   SERVICE VALIDATION
========================================================= */

if (!in_array($service, $allowedServices, true)) {
    header("Location: ../appointment.php?error=service");
    exit;
}


/* =========================================================
   DATE VALIDATION
========================================================= */

$dateObject = DateTime::createFromFormat(
    'Y-m-d',
    $appointmentDate
);

$dateErrors = DateTime::getLastErrors();

$dateHasErrors = is_array($dateErrors)
    && (
        $dateErrors['warning_count'] > 0 ||
        $dateErrors['error_count'] > 0
    );

$today = new DateTime('today');

if (
    !$dateObject ||
    $dateHasErrors ||
    $dateObject->format('Y-m-d') !== $appointmentDate ||
    $dateObject < $today
) {
    header("Location: ../appointment.php?error=date");
    exit;
}


/* =========================================================
   TIME FORMAT VALIDATION
========================================================= */

if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $appointmentTime)) {
    header("Location: ../appointment.php?error=time");
    exit;
}


/* =========================================================
   CLINIC HOURS VALIDATION

   Monday - Saturday:
   8:00 AM - 6:00 PM

   Sunday:
   9:00 AM - 1:00 PM
========================================================= */

$dayOfWeek = (int) $dateObject->format('w');

if ($dayOfWeek === 0) {

    // Sunday
    $openingTime = '09:00';
    $closingTime = '13:00';

} else {

    // Monday - Saturday
    $openingTime = '08:00';
    $closingTime = '18:00';
}

if (
    $appointmentTime < $openingTime ||
    $appointmentTime > $closingTime
) {
    header("Location: ../appointment.php?error=time");
    exit;
}


/* =========================================================
   SAVE APPOINTMENT
========================================================= */

try {

    /* -----------------------------------------------------
       MAKE SURE CUSTOMER STILL EXISTS
    ----------------------------------------------------- */

    $customerStmt = $pdo->prepare(
        "SELECT id
         FROM customers
         WHERE id = :customer_id
         LIMIT 1"
    );

    $customerStmt->execute([
        ':customer_id' => $customerId
    ]);

    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {

        $_SESSION = [];
        session_destroy();

        header("Location: ../login.php");
        exit;
    }


    /* -----------------------------------------------------
       STORE PET TYPE WITH PET NAME

       Your appointments table has no separate pet_type
       column.

       Example:
       Dog - Buddy
       Cat - Mimi
    ----------------------------------------------------- */

    $petDisplayName = $petType . ' - ' . $petName;


    /* -----------------------------------------------------
       INSERT APPOINTMENT

       Actual columns:

       customer_id
       owner_name
       pet_name
       service
       appointment_date
       appointment_time
       status
       created_at
    ----------------------------------------------------- */

    $sql = "
        INSERT INTO appointments
        (
            customer_id,
            owner_name,
            pet_name,
            service,
            appointment_date,
            appointment_time,
            status
        )
        VALUES
        (
            :customer_id,
            :owner_name,
            :pet_name,
            :service,
            :appointment_date,
            :appointment_time,
            'Pending'
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':customer_id' => $customerId,
        ':owner_name' => $ownerName,
        ':pet_name' => $petDisplayName,
        ':service' => $service,
        ':appointment_date' => $appointmentDate,
        ':appointment_time' => $appointmentTime
    ]);


    /* -----------------------------------------------------
       SUCCESS
    ----------------------------------------------------- */

    header("Location: ../appointment.php?success=1");
    exit;


} catch (PDOException $e) {

    /* -----------------------------------------------------
       DATABASE ERROR

       Keep the actual error in the server log instead of
       displaying database information to the customer.
    ----------------------------------------------------- */

    error_log(
        "Minguito appointment booking error: "
        . $e->getMessage()
    );

    header("Location: ../appointment.php?error=database");
    exit;
}
?>