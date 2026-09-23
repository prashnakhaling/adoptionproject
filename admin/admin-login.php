<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/dataconnection.php';

/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {

    header("Location: admindashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN CREDENTIALS
|--------------------------------------------------------------------------
|
| For your college project, these can be kept here.
| In a production system, use a database and password_hash().
|
*/

$adminUsername = "admin";
$adminPassword = "admin123";

$error = "";


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Please enter username and password.";
    } elseif (
        $username === $adminUsername &&
        $password === $adminPassword
    ) {

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;

        header("Location: admindashboard.php");
        exit;
    } else {

        $error = "Invalid admin username or password.";
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Admin Login - Happy Tails</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg,
                    #e8e0f2,
                    #fff8f4);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: #fff;
            padding: 35px;
            border-radius: 20px;
            box-shadow:
                0 15px 40px rgba(0, 0, 0, .12);
        }

        .logo {
            text-align: center;
            font-size: 30px;
            font-weight: 700;
            color: #5a34ae;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #443b55;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 9px;
            outline: none;
            font-size: 14px;
        }

        input:focus {
            border-color: #5a34ae;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 9px;
            background: #5a34ae;
            color: white;
            font-weight: 700;
            cursor: pointer;
            font-size: 15px;
        }

        .login-btn:hover {
            background: #48258f;
        }

        .error {
            background: #ffe8e8;
            color: #c03939;
            padding: 11px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
            text-align: center;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #5a34ae;
            text-decoration: none;
            font-size: 13px;
        }
    </style>

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                🐾 Happy Tails
            </div>

            <div class="subtitle">
                Admin Dashboard Login
            </div>

            <?php if ($error !== ''): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label>
                        Admin Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Enter admin username"
                        required>

                </div>

                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter admin password"
                        required>

                </div>

                <button
                    type="submit"
                    class="login-btn">

                    Login

                </button>

            </form>

            <a
                href="../index.php"
                class="back">

                ← Back to Website

            </a>

        </div>

    </div>

</body>

</html>