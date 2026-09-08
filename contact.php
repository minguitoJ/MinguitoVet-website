<?php

$pageTitle = "Contact";

include 'includes/header.php';
include 'includes/navbar.php';

?>

<section class="page-hero">

    <div class="container">

        <p class="section-label">
            CONTACT US
        </p>

        <h1>
            We're Here to
            <span>Help Your Pet</span>
        </h1>

    </div>

</section>


<section class="contact-section">

    <div class="container contact-grid">


        <!-- CONTACT INFORMATION -->

        <div class="contact-info">

            <h2>
                Get in Touch
            </h2>

            <p>
                Have questions about our services?
                Contact Minguito Veterinary Clinic.
            </p>


            <div class="contact-item">

                <strong>
                    ☎ Phone
                </strong>

                <p>
                    (035) 522 1234
                </p>

            </div>


            <div class="contact-item">

                <strong>
                    📍 Location
                </strong>

                <p>
                    Northroad Orchid, Daro,<br>
                    Dumaguete City, Neg. Or.
                </p>

            </div>


            <div class="contact-item">

                <strong>
                    ✉ Email
                </strong>

                <p>
                    minguitoveterinaryclinic@gmail.com
                </p>

            </div>


            <div class="contact-item">

                <strong>
                    🕐 Clinic Hours
                </strong>

                <p>
                    Monday – Saturday:
                    8:00 AM – 6:00 PM

                    <br><br>

                    Sunday:
                    9:00 AM – 1:00 PM
                </p>

            </div>

        </div>


        <!-- GOOGLE MAP -->

        <div class="contact-map">

            <div class="contact-map-header">

                <span class="contact-map-label">
                    FIND US
                </span>

                <h3>
                    Visit Our Clinic
                </h3>

            </div>


            <div class="map-frame">

                <iframe
                    src="https://www.google.com/maps?q=Northroad%20Orchid%2C%20Daro%2C%20Dumaguete%20City%2C%20Negros%20Oriental&output=embed"
                    title="Minguito Veterinary Clinic location map"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen>
                </iframe>

            </div>

        </div>


        <!-- CONTACT FORM -->

        <div class="contact-form">

            <h2>
                Send Us a Message
            </h2>

            <form
                action="actions/contact_form.php"
                method="POST">

                <input
                    type="text"
                    name="name"
                    placeholder="Your Name"
                    autocomplete="name"
                    required>


                <input
                    type="email"
                    name="email"
                    placeholder="Your Email"
                    autocomplete="email"
                    required>


                <input
                    type="text"
                    name="subject"
                    placeholder="Subject"
                    required>


                <textarea
                    name="message"
                    rows="6"
                    placeholder="Your Message"
                    required></textarea>


                <button
                    type="submit"
                    class="btn btn-primary">

                    SEND MESSAGE

                </button>

            </form>

        </div>


    </div>

</section>


<?php

include 'includes/footer.php';

?>