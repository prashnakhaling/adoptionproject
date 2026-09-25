<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

require_once __DIR__ . '/admin/dataconnection.php';


/* =========================================================
   PHPMailer
   ========================================================= */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';


/* =========================================================
   LOGIN CHECK
   ========================================================= */

if (
    empty($_SESSION['logged_in']) ||
    empty($_SESSION['user_email'])
) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   USER INFORMATION
   ========================================================= */

$userEmail = $_SESSION['user_email'];

$userName = '';

if (!empty($_SESSION['user_name'])) {
    $userName = $_SESSION['user_name'];
} elseif (!empty($_SESSION['username'])) {
    $userName = $_SESSION['username'];
} elseif (!empty($_SESSION['name'])) {
    $userName = $_SESSION['name'];
} elseif (!empty($_SESSION['user'])) {
    $userName = $_SESSION['user'];
}

if ($userName === '') {
    $userName = 'User';
}


/* =========================================================
   HELPER: IMAGE PATH
   ========================================================= */

function getDogImagePath($imagePath)
{
    $imagePath = trim((string)$imagePath);

    if ($imagePath === '') {
        return 'placeholder.jpg';
    }

    $imagePath = str_replace('\\', '/', $imagePath);

    $imagePath = preg_replace('#^\./+#', '', $imagePath);

    $imagePath = ltrim($imagePath, '/');


    /* Full relative path already stored */

    if (is_file(__DIR__ . '/' . $imagePath)) {
        return $imagePath;
    }


    /* Only filename stored */

    if (strpos($imagePath, '/') === false) {

        $dogPicPath = 'dogpic/' . $imagePath;

        if (is_file(__DIR__ . '/' . $dogPicPath)) {
            return $dogPicPath;
        }


        $imagesPath = 'images/' . $imagePath;

        if (is_file(__DIR__ . '/' . $imagesPath)) {
            return $imagesPath;
        }
    }


    return 'placeholder.jpg';
}


/* =========================================================
   HELPER: CHECK DOG TABLE COLUMNS
   ========================================================= */

$dogColumns = [];

$columnResult = $conn->query("SHOW COLUMNS FROM dogs");

if ($columnResult) {

    while ($column = $columnResult->fetch_assoc()) {

        $dogColumns[] = $column['Field'];
    }
}


/* =========================================================
   HELPER: COLUMN EXISTS
   ========================================================= */

function dogColumnExists($column)
{
    global $dogColumns;

    return in_array($column, $dogColumns, true);
}


/* =========================================================
   AJAX RECOMMENDATION
   ========================================================= */

