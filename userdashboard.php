<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
   GMAIL CONFIGURATION
   ========================================================= */
$mailUsername = "happytailsnepal@gmail.com";
$mailPassword = "avovnrqcuhnkcvkd";
$mailFromName = "Happy Tails";


/* =========================================================
   SESSION / LOGIN CHECK
   ========================================================= */
if (
    empty($_SESSION['logged_in']) ||
    empty($_SESSION['user_email'])
) {
    header("Location: index.php");
    exit;
}

$loggedInEmail = trim($_SESSION['user_email']);

$loggedInName =
    $_SESSION['user_name']
    ?? $_SESSION['username']
    ?? $_SESSION['name']
    ?? $_SESSION['user']
    ?? 'User';


/* =========================================================
   GET USER FROM DATABASE
   ========================================================= */
$userStmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE email = ?
    LIMIT 1
");

if ($userStmt) {

    $userStmt->bind_param("s", $loggedInEmail);
    $userStmt->execute();

    $userResult = $userStmt->get_result();

    if ($userResult && $userResult->num_rows > 0) {

        $userData = $userResult->fetch_assoc();

        if (!empty($userData['username'])) {
            $loggedInName = $userData['username'];
        }

        if (!empty($userData['name'])) {
            $loggedInName = $userData['name'];
        }

        if (!empty($userData['email'])) {
            $loggedInEmail = $userData['email'];
        }
    }

    $userStmt->close();
}


/* =========================================================
   REFRESH SESSION
   ========================================================= */
$_SESSION['user_name'] = $loggedInName;
$_SESSION['user_email'] = $loggedInEmail;


/* =========================================================
   SEND APPLICATION EMAIL
   ========================================================= */
function sendApplicationSubmittedEmail(
    $toEmail,
    $applicantName,
    $dogBreed
) {
    global $mailUsername, $mailPassword, $mailFromName;

    try {

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUsername;
        $mail->Password   = $mailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mailUsername, $mailFromName);
        $mail->addAddress($toEmail, $applicantName);

        $mail->isHTML(true);

        $mail->Subject = "Adoption Application Submitted - Happy Tails";

        $mail->Body = "
            <h2>Happy Tails Dog Adoption</h2>

            <p>Dear <strong>" .
            htmlspecialchars($applicantName) .
            "</strong>,</p>

            <p>
                Your dog adoption application has been successfully submitted.
            </p>

            <p>
                <strong>Dog:</strong> " .
            htmlspecialchars($dogBreed) .
            "</p>

            <p>
                Our admin team will review your application and contact you
                regarding the next steps.
            </p>

            <p>Thank you for choosing Happy Tails.</p>

            <p>
                <strong>Happy Tails Team</strong>
            </p>
        ";

        $mail->AltBody =
            "Dear $applicantName,\n\n" .
            "Your adoption application for $dogBreed has been successfully submitted.\n\n" .
            "Happy Tails Team";

        $mail->send();

        return true;
    } catch (Exception $e) {

        return false;
    }
}


/* =========================================================
   VARIABLES
   ========================================================= */
$successMessage = '';
$errorMessage = '';
$fieldErrors = [];

$selectedDog = null;

$showDetails = false;
$showAdopt = false;


/* =========================================================
   GET ALL DOGS
   INCLUDING SIZE AND GENDER
   ========================================================= */
$dogs = [];

