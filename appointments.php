<?php

session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?redirect=appointments.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$customerId = (int) $_SESSION['customer_id'];

$appointments = [];
$error = '';

try {

    $stmt = $pdo->prepare(
        'SELECT
            a.id,
            a.owner_name,
            a.pet_name,
            a.service,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.created_at,
            p.id AS payment_id,
            p.amount AS payment_amount,
            p.payment_method,
            p.reference_number,
            p.payment_status
         FROM appointments a
         LEFT JOIN payments p
            ON p.id = (
                SELECT MAX(p2.id)
                FROM payments p2
                WHERE p2.appointment_id = a.id
            )
         WHERE a.customer_id = :customer_id
         ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC'
    );

    $stmt->execute([
        ':customer_id' => $customerId
    ]);

    $appointments = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log(
        'Minguito customer appointments error: ' .
        $e->getMessage()
    );

    $error = 'Unable to load your appointments right now.';
}

$pageTitle = 'My Appointments';


function statusClass(string $status): string
{
    return match (strtolower(trim($status))) {
        'approved'  => 'approved',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        default     => 'pending'
    };
}


function formatDateValue(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date('F j, Y', $timestamp)
        : $date;
}


function formatTimeValue(string $time): string
{
    $timestamp = strtotime($time);

    return $timestamp !== false
        ? date('g:i A', $timestamp)
        : $time;
}

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<style>

.customer-appointments-page {
    min-height: 72vh;
    padding: 72px 0 90px;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(181, 122, 47, .08),
            transparent 26%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(7, 59, 42, .07),
            transparent 29%
        ),
        #f8efe2;
}

.customer-appointments-wrap {
    width: min(1040px, calc(100% - 32px));
    margin: 0 auto;
}

.customer-heading {
    text-align: center;
    margin-bottom: 28px;
}

.customer-heading .label {
    color: #b57a2f;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.8px;
}

.customer-heading h1 {
    margin: 8px 0 10px;
    color: #073b2a;

    font:
        700 clamp(40px, 5vw, 58px)/1.03
        "Playfair Display",
        Georgia,
        serif;
}

.customer-heading p {
    max-width: 650px;
    margin: 0 auto;
    color: #66756e;
    font-size: 14px;
    line-height: 1.7;
}

.appointments-toolbar {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-height: 46px;
    padding: 0 19px;

    border-radius: 11px;
    text-decoration: none;

    font-size: 12px;
    font-weight: 800;

    transition: .22s ease;
}

.toolbar-btn.primary {
    background: #073b2a;
    color: #fff;
}

.toolbar-btn.primary:hover {
    background: #b57a2f;
    transform: translateY(-1px);
}

.toolbar-btn.secondary {
    border: 1px solid #d9c6aa;
    background: #fffaf3;
    color: #073b2a;
}

.toolbar-btn.secondary:hover {
    border-color: #b57a2f;
    color: #b57a2f;
}

.notice {
    margin-bottom: 18px;
    padding: 14px 16px;

    border-radius: 12px;

    background: #fff1ee;
    border: 1px solid #efc4be;

    color: #a13d32;

    text-align: center;
    font-size: 13px;
}

.appointment-list {
    display: grid;
    gap: 16px;
}

.appointment-card {
    background: #fff;

    border:
        1px solid
        rgba(7, 59, 42, .10);

    border-radius: 21px;

    padding: 22px 24px;

    box-shadow:
        0 15px 34px
        rgba(7, 59, 42, .08);
}

.card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    gap: 16px;
    margin-bottom: 15px;
}

.card-title {
    min-width: 0;
}

.card-title h2 {
    margin: 0 0 5px;

    color: #073b2a;
    font-size: 18px;
}

.card-title p {
    margin: 0;

    color: #78867f;
    font-size: 12px;
}

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-height: 29px;
    padding: 0 11px;

    border-radius: 999px;

    background: #f5e5c9;
    color: #9b641e;

    font-size: 11px;
    font-weight: 800;

    white-space: nowrap;
}

.status.approved {
    background: #e8f6ef;
    color: #14633f;
}

.status.completed {
    background: #e8f0ff;
    color: #305da8;
}

.status.cancelled {
    background: #fff1ee;
    color: #a13d32;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
}

.detail-box {
    min-width: 0;

    padding: 12px 13px;

    border-radius: 12px;
    background: #f8f1e5;
}

