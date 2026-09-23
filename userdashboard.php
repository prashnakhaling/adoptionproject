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

$mailUsername = "happytailsnepal@gmail.com";

/*
 * Yaha Gmail App Password राख्नु।
 * Spaces राखे पनि code le automatically remove garcha.
 */
$mailPassword = "avovnrqcuhnkcvkd";

$mailFromName = "Happy Tails";


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
|
| Timro login.php le:
|
| $_SESSION['logged_in'] = true;
| $_SESSION['user_name'] = $user['name'];
| $_SESSION['user_email'] = $user['email'];
|
*/

if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET SESSION IDENTIFICATION
|--------------------------------------------------------------------------
*/

$sessionEmail = trim(
    $_SESSION['user_email'] ?? ''
);

$sessionName = trim(
    $_SESSION['user_name'] ?? ''
);


/*
|--------------------------------------------------------------------------
| OLD SESSION FALLBACK
|--------------------------------------------------------------------------
|
| Purano session structure bhaye pani dashboard work garos.
|--------------------------------------------------------------------------
*/

if ($sessionName === '' && !empty($_SESSION['username'])) {

    $sessionName = trim(
        $_SESSION['username']
    );
}

if ($sessionName === '' && !empty($_SESSION['name'])) {

    $sessionName = trim(
        $_SESSION['name']
    );
}

if (
    $sessionName === '' &&
    isset($_SESSION['user']) &&
    is_array($_SESSION['user'])
) {

    $sessionName = trim(
        $_SESSION['user']['name'] ?? ''
    );

    if ($sessionEmail === '') {

        $sessionEmail = trim(
            $_SESSION['user']['email'] ?? ''
        );
    }
}


/*
|--------------------------------------------------------------------------
| GET ACTUAL USER FROM DATABASE
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Name ra email session ma matra trust gardaina.
| Database bata actual user fetch garcha.
|--------------------------------------------------------------------------
*/

$loggedInName = '';
$loggedInEmail = '';

$userFound = false;


/*
|--------------------------------------------------------------------------
| FIRST PRIORITY: EMAIL
|--------------------------------------------------------------------------
*/

