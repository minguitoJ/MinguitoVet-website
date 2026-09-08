<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../contact.php");

    exit;

}


$name = trim($_POST['name']);
$email = trim($_POST['email']);
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);


/*
    For now, this simply sends the user
    back to the contact page.

    Later, you can connect this to email
    or store messages in MySQL.
*/


header("Location: ../contact.php?success=1");

exit;