if (isset($_GET['ajax_recommend'])) {

    /*
    IMPORTANT:
    No HTML should be output before this JSON response.
    */

    header('Content-Type: application/json; charset=UTF-8');

    $search = trim($_GET['ajax_recommend']);


    /* Empty search */

    if ($search === '') {

        echo json_encode([
            'success' => false,
            'message' => 'Please enter something to search.',
            'dogs' => []
        ]);

        exit;
    }


    /* =====================================================
       BUILD QUERY USING EXISTING COLUMNS
       ===================================================== */

    $selectFields = [
        "dog_id",
        "dog_breed"
    ];


    if (dogColumnExists('dog_image')) {
        $selectFields[] = "dog_image";
    } else {
        $selectFields[] = "'' AS dog_image";
    }


    if (dogColumnExists('age')) {
        $selectFields[] = "age";
    } else {
        $selectFields[] = "'' AS age";
    }


    if (dogColumnExists('size')) {
        $selectFields[] = "size";
    } else {
        $selectFields[] = "'' AS size";
    }


    if (dogColumnExists('gender')) {
        $selectFields[] = "gender";
    } else {
        $selectFields[] = "'' AS gender";
    }


    if (dogColumnExists('description')) {
        $selectFields[] = "description";
    } else {
        $selectFields[] = "'' AS description";
    }


    $sql = "SELECT "
        . implode(", ", $selectFields)
        . " FROM dogs";


    /* Order */

    if (dogColumnExists('added_date')) {

        $sql .= " ORDER BY added_date DESC";
    } else {

        $sql .= " ORDER BY dog_id DESC";
    }


    $result = $conn->query($sql);


    /* Database error */

    if (!$result) {

        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error,
            'dogs' => []
        ]);

        exit;
    }


    /* =====================================================
       SEARCH TEXT
       ===================================================== */

    $searchLower = strtolower($search);

    $words = preg_split(
        '/\s+/',
        $searchLower,
        -1,
        PREG_SPLIT_NO_EMPTY
    );


    $recommendations = [];


    /* =====================================================
       SCORE DOGS
       ===================================================== */

    while ($dog = $result->fetch_assoc()) {

        $breed = strtolower(
            trim((string)$dog['dog_breed'])
        );

        $age = strtolower(
            trim((string)$dog['age'])
        );

        $size = strtolower(
            trim((string)$dog['size'])
        );

        $gender = strtolower(
            trim((string)$dog['gender'])
        );

        $description = strtolower(
            trim((string)$dog['description'])
        );


        $score = 0;


        /* =================================================
           EXACT BREED
           ================================================= */

        if ($breed === $searchLower) {

            $score += 100;
        } elseif (
            $breed !== '' &&
            strpos($breed, $searchLower) !== false
        ) {

            $score += 80;
        }


        /* =================================================
           WORD-BY-WORD MATCHING
           ================================================= */

        foreach ($words as $word) {

            if ($word === '') {
                continue;
            }


            /* Breed */

            if (
                $breed !== '' &&
                strpos($breed, $word) !== false
            ) {

                $score += 40;
            }


            /* Size */

            if (
                $size !== '' &&
                strpos($size, $word) !== false
            ) {

                $score += 25;
            }


            /* Gender */

            if (
                $gender !== '' &&
                strpos($gender, $word) !== false
            ) {

                $score += 25;
            }


            /* Age */

            if (
                $age !== '' &&
                strpos($age, $word) !== false
            ) {

                $score += 20;
            }


            /* Description */

            if (
                $description !== '' &&
                strpos($description, $word) !== false
            ) {

                $score += 15;
            }
        }


        /* =================================================
           ADD MATCHED DOG
           ================================================= */

        if ($score > 0) {

            $image = getDogImagePath(
                $dog['dog_image']
            );


            $recommendations[] = [

                'dog_id' => (int)$dog['dog_id'],

                'dog_breed' =>
                $dog['dog_breed'],

                'age' =>
                $dog['age'],

                'size' =>
                $dog['size'],

                'gender' =>
                $dog['gender'],

                'description' =>
                $dog['description'],

                'image_url' =>
                $image,

                'score' =>
                min($score, 100)
            ];
        }
    }


    /* =====================================================
       SORT BY SCORE
       ===================================================== */

    usort(
        $recommendations,
        function ($a, $b) {

            if ($a['score'] == $b['score']) {

                return
                    $b['dog_id']
                    <=>
                    $a['dog_id'];
            }

            return
                $b['score']
                <=>
                $a['score'];
        }
    );


    /* =====================================================
       TOP 6
       ===================================================== */

    $recommendations =
        array_slice(
            $recommendations,
            0,
            6
        );


    /* =====================================================
       FALLBACK
       
       If search does not match any dog,
       show latest available dogs.
       ===================================================== */

    if (count($recommendations) === 0) {

        $fallbackSql = "SELECT "
            . implode(", ", $selectFields)
            . " FROM dogs";

        if (dogColumnExists('added_date')) {

            $fallbackSql .=
                " ORDER BY added_date DESC";
        } else {

            $fallbackSql .=
                " ORDER BY dog_id DESC";
        }

        $fallbackSql .= " LIMIT 6";


        $fallbackResult =
            $conn->query($fallbackSql);


        if ($fallbackResult) {

            while (
                $dog =
                $fallbackResult->fetch_assoc()
            ) {

                $image =
                    getDogImagePath(
                        $dog['dog_image']
                    );


                $recommendations[] = [

                    'dog_id' =>
                    (int)$dog['dog_id'],

                    'dog_breed' =>
                    $dog['dog_breed'],

                    'age' =>
                    $dog['age'],

                    'size' =>
                    $dog['size'],

                    'gender' =>
                    $dog['gender'],

                    'description' =>
                    $dog['description'],

                    'image_url' =>
                    $image,

                    'score' => 0
                ];
            }
        }
    }


    /* =====================================================
       SEND JSON
       ===================================================== */

    echo json_encode(
        [
            'success' => true,

            'search' => $search,

            'count' =>
            count($recommendations),

            'dogs' =>
            $recommendations
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   ADOPTION APPLICATION
   ========================================================= */

$successMessage = '';
$errorMessage = '';


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_adoption'])
) {


    /* =====================================================
       FORM DATA
       ===================================================== */

    $ownerName =
        trim($_POST['owner_name'] ?? '');

    $dogId =
        (int)($_POST['dog_id'] ?? 0);

    $dogBreed =
        trim($_POST['dog_breed'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');

    $address =
        trim($_POST['address'] ?? '');

    $reason =
        trim($_POST['reason'] ?? '');


    /* =====================================================
       VALIDATION
       ===================================================== */

    if (
        $ownerName === '' ||
        $dogId <= 0 ||
        $dogBreed === '' ||
        $phone === '' ||
        $address === '' ||
        $reason === ''
    ) {

        $errorMessage =
            "Please fill in all fields.";
    } elseif (
        !preg_match(
            '/^(97|98)[0-9]{8}$/',
            $phone
        )
    ) {

        $errorMessage =
            "Please enter a valid Nepal mobile number.";
    } else {


        /* =================================================
           CHECK APPLICATION TABLE
           ================================================= */

        $checkTable =
            $conn->query(
                "SHOW TABLES LIKE 'adoption_applications'"
            );


        if (
            !$checkTable ||
            $checkTable->num_rows === 0
        ) {

            $errorMessage =
                "The adoption_applications table does not exist.";
        } else {


            /* =================================================
               INSERT APPLICATION
               ================================================= */

            $stmt = $conn->prepare(
                "INSERT INTO adoption_applications
                (
                    owner_name,
                    dog_id,
                    dog_breed,
                    phone,
                    address,
                    reason
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );


            if (!$stmt) {

                $errorMessage =
                    "Database prepare error: "
                    . $conn->error;
            } else {

                $stmt->bind_param(
                    "sissss",
                    $ownerName,
                    $dogId,
                    $dogBreed,
                    $phone,
                    $address,
                    $reason
                );


                if ($stmt->execute()) {

                    $successMessage =
                        "Your adoption application has been submitted successfully!";


                    /* =========================================
                       EMAIL ADMIN
                       ========================================= */

                    try {

                        $mail =
                            new PHPMailer(true);


                        $mail->isSMTP();

                        $mail->Host =
                            'smtp.gmail.com';

                        $mail->SMTPAuth =
                            true;

                        $mail->Username =
                            'happytailsnepal@gmail.com';

                        /*
                         IMPORTANT:
                         Replace this with your Gmail App Password.
                        */

                        $mail->Password =
                            'YOUR_GMAIL_APP_PASSWORD';


                        $mail->SMTPSecure =
                            PHPMailer::ENCRYPTION_STARTTLS;

                        $mail->Port =
                            587;


                        $mail->setFrom(
                            'happytailsnepal@gmail.com',
                            'Happy Tails Dog Adoption'
                        );


                        $mail->addAddress(
                            'happytailsnepal@gmail.com'
                        );


                        $mail->isHTML(true);


                        $mail->Subject =
                            'New Dog Adoption Application';


                        $mail->Body = "

                            <h2>New Adoption Application</h2>

                            <p>
                                <strong>Applicant Name:</strong>
                                " .
                            htmlspecialchars(
                                $ownerName
                            ) .
                            "
                            </p>

                            <p>
                                <strong>Email:</strong>
                                " .
                            htmlspecialchars(
                                $userEmail
                            ) .
                            "
                            </p>

                            <p>
                                <strong>Phone:</strong>
                                " .
                            htmlspecialchars(
                                $phone
                            ) .
                            "
                            </p>

                            <p>
                                <strong>Address:</strong>
                                " .
                            nl2br(
                                htmlspecialchars(
                                    $address
                                )
                            ) .
                            "
                            </p>

                            <p>
                                <strong>Dog:</strong>
                                " .
                            htmlspecialchars(
                                $dogBreed
                            ) .
                            "
                            </p>

                            <p>
                                <strong>Reason:</strong>
                                " .
                            nl2br(
                                htmlspecialchars(
                                    $reason
                                )
                            ) .
                            "
                            </p>

                        ";


                        $mail->send();
                    } catch (Exception $e) {

                        /*
                        Application is already saved.
                        Email error is not shown to the user.
                        */
                    }


                    $stmt->close();
                } else {

                    $errorMessage =
                        "Application could not be submitted: "
                        . $stmt->error;

                    $stmt->close();
                }
            }
        }
    }
}


/* =========================================================
   GET ALL AVAILABLE DOGS
   ========================================================= */

$availableDogs = [];


$availableSelectFields = [

    "dog_id",

    "dog_breed"
];


if (dogColumnExists('dog_image')) {

    $availableSelectFields[] =
        "dog_image";
} else {

    $availableSelectFields[] =
        "'' AS dog_image";
}


if (dogColumnExists('age')) {

    $availableSelectFields[] =
        "age";
} else {

    $availableSelectFields[] =
        "'' AS age";
}


if (dogColumnExists('size')) {

    $availableSelectFields[] =
        "size";
} else {

    $availableSelectFields[] =
        "'' AS size";
}


if (dogColumnExists('gender')) {

    $availableSelectFields[] =
        "gender";
} else {

    $availableSelectFields[] =
        "'' AS gender";
}


if (dogColumnExists('description')) {

    $availableSelectFields[] =
        "description";
} else {

    $availableSelectFields[] =
        "'' AS description";
}


$availableSql =
    "SELECT "
    . implode(
        ", ",
        $availableSelectFields
    )
    . " FROM dogs";


if (dogColumnExists('added_date')) {

    $availableSql .=
        " ORDER BY added_date DESC";
} else {

    $availableSql .=
        " ORDER BY dog_id DESC";
}


$availableResult =
    $conn->query($availableSql);


if ($availableResult) {

    while (
        $dog =
        $availableResult->fetch_assoc()
    ) {

        $dog['image_url'] =
            getDogImagePath(
                $dog['dog_image']
            );

        $availableDogs[] =
            $dog;
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

    <title>
        Happy Tails - User Dashboard
    </title>


    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #f5f6ff;

            color:
                #333;
        }


        /* =================================================
           NAVBAR
           ================================================= */

        .navbar {

            width: 100%;

            background:
                linear-gradient(135deg,
                    #7a83be,
                    #5f67a3);

            padding:
                16px 40px;

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            color:
                white;

            position:
                sticky;

            top:
                0;

            z-index:
                1000;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.12);
        }


        .logo {

            font-size:
                24px;

            font-weight:
                bold;
        }


        .nav-right {

            display:
                flex;

            align-items:
                center;

            gap:
                20px;
        }


        .user-name {

            font-size:
                15px;
        }


        .logout-btn {

            text-decoration:
                none;

            color:
                white;

            background:
                rgba(255, 255, 255, 0.18);

            padding:
                9px 16px;

            border-radius:
                8px;

            transition:
                0.3s;
        }


        .logout-btn:hover {

            background:
                rgba(255, 255, 255, 0.3);
        }


        /* =================================================
           MAIN
           ================================================= */

        .container {

            width:
                92%;

            max-width:
                1200px;

            margin:
                35px auto;
        }


        .welcome {

            text-align:
                center;

            margin-bottom:
                30px;
        }


        .welcome h1 {

            color:
                #5f67a3;

            margin-bottom:
                8px;
        }


        .welcome p {

            color:
                #666;
        }


        /* =================================================
           MESSAGES
           ================================================= */

        .message {

            padding:
                15px 18px;

            border-radius:
                10px;

            margin-bottom:
                20px;

            text-align:
                center;
        }


        .success-message {

            background:
                #e7f8ed;

            color:
                #24733b;

            border:
                1px solid #b9e8c7;
        }


        .error-message {

            background:
                #ffe9e9;

            color:
                #a33333;

            border:
                1px solid #f1bcbc;
        }


        /* =================================================
           SEARCH
           ================================================= */

        .search-box {

            background:
                white;

            padding:
                25px;

            border-radius:
                16px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);

            margin-bottom:
                30px;
        }


        .search-box h2 {

            color:
                #5f67a3;

            margin-bottom:
                15px;

            text-align:
                center;
        }


        .search-area {

            display:
                flex;

            gap:
                10px;

            max-width:
                750px;

            margin:
                auto;
        }


        .search-area input {

            flex:
                1;

            padding:
                14px 18px;

            border:
                1px solid #d0d0d0;

            border-radius:
                10px;

            font-size:
                16px;

            outline:
                none;
        }


        .search-area input:focus {

            border-color:
                #6f75b5;
        }


        .search-area button {

            padding:
                14px 25px;

            border:
                none;

            border-radius:
                10px;

            background:
                #6f75b5;

            color:
                white;

            font-size:
                16px;

            cursor:
                pointer;
        }


        .search-area button:hover {

            background:
                #5f67a3;
        }


        /* =================================================
           RECOMMENDATIONS
           ================================================= */

        #recommendationSection {

            display:
                none;

            margin-bottom:
                45px;
        }


        .recommendation-title {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            margin-bottom:
                20px;
        }


        .recommendation-title h2 {

            color:
                #5f67a3;
        }


        #resultCount {

            color:
                #777;

            font-size:
                14px;
        }


        .dog-grid {

            display:
                grid;

            grid-template-columns:
                repeat(auto-fit,
                    minmax(240px, 1fr));

            gap:
                22px;
        }


        /* =================================================
           DOG CARD
           ================================================= */

        .dog-card {

            background:
                white;

            border-radius:
                16px;

            overflow:
                hidden;

            box-shadow:
                0 5px 18px rgba(0, 0, 0, 0.09);

            transition:
                0.3s;
        }


        .dog-card:hover {

            transform:
                translateY(-5px);

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.13);
        }


        .dog-card img {

            width:
                100%;

            height:
                220px;

            object-fit:
                cover;

            display:
                block;
        }


        .dog-content {

            padding:
                18px;
        }


        .dog-content h3 {

            color:
                #5f67a3;

            margin-bottom:
                12px;

            font-size:
                20px;
        }


        .dog-info {

            color:
                #555;

            margin:
                7px 0;

            font-size:
                14px;
        }


        .match-score {

            display:
                inline-block;

            margin-top:
                10px;

            padding:
                5px 10px;

            border-radius:
                20px;

            background:
                #eef2ff;

            color:
                #5f67a3;

            font-size:
                12px;

            font-weight:
                bold;
        }


        .adopt-btn {

            width:
                100%;

            margin-top:
                15px;

            padding:
                12px;

            border:
                none;

            border-radius:
                8px;

            background:
                #6f75b5;

            color:
                white;

            font-size:
                15px;

            cursor:
                pointer;
        }


        .adopt-btn:hover {

            background:
                #5f67a3;
        }


        /* =================================================
           NO RESULT
           ================================================= */

        .no-result {

            grid-column:
                1 / -1;

            text-align:
                center;

            background:
                white;

            padding:
                35px;

            border-radius:
                12px;

            color:
                #666;
        }


        /* =================================================
           AVAILABLE DOGS
           ================================================= */

        .section-title {

            color:
                #5f67a3;

            margin-bottom:
                20px;
        }


        .available-section {

            margin-bottom:
                50px;
        }


        /* =================================================
           ADOPTION FORM
           ================================================= */

        .adoption-section {

            background:
                white;

            padding:
                30px;

            border-radius:
                16px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);

            margin-bottom:
                50px;
        }


        .adoption-section h2 {

            color:
                #5f67a3;

            margin-bottom:
                25px;

            text-align:
                center;
        }


        .form-grid {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                18px;
        }


        .form-group {

            display:
                flex;

            flex-direction:
                column;

            gap:
                7px;
        }


        .form-group.full {

            grid-column:
                1 / -1;
        }


        .form-group label {

            font-weight:
                bold;

            font-size:
                14px;

            color:
                #444;
        }


        .form-group input,
        .form-group textarea {

            padding:
                12px 14px;

            border:
                1px solid #d0d0d0;

            border-radius:
                8px;

            font-size:
                15px;

            outline:
                none;
        }


        .form-group input:focus,
        .form-group textarea:focus {

            border-color:
                #6f75b5;
        }


        .form-group textarea {

            min-height:
                110px;

            resize:
                vertical;
        }


        .submit-btn {

            grid-column:
                1 / -1;

            padding:
                14px;

            border:
                none;

            border-radius:
                9px;

            background:
                #6f75b5;

            color:
                white;

            font-size:
                16px;

            cursor:
                pointer;
        }


        .submit-btn:hover {

            background:
                #5f67a3;
        }


        /* =================================================
           LOADING
           ================================================= */

        .loading {

            grid-column:
                1 / -1;

            text-align:
                center;

            padding:
                30px;

            color:
                #666;
        }


        /* =================================================
           FOOTER
           ================================================= */

        footer {

            text-align:
                center;

            padding:
                25px;

            background:
                #5f67a3;

            color:
                white;

            margin-top:
                30px;
        }


        /* =================================================
           MOBILE
           ================================================= */

        @media(max-width: 700px) {

            .navbar {

                padding:
                    14px 20px;
            }


            .nav-right {

                gap:
                    8px;
            }


            .user-name {

                display:
                    none;
            }


            .search-area {

                flex-direction:
                    column;
            }


            .search-area button {

                width:
                    100%;
            }


            .form-grid {

                grid-template-columns:
                    1fr;
            }


            .form-group.full {

                grid-column:
                    auto;
            }


            .submit-btn {

                grid-column:
                    auto;
            }


            .recommendation-title {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                gap:
                    5px;
            }
        }
    </style>

