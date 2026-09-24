<!-- =====================================================
     HAPPY TAILS FOOTER
     HTML + CSS IN SAME FILE
===================================================== -->

<style>
    /* =====================================================
   MAIN FOOTER
===================================================== */

    .htd-footer {
        width: 100%;
        margin: 0;
        padding: 0;

        background: #aeb3df;
        color: #25234f;

        box-sizing: border-box;
    }


    /* =====================================================
   FOOTER CONTAINER
===================================================== */

    .htd-footer-container {
        width: 100%;
        max-width: 1200px;

        margin: 0 auto;

        padding: 38px 40px 35px;

        display: grid;

        grid-template-columns: 1.5fr 1fr 1.2fr;

        gap: 65px;

        box-sizing: border-box;
    }


    /* =====================================================
   BRAND SECTION
===================================================== */

    .htd-footer-brand {
        max-width: 400px;

        text-align: center;
    }


    /* =====================================================
   LOGO
===================================================== */

    .htd-footer-logo {
        display: flex;

        align-items: center;
        justify-content: center;

        width: 100%;

        margin: 0 0 14px;

        padding: 0;

        text-decoration: none;
    }


    /*
   Logo is made larger without increasing
   the overall footer section unnecessarily.
*/

    .htd-footer-logo img {
        width: 220px !important;
        height: 150px !important;

        max-width: none !important;
        max-height: none !important;

        object-fit: contain;

        display: block;

        margin: -15px auto -8px;
    }


    /* =====================================================
   DESCRIPTION
===================================================== */

    .htd-footer-description {
        margin: 0 auto 20px;

        max-width: 390px;

        color: #45446b;

        font-size: 15px;

        line-height: 1.65;
    }


    /* =====================================================
   ADOPT BUTTON
===================================================== */

    .htd-footer-adopt-btn {
        display: inline-flex;

        align-items: center;
        justify-content: center;

        gap: 10px;

        padding: 11px 19px;

        background: #6938c6;

        color: #ffffff;

        border-radius: 7px;

        text-decoration: none;

        font-size: 14px;

        font-weight: 700;

        line-height: 1.3;

        transition:
            background 0.25s ease,
            transform 0.25s ease;
    }


    .htd-footer-adopt-btn:hover {
        background: #5528aa;

        color: #ffffff;

        transform: translateY(-2px);
    }


    /* =====================================================
   FOOTER COLUMNS
===================================================== */

    .htd-footer-column {
        min-width: 0;

        padding-top: 12px;
    }


    /* =====================================================
   HEADINGS
===================================================== */

    .htd-footer-heading {
        margin: 0 0 22px;

        padding: 0;

        color: #292451;

        font-size: 19px;

        font-weight: 800;

        line-height: 1.3;
    }


    /*
   No border
   No ::after
   No separator line
*/


    /* =====================================================
   QUICK LINKS
===================================================== */

    .htd-footer-links {
        list-style: none;

        margin: 0;
        padding: 0;
    }


    .htd-footer-links li {
        margin: 0 0 14px;

        padding: 0;
    }


    .htd-footer-links a {
        display: inline-block;

        color: #45446b;

        text-decoration: none;

        font-size: 15px;

        line-height: 1.5;

        transition:
            color 0.25s ease,
            transform 0.25s ease;
    }


    .htd-footer-links a:hover {
        color: #5d2fb4;

        transform: translateX(4px);
    }


    /* =====================================================
   GET IN TOUCH
===================================================== */

    .htd-footer-contact-item {
        display: flex;

        align-items: center;

        gap: 14px;

        margin: 0 0 17px;

        padding: 0;

        min-height: 38px;
    }


    /* =====================================================
   CONTACT ICON
===================================================== */

    .htd-footer-contact-icon {
        width: 34px;
        height: 34px;

        min-width: 34px;

        flex-shrink: 0;

        display: flex;

        align-items: center;
        justify-content: center;

        background: #ffffff;

        color: #6938c6;

        border-radius: 50%;

        font-size: 15px;

        font-weight: 700;

        line-height: 1;

        box-sizing: border-box;
    }


    /* =====================================================
   CONTACT CONTENT
===================================================== */

    .htd-footer-contact-content {
        display: flex;

        flex-direction: column;

        justify-content: center;

        align-items: flex-start;

        gap: 3px;

        min-width: 0;
    }


    /* Contact label */

    .htd-footer-contact-label {
        display: block;

        margin: 0;

        color: #5d2fb4;

        font-size: 12px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: 0.8px;

        line-height: 1.3;
    }


    /* Contact text */

    .htd-footer-contact-content a,
    .htd-footer-contact-content span:not(.htd-footer-contact-label) {
        display: block;

        margin: 0;

        color: #45446b;

        font-size: 14px;

        line-height: 1.5;

        text-decoration: none;
    }


    .htd-footer-contact-content a:hover {
        color: #5d2fb4;
    }


    /* =====================================================
   COPYRIGHT
===================================================== */

    .htd-footer-bottom {
        width: 100%;

        background: #969dcc;

        border-top: 1px solid rgba(255, 255, 255, 0.25);

        box-sizing: border-box;
    }


    .htd-footer-bottom-inner {
        width: 100%;
        max-width: 1200px;

        margin: 0 auto;

        padding: 13px 40px;

        display: flex;

        align-items: center;
        justify-content: center;

        text-align: center;

        box-sizing: border-box;
    }


    .htd-footer-copyright {
        margin: 0;

        color: #ffffff;

        font-size: 13px;

        line-height: 1.5;
    }


    /* =====================================================
   TABLET
===================================================== */

    @media (max-width: 900px) {

        .htd-footer-container {
            grid-template-columns: 1.4fr 1fr 1.2fr;

            gap: 35px;

            padding: 35px 30px 32px;
        }


        .htd-footer-logo img {
            width: 140px !important;

            height: 80px !important;
        }


        .htd-footer-description {
            font-size: 14px;

            line-height: 1.6;
        }


        .htd-footer-heading {
            font-size: 18px;
        }


        .htd-footer-links a {
            font-size: 14px;
        }


        .htd-footer-contact-content a,
        .htd-footer-contact-content span:not(.htd-footer-contact-label) {
            font-size: 13px;
        }

    }


    /* =====================================================
   MOBILE
===================================================== */

    @media (max-width: 700px) {

        .htd-footer-container {
            grid-template-columns: 1fr;

            gap: 32px;

            padding: 35px 25px;
        }


        /* Brand */

        .htd-footer-brand {
            width: 100%;

            max-width: 100%;

            text-align: center;
        }


        .htd-footer-logo {
            justify-content: center;

            margin-bottom: 12px;
        }


        .htd-footer-logo img {
            width: 145px !important;

            height: 82px !important;
        }


        .htd-footer-description {
            max-width: 100%;

            margin-left: auto;
            margin-right: auto;

            font-size: 14px;
        }


        .htd-footer-adopt-btn {
            margin: 0 auto;
        }


        /* Columns */

        .htd-footer-column {
            width: 100%;

            padding-top: 0;

            text-align: center;
        }


        .htd-footer-heading {
            margin-bottom: 18px;

            font-size: 19px;
        }


        /* Quick Links */

        .htd-footer-links li {
            margin-bottom: 12px;
        }


        .htd-footer-links a {
            font-size: 15px;
        }


        /* Contact */

        .htd-footer-contact-item {
            width: fit-content;

            margin-left: auto;
            margin-right: auto;

            justify-content: flex-start;

            text-align: left;
        }


        .htd-footer-contact-content {
            align-items: flex-start;
        }


        .htd-footer-bottom-inner {
            padding: 13px 20px;
        }

    }


    /* =====================================================
   SMALL MOBILE
===================================================== */

    @media (max-width: 400px) {

        .htd-footer-container {
            padding: 30px 18px;
        }


        .htd-footer-logo img {
            width: 135px !important;

            height: 78px !important;
        }


        .htd-footer-description {
            font-size: 13px;

            line-height: 1.6;
        }


        .htd-footer-adopt-btn {
            width: 100%;

            box-sizing: border-box;

            font-size: 13px;
        }


        .htd-footer-heading {
            font-size: 18px;
        }


        .htd-footer-links a {
            font-size: 14px;
        }


        .htd-footer-contact-content a,
        .htd-footer-contact-content span:not(.htd-footer-contact-label) {
            font-size: 13px;
        }


        .htd-footer-copyright {
            font-size: 11px;
        }

    }