if ($sessionEmail !== '') {

    $userStmt = $conn->prepare(
        "SELECT name, email
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    if ($userStmt) {

        $userStmt->bind_param(
            "s",
            $sessionEmail
        );

        $userStmt->execute();

        $userResult =
            $userStmt->get_result();

        if ($userResult->num_rows === 1) {

            $userData =
                $userResult->fetch_assoc();

            $loggedInName =
                trim(
                    $userData['name'] ?? ''
                );

            $loggedInEmail =
                trim(
                    $userData['email'] ?? ''
                );

            $userFound = true;
        }

        $userStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| SECOND PRIORITY: NAME
|--------------------------------------------------------------------------
*/

if (
    !$userFound &&
    $sessionName !== ''
) {

    $userStmt = $conn->prepare(
        "SELECT name, email
         FROM users
         WHERE name = ?
         LIMIT 1"
    );

    if ($userStmt) {

        $userStmt->bind_param(
            "s",
            $sessionName
        );

        $userStmt->execute();

        $userResult =
            $userStmt->get_result();

        if ($userResult->num_rows === 1) {

            $userData =
                $userResult->fetch_assoc();

            $loggedInName =
                trim(
                    $userData['name'] ?? ''
                );

            $loggedInEmail =
                trim(
                    $userData['email'] ?? ''
                );

            $userFound = true;
        }

        $userStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (
    !$userFound ||
    $loggedInName === '' ||
    $loggedInEmail === ''
) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| REFRESH SESSION WITH DATABASE VALUES
|--------------------------------------------------------------------------
|
| Aba session ma pani exact database ko name/email huncha.
|--------------------------------------------------------------------------
*/

$_SESSION['logged_in'] = true;

$_SESSION['user_name'] =
    $loggedInName;

$_SESSION['user_email'] =
    $loggedInEmail;


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

    global
        $mailUsername,
        $mailPassword,
        $mailFromName;


    $toEmail =
        trim($toEmail);


    if ($toEmail === '') {

        error_log(
            "Happy Tails: User email is empty."
        );

        return false;
    }


    if (
        !filter_var(
            $toEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        error_log(
            "Happy Tails: Invalid recipient email: " .
                $toEmail
        );

        return false;
    }


    $mail =
        new PHPMailer(true);


    try {

        /*
        |--------------------------------------------------------------------------
        | SMTP
        |--------------------------------------------------------------------------
        */

        $mail->isSMTP();

        $mail->Host =
            'smtp.gmail.com';

        $mail->SMTPAuth =
            true;

        $mail->Username =
            $mailUsername;

        $mail->Password =
            str_replace(
                ' ',
                '',
                trim($mailPassword)
            );

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port =
            587;

        $mail->CharSet =
            'UTF-8';

        $mail->SMTPDebug =
            0;

        $mail->Debugoutput =
            'error_log';


        /*
        |--------------------------------------------------------------------------
        | SENDER
        |--------------------------------------------------------------------------
        */

        $mail->setFrom(
            $mailUsername,
            $mailFromName
        );


        /*
        |--------------------------------------------------------------------------
        | RECIPIENT
        |--------------------------------------------------------------------------
        */

        $mail->addAddress(
            $toEmail,
            $applicantName
        );


        /*
        |--------------------------------------------------------------------------
        | HTML EMAIL
        |--------------------------------------------------------------------------
        */

        $mail->isHTML(true);

        $mail->Subject =
            'Happy Tails - Adoption Application Submitted';


        $safeApplicantName =
            htmlspecialchars(
                $applicantName,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeDogBreed =
            htmlspecialchars(
                $dogBreed,
                ENT_QUOTES,
                'UTF-8'
            );


        $mail->Body = "

        <div style='
            font-family:Arial,sans-serif;
            background:#f4f1fa;
            padding:30px;
        '>

            <div style='
                max-width:600px;
                margin:0 auto;
                background:#ffffff;
                padding:30px;
                border-radius:16px;
                border:1px solid #e1d8f0;
            '>

                <h2 style='
                    color:#5a34ae;
                    margin-top:0;
                '>
                    🐾 Happy Tails
                </h2>

                <p>
                    Dear
                    <strong>
                        {$safeApplicantName}
                    </strong>,
                </p>

                <p style='
                    line-height:1.7;
                    color:#555;
                '>
                    Your dog adoption application has been
                    <strong>successfully submitted</strong>.
                </p>

                <div style='
                    background:#f7f4fc;
                    border:1px solid #e4dcf5;
                    padding:18px;
                    border-radius:10px;
                    margin:20px 0;
                '>

                    <p style='margin:0;color:#444;'>

                        <strong>
                            Dog:
                        </strong>

                        {$safeDogBreed}

                    </p>

                </div>

                <p style='
                    line-height:1.7;
                    color:#555;
                '>
                    Our Happy Tails team will review your
                    application and contact you regarding
                    the next steps.
                </p>

                <p style='
                    line-height:1.7;
                    color:#555;
                '>
                    Thank you for choosing
                    <strong>Happy Tails</strong>
                    and giving a dog a loving home. ❤️
                </p>

                <p style='
                    line-height:1.6;
                    color:#555;
                '>

                    Regards,<br>

                    <strong>
                        Happy Tails Team
                    </strong>

                </p>

            </div>

        </div>
        ";


        /*
        |--------------------------------------------------------------------------
        | PLAIN TEXT
        |--------------------------------------------------------------------------
        */

        $mail->AltBody =
            "Dear " .
            $applicantName .
            ",\n\n" .

            "Your dog adoption application has been " .
            "successfully submitted.\n\n" .

            "Dog: " .
            $dogBreed .
            "\n\n" .

            "Our Happy Tails team will review your " .
            "application and contact you regarding " .
            "the next steps.\n\n" .

            "Thank you for choosing Happy Tails.\n\n" .

            "Regards,\n" .
            "Happy Tails Team";


        /*
        |--------------------------------------------------------------------------
        | SEND
        |--------------------------------------------------------------------------
        */

        $mail->send();


        error_log(
            "Happy Tails: Adoption email sent to " .
                $toEmail
        );


        return true;
    } catch (Exception $e) {

        error_log(
            "Happy Tails Email Error: " .
                $mail->ErrorInfo
        );

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$successMessage = '';

$errorMessage = '';

$fieldErrors = [

    'owner_name' => '',
    'phone'      => '',
    'address'    => '',
    'reason'     => ''

];

$selectedDog = null;

$showDetails = false;

$showAdopt = false;


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


$result =
    mysqli_query(
        $conn,
        $sql
    );


if ($result) {

    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

        $dogs[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| DOG LOOKUP
|--------------------------------------------------------------------------
*/

$dogsById = [];


foreach ($dogs as $dog) {

    $dogsById[(int)$dog['dog_id']] = $dog;
}


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
    |--------------------------------------------------------------------------
    | DOG ID
    |--------------------------------------------------------------------------
    */

    $dogId =
        intval(
            $_POST['dog_id'] ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | IMPORTANT NAME FIX
    |--------------------------------------------------------------------------
    |
    | User le POST bata name pathaye pani trust gardaina.
    |
    | Always database bata login bhako user ko name.
    |--------------------------------------------------------------------------
    */

    $ownerName =
        $loggedInName;


    /*
    |--------------------------------------------------------------------------
    | PHONE
    |--------------------------------------------------------------------------
    */

    $phone =
        trim(
            $_POST['phone'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | ADDRESS
    |--------------------------------------------------------------------------
    */

    $address =
        trim(
            $_POST['address'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | REASON
    |--------------------------------------------------------------------------
    */

    $reason =
        trim(
            $_POST['reason'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | NAME VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($ownerName === '') {

        $fieldErrors['owner_name'] =
            "Name could not be found.";
    } elseif (
        strlen($ownerName) < 2
    ) {

        $fieldErrors['owner_name'] =
            "Invalid user name.";
    } elseif (
        strlen($ownerName) > 100
    ) {

        $fieldErrors['owner_name'] =
            "User name is too long.";
    } elseif (
        !preg_match(
            "/^[a-zA-Z\s.'-]+$/",
            $ownerName
        )
    ) {

        $fieldErrors['owner_name'] =
            "Invalid user name.";
    }


    /*
    |--------------------------------------------------------------------------
    | PHONE VALIDATION
    |--------------------------------------------------------------------------
    */

    $cleanPhone = '';


    if ($phone === '') {

        $fieldErrors['phone'] =
            "Phone number is required.";
    } else {

        $normalizedPhone =
            preg_replace(
                '/[\s\-()]/',
                '',
                $phone
            );


        if (
            strpos(
                $normalizedPhone,
                '+977'
            ) === 0
        ) {

            $normalizedPhone =
                substr(
                    $normalizedPhone,
                    4
                );
        }


        if (
            !preg_match(
                '/^(970|971|974|975|976|980|981|982|984|985|986)[0-9]{7}$/',
                $normalizedPhone
            )
        ) {

            $fieldErrors['phone'] =
                "Please enter a valid Nepal mobile number.";
        } else {

            $cleanPhone =
                $normalizedPhone;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ADDRESS VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($address === '') {

        $fieldErrors['address'] =
            "Address is required.";
    } elseif (
        strlen($address) < 5
    ) {

        $fieldErrors['address'] =
            "Address must be at least 5 characters.";
    } elseif (
        strlen($address) > 255
    ) {

        $fieldErrors['address'] =
            "Address must not exceed 255 characters.";
    }


    /*
    |--------------------------------------------------------------------------
    | REASON VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($reason === '') {

        $fieldErrors['reason'] =
            "Reason for adoption is required.";
    } elseif (
        strlen($reason) < 10
    ) {

        $fieldErrors['reason'] =
            "Reason for adoption must be at least 10 characters.";
    } elseif (
        strlen($reason) > 1000
    ) {

        $fieldErrors['reason'] =
            "Reason for adoption must not exceed 1000 characters.";
    }


    /*
    |--------------------------------------------------------------------------
    | DOG VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $dogId <= 0 ||
        !isset($dogsById[$dogId])
    ) {

        $errorMessage =
            "The selected dog is invalid. Please select a valid dog.";
    } else {

        $selectedDog =
            $dogsById[$dogId];
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ERRORS
    |--------------------------------------------------------------------------
    */

    $hasFieldErrors = false;


    foreach ($fieldErrors as $error) {

        if ($error !== '') {

            $hasFieldErrors = true;

            break;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT APPLICATION
    |--------------------------------------------------------------------------
    */

    if (
        !$hasFieldErrors &&
        $dogId > 0 &&
        isset($dogsById[$dogId])
    ) {

        $selectedDog =
            $dogsById[$dogId];


        $dogBreed =
            trim(
                $selectedDog['dog_breed'] ?? ''
            );


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
            $conn->prepare(
                $insertSql
            );


        if (!$stmt) {

            $errorMessage =
                "Database error. Please try again.";
        } else {

            $dogIdInt =
                (int)$dogId;


            $stmt->bind_param(
                "sissss",
                $ownerName,
                $dogIdInt,
                $dogBreed,
                $cleanPhone,
                $address,
                $reason
            );


            if ($stmt->execute()) {


                /*
                |--------------------------------------------------------------------------
                | SEND EMAIL TO DATABASE USER EMAIL
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                | $loggedInEmail database bata आएको ho.
                |--------------------------------------------------------------------------
                */

                $emailSent =
                    sendApplicationSubmittedEmail(
                        $loggedInEmail,
                        $loggedInName,
                        $dogBreed
                    );


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                $successMessage =
                    "Your application for " .
                    htmlspecialchars(
                        $dogBreed,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                    " has been submitted successfully.";


                if ($emailSent) {

                    $successMessage .=
                        " A confirmation email has also been sent to " .
                        htmlspecialchars(
                            $loggedInEmail,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        ".";
                } else {

                    $successMessage .=
                        " However, the confirmation email could not be sent.";
                }


                /*
                |--------------------------------------------------------------------------
                | CLEAR FORM
                |--------------------------------------------------------------------------
                */

                $_POST = [];

                $showAdopt =
                    false;
            } else {

                $errorMessage =
                    "Unable to submit your application. Please try again.";
            }


            $stmt->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW FORM AGAIN ON ERROR
    |--------------------------------------------------------------------------
    */

    if (
        $hasFieldErrors ||
        $errorMessage !== ''
    ) {

        $showAdopt =
            true;


        if (
            $dogId > 0 &&
            isset($dogsById[$dogId])
        ) {

            $selectedDog =
                $dogsById[$dogId];
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET DOG DETAILS
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['dog_id']) &&
    intval($_GET['dog_id']) > 0
) {

    $requestedDogId =
        intval(
            $_GET['dog_id']
        );


    if (
        isset(
            $dogsById[$requestedDogId]
        )
    ) {

        $selectedDog =
            $dogsById[$requestedDogId];

        $showDetails =
            true;
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
        intval(
            $_GET['dog_id']
        );


    if (
        isset(
            $dogsById[$requestedDogId]
        )
    ) {

        $selectedDog =
            $dogsById[$requestedDogId];

        $showDetails =
            false;

        $showAdopt =
            true;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search'] ?? ''
    );


$displayDogs = [];


if ($search !== '') {

    foreach ($dogs as $dog) {

        if (
            stripos(
                $dog['dog_breed'],
                $search
            ) !== false
        ) {

            $displayDogs[] =
                $dog;
        }
    }
} else {

    $displayDogs =
        $dogs;
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
        }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff8f4;
            color: #333;
            overflow-x: hidden;
        }


        /* =================================================
           TOP BAR
        ================================================= */

        .topbar {
            width: 88%;
            max-width: 1400px;
            margin: 18px auto 0;

            background: rgba(255, 255, 255, 0.92);

            padding: 14px 24px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            border-radius: 25px;

            border: 1px solid #e1dcef;

            box-shadow:
                0 8px 25px rgba(90, 52, 174, 0.08);

            position: relative;
            z-index: 100;
        }


        .logo {
            font-size: 25px;
            font-weight: 700;
            color: #5a34ae;
        }


        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .top-btn {
            text-decoration: none;
            padding: 10px 17px;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }


        .chat-btn {
            background: #eeeafb;
            color: #5a34ae;
        }


        .chat-btn:hover {
            background: #dcd3f2;
            color: #48258f;
        }


        .logout-btn {
            background: #5a34ae;
            color: #ffffff;
        }


        .logout-btn:hover {
            background: #48258f;
        }


        /* =================================================
           MAIN CONTAINER
        ================================================= */

        .container {
            width: 88%;
            max-width: 1250px;
            margin: 45px auto;
        }


        /* =================================================
           SEARCH
        ================================================= */

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 35px;
        }


        .search-box input {
            flex: 1;
            padding: 14px 17px;

            border: 1px solid #d8d0e8;
            border-radius: 12px;

            font-size: 15px;

            outline: none;
            background: #ffffff;
        }


        .search-box input:focus {
            border-color: #5a34ae;

            box-shadow:
                0 0 0 3px rgba(90, 52, 174, 0.08);
        }


        .search-box button {
            padding: 13px 23px;

            border: none;
            border-radius: 12px;

            background: #5a34ae;
            color: white;

            cursor: pointer;
            font-weight: 600;
        }


        .search-box button:hover {
            background: #48258f;
        }


        /* =================================================
           DOG GRID
        ================================================= */

        .dog-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 25px;
        }


        .dog-card {
            background: #ffffff;

            border: 1px solid #e3dcef;

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 7px 25px rgba(90, 52, 174, 0.08);

            transition: 0.25s ease;
        }


        .dog-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 12px 30px rgba(90, 52, 174, 0.14);
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

            border-radius: 10px;

            font-weight: 600;

            margin-top: 12px;
        }


        .view-btn,
        .adopt-btn {
            background: #5a34ae;
            color: #ffffff;
        }


        .view-btn:hover,
        .adopt-btn:hover {
            background: #48258f;
        }


        .back-btn {
            background: #eeeafb;
            color: #5a34ae;
        }


        .back-btn:hover {
            background: #ddd5f1;
        }


        /* =================================================
           SUCCESS / ERROR
        ================================================= */

        .success-message {
            background: #eeeafb;
            color: #4b278f;

            border: 1px solid #cfc6ec;

            padding: 15px 18px;

            border-radius: 12px;

            margin-bottom: 25px;
        }


        .error-message {
            background: #fff0f0;
            color: #b42318;

            border: 1px solid #f0b8b8;

            padding: 15px 18px;

            border-radius: 12px;

            margin-bottom: 25px;
        }


        /* =================================================
           DETAILS
        ================================================= */

        .detail-card {
            background: #ffffff;

            border-radius: 18px;

            padding: 30px;

            border: 1px solid #e3dcef;

            box-shadow:
                0 8px 25px rgba(90, 52, 174, 0.08);
        }


        .detail-image {
            width: 100%;
            max-width: 600px;
            height: 400px;

            object-fit: cover;

            border-radius: 15px;

            display: block;

            margin: 25px auto 30px;
        }


        .detail-card h1 {
            color: #5a34ae;
            margin-bottom: 25px;
        }


        .info-item {
            display: flex;

            padding: 13px 0;

            border-bottom:
                1px solid #eeeeee;
        }


        .info-label {
            width: 130px;
            font-weight: 700;
            color: #48258f;
        }


        .info-value {
            color: #555;
        }


        .description-box {
            margin-top: 25px;
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


        /* =================================================
           ADOPTION FORM
        ================================================= */

        .form-card {
            background: #ffffff;

            border-radius: 22px;

            padding: 32px;

            border: 1px solid #ded5ee;

            max-width: 800px;

            margin: 0 auto;

            box-shadow:
                0 12px 35px rgba(90, 52, 174, 0.10);

            position: relative;
        }


        .form-card h1 {
            color: #5a34ae;

            margin-top: 20px;
            margin-bottom: 25px;

            padding-right: 45px;
        }


        /* =================================================
           CLOSE X BUTTON
        ================================================= */

        .close-form-btn {
            position: absolute;

            top: 18px;
            right: 18px;

            width: 40px;
            height: 40px;

            display: flex;

            align-items: center;
            justify-content: center;

            border: none;

            border-radius: 50%;

            background: #eeeafb;

            color: #5a34ae;

            text-decoration: none;

            font-size: 25px;

            font-weight: bold;

            transition: 0.2s ease;
        }


        .close-form-btn:hover {
            background: #5a34ae;
            color: #ffffff;

            transform: rotate(90deg);
        }


        /* =================================================
           SELECTED DOG
        ================================================= */

        .selected-dog {
            background:
                linear-gradient(135deg,
                    #f0ebf9,
                    #faf7ff);

            border: 1px solid #e4dcf5;

            border-radius: 12px;

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

            border-radius: 9px;
        }


        .selected-dog h3 {
            margin: 0 0 7px;
            color: #5a34ae;
        }


        .selected-dog p {
            margin: 4px 0;
            color: #666;
        }


        /* =================================================
           FORM GROUP
        ================================================= */

        .form-group {
            margin-bottom: 22px;
        }


        .form-group label {
            display: block;

            font-weight: 600;

            margin-bottom: 8px;

            color: #444;
        }


        .form-group input,
        .form-group textarea {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d8d1e3;

            border-radius: 10px;

            font-size: 15px;

            font-family: inherit;

            transition: 0.2s;

            background: #ffffff;
        }


        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;

            border-color: #5a34ae;

            box-shadow:
                0 0 0 3px rgba(90, 52, 174, 0.08);
        }


        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }


        /* =================================================
           READONLY NAME
        ================================================= */

        .readonly-input {
            background: #f1eef8 !important;

            color: #555 !important;

            border-color: #dcd5eb !important;

            cursor: not-allowed;
        }


        .readonly-input:focus {
            border-color: #dcd5eb !important;

            box-shadow: none !important;
        }


        /* =================================================
           FIELD ERROR
        ================================================= */

        .form-group.has-error input,
        .form-group.has-error textarea {
            border-color: #dc3545;

            background: #fffafa;
        }


        .field-error {
            display: block;

            color: #dc3545;

            font-size: 13px;

            margin-top: 6px;

            font-weight: 500;
        }


        /* =================================================
           SUBMIT
        ================================================= */

        .submit-btn {
            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 12px;

            background: #5a34ae;

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;
        }


        .submit-btn:hover {
            background: #48258f;

            box-shadow:
                0 6px 18px rgba(90, 52, 174, 0.22);
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .dog-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 600px) {

            .topbar {
                width: 94%;
                padding: 13px 16px;

                flex-wrap: wrap;

                border-radius: 18px;
            }


            .logo {
                font-size: 21px;
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
                padding: 22px 18px;
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


            .close-form-btn {
                top: 14px;
                right: 14px;
            }
        }
    </style>

</head>


<body>


    <!-- =====================================================
     HEADER
===================================================== -->

    <header class="topbar">


        <div class="logo">
            🐾 Happy Tails
        </div>


        <div class="top-actions">


            <!-- CHAT WITH ADMIN - RETAINED -->

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


        <!-- =================================================
         SUCCESS MESSAGE
    ================================================== -->

        <?php if ($successMessage !== ''): ?>

            <div class="success-message">

                <?= $successMessage ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
         GENERAL ERROR
    ================================================== -->

        <?php if ($errorMessage !== ''): ?>

            <div class="error-message">

                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($showAdopt && $selectedDog): ?>


            <!-- =================================================
             ADOPTION FORM
        ================================================== -->

            <div class="form-card">


                <!-- X CLOSE BUTTON -->

                <a
                    href="userdashboard.php"
                    class="close-form-btn"
                    title="Close">

                    &times;

                </a>


                <h1>
                    Dog Adoption Form
                </h1>


                <!-- SELECTED DOG -->

                <div class="selected-dog">


                    <img
                        src="<?= htmlspecialchars(
                                    $selectedDog['dog_image'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        alt="<?= htmlspecialchars(
                                    $selectedDog['dog_breed'] ?? 'Dog',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">


                    <div>


                        <h3>

                            <?= htmlspecialchars(
                                $selectedDog['dog_breed']
                                    ?? 'Unknown Breed',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </h3>


                        <p>

                            <strong>
                                Dog ID:
                            </strong>

                            #<?= (int)$selectedDog['dog_id'] ?>

                        </p>


                        <p>

                            <strong>
                                Age:
                            </strong>

                            <?= htmlspecialchars(
                                $selectedDog['age'] ?? 'N/A',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </p>


                    </div>


                </div>


                <!-- =================================================
                 FORM
            ================================================== -->

                <form
                    method="POST"
                    action="userdashboard.php?dog_id=<?= (int)$selectedDog['dog_id'] ?>&adopt=1"
                    novalidate>


                    <input
                        type="hidden"
                        name="dog_id"
                        value="<?= (int)$selectedDog['dog_id'] ?>">


                    <!-- =================================================
                     NAME - DATABASE VALUE
                ================================================== -->

                    <div
                        class="form-group <?= !empty($fieldErrors['owner_name']) ? 'has-error' : '' ?>">


                        <label for="owner_name">
                            Name
                        </label>


                        <input
                            type="text"
                            id="owner_name"
                            value="<?= htmlspecialchars(
                                        $loggedInName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            class="readonly-input"
                            readonly
                            autocomplete="name">


                        <small
                            style="
                            display:block;
                            color:#777;
                            margin-top:6px;
                        ">

                            Your registered name is automatically
                            taken from your account.

                        </small>


                        <?php if (!empty($fieldErrors['owner_name'])): ?>

                            <span class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['owner_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                     EMAIL - DATABASE VALUE
                ================================================== -->

                    <div class="form-group">


                        <label for="user_email">
                            Registered Email
                        </label>


                        <input
                            type="email"
                            id="user_email"
                            value="<?= htmlspecialchars(
                                        $loggedInEmail,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            class="readonly-input"
                            readonly>


                        <small
                            style="
                            display:block;
                            color:#777;
                            margin-top:6px;
                        ">

                            Adoption notification will be sent to
                            this registered email.

                        </small>


                    </div>


                    <!-- =================================================
                     PHONE
                ================================================== -->

                    <div
                        class="form-group <?= !empty($fieldErrors['phone']) ? 'has-error' : '' ?>">


                        <label for="phone">
                            Phone Number
                        </label>


                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars(
                                        $_POST['phone'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            placeholder="98XXXXXXXX"
                            maxlength="16"
                            inputmode="tel"
                            autocomplete="tel">


                        <?php if (!empty($fieldErrors['phone'])): ?>

                            <span class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                     ADDRESS
                ================================================== -->

                    <div
                        class="form-group <?= !empty($fieldErrors['address']) ? 'has-error' : '' ?>">


                        <label for="address">
                            Address
                        </label>


                        <textarea
                            id="address"
                            name="address"
                            placeholder="Enter your address"
                            minlength="5"
                            maxlength="255"
                            autocomplete="street-address"><?= htmlspecialchars(
                                                                $_POST['address'] ?? '',
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?></textarea>


                        <?php if (!empty($fieldErrors['address'])): ?>

                            <span class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['address'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                     REASON
                ================================================== -->

                    <div
                        class="form-group <?= !empty($fieldErrors['reason']) ? 'has-error' : '' ?>">


                        <label for="reason">
                            Reason for Adoption
                        </label>


                        <textarea
                            id="reason"
                            name="reason"
                            placeholder="Why do you want to adopt this dog?"
                            minlength="10"
                            maxlength="1000"><?= htmlspecialchars(
                                                    $_POST['reason'] ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?></textarea>


                        <?php if (!empty($fieldErrors['reason'])): ?>

                            <span class="field-error">

                                <?= htmlspecialchars(
                                    $fieldErrors['reason'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        <?php endif; ?>


                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit_adoption"
                        class="submit-btn">

                        Submit Adoption Application

                    </button>


                </form>


            </div>


        <?php elseif ($showDetails && $selectedDog): ?>


            <!-- =================================================
             DOG DETAILS
        ================================================== -->

            <div class="detail-card">


                <a
                    href="userdashboard.php"
                    class="back-btn">

                    ← Back to Dogs

                </a>


                <h1>
                    Dog Details
                </h1>


                <img
                    class="detail-image"
                    src="<?= htmlspecialchars(
                                $selectedDog['dog_image'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                    alt="<?= htmlspecialchars(
                                $selectedDog['dog_breed'] ?? 'Dog',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">


                <div class="info-item">

                    <span class="info-label">
                        Dog ID
                    </span>

                    <span class="info-value">
                        #<?= (int)$selectedDog['dog_id'] ?>
                    </span>

                </div>


                <div class="info-item">

                    <span class="info-label">
                        Breed
                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $selectedDog['dog_breed']
                                ?? 'N/A',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>


                <div class="info-item">

                    <span class="info-label">
                        Age
                    </span>

                    <span class="info-value">

                        <?= htmlspecialchars(
                            $selectedDog['age']
                                ?? 'N/A',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>


                <div class="description-box">

                    <h3>
                        Description
                    </h3>


                    <?php if (
                        !empty(trim(
                                $selectedDog['description'] ?? ''
                            ))
                    ): ?>

                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    $selectedDog['description'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>

                        </p>

                    <?php else: ?>

                        <p class="no-description">

                            No description available for this dog.

                        </p>

                    <?php endif; ?>

                </div>


                <div class="detail-actions">

                    <a
                        href="userdashboard.php?dog_id=<?= (int)$selectedDog['dog_id'] ?>&adopt=1"
                        class="adopt-btn">

                        Adopt This Dog

                    </a>

                </div>


            </div>


        <?php else: ?>


            <!-- =================================================
             DOG LIST
        ================================================== -->

            <form
                method="GET"
                class="search-box">


                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars(
                                $search,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
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
                                            $dog['dog_image'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                alt="<?= htmlspecialchars(
                                            $dog['dog_breed']
                                                ?? 'Dog',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">


                            <div class="dog-content">


                                <h3>

                                    <?= htmlspecialchars(
                                        $dog['dog_breed']
                                            ?? 'Unknown Breed',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </h3>


                                <p>

                                    <strong>
                                        Dog ID:
                                    </strong>

                                    #<?= (int)$dog['dog_id'] ?>

                                </p>


                                <p>

                                    <strong>
                                        Age:
                                    </strong>

                                    <?= htmlspecialchars(
                                        $dog['age'] ?? 'N/A',
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


            <?php endif; ?>


        <?php endif; ?>


    </div>


</body>

</html>