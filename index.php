<?php

$pageTitle = "Home";

include 'includes/header.php';
include 'includes/navbar.php';

?>

<!-- =========================================================
     HERO SECTION
========================================================= -->

<section class="hero">
    <div class="hero-background">
        <?php
        $heroImages = [
            'assets/images/hero1.png',
            'assets/images/hero2.png',
            'assets/images/hero3.png',
            'assets/images/hero4.png'
        ];

        foreach ($heroImages as $index => $image) {
            if (file_exists(__DIR__ . '/' . $image)) {
                ?>
                <img
                    class="hero-slide<?php echo $index === 0 ? ' active' : ''; ?>"
                    src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Minguito Veterinary Clinic"
                >
                <?php
            }
        }
        ?>
    </div>

    <div class="container hero-content">
        <div class="hero-text">
            <p class="eyebrow">
                COMPASSION <span>•</span> CARE <span>•</span> TRUST
            </p>

            <h1>
                Compassionate Care
                <span>for Your Best Friend</span>
            </h1>

            <p class="hero-description">
                At Minguito Veterinary Clinic, we provide professional
                and compassionate veterinary care to help your pets live
                healthy, happy, and comfortable lives.
            </p>

            <div class="hero-buttons">
                <a href="appointment.php" class="btn btn-primary">
                    BOOK APPOINTMENT
                </a>

                <a href="services.php" class="btn btn-outline">
                    OUR SERVICES
                    <span class="btn-arrow">→</span>
                </a>
            </div>
        </div>

        <div class="slider-dots" aria-label="Hero image navigation">
            <?php
            $slideCount = 0;
            foreach ($heroImages as $image) {
                if (file_exists(__DIR__ . '/' . $image)) {
                    ?>
                    <button
                        type="button"
                        class="slider-dot<?php echo $slideCount === 0 ? ' active' : ''; ?>"
                        data-slide="<?php echo $slideCount; ?>"
                        aria-label="Go to slide <?php echo $slideCount + 1; ?>">
                    </button>
                    <?php
                    $slideCount++;
                }
            }
            ?>
        </div>
    </div>
</section>

<style>
/* =========================================================
   HOMEPAGE HERO SLIDER FIX
   ========================================================= */

.hero {
    position: relative;
    overflow: hidden;
}

.hero-background {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    overflow: hidden !important;
}

.hero-background::after {
    content: "";
    position: absolute;
    inset: 0;
        z-index: 2;
    pointer-events: none;
}

.hero-background .hero-slide {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transform: scale(1.02);
    transition:
        opacity 0.9s ease,
        transform 6s ease;
    z-index: 1;
}

.hero-background .hero-slide.active {
    opacity: 1 !important;
    visibility: visible !important;
    transform: scale(1) !important;
}

.hero-content {
    position: relative;
    z-index: 5;
}

.hero-text {
    position: relative;
    z-index: 6;
}

.hero-buttons {
    margin-top: 34px !important;
}

.hero-buttons .btn-icon,
.hero-trust {
    display: none !important;
}

.slider-dots {
    position: absolute !important;
    left: 50% !important;
    bottom: 24px !important;
    transform: translateX(-50%) !important;
    z-index: 10 !important;
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: 9px;
}

.slider-dot {
    width: 10px !important;
    height: 10px !important;
    min-width: 10px;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.55) !important;
    cursor: pointer;
    transition: all 0.25s ease;
}

.slider-dot:hover {
    background: #ffffff !important;
    transform: scale(1.15);
}

.slider-dot.active {
    width: 28px !important;
    border-radius: 10px !important;
    background: #d8b45a !important;
}

/* =========================================================
   HOMEPAGE SERVICES SECTION FIX
   ========================================================= */

.services-section {
    position: relative;
    padding: 95px 0 105px;
    overflow: hidden;
}

.services-section .section-heading {
    max-width: 850px;
    margin: 0 auto 50px;
    text-align: center;
    padding: 0 20px;
}

.services-section .section-heading h2 {
    margin-bottom: 0;
}

.service-grid {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 28px !important;
    align-items: stretch !important;
}

