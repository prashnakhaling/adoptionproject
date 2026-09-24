<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';

$errors = [];

$activeSignup = true;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {

    $activeSignup = false;

    $username = trim($_POST['login_username'] ?? '');
    $password = $_POST['login_password'] ?? '';

    $loginErrors = [];

    if ($username === '') {

        $loginErrors['login_username'] =
            "Username is required.";
    }

    if ($password === '') {

        $loginErrors['login_password'] =
            "Password is required.";
    }
    if ($username !== '') {

        $stmt = $conn->prepare(
            "SELECT name, email, password
             FROM users
             WHERE name = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            $loginErrors['login_general'] =
                "User does not exist.";
        } else {

            $user = $result->fetch_assoc();

            if ($password !== '') {

                if (
                    !password_verify(
                        $password,
                        $user['password']
                    )
                ) {

                    $loginErrors['login_general'] =
                        "Incorrect password.";
                } else {
                    $_SESSION['logged_in'] = true;

                    $_SESSION['user_name'] =
                        $user['name'];

                    $_SESSION['user_email'] =
                        $user['email'];

                    header(
                        "Location: http://adoptionproject.loc/userdashboard.php"
                    );

                    exit();
                }
            }
        }

        $stmt->close();
    }

    if (!empty($loginErrors)) {

        $_SESSION['login_errors'] =
            $loginErrors;

        $_SESSION['login_username'] =
            $username;

        $_SESSION['active_login'] = true;

        header(
            "Location: " . $_SERVER['PHP_SELF']
        );

        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {

    $activeSignup = true;

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {

        $errors['name'] = "Name is required.";
    } elseif (strlen($name) < 6) {

        $errors['name'] =
            "Name must be at least 6 characters long.";
    } elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {

        $errors['name'] =
            "Name can contain letters and spaces only.";
    } else {

        $stmt = $conn->prepare(
            "SELECT name FROM users WHERE name = ? LIMIT 1"
        );

        $stmt->bind_param("s", $name);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $errors['name'] =
                "This name is already registered.";
        }

        $stmt->close();
    }


    // EMAIL VALIDATION

    if ($email === '') {

        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors['email'] =
            "Please enter a valid email address.";
    } elseif (!preg_match(
        "/^[A-Za-z0-9._%+-]+@gmail\.com$/",
        $email
    )) {

        $errors['email'] =
            "Please use a Gmail address ending with @gmail.com.";
    } else {

        $stmt = $conn->prepare(
            "SELECT email FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $errors['email'] =
                "This email is already registered.";
        }

        $stmt->close();
    }


    // PASSWORD VALIDATION

    if ($password === '') {

        $errors['password'] =
            "Password is required.";
    } elseif (strlen($password) < 8) {

        $errors['password'] =
            "Password must be at least 8 characters long.";
    } elseif (!preg_match("/[0-9]/", $password)) {

        $errors['password'] =
            "Password must contain at least one number.";
    } elseif (!preg_match(
        "/[^A-Za-z0-9]/",
        $password
    )) {

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


    /*
     * If there are validation errors,
     * save them temporarily in session.
     */

    if (!empty($errors)) {

        $_SESSION['signup_errors'] = $errors;

        $_SESSION['signup_name'] = $name;

        $_SESSION['signup_email'] = $email;


        /*
         * Redirect to same page.
         */

        header(
            "Location: " . $_SERVER['PHP_SELF']
        );

        exit();
    }
}


/* --------------------------------
   GET LOGIN ERRORS
-------------------------------- */

$loginErrors = [];

$loginUsername = '';

if (isset($_SESSION['login_errors'])) {

    $loginErrors =
        $_SESSION['login_errors'];

    $loginUsername =
        $_SESSION['login_username'] ?? '';

    unset($_SESSION['login_errors']);

    unset($_SESSION['login_username']);
}


/* --------------------------------
   MAKE LOGIN FORM ACTIVE
   WHEN LOGIN HAS ERROR
-------------------------------- */

if (isset($_SESSION['active_login'])) {

    $activeSignup = false;

    unset($_SESSION['active_login']);
}


/* --------------------------------
   GET SIGNUP ERRORS
-------------------------------- */

if (isset($_SESSION['signup_errors'])) {

    $errors =
        $_SESSION['signup_errors'];

    $savedName =
        $_SESSION['signup_name'] ?? '';

    $savedEmail =
        $_SESSION['signup_email'] ?? '';


    unset($_SESSION['signup_errors']);

    unset($_SESSION['signup_name']);

    unset($_SESSION['signup_email']);
} else {

    $savedName = '';

    $savedEmail = '';
}

?>

<?php require __DIR__ . '/includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <link
        rel="stylesheet"
        href="assets/style.css">

    <title>Sign In / Sign Up</title>

</head>


<body>

    <section class="main-form-container">

        <div
            class="login-container <?php
                                    echo $activeSignup
                                        ? 'login-container--active'
                                        : '';
                                    ?>"
            id="loginContainer">


            <!-- =========================================
                 LOGIN FORM
            ========================================== -->

            <div class="login-form-panel login-form-panel--signin">

                <form
                    class="login-form"
                    action=""
                    method="POST">

                    <h1 class="login-title">
                        Log In Form
                    </h1>


                    <!-- USERNAME -->

                    <input
                        type="text"
                        name="login_username"
                        class="login-input"
                        placeholder="Username"
                        autocomplete="username"
                        value="<?php
                                echo htmlspecialchars(
                                    $loginUsername
                                );
                                ?>">


                    <?php if (!empty($loginErrors['login_username'])): ?>

                        <span class="form-error">

                            <?php
                            echo htmlspecialchars(
                                $loginErrors['login_username']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- PASSWORD -->

                    <input
                        type="password"
                        name="login_password"
                        class="login-input"
                        placeholder="Password"
                        autocomplete="current-password">


                    <?php if (!empty($loginErrors['login_password'])): ?>

                        <span class="form-error">

                            <?php
                            echo htmlspecialchars(
                                $loginErrors['login_password']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- DATABASE LOGIN ERROR -->

                    <?php if (!empty($loginErrors['login_general'])): ?>

                        <span class="form-error">

                            <?php
                            echo htmlspecialchars(
                                $loginErrors['login_general']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <a
                        href="#"
                        class="login-link">

                        Forgot your password?

                    </a>


                    <button
                        type="submit"
                        name="login"
                        class="login-button">

                        Log In

                    </button>

                </form>

            </div>


            <!-- =========================================
                 SIGN UP FORM
            ========================================== -->

            <div class="login-form-panel login-form-panel--signup">

                <form
                    class="login-form"
                    action=""
                    method="POST">

                    <h1 class="login-title">
                        Create Account
                    </h1>


                    <!-- Name -->

                    <input
                        type="text"
                        name="name"
                        class="login-input"
                        placeholder="Name"
                        autocomplete="name"
                        value="<?php
                                echo htmlspecialchars(
                                    $savedName
                                );
                                ?>">


                    <?php if (!empty($errors['name'])): ?>

                        <span class="form-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['name']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- Email -->

                    <input
                        type="email"
                        name="email"
                        class="login-input"
                        placeholder="Email"
                        autocomplete="email"
                        value="<?php
                                echo htmlspecialchars(
                                    $savedEmail
                                );
                                ?>">


                    <?php if (!empty($errors['email'])): ?>

                        <span class="form-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['email']
                            );
                            ?>

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

                            <?php
                            echo htmlspecialchars(
                                $errors['password']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <?php if (!empty($errors['general'])): ?>

                        <span class="form-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['general']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <button
                        type="submit"
                        name="signup"
                        class="login-button">

                        Sign Up

                    </button>

                </form>

            </div>


            <!-- =========================================
                 OVERLAY
            ========================================== -->

            <div class="login-overlay-container">

                <div class="login-overlay">


                    <div
                        class="login-overlay-panel login-overlay-panel--right">

                        <h3 class="login-title">
                            Welcome Back!
                        </h3>

                        <p class="login-text">
                            Create an account?
                        </p>

                        <button
                            type="button"
                            class="login-button login-button--ghost"
                            id="loginSignUp">

                            Sign Up

                        </button>

                    </div>


                    <div
                        class="login-overlay-panel login-overlay-panel--left">

                        <h3 class="login-title">
                            Welcome Back!
                        </h3>

                        <p class="login-text">
                            Already have an account?
                        </p>

                        <button
                            type="button"
                            class="login-button login-button--ghost"
                            id="loginSignIn">

                            Log in

                        </button>

                    </div>


                </div>

            </div>

        </div>


        <script>
            const loginContainer =
                document.getElementById(
                    'loginContainer'
                );


            document
                .getElementById('loginSignUp')
                .addEventListener(
                    'click',
                    () => {

                        loginContainer.classList.add(
                            'login-container--active'
                        );

                    }
                );


            document
                .getElementById('loginSignIn')
                .addEventListener(
                    'click',
                    () => {

                        loginContainer.classList.remove(
                            'login-container--active'
                        );

                    }
                );
        </script>


    </section>

</body>

</html>