<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?redirect=payment.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$customerId = (int)$_SESSION['customer_id'];
$appointmentId = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

if ($appointmentId <= 0) {
    header('Location: appointments.php');
    exit;
}

$message = '';
$messageType = 'error';
$appointment = null;
$service = null;
$payment = null;
$paymentItems = [];
$billTotal = 0.00;

/*
|--------------------------------------------------------------------------
| APPOINTMENT SERVICE HELPERS
|--------------------------------------------------------------------------
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
| LOAD APPOINTMENT
|--------------------------------------------------------------------------
*/
try {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.owner_name,
            a.pet_name,
            a.service,
            a.appointment_date,
            a.appointment_time,
            a.status
        FROM appointments a
        WHERE a.id = :appointment_id
          AND a.customer_id = :customer_id
        LIMIT 1
    ");

    $stmt->execute([
        ':appointment_id' => $appointmentId,
        ':customer_id' => $customerId
    ]);

    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        header('Location: appointments.php');
        exit;
    }

    if ($appointment['status'] !== 'Approved') {
        header('Location: appointments.php');
        exit;
    }

    /*
     * Load every selected service from the Active Services table.
     */
    $appointmentServiceNames =
        getAppointmentServiceNames(
            (string)$appointment['service']
        );

    $activeAppointmentServices =
        getActiveServicesByNames(
            $pdo,
            $appointmentServiceNames
        );

    $service = null;

    if (!empty($appointmentServiceNames)) {

        $firstServiceKey =
            strtolower(
                trim($appointmentServiceNames[0])
            );

        $service =
            $activeAppointmentServices[
                $firstServiceKey
            ] ?? null;
    }

    /*
     * Load the latest payment/bill prepared by the admin.
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

    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    /*
     * Load all bill items. This is important because the admin may have
     * added medicine to the bill.
     */
    if ($payment) {
        $itemsStmt = $pdo->prepare("
            SELECT
                id,
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
            ':payment_id' => $payment['id']
        ]);

        $paymentItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log('Minguito customer payment load error: ' . $e->getMessage());
    $message = 'Unable to load the payment information right now.';
}

