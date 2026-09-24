<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dog Adoption</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

</body>

</html>
<header class="site-header">

    <div class="header-container">

        <div class="logo-container">
            <a href="http://adoptionproject.loc/" class="logo">
                <img src="assets/images/happy-tails.png" alt="Happy Tails Logo" />
            </a>
        </div>
        <div class="nav-container">
            <nav class="main-nav" id="mainNav">
                <a href="http://adoptionproject.loc/"><strong>Home</strong></a>
                <a href="/aboutus.php"><strong>About Us</strong></a>
                <a href="/availabledogs.php"><strong>Available Dogs</strong></a>
                <!-- <a href="/adoptionform.php"><strong>Adopt Dog</strong></a> -->
                <a href="#"><strong>Stories</strong></a>
            </nav>
        </div>

        <div class="header-actions">
            <a href="#" class="donate-btn"> <i class="fa-regular fa-heart"></i><strong>Donate</strong></a>
            <a href="login.php" class="donate-btn"><i class="fa-regular fa-user"></i> <strong>Log In</strong></a>
            <!-- Hamburger -->
            <button
                type="button"
                class="menu-toggle"
                id="menuToggle"
                aria-label="Open Menu"
                aria-expanded="false"
                aria-controls="mainNav">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div>

    </div>
</header>

<script>
    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    menuToggle.addEventListener("click", function() {

        menuToggle.classList.toggle("active");
        mainNav.classList.toggle("open");

        const isOpen = mainNav.classList.contains("open");

        menuToggle.setAttribute("aria-expanded", isOpen);
    });

    // Close menu when a navigation link is clicked
    const navLinks = mainNav.querySelectorAll("a");

    navLinks.forEach(function(link) {

        link.addEventListener("click", function() {

            menuToggle.classList.remove("active");
            mainNav.classList.remove("open");

            menuToggle.setAttribute("aria-expanded", "false");
        });

    });
</script>