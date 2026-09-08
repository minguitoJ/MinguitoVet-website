<?php

$pageTitle = "Our Team";

include 'includes/header.php';
include 'includes/navbar.php';

?>

<style>

/* =========================================================
   MINGUITO TEAM PAGE
========================================================= */

.team-page {
    padding: 38px 0 90px;

    background:
        radial-gradient(
            circle at 8% 20%,
            rgba(184,120,41,.06),
            transparent 24%
        ),
        linear-gradient(
            135deg,
            #fbf3e6 0%,
            #f5e8d6 100%
        );
}


/* =========================================================
   TEAM GRID
========================================================= */

.team-page .team-large-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 32px;

    align-items: stretch;
}


/* =========================================================
   TEAM CARD
========================================================= */

.team-page .team-profile {
    display: flex;

    flex-direction: column;

    overflow: hidden;

    min-width: 0;

    border: 1px solid rgba(7,59,42,.10);

    border-radius: 28px;

    background: #073b2a;

    box-shadow:
        0 18px 42px rgba(7,59,42,.13);

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}


.team-page .team-profile:hover {
    transform: translateY(-6px);

    box-shadow:
        0 26px 52px rgba(7,59,42,.18);
}


/* =========================================================
   TEAM IMAGE
========================================================= */

.team-page .team-profile > img {
    display: block;

    width: 100%;

    aspect-ratio: 4 / 3;

    height: auto;

    object-fit: cover;

    object-position: center center;

    background: #e7ded0;
}


/* =========================================================
   TEAM INFORMATION
========================================================= */

.team-page .team-profile > div {
    display: flex;

    flex: 1;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    min-height: 205px;

    padding: 28px 28px 30px;

    text-align: center;
}


.team-page .team-profile h2 {
    margin: 0 0 8px;

    color: #fff;

    font-family:
        "DM Sans",
        sans-serif;

    font-size: 28px;

    line-height: 1.2;
}


.team-page .team-profile span {
    display: inline-block;

    margin-bottom: 14px;

    color: #d9a45b;

    font-size: 15px;

    font-weight: 800;
}


.team-page .team-profile p {
    max-width: 430px;

    margin: 0;

    color: rgba(255,255,255,.92);

    font-size: 15px;

    line-height: 1.7;
}


/* =========================================================
   WHY PET OWNERS CHOOSE US
========================================================= */

.why-team-section {
    padding: 72px 0 78px;

    background: #073b2a;

    color: #fff;
}


.why-team-heading {
    margin-bottom: 38px;

    text-align: center;
}


.why-team-heading .section-label {
    margin: 0 0 10px;

    color: #d9a45b;

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 2px;
}


.why-team-heading h2 {
    margin: 0;

    color: #fff;

    font-family:
        Georgia,
        "Times New Roman",
        serif;

    font-size: 38px;

    line-height: 1.2;
}


/* =========================================================
   WHY CHOOSE US GRID
========================================================= */

.why-team-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

    max-width: 1000px;

    margin: 0 auto;
}


/* =========================================================
   WHY CHOOSE US CARD
========================================================= */

.why-team-card {
    position: relative;

    overflow: hidden;

    min-height: 165px;

    padding: 22px 16px;

    border: 1px solid
        rgba(255,255,255,.20);

    border-radius: 16px;

    background:
        rgba(255,255,255,.035);

    text-align: center;

    transition:
        transform .25s ease,
        background .25s ease,
        border-color .25s ease;
}


/* Small gold accent */

.why-team-card::before {
    content: "";

    position: absolute;

    top: 0;

    left: 25%;

    width: 50%;

    height: 2px;

    background: #b87829;

    opacity: .65;
}


.why-team-card:hover {
    transform: translateY(-5px);

    background:
        rgba(255,255,255,.08);

    border-color:
        rgba(255,255,255,.35);
}


/* =========================================================
   WHY CHOOSE US ICON
========================================================= */

.why-team-icon {
    width: 42px;

    height: 42px;

    margin: 0 auto 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    border: 1px solid
        rgba(255,255,255,.30);

    border-radius: 50%;

    background:
        rgba(255,255,255,.05);

    font-size: 18px;
}


/* =========================================================
   WHY CHOOSE US TITLE
========================================================= */

.why-team-card h3 {
    margin: 0 0 9px;

    color: #fff;

    font-size: 14px;

    font-weight: 700;

    line-height: 1.3;
}


