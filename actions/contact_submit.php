<?php
/**
 * Handles POST submissions from contact.php's message form.
 */

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('../contact.php', 'error');
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('../contact.php', 'error');
}

$saved = create_contact_message([
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone,
    'message' => $message,
]);

redirect_with_status('../contact.php', $saved ? 'success' : 'error');
