<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
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

    header("Location: admindashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$breed = trim($_POST['dog_breed'] ?? '');
$age = trim($_POST['age'] ?? '');
$description = trim($_POST['description'] ?? '');

$errors = [];


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($breed === '') {

    $errors[] = "Dog breed is required.";
}

if ($age === '') {

    $errors[] = "Dog age is required.";
}


/*
|--------------------------------------------------------------------------
| IMAGE
|--------------------------------------------------------------------------
*/

$imagePath = '';


if (
    isset($_FILES['dog_image']) &&
    $_FILES['dog_image']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['dog_image']['error'] !== UPLOAD_ERR_OK) {

        $errors[] = "There was an error uploading the image.";
    } else {

        $originalName =
            $_FILES['dog_image']['name'];

        $tmpName =
            $_FILES['dog_image']['tmp_name'];

        $extension =
            strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );


        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'jfif'
        ];


        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            $errors[] =
                "Invalid image format.";
        } else {

            $uploadDir =
                __DIR__ . '/../dogpic/';


            if (!is_dir($uploadDir)) {

                mkdir(
                    $uploadDir,
                    0777,
                    true
                );
            }


            $fileName =
                uniqid(
                    'dog_',
                    true
                ) .
                '.' .
                $extension;


            $destination =
                $uploadDir .
                $fileName;


            if (
                move_uploaded_file(
                    $tmpName,
                    $destination
                )
            ) {

                $imagePath =
                    'dogpic/' .
                    $fileName;
            } else {

                $errors[] =
                    "Unable to save uploaded image.";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| INSERT DOG
|--------------------------------------------------------------------------
*/

if (empty($errors)) {

    $stmt = $conn->prepare("
        INSERT INTO dogs
        (
            dog_breed,
            age,
            description,
            dog_image
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ");


    if (!$stmt) {

        die("Database prepare error: " .
            $conn->error);
    }


    $stmt->bind_param(
        "ssss",
        $breed,
        $age,
        $description,
        $imagePath
    );


    if (!$stmt->execute()) {

        if (!empty($imagePath)) {

            $imageFile =
                __DIR__ .
                '/../' .
                $imagePath;

            if (file_exists($imageFile)) {

                @unlink($imageFile);
            }
        }


        die("Unable to add dog: " .
            $stmt->error);
    }


    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(
    "Location: admindashboard.php"
);

exit;
