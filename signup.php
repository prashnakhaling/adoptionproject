<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    // =========================
    // NAME VALIDATION
    // =========================

    if ($name === '') {

        $errors['name'] = "Name is required.";

    } elseif (strlen($name) < 6) {

        $errors['name'] = "Name must be at least 6 characters long.";

    } elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {

        $errors['name'] = "Name can contain letters and spaces only.";

    } else {

        // Check if name already exists
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


    // =========================
    // EMAIL VALIDATION
    // =========================

    if ($email === '') {

        $errors['email'] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors['email'] = "Please enter a valid email address.";

    } elseif (!preg_match("/^[A-Za-z0-9._%+-]+@gmail\.com$/", $email)) {

        $errors['email'] = "Please use a Gmail address ending with @gmail.com.";

    } else {

        // Check if email already exists
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


    // =========================
    // PASSWORD VALIDATION
    // =========================

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


    // =========================
    // INSERT INTO DATABASE
    // =========================

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

            // Account created
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