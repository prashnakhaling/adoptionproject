<?php require __DIR__ . '/includes/header.php'; ?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>About Us | Happy Tails Dog Adoption</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Font -->
    <link rel="preconnect"
        href="https://fonts.googleapis.com">

    <link rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>
    <link rel="stylesheet" href="assets/style.css">


    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">


    <style>
        /* =========================================================
   HAPPY TAILS - ABOUT PAGE ONLY
   All classes use ht-about- prefix to avoid conflicts
   with header.php and other pages.
========================================================= */


        /* ---------- Page Base ---------- */

        .ht-about-page {
            font-family: 'Poppins', sans-serif;
            color: #29264d;
            background: #ffffff;
            width: 100%;
            overflow-x: hidden;
        }

        .ht-about-page *,
        .ht-about-page *::before,
        .ht-about-page *::after {
            box-sizing: border-box;
        }

        .ht-about-page img {
            max-width: 100%;
            display: block;
        }

        .ht-about-page a {
            text-decoration: none;
        }


        /* =========================================================
   HERO
========================================================= */

        .ht-about-hero {
            width: 100%;
            background: #b2b5ff;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .ht-about-hero::before {
            content: "🐾";

            position: absolute;
            left: 5%;
            top: 20px;

            font-size: 160px;

            opacity: 0.07;

            transform: rotate(-15deg);
        }

        .ht-about-hero::after {
            content: "🐾";

            position: absolute;
            right: 5%;
            bottom: -25px;

            font-size: 160px;

            opacity: 0.07;

            transform: rotate(15deg);
        }

        .ht-about-hero-inner {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 850px;

            margin: 0 auto;
        }

        .ht-about-hero-label {
            font-size: 12px;
            font-weight: 700;

            letter-spacing: 3px;
            text-transform: uppercase;

            color: #6635b0;

            margin-bottom: 12px;
        }

        .ht-about-hero-title {
            font-size: 46px;
            line-height: 1.2;

            color: #29244c;

            margin: 0 0 16px;
        }

        .ht-about-hero-title span {
            color: #6335b3;
        }

        .ht-about-hero-text {
            max-width: 680px;

            margin: 0 auto;

            color: #5d5879;

            font-size: 15px;
            line-height: 1.9;
        }


        /* =========================================================
   COMMON SECTION
========================================================= */

        .ht-about-section {
            width: 100%;
            padding: 80px 7%;
        }

        .ht-about-container {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
        }


        /* =========================================================
   COMMON SECTION HEADING
========================================================= */

        .ht-about-heading {
            text-align: center;

            max-width: 720px;

            margin: 0 auto 45px;
        }

        .ht-about-heading-label {
            font-size: 12px;
            font-weight: 700;

            letter-spacing: 2px;
            text-transform: uppercase;

            color: #7040bd;

            margin-bottom: 9px;
        }

        .ht-about-heading-title {
            font-size: 32px;
            line-height: 1.3;

            color: #2b274d;

            margin: 0 0 12px;
        }

        .ht-about-heading-text {
            font-size: 14px;
            line-height: 1.8;

            color: #706b7d;

            margin: 0;
        }


        /* =========================================================
   ABOUT PROJECT
========================================================= */

        .ht-about-project {
            background: #ffffff;
        }

        .ht-about-project-grid {
            display: grid;

            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);

            gap: 65px;

            align-items: center;
        }

        .ht-about-project-image {
            width: 100%;
        }

        .ht-about-project-image img {
            width: 100%;
            height: 400px;

            object-fit: cover;

            border-radius: 18px;

            box-shadow: 0 8px 25px rgba(61, 47, 115, 0.10);
        }

        .ht-about-project-content-label {
            font-size: 12px;
            font-weight: 700;

            letter-spacing: 2px;
            text-transform: uppercase;

            color: #7040bd;

            margin-bottom: 10px;
        }

        .ht-about-project-title {
            font-size: 34px;
            line-height: 1.3;

            color: #2b274d;

            margin: 0 0 18px;
        }

        .ht-about-project-title span {
            color: #6d3dc0;
        }

        .ht-about-project-text {
            font-size: 14px;
            line-height: 1.9;

            color: #6d687b;

            margin: 0 0 14px;
        }


        /* =========================================================
   WHY SECTION
========================================================= */

        .ht-about-why {
            background: #f7f4ff;
        }

        .ht-about-card-grid {
            display: grid;

            grid-template-columns: repeat(3, minmax(0, 1fr));

            gap: 25px;
        }

        .ht-about-info-card {
            background: #ffffff;

            border: 1px solid #eee9ff;

            border-radius: 16px;

            padding: 32px 27px;

            transition: transform 0.25s ease,
                box-shadow 0.25s ease;
        }

        .ht-about-info-card:hover {
            transform: translateY(-5px);

            box-shadow: 0 12px 28px rgba(76, 48, 135, 0.10);
        }

        .ht-about-info-icon {
            width: 56px;
            height: 56px;

            border-radius: 50%;

            background: #dfdcff;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #6739b8;

            font-size: 22px;

            margin-bottom: 18px;
        }

        .ht-about-info-title {
            font-size: 17px;

            color: #312b4e;

            margin: 0 0 10px;
        }

        .ht-about-info-text {
            font-size: 13px;

            line-height: 1.8;

            color: #746e80;

            margin: 0;
        }


        /* =========================================================
   MISSION
========================================================= */

        .ht-about-mission {
            background: #b2b5ff;
        }

        .ht-about-mission-grid {
            display: grid;

            grid-template-columns: repeat(3, minmax(0, 1fr));

            gap: 22px;

            margin-top: 40px;
        }

        .ht-about-mission-card {
            background: #ffffff;

            border-radius: 16px;

            padding: 32px 25px;

            text-align: center;
        }

        .ht-about-mission-number {
            width: 44px;
            height: 44px;

            border-radius: 50%;

            background: #e3e0ff;

            color: #6837b7;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 16px;

            font-size: 13px;
            font-weight: 700;
        }

        .ht-about-mission-title {
            font-size: 16px;

            color: #302a4e;

            margin: 0 0 9px;
        }

        .ht-about-mission-text {
            font-size: 12px;

            line-height: 1.8;

            color: #746f80;

            margin: 0;
        }


        /* =========================================================
   PLATFORM FEATURES
========================================================= */

        .ht-about-features {
            background: #ffffff;
        }

        .ht-about-feature-grid {
            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 22px;
        }

        .ht-about-feature {
            display: flex;

            align-items: flex-start;

            gap: 18px;

            background: #f8f6ff;

            border: 1px solid #eeeaff;

            border-radius: 14px;

            padding: 25px;
        }

        .ht-about-feature-icon {
            flex: 0 0 52px;

            width: 52px;
            height: 52px;

            border-radius: 12px;

            background: #dedaff;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #6638b5;

            font-size: 20px;
        }

        .ht-about-feature-title {
            font-size: 16px;

            color: #302a4c;

            margin: 0 0 7px;
        }

        .ht-about-feature-text {
            font-size: 12px;

            line-height: 1.7;

            color: #736e7f;

            margin: 0;
        }


        /* =========================================================
   HOW IT WORKS
========================================================= */

        .ht-about-how {
            background: #f6f3ff;
        }

        .ht-about-steps {
            display: grid;

            grid-template-columns: repeat(4, minmax(0, 1fr));

            gap: 20px;

            margin-top: 45px;
        }

        .ht-about-step {
            text-align: center;

            position: relative;
        }

        .ht-about-step-circle {
            width: 62px;
            height: 62px;

            border-radius: 50%;

            background: #7141c4;

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto;

            font-size: 20px;

            box-shadow: 0 6px 15px rgba(84, 44, 150, 0.18);
        }

        .ht-about-step-title {
            font-size: 15px;

            color: #302a4d;

            margin: 17px 0 7px;
        }

        .ht-about-step-text {
            font-size: 12px;

            line-height: 1.7;

            color: #777183;

            margin: 0;
        }


        /* =========================================================
   OBJECTIVES
========================================================= */

        .ht-about-objectives {
            background: #ffffff;
        }

        .ht-about-objectives-grid {
            display: grid;

            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);

            gap: 55px;

            align-items: center;
        }

        .ht-about-objectives-image img {
            width: 100%;
            height: 360px;

            object-fit: cover;

            border-radius: 18px;

            box-shadow: 0 8px 25px rgba(61, 47, 115, 0.10);
        }

        .ht-about-objectives-label {
            color: #7040bd;

            font-size: 12px;
            font-weight: 700;

            letter-spacing: 2px;

            text-transform: uppercase;

            margin-bottom: 9px;
        }

        .ht-about-objectives-title {
            font-size: 31px;

            color: #2d284c;

            margin: 0 0 17px;
        }

        .ht-about-objectives-intro {
            color: #716b7d;

            font-size: 13px;

            line-height: 1.8;

            margin: 0 0 20px;
        }

        .ht-about-objective-list {
            list-style: none;

            margin: 0;
            padding: 0;
        }

        .ht-about-objective-list li {
            display: flex;

            align-items: flex-start;

            gap: 12px;

            margin-bottom: 14px;

            color: #625d70;

            font-size: 13px;

            line-height: 1.6;
        }

        .ht-about-objective-list i {
            color: #6e3dc0;

            margin-top: 3px;

            flex-shrink: 0;
        }


        /* =========================================================
   USERS
========================================================= */

        .ht-about-users {
            background: #b2b5ff;
        }

        .ht-about-user-grid {
            display: grid;

            grid-template-columns: repeat(3, minmax(0, 1fr));

            gap: 25px;

            margin-top: 40px;
        }

        .ht-about-user-card {
            background: #ffffff;

            padding: 30px 22px;

            border-radius: 15px;

            text-align: center;
        }

        .ht-about-user-icon {
            font-size: 27px;

            color: #6c3cbc;

            margin-bottom: 15px;
        }

        .ht-about-user-title {
            color: #312b4d;

            font-size: 16px;

            margin: 0 0 8px;
        }

        .ht-about-user-text {
            color: #746f80;

            font-size: 12px;

            line-height: 1.7;

            margin: 0;
        }


        /* =========================================================
   CTA
========================================================= */

        .ht-about-cta-section {
            width: 100%;

            background: #ffffff;

            padding: 55px 7%;
        }

        .ht-about-cta {
            max-width: 1100px;

            margin: 0 auto;

            background: #eee9ff;

            padding: 32px 38px;

            border-radius: 16px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 25px;
        }

        .ht-about-cta-title {
            color: #30294e;

            font-size: 20px;

            margin: 0 0 6px;
        }

        .ht-about-cta-text {
            color: #777180;

            font-size: 12px;

            margin: 0;
        }

        .ht-about-cta-button {
            display: inline-flex;

            align-items: center;

            gap: 8px;

            background: #6936bd;

            color: #ffffff;

            padding: 12px 22px;

            border-radius: 8px;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;

            transition: 0.3s ease;
        }

        .ht-about-cta-button:hover {
            background: #4f2897;

            transform: translateY(-2px);
        }


        /* =========================================================
   RESPONSIVE - TABLET
========================================================= */

        @media (max-width: 1000px) {

            .ht-about-section {
                padding: 65px 5%;
            }

            .ht-about-project-grid,
            .ht-about-objectives-grid {
                gap: 40px;
            }

            .ht-about-project-title {
                font-size: 30px;
            }

            .ht-about-card-grid,
            .ht-about-mission-grid,
            .ht-about-user-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ht-about-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));

                row-gap: 40px;
            }

            .ht-about-hero-title {
                font-size: 40px;
            }

        }


        /* =========================================================
   RESPONSIVE - MOBILE
========================================================= */

        @media (max-width: 700px) {

            .ht-about-section {
                padding: 55px 6%;
            }


            /* Hero */

            .ht-about-hero {
                padding: 60px 20px;
            }

            .ht-about-hero::before,
            .ht-about-hero::after {
                font-size: 100px;
            }

            .ht-about-hero-title {
                font-size: 32px;
            }

            .ht-about-hero-text {
                font-size: 13px;
            }


            /* Heading */

            .ht-about-heading {
                margin-bottom: 35px;
            }

            .ht-about-heading-title {
                font-size: 27px;
            }

            .ht-about-heading-text {
                font-size: 13px;
            }


            /* Project */

            .ht-about-project-grid {
                grid-template-columns: 1fr;

                gap: 35px;
            }

            .ht-about-project-image img {
                height: 300px;
            }

            .ht-about-project-title {
                font-size: 28px;
            }


            /* Cards */

            .ht-about-card-grid {
                grid-template-columns: 1fr;

                gap: 18px;
            }


            /* Mission */

            .ht-about-mission-grid {
                grid-template-columns: 1fr;

                gap: 18px;

                margin-top: 30px;
            }


            /* Features */

            .ht-about-feature-grid {
                grid-template-columns: 1fr;

                gap: 16px;
            }

            .ht-about-feature {
                padding: 20px;
            }


            /* How */

            .ht-about-steps {
                grid-template-columns: 1fr;

                gap: 35px;

                margin-top: 30px;
            }


            /* Objectives */

            .ht-about-objectives-grid {
                grid-template-columns: 1fr;

                gap: 35px;
            }

            .ht-about-objectives-image img {
                height: 300px;
            }

            .ht-about-objectives-title {
                font-size: 27px;
            }


            /* Users */

            .ht-about-user-grid {
                grid-template-columns: 1fr;

                gap: 18px;
            }


            /* CTA */

            .ht-about-cta-section {
                padding: 40px 6%;
            }

            .ht-about-cta {
                padding: 25px;

                flex-direction: column;

                align-items: flex-start;
            }

            .ht-about-cta-title {
                font-size: 18px;
            }

            .ht-about-cta-button {
                width: 100%;

                justify-content: center;
            }

        }


        /* =========================================================
   SMALL MOBILE
========================================================= */

        @media (max-width: 420px) {

            .ht-about-hero-title {
                font-size: 28px;
            }

            .ht-about-hero-label {
                font-size: 10px;

                letter-spacing: 2px;
            }

            .ht-about-project-title {
                font-size: 25px;
            }

            .ht-about-project-text {
                font-size: 13px;
            }

            .ht-about-info-card {
                padding: 25px 22px;
            }

            .ht-about-heading-title {
                font-size: 24px;
            }

            .ht-about-section {
                padding: 48px 5%;
            }

        }
    </style>