/*
|--------------------------------------------------------------------------
| SUBMIT CUSTOMER PAYMENT
|--------------------------------------------------------------------------
|
| The customer does NOT create a new bill.
| The customer submits the bill already prepared by the admin.
|
| The payment remains Pending until the admin verifies it.
| Medicine stock is NOT deducted here.
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'submit_payment') {

    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $referenceNumber = trim($_POST['reference_number'] ?? '');

    if (!in_array($paymentMethod, ['Cash', 'GCash'], true)) {

        $message = 'Please select a valid payment method.';

    } elseif ($paymentMethod === 'GCash' && $referenceNumber === '') {

        $message = 'GCash reference number is required.';

    } else {

        try {
            $pdo->beginTransaction();

            /*
             * Lock appointment and verify ownership.
             */
            $lockAppointment = $pdo->prepare("
                SELECT
                    id,
                    service,
                    status
                FROM appointments
                WHERE id = :appointment_id
                  AND customer_id = :customer_id
                FOR UPDATE
            ");

            $lockAppointment->execute([
                ':appointment_id' => $appointmentId,
                ':customer_id' => $customerId
            ]);

            $lockedAppointment = $lockAppointment->fetch(PDO::FETCH_ASSOC);

            if (!$lockedAppointment) {
                throw new Exception('Appointment not found.');
            }

            if ($lockedAppointment['status'] !== 'Approved') {
                throw new Exception(
                    'Only approved appointments can receive payment.'
                );
            }

            /*
             * Lock latest payment.
             */
            $latestPaymentStmt = $pdo->prepare("
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

            $latestPaymentStmt->execute([
                ':appointment_id' => $appointmentId
            ]);

            $latestPayment = $latestPaymentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$latestPayment) {
                throw new Exception(
                    'The clinic has not prepared a payment bill for this appointment yet.'
                );
            }

            if ($latestPayment['payment_status'] === 'Paid') {
                throw new Exception(
                    'This appointment has already been paid.'
                );
            }

            /*
             * Load the bill items belonging to the prepared payment.
             */
            $lockedItemsStmt = $pdo->prepare("
                SELECT
                    id,
                    item_type,
                    item_name,
                    quantity,
                    unit_price,
                    subtotal
                FROM payment_items
                WHERE payment_id = :payment_id
                ORDER BY id ASC
                FOR UPDATE
            ");

            $lockedItemsStmt->execute([
                ':payment_id' => $latestPayment['id']
            ]);

            $lockedItems = $lockedItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            /*
             * Safety fallback:
             * If the admin's bill has no items, create every selected
             * service from the current Active Services prices.
             */
            if (empty($lockedItems)) {

                $lockedServiceNames =
                    getAppointmentServiceNames(
                        (string)$lockedAppointment['service']
                    );

                $lockedServices =
                    getActiveServicesByNames(
                        $pdo,
                        $lockedServiceNames
                    );

                if (empty($lockedServiceNames)) {
                    throw new Exception(
                        'The appointment has no veterinary services selected.'
                    );
                }

                $insertServiceItem = $pdo->prepare("
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

                foreach ($lockedServiceNames as $serviceName) {

                    $serviceKey =
                        strtolower(trim($serviceName));

                    if (!isset($lockedServices[$serviceKey])) {
                        throw new Exception(
                            'The appointment service "' .
                            $serviceName .
                            '" is not currently Active in the Services page.'
                        );
                    }

                    $lockedService =
                        $lockedServices[$serviceKey];

                    $servicePrice =
                        (float)$lockedService['price'];

                    if ($servicePrice <= 0) {
                        throw new Exception(
                            'The appointment service "' .
                            $lockedService['name'] .
                            '" does not have a valid price.'
                        );
                    }

                    $insertServiceItem->execute([
                        ':payment_id' =>
                            $latestPayment['id'],
                        ':item_name' =>
                            $lockedService['name'],
                        ':unit_price' =>
                            $servicePrice,
                        ':subtotal' =>
                            $servicePrice
                    ]);
                }

                $lockedItemsStmt->execute([
                    ':payment_id' =>
                        $latestPayment['id']
                ]);

                $lockedItems =
                    $lockedItemsStmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );
            }

            /*
             * Calculate the bill from payment_items.
             * This preserves admin-added medicine.
             */
            $amount = 0.00;

            foreach ($lockedItems as $item) {

                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $subtotal = (float)$item['subtotal'];

                if ($quantity <= 0 || $unitPrice < 0 || $subtotal < 0) {
                    throw new Exception(
                        'The payment bill contains an invalid item.'
                    );
                }

                /*
                 * Keep the stored subtotal consistent with quantity × price.
                 */
                $expectedSubtotal = round(
                    $quantity * $unitPrice,
                    2
                );

                if (abs($expectedSubtotal - $subtotal) > 0.01) {
                    throw new Exception(
                        'The payment bill contains an invalid item total. Please ask the administrator to prepare the bill again.'
                    );
                }

                $amount += $subtotal;
            }

            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new Exception(
                    'The payment total must be greater than zero.'
                );
            }

            /*
             * Submit the customer's selected method.
             * Payment stays Pending.
             */
            $updatePayment = $pdo->prepare("
                UPDATE payments
                SET
                    amount = :amount,
                    payment_method = :payment_method,
                    reference_number = :reference_number,
                    payment_status = 'Pending',
                    paid_at = NULL
                WHERE id = :id
            ");

            $updatePayment->execute([
                ':amount' => $amount,
                ':payment_method' => $paymentMethod,
                ':reference_number' =>
                    $paymentMethod === 'GCash'
                        ? $referenceNumber
                        : 'CASH_SUBMITTED',
                ':id' => $latestPayment['id']
            ]);

            $pdo->commit();

            header('Location: appointments.php?payment=submitted');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Minguito customer payment submit error: ' .
                $e->getMessage()
            );

            $message = $e->getMessage();
        }
    }

    /*
     * Reload the payment and its items after an error.
     */
    try {

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

        $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

        $paymentItems = [];

        if ($payment) {

            $itemsStmt = $pdo->prepare("
                SELECT
                    id,
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
                ':payment_id' => $payment['id']
            ]);

            $paymentItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        error_log(
            'Minguito customer payment refresh error: ' .
            $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| CALCULATE BILL DISPLAY
|--------------------------------------------------------------------------
*/
$billTotal = 0.00;

foreach ($paymentItems as $item) {
    $billTotal += (float)$item['subtotal'];
}

$billTotal = round($billTotal, 2);

/*
 * If there is no prepared bill yet, show the estimated total of all
 * selected Active services.
 */
if (empty($paymentItems)) {

    $appointmentServiceNames =
        getAppointmentServiceNames(
            (string)($appointment['service'] ?? '')
        );

    $estimatedServices =
        getActiveServicesByNames(
            $pdo,
            $appointmentServiceNames
        );

    $estimatedTotal = 0.00;

    foreach (
        $appointmentServiceNames
        as $serviceName
    ) {

        $serviceKey =
            strtolower(trim($serviceName));

        if (isset($estimatedServices[$serviceKey])) {
            $estimatedTotal +=
                (float)$estimatedServices[$serviceKey]['price'];
        }
    }

    $billTotal =
        round($estimatedTotal, 2);
}

$pageTitle = 'Payment';
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<style>
.payment-page {
    min-height: 72vh;
    padding: 72px 0 90px;
    background:
        radial-gradient(circle at 10% 10%, rgba(181,122,47,.08), transparent 27%),
        radial-gradient(circle at 90% 90%, rgba(7,59,42,.07), transparent 30%),
        #f8efe2;
}

.payment-wrap {
    width: min(980px, calc(100% - 32px));
    margin: 0 auto;
}

.payment-heading {
    text-align: center;
    margin-bottom: 28px;
}

.payment-heading .label {
    color: #b57a2f;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.8px;
}

.payment-heading h1 {
    margin: 8px 0 10px;
    color: #073b2a;
    font: 700 clamp(38px, 5vw, 54px)/1.05 "Playfair Display", Georgia, serif;
}

.payment-heading p {
    margin: 0 auto;
    max-width: 650px;
    color: #66756e;
    font-size: 14px;
    line-height: 1.7;
}

.payment-grid {
    display: grid;
    grid-template-columns: .9fr 1.1fr;
    gap: 18px;
    align-items: start;
}

.payment-card {
    background: #fff;
    border: 1px solid rgba(7,59,42,.10);
    border-radius: 21px;
    box-shadow: 0 15px 34px rgba(7,59,42,.08);
    overflow: hidden;
}

.card-head {
    padding: 21px 22px;
    border-bottom: 1px solid #eadfce;
}

.card-head h2 {
    margin: 0 0 4px;
    color: #073b2a;
    font-size: 18px;
}

.card-head p {
    margin: 0;
    color: #77857e;
    font-size: 12px;
}

.card-body {
    padding: 22px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 11px 0;
    border-bottom: 1px solid #f0e7da;
    font-size: 13px;
}

.info-row:last-child {
    border-bottom: 0;
}

.info-row span {
    color: #89958f;
}

.info-row strong {
    color: #214f43;
    text-align: right;
}

.bill-box {
    margin-top: 18px;
    padding: 16px;
    border-radius: 15px;
    background: #f8f1e5;
}

.bill-title {
    margin-bottom: 12px;
    color: #073b2a;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .7px;
}

.bill-item {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 9px 0;
    border-bottom: 1px solid #e8dbc8;
    color: #214f43;
    font-size: 13px;
}

.bill-item:last-child {
    border-bottom: 0;
}

.bill-item small {
    display: block;
    margin-top: 3px;
    color: #89958f;
    font-size: 10px;
}

.bill-total {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 13px;
    padding-top: 13px;
    border-top: 1px solid #dfcfb8;
    color: #073b2a;
    font-size: 18px;
    font-weight: 800;
}

.notice {
    margin-bottom: 18px;
    padding: 14px 16px;
    border-radius: 12px;
    font-size: 13px;
}

.notice.error {
    background: #fff1ee;
    border: 1px solid #efc4be;
    color: #a13d32;
}

.notice.success {
    background: #eaf7ef;
    border: 1px solid #c9dfd1;
    color: #246a45;
}

.pending-box,
.paid-box,
.cancelled-box {
    padding: 16px;
    border-radius: 15px;
}

.pending-box {
    background: #fff7e8;
    border: 1px solid #ecd5a8;
}

.paid-box {
    background: #eaf7ef;
    border: 1px solid #c9dfd1;
}

.cancelled-box {
    background: #fff1ee;
    border: 1px solid #efc4be;
}

.pending-box strong,
.paid-box strong,
.cancelled-box strong {
    display: block;
    margin-bottom: 5px;
    color: #073b2a;
    font-size: 14px;
}

.pending-box p,
.paid-box p,
.cancelled-box p {
    margin: 0;
    color: #66756e;
    font-size: 12px;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 17px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    color: #214f43;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.method-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.method-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.method-option label {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 50px;
    border: 1px solid #ddcfbb;
    border-radius: 11px;
    background: #fffaf3;
    color: #073b2a;
    cursor: pointer;
    font-size: 12px;
    font-weight: 800;
}

.method-option input:checked + label {
    border-color: #073b2a;
    background: #e8f2ec;
    box-shadow: 0 0 0 2px rgba(7,59,42,.07);
}

.gcash-field {
    display: none;
}

.gcash-field.show {
    display: block;
}

.gcash-field input {
    width: 100%;
    height: 46px;
    padding: 0 13px;
    border: 1px solid #ddcfbb;
    border-radius: 10px;
    background: #fff;
    color: #214f43;
    font: inherit;
    box-sizing: border-box;
}

.gcash-help {
    margin-top: 6px;
    color: #89958f;
    font-size: 11px;
    line-height: 1.5;
}

.submit-btn,
.back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
}

.submit-btn {
    width: 100%;
    border: 0;
    background: #073b2a;
    color: #fff;
    cursor: pointer;
}

.submit-btn:hover {
    background: #b57a2f;
}

.back-btn {
    width: 100%;
    margin-top: 10px;
    border: 1px solid #d9c6aa;
    background: #fffaf3;
    color: #073b2a;
}

@media (max-width: 800px) {
    .payment-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 500px) {
    .method-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="payment-page">
    <div class="payment-wrap">

        <header class="payment-heading">
            <div class="label">CUSTOMER PORTAL</div>
            <h1>Pay Appointment</h1>
            <p>
                Review the bill prepared by the clinic, choose your payment
                method, and submit it for verification.
            </p>
        </header>

        <?php if ($message !== ''): ?>
            <div class="notice <?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="payment-grid">

            <!-- APPOINTMENT + BILL -->
            <article class="payment-card">

                <div class="card-head">
                    <h2>Appointment Details</h2>
                    <p>Information connected to your payment.</p>
                </div>

                <div class="card-body">

                    <div class="info-row">
                        <span>Appointment</span>
                        <strong>#<?= (int)$appointment['id'] ?></strong>
                    </div>

                    <div class="info-row">
                        <span>Pet</span>
                        <strong>
                            <?= htmlspecialchars(
                                $appointment['pet_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Owner</span>
                        <strong>
                            <?= htmlspecialchars(
                                $appointment['owner_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Date</span>
                        <strong>
                            <?= date(
                                'F j, Y',
                                strtotime($appointment['appointment_date'])
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Time</span>
                        <strong>
                            <?= date(
                                'g:i A',
                                strtotime($appointment['appointment_time'])
                            ) ?>
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Status</span>
                        <strong>Approved</strong>
                    </div>

                    <?php if (!empty($paymentItems)): ?>

                        <div class="bill-box">

                            <div class="bill-title">
                                <i class="fa-solid fa-file-invoice"></i>
                                Bill Details
                            </div>

                            <?php foreach ($paymentItems as $item): ?>

                                <div class="bill-item">

                                    <div>
                                        <?= htmlspecialchars(
                                            $item['item_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <small>
                                            <?= htmlspecialchars(
                                                $item['item_type'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                            ·
                                            Qty <?= (int)$item['quantity'] ?>
                                            ·
                                            ₱<?= number_format(
                                                (float)$item['unit_price'],
                                                2
                                            ) ?>
                                            each
                                        </small>
                                    </div>

                                    <strong>
                                        ₱<?= number_format(
                                            (float)$item['subtotal'],
                                            2
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                            <div class="bill-total">
                                <span>Total</span>
                                <strong>
                                    ₱<?= number_format($billTotal, 2) ?>
                                </strong>
                            </div>

                        </div>

                    <?php elseif ($service && (float)$service['price'] > 0): ?>

                        <div class="bill-box">

                            <div class="bill-title">
                                <i class="fa-solid fa-file-invoice"></i>
                                Service Charge
                            </div>

                            <div class="bill-item">

                                <div>
                                    <?= htmlspecialchars(
                                        $service['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <small>
                                        Appointment service · Qty 1
                                    </small>
                                </div>

                                <strong>
                                    ₱<?= number_format(
                                        (float)$service['price'],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                            <div class="bill-total">
                                <span>Total</span>
                                <strong>
                                    ₱<?= number_format($billTotal, 2) ?>
                                </strong>
                            </div>

                        </div>

                    <?php else: ?>

                        <div class="notice error" style="margin-top:18px;margin-bottom:0;">
                            The price for this service is not currently available.
                            Please contact the clinic administrator.
                        </div>

                    <?php endif; ?>

                </div>
            </article>

            <!-- PAYMENT -->
            <article class="payment-card">

                <div class="card-head">
                    <h2>Payment</h2>
                    <p>Choose a method and submit the prepared bill.</p>
                </div>

                <div class="card-body">

                    <?php if (
                        $payment &&
                        $payment['payment_status'] === 'Pending' &&
                        (
                            !empty(trim($payment['payment_method'] ?? '')) ||
                            !empty(trim($payment['reference_number'] ?? ''))
                        )
                    ): ?>

                        <div class="pending-box">
                            <strong>⏳ Pending Verification</strong>
                            <p>
                                Your payment has been submitted.
                                The clinic administrator must verify it
                                before it becomes Paid.
                            </p>
                        </div>

                    <?php elseif (
                        $payment &&
                        $payment['payment_status'] === 'Paid'
                    ): ?>

                        <div class="paid-box">
                            <strong>✓ Payment Verified</strong>
                            <p>
                                Your payment of
                                ₱<?= number_format(
                                    (float)$payment['amount'],
                                    2
                                ) ?>
                                has been verified by the clinic.
                            </p>
                        </div>

                    <?php elseif (
                        $payment &&
                        $payment['payment_status'] === 'Cancelled'
                    ): ?>

                        <div class="cancelled-box">
                            <strong>Payment Rejected</strong>
                            <p>
                                Your previous payment submission was rejected.
                                You may submit the payment again.
                            </p>
                        </div>

                    <?php elseif (
                        $payment &&
                        $payment['payment_status'] === 'Pending'
                    ): ?>

                        <div class="pending-box">
                            <strong>Bill Ready for Payment</strong>
                            <p>
                                The clinic has prepared your bill.
                                Please choose Cash or GCash below and submit
                                your payment for verification.
                            </p>
                        </div>

                    <?php endif; ?>

                    <?php
                    /*
                     * Allow submission when:
                     * - no payment exists
                     * - payment was Cancelled
                     * - payment is Pending but has not yet been submitted
                     *   (identified by no GCash reference)
                     */
                    $canSubmit =
                        !$payment ||
                        $payment['payment_status'] === 'Cancelled' ||
                        (
                            $payment['payment_status'] === 'Pending' &&
                            empty($payment['reference_number'])
                        );
                    ?>

                    <?php if ($canSubmit && $billTotal > 0): ?>

                        <form
                            method="POST"
                            action="payment.php?appointment_id=<?= (int)$appointmentId ?>"
                            id="paymentForm"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="submit_payment"
                            >

                            <input
                                type="hidden"
                                name="appointment_id"
                                value="<?= (int)$appointmentId ?>"
                            >

                            <div class="form-group">

                                <label>Payment Method</label>

                                <div class="method-grid">

                                    <div class="method-option">

                                        <input
                                            type="radio"
                                            id="cash"
                                            name="payment_method"
                                            value="Cash"
                                            checked
                                        >

                                        <label for="cash">
                                            💵 Cash
                                        </label>

                                    </div>

                                    <div class="method-option">

                                        <input
                                            type="radio"
                                            id="gcash"
                                            name="payment_method"
                                            value="GCash"
                                        >

                                        <label for="gcash">
                                            📱 GCash
                                        </label>

                                    </div>

                                </div>

                            </div>

                            <div
                                class="form-group gcash-field"
                                id="gcashField"
                            >

                                <label for="reference_number">
                                    GCash Reference Number
                                </label>

                                <input
                                    type="text"
                                    id="reference_number"
                                    name="reference_number"
                                    maxlength="100"
                                    placeholder="Enter your GCash reference number"
                                >

                                <div class="gcash-help">
                                    Required when GCash is selected.
                                </div>

                            </div>

                            <button
                                type="submit"
                                class="submit-btn"
                            >
                                <i class="fa-solid fa-paper-plane"></i>
                                Submit Payment for Verification
                            </button>

                            <a
                                href="appointments.php"
                                class="back-btn"
                            >
                                ← Back to My Appointments
                            </a>

                        </form>

                    <?php else: ?>

                        <a
                            href="appointments.php"
                            class="back-btn"
                        >
                            ← Back to My Appointments
                        </a>

                    <?php endif; ?>

                </div>
            </article>

        </div>
    </div>
</section>

<script>
(function () {

    const cash = document.getElementById('cash');
    const gcash = document.getElementById('gcash');
    const gcashField = document.getElementById('gcashField');
    const referenceInput = document.getElementById('reference_number');

    if (!cash || !gcash || !gcashField) {
        return;
    }

    function updatePaymentMethod() {

        const isGCash = gcash.checked;

        gcashField.classList.toggle(
            'show',
            isGCash
        );

        if (referenceInput) {
            referenceInput.required = isGCash;
        }
    }

    cash.addEventListener(
        'change',
        updatePaymentMethod
    );

    gcash.addEventListener(
        'change',
        updatePaymentMethod
    );

    updatePaymentMethod();

})();
</script>

<?php include 'includes/footer.php'; ?>
