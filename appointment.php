<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php?redirect=appointment.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$customerId = (int) $_SESSION['customer_id'];
$customer = null;
$error = '';

try {
    $customerStmt = $pdo->prepare(
        'SELECT id, full_name, email, contact_number
         FROM customers
         WHERE id = :id
         LIMIT 1'
    );

    $customerStmt->execute([
        ':id' => $customerId
    ]);

    $customer = $customerStmt->fetch();

    if (!$customer) {
        $_SESSION = [];
        session_destroy();

        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    $error = 'Unable to load your account information. Please try again.';
}

$pageTitle = 'Book an Appointment';

$today = date('Y-m-d');

$selectedServices = [];

if (isset($_GET['services']) && is_array($_GET['services'])) {
    $selectedServices = array_values(array_filter(
        array_map('trim', $_GET['services']),
        static fn($service) => $service !== ''
    ));
} elseif (isset($_GET['service'])) {
    $legacyService = trim((string)$_GET['service']);

    if ($legacyService !== '') {
        $selectedServices = [$legacyService];
    }
}

/*
|--------------------------------------------------------------------------
| ACTIVE VETERINARY SERVICES
|--------------------------------------------------------------------------
| Services are loaded directly from the Services table.
| Only Active services are displayed to customers.
*/
$services = [];

try {
    $serviceListStmt = $pdo->query("
        SELECT id, name, price
        FROM services
        WHERE status = 'Active'
        ORDER BY name ASC
    ");

    $services = $serviceListStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log(
        'Minguito appointment services load error: ' .
        $e->getMessage()
    );

    $services = [];
}

$activeServiceNames = array_map(
    static fn($service) => trim((string)$service['name']),
    $services
);

$selectedServices = array_values(array_filter(
    $selectedServices,
    static fn($service) =>
        in_array($service, $activeServiceNames, true)
));


/*
|--------------------------------------------------------------------------
| CLINIC HOURS
|--------------------------------------------------------------------------
| Monday - Saturday: 8:00 AM - 6:00 PM
| Sunday: 9:00 AM - 1:00 PM
*/
$clinicHours = [
    1 => ['08:00', '18:00'], // Monday
    2 => ['08:00', '18:00'], // Tuesday
    3 => ['08:00', '18:00'], // Wednesday
    4 => ['08:00', '18:00'], // Thursday
    5 => ['08:00', '18:00'], // Friday
    6 => ['08:00', '18:00'], // Saturday
    0 => ['09:00', '13:00'], // Sunday
];

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>

/* =========================================================
   MINGUITO APPOINTMENT PAGE
   Scoped styles so this page does not disturb
   the shared header, navbar, or footer.
========================================================= */

.appointment-page {
    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(190, 126, 38, 0.08),
            transparent 28%
        ),
        radial-gradient(
            circle at 90% 85%,
            rgba(17, 67, 51, 0.07),
            transparent 30%
        ),
        #f8efe0;

    padding: 76px 0 92px;
}

.appointment-page .appointment-layout {
    max-width: 1180px;
    margin: 0 auto;

    display: grid;
    grid-template-columns: 390px minmax(0, 1fr);

    gap: 58px;
    align-items: start;
}


/* =========================================================
   LEFT SIDE - LOCATION
========================================================= */

.appointment-page .location-card {
    position: relative;
    overflow: hidden;

    background: #ffffff;

    border: 1px solid rgba(17, 67, 51, 0.08);
    border-radius: 28px;

    padding: 34px;

    box-shadow:
        0 18px 45px rgba(30, 55, 43, 0.10);
}

.appointment-page .location-card::before {
    content: "";

    position: absolute;

    width: 150px;
    height: 150px;

    border-radius: 50%;

    background: rgba(190, 126, 38, 0.08);

    top: -78px;
    right: -55px;
}

.appointment-page .location-eyebrow {
    margin: 0 0 9px;

    color: #be7e26;

    font-size: 12px;
    font-weight: 800;

    letter-spacing: 2px;
    text-transform: uppercase;
}

.appointment-page .location-card h2 {
    margin: 0 0 18px;

    color: #064b3b;

    font-family: Georgia, "Times New Roman", serif;

    font-size: 38px;
    line-height: 1.1;
}

.appointment-page .location-address {
    display: flex;

    gap: 12px;
    align-items: flex-start;

    margin: 0 0 20px;

    color: #214f43;

    font-size: 16px;
    line-height: 1.7;
}

.appointment-page .location-pin {
    flex: 0 0 34px;

    width: 34px;
    height: 34px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    background: #f5e3c5;
    color: #be7e26;

    font-size: 17px;
    font-weight: 800;
}

.appointment-page .maps-link {
    display: inline-flex;

    align-items: center;
    gap: 7px;

    margin-bottom: 22px;

    color: #064b3b;

    font-weight: 700;

    text-decoration: underline;
    text-underline-offset: 4px;
}

.appointment-page .maps-link:hover {
    color: #be7e26;
}

.appointment-page .map-frame {
    overflow: hidden;

    height: 285px;

    border-radius: 20px;

    background: #eee9df;

    box-shadow:
        inset 0 0 0 1px rgba(17, 67, 51, 0.06);
}

.appointment-page .map-frame iframe {
    width: 100%;
    height: 100%;

    display: block;

    border: 0;
}

.appointment-page .clinic-note {
    margin: 20px 0 0;

    padding: 15px 16px;

    border-radius: 15px;

    background: #f8f1e5;

    color: #52685f;

    font-size: 13px;
    line-height: 1.6;
}

.appointment-page .clinic-note strong {
    display: block;

    margin-bottom: 3px;

    color: #064b3b;
}


/* =========================================================
   RIGHT SIDE - APPOINTMENT FORM
========================================================= */

.appointment-page .appointment-form {
    padding-top: 8px;
}

.appointment-page .section-label {
    margin: 0 0 10px;

    color: #be7e26;

    font-size: 13px;
    font-weight: 800;

    letter-spacing: 2px;
}

.appointment-page .appointment-form h1 {
    margin: 0 0 13px;

    color: #be7e26;

    font-family: Georgia, "Times New Roman", serif;

    font-size: clamp(46px, 5vw, 67px);
    line-height: 0.98;

    letter-spacing: -1.5px;
}

.appointment-page .form-intro {
    max-width: 690px;

    margin: 0 0 30px;

    color: #53685f;

    font-size: 16px;
    line-height: 1.7;
}


/* =========================================================
   ACCOUNT INFORMATION
========================================================= */

.appointment-page .account-note {
    margin: -12px 0 26px;

    padding: 12px 14px;

    border-radius: 12px;

    background: #f8f1e5;

    color: #53685f;

    font-size: 12px;
    line-height: 1.6;

    border: 1px solid rgba(7, 59, 42, 0.08);
}

.appointment-page .account-note strong {
    color: #064b3b;
}


/* =========================================================
   SUCCESS / ERROR MESSAGE
========================================================= */

.appointment-page .success-message {
    margin: 0 0 22px;

    padding: 15px 18px;

    border: 1px solid rgba(22, 117, 78, 0.18);

    border-radius: 14px;

    background: #e9f7ef;

    color: #14633f;

    font-weight: 700;
}

.appointment-page .success-message a {
    color: #14633f;

    text-decoration: underline;
}


/* =========================================================
   FORM
========================================================= */

.appointment-page .appointment-form form {
    display: flex;

    flex-direction: column;

    gap: 17px;
}

.appointment-page .form-row {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 17px;
}

.appointment-page .form-group {
    min-width: 0;
}

.appointment-page .form-group label {
    display: block;

    margin: 0 0 7px 2px;

    color: #174f42;

    font-size: 13px;
    font-weight: 750;
}

.appointment-page .input-wrap {
    position: relative;
}
/* =========================================================
   INPUTS / SELECT
========================================================= */

.appointment-page input,
.appointment-page select {
    width: 100%;
    height: 62px;

    box-sizing: border-box;

    padding: 0 18px;

    border: 1.5px solid #d49a52;

    border-radius: 9px;

    outline: none;

    background: rgba(255, 250, 242, 0.58);

    color: #064b3b;

    font: inherit;
    font-size: 16px;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}
.appointment-page input::placeholder {
    color: #718078;
}

.appointment-page input:focus,
.appointment-page select:focus {
    border-color: #064b3b;

    background: #fffaf2;

    box-shadow:
        0 0 0 4px rgba(6, 75, 59, 0.08);
}

.appointment-page select {
    cursor: pointer;

    appearance: auto;
}

.appointment-page input[type="date"],
.appointment-page input[type="time"] {
    color-scheme: light;
}


/* =========================================================
   MULTIPLE VETERINARY SERVICES
========================================================= */

.appointment-page .service-help {
    margin: -5px 0 11px 2px;
    color: #718078;
    font-size: 12px;
    line-height: 1.5;
}

.appointment-page .service-checkboxes {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.appointment-page .service-option {
    position: relative;
}

.appointment-page .service-option input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.appointment-page .service-option label {
    min-height: 66px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 15px;
    border: 1.5px solid #d49a52;
    border-radius: 11px;
    background: rgba(255, 250, 242, 0.58);
    color: #174f42;
    cursor: pointer;
    transition: .2s ease;
}

.appointment-page .service-option label:hover {
    border-color: #064b3b;
    background: #fffaf2;
    transform: translateY(-1px);
}

.appointment-page .service-option input[type="checkbox"]:checked + label {
    border-color: #064b3b;
    background: #eaf4ed;
    box-shadow: 0 0 0 3px rgba(6, 75, 59, .08);
}

.appointment-page .service-option input[type="checkbox"]:checked + label::before {
    content: "✓";
    order: 2;
    flex: 0 0 25px;
    width: 25px;
    height: 25px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #064b3b;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
}

.appointment-page .service-option .service-name {
    display: block;
    font-size: 13px;
    font-weight: 750;
    line-height: 1.35;
}

.appointment-page .service-option .service-price {
    display: block;
    margin-top: 4px;
    color: #be7e26;
    font-size: 12px;
    font-weight: 800;
}

.appointment-page .service-summary {
    margin-top: 13px;
    padding: 13px 15px;
    border: 1px solid rgba(6, 75, 59, .10);
    border-radius: 12px;
    background: #f8f1e5;
    color: #53685f;
    font-size: 12px;
    line-height: 1.6;
}

.appointment-page .service-summary strong {
    color: #064b3b;
}

.appointment-page .service-summary-total {
    float: right;
    color: #be7e26;
    font-size: 14px;
    font-weight: 850;
}

.appointment-page .service-selection-error {
    display: none;
    margin-top: 9px;
    padding: 9px 11px;
    border-radius: 9px;
    background: #fff1ee;
    border: 1px solid #efc4be;
    color: #a13d32;
    font-size: 12px;
}

.appointment-page .service-selection-error.show {
    display: block;
}

/* =========================================================
   BOOK BUTTON
========================================================= */

.appointment-page .appointment-submit {
    width: 100%;

    min-height: 68px;

    margin-top: 5px;

    border: 0;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #d8b06f,
            #c8964a
        );

    color: #064b3b;

    font: inherit;

    font-size: 19px;
    font-weight: 850;

    letter-spacing: 0.3px;

    cursor: pointer;

    box-shadow:
        0 12px 24px rgba(190, 126, 38, 0.18);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        filter 0.2s ease;
}

.appointment-page .appointment-submit:hover {
    transform: translateY(-2px);

    box-shadow:
        0 16px 30px rgba(190, 126, 38, 0.25);

    filter: brightness(1.03);
}

.appointment-page .appointment-submit:active {
    transform: translateY(0);
}


/* =========================================================
   FOOTNOTE
========================================================= */

.appointment-page .form-footnote {
    margin: 2px 0 0;

    color: #738078;

    font-size: 12px;
    line-height: 1.6;

    text-align: center;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 980px) {

    .appointment-page {
        padding: 58px 0 72px;
    }

    .appointment-page .appointment-layout {
        grid-template-columns: 1fr;

        gap: 42px;

        max-width: 720px;
    }

    .appointment-page .location-card {
        order: 2;
    }

    .appointment-page .appointment-form {
        order: 1;

        padding-top: 0;
    }

    .appointment-page .map-frame {
        height: 320px;
    }
}


@media (max-width: 620px) {

    .appointment-page .service-checkboxes {
        grid-template-columns: 1fr;
    }

    .appointment-page {
        padding: 40px 0 58px;
    }

    .appointment-page .appointment-layout {
        width: calc(100% - 30px);

        gap: 32px;
    }

    .appointment-page .location-card {
        padding: 26px;

        border-radius: 22px;
    }

    .appointment-page .location-card h2 {
        font-size: 34px;
    }

    .appointment-page .appointment-form h1 {
        font-size: 45px;
    }

    .appointment-page .form-row {
        grid-template-columns: 1fr;

        gap: 17px;
    }

    .appointment-page .map-frame {
        height: 260px;
    }
}

</style>


<!-- =========================================================
     APPOINTMENT SECTION
========================================================= -->

<section class="appointment-page">

    <div class="container appointment-layout">


        <!-- =====================================================
             LOCATION CARD
        ====================================================== -->

        <aside class="location-card">

            <p class="location-eyebrow">
                MINGUITO VETERINARY CLINIC
            </p>

            <h2>
                Our Location
            </h2>


            <p class="location-address">

                <span class="location-pin">
                    ●
                </span>

                <span>
                    Northroad Orchid, Daro,<br>
                    Dumaguete City, Neg. Or.
                </span>

            </p>


            <a
                class="maps-link"
                href="https://www.google.com/maps/search/?api=1&query=Northroad%20Orchid%2C%20Daro%2C%20Dumaguete%20City%2C%20Negros%20Oriental"
                target="_blank"
                rel="noopener noreferrer">

                View on Google Maps →

            </a>


            <div class="map-frame">

                <iframe
                    src="https://www.google.com/maps?q=Northroad%20Orchid%2C%20Daro%2C%20Dumaguete%20City%2C%20Negros%20Oriental&output=embed"
                    title="Minguito Veterinary Clinic location map"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen>
                </iframe>

            </div>


            <div class="clinic-note">

                <strong>
                    Clinic Hours
                </strong>

                Monday – Saturday:
                8:00 AM – 6:00 PM

                <br>

                Sunday:
                9:00 AM – 1:00 PM

            </div>

        </aside>



        <!-- =====================================================
             APPOINTMENT FORM
        ====================================================== -->

        <main class="appointment-form">

            <p class="section-label">
                BOOK AN APPOINTMENT
            </p>


            <h1>
                Book an Appointment
            </h1>


            <p class="form-intro">
                Schedule a visit for your pet by completing the form below.
                Please choose your preferred service, date, and time.
            </p>


            <!-- ACCOUNT -->

            <div class="account-note">

                Booking as

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $customer['full_name'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </strong>

                ·

                <?php
                echo htmlspecialchars(
                    $customer['email'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>



            <!-- SUCCESS MESSAGE -->

            <?php if (isset($_GET['success'])): ?>

                <div class="success-message">

                    Your appointment request has been submitted successfully!

                    <div
                        style="
                            margin-top:8px;
                            font-size:12px;
                            font-weight:600;
                        ">

                        <a href="appointments.php">

                            View My Appointments →

                        </a>

                    </div>

                </div>

            <?php endif; ?>



            <!-- ERROR MESSAGE -->

            <?php if (isset($_GET['error'])): ?>

                <div
                    class="success-message"
                    style="
                        background:#fff1ee;
                        border-color:#efc4be;
                        color:#a13d32;
                    ">

                    <?php

                    $bookingError = [

                        'missing' =>
                            'Please complete all appointment fields.',

                        'service' =>
                            'Please select a valid veterinary service.',

                        'date' =>
                            'Please choose a valid appointment date that is today or later.',

                        'time' =>
                            'Please choose a valid appointment time.',

                        'database' =>
                            'We could not save your appointment right now. Please try again.',

                        'slot_taken' =>
                            'Sorry, this appointment date and time has already been booked. Please choose another date or time.'

                    ];

                    echo htmlspecialchars(

                        $bookingError[$_GET['error']]
                            ?? 'We could not submit your appointment.',

                        ENT_QUOTES,

                        'UTF-8'

                    );

                    ?>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 BOOKING FORM
            ================================================== -->

            <form
                action="actions/book_appointment.php"
                method="POST">


                <!-- OWNER + ANIMAL -->

                <div class="form-row">


                    <!-- OWNER -->

                    <div class="form-group">

                        <label for="owner_name">
                            Pet Owner Name
                        </label>

                        <div class="input-wrap">
<input
                                
                                id="owner_name"
                                type="text"
                                name="owner_name"
                                value="<?php
                                    echo htmlspecialchars(
                                        $customer['full_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                                autocomplete="name"
                                readonly
                                required>

                        </div>

                    </div>



                    <!-- ANIMAL TYPE -->

                    <div class="form-group">

                        <label for="pet_type">
                            Animal Type
                        </label>

                        <div class="input-wrap">
<select
                                id="pet_type"
                                name="pet_type"
                                required>

                                <option value="" disabled selected hidden>
                                    Select animal type
                                </option>

                                <option value="Dog">
                                    Dog
                                </option>

                                <option value="Cat">
                                    Cat
                                </option>

                            </select>

                        </div>

                    </div>


                </div>



                <!-- PET NAME -->

                <div class="form-group">

                    <label for="pet_name">
                        Pet Name
                    </label>

                    <div class="input-wrap">
<input
                            
                            id="pet_name"
                            type="text"
                            name="pet_name"
                            placeholder="Enter your pet's name"
                            required>

                    </div>

                </div>



                <!-- MULTIPLE VETERINARY SERVICES -->

                <div class="form-group service-selection-group">

                    <label>
                        Veterinary Services
                    </label>

                    <p class="service-help">
                        Select one or more services for your pet.
                    </p>

                    <div class="service-checkboxes">

                        <?php foreach ($services as $serviceOption): ?>

                            <?php
                            $serviceName =
                                trim((string)$serviceOption['name']);

                            $servicePrice =
                                (float)$serviceOption['price'];

                            $serviceChecked =
                                in_array(
                                    $serviceName,
                                    $selectedServices,
                                    true
                                );
                            ?>

                            <div class="service-option">

                                <input
                                    type="checkbox"
                                    id="service_<?= (int)$serviceOption['id'] ?>"
                                    name="services[]"
                                    value="<?= htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-price="<?= htmlspecialchars(number_format($servicePrice, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $serviceChecked ? 'checked' : '' ?>
                                >

                                <label for="service_<?= (int)$serviceOption['id'] ?>">

                                    <span>
                                        <span class="service-name">
                                            <?= htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') ?>
                                        </span>

                                        <span class="service-price">
                                            ₱<?= number_format($servicePrice, 2) ?>
                                        </span>
                                    </span>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <?php if (empty($services)): ?>

                        <div class="service-selection-error show">
                            No active veterinary services are currently available.
                            Please contact the clinic administrator.
                        </div>

                    <?php endif; ?>

                    <div class="service-summary" id="serviceSummary">

                        <strong id="serviceSummaryText">
                            No service selected
                        </strong>

                        <span
                            class="service-summary-total"
                            id="serviceSummaryTotal">
                            ₱0.00
                        </span>

                    </div>

                    <div
                        class="service-selection-error"
                        id="serviceSelectionError">
                        Please select at least one veterinary service.
                    </div>

                </div>

                <!-- DATE + TIME -->

                <div class="form-row">


                    <!-- DATE -->

                    <div class="form-group">

                        <label for="appointment_date">
                            Preferred Date
                        </label>

                        <div class="input-wrap">
<input
                                
                                id="appointment_date"
                                type="date"
                                name="appointment_date"
                                min="<?php
                                    echo htmlspecialchars(
                                        $today,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                                required>

                        </div>

                    </div>



                    <!-- TIME -->

                    <div class="form-group">

                        <label for="appointment_time">
                            Preferred Time
                        </label>

                        <div class="input-wrap">
<input
                                
                                id="appointment_time"
                                type="time"
                                name="appointment_time"
                                required>

                        </div>

                    </div>


                </div>



                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="appointment-submit">

                    BOOK APPOINTMENT

                </button>


                <p class="form-footnote">

                    Appointment requests are subject to clinic
                    confirmation and availability.

                </p>


            </form>

        </main>

    </div>

</section>



<!-- =========================================================
     CLINIC HOURS VALIDATION
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const dateInput =
        document.getElementById('appointment_date');

    const timeInput =
        document.getElementById('appointment_time');

    const form =
        timeInput
            ? timeInput.closest('form')
            : null;


    const clinicHours =
        <?= json_encode(
            $clinicHours,
            JSON_UNESCAPED_SLASHES
        ) ?>;

    const serviceCheckboxes = Array.from(
        document.querySelectorAll(
            '.service-selection-group input[name="services[]"]'
        )
    );

    const serviceSummaryText =
        document.getElementById('serviceSummaryText');

    const serviceSummaryTotal =
        document.getElementById('serviceSummaryTotal');

    const serviceSelectionError =
        document.getElementById('serviceSelectionError');

    function updateServiceSummary() {

        const selected =
            serviceCheckboxes.filter(
                checkbox => checkbox.checked
            );

        const names = selected.map(
            checkbox => {

                const label =
                    document.querySelector(
                        'label[for="' +
                        checkbox.id +
                        '"] .service-name'
                    );

                return label
                    ? label.textContent.trim()
                    : checkbox.value;
            }
        );

        const total = selected.reduce(
            (sum, checkbox) => {
                return sum +
                    parseFloat(
                        checkbox.dataset.price || '0'
                    );
            },
            0
        );

        if (serviceSummaryText) {

            if (names.length === 0) {
                serviceSummaryText.textContent =
                    'No service selected';
            } else if (names.length === 1) {
                serviceSummaryText.textContent =
                    'Selected: ' + names[0];
            } else {
                serviceSummaryText.textContent =
                    'Selected: ' +
                    names.length +
                    ' services';
            }
        }

        if (serviceSummaryTotal) {
            serviceSummaryTotal.textContent =
                '₱' +
                total.toLocaleString(
                    'en-PH',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );
        }

        if (serviceSelectionError) {
            serviceSelectionError.classList.toggle(
                'show',
                selected.length === 0
            );
        }
    }

    serviceCheckboxes.forEach(
        checkbox => {
            checkbox.addEventListener(
                'change',
                updateServiceSummary
            );
        }
    );

    updateServiceSummary();




    /* =====================================================
       UPDATE TIME LIMITS WHEN DATE CHANGES
    ====================================================== */

    function updateTimeRules() {

        if (!dateInput || !timeInput) {
            return;
        }


        /*
         * No date selected yet
         */

        if (!dateInput.value) {

            timeInput.min = '08:00';
            timeInput.max = '18:00';
            timeInput.step = '1800';

            return;
        }



        /*
         * Create date using noon to avoid
         * timezone-related day shifting.
         */

        const selected =
            new Date(
                dateInput.value + 'T12:00:00'
            );


        const day =
            selected.getDay();


        const hours =
            clinicHours[day];


        if (!hours) {
            return;
        }



        /*
         * Apply clinic opening/closing time
         */

        timeInput.min = hours[0];

        timeInput.max = hours[1];

        timeInput.step = '1800';



        /*
         * Clear an already-selected time
         * if it falls outside clinic hours.
         */

        if (
            timeInput.value &&
            (
                timeInput.value < hours[0] ||
                timeInput.value > hours[1]
            )
        ) {

            timeInput.value = '';

        }

    }



    /*
     * Run when date changes.
     */

    dateInput.addEventListener(
        'change',
        updateTimeRules
    );


    /*
     * Run once when page loads.
     */

    updateTimeRules();



    /* =====================================================
       FINAL FORM VALIDATION
    ====================================================== */

    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                const selectedServices =
                    serviceCheckboxes.filter(
                        checkbox => checkbox.checked
                    );

                if (selectedServices.length === 0) {

                    event.preventDefault();

                    if (serviceSelectionError) {
                        serviceSelectionError.classList.add('show');
                    }

                    if (serviceCheckboxes[0]) {
                        serviceCheckboxes[0].focus();
                    }

                    return;
                }

                if (
                    !dateInput.value ||
                    !timeInput.value
                ) {
                    return;
                }



                const selected =
                    new Date(
                        dateInput.value + 'T12:00:00'
                    );


                const hours =
                    clinicHours[selected.getDay()];



                /*
                 * Make sure the selected time is
                 * within the clinic's operating hours.
                 */

                if (
                    !hours ||
                    timeInput.value < hours[0] ||
                    timeInput.value > hours[1]
                ) {

                    event.preventDefault();


                    if (selected.getDay() === 0) {

                        timeInput.setCustomValidity(
                            'Sunday appointments are available only from 9:00 AM to 1:00 PM.'
                        );

                    } else {

                        timeInput.setCustomValidity(
                            'Appointments from Monday to Saturday are available only from 8:00 AM to 6:00 PM.'
                        );

                    }


                    timeInput.reportValidity();

                    timeInput.focus();

                    return;
                }



                /*
                 * Clear validation error.
                 */

                timeInput.setCustomValidity('');

            }
        );



        /*
         * Remove browser validation error
         * once user changes the time.
         */

        timeInput.addEventListener(
            'input',
            function () {

                timeInput.setCustomValidity('');

            }
        );

    }

});

</script>



<?php
include 'includes/footer.php';
?>