</head>


<body>


    <!-- =========================================================
     ABOUT PAGE WRAPPER
========================================================= -->

    <div class="ht-about-page">


        <!-- =====================================================
         ABOUT HERO
    ===================================================== -->

        <section class="ht-about-hero">

            <div class="ht-about-hero-inner">

                <div class="ht-about-hero-label">
                    ABOUT HAPPY TAILS
                </div>

                <h1 class="ht-about-hero-title">
                    About <span>Happy Tails</span>
                </h1>

                <p class="ht-about-hero-text">
                    Happy Tails is a web-based dog adoption platform designed
                    to connect dogs looking for homes with people who are ready
                    to provide them with love, care and a forever home.
                </p>

            </div>

        </section>



        <!-- =====================================================
         ABOUT PROJECT
    ===================================================== -->

        <section class="ht-about-section ht-about-project">

            <div class="ht-about-container">

                <div class="ht-about-project-grid">


                    <div class="ht-about-project-image">

                        <img
                            src="images/about-dog.jpg"
                            alt="Dog waiting for adoption">

                    </div>


                    <div class="ht-about-project-content">

                        <div class="ht-about-project-content-label">
                            ABOUT THE PROJECT
                        </div>

                        <h2 class="ht-about-project-title">
                            Making Dog Adoption
                            <span>Simple & Accessible</span>
                        </h2>

                        <p class="ht-about-project-text">
                            Happy Tails is a web-based dog adoption platform
                            developed to make the process of finding and adopting
                            dogs easier and more accessible.
                        </p>

                        <p class="ht-about-project-text">
                            The platform provides a centralized place where
                            potential adopters can explore available dogs,
                            understand their information and learn about the
                            adoption process.
                        </p>

                        <p class="ht-about-project-text">
                            Instead of searching through different sources,
                            users can discover adoption-related information
                            through one simple and user-friendly platform.
                        </p>

                        <p class="ht-about-project-text">
                            The project focuses on using technology to support
                            responsible dog adoption while creating a better
                            experience for both dogs and potential adopters.
                        </p>

                    </div>

                </div>

            </div>

        </section>



        <!-- =====================================================
         WHY WE CREATED IT
    ===================================================== -->

        <section class="ht-about-section ht-about-why">

            <div class="ht-about-container">

                <div class="ht-about-heading">

                    <div class="ht-about-heading-label">
                        WHY HAPPY TAILS?
                    </div>

                    <h2 class="ht-about-heading-title">
                        Why We Created This Platform
                    </h2>

                    <p class="ht-about-heading-text">
                        Finding the right dog for adoption can sometimes be
                        difficult when information is scattered across different
                        places. Happy Tails brings important information together
                        in one convenient platform.
                    </p>

                </div>


                <div class="ht-about-card-grid">


                    <div class="ht-about-info-card">

                        <div class="ht-about-info-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>

                        <h3 class="ht-about-info-title">
                            Difficult To Find Dogs
                        </h3>

                        <p class="ht-about-info-text">
                            Potential adopters may not know where to find dogs
                            that are currently available. The platform provides
                            one place to browse adoptable dogs.
                        </p>

                    </div>


                    <div class="ht-about-info-card">

                        <div class="ht-about-info-icon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>

                        <h3 class="ht-about-info-title">
                            Lack Of Information
                        </h3>

                        <p class="ht-about-info-text">
                            Information about a dog's age, breed and other
                            details can help potential adopters understand
                            whether a dog is suitable for them.
                        </p>

                    </div>


                    <div class="ht-about-info-card">

                        <div class="ht-about-info-icon">
                            <i class="fa-solid fa-laptop"></i>
                        </div>

                        <h3 class="ht-about-info-title">
                            Need For A Digital Platform
                        </h3>

                        <p class="ht-about-info-text">
                            A dedicated digital platform makes it easier for
                            users to explore dogs and learn about adoption
                            from anywhere.
                        </p>

                    </div>


                </div>

            </div>

        </section>



        <!-- =====================================================
         MISSION
    ===================================================== -->

        <section class="ht-about-section ht-about-mission">

            <div class="ht-about-container">

                <div class="ht-about-heading">

                    <div class="ht-about-heading-label">
                        OUR MISSION
                    </div>

                    <h2 class="ht-about-heading-title">
                        What Happy Tails Aims To Do
                    </h2>

                    <p class="ht-about-heading-text">
                        Our mission is to use technology to support responsible
                        dog adoption and create a better connection between dogs
                        and potential adopters.
                    </p>

                </div>


                <div class="ht-about-mission-grid">


                    <div class="ht-about-mission-card">

                        <div class="ht-about-mission-number">
                            01
                        </div>

                        <h3 class="ht-about-mission-title">
                            Promote Adoption
                        </h3>

                        <p class="ht-about-mission-text">
                            Encourage people to consider adoption and give dogs
                            an opportunity to find a loving family.
                        </p>

                    </div>


                    <div class="ht-about-mission-card">

                        <div class="ht-about-mission-number">
                            02
                        </div>

                        <h3 class="ht-about-mission-title">
                            Improve Accessibility
                        </h3>

                        <p class="ht-about-mission-text">
                            Make information about available dogs easier for
                            potential adopters to discover and understand.
                        </p>

                    </div>


                    <div class="ht-about-mission-card">

                        <div class="ht-about-mission-number">
                            03
                        </div>

                        <h3 class="ht-about-mission-title">
                            Encourage Responsibility
                        </h3>

                        <p class="ht-about-mission-text">
                            Help users understand that adopting a dog is a
                            long-term responsibility and commitment.
                        </p>

                    </div>


                </div>

            </div>

        </section>



        <!-- =====================================================
         PLATFORM FEATURES
    ===================================================== -->

        <section class="ht-about-section ht-about-features">

            <div class="ht-about-container">

                <div class="ht-about-heading">

                    <div class="ht-about-heading-label">
                        PLATFORM FEATURES
                    </div>

                    <h2 class="ht-about-heading-title">
                        What Happy Tails Provides
                    </h2>

                    <p class="ht-about-heading-text">
                        The platform brings together several features designed
                        to make the dog adoption journey easier to understand.
                    </p>

                </div>


                <div class="ht-about-feature-grid">


                    <div class="ht-about-feature">

                        <div class="ht-about-feature-icon">
                            <i class="fa-solid fa-dog"></i>
                        </div>

                        <div>

                            <h3 class="ht-about-feature-title">
                                Available Dogs
                            </h3>

                            <p class="ht-about-feature-text">
                                Users can browse dogs that are available for
                                adoption and view their important information.
                            </p>

                        </div>

                    </div>


                    <div class="ht-about-feature">

                        <div class="ht-about-feature-icon">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div>

                            <h3 class="ht-about-feature-title">
                                User Accounts
                            </h3>

                            <p class="ht-about-feature-text">
                                Registered users can access the platform through
                                their personal accounts.
                            </p>

                        </div>

                    </div>


                    <div class="ht-about-feature">

                        <div class="ht-about-feature-icon">
                            <i class="fa-solid fa-heart"></i>
                        </div>

                        <div>

                            <h3 class="ht-about-feature-title">
                                Adoption Interest
                            </h3>

                            <p class="ht-about-feature-text">
                                Users can show interest in adopting a dog and
                                continue toward the adoption process.
                            </p>

                        </div>

                    </div>


                    <div class="ht-about-feature">

                        <div class="ht-about-feature-icon">
                            <i class="fa-solid fa-book-open"></i>
                        </div>

                        <div>

                            <h3 class="ht-about-feature-title">
                                Adoption Stories
                            </h3>

                            <p class="ht-about-feature-text">
                                Stories can show successful adoption experiences
                                and encourage others to consider adoption.
                            </p>

                        </div>

                    </div>


                    <div class="ht-about-feature">

                        <div class="ht-about-feature-icon">
                            <i class="fa-solid fa-shield-heart"></i>
                        </div>

                        <div>

                            <h3 class="ht-about-feature-title">
                                Adoption Information
                            </h3>

                            <p class="ht-about-feature-text">
                                The platform provides information that can help
                                users understand responsible adoption.
                            </p>

                        </div>

                    </div>


                    <div class="ht-about-feature">

                        <div class="ht-about-feature-icon">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </div>

                        <div>

                            <h3 class="ht-about-feature-title">
                                Donation Support
                            </h3>

                            <p class="ht-about-feature-text">
                                Visitors can support dog welfare through the
                                donation section of the platform.
                            </p>

                        </div>

                    </div>


                </div>

            </div>

        </section>



        <!-- =====================================================
         HOW IT WORKS
    ===================================================== -->

        <section class="ht-about-section ht-about-how">

            <div class="ht-about-container">

                <div class="ht-about-heading">

                    <div class="ht-about-heading-label">
                        HOW IT WORKS
                    </div>

                    <h2 class="ht-about-heading-title">
                        From Browsing To Adoption
                    </h2>

                    <p class="ht-about-heading-text">
                        Happy Tails is designed to make the adoption journey
                        simple and easy to understand.
                    </p>

                </div>


                <div class="ht-about-steps">


                    <div class="ht-about-step">

                        <div class="ht-about-step-circle">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>

                        <h3 class="ht-about-step-title">
                            Browse Dogs
                        </h3>

                        <p class="ht-about-step-text">
                            Explore dogs currently available for adoption.
                        </p>

                    </div>


                    <div class="ht-about-step">

                        <div class="ht-about-step-circle">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>

                        <h3 class="ht-about-step-title">
                            Learn About Them
                        </h3>

                        <p class="ht-about-step-text">
                            Check the dog's information and understand their needs.
                        </p>

                    </div>


                    <div class="ht-about-step">

                        <div class="ht-about-step-circle">
                            <i class="fa-solid fa-heart"></i>
                        </div>

                        <h3 class="ht-about-step-title">
                            Choose Your Companion
                        </h3>

                        <p class="ht-about-step-text">
                            Find a dog that may be suitable for your family.
                        </p>

                    </div>


                    <div class="ht-about-step">

                        <div class="ht-about-step-circle">
                            <i class="fa-solid fa-house"></i>
                        </div>

                        <h3 class="ht-about-step-title">
                            Begin Adoption
                        </h3>

                        <p class="ht-about-step-text">
                            Take the next step toward providing a loving home.
                        </p>

                    </div>


                </div>

            </div>

        </section>



        <!-- =====================================================
         OBJECTIVES
    ===================================================== -->

        <section class="ht-about-section ht-about-objectives">

            <div class="ht-about-container">

                <div class="ht-about-objectives-grid">


                    <div class="ht-about-objectives-image">

                        <img
                            src="images/about-rescue.jpg"
                            alt="Dog adoption">

                    </div>


                    <div>

                        <div class="ht-about-objectives-label">
                            PROJECT OBJECTIVES
                        </div>

                        <h2 class="ht-about-objectives-title">
                            What We Want To Achieve
                        </h2>

                        <p class="ht-about-objectives-intro">
                            The Happy Tails project has been developed with
                            several objectives focused on improving the overall
                            dog adoption experience.
                        </p>


                        <ul class="ht-about-objective-list">

                            <li>
                                <i class="fa-solid fa-check"></i>

                                <span>
                                    Provide a centralized platform for browsing
                                    adoptable dogs.
                                </span>
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>

                                <span>
                                    Make dog and adoption information easy to access.
                                </span>
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>

                                <span>
                                    Provide a simple and user-friendly interface.
                                </span>
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>

                                <span>
                                    Encourage responsible dog adoption.
                                </span>
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>

                                <span>
                                    Connect potential adopters with dogs looking
                                    for homes.
                                </span>
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>

                                <span>
                                    Use technology to support animal welfare
                                    and adoption awareness.
                                </span>
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </section>



        <!-- =====================================================
         WHO IS IT FOR
    ===================================================== -->

        <section class="ht-about-section ht-about-users">

            <div class="ht-about-container">

                <div class="ht-about-heading">

                    <div class="ht-about-heading-label">
                        WHO IS IT FOR?
                    </div>

                    <h2 class="ht-about-heading-title">
                        Built For People Who Care
                    </h2>

                    <p class="ht-about-heading-text">
                        Happy Tails is designed for people and organizations
                        involved in dog adoption and animal welfare.
                    </p>

                </div>


                <div class="ht-about-user-grid">


                    <div class="ht-about-user-card">

                        <div class="ht-about-user-icon">
                            <i class="fa-solid fa-house-user"></i>
                        </div>

                        <h3 class="ht-about-user-title">
                            Potential Adopters
                        </h3>

                        <p class="ht-about-user-text">
                            People looking for a dog to welcome into their family
                            can explore available dogs and adoption information.
                        </p>

                    </div>


                    <div class="ht-about-user-card">

                        <div class="ht-about-user-icon">
                            <i class="fa-solid fa-paw"></i>
                        </div>

                        <h3 class="ht-about-user-title">
                            Shelters & Rescuers
                        </h3>

                        <p class="ht-about-user-text">
                            Shelters and rescue organizations can showcase dogs
                            that are looking for suitable homes.
                        </p>

                    </div>


                    <div class="ht-about-user-card">

                        <div class="ht-about-user-icon">
                            <i class="fa-solid fa-people-group"></i>
                        </div>

                        <h3 class="ht-about-user-title">
                            Animal Lovers
                        </h3>

                        <p class="ht-about-user-text">
                            Animal lovers can learn about adoption and support
                            efforts that improve the lives of dogs.
                        </p>

                    </div>


                </div>

            </div>

        </section>



        <!-- =====================================================
         CTA
    ===================================================== -->

        <section class="ht-about-cta-section">

            <div class="ht-about-cta">

                <div>

                    <h2 class="ht-about-cta-title">
                        Ready to find your new best friend?
                    </h2>

                    <p class="ht-about-cta-text">
                        Explore the dogs waiting for a loving forever home.
                    </p>

                </div>


                <a
                    href="available-dogs.php"
                    class="ht-about-cta-button">

                    Browse Available Dogs

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>

        </section>


    </div>


</body>

</html>