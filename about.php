<?php
$pageTitle = "About Us";

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>

/* =========================================================
   MINGUITO ABOUT PAGE
   EXISTING CONTENT + ENHANCED DESIGN
========================================================= */


/* =========================================================
   MAIN ABOUT SECTION
========================================================= */

.about-page-section {
    position: relative;
    overflow: hidden;
    min-height: 660px;
    padding: 80px 0 90px;

    background:
        radial-gradient(
            circle at 12% 18%,
            rgba(184,120,41,.08),
            transparent 27%
        ),
        radial-gradient(
            circle at 88% 80%,
            rgba(7,59,42,.06),
            transparent 25%
        ),
        linear-gradient(
            135deg,
            #fbf3e6 0%,
            #f4e6d2 100%
        );
}


/* Decorative circles */

.about-page-section::before {
    content: "";
    position: absolute;

    width: 420px;
    height: 420px;

    top: -245px;
    right: -170px;

    border: 1px solid rgba(7,59,42,.08);
    border-radius: 50%;

    pointer-events: none;
}

.about-page-section::after {
    content: "";
    position: absolute;

    width: 260px;
    height: 260px;

    left: -175px;
    bottom: -165px;

    border: 1px solid rgba(184,120,41,.12);
    border-radius: 50%;

    pointer-events: none;
}


.about-page-container {
    position: relative;
    z-index: 2;

    width: min(1180px, 92%);
    margin: 0 auto;

    display: grid;
    grid-template-columns:
        minmax(0, 1.05fr)
        minmax(0, .95fr);

    gap: 72px;
    align-items: center;
}


/* =========================================================
   IMAGE
========================================================= */

.about-page-image-wrap {
    position: relative;
    min-width: 0;
}


/* Gold offset decoration */

.about-page-image-wrap::before {
    content: "";

    position: absolute;

    left: -18px;
    bottom: -18px;

    width: 82%;
    height: 82%;

    border-radius: 30px;

    background: rgba(184,120,41,.11);

    z-index: 0;
}


/* Extra frame */

.about-page-image-wrap::after {
    content: "";

    position: absolute;

    top: -14px;
    right: -14px;

    width: 90px;
    height: 90px;

    border-top: 2px solid rgba(184,120,41,.55);
    border-right: 2px solid rgba(184,120,41,.55);

    border-radius: 0 22px 0 0;

    z-index: 0;
}


.about-page-image {
    position: relative;
    z-index: 1;

    display: block;

    width: 100%;
    height: 500px;

    object-fit: cover;
    object-position: center;

    border-radius: 30px;

    box-shadow:
        0 24px 52px rgba(7,59,42,.15);

    transition:
        transform .4s ease,
        box-shadow .4s ease;
}


.about-page-image-wrap:hover .about-page-image {
    transform: translateY(-5px);

    box-shadow:
        0 30px 60px rgba(7,59,42,.18);
}


/* =========================================================
   ABOUT TEXT
========================================================= */

.about-page-content {
    max-width: 540px;
    min-width: 0;

    color: #073b2a;
}


.about-page-subtitle {
    position: relative;

    margin: 0 0 16px;

    color: #b87829;

    font-size: 13px;
    line-height: 1.4;

    font-weight: 800;
    letter-spacing: 1.6px;
}


/* Small decorative line */

.about-page-subtitle::after {
    content: "";

    display: inline-block;

    width: 42px;
    height: 2px;

    margin-left: 12px;

    vertical-align: middle;

    background: #b87829;
    border-radius: 10px;
}


.about-page-content h1 {
    margin: 0 0 25px;

    color: #073b2a;

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size:
        clamp(42px, 4.4vw, 60px);

    line-height: 1.03;

    letter-spacing: -.8px;
}


.about-page-content h1 span {
    display: block;

    color: #b87829;
}


.about-page-copy {
    margin: 0 0 16px;

    max-width: 520px;

    color: #21483a;

    font-size: 15px;
    line-height: 1.85;
}


/* =========================================================
   LEARN MORE BUTTON
========================================================= */

.about-page-button {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    min-height: 50px;

    margin-top: 17px;

    padding: 0 27px;

    border: 2px solid #073b2a;
    border-radius: 10px;

    background: #073b2a;

    color: #fff;

    text-decoration: none;

    font-size: 14px;
    font-weight: 800;

    box-shadow:
        0 10px 22px rgba(7,59,42,.13);

    transition: .25s ease;
}


