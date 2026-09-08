<?php

session_start();

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../contact.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($name === "" || $email === "" || $subject === "" || $message === "") {
    header("Location: ../contact.php?error=missing");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../contact.php?error=email");
    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | SAVE CUSTOMER MESSAGE
    |--------------------------------------------------------------------------
    | The admin messages page reads from the same contact_messages table.
    */

    $stmt = $pdo->prepare("
        INSERT INTO contact_messages
        (
            name,
            email,
            subject,
            message,
            created_at
        )
        VALUES
        (
            :name,
            :email,
            :subject,
            :message,
            NOW()
        )
    ");

    $stmt->execute([
        ":name"    => $name,
        ":email"   => $email,
        ":subject" => $subject,
        ":message" => $message
    ]);

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    header("Location: ../contact.php?success=1");
    exit;

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    | Do not expose the actual database error to customers.
    */

    header("Location: ../contact.php?error=database");
    exit;
}
?>
