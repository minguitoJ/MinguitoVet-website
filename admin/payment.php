<?php
session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin();

$appointmentId = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

if ($appointmentId <= 0) {
    header("Location: appointments.php");
    exit;
}

$message = '';
$messageType = 'error';

/*
|--------------------------------------------------------------------------
| APPOINTMENT SERVICE HELPERS
|--------------------------------------------------------------------------
| Multiple selected services are stored in appointments.service as a
| comma-separated list. Each service becomes its own payment item.
*/
function getAppointmentServiceNames(string $serviceValue): array
{
    return array_values(array_unique(array_filter(
        array_map(
            static fn($name) => trim($name),
            explode(',', $serviceValue)
        ),
        static fn($name) => $name !== ''
    )));
}

function getActiveServicesByNames(
    PDO $pdo,
    array $serviceNames
): array {
    if (empty($serviceNames)) {
        return [];
    }

    $placeholders = [];
    $params = [];

    foreach ($serviceNames as $index => $serviceName) {

        $placeholder = ':service_' . $index;

        $placeholders[] = $placeholder;
        $params[$placeholder] = $serviceName;
    }

    $stmt = $pdo->prepare("
        SELECT id, name, price
        FROM services
        WHERE status = 'Active'
          AND LOWER(TRIM(name)) IN (" .
        implode(', ', $placeholders) .
        ")
        ORDER BY name ASC
    ");

    foreach ($params as $placeholder => $value) {
        $stmt->bindValue(
            $placeholder,
            $value,
            PDO::PARAM_STR
        );
    }

    $stmt->execute();

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];

    foreach ($services as $service) {
        $map[
            strtolower(trim($service['name']))
        ] = $service;
    }

    return $map;
}

/*
|--------------------------------------------------------------------------
| SAVE / PREPARE PAYMENT BILL
|--------------------------------------------------------------------------
| Admin prepares the bill only.
| The payment stays Pending until the customer submits payment and the
| admin verifies it. Medicine stock is NOT deducted here.
*/
/*
|--------------------------------------------------------------------------
| VERIFY / REJECT CUSTOMER PAYMENT
|--------------------------------------------------------------------------
| This happens on the SAME admin/payment.php page.
| - Verify: marks payment Paid and deducts medicine stock once.
| - Reject: marks payment Cancelled and does not change stock.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['action'] ?? '', ['verify_payment', 'reject_payment'], true)) {

    $action = $_POST['action'];

    try {
        $pdo->beginTransaction();

        $paymentLockStmt = $pdo->prepare("
            SELECT
                p.id,
                p.amount,
                p.payment_method,
                p.reference_number,
                p.payment_status,
                a.status AS appointment_status
            FROM payments p
            INNER JOIN appointments a
                ON a.id = p.appointment_id
            WHERE p.appointment_id = :appointment_id
            ORDER BY p.id DESC
            LIMIT 1
            FOR UPDATE
        ");
        $paymentLockStmt->execute([':appointment_id' => $appointmentId]);
        $lockedPayment = $paymentLockStmt->fetch(PDO::FETCH_ASSOC);

        if (!$lockedPayment) {
            throw new Exception('No payment submission was found for this appointment.');
        }

        if ($lockedPayment['payment_status'] !== 'Pending') {
            throw new Exception('This payment has already been processed and is locked.');
        }

        if ($lockedPayment['appointment_status'] !== 'Approved') {
            throw new Exception('Only an approved appointment can have its payment verified.');
        }

        if ($action === 'reject_payment') {
            $rejectStmt = $pdo->prepare("
                UPDATE payments
                SET payment_status = 'Cancelled',
                    paid_at = NULL
                WHERE id = :id
                  AND payment_status = 'Pending'
            ");
            $rejectStmt->execute([':id' => $lockedPayment['id']]);
            $pdo->commit();
            header('Location: payment.php?appointment_id=' . $appointmentId . '&rejected=1');
            exit;
        }

        $itemsLockStmt = $pdo->prepare("
            SELECT item_type, item_name, quantity, unit_price, subtotal
            FROM payment_items
            WHERE payment_id = :payment_id
            ORDER BY id ASC
            FOR UPDATE
        ");
        $itemsLockStmt->execute([':payment_id' => $lockedPayment['id']]);
        $lockedItems = $itemsLockStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($lockedItems)) {
            throw new Exception('This payment has no bill items. Prepare the payment bill first.');
        }

        $itemsTotal = 0.00;
        foreach ($lockedItems as $billItem) {
            $qty = (int)$billItem['quantity'];
            $unit = (float)$billItem['unit_price'];
            $subtotal = (float)$billItem['subtotal'];

            if ($qty <= 0 || $unit < 0 || $subtotal < 0) {
                throw new Exception('Invalid payment item found in this bill.');
            }

            if (abs(round($unit * $qty, 2) - $subtotal) > 0.01) {
                throw new Exception('The payment bill contains an invalid item subtotal for "' . $billItem['item_name'] . '".');
            }

            $itemsTotal += $subtotal;
        }

        $itemsTotal = round($itemsTotal, 2);
        $paymentAmount = round((float)$lockedPayment['amount'], 2);

        if (abs($itemsTotal - $paymentAmount) > 0.01) {
            throw new Exception('Payment total does not match the bill items. Please prepare the bill again.');
        }

        foreach ($lockedItems as $billItem) {
            if ($billItem['item_type'] !== 'Medicine') {
                continue;
            }

            $quantity = (int)$billItem['quantity'];
            $medicineLockStmt = $pdo->prepare("
                SELECT id, name, stock, status
                FROM medicines
                WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
                LIMIT 1
                FOR UPDATE
            ");
            $medicineLockStmt->execute([':name' => $billItem['item_name']]);
            $medicine = $medicineLockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$medicine) {
                throw new Exception('Medicine "' . $billItem['item_name'] . '" no longer exists in the Medicines table.');
            }
            if ($medicine['status'] !== 'Active') {
                throw new Exception('Medicine "' . $medicine['name'] . '" is inactive. Payment cannot be verified.');
            }
            if ((int)$medicine['stock'] < $quantity) {
                throw new Exception('Not enough stock for "' . $medicine['name'] . '". Available stock: ' . (int)$medicine['stock'] . '.');
            }

            $deductStmt = $pdo->prepare("
                UPDATE medicines
                SET stock = stock - :quantity
                WHERE id = :id
                  AND stock >= :quantity
            ");
            $deductStmt->execute([
                ':quantity' => $quantity,
                ':id' => $medicine['id']
            ]);

            if ($deductStmt->rowCount() !== 1) {
                throw new Exception('Medicine stock could not be updated for "' . $medicine['name'] . '".');
            }
        }

        $verifyStmt = $pdo->prepare("
            UPDATE payments
            SET payment_status = 'Paid',
                paid_at = NOW()
            WHERE id = :id
              AND payment_status = 'Pending'
        ");
        $verifyStmt->execute([':id' => $lockedPayment['id']]);

        if ($verifyStmt->rowCount() !== 1) {
            throw new Exception('Payment could not be marked as Paid.');
        }

        $pdo->commit();
        header('Location: payment.php?appointment_id=' . $appointmentId . '&verified=1');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'prepare_payment') {

    $medicineIds = $_POST['medicine_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];

    try {
        $pdo->beginTransaction();

        // Lock appointment.
        $appointmentStmt = $pdo->prepare("
            SELECT
                id,
                owner_name,
                pet_name,
                service,
                appointment_date,
                appointment_time,
                status
            FROM appointments
            WHERE id = :id
            FOR UPDATE
        ");
        $appointmentStmt->execute([':id' => $appointmentId]);
        $appointment = $appointmentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception('Appointment not found.');
        }

        if ($appointment['status'] !== 'Approved') {
            throw new Exception('Only approved appointments can have a payment bill prepared.');
        }

        /*
         * Resolve every selected appointment service from the Active
         * Services table. Prices are never typed by the admin.
         */
        $appointmentServiceNames =
            getAppointmentServiceNames(
                (string)$appointment['service']
            );

        if (empty($appointmentServiceNames)) {
            throw new Exception(
                'This appointment has no veterinary services selected.'
            );
        }

        $activeServicesByName =
            getActiveServicesByNames(
                $pdo,
                $appointmentServiceNames
            );

        $serviceItems = [];
        $serviceTotal = 0.00;

        foreach ($appointmentServiceNames as $serviceName) {

            $serviceKey =
                strtolower(trim($serviceName));

            if (!isset($activeServicesByName[$serviceKey])) {
                throw new Exception(
                    'The selected appointment service "' .
                    $serviceName .
                    '" is not available as an Active service. Please add or activate it in the Services page.'
                );
            }

            $resolvedService =
                $activeServicesByName[$serviceKey];

            $servicePrice =
                (float)$resolvedService['price'];

            if ($servicePrice <= 0) {
                throw new Exception(
                    'The service "' .
                    $resolvedService['name'] .
                    '" has a price of ₱0.00. Please set its correct price in the Services page.'
                );
            }

            $serviceItems[] = [
                'name' => $resolvedService['name'],
                'price' => $servicePrice
            ];

            $serviceTotal += $servicePrice;
        }

        $serviceTotal =
            round($serviceTotal, 2);

        // Validate medicine selections using current database values.
        $items = [];
        $medicineTotal = 0.00;

        if (is_array($medicineIds) && is_array($quantities)) {
            foreach ($medicineIds as $index => $medicineIdRaw) {

                $medicineId = (int)$medicineIdRaw;
                $quantity = (int)($quantities[$index] ?? 0);

                if ($medicineId <= 0 || $quantity <= 0) {
                    continue;
                }

                if ($quantity > 5) {
                    throw new Exception(
                        'Medicine quantity cannot exceed 5 for one item.'
                    );
                }

                $medicineStmt = $pdo->prepare("
                    SELECT id, name, price, stock, status
                    FROM medicines
                    WHERE id = :id
                    FOR UPDATE
                ");
                $medicineStmt->execute([
                    ':id' => $medicineId
                ]);

                $medicine = $medicineStmt->fetch(PDO::FETCH_ASSOC);

                if (!$medicine) {
                    throw new Exception(
                        'One of the selected medicine items was not found.'
                    );
                }

                if ($medicine['status'] !== 'Active') {
                    throw new Exception(
                        'The selected medicine "' .
                        $medicine['name'] .
                        '" is inactive.'
                    );
                }

                if ((int)$medicine['stock'] <= 0) {
                    throw new Exception(
                        'The selected medicine "' .
                        $medicine['name'] .
                        '" is out of stock.'
                    );
                }

                if ($quantity > (int)$medicine['stock']) {
                    throw new Exception(
                        'Not enough stock for "' .
                        $medicine['name'] .
                        '". Available stock: ' .
                        (int)$medicine['stock'] .
                        '.'
                    );
                }

                $unitPrice = (float)$medicine['price'];

                if ($unitPrice <= 0) {
                    throw new Exception(
                        'The medicine "' .
                        $medicine['name'] .
                        '" has a price of ₱0.00.'
                    );
                }

                $subtotal = $unitPrice * $quantity;

                $items[] = [
                    'id'         => (int)$medicine['id'],
                    'name'       => $medicine['name'],
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal
                ];

                $medicineTotal += $subtotal;
            }
        }

        $totalAmount = $serviceTotal + $medicineTotal;

        if ($totalAmount <= 0) {
            throw new Exception('Payment total must be greater than zero.');
        }

        /*
         * Reuse the latest payment when it is still unpaid/cancelled.
         * A Paid payment is locked and cannot be edited.
         */
        $existingPaymentStmt = $pdo->prepare("
            SELECT
                id,
                amount,
                payment_method,
                reference_number,
                payment_status
            FROM payments
            WHERE appointment_id = :appointment_id
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");
        $existingPaymentStmt->execute([
            ':appointment_id' => $appointmentId
        ]);
        $existingPayment = $existingPaymentStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingPayment
            && $existingPayment['payment_status'] === 'Paid') {

            throw new Exception(
                'This appointment has already been paid and its payment is locked.'
            );
        }

        if ($existingPayment) {

            $paymentId = (int)$existingPayment['id'];

            $updatePayment = $pdo->prepare("
                UPDATE payments
                SET
                    amount = :amount,
                    payment_status = 'Pending',
                    paid_at = NULL
                WHERE id = :id
            ");

            $updatePayment->execute([
                ':amount' => $totalAmount,
                ':id'     => $paymentId
            ]);

            // Rebuild bill items.
            $deleteItems = $pdo->prepare("
                DELETE FROM payment_items
                WHERE payment_id = :payment_id
            ");

            $deleteItems->execute([
                ':payment_id' => $paymentId
            ]);

        } else {

            $insertPayment = $pdo->prepare("
                INSERT INTO payments
                (
                    appointment_id,
                    amount,
                    payment_method,
                    reference_number,
                    payment_status,
                    paid_at
                )
                VALUES
                (
                    :appointment_id,
                    :amount,
                    'Cash',
                    NULL,
                    'Pending',
                    NULL
                )
            ");

            $insertPayment->execute([
                ':appointment_id' => $appointmentId,
                ':amount' => $totalAmount
            ]);

            $paymentId = (int)$pdo->lastInsertId();
        }

        // Add every selected appointment service as its own bill item.
        $serviceItemStmt = $pdo->prepare("
            INSERT INTO payment_items
            (
                payment_id,
                item_type,
                item_name,
                quantity,
                unit_price,
                subtotal
            )
            VALUES
            (
                :payment_id,
                'Service',
                :item_name,
                1,
                :unit_price,
                :subtotal
            )
        ");

        foreach ($serviceItems as $serviceItem) {

            $serviceItemStmt->execute([
                ':payment_id' => $paymentId,
                ':item_name'  => $serviceItem['name'],
                ':unit_price' => $serviceItem['price'],
                ':subtotal'   => $serviceItem['price']
            ]);
        }

        // Add medicine items.
        if (!empty($items)) {

            $medicineItemStmt = $pdo->prepare("
                INSERT INTO payment_items
                (
                    payment_id,
                    item_type,
                    item_name,
                    quantity,
                    unit_price,
                    subtotal
                )
                VALUES
                (
                    :payment_id,
                    'Medicine',
                    :item_name,
                    :quantity,
                    :unit_price,
                    :subtotal
                )
            ");

            foreach ($items as $item) {
                $medicineItemStmt->execute([
                    ':payment_id' => $paymentId,
                    ':item_name'  => $item['name'],
                    ':quantity'   => $item['quantity'],
                    ':unit_price' => $item['unit_price'],
                    ':subtotal'   => $item['subtotal']
                ]);
            }
        }

        $pdo->commit();

        header("Location: payment.php?appointment_id=" . $appointmentId . "&saved=1");
        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e->getMessage();
        $messageType = 'error';
    }
}

/*
|--------------------------------------------------------------------------
| LOAD APPOINTMENT
|--------------------------------------------------------------------------
*/
$appointmentStmt = $pdo->prepare("
    SELECT
        a.id,
        a.owner_name,
        a.pet_name,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.status,
        c.full_name AS customer_name,
        c.email AS customer_email
    FROM appointments a
    LEFT JOIN users c
        ON a.customer_id = c.id
       AND c.role = 'customer'
    WHERE a.id = :id
    LIMIT 1
");
$appointmentStmt->execute([
    ':id' => $appointmentId
]);

$appointment = $appointmentStmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: appointments.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET ALL APPOINTMENT SERVICE PRICES
|--------------------------------------------------------------------------
*/
$appointmentServiceNames =
    getAppointmentServiceNames(
        (string)$appointment['service']
    );

$displayServices =
    getActiveServicesByNames(
        $pdo,
        $appointmentServiceNames
    );

$displayServiceTotal = 0.00;
$missingDisplayServices = [];

foreach ($appointmentServiceNames as $serviceName) {

    $key =
        strtolower(trim($serviceName));

    if (!isset($displayServices[$key])) {
        $missingDisplayServices[] = $serviceName;
        continue;
    }

    $displayServiceTotal +=
        (float)$displayServices[$key]['price'];
}

$displayServiceTotal =
    round($displayServiceTotal, 2);

$serviceFound =
    empty($missingDisplayServices);

$servicePrice =
    $displayServiceTotal;

/*
|--------------------------------------------------------------------------
| ACTIVE MEDICINES
|--------------------------------------------------------------------------
*/
$medicineStmt = $pdo->query("
    SELECT id, name, price, stock
    FROM medicines
    WHERE status = 'Active'
      AND stock > 0
    ORDER BY name ASC
");

$medicines = $medicineStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| EXISTING PAYMENT + ITEMS
|--------------------------------------------------------------------------
*/
$paymentStmt = $pdo->prepare("
    SELECT
        id,
        amount,
        payment_method,
        reference_number,
        payment_status,
        paid_at,
        created_at
    FROM payments
    WHERE appointment_id = :appointment_id
    ORDER BY id DESC
    LIMIT 1
");

$paymentStmt->execute([
    ':appointment_id' => $appointmentId
]);

$existingPayment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

$paymentItems = [];

if ($existingPayment) {

    $itemsStmt = $pdo->prepare("
        SELECT
            item_type,
            item_name,
            quantity,
            unit_price,
            subtotal
        FROM payment_items
        WHERE payment_id = :payment_id
        ORDER BY id ASC
    ");

    $itemsStmt->execute([
        ':payment_id' => $existingPayment['id']
    ]);

    $paymentItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| SAVED MESSAGE
|--------------------------------------------------------------------------
*/
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $message = 'Payment bill saved successfully. The customer can now review and submit the payment.';
    $messageType = 'success';
}

if (isset($_GET['verified']) && $_GET['verified'] === '1') {
    $message = 'Payment verified successfully. The payment is now Paid and medicine stock has been deducted.';
    $messageType = 'success';
}

if (isset($_GET['rejected']) && $_GET['rejected'] === '1') {
    $message = 'Payment submission was rejected. Medicine stock was not changed.';
    $messageType = 'success';
}

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';

/*
|--------------------------------------------------------------------------
| CALCULATE DISPLAY TOTAL
|--------------------------------------------------------------------------
*/
$displayTotal = 0.00;

if (!empty($paymentItems)) {
    foreach ($paymentItems as $item) {
        $displayTotal += (float)$item['subtotal'];
    }
} elseif ($serviceFound) {
    $displayTotal = $servicePrice;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Management | Minguito Veterinary Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        :root {
            --green: #173d32;
            --green-dark: #0d3025;
            --green-soft: #e7f2ec;
            --gold: #b57a2f;
            --cream: #f6efe3;
            --white: #fff;
            --text: #24352e;
            --muted: #718078;
            --border: #e5d9c8;
            --danger: #a13d32;
            --danger-soft: #f8e3e0;
            --success: #246a45;
            --success-soft: #e2f1e8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "DM Sans", Arial, sans-serif;
            background: linear-gradient(135deg, #fbf5ea, #f2e5d2);
            color: var(--text);
        }

        .admin-content {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding: 40px 34px 70px;
        }

        .page {
            width: min(1200px, 100%);
            margin: 0 auto;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 26px;
        }

        .eyebrow {
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 1.8px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .page-heading h1 {
            font-family: "Playfair Display", Georgia, serif;
            font-size: 42px;
            line-height: 1.05;
            font-weight: 700;
            color: var(--green);
        }

        .page-heading p {
            color: var(--muted);
            margin-top: 8px;
            font-size: 14px;
        }

        .back-btn {
            text-decoration: none;
            color: var(--green);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 11px 15px;
            font-size: 13px;
            font-weight: 600;
        }

        .back-btn:hover {
            border-color: var(--gold);
        }

        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-error {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid #efc4be;
        }

        .alert-success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid #c8dfd0;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 20px;
            align-items: start;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: 0 10px 28px rgba(52, 43, 29, .06);
            overflow: hidden;
        }

        .card-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }

        .card-header h2 {
            color: var(--green);
            font-size: 17px;
            font-weight: 700;
        }

        .card-header p {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
        }

        .card-body {
            padding: 20px;
        }

        .appointment-info {
            display: grid;
            gap: 14px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee7dc;
        }

        .info-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .info-label {
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .info-value {
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 20px;
            background: var(--green-soft);
            color: var(--success);
            font-size: 10px;
            font-weight: 700;
        }

        .service-line {
            margin-top: 20px;
            background: #fbf7ef;
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 14px;
        }

        .line-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .line-name {
            color: var(--green);
            font-size: 13px;
            font-weight: 700;
        }

        .line-price {
            color: var(--green);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .line-sub {
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
        }

        .service-error {
            margin-top: 10px;
            padding: 10px 12px;
            background: var(--danger-soft);
            border: 1px solid #efc4be;
            color: var(--danger);
            border-radius: 9px;
            font-size: 11px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            color: #4e6058;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 7px;
        }

        .medicine-list {
            display: grid;
            gap: 10px;
        }

        .medicine-row {
            display: grid;
            grid-template-columns: 1fr 105px 42px;
            gap: 9px;
            align-items: end;
        }

        .medicine-row .form-group {
            margin-bottom: 0;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            height: 43px;
            padding: 0 12px;
            border: 1px solid #dcd1c0;
            border-radius: 9px;
            background: #fffaf3;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(181, 122, 47, .09);
        }

        .remove-btn {
            width: 42px;
            height: 43px;
            border: 0;
            border-radius: 9px;
            background: var(--danger-soft);
            color: var(--danger);
            cursor: pointer;
        }

        .remove-btn:hover {
            background: #f2d4d0;
        }

        .add-item-btn {
            margin-top: 12px;
            border: 1px dashed #cdbb9f;
            background: #fffaf3;
            color: var(--green);
            border-radius: 9px;
            padding: 10px 13px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
        }

        .add-item-btn:hover {
            border-color: var(--gold);
        }

        .hint {
            margin-top: 6px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .total-box {
            margin-top: 20px;
            padding: 16px;
            border-radius: 12px;
            background: var(--green);
            color: #fff;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .total-line span:first-child {
            font-size: 12px;
            opacity: .8;
        }

        .total-amount {
            font-size: 25px;
            font-weight: 700;
        }

        .submit-btn {
            width: 100%;
            height: 46px;
            margin-top: 5px;
            border: 0;
            border-radius: 10px;
            background: var(--green);
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
        }

        .submit-btn:hover {
            background: var(--gold);
        }

        .submit-btn:disabled {
            background: #9baba4;
            cursor: not-allowed;
        }

        .workflow-box {
            margin-top: 15px;
            padding: 13px;
            border-radius: 10px;
            background: #fbf7ef;
            border: 1px solid var(--border);
            color: #65756e;
            font-size: 11px;
            line-height: 1.6;
        }

        .workflow-box strong {
            color: var(--green);
        }

        .existing-items {
            margin-top: 15px;
            display: grid;
            gap: 8px;
        }

        .existing-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            font-size: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #eee7dc;
        }

        .existing-item:last-child {
            border-bottom: 0;
        }

        .existing-item span:first-child {
            color: var(--muted);
        }

        .pending-box {
            background: #fff8e8;
            border: 1px solid #ead8ae;
            border-radius: 12px;
            padding: 14px;
            color: #795b22;
            font-size: 12px;
            line-height: 1.5;
        }

        .paid-box {
            background: var(--green-soft);
            border: 1px solid #c8dfd0;
            border-radius: 12px;
            padding: 16px;
        }

        .paid-box strong {
            color: var(--success);
            font-size: 14px;
        }

        .paid-box p {
            margin-top: 5px;
            color: #587067;
            font-size: 12px;
            line-height: 1.5;
        }

        .verification-details {
            margin-top: 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fffaf3;
            overflow: hidden;
        }

        .verification-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 11px 13px;
            border-bottom: 1px solid #eee7dc;
            font-size: 12px;
        }

        .verification-row:last-child { border-bottom: 0; }
        .verification-row span { color: var(--muted); }
        .verification-row strong { color: var(--text); text-align: right; }

        .verification-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }

        .verification-actions form { margin: 0; }

        .verify-btn,
        .reject-btn {
            width: 100%;
            min-height: 46px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 14px;
        }

        .verify-btn { background: var(--success); }
        .verify-btn:hover { background: var(--green); }
        .reject-btn { background: var(--danger); }
        .reject-btn:hover { background: #8b3027; }

        @media (max-width: 950px) {
            .admin-content {
                margin-left: 0;
                width: 100%;
                padding: 30px 20px 50px;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-heading h1 {
                font-size: 34px;
            }

            .medicine-row {
                grid-template-columns: 1fr 85px 40px;
            }
        }
    </style>
</head>

<body class="admin-with-sidebar">

<?php include 'sidebar.php'; ?>

<main class="admin-content">
    <div class="page">

        <div class="page-heading">
            <div>
                <div class="eyebrow">Payment Management</div>
                <h1>Prepare Payment</h1>
                <p>Create the patient's bill and add medicine if needed.</p>
            </div>

            <a href="appointments.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Appointments
            </a>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid">

            <!-- APPOINTMENT DETAILS -->
            <section class="card">

                <div class="card-header">
                    <h2>Appointment Details</h2>
                    <p>Information connected to this payment.</p>
                </div>

                <div class="card-body">

                    <div class="appointment-info">

                        <div class="info-row">
                            <div class="info-label">Appointment</div>
                            <div class="info-value">
                                #<?= (int)$appointment['id'] ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Owner</div>
                            <div class="info-value">
                                <?= htmlspecialchars($appointment['owner_name']) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Pet</div>
                            <div class="info-value">
                                <?= htmlspecialchars($appointment['pet_name']) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Date</div>
                            <div class="info-value">
                                <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Time</div>
                            <div class="info-value">
                                <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge">
                                    <?= htmlspecialchars($appointment['status']) ?>
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="service-line">

                        <div class="line-top">

                            <div class="line-name">
                                Selected Veterinary Services
                            </div>

                            <div class="line-price">
                                <?php if ($serviceFound): ?>
                                    ₱<?= number_format(
                                        $displayServiceTotal,
                                        2
                                    ) ?>
                                <?php else: ?>
                                    Price unavailable
                                <?php endif; ?>
                            </div>

                        </div>

                        <div class="existing-items">

                            <?php foreach (
                                $appointmentServiceNames
                                as $serviceName
                            ): ?>

                                <?php
                                $serviceKey =
                                    strtolower(trim($serviceName));

                                $resolvedDisplayService =
                                    $displayServices[
                                        $serviceKey
                                    ] ?? null;
                                ?>

                                <div class="existing-item">

                                    <span>
                                        <?= htmlspecialchars(
                                            $serviceName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <strong>
                                        <?php if (
                                            $resolvedDisplayService
                                        ): ?>
                                            ₱<?= number_format(
                                                (float)
                                                $resolvedDisplayService['price'],
                                                2
                                            ) ?>
                                        <?php else: ?>
                                            Price unavailable
                                        <?php endif; ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="line-sub">
                            <?= count($appointmentServiceNames) ?>
                            selected service<?= count(
                                $appointmentServiceNames
                            ) === 1 ? '' : 's' ?>
                        </div>

                        <?php if (!$serviceFound): ?>

                            <div class="service-error">

                                <strong>
                                    Service price not found.
                                </strong><br>

                                The appointment contains service(s)
                                that are not currently Active:

                                <strong>
                                    <?= htmlspecialchars(
                                        implode(
                                            ', ',
                                            $missingDisplayServices
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>.

                                Add or activate the exact service names
                                in <strong>Admin → Services</strong>.

                            </div>

                        <?php elseif ($displayServiceTotal <= 0): ?>

                            <div class="service-error">
                                <strong>
                                    Service price is ₱0.00.
                                </strong><br>

                                Please set the correct prices in
                                <strong>Admin → Services</strong>.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </section>

            <!-- PAYMENT BILL -->
            <section class="card">

                <?php if ($existingPayment && $existingPayment['payment_status'] === 'Paid'): ?>

                    <div class="card-header">
                        <h2>Payment Locked</h2>
                        <p>This appointment has already been paid.</p>
                    </div>

                    <div class="card-body">

                        <div class="paid-box">
                            <strong>
                                <i class="fa-solid fa-circle-check"></i>
                                Payment Paid
                            </strong>

                            <p>
                                This payment can no longer be edited.
                                <br>
                                Amount:
                                ₱<?= number_format((float)$existingPayment['amount'], 2) ?>
                                <br>
                                Method:
                                <?= htmlspecialchars($existingPayment['payment_method']) ?>
                            </p>
                        </div>

                        <?php if (!empty($paymentItems)): ?>

                            <div class="existing-items">

                                <?php foreach ($paymentItems as $item): ?>

                                    <div class="existing-item">

                                        <span>
                                            <?= htmlspecialchars($item['item_name']) ?>
                                            × <?= (int)$item['quantity'] ?>
                                        </span>

                                        <strong>
                                            ₱<?= number_format((float)$item['subtotal'], 2) ?>
                                        </strong>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php elseif ($existingPayment && $existingPayment['payment_status'] === 'Pending'): ?>

                    <div class="card-header">
                        <h2>Payment Bill Prepared</h2>
                        <p>The customer can now review and submit payment.</p>
                    </div>

                    <div class="card-body">

                        <?php
                        $customerSubmitted =
                            !empty(trim($existingPayment['reference_number'] ?? '')) &&
                            (
                                ($existingPayment['payment_method'] ?? '') === 'GCash' ||
                                (
                                    ($existingPayment['payment_method'] ?? '') === 'Cash' &&
                                    ($existingPayment['reference_number'] ?? '') === 'CASH_SUBMITTED'
                                )
                            );
                        ?>

                        <?php if ($customerSubmitted): ?>
                            <div class="pending-box">
                                <strong>
                                    <i class="fa-solid fa-clock"></i>
                                    Payment Submitted — Verification Required
                                </strong>
                                <br><br>
                                The customer has submitted
                                <strong><?= htmlspecialchars($existingPayment['payment_method']) ?></strong>
                                payment. Review the bill and verify or reject it below.
                            </div>

                            <div class="verification-details">
                                <div class="verification-row">
                                    <span>Payment Method</span>
                                    <strong><?= htmlspecialchars($existingPayment['payment_method']) ?></strong>
                                </div>
                                <div class="verification-row">
                                    <span>Reference Number</span>
                                    <strong><?= !empty($existingPayment['reference_number']) ? htmlspecialchars($existingPayment['reference_number']) : 'Not applicable for Cash' ?></strong>
                                </div>
                                <div class="verification-row">
                                    <span>Amount</span>
                                    <strong>₱<?= number_format((float)$existingPayment['amount'], 2) ?></strong>
                                </div>
                            </div>

                            <?php if (!empty($paymentItems)): ?>
                                <div class="existing-items">
                                    <?php foreach ($paymentItems as $item): ?>
                                        <div class="existing-item">
                                            <span><?= htmlspecialchars($item['item_name']) ?> × <?= (int)$item['quantity'] ?></span>
                                            <strong>₱<?= number_format((float)$item['subtotal'], 2) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="verification-actions">
                                <form method="POST" onsubmit="return confirm('Verify this payment and mark it as Paid? Medicine stock will be deducted now.');">
                                    <input type="hidden" name="action" value="verify_payment">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$appointmentId ?>">
                                    <button type="submit" class="verify-btn">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Verify &amp; Mark as Paid
                                    </button>
                                </form>

                                <form method="POST" onsubmit="return confirm('Reject this payment submission?');">
                                    <input type="hidden" name="action" value="reject_payment">
                                    <input type="hidden" name="appointment_id" value="<?= (int)$appointmentId ?>">
                                    <button type="submit" class="reject-btn">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        Reject Payment
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="pending-box">
                                <strong>
                                    <i class="fa-solid fa-file-invoice"></i>
                                    Payment Bill Prepared
                                </strong>
                                <br><br>
                                The bill is ready. The customer must submit Cash or GCash payment.
                                After submission, return here to verify it.
                            </div>
                        <?php endif; ?>


                        <div class="total-box">

                            <div class="total-line">
                                <span>Total Amount</span>

                                <span class="total-amount">
                                    ₱<?= number_format((float)$displayTotal, 2) ?>
                                </span>
                            </div>

                        </div>

                        <div class="workflow-box">
                            <strong>Payment workflow:</strong>
                            The customer reviews this bill and submits payment.
                            The payment remains Pending until the admin verifies it.
                            Medicine stock is deducted only after verification.
                        </div>

                    </div>

                <?php elseif ($appointment['status'] !== 'Approved'): ?>

                    <div class="card-header">
                        <h2>Payment Unavailable</h2>
                        <p>The appointment must be approved first.</p>
                    </div>

                    <div class="card-body">

                        <div class="pending-box">
                            <strong>
                                <i class="fa-solid fa-clock"></i>
                                <?= htmlspecialchars($appointment['status']) ?>
                            </strong>

                            <br>

                            Payment billing is available only after the
                            appointment has been approved.
                        </div>

                    </div>

                <?php else: ?>

                    <div class="card-header">
                        <h2>Payment Details</h2>
                        <p>Add medicine to the patient's bill if needed.</p>
                    </div>

                    <div class="card-body">

                        <form method="POST" id="paymentForm">

                            <input
                                type="hidden"
                                name="action"
                                value="prepare_payment"
                            >

                            <input
                                type="hidden"
                                name="appointment_id"
                                value="<?= (int)$appointmentId ?>"
                            >

                            <div class="form-group">

                                <label>Additional Medicine</label>

                                <div
                                    class="medicine-list"
                                    id="medicineList"
                                ></div>

                                <button
                                    type="button"
                                    class="add-item-btn"
                                    onclick="addMedicineRow()"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                    Add Medicine
                                </button>

                                <div class="hint">
                                    Medicine price comes directly from the Medicines table.
                                    Stock is checked when the bill is saved.
                                    Maximum quantity is 6 per medicine item.
                                </div>

                            </div>

                            <div class="total-box">

                                <div class="total-line">

                                    <span>Total Amount</span>

                                    <span
                                        class="total-amount"
                                        id="totalAmount"
                                    >
                                        ₱<?= number_format($servicePrice, 2) ?>
                                    </span>

                                </div>

                            </div>

                            <button
                                type="submit"
                                class="submit-btn"
                                id="saveButton"
                                <?= (!$serviceFound || $servicePrice <= 0) ? 'disabled' : '' ?>
                            >
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Save Payment Bill
                            </button>

                        </form>

                        <div class="workflow-box">
                            <strong>Payment workflow:</strong>
                            Saving this bill does not mark the payment as Paid.
                            The customer can review the bill and submit payment afterward.
                            Medicine stock is deducted only after the payment is verified.
                        </div>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </div>
</main>

<script>
    const servicePrice = <?= json_encode($servicePrice) ?>;

    const medicines = <?= json_encode(
        array_map(
            static function ($medicine) {
                return [
                    'id' => (int)$medicine['id'],
                    'name' => $medicine['name'],
                    'price' => (float)$medicine['price'],
                    'stock' => (int)$medicine['stock']
                ];
            },
            $medicines
        ),
        JSON_UNESCAPED_UNICODE
    ) ?>;

    const medicineList = document.getElementById('medicineList');
    const totalAmount = document.getElementById('totalAmount');
    const paymentForm = document.getElementById('paymentForm');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function addMedicineRow() {

        if (!medicineList || medicines.length === 0) {
            alert('There are no active medicines with available stock.');
            return;
        }

        const row = document.createElement('div');

        row.className = 'medicine-row';

        let options = '<option value="">Select medicine</option>';

        medicines.forEach(function (medicine) {

            options += `
                <option
                    value="${medicine.id}"
                    data-price="${medicine.price}"
                    data-stock="${medicine.stock}"
                >
                    ${escapeHtml(medicine.name)}
                    — ₱${medicine.price.toFixed(2)}
                    (Stock: ${medicine.stock})
                </option>
            `;
        });

        row.innerHTML = `
            <div class="form-group">

                <label>Medicine</label>

                <select
                    name="medicine_id[]"
                    onchange="updateTotal()"
                    required
                >
                    ${options}
                </select>

            </div>

            <div class="form-group">

                <label>Qty</label>

                <input
                    type="number"
                    name="quantity[]"
                    min="1"
                    max="6"
                    value="1"
                    onchange="updateTotal()"
                    oninput="updateTotal()"
                    required
                >

            </div>

            <button
                type="button"
                class="remove-btn"
                title="Remove medicine"
                onclick="this.closest('.medicine-row').remove(); updateTotal();"
            >
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        medicineList.appendChild(row);

        updateTotal();
    }

    function updateTotal() {

        if (!totalAmount) {
            return;
        }

        let total = Number(servicePrice) || 0;

        document.querySelectorAll('.medicine-row').forEach(function (row) {

            const select = row.querySelector('select');
            const quantityInput = row.querySelector('input[type="number"]');

            if (!select || !quantityInput) {
                return;
            }

            const selected =
                select.options[select.selectedIndex];

            if (!selected || !selected.dataset.price) {
                return;
            }

            let quantity =
                parseInt(quantityInput.value, 10) || 1;

            const stock =
                parseInt(selected.dataset.stock, 10) || 0;

            if (quantity < 1) {
                quantity = 1;
                quantityInput.value = 1;
            }

            if (quantity > 6) {
                quantity = 6;
                quantityInput.value = 6;
            }

            if (stock > 0 && quantity > stock) {
                quantity = stock;
                quantityInput.value = stock;
            }

            total +=
                Number(selected.dataset.price) * quantity;
        });

        totalAmount.textContent =
            '₱' +
            total.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }

    if (paymentForm) {

        paymentForm.addEventListener('submit', function (event) {

            updateTotal();

            const rows =
                document.querySelectorAll('.medicine-row');

            const selectedIds = new Set();

            for (const row of rows) {

                const select =
                    row.querySelector('select');

                const quantityInput =
                    row.querySelector('input[type="number"]');

                if (!select || !quantityInput) {
                    continue;
                }

                if (!select.value) {
                    event.preventDefault();

                    alert('Please select a medicine or remove the empty medicine row.');

                    select.focus();

                    return;
                }

                const medicineId = select.value;

                if (selectedIds.has(medicineId)) {
                    event.preventDefault();

                    alert('Please do not add the same medicine more than once.');

                    select.focus();

                    return;
                }

                selectedIds.add(medicineId);

                let quantity =
                    parseInt(quantityInput.value, 10) || 0;

                if (quantity < 1 || quantity > 6) {
                    event.preventDefault();

                    alert('Medicine quantity must be between 1 and 6.');

                    quantityInput.focus();

                    return;
                }
            }
        });
    }

    updateTotal();
</script>

</body>
</html>
