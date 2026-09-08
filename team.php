<?php

$pageTitle = "Our Team";

include 'includes/header.php';
include 'includes/navbar.php';

?>

<style>
/* =========================================================
   MINGUITO TEAM PAGE — CARD / IMAGE FIT
========================================================= */

.team-page {
    padding: 38px 0 90px;
    background:
        radial-gradient(circle at 8% 20%, rgba(184,120,41,.06), transparent 24%),
        linear-gradient(135deg, #fbf3e6 0%, #f5e8d6 100%);
}

.team-page .team-large-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 32px;
    align-items: stretch;
}

.team-page .team-profile {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
    border: 1px solid rgba(7,59,42,.10);
    border-radius: 28px;
    background: #073b2a;
    box-shadow: 0 18px 42px rgba(7,59,42,.13);
    transition: transform .3s ease, box-shadow .3s ease;
}

.team-page .team-profile:hover {
    transform: translateY(-6px);
    box-shadow: 0 26px 52px rgba(7,59,42,.18);
}

/* Image area: fixed proportion so the photo is not awkwardly cropped */
.team-page .team-profile > img {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    height: auto;
    object-fit: cover;
    object-position: center center;
    background: #e7ded0;
}

/* Text area stays consistent between the two cards */
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
    font-family: "DM Sans", sans-serif;
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

/* Keep the page heading visually balanced with the cards */
.team-page + .site-footer {
    margin-top: 0;
}

/* Tablet */
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
}

/* Mobile */
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
}
</style>


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


<section class="team-page">

    <div class="container">

        <div class="team-large-grid">

            <article class="team-profile">

                <img
                    src="assets/images/services/team/maria-santos.png"
                    alt="Dr. Maria Santos">

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


            <article class="team-profile">

                <img
                    src="assets/images/services/team/miguel-reyes.png"
                    alt="Dr. Miguel Reyes">

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


<?php

include 'includes/footer.php';

?>
