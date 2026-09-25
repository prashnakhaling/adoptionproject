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
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}


$userEmail = $_SESSION['user_email'] ?? '';
$userName  = $_SESSION['username'] ?? 'User';


/* =========================================================
   IMAGE PATH FUNCTION
   ========================================================= */

function getDogImagePath($imagePath)
{
    $imagePath = trim((string)$imagePath);

    if ($imagePath === '') {
        return 'placeholder.jpg';
    }


    /*
     * Remove leading slash if present
     */

    $imagePath = ltrim($imagePath, '/\\');


    /*
     * If database already contains:
     *
     * dogpic/dog1.jpg
     *
     * check it directly.
     */

    if (file_exists(__DIR__ . '/' . $imagePath)) {
        return $imagePath;
    }


    /*
     * If only filename is stored:
     *
     * dog1.jpg
     */

    if (
        file_exists(
            __DIR__ . '/dogpic/' . $imagePath
        )
    ) {
        return 'dogpic/' . $imagePath;
    }


    /*
     * Check images folder
     */

    if (
        file_exists(
            __DIR__ . '/images/' . $imagePath
        )
    ) {
        return 'images/' . $imagePath;
    }


    /*
     * Check dogpic again using basename
     */

    $filename = basename($imagePath);

    if (
        file_exists(
            __DIR__ . '/dogpic/' . $filename
        )
    ) {
        return 'dogpic/' . $filename;
    }


    return 'placeholder.jpg';
}


/* =========================================================
   DOG COLUMNS
   ========================================================= */

$dogColumns = [];

$columnResult = $conn->query(
    "SHOW COLUMNS FROM dogs"
);

if ($columnResult) {

    while ($column = $columnResult->fetch_assoc()) {

        $dogColumns[] = $column['Field'];
    }
}


/* =========================================================
   COLUMN CHECK
   ========================================================= */

function hasDogColumn($column, $dogColumns)
{
    return in_array(
        $column,
        $dogColumns
    );
}


/* =========================================================
   SEARCH ALGORITHM
   ========================================================= */

function searchDogs(
    $conn,
    $search,
    $dogColumns
) {

    $search = trim($search);

    if ($search === '') {
        return [];
    }


    $searchLike =
        '%' . $search . '%';


    $conditions = [];


    /*
     * Breed
     */

    if (
        hasDogColumn(
            'dog_breed',
            $dogColumns
        )
    ) {

        $conditions[] =
            "dog_breed LIKE ?";
    }


    /*
     * Age
     */

    if (
        hasDogColumn(
            'age',
            $dogColumns
        )
    ) {

        $conditions[] =
            "age LIKE ?";
    }


    /*
     * Size
     */

    if (
        hasDogColumn(
            'size',
            $dogColumns
        )
    ) {

        $conditions[] =
            "size LIKE ?";
    }


    /*
     * Gender
     */

    if (
        hasDogColumn(
            'gender',
            $dogColumns
        )
    ) {

        $conditions[] =
            "gender LIKE ?";
    }


    /*
     * Description
     */

    if (
        hasDogColumn(
            'description',
            $dogColumns
        )
    ) {

        $conditions[] =
            "description LIKE ?";
    }


    if (empty($conditions)) {
        return [];
    }


    $sql = "
        SELECT *
        FROM dogs
        WHERE
        " . implode(
        " OR ",
        $conditions
    );


    /*
     * Order
     */

    if (
        hasDogColumn(
            'added_date',
            $dogColumns
        )
    ) {

        $sql .= "
            ORDER BY added_date DESC
        ";
    } else {

        $sql .= "
            ORDER BY dog_id DESC
        ";
    }


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        error_log(
            "Search Error: "
                . $conn->error
        );

        return [];
    }


    /*
     * Create parameters
     */

    $params = [];

    $types = '';


    foreach (
        $conditions
        as $condition
    ) {

        $params[] =
            $searchLike;

        $types .= 's';
    }


    $stmt->bind_param(
        $types,
        ...$params
    );


    if (!$stmt->execute()) {

        $stmt->close();

        return [];
    }


    $result =
        $stmt->get_result();


    $dogs = [];


    while (
        $row =
        $result->fetch_assoc()
    ) {

        /*
         * IMPORTANT:
         * Convert database image path
         * to correct path.
         */

        $row['image_url'] =
            getDogImagePath(
                $row['dog_image'] ?? ''
            );


        $dogs[] = $row;
    }


    $stmt->close();


    return $dogs;
}


