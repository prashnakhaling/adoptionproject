<?php
ob_start(); // Prevent headers already sent
session_start();

$host = 'localhost';
$dbname = 'dogadoption';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['signup_error'] = "All fields are required.";
        header("Location: homepage.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['signup_error'] = "Invalid email format.";
        header("Location: homepage.php");
        exit();
    }

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['signup_error'] = "Username or email already exists.";
        header("Location: homepage.php");
        exit();
    }

    // Hash password and insert new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");

    if ($stmt->execute([$username, $email, $hashedPassword])) {
        $_SESSION['signup_success'] = "Signup successful! You can now log in.";
        header("Location: homepage.php");
        exit();
    } else {
        $_SESSION['signup_error'] = "Signup failed. Please try again.";
        header("Location: homepage.php");
        exit();
    }
}