$dogsResult = $conn->query("
    SELECT
        dog_id,
        dog_breed,
        dog_image,
        age,
        size,
        gender,
        description
    FROM dogs
    ORDER BY dog_id DESC
");

if ($dogsResult) {

    while ($row = $dogsResult->fetch_assoc()) {

        $dogs[] = $row;
    }
}


/* =========================================================
   STORE DOGS BY ID
   ========================================================= */
$dogsById = [];

foreach ($dogs as $dog) {

    $dogsById[$dog['dog_id']] = $dog;
}


/* =========================================================
   ADOPTION FORM SUBMISSION
   ========================================================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_adoption'])
) {

    $dogId = intval($_POST['dog_id'] ?? 0);

    $ownerName = trim($loggedInName);

    $phone = trim($_POST['phone'] ?? '');

    $address = trim($_POST['address'] ?? '');

    $reason = trim($_POST['reason'] ?? '');


    /* -----------------------------------------
       GET SELECTED DOG
       ----------------------------------------- */
    if ($dogId > 0 && isset($dogsById[$dogId])) {

        $selectedDog = $dogsById[$dogId];
    } else {

        $fieldErrors['dog_id'] = "Invalid dog selected.";
    }


    /* -----------------------------------------
       VALIDATE PHONE
       ----------------------------------------- */
    if ($phone === '') {

        $fieldErrors['phone'] = "Phone number is required.";
    } elseif (!preg_match('/^(97|98)[0-9]{8}$/', $phone)) {

        $fieldErrors['phone'] =
            "Enter a valid Nepal mobile number.";
    }


    /* -----------------------------------------
       VALIDATE ADDRESS
       ----------------------------------------- */
    if ($address === '') {

        $fieldErrors['address'] =
            "Home address is required.";
    }


    /* -----------------------------------------
       VALIDATE REASON
       ----------------------------------------- */
    if ($reason === '') {

        $fieldErrors['reason'] =
            "Please provide a reason for adoption.";
    }


    /* -----------------------------------------
       INSERT APPLICATION
       ----------------------------------------- */
    if (empty($fieldErrors) && $selectedDog) {

        $dogBreed = $selectedDog['dog_breed'];

        $stmt = $conn->prepare("
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

        if ($stmt) {

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
                    "Your adoption application has been submitted successfully.";

                /*
                 * Send email notification
                 */
                sendApplicationSubmittedEmail(
                    $loggedInEmail,
                    $ownerName,
                    $dogBreed
                );

                $showAdopt = false;
            } else {

                $errorMessage =
                    "Unable to submit your application. Please try again.";
            }

            $stmt->close();
        } else {

            $errorMessage =
                "Database error while preparing application.";
        }
    }
}


/* =========================================================
   VIEW DETAILS
   ========================================================= */
if (isset($_GET['dog_id'])) {

    $dogId = intval($_GET['dog_id']);

    if ($dogId > 0 && isset($dogsById[$dogId])) {

        $selectedDog = $dogsById[$dogId];

        $showDetails = true;
    }
}


/* =========================================================
   ADOPT DOG
   ========================================================= */
if (isset($_GET['adopt'])) {

    $dogId = intval($_GET['adopt']);

    if ($dogId > 0 && isset($dogsById[$dogId])) {

        $selectedDog = $dogsById[$dogId];

        $showAdopt = true;
        $showDetails = false;
    }
}


/* =========================================================
   LINEAR SEARCH ALGORITHM
   ========================================================= */
function linearSearchDogs($dogs, $searchKeyword)
{
    $results = [];

    $searchKeyword = trim($searchKeyword);

    if ($searchKeyword === '') {

        return $dogs;
    }


    foreach ($dogs as $dog) {

        $breed = trim($dog['dog_breed'] ?? '');

        if (
            $breed !== '' &&
            stripos($breed, $searchKeyword) !== false
        ) {

            $results[] = $dog;
        }
    }

    return $results;
}


/* =========================================================
   RECOMMENDATION ALGORITHM
   ========================================================= */
function recommendDogs($dogs, $searchKeyword)
{
    $recommendations = [];

    $searchKeyword =
        strtolower(trim($searchKeyword));


    /*
     * If there is no search keyword,
     * show first 3 dogs as recommendations.
     */
    if ($searchKeyword === '') {

        $count = 0;

        foreach ($dogs as $dog) {

            $dog['recommendation_score'] = 50;

            $recommendations[] = $dog;

            $count++;

            if ($count >= 3) {
                break;
            }
        }

        return $recommendations;
    }


    /*
     * Calculate recommendation score
     * based on breed matching.
     */
    foreach ($dogs as $dog) {

        $breed =
            strtolower(
                trim($dog['dog_breed'] ?? '')
            );

        $score = 0;


        /*
         * Exact match
         */
        if ($breed === $searchKeyword) {

            $score = 100;
        }


        /*
         * Search keyword exists inside breed
         */ elseif (
            $breed !== '' &&
            stripos($breed, $searchKeyword) !== false
        ) {

            $score = 70;
        }


        /*
         * Breed exists inside search keyword
         */ elseif (
            $breed !== '' &&
            stripos($searchKeyword, $breed) !== false
        ) {

            $score = 60;
        }


        if ($score > 0) {

            $dog['recommendation_score'] = $score;

            $recommendations[] = $dog;
        }
    }


    /*
     * Sort by highest recommendation score.
     */
    usort(
        $recommendations,
        function ($a, $b) {

            return ($b['recommendation_score'] ?? 0)
                <=>
                ($a['recommendation_score'] ?? 0);
        }
    );


    /*
     * Show maximum 3 recommendations.
     */
    return array_slice(
        $recommendations,
        0,
        3
    );
}