/* =========================================================
   WHY CHOOSE US DESCRIPTION
========================================================= */

.why-team-card p {
    max-width: 190px;

    margin: 0 auto;

    color:
        rgba(255,255,255,.80);

    font-size: 11px;

    line-height: 1.55;
}


/* =========================================================
   TEAM CLOSING / COMMITMENT
========================================================= */

.team-closing {
    position: relative;

    overflow: hidden;

    padding: 85px 20px 90px;

    /*
       Transparent decorative pattern
       placed behind the section content.
    */

    background:
        linear-gradient(
            rgba(251, 243, 230, 0.90),
            rgba(251, 243, 230, 0.90)
        ),
        url("assets/images/team-pattern.png");

    background-size: 620px auto;

    background-position: center;

    background-repeat: repeat;

    text-align: center;
}


/* =========================================================
   CLOSING CONTENT
========================================================= */

.team-closing-content {
    position: relative;

    z-index: 2;

    max-width: 800px;

    margin: 0 auto;
}


/* =========================================================
   CLOSING LABEL
========================================================= */

.team-closing .section-label {
    margin: 0 0 12px;

    color: #b87829;

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 2px;
}


/* =========================================================
   CLOSING HEADING
========================================================= */

.team-closing h2 {
    margin: 0 0 20px;

    color: #073b2a;

    font-family:
        Georgia,
        "Times New Roman",
        serif;

    font-size: 40px;

    line-height: 1.2;
}


/* =========================================================
   CLOSING DESCRIPTION
========================================================= */

.team-closing-description {
    max-width: 700px;

    margin: 0 auto;

    color: #4b625a;

    font-size: 16px;

    line-height: 1.8;
}


/* =========================================================
   GOLD DIVIDER
========================================================= */

.team-closing-divider {
    width: 70px;

    height: 2px;

    margin: 28px auto 20px;

    background: #b87829;
}


/* =========================================================
   FINAL TAGLINE
========================================================= */

.team-closing-tagline {
    margin: 0;

    color: #073b2a;

    font-size: 14px;

    font-weight: 700;
}


/* =========================================================
   BOOK APPOINTMENT BUTTON
========================================================= */

.team-closing-button {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    margin-top: 28px;

    padding: 14px 25px;

    border-radius: 10px;

    background: #b87829;

    color: #fff;

    font-size: 13px;

    font-weight: 700;

    text-decoration: none;

    box-shadow:
        0 8px 20px
        rgba(184,120,41,.20);

    transition:
        transform .25s ease,
        background .25s ease,
        box-shadow .25s ease;
}


.team-closing-button:hover {
    transform: translateY(-3px);

    background: #9f651f;

    color: #fff;

    box-shadow:
        0 12px 25px
        rgba(184,120,41,.28);
}


.team-closing-button span {
    font-size: 14px;
}


/* =========================================================
   FOOTER SPACING
========================================================= */