.service-card {
    display: flex !important;
    flex-direction: column !important;
    min-width: 0;
    min-height: 465px;
    padding: 24px !important;
    background: #073b2a !important;
    border-radius: 22px !important;
    overflow: hidden;
    box-shadow: 0 12px 35px rgba(25, 67, 45, 0.10) !important;
    border: 1px solid rgba(35, 91, 61, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.service-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 18px 42px rgba(25, 67, 45, 0.16) !important;
}

.service-card h3 {
    margin: 0 0 18px !important;
    min-height: 30px;
    color: #ffff;
    font-size: 1.22rem;
}

.service-card img {
    display: block !important;
    width: 100% !important;
    height: 205px !important;
    min-height: 205px !important;
    object-fit: cover !important;
    border-radius: 15px !important;
    margin: 0 0 18px !important;
}

.service-card p {
    flex: 1 !important;
    margin: 0 0 20px !important;
    color: #f8efe2;
    line-height: 1.65;
}

.service-card > a {
    display: inline-flex !important;
    align-items: center;
    width: fit-content;
    margin-top: auto !important;
    color: #b08a35 !important;
    font-weight: 700;
    text-decoration: none;
    transition: gap 0.25s ease, color 0.25s ease;
}

.service-card > a:hover {
    color: #1e5137 !important;
}

/* Keep homepage service cards clean on tablets/phones */
@media (max-width: 1050px) {
    .service-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 700px) {
    .services-section {
        padding: 75px 0 85px;
    }

    .service-grid {
        grid-template-columns: 1fr !important;
        gap: 22px !important;
    }

    .service-card {
        min-height: 0;
    }

    .service-card img {
        height: 220px !important;
        min-height: 220px !important;
    }

    .slider-dots {
        bottom: 16px !important;
    }

    .hero-buttons {
        margin-top: 28px !important;
    }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const slides = document.querySelectorAll(".hero-background .hero-slide");
    const dots = document.querySelectorAll(".slider-dot");

    if (!slides.length) {
        return;
    }

    let currentSlide = 0;
    let sliderTimer = null;

    function showSlide(index) {
        if (index < 0) {
            index = slides.length - 1;
        }

        if (index >= slides.length) {
            index = 0;
        }

        slides.forEach(function (slide, i) {
            slide.classList.toggle("active", i === index);
        });

        dots.forEach(function (dot, i) {
            dot.classList.toggle("active", i === index);
        });

        currentSlide = index;
    }

    function startSlider() {
        if (slides.length <= 1) {
            return;
        }

        clearInterval(sliderTimer);

        sliderTimer = setInterval(function () {
            showSlide(currentSlide + 1);
        }, 5000);
    }

    dots.forEach(function (dot, index) {
        dot.addEventListener("click", function () {
            showSlide(index);
            startSlider();
        });
    });

    showSlide(0);
    startSlider();
});
</script>

<!-- =========================================================
     ABOUT PREVIEW
========================================================= -->

<section class="about-section">

    <!-- Decorative green background shapes -->
    <div class="about-shape-right"></div>
    <div class="about-shape-bottom"></div>

    <div class="container about-container">

        <!-- ABOUT IMAGE -->
        <div class="about-image">

            <img
                src="assets/images/about-building.png"
                alt="Minguito Veterinary Clinic building">

        </div>


        <!-- ABOUT CONTENT -->
        <div class="about-content">

            <p class="section-label">
                ABOUT OUR CLINIC
            </p>

            <h2>
                Dedicated to Your Pet's
                <span>Health and Happiness</span>
            </h2>

            <p>
                Minguito Veterinary Clinic is dedicated to providing
                compassionate, quality care for every pet. Our team
                creates a safe and welcoming environment where pets
                feel comfortable and owners feel confident.
            </p>

            <p>
                We're committed to supporting their health and
                happiness at every stage of life.
            </p>

            <a
                href="about.php"
                class="btn btn-primary">

                Learn More About Us

                <span class="btn-arrow">→</span>

            </a>

        </div>

    </div>

</section>



<!-- =========================================================
     SERVICES
========================================================= -->