/* =========================================================
   SEARCH
   ========================================================= */
$search = trim($_GET['search'] ?? '');


/*
 * Linear Search
 */
$displayDogs =
    linearSearchDogs(
        $dogs,
        $search
    );


/*
 * Recommendation Algorithm
 */
$recommendedDogs =
    recommendDogs(
        $dogs,
        $search
    );

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Happy Tails - User Dashboard</title>


    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            font-family: Arial, sans-serif;

            background: #eef2ff;

            color: #333;
        }


        /* =========================================================
   TOP BAR
   ========================================================= */

        .topbar {

            width: 100%;

            background:
                linear-gradient(135deg,
                    #7a83be,
                    #5f67a3);

            color: white;

            padding: 15px 35px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.15);
        }


        .logo {

            font-size: 24px;

            font-weight: bold;
        }


        .top-actions {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .top-btn {

            text-decoration: none;

            color: white;

            background: rgba(255, 255, 255, 0.15);

            padding: 9px 16px;

            border-radius: 8px;

            transition: 0.3s;
        }


        .top-btn:hover {

            background: white;

            color: #5f67a3;
        }


        /* =========================================================
   CONTAINER
   ========================================================= */

        .container {

            width: 92%;

            max-width: 1200px;

            margin: 30px auto;
        }


        /* =========================================================
   WELCOME
   ========================================================= */

        .welcome {

            background: white;

            padding: 25px;

            border-radius: 15px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px rgba(0, 0, 0, 0.08);
        }


        .welcome h1 {

            color: #5f67a3;

            margin-bottom: 8px;
        }


        .welcome p {

            color: #666;
        }


        /* =========================================================
   MESSAGES
   ========================================================= */

        .success-message {

            background: #dff7e5;

            color: #1d6b35;

            padding: 14px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        .error-message {

            background: #ffe1e1;

            color: #a33;

            padding: 14px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        /* =========================================================
   SEARCH
   ========================================================= */

        .search-section {

            background: white;

            padding: 20px;

            border-radius: 15px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px rgba(0, 0, 0, 0.08);
        }


        .search-section h2 {

            color: #5f67a3;

            margin-bottom: 15px;
        }


        .search-form {

            display: flex;

            gap: 10px;
        }


        .search-form input {

            flex: 1;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            font-size: 15px;

            outline: none;
        }


        .search-form input:focus {

            border-color: #7a83be;
        }


        .search-btn {

            border: none;

            background: #5f67a3;

            color: white;

            padding: 12px 22px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 15px;
        }


        .search-btn:hover {

            background: #4e568f;
        }


        /* =========================================================
   RECOMMENDATION SECTION
   ========================================================= */

        .recommendation-section {

            margin-bottom: 35px;
        }


        .recommendation-title {

            color: #5f67a3;

            margin-bottom: 6px;
        }


        .recommendation-subtitle {

            color: #777;

            margin-bottom: 18px;
        }


        .recommendation-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(260px, 1fr));

            gap: 20px;
        }


        .recommendation-card {

            background: white;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 18px rgba(0, 0, 0, 0.09);
        }


        .recommendation-card img {

            width: 100%;

            height: 190px;

            object-fit: cover;
        }


        .recommendation-content {

            padding: 18px;
        }


        .recommendation-content h3 {

            color: #5f67a3;

            margin-bottom: 10px;
        }


        .recommendation-content p {

            margin: 7px 0;

            color: #555;
        }


        .match-badge {

            display: inline-block;

            background: #eeeaff;

            color: #5f67a3;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 13px;

            margin-top: 5px;
        }


        .recommendation-view-btn {

            display: inline-block;

            margin-top: 12px;

            background: #5f67a3;

            color: white;

            text-decoration: none;

            padding: 9px 14px;

            border-radius: 7px;
        }


        /* =========================================================
   AVAILABLE DOGS
   ========================================================= */

        .dogs-title {

            color: #5f67a3;

            margin-bottom: 18px;
        }


        .dog-grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(260px, 1fr));

            gap: 22px;
        }


        .dog-card {

            background: white;

            border-radius: 15px;

            overflow: hidden;

            box-shadow:
                0 5px 18px rgba(0, 0, 0, 0.09);

            transition: 0.3s;
        }


        .dog-card:hover {

            transform: translateY(-4px);
        }


        .dog-card img {

            width: 100%;

            height: 200px;

            object-fit: cover;
        }


        .dog-content {

            padding: 18px;
        }


        .dog-content h3 {

            color: #5f67a3;

            margin-bottom: 10px;
        }


        .dog-content p {

            margin: 7px 0;

            color: #555;
        }


        .view-btn {

            display: inline-block;

            margin-top: 12px;

            background: #5f67a3;

            color: white;

            text-decoration: none;

            padding: 9px 15px;

            border-radius: 7px;
        }


        .view-btn:hover {

            background: #4e568f;
        }


        /* =========================================================
   DETAIL CARD
   ========================================================= */

        .detail-card {

            background: white;

            padding: 25px;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.1);
        }


        .detail-card img {

            width: 100%;

            max-width: 500px;

            height: 320px;

            object-fit: cover;

            border-radius: 12px;

            margin-bottom: 20px;
        }


        .detail-card h2 {

            color: #5f67a3;

            margin-bottom: 15px;
        }


        .detail-card p {

            margin: 10px 0;

            line-height: 1.6;

            color: #555;
        }


        .detail-buttons {

            display: flex;

            gap: 10px;

            margin-top: 20px;
        }


        .adopt-btn {

            display: inline-block;

            background: #5f67a3;

            color: white;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 8px;
        }


        .back-btn {

            display: inline-block;

            background: #ddd;

            color: #333;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 8px;
        }


        /* =========================================================
   ADOPTION FORM
   ========================================================= */

        .adoption-form {

            background: white;

            padding: 28px;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.1);
        }


        .adoption-form h2 {

            color: #5f67a3;

            margin-bottom: 20px;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;
        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            font-size: 15px;

            outline: none;
        }


        .form-group input:focus,
        .form-group textarea:focus {

            border-color: #7a83be;
        }


        .form-group textarea {

            min-height: 120px;

            resize: vertical;
        }


        .readonly-field {

            background: #f5f5f5;

            cursor: not-allowed;
        }


        .field-error {

            color: #d33;

            font-size: 13px;

            margin-top: 5px;
        }


        .submit-btn {

            border: none;

            background: #5f67a3;

            color: white;

            padding: 12px 22px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 15px;
        }


        .submit-btn:hover {

            background: #4e568f;
        }


        /* =========================================================
   NO DOGS
   ========================================================= */

        .no-dogs {

            background: white;

            padding: 30px;

            border-radius: 12px;

            text-align: center;

            color: #777;
        }


        /* =========================================================
   RESPONSIVE
   ========================================================= */

        @media (max-width: 700px) {

            .topbar {

                padding: 15px;

                flex-direction: column;

                gap: 12px;
            }


            .search-form {

                flex-direction: column;
            }


            .detail-card img {

                height: 240px;
            }
        }
    </style>