.team-closing + .site-footer {
    margin-top: 0;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 900px) {

    .team-page {
        padding: 34px 0 72px;
    }


    .team-page .team-large-grid {
        gap: 24px;
    }


    .team-page .team-profile h2 {
        font-size: 25px;
    }


    .team-page .team-profile > div {
        min-height: 190px;

        padding: 24px 22px 26px;
    }


    .why-team-grid {
        grid-template-columns:
            repeat(2, 1fr);

        max-width: 650px;
    }


    .team-closing h2 {
        font-size: 36px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .team-page {
        padding: 28px 0 60px;
    }


    .team-page .team-large-grid {
        grid-template-columns: 1fr;

        gap: 24px;

        width: 92%;

        margin: 0 auto;
    }


    .team-page .team-profile {
        border-radius: 22px;
    }


    .team-page .team-profile > img {
        aspect-ratio: 4 / 3;
    }


    .team-page .team-profile > div {
        min-height: 0;

        padding: 23px 22px 27px;
    }


    .team-page .team-profile h2 {
        font-size: 24px;
    }


    .team-page .team-profile p {
        font-size: 14px;
    }


    /* WHY CHOOSE US */

    .why-team-section {
        padding: 60px 20px 65px;
    }


    .why-team-heading h2 {
        font-size: 32px;
    }


    .why-team-grid {
        grid-template-columns: 1fr;

        max-width: 400px;
    }


    .why-team-card {
        min-height: auto;

        padding: 25px 20px;
    }


    /* COMMITMENT */

    .team-closing {
        padding: 65px 20px 70px;

        background-size: 450px auto;
    }


    .team-closing h2 {
        font-size: 31px;
    }


    .team-closing-description {
        font-size: 14px;
    }

}

</style>


<!-- =========================================================
     PAGE HERO
========================================================= -->

<section class="page-hero">

    <div class="container">

        <p class="section-label">
            OUR TEAM
        </p>

        <h1>
            Our Veterinary Team
        </h1>

        <p>
            Our veterinarians are committed to
            providing the best possible care
            for your pets.
        </p>

    </div>

</section>


<!-- =========================================================
     VETERINARY TEAM
========================================================= -->

<section class="team-page">

    <div class="container">

        <div class="team-large-grid">


            <!-- =================================================
                 DR. MARIA SANTOS
            ================================================== -->

            <article class="team-profile">

                <img
                    src="assets/images/services/team/maria-santos.png"
                    alt="Dr. Maria Santos"
                >

                <div>

                    <h2>
                        Dr. Maria Santos
                    </h2>

                    <span>
                        Veterinarian
                    </span>

                    <p>
                        Specializes in small animal
                        medicine and preventive care.
                    </p>

                </div>

            </article>


            <!-- =================================================
                 DR. MIGUEL REYES
            ================================================== -->

            <article class="team-profile">

                <img
                    src="assets/images/services/team/miguel-reyes.png"
                    alt="Dr. Miguel Reyes"
                >

                <div>

                    <h2>
                        Dr. Miguel Reyes
                    </h2>

                    <span>
                        Veterinarian
                    </span>

                    <p>
                        Specializes in surgery
                        and emergency medicine.
                    </p>

                </div>

            </article>


        </div>

    </div>

</section>


<!-- =========================================================
     WHY PET OWNERS CHOOSE US
========================================================= -->

<section class="why-team-section">

    <div class="container">

        <div class="why-team-heading">

            <p class="section-label">
                WHY CHOOSE US
            </p>

            <h2>
                Why Pet Owners Choose Us
            </h2>

        </div>


        <div class="why-team-grid">


            <!-- CARD 1 -->

            <div class="why-team-card">

                <div class="why-team-icon">
                    🩺
                </div>

                <h3>
                    Experienced Veterinarians
                </h3>

                <p>
                    Our team is highly trained and
                    passionate about animal care.
                </p>

            </div>


            <!-- CARD 2 -->

            <div class="why-team-card">

                <div class="why-team-icon">
                    ✚
                </div>

                <h3>
                    Modern Facilities
                </h3>

                <p>
                    We use advanced equipment for
                    accurate diagnosis and treatment.
                </p>

            </div>


            <!-- CARD 3 -->

            <div class="why-team-card">

                <div class="why-team-icon">
                    🐾
                </div>

                <h3>
                    Personalized Care
                </h3>

                <p>
                    We create tailored care plans for
                    the unique needs of every pet.
                </p>

            </div>


            <!-- CARD 4 -->

            <div class="why-team-card">

                <div class="why-team-icon">
                    🏠
                </div>

                <h3>
                    Pet-Friendly Environment
                </h3>

                <p>
                    A calm, clean, and welcoming space
                    for pets and their owners.
                </p>

            </div>


        </div>

    </div>

</section>


<!-- =========================================================
     TEAM CLOSING MESSAGE
========================================================= -->

<section class="team-closing">

    <div class="container">

        <div class="team-closing-content">

            <p class="section-label">
                OUR COMMITMENT
            </p>


            <h2>
                Caring for Pets, Supporting Families
            </h2>


            <p class="team-closing-description">

                At Minguito Veterinary Clinic, our team is
                dedicated to providing compassionate,
                professional, and personalized care for
                every pet. We work together with pet owners
                to help their beloved companions live
                healthier, happier lives.

            </p>


            <div class="team-closing-divider"></div>


            <p class="team-closing-tagline">

                Your pet's health and happiness are always
                at the heart of what we do.

            </p>


            <a
                href="appointment.php"
                class="team-closing-button"
            >

                <span></span>

                Book an Appointment

            </a>


        </div>

    </div>

</section>


<?php

include 'includes/footer.php';

?>