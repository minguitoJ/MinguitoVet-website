<?php
$pageTitle = "About Us";

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
/* =========================================================
   MINGUITO ABOUT PAGE — FIXED LAYOUT
   Scoped to this page so it will not affect other sections.
========================================================= */

.about-page-section {
    position: relative;
    overflow: hidden;
    min-height: 660px;
    padding: 80px 0 90px;
    background:
        radial-gradient(circle at 12% 18%, rgba(184,120,41,.07), transparent 27%),
        linear-gradient(135deg, #fbf3e6 0%, #f4e6d2 100%);
}

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
    border: 1px solid rgba(184,120,41,.10);
    border-radius: 50%;
    pointer-events: none;
}

.about-page-container {
    position: relative;
    z-index: 2;
    width: min(1180px, 92%);
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
    gap: 72px;
    align-items: center;
}

.about-page-image-wrap {
    position: relative;
    min-width: 0;
}

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

.about-page-image {
    position: relative;
    z-index: 1;
    display: block;
    width: 100%;
    height: 500px;
    object-fit: cover;
    object-position: center;
    border-radius: 30px;
    box-shadow: 0 24px 52px rgba(7,59,42,.15);
    transition: transform .4s ease, box-shadow .4s ease;
}

.about-page-image-wrap:hover .about-page-image {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px rgba(7,59,42,.18);
}

.about-page-content {
    max-width: 540px;
    min-width: 0;
    color: #073b2a;
}

.about-page-subtitle {
    margin: 0 0 16px;
    color: #b87829;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 800;
    letter-spacing: 1.6px;
}

.about-page-content h1 {
    margin: 0 0 25px;
    color: #073b2a;
    font-family: "Playfair Display", Georgia, serif;
    font-size: clamp(42px, 4.4vw, 60px);
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
    box-shadow: 0 10px 22px rgba(7,59,42,.13);
    transition: .25s ease;
}

.about-page-button:hover {
    background: #b87829;
    border-color: #b87829;
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 14px 26px rgba(184,120,41,.18);
}

/* Tablet */
@media (max-width: 1000px) {
    .about-page-section {
        min-height: 0;
        padding: 70px 0 80px;
    }

    .about-page-container {
        width: min(92%, 900px);
        grid-template-columns: 1fr 1fr;
        gap: 42px;
    }

    .about-page-image {
        height: 430px;
    }

    .about-page-content h1 {
        font-size: 44px;
    }
}

/* Mobile */
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
}

/* Small phones */
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
}
</style>


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

<?php include 'includes/footer.php'; ?>
