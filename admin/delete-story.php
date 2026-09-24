<?php

session_start();


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
| GET STORY ID
|--------------------------------------------------------------------------
*/

$storyId = (int)(
    $_POST['story_id'] ?? 0
);


if ($storyId > 0) {

    $stmt = $conn->prepare("
        DELETE FROM stories
        WHERE story_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $storyId
        );

        $stmt->execute();

        $stmt->close();
    }
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