</style>


<!-- =====================================================
     FOOTER HTML
===================================================== -->

<footer class="htd-footer">

    <div class="htd-footer-container">


        <!-- =============================================
             LOGO + HAPPY TAILS
        ============================================== -->

        <div class="htd-footer-brand">

            <a
                href="index.php"
                class="htd-footer-logo">

                <img
                    src="assets/images/happy-tails.png"
                    alt="Happy Tails Logo" />

            </a>


            <p class="htd-footer-description">

                Every dog deserves a forever home.
                Help us give loving dogs the second chance
                they deserve.

            </p>


            <a
                href="available-dogs.php"
                class="htd-footer-adopt-btn">

                <span>
                    Find Your Perfect Companion
                </span>

                <span>
                    →
                </span>

            </a>

        </div>


        <!-- =============================================
             QUICK LINKS
        ============================================== -->

        <div class="htd-footer-column">

            <h3 class="htd-footer-heading">
                Quick Links
            </h3>


            <ul class="htd-footer-links">

                <li>
                    <a href="index.php">
                        Home
                    </a>
                </li>


                <li>
                    <a href="about.php">
                        About Us
                    </a>
                </li>


                <li>
                    <a href="available-dogs.php">
                        Available Dogs
                    </a>
                </li>


                <li>
                    <a href="stories.php">
                        Stories
                    </a>
                </li>

            </ul>

        </div>


        <!-- =============================================
             GET IN TOUCH
        ============================================== -->

        <div class="htd-footer-column">

            <h3 class="htd-footer-heading">
                Get In Touch
            </h3>


            <!-- EMAIL -->

            <div class="htd-footer-contact-item">

                <span class="htd-footer-contact-icon">
                    ✉
                </span>


                <div class="htd-footer-contact-content">

                    <span class="htd-footer-contact-label">
                        Email
                    </span>

                    <a href="mailto:happytailsnepal@gmail.com">
                        happytailsnepal@gmail.com
                    </a>

                </div>

            </div>


            <!-- PHONE -->

            <div class="htd-footer-contact-item">

                <span class="htd-footer-contact-icon">
                    ☎
                </span>


                <div class="htd-footer-contact-content">

                    <span class="htd-footer-contact-label">
                        Phone
                    </span>

                    <a href="tel:+1234567890">
                        (123) 456-7890
                    </a>

                </div>

            </div>


            <!-- LOCATION -->

            <div class="htd-footer-contact-item">

                <span class="htd-footer-contact-icon">
                    ⌂
                </span>


                <div class="htd-footer-contact-content">

                    <span class="htd-footer-contact-label">
                        Location
                    </span>

                    <span>
                       Kathmandu, Nepal
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- =============================================
         COPYRIGHT
    ============================================== -->

    <div class="htd-footer-bottom">

        <div class="htd-footer-bottom-inner">

            <p class="htd-footer-copyright">
                © 2025 Happy Tails Dog Adoption. All Rights Reserved.
            </p>

        </div>

    </div>

</footer>