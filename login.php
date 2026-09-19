<?php require __DIR__ . '/includes/header.php';
require_once __DIR__ . "/admin/dataconnection.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">

    <title>Sign In / Sign Up</title>
    <style>

    </style>
</head>

<body>
    <section class="main-form-container">

        <div class="login-container" id="loginContainer">
            <div class="login-form-panel login-form-panel--signup">
                <form class="login-form" onsubmit="return false;">
                    <h1 class="login-title">Create Account</h1>
                    <!-- <div class="login-social">
                        <a href="#" class="login-social-link" aria-label="Sign up with Facebook">f</a>
                        <a href="#" class="login-social-link" aria-label="Sign up with Google">G+</a>
                        <a href="#" class="login-social-link" aria-label="Sign up with LinkedIn">in</a>
                    </div>
                    <span class="login-subtext">or use your email for registration</span> -->
                    <input type="text" class="login-input" placeholder="Name" autocomplete="name">
                    <input type="email" class="login-input" placeholder="Email" autocomplete="email">
                    <input type="password" class="login-input" placeholder="Password" autocomplete="new-password">
                    <button type="submit" class="login-button">Sign Up</button>
                </form>
            </div>

            <div class="login-form-panel login-form-panel--signin">
                <form class="login-form" onsubmit="return false;">
                    <h1 class="login-title">Log In Form</h1>
                    <!-- <div class="login-social">
                        <a href="#" class="login-social-link" aria-label="Sign in with Facebook">f</a>
                        <a href="#" class="login-social-link" aria-label="Sign in with Google">G+</a>
                        <a href="#" class="login-social-link" aria-label="Sign in with LinkedIn">in</a>
                    </div> -->
                    <!-- <span class="login-subtext">or use your account</span> -->
                    <input type="email" class="login-input" placeholder="Email" autocomplete="email">
                    <input type="password" class="login-input" placeholder="Password" autocomplete="current-password">
                    <a href="#" class="login-link">Forgot your password?</a>
                    <button type="submit" class="login-button">Log In</button>
                </form>
            </div>

            <div class="login-overlay-container">
                <div class="login-overlay">
                    <div class="login-overlay-panel login-overlay-panel--left">
                        <h3 class="login-title">Welcome Back!</h3>
                        <p class="login-text">Already have an account?</p>
                        <button class="login-button login-button--ghost" id="loginSignIn">Log in</button>
                    </div>
                    <div class="login-overlay-panel login-overlay-panel--right">
                        <h3 class="login-title">Welcome Back!</h3>
                        <p class="login-text">Create an account?</p>
                        <button class="login-button login-button--ghost" id="loginSignUp">Sign Up</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const loginContainer = document.getElementById('loginContainer');
            document.getElementById('loginSignUp').addEventListener('click', () => {
                loginContainer.classList.add('login-container--active');
            });
            document.getElementById('loginSignIn').addEventListener('click', () => {
                loginContainer.classList.remove('login-container--active');
            });
        </script>

    </section>
</body>

</html>