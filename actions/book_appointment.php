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

$selectedServices = $_POST['services'] ?? [];

/*
 * Backward compatibility for an older single-service form.
 */
if (
    empty($selectedServices)
    && isset($_POST['service'])
    && trim((string)$_POST['service']) !== ''
) {
    $selectedServices = [
        trim((string)$_POST['service'])
    ];
}

if (!is_array($selectedServices)) {
    $selectedServices = [];
}

$selectedServices = array_values(array_unique(array_filter(
    array_map(
        static fn($service) => trim((string)$service),
        $selectedServices
    ),
    static fn($service) => $service !== ''
)));

$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '');


/* =========================================================
   REQUIRED FIELD VALIDATION
========================================================= */

if (
    $ownerName === '' ||
    $petName === '' ||
    $petType === '' ||
    empty($selectedServices) ||
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
=========================================================
   Every selected service must exist in the Services table
   and must currently be Active.
========================================================= */

$servicePlaceholders = [];
$serviceParams = [];

foreach ($selectedServices as $index => $selectedService) {

    $placeholder = ':service_' . $index;

    $servicePlaceholders[] = $placeholder;
    $serviceParams[$placeholder] = $selectedService;
}

$serviceValidationStmt = $pdo->prepare("
    SELECT name
    FROM services
    WHERE status = 'Active'
      AND LOWER(TRIM(name)) IN (" .
    implode(', ', $servicePlaceholders) .
    ")
");

foreach ($serviceParams as $placeholder => $value) {
    $serviceValidationStmt->bindValue(
        $placeholder,
        $value,
        PDO::PARAM_STR
    );
}

$serviceValidationStmt->execute();

$activeServiceNames =
    $serviceValidationStmt->fetchAll(PDO::FETCH_COLUMN);

$activeServiceMap = [];

foreach ($activeServiceNames as $activeName) {
    $activeServiceMap[
        strtolower(trim($activeName))
    ] = $activeName;
}

$normalizedSelectedServices = [];

foreach ($selectedServices as $selectedService) {

    $key = strtolower(trim($selectedService));

    if (!isset($activeServiceMap[$key])) {
        header("Location: ../appointment.php?error=service");
        exit;
    }

    $normalizedSelectedServices[] =
        $activeServiceMap[$key];
}

$normalizedSelectedServices =
    array_values(array_unique($normalizedSelectedServices));

/*
 * Keep the existing appointments.service column.
 * Multiple services are stored as a readable comma-separated list.
 */
$service =
    implode(', ', $normalizedSelectedServices);


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

       The database now has a UNIQUE constraint on:

       appointment_date
       appointment_time

       This enforces first-come-first-serve booking.
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
       DUPLICATE APPOINTMENT SLOT

       MySQL error 1062 means a UNIQUE constraint was
       violated.

       This happens when another customer has already
       booked the exact same date and time.
    ----------------------------------------------------- */

    if (
        isset($e->errorInfo[1]) &&
        (int) $e->errorInfo[1] === 1062
    ) {

        error_log(
            "Minguito duplicate appointment slot: "
            . $appointmentDate
            . " "
            . $appointmentTime
        );

        header("Location: ../appointment.php?error=slot_taken");
        exit;
    }


    /* -----------------------------------------------------
       OTHER DATABASE ERROR

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