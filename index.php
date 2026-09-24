<?php require __DIR__ . '/includes/header.php'; ?>
<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "dogadoption");

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch dogs from database
$sql = "SELECT dog_id, dog_breed, age, dog_image 
        FROM dogs 
        ORDER BY added_date DESC 
        LIMIT 4";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Happy Tails Dog Adoption</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        function loadPage(pageUrl) {
            fetch(pageUrl)
                .then((response) => response.text())
                .then((html) => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");
                    const bodyContent = doc.body.innerHTML;
                    document.getElementById("slide-content").innerHTML = bodyContent;
                    document
                        .getElementById("page-slide-container")
                        .classList.add("open");
                })
                .catch((err) => console.error("Failed to load page:", err));
        }

        function closeSlide() {
            document
                .getElementById("page-slide-container")
                .classList.remove("open");
        }

        function showhideUsers() {
            const userSection = document.getElementById("userSection");
            const viewMoreButton = document.querySelector(".viewmore");

            userSection.classList.toggle("hide");

            if (!userSection.classList.contains("hide")) {
                // Move the button inside at the end
                userSection.appendChild(viewMoreButton);

                // Scroll into view
                userSection.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });
            }
        }


        function showLoginModal() {
            document.getElementById('loginModal').style.display = 'flex';
            showTab('login'); // Default to login tab
        }

        function hideLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }

        function showTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const loginTab = document.getElementById('loginTab');
            const signupTab = document.getElementById('signupTab');

            if (tab === 'login') {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                loginTab.style.fontWeight = 'bold';
                signupTab.style.fontWeight = 'normal';
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                loginTab.style.fontWeight = 'normal';
                signupTab.style.fontWeight = 'bold';
            }
        }

        // Show alert if "error=1" is in the URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('error') === '1') {
            alert("Login failed! Email or password is incorrect.");
        }
    </script>
</head>

