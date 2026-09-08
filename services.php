<?php

$pageTitle = "Services";

include 'includes/header.php';
include 'includes/navbar.php';

?>


<section class="page-hero">

    <div class="container">

        <p class="section-label">
            OUR SERVICES
        </p>

        <h1>
            Complete Care
            <span>for Every Stage of Their Life</span>
        </h1>

    </div>

</section>


<section class="services-page">

    <div class="container service-grid">


        <!-- GENERAL CHECKUP -->

        <div class="service-card">

            <h3>General Checkup</h3>

            <img
                src="assets/images/services/checkup.jpg"
                alt="Veterinarian performing a general checkup">

            <p>
                Regular examinations to monitor
                your pet's overall health.
            </p>

            <a
                href="appointment.php?service=General%20Checkup"
                aria-label="Book General Checkup">
                Book This Service →
            </a>

        </div>


        <!-- VACCINATION -->

        <div class="service-card">

            <h3>Vaccination</h3>

            <img
                src="assets/images/services/vaccination.jpg"
                alt="Veterinarian providing pet vaccination">

            <p>
                Regular examinations and
                preventive protection for pets.
            </p>

            <a
                href="appointment.php?service=Vaccination"
                aria-label="Book Vaccination">
                Book This Service →
            </a>

        </div>


        <!-- PREVENTIVE CARE -->

        <div class="service-card">

            <h3>Preventive Care</h3>

            <img
                src="assets/images/services/preventive-care.jpg"
                alt="Veterinarian providing preventive care">

            <p>
                Keep your pet healthy with
                regular health checks.
            </p>

            <a
                href="appointment.php?service=Preventive%20Care"
                aria-label="Book Preventive Care">
                Book This Service →
            </a>

        </div>


        <!-- DENTAL CARE -->

        <div class="service-card">

            <h3>Dental Care</h3>

            <img
                src="assets/images/services/dental-care.jpg"
                alt="Veterinarian providing dental care">

            <p>
                Support your pet's oral health
                with proper dental care.
            </p>

            <a
                href="appointment.php?service=Dental%20Care"
                aria-label="Book Dental Care">
                Book This Service →
            </a>

        </div>


        <!-- SURGERY & TREATMENT -->

        <div class="service-card">

            <h3>Surgery &amp; Treatment</h3>

            <img
                src="assets/images/services/surgery-treatment.png"
                alt="Veterinary surgery and treatment">

            <p>
                Professional surgical procedures
                and veterinary treatment.
            </p>

            <a
                href="appointment.php?service=Surgery%20%26%20Treatment"
                aria-label="Book Surgery and Treatment">
                Book This Service →
            </a>

        </div>


        <!-- LABORATORY & DIAGNOSTICS -->

        <div class="service-card">

            <h3>Laboratory &amp; Diagnostics</h3>

            <img
                src="assets/images/services/laboratory-diagnostics.jpg"
                alt="Veterinary laboratory and diagnostic services">

            <p>
                Diagnostic services to help
                identify your pet's health needs.
            </p>

            <a
                href="appointment.php?service=Laboratory%20%26%20Diagnostics"
                aria-label="Book Laboratory and Diagnostics">
                Book This Service →
            </a>

        </div>


        <!-- GROOMING & WELLNESS -->

        <div class="service-card">

            <h3>Grooming &amp; Wellness</h3>

            <img
                src="assets/images/services/grooming-wellness.png"
                alt="Pet grooming and wellness service">

            <p>
                Wellness and grooming services
                for your pet.
            </p>

            <a
                href="appointment.php?service=Grooming%20%26%20Wellness"
                aria-label="Book Grooming and Wellness">
                Book This Service →
            </a>

        </div>


    </div>

</section>


<?php

include 'includes/footer.php';

?>