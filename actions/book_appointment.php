<?php

require_once "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../appointment.php");

    exit;

}


$owner_name = trim($_POST['owner_name']);
$pet_name = trim($_POST['pet_name']);
$service = trim($_POST['service']);
$appointment_date = $_POST['appointment_date'];
$appointment_time = $_POST['appointment_time'];


$sql = "
    INSERT INTO appointments
    (
        owner_name,
        pet_name,
        service,
        appointment_date,
        appointment_time,
        status
    )
    VALUES
    (
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

    ':owner_name' => $owner_name,

    ':pet_name' => $pet_name,

    ':service' => $service,

    ':appointment_date' => $appointment_date,

    ':appointment_time' => $appointment_time

]);


header(
    "Location: ../appointment.php?success=1"
);

exit;