</head>


<body>


    <!-- =====================================================
     TOP BAR
     ===================================================== -->

    <div class="topbar">

        <div class="logo">
            🐾 Happy Tails
        </div>


        <div class="top-actions">

            <a href="user_chatsupport.php"
                class="top-btn">
                Chat with Admin
            </a>


            <a href="logout.php"
                class="top-btn">
                Logout
            </a>

        </div>

    </div>


    <!-- =====================================================
     MAIN CONTAINER
     ===================================================== -->

    <div class="container">


        <!-- =================================================
         WELCOME
         ================================================= -->

        <div class="welcome">

            <h1>
                Welcome, <?= htmlspecialchars($loggedInName, ENT_QUOTES, 'UTF-8') ?>!
            </h1>

            <p>
                Find your perfect companion and give a dog a loving home.
            </p>

        </div>


        <!-- =================================================
         SUCCESS MESSAGE
         ================================================= -->

        <?php if ($successMessage): ?>

            <div class="success-message">

                <?= htmlspecialchars(
                    $successMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
         ERROR MESSAGE
         ================================================= -->

        <?php if ($errorMessage): ?>

            <div class="error-message">

                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
         ADOPTION FORM
         ================================================= -->

        <?php if ($showAdopt && $selectedDog): ?>


            <div class="adoption-form">

                <h2>
                    Apply to Adopt
                </h2>


                <form method="POST"
                    action="">


                    <input type="hidden"
                        name="dog_id"
                        value="<?= (int)$selectedDog['dog_id'] ?>">


                    <!-- Full Name -->

                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <input type="text"
                            value="<?= htmlspecialchars(
                                        $loggedInName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            readonly
                            class="readonly-field">

                    </div>


                    <!-- Email -->

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input type="email"
                            value="<?= htmlspecialchars(
                                        $loggedInEmail,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            readonly
                            class="readonly-field">

                    </div>


                    <!-- Dog Breed -->

                    <div class="form-group">

                        <label>
                            Dog to Adopt
                        </label>

                        <input type="text"
                            value="<?= htmlspecialchars(
                                        $selectedDog['dog_breed'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            readonly
                            class="readonly-field">

                    </div>


                    <!-- Size -->

                    <div class="form-group">

                        <label>
                            Size
                        </label>

                        <input type="text"
                            value="<?= htmlspecialchars(
                                        $selectedDog['size'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            readonly
                            class="readonly-field">

                    </div>


                    <!-- Gender -->

                    <div class="form-group">

                        <label>
                            Gender
                        </label>

                        <input type="text"
                            value="<?= htmlspecialchars(
                                        $selectedDog['gender'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            readonly
                            class="readonly-field">

                    </div>


                    <!-- Phone -->

                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input type="text"
                            name="phone"
                            placeholder="Enter your phone number"
                            value="<?= htmlspecialchars(
                                        $_POST['phone'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                        <?php if (isset($fieldErrors['phone'])): ?>

                            <div class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- Address -->

                    <div class="form-group">

                        <label>
                            Home Address
                        </label>

                        <input type="text"
                            name="address"
                            placeholder="Enter your home address"
                            value="<?= htmlspecialchars(
                                        $_POST['address'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                        <?php if (isset($fieldErrors['address'])): ?>

                            <div class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['address'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- Reason -->

                    <div class="form-group">

                        <label>
                            Reason for Adoption
                        </label>

                        <textarea
                            name="reason"
                            placeholder="Why do you want to adopt this dog?"><?= htmlspecialchars(
                                                                                    $_POST['reason'] ?? '',
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                ) ?></textarea>

                        <?php if (isset($fieldErrors['reason'])): ?>

                            <div class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['reason'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <button type="submit"
                        name="submit_adoption"
                        class="submit-btn">

                        Submit Adoption Application

                    </button>


                    <a href="userdashboard.php"
                        class="back-btn">

                        Cancel

                    </a>

                </form>

            </div>


            <!-- =================================================
         DOG DETAILS
         ================================================= -->

        <?php elseif ($showDetails && $selectedDog): ?>


            <div class="detail-card">


                <?php
                $imagePath = trim(
                    $selectedDog['dog_image'] ?? ''
                );

                if (
                    empty($imagePath) ||
                    !file_exists(__DIR__ . '/' . $imagePath)
                ) {

                    $imagePath = 'placeholder.jpg';
                }
                ?>


                <img src="<?= htmlspecialchars(
                                $imagePath,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    alt="Dog">


                <h2>
                    <?= htmlspecialchars(
                        $selectedDog['dog_breed'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>


                <p>
                    <strong>Dog ID:</strong>
                    <?= (int)$selectedDog['dog_id'] ?>
                </p>


                <p>
                    <strong>Age:</strong>
                    <?= htmlspecialchars(
                        $selectedDog['age'] ?? 'N/A',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>


                <!-- SIZE WITHOUT ICON -->

                <p>
                    <strong>Size:</strong>
                    <?= htmlspecialchars(
                        $selectedDog['size'] ?? 'N/A',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>


                <!-- GENDER WITHOUT ICON -->

                <p>
                    <strong>Gender:</strong>
                    <?= htmlspecialchars(
                        $selectedDog['gender'] ?? 'N/A',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>


                <p>
                    <strong>Description:</strong>
                    <?= nl2br(
                        htmlspecialchars(
                            $selectedDog['description'] ?? 'No description available.',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </p>


                <div class="detail-buttons">

                    <a href="userdashboard.php?adopt=<?= (int)$selectedDog['dog_id'] ?>"
                        class="adopt-btn">

                        Adopt This Dog

                    </a>


                    <a href="userdashboard.php"
                        class="back-btn">

                        Back

                    </a>

                </div>

            </div>


        <?php else: ?>


            <!-- =================================================
             SEARCH
             ================================================= -->

            <div class="search-section">

                <h2>
                    Search Dogs
                </h2>


                <form method="GET"
                    action="userdashboard.php"
                    class="search-form">

                    <input type="text"
                        id="searchInput"
                        name="search"
                        placeholder="Search by dog breed..."
                        value="<?= htmlspecialchars(
                                    $search,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">


                    <button type="submit"
                        class="search-btn">

                        Search

                    </button>

                </form>

            </div>


            <!-- =================================================
             RECOMMENDATIONS
             ================================================= -->

            <?php if (!empty($recommendedDogs)): ?>


                <div class="recommendation-section">

                    <h2 class="recommendation-title">

                        Recommended Dogs

                    </h2>


                    <p class="recommendation-subtitle">

                        Dogs recommended based on your search.

                    </p>


                    <div class="recommendation-grid">


                        <?php foreach ($recommendedDogs as $dog): ?>


                            <?php

                            $imagePath =
                                trim(
                                    $dog['dog_image'] ?? ''
                                );

                            if (
                                empty($imagePath) ||
                                !file_exists(
                                    __DIR__ . '/' . $imagePath
                                )
                            ) {

                                $imagePath =
                                    'placeholder.jpg';
                            }

                            ?>


                            <div class="recommendation-card">


                                <img
                                    src="<?= htmlspecialchars(
                                                $imagePath,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    alt="Dog">


                                <div class="recommendation-content">


                                    <h3>

                                        <?= htmlspecialchars(
                                            $dog['dog_breed'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </h3>


                                    <p>

                                        <strong>Dog ID:</strong>

                                        <?= (int)$dog['dog_id'] ?>

                                    </p>


                                    <p>

                                        <strong>Age:</strong>

                                        <?= htmlspecialchars(
                                            $dog['age'] ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>


                                    <!-- SIZE WITHOUT ICON -->

                                    <p>

                                        <strong>Size:</strong>

                                        <?= htmlspecialchars(
                                            $dog['size'] ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>


                                    <!-- GENDER WITHOUT ICON -->

                                    <p>

                                        <strong>Gender:</strong>

                                        <?= htmlspecialchars(
                                            $dog['gender'] ?? 'N/A',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>


                                    <span class="match-badge">

                                        Match:
                                        <?= (int)(
                                            $dog['recommendation_score']
                                            ?? 0
                                        ) ?>%

                                    </span>


                                    <br>


                                    <a
                                        href="userdashboard.php?dog_id=<?= (int)$dog['dog_id'] ?>"
                                        class="recommendation-view-btn">

                                        View Details

                                    </a>

                                </div>

                            </div>


                        <?php endforeach; ?>


                    </div>

                </div>


            <?php endif; ?>


            <!-- =================================================
             AVAILABLE DOGS
             ================================================= -->

            <h2 class="dogs-title">

                Available Dogs

            </h2>


            <?php if (!empty($displayDogs)): ?>


                <div class="dog-grid">


                    <?php foreach ($displayDogs as $dog): ?>


                        <?php

                        $imagePath =
                            trim(
                                $dog['dog_image'] ?? ''
                            );

                        if (
                            empty($imagePath) ||
                            !file_exists(
                                __DIR__ . '/' . $imagePath
                            )
                        ) {

                            $imagePath =
                                'placeholder.jpg';
                        }

                        ?>


                        <div class="dog-card">


                            <img
                                src="<?= htmlspecialchars(
                                            $imagePath,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="Dog">


                            <div class="dog-content">


                                <h3>

                                    <?= htmlspecialchars(
                                        $dog['dog_breed'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </h3>


                                <p>

                                    <strong>Dog ID:</strong>

                                    <?= (int)$dog['dog_id'] ?>

                                </p>


                                <p>

                                    <strong>Age:</strong>

                                    <?= htmlspecialchars(
                                        $dog['age'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>


                                <!-- SIZE WITHOUT ICON -->

                                <p>

                                    <strong>Size:</strong>

                                    <?= htmlspecialchars(
                                        $dog['size'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>


                                <!-- GENDER WITHOUT ICON -->

                                <p>

                                    <strong>Gender:</strong>

                                    <?= htmlspecialchars(
                                        $dog['gender'] ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>


                                <a
                                    href="userdashboard.php?dog_id=<?= (int)$dog['dog_id'] ?>"
                                    class="view-btn">

                                    View Details

                                </a>

                            </div>

                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="no-dogs">

                    No dogs found matching your search.

                </div>


            <?php endif; ?>


        <?php endif; ?>


    </div>


    <!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

    <script>
        const searchInput =
            document.getElementById('searchInput');


        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function() {

                    if (this.value.trim() === '') {

                        window.location.href =
                            'userdashboard.php';
                    }

                }
            );
        }
    </script>


</body>

</html>