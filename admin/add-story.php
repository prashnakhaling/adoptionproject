<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['admin_logged_in']) ||
    $_SESSION['admin_logged_in'] !== true
) {

    header("Location: admin-login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/dataconnection.php';


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: admindashboard.php#stories");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$storyImage = trim(
    $_POST['story_image'] ?? ''
);

$description = trim(
    $_POST['description'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $storyImage === '' ||
    $description === ''
) {

    header("Location: admindashboard.php#stories");
    exit;
}


/*
|--------------------------------------------------------------------------
| SAVE STORY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO stories (
        story_image,
        description
    )
    VALUES (?, ?)
");


if ($stmt) {

    $stmt->bind_param(
        "ss",
        $storyImage,
        $description
    );

    $stmt->execute();

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| RETURN
|--------------------------------------------------------------------------
*/

header(
    "Location: admindashboard.php#stories"
);

exit;
