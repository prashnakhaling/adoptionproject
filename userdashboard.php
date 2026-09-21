<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';


/*
|--------------------------------------------------------------------------
| EMAIL CONFIGURATION
|--------------------------------------------------------------------------
*/

$mailUsername = "YOUR_GMAIL@gmail.com";
$mailPassword = "YOUR_16_CHARACTER_APP_PASSWORD";
$mailFromName = "Happy Tails";


/*
|--------------------------------------------------------------------------
| SEND APPLICATION EMAIL
|--------------------------------------------------------------------------
*/

function sendApplicationSubmittedEmail(
    $toEmail,
    $applicantName,
    $dogBreed
) {

    global $mailUsername, $mailPassword, $mailFromName;

    if (empty($toEmail)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUsername;
        $mail->Password   = $mailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(
            $mailUsername,
            $mailFromName
        );

        $mail->addAddress(
            $toEmail,
            $applicantName
        );

        $mail->isHTML(true);

        $mail->Subject = 'Dog Adoption Application Submitted';

        $mail->Body = "
            <div style='font-family:Arial,sans-serif; line-height:1.6;'>
                <h2 style='color:#5a34ae;'>Happy Tails</h2>

                <p>Dear <strong>" .
            htmlspecialchars($applicantName) .
            "</strong>,</p>

                <p>
                    Your dog adoption application has been
                    successfully submitted.
                </p>

                <p>
                    <strong>Dog:</strong> " .
            htmlspecialchars($dogBreed) .
            "
                </p>

                <p>
                    Our team will review your application and
                    contact you regarding the next steps.
                </p>

                <p>
                    Thank you for choosing Happy Tails.
                </p>

                <br>

                <p>
                    Regards,<br>
                    <strong>Happy Tails Team</strong>
                </p>
            </div>
        ";

        $mail->AltBody =
            "Dear $applicantName,\n\n" .
            "Your dog adoption application for " .
            "$dogBreed has been successfully submitted.\n\n" .
            "Our team will review your application and " .
            "contact you regarding the next steps.\n\n" .
            "Regards,\nHappy Tails Team";

        $mail->send();

        return true;
    } catch (Exception $e) {

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| GET LOGGED-IN USER
|--------------------------------------------------------------------------
*/

$loggedInName  = '';
$loggedInEmail = '';

/*
 * Different session names are supported here so that
 * your existing login system does not need to be changed.
 */

if (!empty($_SESSION['username'])) {

    $loggedInName = trim($_SESSION['username']);
} elseif (!empty($_SESSION['name'])) {

    $loggedInName = trim($_SESSION['name']);
} elseif (isset($_SESSION['user'])) {

    if (is_array($_SESSION['user'])) {

        $loggedInName =
            trim($_SESSION['user']['name'] ?? '');

        $loggedInEmail =
            trim($_SESSION['user']['email'] ?? '');
    } else {

        $loggedInName =
            trim($_SESSION['user']);
    }
}


/*
|--------------------------------------------------------------------------
| GET USER EMAIL FROM DATABASE
|--------------------------------------------------------------------------
*/

if (!empty($loggedInName) && empty($loggedInEmail)) {

    $userStmt = $conn->prepare(
        "SELECT email
         FROM users
         WHERE name = ?
         LIMIT 1"
    );

    if ($userStmt) {

        $userStmt->bind_param(
            "s",
            $loggedInName
        );

        $userStmt->execute();

        $userResult =
            $userStmt->get_result();

        if ($userRow = $userResult->fetch_assoc()) {

            $loggedInEmail =
                trim($userRow['email'] ?? '');
        }

        $userStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| FETCH ALL DOGS
|--------------------------------------------------------------------------
*/

$dogs = [];

$sql = "
    SELECT
        dog_id,
        dog_breed,
        dog_image,
        age,
        description
    FROM dogs
    ORDER BY dog_id DESC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $dogs[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| CREATE DOG LOOKUP ARRAY
|--------------------------------------------------------------------------
*/

$dogsById = [];

foreach ($dogs as $dog) {

    $dogsById[(int)$dog['dog_id']] = $dog;
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage   = '';

$formErrors = [];

$selectedDog = null;

$showDetails = false;
$showAdopt   = false;


/*
|--------------------------------------------------------------------------
| ADOPTION FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_adoption'])
) {

    /*
     * Name comes from logged-in user.
     */
    $ownerName =
        trim($_POST['owner_name'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');

    $address =
        trim($_POST['address'] ?? '');

    $reason =
        trim($_POST['reason'] ?? '');

    $dogId =
        intval($_POST['dog_id'] ?? 0);


    /*
     |--------------------------------------------------------------------------
     | VALIDATION
     |--------------------------------------------------------------------------
     |
     | IMPORTANT:
     | Empty fields are validated FIRST.
     | Therefore "Please login" will NOT appear simply because
     | the form fields are empty.
     |
     */


    /*
     * NAME VALIDATION
     */

    if ($ownerName === '') {

        $formErrors[] =
            "Name is required.";
    }


    /*
     * PHONE VALIDATION
     */

    if ($phone === '') {

        $formErrors[] =
            "Phone number is required.";
    } elseif (
        !preg_match(
            '/^[0-9+\-\s()]{7,20}$/',
            $phone
        )
    ) {

        $formErrors[] =
            "Please enter a valid phone number.";
    }


    /*
     * ADDRESS VALIDATION
     */

    if ($address === '') {

        $formErrors[] =
            "Address is required.";
    } elseif (strlen($address) < 5) {

        $formErrors[] =
            "Address must be at least 5 characters.";
    }


    /*
     * REASON VALIDATION
     */

    if ($reason === '') {

        $formErrors[] =
            "Reason for adoption is required.";
    } elseif (strlen($reason) < 10) {

        $formErrors[] =
            "Reason for adoption must be at least 10 characters.";
    }


    /*
     * DOG VALIDATION
     */

    if (
        $dogId <= 0 ||
        !isset($dogsById[$dogId])
    ) {

        $formErrors[] =
            "Please select a valid dog.";
    }


    /*
     |--------------------------------------------------------------------------
     | LOGIN CHECK
     |--------------------------------------------------------------------------
     |
     | Only check login AFTER validating the form.
     |
     */

    if (
        empty($loggedInName) &&
        empty($ownerName)
    ) {

        $formErrors[] =
            "Please login before submitting an adoption application.";
    }


    /*
     |--------------------------------------------------------------------------
     | IF NO VALIDATION ERRORS
     |--------------------------------------------------------------------------
     */

    if (empty($formErrors)) {

        /*
         * Get selected dog
         */

        $selectedDog =
            $dogsById[$dogId];

        $dogBreed =
            $selectedDog['dog_breed'];


        /*
         * Insert application
         */

        $insertSql = "
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
        ";

        $stmt =
            $conn->prepare($insertSql);

        if (!$stmt) {

            $errorMessage =
                "Database error: " .
                $conn->error;
        } else {

            $dogIdInt = (int)$dogId;

            $stmt->bind_param(
                "sissss",
                $ownerName,
                $dogIdInt,
                $dogBreed,
                $phone,
                $address,
                $reason
            );

            if ($stmt->execute()) {

                /*
                 * Send confirmation email
                 */

                $emailSent = false;

                if (!empty($loggedInEmail)) {

                    $emailSent =
                        sendApplicationSubmittedEmail(
                            $loggedInEmail,
                            $ownerName,
                            $dogBreed
                        );
                }


                /*
                 * Success message
                 */

                $successMessage =
                    "Your adoption application for " .
                    htmlspecialchars($dogBreed) .
                    " has been submitted successfully.";

                if ($emailSent) {

                    $successMessage .=
                        " A confirmation email has also been sent to your email address.";
                }


                /*
                 * Clear form values
                 */

                $_POST = [];

                $showAdopt = false;
            } else {

                $errorMessage =
                    "Unable to submit your application. Please try again.";
            }

            $stmt->close();
        }
    }


    /*
     * If validation failed, keep adoption form open.
     */

    if (!empty($formErrors)) {

        $showAdopt = true;

        if ($dogId > 0 && isset($dogsById[$dogId])) {

            $selectedDog =
                $dogsById[$dogId];
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET DOG ID
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['dog_id']) &&
    intval($_GET['dog_id']) > 0
) {

    $requestedDogId =
        intval($_GET['dog_id']);

    if (isset($dogsById[$requestedDogId])) {

        $selectedDog =
            $dogsById[$requestedDogId];

        $showDetails = true;
    }
}


/*
|--------------------------------------------------------------------------
| ADOPT MODE
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['adopt']) &&
    $_GET['adopt'] == '1' &&
    isset($_GET['dog_id'])
) {

    $requestedDogId =
        intval($_GET['dog_id']);

    if (isset($dogsById[$requestedDogId])) {

        $selectedDog =
            $dogsById[$requestedDogId];

        $showDetails = false;
        $showAdopt   = true;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$displayDogs = [];

if ($search !== '') {

    foreach ($dogs as $dog) {

        if (
            stripos(
                $dog['dog_breed'],
                $search
            ) !== false
        ) {

            $displayDogs[] = $dog;
        }
    }
} else {

    $displayDogs = $dogs;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Happy Tails - User Dashboard</title>


    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #2b2b2b;
        }


        /* ================= HEADER ================= */

        .topbar {
            background: #ffffff;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e6e6e6;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 25px;
            font-weight: 700;
            color: #5a34ae;
        }

        .top-actions {
            display: flex;
            gap: 10px;
        }

        .top-btn {
            text-decoration: none;
            padding: 10px 17px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .chat-btn {
            background: #eee9fa;
            color: #5a34ae;
        }

        .logout-btn {
            background: #5a34ae;
            color: #ffffff;
        }


        /* ================= CONTAINER ================= */

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }


        /* ================= SEARCH ================= */

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .search-box input {
            flex: 1;
            padding: 13px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }

        .search-box button {
            padding: 13px 22px;
            border: none;
            border-radius: 8px;
            background: #5a34ae;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }


        /* ================= DOG GRID ================= */

        .dog-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 24px;
        }

        .dog-card {
            background: #ffffff;
            border: 1px solid #e6e6e6;
            border-radius: 12px;
            overflow: hidden;
        }

        .dog-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            display: block;
        }

        .dog-content {
            padding: 20px;
        }

        .dog-content h3 {
            margin: 0 0 10px;
            color: #5a34ae;
        }

        .dog-content p {
            margin: 7px 0;
            color: #6b6b6b;
        }

        .view-btn,
        .adopt-btn,
        .back-btn {
            display: inline-block;
            text-decoration: none;
            border: none;
            cursor: pointer;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 12px;
        }

        .view-btn,
        .adopt-btn {
            background: #5a34ae;
            color: #ffffff;
        }

        .back-btn {
            background: #eee9fa;
            color: #5a34ae;
        }


        /* ================= MESSAGE ================= */

        .success-message {
            background: #e7f7ed;
            color: #176b35;
            border: 1px solid #bce5c9;
            padding: 15px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .error-message {
            background: #fff0f0;
            color: #b42318;
            border: 1px solid #f0b8b8;
            padding: 15px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .error-message ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }


        /* ================= DETAILS ================= */

        .detail-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 30px;
            border: 1px solid #e6e6e6;
        }

        .detail-image {
            width: 100%;
            max-width: 600px;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
            margin: 0 auto 30px;
        }

        .detail-card h1 {
            color: #5a34ae;
            margin-bottom: 25px;
        }

        .info-item {
            display: flex;
            padding: 13px 0;
            border-bottom: 1px solid #eeeeee;
        }

        .info-label {
            width: 130px;
            font-weight: 700;
        }

        .info-value {
            color: #555;
        }

        .description-box {
            margin-top: 25px;
            padding-top: 5px;
        }

        .description-box h3 {
            color: #5a34ae;
            margin-bottom: 10px;
        }

        .description-box p {
            line-height: 1.7;
            color: #555;
            margin: 0;
        }

        .no-description {
            color: #888 !important;
            font-style: italic;
        }

        .detail-actions {
            margin-top: 25px;
        }


        /* ================= ADOPTION FORM ================= */

        .form-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 30px;
            border: 1px solid #e6e6e6;
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card h1 {
            color: #5a34ae;
            margin-top: 0;
        }

        .selected-dog {
            background: #f7f4fc;
            border: 1px solid #e4dcf5;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            gap: 18px;
            align-items: center;
            margin-bottom: 28px;
        }

        .selected-dog img {
            width: 110px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
        }

        .selected-dog h3 {
            margin: 0 0 7px;
            color: #5a34ae;
        }

        .selected-dog p {
            margin: 4px 0;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d8d8d8;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #5a34ae;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input[readonly] {
            background: #f5f5f5;
            color: #555;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #5a34ae;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #48258f;
        }


        /* ================= SUCCESS CARD ================= */

        .success-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 40px;
            text-align: center;
            border: 1px solid #e6e6e6;
            max-width: 700px;
            margin: 40px auto;
        }

        .success-card h2 {
            color: #5a34ae;
        }


        /* ================= RESPONSIVE ================= */

        @media (max-width: 900px) {

            .dog-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 600px) {

            .topbar {
                padding: 15px 4%;
            }

            .top-actions {
                gap: 5px;
            }

            .top-btn {
                padding: 8px 10px;
                font-size: 12px;
            }

            .container {
                width: 92%;
                margin: 25px auto;
            }

            .dog-grid {
                grid-template-columns: 1fr;
            }

            .search-box {
                flex-direction: column;
            }

            .detail-card,
            .form-card {
                padding: 20px;
            }

            .detail-image {
                height: 280px;
            }

            .selected-dog {
                align-items: flex-start;
            }

            .selected-dog img {
                width: 90px;
                height: 75px;
            }

            .info-item {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>

</head>


<body>


    <!-- ================= HEADER ================= -->

    <header class="topbar">

        <div class="logo">
            🐾 Happy Tails
        </div>

        <div class="top-actions">

            <a
                href="user_chatsupport.php"
                class="top-btn chat-btn">
                Chat with Admin
            </a>

            <a
                href="logout.php"
                class="top-btn logout-btn">
                Logout
            </a>

        </div>

    </header>


    <div class="container">


        <!-- ================= SUCCESS MESSAGE ================= -->

        <?php if ($successMessage !== ''): ?>

            <div class="success-message">
                <?= $successMessage ?>
            </div>

        <?php endif; ?>


        <!-- ================= ERROR MESSAGE ================= -->

        <?php if (!empty($formErrors)): ?>

            <div class="error-message">

                <strong>
                    Please correct the following:
                </strong>

                <ul>

                    <?php foreach ($formErrors as $error): ?>

                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if ($showAdopt && $selectedDog): ?>


            <!-- ==========================================================
             ADOPTION FORM
             ========================================================== -->

            <div class="form-card">

                <a
                    href="userdashboard.php?dog_id=<?= (int)$selectedDog['dog_id'] ?>"
                    class="back-btn">
                    ← Back to Details
                </a>


                <h1>
                    Dog Adoption Form
                </h1>


                <!-- SELECTED DOG -->

                <div class="selected-dog">

                    <img
                        src="<?= htmlspecialchars($selectedDog['dog_image'] ?? '') ?>"
                        alt="<?= htmlspecialchars($selectedDog['dog_breed'] ?? 'Dog') ?>">

                    <div>

                        <h3>
                            <?= htmlspecialchars($selectedDog['dog_breed'] ?? 'Unknown Breed') ?>
                        </h3>

                        <p>
                            <strong>Dog ID:</strong>
                            #<?= htmlspecialchars((string)$selectedDog['dog_id']) ?>
                        </p>

                        <p>
                            <strong>Age:</strong>
                            <?= htmlspecialchars($selectedDog['age'] ?? 'N/A') ?>
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    action="userdashboard.php?dog_id=<?= (int)$selectedDog['dog_id'] ?>&adopt=1">


                    <input
                        type="hidden"
                        name="dog_id"
                        value="<?= (int)$selectedDog['dog_id'] ?>">


                    <!-- NAME -->

                    <div class="form-group">

                        <label for="owner_name">
                            Name
                        </label>

                        <input
                            type="text"
                            id="owner_name"
                            name="owner_name"
                            value="<?= htmlspecialchars(
                                        $_POST['owner_name']
                                            ?? $loggedInName
                                            ?? ''
                                    ) ?>"
                            placeholder="Your name"
                            readonly
                            required>

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars(
                                        $_POST['phone'] ?? ''
                                    ) ?>"
                            placeholder="Enter your phone number"
                            required>

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="Enter your address"
                            required><?= htmlspecialchars(
                                            $_POST['address'] ?? ''
                                        ) ?></textarea>

                    </div>


                    <!-- REASON -->

                    <div class="form-group">

                        <label for="reason">
                            Reason for Adoption
                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            placeholder="Why do you want to adopt this dog?"
                            required><?= htmlspecialchars(
                                            $_POST['reason'] ?? ''
                                        ) ?></textarea>

                    </div>


                    <button
                        type="submit"
                        name="submit_adoption"
                        class="submit-btn">
                        Submit Adoption Application
                    </button>

                </form>

            </div>


        <?php elseif ($showDetails && $selectedDog): ?>


            <!-- ==========================================================
             DOG DETAILS
             ========================================================== -->

            <div class="detail-card">


                <a
                    href="userdashboard.php"
                    class="back-btn">
                    ← Back to Dogs
                </a>


                <h1>
                    Dog Details
                </h1>


                <!-- DOG IMAGE -->

                <img
                    class="detail-image"
                    src="<?= htmlspecialchars(
                                $selectedDog['dog_image'] ?? ''
                            ) ?>"
                    alt="<?= htmlspecialchars(
                                $selectedDog['dog_breed'] ?? 'Dog'
                            ) ?>">


                <!-- DOG ID -->

                <div class="info-item">

                    <span class="info-label">
                        Dog ID
                    </span>

                    <span class="info-value">

                        #<?= htmlspecialchars(
                                (string)$selectedDog['dog_id']
                            ) ?>

                    </span>

                </div>


                <!-- BREED -->

                <div class="info-item">

                    <span class="info-label">
                        Breed
                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $selectedDog['dog_breed']
                                ?? 'N/A'
                        ) ?>

                    </span>

                </div>


                <!-- AGE -->

                <div class="info-item">

                    <span class="info-label">
                        Age
                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $selectedDog['age']
                                ?? 'N/A'
                        ) ?>

                    </span>

                </div>


                <!-- DESCRIPTION
                 This heading ALWAYS appears.
            -->

                <div class="description-box">

                    <h3>
                        Description
                    </h3>

                    <?php if (
                        !empty(trim(
                                $selectedDog['description']
                                    ?? ''
                            ))
                    ): ?>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $selectedDog['description']
                                )
                            ) ?>
                        </p>

                    <?php else: ?>

                        <p class="no-description">
                            No description available for this dog.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- ACTIONS -->

                <div class="detail-actions">

                    <a
                        href="userdashboard.php?dog_id=<?= (int)$selectedDog['dog_id'] ?>&adopt=1"
                        class="adopt-btn">
                        Adopt This Dog
                    </a>

                </div>

            </div>


        <?php else: ?>


            <!-- ==========================================================
             DOG LIST
             ========================================================== -->


            <form
                method="GET"
                class="search-box">

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search dog by breed...">

                <button type="submit">
                    Search
                </button>

            </form>


            <?php if (empty($displayDogs)): ?>

                <div class="error-message">
                    No dogs found.
                </div>

            <?php else: ?>


                <div class="dog-grid">


                    <?php foreach ($displayDogs as $dog): ?>

                        <div class="dog-card">


                            <img
                                src="<?= htmlspecialchars(
                                            $dog['dog_image'] ?? ''
                                        ) ?>"
                                alt="<?= htmlspecialchars(
                                            $dog['dog_breed'] ?? 'Dog'
                                        ) ?>">


                            <div class="dog-content">

                                <h3>
                                    <?= htmlspecialchars(
                                        $dog['dog_breed']
                                            ?? 'Unknown Breed'
                                    ) ?>
                                </h3>


                                <p>
                                    <strong>Dog ID:</strong>
                                    #<?= htmlspecialchars(
                                            (string)$dog['dog_id']
                                        ) ?>
                                </p>


                                <p>
                                    <strong>Age:</strong>
                                    <?= htmlspecialchars(
                                        $dog['age'] ?? 'N/A'
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


            <?php endif; ?>


        <?php endif; ?>


    </div>


</body>

</html>