.detail-box span {
    display: block;

    margin-bottom: 4px;

    color: #89958f;

    font-size: 10px;
    font-weight: 800;

    letter-spacing: .85px;
    text-transform: uppercase;
}

.detail-box strong {
    display: block;

    overflow: hidden;

    color: #214f43;

    font-size: 13px;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.empty-state {
    background: #fff;

    border: 1px dashed #d9c7af;

    border-radius: 21px;

    padding: 54px 24px;

    text-align: center;

    box-shadow:
        0 15px 30px
        rgba(7, 59, 42, .05);
}

.empty-state .icon {
    width: 56px;
    height: 56px;

    margin: 0 auto 13px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    background: #f8f1e5;
    color: #b57a2f;

    font-size: 22px;
}

.empty-state h2 {
    margin: 0 0 8px;

    color: #073b2a;
    font-size: 21px;
}

.empty-state p {
    max-width: 460px;

    margin: 0 auto 19px;

    color: #66756e;

    font-size: 14px;
    line-height: 1.7;
}

.payment-notice {
    margin-bottom: 18px;
    padding: 14px 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 13px;
    font-weight: 700;
}

.payment-notice.success {
    background: #e8f6ef;
    border: 1px solid #c8dfd0;
    color: #14633f;
}

.payment-area {
    margin-top: 17px;
    padding-top: 16px;
    border-top: 1px solid rgba(7, 59, 42, .10);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.payment-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 35px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
}

.payment-status.required {
    background: #f5e5c9;
    color: #9b641e;
}

.payment-status.pending {
    background: #fff4dc;
    color: #8a641d;
}

.payment-status.paid {
    background: #e8f6ef;
    color: #14633f;
}

.payment-status.cancelled {
    background: #fff1ee;
    color: #a13d32;
}

.payment-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 38px;
    padding: 0 15px;
    border-radius: 10px;
    text-decoration: none;
    background: #073b2a;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    transition: .22s ease;
}

.payment-button:hover {
    background: #b57a2f;
    transform: translateY(-1px);
}

.payment-info {
    width: 100%;
    margin-top: 2px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 9px;
}

.payment-info-box {
    padding: 10px 12px;
    border-radius: 10px;
    background: #fbf7ef;
    border: 1px solid #eadfce;
}

.payment-info-box span {
    display: block;
    margin-bottom: 3px;
    color: #89958f;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.payment-info-box strong {
    display: block;
    overflow: hidden;
    color: #214f43;
    font-size: 12px;
    white-space: nowrap;
    text-overflow: ellipsis;
}

