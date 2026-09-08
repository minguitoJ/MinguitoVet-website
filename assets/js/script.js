document.addEventListener("DOMContentLoaded", function () {

    const menuToggle =
        document.getElementById("menuToggle");

    const mainNav =
        document.getElementById("mainNav");


    if (menuToggle && mainNav) {

        menuToggle.addEventListener(
            "click",
            function () {

                mainNav.classList.toggle("show");

            }
        );

    }


    /* Prevent booking dates in the past */

    const dateInput =
        document.querySelector(
            'input[name="appointment_date"]'
        );


    if (dateInput) {

        const today =
            new Date().toISOString().split("T")[0];

        dateInput.setAttribute(
            "min",
            today
        );

    }

});