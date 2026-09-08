<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php
        echo isset($pageTitle)
            ? htmlspecialchars(
                $pageTitle,
                ENT_QUOTES,
                'UTF-8'
            ) . ' | Minguito Veterinary Clinic'
            : 'Minguito Veterinary Clinic';
        ?>
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/responsive.css"
    >


    <style>

        /* =====================================================
           GLOBAL HEADER
        ===================================================== */

        html {
            overflow-x: hidden;
        }

        body {
            margin: 0;
            overflow-x: hidden;
        }

        .site-header {

            position: sticky !important;

            top: 0 !important;
            left: 0;
            right: 0;

            z-index: 9999;

            width: 100%;

            background: #f8efdf;

            border-bottom:
                1px solid
                rgba(7, 59, 42, 0.08);

            box-shadow:
                0 3px 14px
                rgba(7, 59, 42, 0.05);

        }


        /*
         * Prevent the header and navbar from clipping
         * dropdowns or menu elements.
         */

        .site-header,
        .site-header .navbar,
        .site-header nav {
            overflow: visible !important;
        }


        /*
         * Make every header image behave properly.
         */

        .site-header img {
            max-width: 100%;
            height: auto;
        }

    </style>

</head>

<body>