<body>
    <!-- <header>
        <h1>Happy Tails Dog Adoption</h1>
        <p>Find your new best friend today!</p>
    </header> -->

    <!-- <nav>
        <a href="#">Home</a>
        <a href="#dogs-showcase">Available Dogs</a>
        <a href="#" onclick="showLoginModal()">Adopt Dog</a>
        <a href="#contact-section">Contact</a>
    </nav> -->
    <div id="loginModal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
        <div
            style="background:white; padding:2rem; border-radius:8px; width:300px; text-align:center; position:relative;">

            <!-- Tabs -->
            <div>
                <button onclick="showTab('login')" id="loginTab"
                    style="margin-right: 10px;">Login</button>
                <button onclick="showTab('signup')" id="signupTab">Sign
                    Up</button>
            </div>



            <!-- Login Form -->
            <form action="login.php" method="post">
                <div id="loginForm">
                    <h2>Login</h2>
                    <input type="text" name="email" placeholder="Email"
                        style="width:100%; padding:0.5rem; margin-bottom:1rem;" required><br>

                    <input type="password" name="password" placeholder="Password"
                        style="width:100%; padding:0.5rem; margin-bottom:1rem;" required><br>

                    <button type="submit" style="padding:0.5rem 1rem;">Sign In</button>
                </div>
            </form>


            <!-- Sign Up Form -->
            <form action="signup.php" method="POST">
                <div id="signupForm" style="display:none;">
                    <h2>Sign Up</h2>
                    <input type="text" name="username" placeholder="Username"
                        style="width:100%; padding:0.5rem; margin-bottom:1rem;"><br>
                    <input type="email" name="email" placeholder="Email"
                        style="width:100%; padding:0.5rem; margin-bottom:1rem;"><br>
                    <input type="password" name="password" placeholder="Password"
                        style="width:100%; padding:0.5rem; margin-bottom:1rem;"><br>

                    <button style="padding:0.5rem 1rem;">Register</button>
            </form>
        </div>

        <!-- Close Button -->
        <button onclick="hideLoginModal()"
            style="background:red; color:white; padding:0.5rem; position:absolute; top:10px; right:10px;">X</button>
    </div>
    </div>




    <div id="page-slide-container">
        <button onclick="closeSlide()"
            style="position: absolute; top: 10px; right: 20px; padding: 10px">
            Close
        </button>
        <div id="slide-content" style="padding: 60px 20px"></div>
    </div>

    <div class="hero-image">
        <div class="hero-text">
            <h1>Adopt. Love. Repeat.</h1>
            <p>Every dog deserves a forever home. Make a difference today!</p>
        </div>
    </div>
    <section class="adoption-option-section">
        <div>
            <div class="adoption">
                <div class="adoption-option">
                    <i class="fa-solid fa-paw"></i>
                    <h3>Find Your Perfect Companion</h3>
                    <p>Browse our list of adorable dogs looking for a forever home.</p>
                </div>
                <div class="adoption-option">
                    <i class="fa-regular fa-heart"></i>
                    <h3>Simple Adoption Process</h3>
                    <p>Fill out adoption forms and we'll guide you through the next steps.</p>
                </div>
                <div class="adoption-option">
                    <i class="fa-solid fa-shield"></i>
                    <h3>Safe and Verified</h3>
                    <p>All our dogs are health-checked and cared befored adoption.</p>
                </div>
                <div class="adoption-option">
                    <i class="fa-solid fa-house"></i>
                    <h3>Make a Difference </h3>
                    <p>Your are not just adopting a pet, you're giving life full of love and happiness.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- end of adoption option section  -->

    <section class="available-dogs">

        <div class="section-heading">
            <span class="section-subtitle">MEET OUR DOGS</span>
            <h2>Find Your New Best Friend</h2>
            <p>Meet some of the lovely dogs waiting for their forever home.</p>
        </div>

        <div class="dog-container">

            <?php while ($dog = mysqli_fetch_assoc($result)) { ?>

                <div class="dog-card">

                    <div class="dog-image">
                        <img
                            src="<?php echo htmlspecialchars($dog['dog_image']); ?>"
                            alt="<?php echo htmlspecialchars($dog['dog_breed']); ?>">
                    </div>
                    <div class="dog-details">

                        <h3>
                            <?php echo htmlspecialchars($dog['dog_breed']); ?>
                        </h3>

                        <div class="dog-info">
                            <span>
                                <i class="fa-solid fa-paw"></i>
                                <?php echo htmlspecialchars($dog['age']); ?> years
                            </span>
                        </div>

                        <!-- <a href="dog-details.php?id=<?php echo $dog['dog_id']; ?>"
                            class="view-dog-btn">
                            View Dog
                            <i class="fa-solid fa-arrow-right"></i>
                        </a> -->

                    </div>

                </div>

            <?php } ?>

        </div>

        <!-- Button below all 4 dogs -->
        <div class="view-all-container">
            <a href="availabledogs.php" class="view-all-btn">
                View All Dogs
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    </section>


    <section class="why-adopt">

        <div class="why-container">

            <!-- Image -->
            <div class="why-image">

                <img
                    src="assets/images/homepage-footer.webp"
                    alt="Happy dog being hugged">

                <div class="heart-decoration">♡</div>

                <div class="purple-brush"></div>

            </div>


            <!-- Text -->
            <div class="why-content">

                <div class="section-label">
                    <h3>ABOUT HAPPY TAILS</h3>
                </div>

                <h2>
                    Why Adopt?
                </h2>

                <p class="description">
                    Adoption saves lives. It gives dogs a second chance,
                    and it brings unconditional love into your life.
                    When you adopt from Happy Tails, you're not just
                    getting a pet — you're gaining a loyal friend,
                    a new family member, and a lifetime of happiness.
                </p>


                <!-- Benefits -->
                <div class="benefits">

                    <div class="benefit">
                        <div class="benefit-icon">♧</div>
                        <span>Save a life</span>
                    </div>

                    <div class="benefit">
                        <div class="benefit-icon">♡</div>
                        <span>Get unconditional love</span>
                    </div>

                    <div class="benefit">
                        <div class="benefit-icon">⌂</div>
                        <span>Build a better tomorrow</span>
                    </div>

                </div>


                <a href="#" class="story-button">
                    Our Story
                    <span>→</span>
                </a>

            </div>

        </div>

    </section>


    <!-- =========================
         CALL TO ACTION
    ========================== -->

    <section class="cta-section">

        <div class="cta-box">

            <!-- <div class="paw paw-1">✣</div>
            <div class="paw paw-2">✣</div>
            <div class="paw paw-3">✣</div>
            <div class="paw paw-4">✣</div> -->

            <!-- Dog icon -->
            <div class="dog-symbol">
                🐶
            </div>

            <div class="cta-text">

                <h2>
                    Ready to change a life?
                </h2>

                <p>
                    Adopt a dog today and be a reason for their happy tomorrow.
                </p>

            </div>

            <a href="availabledogs.php" class="browse-button">
                Browse Available Dogs
                <span>→</span>
            </a>

        </div>

    </section>

    <!-- <footer class="simple-footer">
        <div class="footer-content" id="contact-section">
            <div class="footer-content">
                <p>&copy; 2025 Happy Tails Dog Adoption</p>
                <p>Email: info@dogadoption.org | Phone: (123) 456-7890</p>
            </div>
        </div>
    </footer> -->

</body>


</html>
<?php require __DIR__ . '/includes/footer.php'; ?>