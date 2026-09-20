<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';

$errors = [];

// Keep signup form active when there is a validation error
$activeSignup = false;


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {

    // Keep signup side visible after form submission
    $activeSignup = true;

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    // NAME VALIDATION

    if ($name === '') {

        $errors['name'] = "Name is required.";
    } elseif (strlen($name) < 6) {

        $errors['name'] = "Name must be at least 6 characters long.";
    } elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {

        $errors['name'] = "Name can contain letters and spaces only.";
    } else {

        $stmt = $conn->prepare(
            "SELECT name FROM users WHERE name = ? LIMIT 1"
        );

        $stmt->bind_param("s", $name);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['name'] = "This name is already registered.";
        }

        $stmt->close();
    }


    // EMAIL VALIDATION

    if ($email === '') {

        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors['email'] = "Please enter a valid email address.";
    } elseif (!preg_match("/^[A-Za-z0-9._%+-]+@gmail\.com$/", $email)) {

        $errors['email'] = "Please use a Gmail address ending with @gmail.com.";
    } else {

        $stmt = $conn->prepare(
            "SELECT email FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['email'] = "This email is already registered.";
        }

        $stmt->close();
    }


    // PASSWORD VALIDATION

    if ($password === '') {

        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 8) {

        $errors['password'] =
            "Password must be at least 8 characters long.";
    } elseif (!preg_match("/[0-9]/", $password)) {

        $errors['password'] =
            "Password must contain at least one number.";
    } elseif (!preg_match("/[^A-Za-z0-9]/", $password)) {

        $errors['password'] =
            "Password must contain at least one special character.";
    }


    // INSERT USER

    if (empty($errors)) {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "sss",
            $name,
            $email,
            $hashedPassword
        );

        if ($stmt->execute()) {

            header("Location: login.php?signup=success");
            exit();
        } else {

            $errors['general'] =
                "Unable to create account. Please try again.";
        }

        $stmt->close();
    }
}
?>
<?php require __DIR__ . '/includes/header.php'; ?>

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

        <div class="login-container <?php echo $activeSignup ? 'login-container--active' : ''; ?>" id="loginContainer">
            <div class="login-form-panel login-form-panel--signup">

                <form class="login-form" action="" method="POST">

                    <h1 class="login-title">Create Account</h1>
                    <!-- <span class="login-subtext">or use your email for registration</span> -->

                    <!-- Name -->
                    <input type="text"
                        name="name"
                        class="login-input"
                        placeholder="Name"
                        autocomplete="name"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

                    <?php if (!empty($errors['name'])): ?>
                        <span class="form-error">
                            <?php echo htmlspecialchars($errors['name']); ?>
                        </span>
                    <?php endif; ?>


                    <!-- Email -->
                    <input
                        type="email"
                        name="email"
                        class="login-input"
                        placeholder="Email"
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                    <?php if (!empty($errors['email'])): ?>
                        <span class="form-error">
                            <?php echo htmlspecialchars($errors['email']); ?>
                        </span>
                    <?php endif; ?>


                    <!-- Password -->
                    <input
                        type="password"
                        name="password"
                        class="login-input"
                        placeholder="Password"
                        autocomplete="new-password">

                    <?php if (!empty($errors['password'])): ?>
                        <span class="form-error">
                            <?php echo htmlspecialchars($errors['password']); ?>
                        </span>
                    <?php endif; ?>


                    <button type="submit" name="signup" class="login-button">
                        Sign Up
                    </button>

                </form>

            </div>


            <div class="login-form-panel login-form-panel--signin">
                <form class="login-form">
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