.about-page-button:hover {
    background: #b87829;
    border-color: #b87829;

    color: #fff;

    transform: translateY(-3px);

    box-shadow:
        0 14px 26px rgba(184,120,41,.18);
}


/* =========================================================
   OUR STORY SECTION
========================================================= */

.about-story-section {
    position: relative;

    padding: 100px 0;

    overflow: hidden;

    background: #fffaf2;
}


/* Decorative background */

.about-story-section::before {
    content: "";

    position: absolute;

    width: 360px;
    height: 360px;

    left: -180px;
    top: 40px;

    border: 1px solid rgba(184,120,41,.12);

    border-radius: 50%;
}


.about-story-section::after {
    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    right: -100px;
    bottom: -100px;

    border: 1px solid rgba(7,59,42,.08);

    border-radius: 50%;
}


.about-story-container {
    position: relative;

    z-index: 2;

    width: min(1050px, 90%);

    margin: 0 auto;
}


/* =========================================================
   SECTION HEADINGS
========================================================= */

.about-section-heading {
    max-width: 760px;

    margin: 0 auto 55px;

    text-align: center;
}


.about-section-label {
    margin: 0 0 12px;

    color: #b87829;

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 2px;
}


.about-section-heading h2 {
    margin: 0 0 18px;

    color: #073b2a;

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size:
        clamp(34px, 4vw, 48px);

    line-height: 1.15;
}


.about-section-heading p {
    margin: 0;

    color: #4a6258;

    font-size: 15px;

    line-height: 1.8;
}


/* =========================================================
   STORY BOX
========================================================= */

.about-story-box {
    position: relative;

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 0;

    overflow: hidden;

    border-radius: 26px;

    background: #073b2a;

    box-shadow:
        0 22px 50px rgba(7,59,42,.14);
}


/* Decorative top line */

.about-story-box::before {
    content: "";

    position: absolute;

    top: 0;
    left: 50%;

    width: 90px;
    height: 3px;

    transform: translateX(-50%);

    background: #d9a45b;

    z-index: 3;
}


.about-story-side {
    position: relative;

    padding: 48px 42px;
}


.about-story-side:first-child {
    background: #073b2a;
}


.about-story-side:last-child {
    background: #0b4935;

    border-left:
        1px solid rgba(255,255,255,.10);
}


.about-story-side h3 {
    margin: 0 0 16px;

    color: #d9a45b;

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size: 27px;
}


.about-story-side p {
    margin: 0;

    color: rgba(255,255,255,.88);

    font-size: 14px;

    line-height: 1.85;
}


/* =========================================================
   MISSION & VISION
========================================================= */

.about-purpose-section {
    position: relative;

    padding: 100px 0;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #f5e8d6 0%,
            #fbf3e6 100%
        );
}


.about-purpose-section::before {
    content: "";

    position: absolute;

    width: 500px;
    height: 500px;

    right: -250px;
    top: -220px;

    border: 1px solid rgba(7,59,42,.07);

    border-radius: 50%;
}


.about-purpose-container {
    position: relative;

    z-index: 2;

    width: min(1050px, 90%);

    margin: 0 auto;
}


.about-purpose-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 28px;
}


/* =========================================================
   PURPOSE CARDS
========================================================= */

.about-purpose-card {
    position: relative;

    padding: 42px 38px;

    border:
        1px solid rgba(7,59,42,.10);

    border-radius: 24px;

    background:
        rgba(255,255,255,.70);

    box-shadow:
        0 16px 38px rgba(7,59,42,.07);

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}


.about-purpose-card:hover {
    transform: translateY(-7px);

    box-shadow:
        0 24px 48px rgba(7,59,42,.12);
}


/* Gold accent */

.about-purpose-card::before {
    content: "";

    position: absolute;

    top: 0;
    left: 38px;

    width: 60px;
    height: 4px;

    background: #b87829;

    border-radius:
        0 0 5px 5px;
}


/* Number */

.about-purpose-number {
    display: flex;

    align-items: center;
    justify-content: center;

    width: 48px;
    height: 48px;

    margin-bottom: 20px;

    border-radius: 50%;

    background: #073b2a;

    color: #d9a45b;

    font-size: 15px;

    font-weight: 800;

    box-shadow:
        0 8px 18px rgba(7,59,42,.12);
}


.about-purpose-card h3 {
    margin: 0 0 13px;

    color: #073b2a;

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size: 29px;
}