/* =========================================================
   RECOMMENDATION SCORE
   ========================================================= */

function calculateRecommendationScore(
    $dog,
    $search
) {

    $search =
        strtolower(
            trim($search)
        );


    if ($search === '') {
        return 0;
    }


    $score = 0;


    /*
     * Dog information
     */

    $breed =
        strtolower(
            trim(
                (string)
                ($dog['dog_breed'] ?? '')
            )
        );


    $age =
        strtolower(
            trim(
                (string)
                ($dog['age'] ?? '')
            )
        );


    $size =
        strtolower(
            trim(
                (string)
                ($dog['size'] ?? '')
            )
        );


    $gender =
        strtolower(
            trim(
                (string)
                ($dog['gender'] ?? '')
            )
        );


    $description =
        strtolower(
            trim(
                (string)
                ($dog['description'] ?? '')
            )
        );


    /* =====================================================
       EXACT BREED
       ===================================================== */

    if (
        $breed !== '' &&
        $breed === $search
    ) {

        $score += 100;
    }


    /* =====================================================
       PARTIAL BREED
       ===================================================== */ elseif (
        $breed !== '' &&
        strpos(
            $breed,
            $search
        ) !== false
    ) {

        $score += 80;
    }


    /* =====================================================
       WORD MATCH
       ===================================================== */

    $words =
        preg_split(
            '/\s+/',
            $search,
            -1,
            PREG_SPLIT_NO_EMPTY
        );


    foreach (
        $words
        as $word
    ) {

        if (
            strlen($word) < 2
        ) {
            continue;
        }


        /*
         * Breed
         */

        if (
            $breed !== '' &&
            strpos(
                $breed,
                $word
            ) !== false
        ) {

            $score += 40;
        }


        /*
         * Size
         */

        if (
            $size !== '' &&
            strpos(
                $size,
                $word
            ) !== false
        ) {

            $score += 25;
        }


        /*
         * Gender
         */

        if (
            $gender !== '' &&
            strpos(
                $gender,
                $word
            ) !== false
        ) {

            $score += 25;
        }


        /*
         * Age
         */

        if (
            $age !== '' &&
            strpos(
                $age,
                $word
            ) !== false
        ) {

            $score += 20;
        }


        /*
         * Description
         */

        if (
            $description !== '' &&
            strpos(
                $description,
                $word
            ) !== false
        ) {

            $score += 15;
        }
    }


    return $score;
}


/* =========================================================
   RECOMMENDATION ALGORITHM
   ========================================================= */

function recommendDogs(
    $conn,
    $search,
    $dogColumns
) {

    $search =
        trim($search);


    if ($search === '') {
        return [];
    }


    /*
     * Get all dogs
     */

    if (
        hasDogColumn(
            'added_date',
            $dogColumns
        )
    ) {

        $sql = "
            SELECT *
            FROM dogs
            ORDER BY added_date DESC
        ";
    } else {

        $sql = "
            SELECT *
            FROM dogs
            ORDER BY dog_id DESC
        ";
    }


    $result =
        $conn->query($sql);


    if (!$result) {
        return [];
    }


    $recommendedDogs = [];


    while (
        $dog =
        $result->fetch_assoc()
    ) {

        /*
         * Calculate score
         */

        $score =
            calculateRecommendationScore(
                $dog,
                $search
            );


        if ($score > 0) {

            /*
             * IMPORTANT:
             * Add correct image URL
             */

            $dog['image_url'] =
                getDogImagePath(
                    $dog['dog_image'] ?? ''
                );


            $dog['recommendation_score'] =
                $score;


            $recommendedDogs[] =
                $dog;
        }
    }


    /*
     * Sort by highest score
     */

    usort(
        $recommendedDogs,
        function ($a, $b) {

            return
                $b['recommendation_score']
                -
                $a['recommendation_score'];
        }
    );


    /*
     * Top 6
     */

    return array_slice(
        $recommendedDogs,
        0,
        6
    );
}