<section class="services-section">

    <div class="section-heading">

        <p class="section-label">
            OUR SERVICES
        </p>


        <h2>
            Complete Care
            <span>for Every Stage of Their Life</span>
        </h2>

    </div>


    <div class="container service-grid">


        <!-- SERVICE 1 -->

        <div class="service-card">

            <h3>
                General Checkup
            </h3>

            <img
                src="assets/images/services/checkup.jpg"
                alt="General checkup">

            <p>
                Regular examinations to
                monitor your pet's overall
                health.
            </p>

            <a href="services.php">
                Learn More →
            </a>

        </div>



        <!-- SERVICE 2 -->

        <div class="service-card">

            <h3>
                Vaccination
            </h3>

            <img
                src="assets/images/services/vaccination.jpg"
                alt="Pet vaccination">

            <p>
                Regular examinations to
                monitor your pet's overall
                health.
            </p>

            <a href="services.php">
                Learn More →
            </a>

        </div>



        <!-- SERVICE 3 -->

        <div class="service-card">

            <h3>
                Preventive Care
            </h3>

            <img
                src="assets/images/services/preventive-care.jpg"
                alt="Preventive pet care">

            <p>
                Keep your pet healthy
                with regular health checks
                and preventive services.
            </p>

            <a href="services.php">
                Learn More →
            </a>

        </div>



        <!-- SERVICE 4 -->

        <div class="service-card">

            <h3>
                Dental Care
            </h3>

            <img
                src="assets/images/services/dental-care.jpg"
                alt="Pet dental care">

            <p>
                Support your pet's
                oral health with
                proper dental care.
            </p>

            <a href="services.php">
                Learn More →
            </a>

        </div>

    </div>

</section>



<!-- =========================================================
     OUR TEAM
========================================================= -->

<section class="team-section">

    <div class="container team-layout">


        <!-- INTRO -->

        <div class="team-intro">

            <p class="section-label">
                OUR TEAM
            </p>


            <h2>
                Our Veterinary Team
            </h2>


            <p>
                Our Veterinarians are committed
                to providing the best possible
                care for your pets.
            </p>


            <a
                href="team.php"
                class="btn btn-primary">

                MEET OUR TEAM
                <span class="btn-arrow">→</span>

            </a>

        </div>



        <!-- TEAM MEMBERS -->

        <div class="team-grid">


            <!-- MARIA -->

            <div class="team-card">

                <img
                    src="assets/images/services/team/maria-santos.png"
                    alt="Dr. Maria Santos">

                <div class="team-info">

                    <h3>
                        Dr. Maria Santos
                    </h3>

                    <span>
                        Veterinarian
                    </span>

                    <p>
                        Specializes in small animal
                        medicine and preventive care.
                    </p>

                </div>

            </div>



            <!-- MIGUEL -->

            <div class="team-card">

                <img
                    src="assets/images/services/team/miguel-reyes.png"
                    alt="Dr. Miguel Reyes">

                <div class="team-info">

                    <h3>
                        Dr. Miguel Reyes
                    </h3>

                    <span>
                        Veterinarian
                    </span>

                    <p>
                        Specializes in surgery
                        and emergency medicine.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     WHY CHOOSE US
========================================================= -->

<section class="why-section">

    <div class="container">

        <h2>
            Why Pet Owners Choose Us
        </h2>

        <div class="why-grid">

            <!-- CARD 1 -->
            <div class="why-card">

                <div class="why-icon">♙</div>

                <h3>
                    Experienced Veterinarians
                </h3>

                <p>
                    Our team is highly trained
                    and passionate about animal care.
                </p>

            </div>

            <!-- CARD 2 -->
            <div class="why-card">

                <div class="why-icon">✚</div>

                <h3>
                    Modern Facilities
                </h3>

                <p>
                    We use advanced equipment
                    for accurate diagnosis and treatment.
                </p>

            </div>

            <!-- CARD 3 -->
            <div class="why-card">

                <div class="why-icon">🐾</div>

                <h3>
                    Personalized Care
                </h3>

                <p>
                    We create tailored care plans
                    for the unique needs of every pet.
                </p>

            </div>

            <!-- CARD 4 -->
            <div class="why-card">

                <div class="why-icon">🏠</div>

                <h3>
                    Pet-Friendly Environment
                </h3>

                <p>
                    A calm, clean, and welcoming
                    space for pets and their owners.
                </p>

            </div>

        </div>

    </div>

</section>


<?php

include 'includes/footer.php';

?>