.about-purpose-card p {
    margin: 0;

    color: #4a6258;

    font-size: 14px;

    line-height: 1.85;
}


/* =========================================================
   FINAL CLOSING SECTION
========================================================= */

.about-final-section {
    position: relative;

    overflow: hidden;

    padding: 100px 20px 105px;

    background:
        linear-gradient(
            135deg,
            #073b2a,
            #0b4935
        );

    text-align: center;
}


/* Decorative circles */

.about-final-section::before {
    content: "";

    position: absolute;

    width: 420px;
    height: 420px;

    top: -280px;
    left: -150px;

    border:
        1px solid rgba(255,255,255,.08);

    border-radius: 50%;
}


.about-final-section::after {
    content: "";

    position: absolute;

    width: 330px;
    height: 330px;

    right: -150px;
    bottom: -230px;

    border:
        1px solid rgba(217,164,91,.14);

    border-radius: 50%;
}


.about-final-content {
    position: relative;

    z-index: 2;

    width: min(780px, 90%);

    margin: 0 auto;
}


.about-final-label {
    margin: 0 0 14px;

    color: #d9a45b;

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 2px;
}


.about-final-content h2 {
    margin: 0 0 22px;

    color: #fff;

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size:
        clamp(36px, 4vw, 50px);

    line-height: 1.15;
}


.about-final-content p {
    margin: 0;

    color: rgba(255,255,255,.82);

    font-size: 15px;

    line-height: 1.9;
}


.about-final-line {
    width: 70px;
    height: 2px;

    margin: 30px auto 24px;

    background: #d9a45b;

    border-radius: 10px;
}