/* =========================================================
   AJAX SEARCH
   ========================================================= */

if (
    isset(
        $_GET['ajax_search']
    )
) {

    ini_set(
        'display_errors',
        0
    );


    header(
        'Content-Type: application/json; charset=UTF-8'
    );


    $search =
        trim(
            $_GET['ajax_search']
        );


    $results =
        searchDogs(
            $conn,
            $search,
            $dogColumns
        );


    echo json_encode([
        'success' => true,
        'count'   => count($results),
        'dogs'    => $results
    ]);


    exit;
}


/* =========================================================
   AJAX RECOMMENDATION
   ========================================================= */

if (
    isset(
        $_GET['ajax_recommend']
    )
) {

    ini_set(
        'display_errors',
        0
    );


    header(
        'Content-Type: application/json; charset=UTF-8'
    );


    $search =
        trim(
            $_GET['ajax_recommend']
        );


    $recommendations =
        recommendDogs(
            $conn,
            $search,
            $dogColumns
        );


    echo json_encode([
        'success' => true,
        'count'   => count($recommendations),
        'dogs'    => $recommendations
    ]);


    exit;
}


/* =========================================================
   ADOPTION APPLICATION
   ========================================================= */

$applicationMessage = '';

$applicationSuccess = false;


if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {


    if (
        isset(
            $_POST['submit_adoption']
        )
    ) {


        $ownerName =
            trim(
                $_POST['owner_name']
                    ?? ''
            );


        $phone =
            trim(
                $_POST['phone']
                    ?? ''
            );


        $address =
            trim(
                $_POST['address']
                    ?? ''
            );


        $reason =
            trim(
                $_POST['reason']
                    ?? ''
            );


        $dogId =
            intval(
                $_POST['dog_id']
                    ?? 0
            );


        $dogBreed =
            trim(
                $_POST['dog_breed']
                    ?? ''
            );


        if (
            $ownerName === '' ||
            $phone === '' ||
            $address === '' ||
            $reason === '' ||
            $dogId <= 0
        ) {

            $applicationMessage =
                "Please fill in all required fields.";
        } else {


            /*
             * Check table
             */

            $tableCheck =
                $conn->query(
                    "SHOW TABLES LIKE 'adoption_applications'"
                );


            if (
                !$tableCheck ||
                $tableCheck->num_rows === 0
            ) {

                $applicationMessage =
                    "adoption_applications table was not found.";
            } else {


                $stmt =
                    $conn->prepare("
                        INSERT INTO adoption_applications
                        (
                            owner_name,
                            dog_id,
                            dog_breed,
                            phone,
                            address,
                            reason
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");


                if (!$stmt) {

                    $applicationMessage =
                        "Database error: "
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


                    if (
                        $stmt->execute()
                    ) {


                        $applicationSuccess =
                            true;


                        $applicationMessage =
                            "Your adoption application has been submitted successfully.";


                        /* =================================================
                           EMAIL
                           ================================================= */

                        try {

                            $mail =
                                new PHPMailer(
                                    true
                                );


                            $mail->isSMTP();


                            $mail->Host =
                                'smtp.gmail.com';


                            $mail->SMTPAuth =
                                true;


                            /*
                             * Enter your Gmail details here.
                             */

                            $mail->Username =
                                'YOUR_GMAIL@gmail.com';


                            $mail->Password =
                                'YOUR_GMAIL_APP_PASSWORD';


                            $mail->SMTPSecure =
                                PHPMailer::ENCRYPTION_STARTTLS;


                            $mail->Port =
                                587;


                            $mail->setFrom(
                                'YOUR_GMAIL@gmail.com',
                                'Happy Tails Dog Adoption'
                            );


                            $mail->addAddress(
                                'YOUR_ADMIN_EMAIL@gmail.com'
                            );


                            $mail->isHTML(
                                true
                            );


                            $mail->Subject =
                                'New Dog Adoption Application';


                            $mail->Body = "

                                <h2>
                                    New Adoption Application
                                </h2>

                                <p>
                                    <strong>
                                        Applicant Name:
                                    </strong>
                                    " .
                                htmlspecialchars(
                                    $ownerName
                                ) .
                                "
                                </p>

                                <p>
                                    <strong>
                                        Dog:
                                    </strong>
                                    " .
                                htmlspecialchars(
                                    $dogBreed
                                ) .
                                "
                                </p>

                                <p>
                                    <strong>
                                        Phone:
                                    </strong>
                                    " .
                                htmlspecialchars(
                                    $phone
                                ) .
                                "
                                </p>

                                <p>
                                    <strong>
                                        Address:
                                    </strong>
                                    " .
                                nl2br(
                                    htmlspecialchars(
                                        $address
                                    )
                                ) .
                                "
                                </p>

                                <p>
                                    <strong>
                                        Reason:
                                    </strong>
                                    " .
                                nl2br(
                                    htmlspecialchars(
                                        $reason
                                    )
                                ) .
                                "

                            ";


                            $mail->send();
                        } catch (
                            Exception $e
                        ) {

                            /*
                             * Application is already
                             * saved in database.
                             */
                        }
                    } else {

                        $applicationMessage =
                            "Failed to submit application.";
                    }


                    $stmt->close();
                }
            }
        }
    }
}


/* =========================================================
   GET ALL DOGS
   ========================================================= */

if (
    hasDogColumn(
        'added_date',
        $dogColumns
    )
) {

    $allDogsSql = "
        SELECT *
        FROM dogs
        ORDER BY added_date DESC
    ";
} else {

    $allDogsSql = "
        SELECT *
        FROM dogs
        ORDER BY dog_id DESC
    ";
}


$dogsResult =
    $conn->query(
        $allDogsSql
    );


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
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f5ff;

            color: #333;
        }


        /* =========================================================
   NAVBAR
   ========================================================= */

        .navbar {

            background:
                linear-gradient(135deg,
                    #7a83be,
                    #5f67a3);

            padding: 15px 40px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            color: white;

            position: sticky;

            top: 0;

            z-index: 1000;
        }


        .logo {

            font-size: 24px;

            font-weight: bold;
        }


        .nav-links {

            display: flex;

            gap: 20px;

            align-items: center;
        }


        .nav-links a {

            color: white;

            text-decoration: none;

            font-weight: 500;
        }


        .nav-links a:hover {

            text-decoration: underline;
        }


        .logout-btn {

            background: white;

            color: #5f67a3 !important;

            padding: 8px 15px;

            border-radius: 20px;
        }


        /* =========================================================
   HERO
   ========================================================= */

        .hero {

            text-align: center;

            padding: 50px 20px 35px;

            background:
                linear-gradient(135deg,
                    #eef2ff,
                    #ffffff);
        }


        .hero h1 {

            font-size: 38px;

            color: #5f67a3;

            margin-bottom: 10px;
        }


        .hero p {

            color: #666;

            font-size: 17px;
        }


        /* =========================================================
   SEARCH
   ========================================================= */

        .search-section {

            max-width: 900px;

            margin: 25px auto;

            padding: 20px;

            text-align: center;
        }


        .search-box {

            display: flex;

            max-width: 700px;

            margin: auto;

            background: white;

            border-radius: 35px;

            padding: 7px;

            box-shadow:
                0 4px 15px rgba(0, 0, 0, 0.08);
        }


        .search-box input {

            flex: 1;

            border: none;

            outline: none;

            padding: 14px 20px;

            font-size: 16px;

            border-radius: 30px;
        }


        .search-box button {

            border: none;

            background: #6f75b5;

            color: white;

            padding: 0 25px;

            border-radius: 30px;

            cursor: pointer;

            font-size: 15px;
        }


        .search-box button:hover {

            background: #555c9d;
        }


        /* =========================================================
   CONTAINER
   ========================================================= */

        .container {

            width: 92%;

            max-width: 1200px;

            margin: auto;
        }


        /* =========================================================
   TITLE
   ========================================================= */

        .section-title {

            text-align: center;

            margin: 35px 0 20px;

            color: #5f67a3;

            font-size: 27px;
        }


        /* =========================================================
   GRID
   ========================================================= */

        .dog-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit,
                    minmax(250px, 1fr));

            gap: 25px;

            margin-bottom: 35px;
        }


        /* =========================================================
   DOG CARD
   ========================================================= */

        .dog-card {

            background: white;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 18px rgba(0, 0, 0, 0.08);

            transition: 0.3s;
        }


        .dog-card:hover {

            transform:
                translateY(-5px);

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.12);
        }


        /*
 * IMPORTANT IMAGE CSS
 */

        .dog-card img {

            width: 100%;

            height: 220px;

            display: block;

            object-fit: cover;

            background: #eeeeee;
        }


        .dog-content {

            padding: 18px;
        }


        .dog-content h3 {

            color: #5f67a3;

            margin-bottom: 10px;

            font-size: 21px;
        }


        .dog-content p {

            margin: 6px 0;

            color: #555;
        }


        .dog-content strong {

            color: #444;
        }


        .adopt-btn {

            width: 100%;

            margin-top: 12px;

            border: none;

            padding: 11px;

            border-radius: 8px;

            background: #6f75b5;

            color: white;

            cursor: pointer;

            font-size: 15px;
        }


        .adopt-btn:hover {

            background: #555c9d;
        }


        /* =========================================================
   RECOMMENDATION
   ========================================================= */

        .recommendation-section {

            background: #eef2ff;

            padding: 30px 20px;

            border-radius: 20px;

            margin-top: 40px;

            margin-bottom: 40px;

            border: 1px solid #dfe3ff;
        }


        .recommendation-heading {

            text-align: center;

            color: #5f67a3;

            margin-bottom: 8px;

            font-size: 28px;
        }


        .recommendation-description {

            text-align: center;

            color: #666;

            margin-bottom: 25px;
        }


        .score {

            display: inline-block;

            background: #e7eaff;

            color: #5f67a3;

            padding: 5px 10px;

            border-radius: 15px;

            font-size: 13px;

            margin-top: 5px;
        }


        /* =========================================================
   NO RESULT
   ========================================================= */

        .no-result {

            text-align: center;

            padding: 35px;

            background: white;

            border-radius: 15px;

            color: #666;

            margin: 20px 0;
        }


        /* =========================================================
   FORM
   ========================================================= */

        .form-section {

            max-width: 700px;

            margin: 50px auto;

            background: white;

            padding: 30px;

            border-radius: 18px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);
        }


        .form-section h2 {

            text-align: center;

            color: #5f67a3;

            margin-bottom: 25px;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            color: #555;
        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            outline: none;

            font-size: 15px;
        }


        .form-group textarea {

            height: 100px;

            resize: vertical;
        }


        .form-submit {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 8px;

            background: #6f75b5;

            color: white;

            font-size: 16px;

            cursor: pointer;
        }


        .form-submit:hover {

            background: #555c9d;
        }


        /* =========================================================
   MESSAGE
   ========================================================= */

        .message {

            max-width: 700px;

            margin: 20px auto;

            padding: 15px;

            border-radius: 8px;

            text-align: center;
        }


        .success {

            background: #dff5e3;

            color: #24743b;
        }


        .error {

            background: #ffe1e1;

            color: #a33;
        }


        /* =========================================================
   FOOTER
   ========================================================= */

        footer {

            background: #5f67a3;

            color: white;

            text-align: center;

            padding: 20px;

            margin-top: 50px;
        }


        /* =========================================================
   MOBILE
   ========================================================= */

        @media (max-width: 700px) {

            .navbar {

                padding: 15px 20px;

                flex-direction: column;

                gap: 12px;
            }


            .nav-links {

                gap: 10px;

                flex-wrap: wrap;

                justify-content: center;
            }


            .hero h1 {

                font-size: 30px;
            }


            .search-box {

                flex-direction: column;

                background: transparent;

                box-shadow: none;

                gap: 10px;
            }


            .search-box input {

                background: white;

                box-shadow:
                    0 3px 10px rgba(0, 0, 0, 0.08);
            }


            .search-box button {

                padding: 12px;
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


        <div class="nav-links">

            <a href="index.php">
                Home
            </a>

            <a href="#available">
                Available Dogs
            </a>

            <a href="#recommendations">
                Recommendations
            </a>

            <a href="#adoption">
                Adopt Dog
            </a>

            <a href="user_chatsupport.php">
                Chat Support
            </a>

            <a
                href="logout.php"
                class="logout-btn">
                Logout
            </a>

        </div>

    </nav>


    <!-- =====================================================
     HERO
     ===================================================== -->

    <section class="hero">

        <h1>

            Welcome,
            <?php
            echo htmlspecialchars(
                $userName
            );
            ?>

            🐶

        </h1>


        <p>

            Find your perfect furry companion
            with Happy Tails.

        </p>

    </section>


    <!-- =====================================================
     SEARCH
     ===================================================== -->

    <section class="search-section">

        <h2 class="section-title">

            🔍 Search Dogs

        </h2>


        <div class="search-box">

            <input
                type="text"
                id="searchInput"
                placeholder="Search by breed, age, size, gender...">


            <button
                type="button"
                onclick="performSearch()">

                Search

            </button>

        </div>

    </section>


    <!-- =====================================================
     SEARCH RESULTS
     ===================================================== -->

    <section
        class="container"
        id="searchResultsSection"
        style="display:none;">

        <h2 class="section-title">

            🔎 Search Results

        </h2>


        <div
            id="searchResults"
            class="dog-grid"></div>

    </section>


    <!-- =====================================================
     RECOMMENDATIONS
     ===================================================== -->

    <section
        class="container"
        id="recommendations">

        <div class="recommendation-section">

            <h2 class="recommendation-heading">

                🤖 Recommended Dogs

            </h2>


            <p class="recommendation-description">

                Recommendations are calculated separately
                using breed, age, size, gender and description.

            </p>


            <div
                id="recommendationResults"
                class="dog-grid">

                <div class="no-result">

                    Search for a dog to see recommendations.

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
     AVAILABLE DOGS
     ===================================================== -->

    <section
        class="container"
        id="available">

        <h2 class="section-title">

            🐕 Available Dogs

        </h2>


        <div class="dog-grid">


            <?php

            if (
                $dogsResult &&
                $dogsResult->num_rows > 0
            ):


                while (
                    $dog =
                    $dogsResult->fetch_assoc()
                ):


                    $dogId =
                        $dog['dog_id']
                        ?? 0;


                    $breed =
                        $dog['dog_breed']
                        ?? 'Unknown';


                    $age =
                        $dog['age']
                        ?? 'Not specified';


                    $size =
                        $dog['size']
                        ?? 'Not specified';


                    $gender =
                        $dog['gender']
                        ?? 'Not specified';


                    $description =
                        $dog['description']
                        ?? 'No description available.';


                    /*
                 * IMPORTANT
                 */

                    $image =
                        getDogImagePath(
                            $dog['dog_image']
                                ?? ''
                        );

            ?>


                    <div class="dog-card">


                        <img
                            src="<?php
                                    echo htmlspecialchars(
                                        $image
                                    );
                                    ?>"
                            alt="<?php
                                    echo htmlspecialchars(
                                        $breed
                                    );
                                    ?>"
                            onerror="
                    this.onerror=null;
                    this.src='placeholder.jpg';
                ">


                        <div class="dog-content">


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $breed
                                );
                                ?>

                            </h3>


                            <p>

                                <strong>
                                    Age:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $age
                                );
                                ?>

                            </p>


                            <p>

                                <strong>
                                    Size:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $size
                                );
                                ?>

                            </p>


                            <p>

                                <strong>
                                    Gender:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $gender
                                );
                                ?>

                            </p>


                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $description
                                );
                                ?>

                            </p>


                            <button
                                class="adopt-btn"
                                onclick='selectDog(
                        <?php
                        echo json_encode(
                            $dogId
                        );
                        ?>,
                        <?php
                        echo json_encode(
                            $breed
                        );
                        ?>
                    )'>

                                Adopt This Dog

                            </button>


                        </div>

                    </div>


                <?php

                endwhile;

            else:

                ?>


                <div class="no-result">

                    No dogs are currently available.

                </div>


            <?php

            endif;

            ?>

        </div>

    </section>


    <!-- =====================================================
     ADOPTION FORM
     ===================================================== -->

    <section
        class="form-section"
        id="adoption">

        <h2>

            🐾 Dog Adoption Application

        </h2>


        <?php

        if (
            $applicationMessage !== ''
        ):

        ?>


            <div
                class="message
            <?php
            echo $applicationSuccess
                ? 'success'
                : 'error';
            ?>">

                <?php

                echo htmlspecialchars(
                    $applicationMessage
                );

                ?>

            </div>


        <?php

        endif;

        ?>


        <form method="POST">


            <input
                type="hidden"
                name="dog_id"
                id="dog_id">


            <input
                type="hidden"
                name="dog_breed"
                id="dog_breed">


            <div class="form-group">


                <label>
                    Full Name
                </label>


                <input
                    type="text"
                    name="owner_name"
                    required
                    value="<?php
                            echo htmlspecialchars(
                                $userName
                            );
                            ?>">

            </div>


            <div class="form-group">


                <label>
                    Email Address
                </label>


                <input
                    type="email"
                    value="<?php
                            echo htmlspecialchars(
                                $userEmail
                            );
                            ?>"
                    readonly>

            </div>


            <div class="form-group">


                <label>
                    Phone Number
                </label>


                <input
                    type="text"
                    name="phone"
                    required>

            </div>


            <div class="form-group">


                <label>
                    Home Address
                </label>


                <textarea
                    name="address"
                    required></textarea>

            </div>


            <div class="form-group">


                <label>
                    Dog to Adopt
                </label>


                <input
                    type="text"
                    id="selectedDog"
                    readonly
                    placeholder="Select a dog above">

            </div>


            <div class="form-group">


                <label>
                    Why do you want to adopt this dog?
                </label>


                <textarea
                    name="reason"
                    required
                    placeholder="Explain why you want to adopt..."></textarea>

            </div>


            <button
                type="submit"
                name="submit_adoption"
                class="form-submit">

                Submit Adoption Application

            </button>

        </form>

    </section>


    <!-- =====================================================
     FOOTER
     ===================================================== -->

    <footer>

        <p>

            ©
            <?php echo date('Y'); ?>

            Happy Tails Dog Adoption

        </p>

    </footer>


    <script>
        /* =========================================================
   ESCAPE HTML
   ========================================================= */

        function escapeHtml(value) {

            if (
                value === null ||
                value === undefined
            ) {

                return '';
            }


            return String(value)

                .replace(
                    /&/g,
                    '&amp;'
                )

                .replace(
                    /</g,
                    '&lt;'
                )

                .replace(
                    />/g,
                    '&gt;'
                )

                .replace(
                    /"/g,
                    '&quot;'
                )

                .replace(
                    /'/g,
                    '&#039;'
                );
        }


        /* =========================================================
           CREATE DOG CARD
           ========================================================= */

        function createDogCard(
            dog,
            showScore
        ) {

            const dogId =
                dog.dog_id || 0;


            const breed =
                dog.dog_breed ||
                'Unknown';


            const age =
                dog.age ||
                'Not specified';


            const size =
                dog.size ||
                'Not specified';


            const gender =
                dog.gender ||
                'Not specified';


            const description =
                dog.description ||
                'No description available.';


            /*
             * IMPORTANT
             *
             * Use image_url returned from PHP.
             *
             * Do NOT use dog.dog_image here.
             */

            const image =
                dog.image_url ||
                'placeholder.jpg';


            let scoreHTML = '';


            if (showScore) {

                const score =
                    dog.recommendation_score ||
                    0;


                scoreHTML = `

            <span class="score">

                Recommendation Score:
                ${escapeHtml(score)}

            </span>

        `;
            }


            return `

        <div class="dog-card">

            <img
                src="${escapeHtml(image)}"
                alt="${escapeHtml(breed)}"
                onerror="
                    this.onerror=null;
                    this.src='placeholder.jpg';
                "
            >


            <div class="dog-content">

                <h3>

                    ${escapeHtml(breed)}

                </h3>


                <p>

                    <strong>
                        Age:
                    </strong>

                    ${escapeHtml(age)}

                </p>


                <p>

                    <strong>
                        Size:
                    </strong>

                    ${escapeHtml(size)}

                </p>


                <p>

                    <strong>
                        Gender:
                    </strong>

                    ${escapeHtml(gender)}

                </p>


                <p>

                    ${escapeHtml(description)}

                </p>


                ${scoreHTML}


                <button
                    class="adopt-btn"
                    onclick='selectDog(
                        ${JSON.stringify(dogId)},
                        ${JSON.stringify(breed)}
                    )'
                >

                    Adopt This Dog

                </button>


            </div>

        </div>

    `;
        }


        /* =========================================================
           SEARCH
           ========================================================= */

        function performSearch() {

            const input =
                document.getElementById(
                    'searchInput'
                );


            const search =
                input.value.trim();


            if (search === '') {

                alert(
                    'Please enter something to search.'
                );

                return;
            }


            /*
             * SEARCH ALGORITHM
             */

            fetch(
                    window.location.pathname +
                    '?ajax_search=' +
                    encodeURIComponent(search) +
                    '&t=' +
                    Date.now()
                )


                .then(
                    function(response) {

                        return response.json();

                    }
                )


                .then(
                    function(data) {


                        const section =
                            document.getElementById(
                                'searchResultsSection'
                            );


                        const results =
                            document.getElementById(
                                'searchResults'
                            );


                        section.style.display =
                            'block';


                        if (
                            !data.success ||
                            !data.dogs ||
                            data.dogs.length === 0
                        ) {


                            results.innerHTML = `

                    <div class="no-result">

                        No dogs found for:

                        <strong>
                            ${escapeHtml(search)}
                        </strong>

                    </div>

                `;


                        } else {


                            results.innerHTML =
                                data.dogs
                                .map(
                                    function(dog) {

                                        return createDogCard(
                                            dog,
                                            false
                                        );

                                    }
                                )
                                .join('');
                        }


                        /*
                         * Load recommendations separately
                         */

                        loadRecommendations(
                            search
                        );

                    }
                )


                .catch(
                    function(error) {

                        console.error(
                            'Search Error:',
                            error
                        );


                        document.getElementById(
                            'searchResults'
                        ).innerHTML = `

                <div class="no-result">

                    Search failed.

                </div>

            `;
                    }
                );

        }


        /* =========================================================
           RECOMMENDATION
           ========================================================= */

        function loadRecommendations(
            search
        ) {

            /*
             * RECOMMENDATION ALGORITHM
             */

            fetch(
                    window.location.pathname +
                    '?ajax_recommend=' +
                    encodeURIComponent(search) +
                    '&t=' +
                    Date.now()
                )


                .then(
                    function(response) {

                        return response.json();

                    }
                )


                .then(
                    function(data) {


                        const box =
                            document.getElementById(
                                'recommendationResults'
                            );


                        if (
                            !data.success ||
                            !data.dogs ||
                            data.dogs.length === 0
                        ) {


                            box.innerHTML = `

                    <div class="no-result">

                        No recommended dogs
                        found for this search.

                    </div>

                `;


                            return;
                        }


                        box.innerHTML =
                            data.dogs
                            .map(
                                function(dog) {

                                    return createDogCard(
                                        dog,
                                        true
                                    );

                                }
                            )
                            .join('');

                    }
                )


                .catch(
                    function(error) {

                        console.error(
                            'Recommendation Error:',
                            error
                        );


                        document.getElementById(
                            'recommendationResults'
                        ).innerHTML = `

                <div class="no-result">

                    Unable to load recommendations.

                </div>

            `;
                    }
                );

        }


        /* =========================================================
           ENTER KEY
           ========================================================= */

        document
            .getElementById(
                'searchInput'
            )
            .addEventListener(
                'keypress',
                function(event) {

                    if (
                        event.key === 'Enter'
                    ) {

                        event.preventDefault();

                        performSearch();
                    }

                }
            );


        /* =========================================================
           SELECT DOG
           ========================================================= */

        function selectDog(
            dogId,
            breed
        ) {

            document.getElementById(
                    'dog_id'
                ).value =
                dogId;


            document.getElementById(
                    'dog_breed'
                ).value =
                breed;


            document.getElementById(
                    'selectedDog'
                ).value =
                breed;


            document.getElementById(
                'adoption'
            ).scrollIntoView({
                behavior: 'smooth'
            });

        }
    </script>


</body>

</html>