</head>


<body>


    <!-- =====================================================
     NAVBAR
     ===================================================== -->

    <nav class="navbar">

        <div class="logo">
            🐾 Happy Tails
        </div>

        <div class="nav-right">

            <span class="user-name">
                Welcome, <?= htmlspecialchars($userName) ?>
            </span>

            <a
                href="logout.php"
                class="logout-btn">
                Logout
            </a>

        </div>

    </nav>


    <!-- =====================================================
     MAIN
     ===================================================== -->

    <main class="container">


        <div class="welcome">

            <h1>
                Find Your Perfect Companion 🐶
            </h1>

            <p>
                Search for a dog and get recommendations based on your search.
            </p>

        </div>


        <!-- =================================================
         MESSAGES
         ================================================= -->

        <?php if ($successMessage !== ''): ?>

            <div class="message success-message">

                <?= htmlspecialchars($successMessage) ?>

            </div>

        <?php endif; ?>


        <?php if ($errorMessage !== ''): ?>

            <div class="message error-message">

                <?= htmlspecialchars($errorMessage) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
         SEARCH
         ================================================= -->

        <div class="search-box">

            <h2>
                🔍 Search for a Dog
            </h2>


            <div class="search-area">

                <input
                    type="text"
                    id="recommendationInput"
                    placeholder="Search breed, age, size, gender..."
                    autocomplete="off">


                <button
                    type="button"
                    onclick="searchDogs()">
                    Search
                </button>

            </div>

        </div>


        <!-- =================================================
         RECOMMENDED DOGS
         ================================================= -->

        <section
            id="recommendationSection">

            <div class="recommendation-title">

                <h2>
                    🐕 Recommended Dogs
                </h2>

                <span id="resultCount"></span>

            </div>


            <div
                id="recommendationResults"
                class="dog-grid"></div>

        </section>


        <!-- =================================================
         AVAILABLE DOGS
         ================================================= -->

        <section class="available-section">

            <h2 class="section-title">
                🐶 Available Dogs
            </h2>


            <div class="dog-grid">


                <?php if (count($availableDogs) > 0): ?>


                    <?php foreach ($availableDogs as $dog): ?>


                        <div class="dog-card">


                            <img
                                src="<?= htmlspecialchars(
                                            $dog['image_url'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="<?= htmlspecialchars(
                                            $dog['dog_breed']
                                        ) ?>"
                                onerror="
                                this.onerror=null;
                                this.src='placeholder.jpg';
                            ">


                            <div class="dog-content">


                                <h3>

                                    <?= htmlspecialchars(
                                        $dog['dog_breed']
                                    ) ?>

                                </h3>


                                <div class="dog-info">

                                    🐕 Age:
                                    <?= htmlspecialchars(
                                        $dog['age'] ?: 'N/A'
                                    ) ?>

                                </div>


                                <div class="dog-info">

                                    📏 Size:
                                    <?= htmlspecialchars(
                                        $dog['size'] ?: 'N/A'
                                    ) ?>

                                </div>


                                <div class="dog-info">

                                    ⚥ Gender:
                                    <?= htmlspecialchars(
                                        $dog['gender'] ?: 'N/A'
                                    ) ?>

                                </div>


                                <?php if (!empty($dog['description'])): ?>

                                    <div class="dog-info">

                                        <?= htmlspecialchars(
                                            strlen(
                                                $dog['description']
                                            ) > 100
                                                ?
                                                substr(
                                                    $dog['description'],
                                                    0,
                                                    100
                                                ) . '...'
                                                :
                                                $dog['description']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                                <button
                                    type="button"
                                    class="adopt-btn"
                                    onclick="selectDog(
                                    <?= (int)$dog['dog_id'] ?>,
                                    <?= htmlspecialchars(
                                        json_encode(
                                            $dog['dog_breed']
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                )">

                                    Adopt This Dog

                                </button>


                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="no-result">

                        No dogs are currently available.

                    </div>


                <?php endif; ?>


            </div>

        </section>


        <!-- =================================================
         ADOPTION FORM
         ================================================= -->

        <section
            class="adoption-section"
            id="adoptionForm">

            <h2>
                📝 Adoption Application
            </h2>


            <form
                method="POST"
                action="">


                <div class="form-grid">


                    <!-- Name -->

                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="owner_name"
                            value="<?= htmlspecialchars(
                                        $userName
                                    ) ?>"
                            required>

                    </div>


                    <!-- Email -->

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            value="<?= htmlspecialchars(
                                        $userEmail
                                    ) ?>"
                            readonly>

                    </div>


                    <!-- Phone -->

                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone"
                            placeholder="98XXXXXXXX"
                            pattern="(97|98)[0-9]{8}"
                            maxlength="10"
                            required>

                    </div>


                    <!-- Dog -->

                    <div class="form-group">

                        <label>
                            Dog to Adopt
                        </label>

                        <input
                            type="hidden"
                            id="dog_id"
                            name="dog_id"
                            required>


                        <input
                            type="text"
                            id="dog_breed"
                            name="dog_breed"
                            placeholder="Select a dog above"
                            readonly
                            required>

                    </div>


                    <!-- Address -->

                    <div class="form-group full">

                        <label>
                            Home Address
                        </label>

                        <textarea
                            name="address"
                            placeholder="Enter your complete address"
                            required></textarea>

                    </div>


                    <!-- Reason -->

                    <div class="form-group full">

                        <label>
                            Why do you want to adopt this dog?
                        </label>

                        <textarea
                            name="reason"
                            placeholder="Tell us why you want to adopt this dog..."
                            required></textarea>

                    </div>


                    <!-- Submit -->

                    <button
                        type="submit"
                        name="submit_adoption"
                        class="submit-btn">

                        Submit Adoption Application

                    </button>


                </div>

            </form>

        </section>


    </main>


    <!-- =====================================================
     FOOTER
     ===================================================== -->

    <footer>

        © <?= date('Y') ?>
        Happy Tails Dog Adoption

    </footer>


    <!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

    <script>
        /* =====================================================
   SEARCH DOGS
   ===================================================== */

        function searchDogs() {

            const input =
                document.getElementById(
                    "recommendationInput"
                );


            const section =
                document.getElementById(
                    "recommendationSection"
                );


            const results =
                document.getElementById(
                    "recommendationResults"
                );


            const count =
                document.getElementById(
                    "resultCount"
                );


            const keyword =
                input.value.trim();


            /* Empty search */

            if (keyword === "") {

                section.style.display =
                    "none";

                results.innerHTML =
                    "";

                count.innerHTML =
                    "";

                return;
            }


            /* Show section */

            section.style.display =
                "block";


            /* Loading */

            results.innerHTML = `

        <div class="loading">

            🔄 Finding recommended dogs...

        </div>

    `;


            count.innerHTML =
                "";


            /*
            =====================================================
            AJAX REQUEST
            =====================================================
            */

            fetch(
                    "userdashboard.php?ajax_recommend=" +
                    encodeURIComponent(keyword) +
                    "&t=" +
                    Date.now()
                )


                .then(function(response) {

                    if (!response.ok) {

                        throw new Error(
                            "Server error: HTTP " +
                            response.status
                        );

                    }


                    return response.text();

                })


                .then(function(text) {

                    console.log(
                        "Recommendation response:",
                        text
                    );


                    let data;


                    try {

                        data =
                            JSON.parse(text);

                    } catch (error) {

                        console.error(
                            "Invalid JSON:",
                            text
                        );


                        throw new Error(
                            "Server did not return valid JSON. Check PHP errors."
                        );
                    }


                    /*
                    =================================================
                    ERROR
                    =================================================
                    */

                    if (!data.success) {

                        results.innerHTML = `

                <div class="no-result">

                    ❌
                    ${escapeHtml(
                        data.message ||
                        "Unable to find dogs."
                    )}

                </div>

            `;

                        return;
                    }


                    /*
                    =================================================
                    COUNT
                    =================================================
                    */

                    count.innerHTML =
                        data.count +
                        " dog(s) found";


                    /*
                    =================================================
                    NO DOGS
                    =================================================
                    */

                    if (
                        !data.dogs ||
                        data.dogs.length === 0
                    ) {

                        results.innerHTML = `

                <div class="no-result">

                    No dogs are currently available.

                </div>

            `;

                        return;
                    }


                    /*
                    =================================================
                    CLEAR OLD RESULTS
                    =================================================
                    */

                    results.innerHTML =
                        "";


                    /*
                    =================================================
                    CREATE CARDS
                    =================================================
                    */

                    data.dogs.forEach(
                        function(dog) {

                            const card =
                                document.createElement(
                                    "div"
                                );


                            card.className =
                                "dog-card";


                            let description =
                                dog.description ||
                                "Friendly and lovable dog.";


                            if (
                                description.length > 100
                            ) {

                                description =
                                    description.substring(
                                        0,
                                        100
                                    ) +
                                    "...";
                            }


                            let scoreHTML =
                                "";


                            if (
                                Number(dog.score) > 0
                            ) {

                                scoreHTML = `

                        <span class="match-score">

                            ${dog.score}% Match

                        </span>

                    `;

                            } else {

                                scoreHTML = `

                        <span class="match-score">

                            Available Dog

                        </span>

                    `;
                            }


                            card.innerHTML = `

                    <img
                        src="${escapeHtml(
                            dog.image_url
                        )}"
                        alt="${escapeHtml(
                            dog.dog_breed
                        )}"
                        onerror="
                            this.onerror=null;
                            this.src='placeholder.jpg';
                        "
                    >


                    <div class="dog-content">


                        <h3>

                            ${escapeHtml(
                                dog.dog_breed
                            )}

                        </h3>


                        <div class="dog-info">

                            🐕 Age:
                            ${escapeHtml(
                                dog.age || "N/A"
                            )}

                        </div>


                        <div class="dog-info">

                            📏 Size:
                            ${escapeHtml(
                                dog.size || "N/A"
                            )}

                        </div>


                        <div class="dog-info">

                            ⚥ Gender:
                            ${escapeHtml(
                                dog.gender || "N/A"
                            )}

                        </div>


                        <div class="dog-info">

                            ${escapeHtml(
                                description
                            )}

                        </div>


                        ${scoreHTML}


                        <button
                            type="button"
                            class="adopt-btn"
                        >
                            Adopt This Dog
                        </button>


                    </div>

                `;


                            /*
                            =========================================
                            ADOPT BUTTON
                            =========================================
                            */

                            const button =
                                card.querySelector(
                                    ".adopt-btn"
                                );


                            button.addEventListener(
                                "click",
                                function() {

                                    selectDog(
                                        dog.dog_id,
                                        dog.dog_breed
                                    );

                                }
                            );


                            results.appendChild(
                                card
                            );

                        }
                    );

                })


                /*
                =====================================================
                ERROR HANDLER
                =====================================================
                */

                .catch(function(error) {

                    console.error(
                        "Recommendation error:",
                        error
                    );


                    results.innerHTML = `

            <div class="no-result">

                ❌
                Recommendation could not be loaded.

                <br><br>

                <small>

                    ${escapeHtml(
                        error.message
                    )}

                </small>

            </div>

        `;


                    count.innerHTML =
                        "";

                });

        }


        /* =====================================================
           ENTER KEY
           ===================================================== */

        document
            .getElementById(
                "recommendationInput"
            )
            .addEventListener(
                "keydown",
                function(event) {

                    if (
                        event.key === "Enter"
                    ) {

                        event.preventDefault();

                        searchDogs();

                    }

                }
            );


        /* =====================================================
           SELECT DOG
           ===================================================== */

        function selectDog(
            dogId,
            dogBreed
        ) {

            const dogIdInput =
                document.getElementById(
                    "dog_id"
                );


            const dogBreedInput =
                document.getElementById(
                    "dog_breed"
                );


            if (dogIdInput) {

                dogIdInput.value =
                    dogId;

            }


            if (dogBreedInput) {

                dogBreedInput.value =
                    dogBreed;

            }


            /*
            Scroll to adoption form
            */

            const form =
                document.getElementById(
                    "adoptionForm"
                );


            if (form) {

                form.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }

        }


        /* =====================================================
           HTML ESCAPE
           ===================================================== */

        function escapeHtml(value) {

            if (
                value === null ||
                value === undefined
            ) {

                return "";

            }


            return String(value)

                .replace(
                    /&/g,
                    "&amp;"
                )

                .replace(
                    /</g,
                    "&lt;"
                )

                .replace(
                    />/g,
                    "&gt;"
                )

                .replace(
                    /"/g,
                    "&quot;"
                )

                .replace(
                    /'/g,
                    "&#039;"
                );

        }
    </script>


</body>

</html>