.about-final-tagline {
    color: #fff !important;

    font-weight: 700;

    letter-spacing: .2px;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1000px) {

    .about-page-section {
        min-height: 0;

        padding: 70px 0 80px;
    }

    .about-page-container {
        width: min(92%, 900px);

        grid-template-columns:
            1fr 1fr;

        gap: 42px;
    }

    .about-page-image {
        height: 430px;
    }

    .about-page-content h1 {
        font-size: 44px;
    }

    .about-story-section,
    .about-purpose-section {
        padding: 80px 0;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .about-page-section {
        padding: 58px 0 72px;
    }

    .about-page-container {
        width: 90%;

        grid-template-columns: 1fr;

        gap: 44px;
    }

    .about-page-image {
        height: 360px;

        border-radius: 24px;
    }

    .about-page-content {
        max-width: 100%;
    }

    .about-page-content h1 {
        font-size: 42px;
    }

    .about-page-copy {
        max-width: 100%;

        font-size: 14px;
    }


    /* STORY */

    .about-story-section {
        padding: 70px 0;
    }

    .about-section-heading {
        margin-bottom: 40px;
    }

    .about-section-heading h2 {
        font-size: 36px;
    }

    .about-story-box {
        grid-template-columns: 1fr;

        border-radius: 22px;
    }

    .about-story-side {
        padding: 36px 28px;
    }

    .about-story-side:last-child {
        border-left: 0;

        border-top:
            1px solid rgba(255,255,255,.10);
    }


    /* PURPOSE */

    .about-purpose-section {
        padding: 70px 0;
    }

    .about-purpose-grid {
        grid-template-columns: 1fr;
    }

    .about-purpose-card {
        padding: 36px 28px;
    }


    /* FINAL */

    .about-final-section {
        padding: 75px 20px 80px;
    }

}


/* =========================================================
   SMALL PHONES
========================================================= */

@media (max-width: 480px) {

    .about-page-section {
        padding: 46px 0 60px;
    }

    .about-page-container {
        width: 90%;

        gap: 34px;
    }

    .about-page-image {
        height: 290px;

        border-radius: 20px;
    }

    .about-page-image-wrap::before {
        left: -10px;
        bottom: -10px;

        border-radius: 22px;
    }

    .about-page-image-wrap::after {
        top: -9px;
        right: -9px;

        width: 65px;
        height: 65px;
    }

    .about-page-subtitle {
        font-size: 11px;

        letter-spacing: 1.2px;
    }

    .about-page-content h1 {
        font-size: 34px;
    }

    .about-page-copy {
        font-size: 13px;

        line-height: 1.7;
    }

    .about-page-button {
        width: 100%;
    }


    .about-section-heading h2 {
        font-size: 31px;
    }

    .about-section-heading p {
        font-size: 13px;
    }


    .about-story-side {
        padding: 30px 24px;
    }

    .about-story-side h3 {
        font-size: 24px;
    }

    .about-story-side p {
        font-size: 13px;
    }


    .about-purpose-card {
        padding: 32px 24px;
    }

    .about-purpose-card h3 {
        font-size: 25px;
    }

    .about-purpose-card p {
        font-size: 13px;
    }


    .about-final-content h2 {
        font-size: 31px;
    }

    .about-final-content p {
        font-size: 13px;
    }

}

</style>


<!-- =========================================================
     EXISTING ABOUT SECTION
========================================================= -->

<section class="about-page-section">

    <div class="about-page-container">

        <!-- CLINIC IMAGE -->
        <div class="about-page-image-wrap">

            <img
                src="assets/images/about-building.png"
                alt="Minguito Veterinary Clinic building"
                class="about-page-image"
            >

        </div>


        <!-- ABOUT CONTENT -->
        <div class="about-page-content">

            <p class="about-page-subtitle">
                ABOUT MINGUITO VETERINARY CLINIC
            </p>

            <h1>
                Caring for Your
                <span>Furry Family</span>
            </h1>

            <p class="about-page-copy">
                At Minguito Veterinary Clinic, we are committed to providing
                compassionate and quality veterinary care for your beloved pets.
            </p>

            <p class="about-page-copy">
                Our goal is to keep every pet healthy, happy, and comfortable
                through professional care and personalized attention.
            </p>

            <a href="contact.php" class="about-page-button">
                Learn More
            </a>

        </div>

    </div>

</section>


<!-- =========================================================
     OUR STORY
========================================================= -->

<section class="about-story-section">

    <div class="about-story-container">

        <div class="about-section-heading">

            <p class="about-section-label">
                OUR STORY
            </p>

            <h2>
                A Clinic Built Around the Human–Pet Bond
            </h2>

            <p>
                Minguito Veterinary Clinic was created with a simple purpose:
                to provide a welcoming place where pet owners can find
                dependable veterinary care while their companions are treated
                with patience and respect.
            </p>

        </div>


        <div class="about-story-box">

            <div class="about-story-side">

                <h3>
                    More Than a Clinic
                </h3>

                <p>
                    We understand that pets are an important part of the
                    family. Every visit is an opportunity to help owners
                    better understand their pets and make informed decisions
                    about their well-being.
                </p>

            </div>


            <div class="about-story-side">

                <h3>
                    A Welcoming Place
                </h3>

                <p>
                    From routine visits to times when pets need extra
                    attention, our goal is to create an environment where
                    both pets and their owners can feel comfortable,
                    respected, and supported.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     MISSION & VISION
========================================================= -->

<section class="about-purpose-section">

    <div class="about-purpose-container">

        <div class="about-section-heading">

            <p class="about-section-label">
                OUR PURPOSE
            </p>

            <h2>
                What Guides Minguito
            </h2>

            <p>
                Our purpose is reflected in the principles that guide how
                we serve pets, owners, and the community.
            </p>

        </div>


        <div class="about-purpose-grid">

            <!-- MISSION -->

            <article class="about-purpose-card">

                <div class="about-purpose-number">
                    01
                </div>

                <h3>
                    Our Mission
                </h3>

                <p>
                    To provide responsible and compassionate veterinary care
                    while helping pet owners make informed choices for the
                    health and well-being of their companions.
                </p>

            </article>


            <!-- VISION -->

            <article class="about-purpose-card">

                <div class="about-purpose-number">
                    02
                </div>

                <h3>
                    Our Vision
                </h3>

                <p>
                    To be a trusted veterinary clinic known for creating
                    positive experiences for pets and owners while becoming
                    a valued part of the community we serve.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =========================================================
     FINAL CLOSING
========================================================= -->

<section class="about-final-section">

    <div class="about-final-content">

        <p class="about-final-label">
            OUR COMMITMENT
        </p>

        <h2>
            Where Every Pet Matters
        </h2>

        <p>
            At Minguito Veterinary Clinic, we believe caring for a pet
            means caring for a member of the family. We strive to make
            every interaction thoughtful, every visit welcoming, and
            every pet feel valued.
        </p>

        <div class="about-final-line"></div>

        <p class="about-final-tagline">
            Caring for pets. Supporting the families who love them.
        </p>

    </div>

</section>


<?php include 'includes/footer.php'; ?>