@media (max-width: 560px) {
    .payment-area {
        align-items: stretch;
        flex-direction: column;
    }

    .payment-button {
        width: 100%;
    }

    .payment-info {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {

    .customer-appointments-page {
        padding: 56px 0 72px;
    }

    .details-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

}

@media (max-width: 560px) {

    .customer-appointments-wrap {
        width: calc(100% - 28px);
    }

    .appointment-card {
        padding: 18px;
    }

    .card-head {
        flex-direction: column;
        align-items: stretch;
    }

    .status {
        width: fit-content;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .toolbar-btn {
        width: 100%;
    }

}

</style>


<section class="customer-appointments-page">

    <div class="customer-appointments-wrap">

        <header class="customer-heading">

            <div class="label">
                CUSTOMER PORTAL
            </div>

            <h1>
                My Appointments
            </h1>

            <p>
                View your appointment requests,
                selected services, and the
                current status of each booking.
            </p>

        </header>


        <div class="appointments-toolbar">

            <a
                class="toolbar-btn primary"
                href="appointment.php"
            >
                📅 BOOK AN APPOINTMENT
            </a>

            <a
                class="toolbar-btn secondary"
                href="profile.php"
            >
                👤 MY PROFILE
            </a>

        </div>


        <?php if ($error !== ''): ?>

            <div class="notice">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endif; ?>

        <?php if (($_GET['payment'] ?? '') === 'submitted'): ?>

            <div class="payment-notice success">
                <i class="fa-solid fa-circle-check"></i>
                <span>Payment submitted successfully. Please wait while the clinic verifies your payment.</span>
            </div>

        <?php endif; ?>


        <?php if (!empty($appointments)): ?>

            <div class="appointment-list">

                <?php foreach (
                    $appointments
                    as $appointment
                ): ?>

                    <?php
                        $status =
                            trim(
                                $appointment['status']
                                ?? 'Pending'
                            );
                    ?>

                    <article class="appointment-card">

                        <div class="card-head">

                            <div class="card-title">

                                <h2>
                                    <?= htmlspecialchars(
                                        $appointment['service'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h2>

                                <p>
                                    Appointment #
                                    <?= (int) $appointment['id'] ?>
                                </p>

                            </div>


                            <span
                                class="status <?= htmlspecialchars(
                                    statusClass($status),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    $status,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </div>


                        <div class="details-grid">

                            <div class="detail-box">

                                <span>
                                    Pet Name
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $appointment['pet_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Owner
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $appointment['owner_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Date
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        formatDateValue(
                                            $appointment[
                                                'appointment_date'
                                            ]
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-box">

                                <span>
                                    Time
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        formatTimeValue(
                                            $appointment[
                                                'appointment_time'
                                            ]
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                            </div>

                        </div>

                        <?php
                            $paymentStatus = trim($appointment['payment_status'] ?? '');
                            $appointmentStatus = strtolower($status);
                        ?>

                        <div class="payment-area">

                            <?php if (
                                $appointmentStatus === 'approved' &&
                                (
                                    $paymentStatus === '' ||
                                    (
                                        $paymentStatus === 'Pending' &&
                                        empty(trim($appointment['reference_number'] ?? ''))
                                    )
                                )
                            ): ?>

                                <span class="payment-status required">
                                    <i class="fa-solid fa-wallet"></i>
                                    Payment Required
                                </span>

                                <a
                                    href="payment.php?appointment_id=<?= (int)$appointment['id'] ?>"
                                    class="payment-button"
                                >
                                    <i class="fa-solid fa-credit-card"></i>
                                    Pay Appointment
                                </a>

                            <?php elseif ($paymentStatus === 'Pending'): ?>

                                <span class="payment-status pending">
                                    <i class="fa-solid fa-clock"></i>
                                    Pending Verification
                                </span>

                                <div class="payment-info">
                                    <div class="payment-info-box">
                                        <span>Amount</span>
                                        <strong>₱<?= number_format((float)$appointment['payment_amount'], 2) ?></strong>
                                    </div>
                                    <div class="payment-info-box">
                                        <span>Method</span>
                                        <strong><?= htmlspecialchars($appointment['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="payment-info-box">
                                        <span>Reference</span>
                                        <strong><?= !empty($appointment['reference_number']) ? htmlspecialchars($appointment['reference_number'], ENT_QUOTES, 'UTF-8') : 'Not applicable' ?></strong>
                                    </div>
                                </div>

                            <?php elseif ($paymentStatus === 'Paid'): ?>

                                <span class="payment-status paid">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Payment Paid
                                </span>

                                <div class="payment-info">
                                    <div class="payment-info-box">
                                        <span>Amount</span>
                                        <strong>₱<?= number_format((float)$appointment['payment_amount'], 2) ?></strong>
                                    </div>
                                    <div class="payment-info-box">
                                        <span>Method</span>
                                        <strong><?= htmlspecialchars($appointment['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="payment-info-box">
                                        <span>Reference</span>
                                        <strong><?= !empty($appointment['reference_number']) ? htmlspecialchars($appointment['reference_number'], ENT_QUOTES, 'UTF-8') : 'Not applicable' ?></strong>
                                    </div>
                                </div>

                            <?php elseif ($paymentStatus === 'Cancelled'): ?>

                                <span class="payment-status cancelled">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                    Payment Rejected
                                </span>

                                <?php if ($appointmentStatus === 'approved'): ?>
                                    <a
                                        href="payment.php?appointment_id=<?= (int)$appointment['id'] ?>"
                                        class="payment-button"
                                    >
                                        <i class="fa-solid fa-rotate-right"></i>
                                        Submit Payment Again
                                    </a>
                                <?php endif; ?>

                            <?php elseif ($appointmentStatus === 'completed'): ?>

                                <span class="payment-status paid">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Appointment Completed
                                </span>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="icon">
                    🐾
                </div>

                <h2>
                    No Appointments Yet
                </h2>

                <p>
                    Your booking history will appear
                    here after you submit your first
                    veterinary appointment.
                </p>

                <a
                    class="toolbar-btn primary"
                    href="appointment.php"
                >
                    BOOK YOUR FIRST APPOINTMENT
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php include 'includes/footer.php'; ?>
