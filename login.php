<?php
session_start();
ob_start();

// Database configuration
$host = 'localhost';
$dbname = 'dogadoption';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Both email and password are required.";
        header("Location: homepage.php");
        exit();
    }

    // Try logging in as a regular user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($user) && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['login_success'] = "Welcome, " . htmlspecialchars($user['name']) . "!";
        header("Location: userdashboard.php");
        exit();
    };

    // Try logging in as an admin (plain-text password match)
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['login_success'] = "Welcome, Admin " . htmlspecialchars($admin['name']) . "!";
        header("Location: admindashboard.php");
        exit();
    }
    session_start();

    $_SESSION['user_id'] = $row['id'];
    $_SESSION['username'] = $row['fullname']; // or $row['name'], depending on your database

    // Invalid credentials
    $_SESSION['login_error'] = "Invalid email or password.";
    header("Location: homepage.php");
    exit();
}
