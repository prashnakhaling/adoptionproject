<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


/*
|--------------------------------------------------------------------------
| PHPMailer
|--------------------------------------------------------------------------
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/dataconnection.php';


/*
|--------------------------------------------------------------------------
| UNREAD CHAT COUNT
|--------------------------------------------------------------------------
|
| Only messages sent by users are counted.
| Admin messages are never counted as notifications.
|
*/

$unreadChatCount = 0;

$unreadChatResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM chat_messages
    WHERE sender_type = 'user'
      AND is_read = 0
");

if ($unreadChatResult) {

  $unreadChatRow =
    $unreadChatResult->fetch_assoc();

  $unreadChatCount =
    (int)($unreadChatRow['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| EMAIL CONFIGURATION
|--------------------------------------------------------------------------
*/

$mailUsername = "YOUR_GMAIL@gmail.com";
$mailPassword = "YOUR_16_CHARACTER_APP_PASSWORD";
$mailFromName = "Happy Tails";

$shelterLocation =
  "Happy Tails Shelter, Kathmandu — Mon to Sat, 10am to 5pm";


/*
|--------------------------------------------------------------------------
| SEND APPLICATION EMAIL
|--------------------------------------------------------------------------
*/

function sendApplicationEmail(
  $toEmail,
  $applicantName,
  $dogBreed,
  $status,
  $reason = ''
) {

  global
    $mailUsername,
    $mailPassword,
    $mailFromName,
    $shelterLocation;


  if (
    empty($toEmail) ||
    !filter_var($toEmail, FILTER_VALIDATE_EMAIL)
  ) {

    return false;
  }


  try {

    $mail = new PHPMailer(true);


    /*
        |--------------------------------------------------------------------------
        | SMTP SETTINGS
        |--------------------------------------------------------------------------
        */

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username = $mailUsername;

    $mail->Password = $mailPassword;

    $mail->SMTPSecure =
      PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;


    /*
        |--------------------------------------------------------------------------
        | FROM / TO
        |--------------------------------------------------------------------------
        */

    $mail->setFrom(
      $mailUsername,
      $mailFromName
    );

    $mail->addAddress(
      $toEmail,
      $applicantName
    );

    $mail->isHTML(true);


    /*
        |--------------------------------------------------------------------------
        | ACCEPTED EMAIL
        |--------------------------------------------------------------------------
        */

    if ($status === 'accepted') {

      $mail->Subject =
        "Your Dog Adoption Application Has Been Accepted!";


      $mail->Body = "

                <div style=\"
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: auto;
                    line-height: 1.6;
                    color: #333;
                \">

                    <h2 style=\"color:#5a34ae;\">
                        Application Accepted
                    </h2>

                    <p>
                        Hi
                        <strong>" .
        htmlspecialchars($applicantName) .
        "</strong>,
                    </p>

                    <p>
                        Great news! Your application to adopt
                        <strong>" .
        htmlspecialchars($dogBreed) .
        "</strong>
                        has been
                        <strong>accepted</strong>.
                    </p>

                    <p>
                        Please visit us at:
                    </p>

                    <p>
                        <strong>" .
        htmlspecialchars($shelterLocation) .
        "</strong>
                    </p>

                    <p>
                        We will guide you through the remaining
                        adoption process.
                    </p>

                    <p>
                        Thank you for choosing Happy Tails.
                    </p>

                    <p>
                        Regards,<br>
                        <strong>Happy Tails</strong>
                    </p>

                </div>
            ";


      $mail->AltBody =
        "Hi {$applicantName},\n\n" .
        "Your application to adopt {$dogBreed} has been accepted.\n\n" .
        "Please visit us at {$shelterLocation} to complete the adoption process.\n\n" .
        "Thank you for choosing Happy Tails.\n\n" .
        "Regards,\nHappy Tails";
    }


    /*
        |--------------------------------------------------------------------------
        | DECLINED EMAIL
        |--------------------------------------------------------------------------
        */ elseif ($status === 'declined') {

      $mail->Subject =
        "Update on Your Dog Adoption Application";


      $safeReason =
        htmlspecialchars(
          $reason,
          ENT_QUOTES,
          'UTF-8'
        );


      $mail->Body = "

                <div style=\"
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: auto;
                    line-height: 1.6;
                    color: #333;
                \">

                    <h2 style=\"color:#d64545;\">
                        Application Status Update
                    </h2>

                    <p>
                        Hi
                        <strong>" .
        htmlspecialchars($applicantName) .
        "</strong>,
                    </p>

                    <p>
                        Your application to adopt
                        <strong>" .
        htmlspecialchars($dogBreed) .
        "</strong>
                        has been
                        <strong>declined</strong>.
                    </p>

                    <div style=\"
                        margin: 20px 0;
                        padding: 15px;
                        background: #f8f8f8;
                        border-left: 4px solid #d64545;
                    \">

                        <strong>
                            Reason:
                        </strong>

                        <p style=\"margin:8px 0 0;\">
                            " .
        nl2br($safeReason) .
        "
                        </p>

                    </div>

                    <p>
                        Thank you for your interest in adopting
                        with Happy Tails.
                    </p>

                    <p>
                        You may apply for another available dog
                        in the future.
                    </p>

                    <p>
                        Regards,<br>
                        <strong>Happy Tails</strong>
                    </p>

                </div>
            ";


      $mail->AltBody =
        "Hi {$applicantName},\n\n" .
        "Your application to adopt {$dogBreed} has been declined.\n\n" .
        "Reason: {$reason}\n\n" .
        "Thank you for your interest in adopting with Happy Tails.\n\n" .
        "Regards,\nHappy Tails";
    }


    /*
        |--------------------------------------------------------------------------
        | SEND
        |--------------------------------------------------------------------------
        */

    $mail->send();

    return true;
  } catch (Exception $e) {

    error_log(
      "PHPMailer Error: " .
        $e->getMessage()
    );

    return false;
  }
}


/*
|--------------------------------------------------------------------------
| DELETE DOG
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['delete_dog'])
) {

  $dogId =
    (int)($_POST['dog_id'] ?? 0);


  if ($dogId > 0) {

    $stmt = $conn->prepare(
      "SELECT dog_image
             FROM dogs
             WHERE dog_id = ?"
    );

    $stmt->bind_param(
      "i",
      $dogId
    );

    $stmt->execute();

    $dog =
      $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if ($dog) {

      $deleteStmt = $conn->prepare(
        "DELETE FROM dogs
                 WHERE dog_id = ?"
      );

      $deleteStmt->bind_param(
        "i",
        $dogId
      );

      $deleteStmt->execute();

      $deleteStmt->close();


      if (!empty($dog['dog_image'])) {

        $imageFile =
          __DIR__ .
          '/../' .
          ltrim(
            $dog['dog_image'],
            '/'
          );

        if (file_exists($imageFile)) {

          @unlink($imageFile);
        }
      }
    }
  }


  header(
    "Location: admindashboard.php"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE DOG
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['update_dog'])
) {

  $dogId =
    (int)($_POST['dog_id'] ?? 0);

  $breed =
    trim($_POST['breed'] ?? '');

  $age =
    trim($_POST['age'] ?? '');

  $description =
    trim($_POST['description'] ?? '');


  if (
    $dogId > 0 &&
    $breed !== '' &&
    $age !== ''
  ) {

    $stmt = $conn->prepare(
      "SELECT dog_image
             FROM dogs
             WHERE dog_id = ?"
    );

    $stmt->bind_param(
      "i",
      $dogId
    );

    $stmt->execute();

    $oldDog =
      $stmt->get_result()->fetch_assoc();

    $stmt->close();


    $oldImage =
      $oldDog['dog_image'] ?? '';

    $newImage =
      $oldImage;


    if (
      isset($_FILES['dog_image']) &&
      $_FILES['dog_image']['error'] === UPLOAD_ERR_OK
    ) {

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
        in_array(
          $extension,
          $allowedExtensions,
          true
        )
      ) {

        $uploadDir =
          __DIR__ .
          '/../dogpic/';


        if (!is_dir($uploadDir)) {

          mkdir(
            $uploadDir,
            0777,
            true
          );
        }


        $newFileName =
          uniqid(
            'dog_',
            true
          ) .
          '.' .
          $extension;


        $destination =
          $uploadDir .
          $newFileName;


        if (
          move_uploaded_file(
            $tmpName,
            $destination
          )
        ) {

          $newImage =
            'dogpic/' .
            $newFileName;


          if (!empty($oldImage)) {

            $oldImageFile =
              __DIR__ .
              '/../' .
              ltrim(
                $oldImage,
                '/'
              );

            if (
              file_exists(
                $oldImageFile
              )
            ) {

              @unlink(
                $oldImageFile
              );
            }
          }
        }
      }
    }


    $updateStmt = $conn->prepare(
      "UPDATE dogs
             SET
                dog_breed = ?,
                age = ?,
                description = ?,
                dog_image = ?
             WHERE dog_id = ?"
    );


    $updateStmt->bind_param(
      "sissi",
      $breed,
      $age,
      $description,
      $newImage,
      $dogId
    );


    $updateStmt->execute();

    $updateStmt->close();
  }


  header(
    "Location: admindashboard.php"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| ACCEPT APPLICATION
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['accept_application'])
) {

  $applicationId =
    (int)(
      $_POST['application_id'] ?? 0
    );


  if ($applicationId > 0) {

    $stmt = $conn->prepare("
            SELECT
                aa.owner_name,
                aa.dog_breed,
                u.email
            FROM adoption_applications aa
            LEFT JOIN users u
                ON u.name = aa.owner_name
            WHERE aa.id = ?
            LIMIT 1
        ");


    $stmt->bind_param(
      "i",
      $applicationId
    );


    $stmt->execute();


    $app =
      $stmt
      ->get_result()
      ->fetch_assoc();


    $stmt->close();


    if ($app) {

      $updateStmt =
        $conn->prepare("
                    UPDATE adoption_applications
                    SET
                        status = 'accepted',
                        decline_reason = NULL
                    WHERE id = ?
                ");


      $updateStmt->bind_param(
        "i",
        $applicationId
      );


      $updateStmt->execute();

      $updateStmt->close();


      sendApplicationEmail(
        $app['email'],
        $app['owner_name'],
        $app['dog_breed'],
        'accepted'
      );
    }
  }


  header(
    "Location: admindashboard.php"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| DECLINE APPLICATION
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['decline_application'])
) {

  $applicationId =
    (int)(
      $_POST['application_id'] ?? 0
    );

  $declineReason =
    trim(
      $_POST['decline_reason'] ?? ''
    );


  if (
    $applicationId > 0 &&
    $declineReason !== ''
  ) {

    $stmt = $conn->prepare("
            SELECT
                aa.owner_name,
                aa.dog_breed,
                u.email
            FROM adoption_applications aa
            LEFT JOIN users u
                ON u.name = aa.owner_name
            WHERE aa.id = ?
            LIMIT 1
        ");


    $stmt->bind_param(
      "i",
      $applicationId
    );


    $stmt->execute();


    $app =
      $stmt
      ->get_result()
      ->fetch_assoc();


    $stmt->close();


    if ($app) {

      $updateStmt =
        $conn->prepare("
                    UPDATE adoption_applications
                    SET
                        status = 'declined',
                        decline_reason = ?
                    WHERE id = ?
                ");


      $updateStmt->bind_param(
        "si",
        $declineReason,
        $applicationId
      );


      $updateStmt->execute();

      $updateStmt->close();


      sendApplicationEmail(
        $app['email'],
        $app['owner_name'],
        $app['dog_breed'],
        'declined',
        $declineReason
      );
    }
  }


  header(
    "Location: admindashboard.php"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| LOAD COUNTS
|--------------------------------------------------------------------------
*/

$pendingCount = 0;
$acceptedCount = 0;
$declinedCount = 0;
$totalDogs = 0;


$countResult =
  $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'pending') AS pending,
            SUM(status = 'accepted') AS accepted,
            SUM(status = 'declined') AS declined
        FROM adoption_applications
    ");


if ($countResult) {

  $counts =
    $countResult->fetch_assoc();

  $pendingCount =
    (int)($counts['pending'] ?? 0);

  $acceptedCount =
    (int)($counts['accepted'] ?? 0);

  $declinedCount =
    (int)($counts['declined'] ?? 0);
}


$dogCountResult =
  $conn->query(
    "SELECT COUNT(*) AS total
         FROM dogs"
  );


if ($dogCountResult) {

  $dogCountRow =
    $dogCountResult->fetch_assoc();

  $totalDogs =
    (int)($dogCountRow['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| LOAD DOGS
|--------------------------------------------------------------------------
*/

$dogs = [];

$dogsResult =
  $conn->query("
        SELECT
            dog_id,
            dog_breed,
            age,
            description,
            dog_image,
            added_date
        FROM dogs
        ORDER BY added_date DESC
    ");


if ($dogsResult) {

  while (
    $row =
    $dogsResult->fetch_assoc()
  ) {

    $dogs[] = $row;
  }
}


/*
|--------------------------------------------------------------------------
| LOAD APPLICATIONS
|--------------------------------------------------------------------------
*/

$applications = [];

$applicationsResult =
  $conn->query("
        SELECT
            aa.id AS application_id,
            aa.owner_name,
            aa.dog_id,
            aa.dog_breed,
            aa.phone,
            aa.address,
            aa.reason,
            aa.status,
            aa.decline_reason,
            aa.created_at,
            u.email AS applicant_email

        FROM adoption_applications aa

        LEFT JOIN users u
            ON u.name = aa.owner_name

        ORDER BY aa.created_at DESC
    ");


if ($applicationsResult) {

  while (
    $row =
    $applicationsResult->fetch_assoc()
  ) {

    $applications[] = $row;
  }
}


/*
|--------------------------------------------------------------------------
| IMAGE HELPER
|--------------------------------------------------------------------------
*/

function getValidImagePath($imagePath)
{

  if (empty($imagePath)) {

    return 'placeholder.jpg';
  }


  $cleanPath =
    ltrim(
      $imagePath,
      '/'
    );


  $fullPath =
    __DIR__ .
    '/../' .
    $cleanPath;


  if (
    file_exists($fullPath)
  ) {

    return '../' . $cleanPath;
  }


  return 'placeholder.jpg';
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
    Happy Tails Admin Dashboard
  </title>


  <style>
    * {
      box-sizing: border-box;
    }


    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: #f6f4fb;
      color: #222;
    }


    button,
    input,
    textarea {
      font-family: inherit;
    }


    /* =========================
           SIDEBAR
        ========================= */

    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: 245px;
      background: #5a34ae;
      color: #fff;
      padding: 25px 18px;
      z-index: 1000;
    }


    .brand {
      font-size: 23px;
      font-weight: 700;
      text-align: center;
      margin-bottom: 35px;
    }


    .brand span {
      display: block;
      font-size: 12px;
      font-weight: 400;
      opacity: .8;
      margin-top: 4px;
    }


    .nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      padding: 13px 15px;
      margin-bottom: 8px;
      color: #fff;
      text-decoration: none;
      border-radius: 10px;
      transition: .2s;
      cursor: pointer;
      border: none;
      background: transparent;
      font-size: 14px;
      text-align: left;
    }


    .nav-link:hover,
    .nav-link.active {
      background: rgba(255, 255, 255, .16);
    }


    /* =========================
           CHAT NAVIGATION
        ========================= */

    .chat-nav-link {
      position: relative;
    }


    .chat-nav-link>span:first-child {
      flex: 1;
    }


    .chat-notification {
      min-width: 22px;
      height: 22px;

      padding: 0 6px;

      background: #ff4d5a;
      color: #fff;

      border-radius: 50px;

      display: inline-flex;
      align-items: center;
      justify-content: center;

      font-size: 11px;
      font-weight: 700;

      box-shadow:
        0 2px 6px rgba(0, 0, 0, .18);
    }


    .sidebar-bottom {
      position: absolute;
      left: 18px;
      right: 18px;
      bottom: 20px;
    }


    /* =========================
           MAIN
        ========================= */

    .main {
      margin-left: 245px;
      padding: 30px;
    }


    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      margin-bottom: 28px;
    }


    .topbar h1 {
      margin: 0;
      font-size: 27px;
      color: #2b2050;
    }


    .topbar p {
      margin: 6px 0 0;
      color: #777;
      font-size: 14px;
    }


    .datetime {
      background: #fff;
      padding: 12px 18px;
      border-radius: 12px;
      box-shadow:
        0 4px 16px rgba(0, 0, 0, .06);
      color: #5a34ae;
      font-weight: 600;
      font-size: 14px;
    }


    /* =========================
           STAT CARDS
        ========================= */

    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
      margin-bottom: 28px;
    }


    .stat-card {
      background: #fff;
      border-radius: 15px;
      padding: 22px;
      box-shadow:
        0 5px 20px rgba(0, 0, 0, .06);
    }


    .stat-label {
      font-size: 13px;
      color: #777;
      margin-bottom: 10px;
    }


    .stat-number {
      font-size: 29px;
      font-weight: 700;
      color: #5a34ae;
    }


    /* =========================
           SECTION
        ========================= */

    .section {
      background: #fff;
      border-radius: 16px;
      padding: 22px;
      margin-bottom: 25px;
      box-shadow:
        0 5px 20px rgba(0, 0, 0, .05);
    }


    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 15px;
      margin-bottom: 18px;
    }


    .section-header h2 {
      margin: 0;
      font-size: 20px;
      color: #2b2050;
    }


    .primary-btn {
      border: none;
      background: #5a34ae;
      color: #fff;
      padding: 11px 17px;
      border-radius: 9px;
      cursor: pointer;
      font-weight: 600;
    }


    .primary-btn:hover {
      background: #48258f;
    }


    /* =========================
           TABLE
        ========================= */

    .table-wrap {
      width: 100%;
      overflow-x: auto;
    }


    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
    }


    th {
      background: #f3f0fa;
      color: #4b4161;
      font-size: 13px;
      text-align: left;
      padding: 13px;
    }


    td {
      padding: 13px;
      border-bottom: 1px solid #eee;
      font-size: 13px;
      vertical-align: top;
    }


    tr:last-child td {
      border-bottom: none;
    }


    .dog-thumb {
      width: 65px;
      height: 55px;
      object-fit: cover;
      border-radius: 8px;
      background: #eee;
    }


    .action-buttons {
      display: flex;
      gap: 7px;
      flex-wrap: wrap;
    }


    .edit-btn,
    .delete-btn,
    .accept-btn,
    .decline-btn {
      border: none;
      border-radius: 7px;
      padding: 8px 11px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
    }


    .edit-btn {
      background: #ece8fa;
      color: #5a34ae;
    }


    .delete-btn {
      background: #ffe8e8;
      color: #c03939;
    }


    .accept-btn {
      background: #e3f7e8;
      color: #218838;
    }


    .decline-btn {
      background: #ffe6e6;
      color: #d64545;
    }


    /* =========================
           STATUS
        ========================= */

    .status {
      display: inline-block;
      padding: 5px 9px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: capitalize;
    }


    .status.pending {
      background: #fff3cd;
      color: #946c00;
    }


    .status.accepted {
      background: #dff5e4;
      color: #237a38;
    }


    .status.declined {
      background: #ffe0e0;
      color: #bd3333;
    }


    .reason-box {
      background: #fff4f4;
      border-left: 3px solid #d64545;
      padding: 8px 10px;
      margin-top: 7px;
      font-size: 12px;
      line-height: 1.5;
    }


    .email-text {
      color: #5a34ae;
      word-break: break-word;
    }


    /* =========================
           MODAL
        ========================= */

    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(20, 10, 35, .55);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }


    .modal.show {
      display: flex;
    }


    .modal-content {
      background: #fff;
      width: 100%;
      max-width: 520px;
      max-height: 90vh;
      overflow-y: auto;
      border-radius: 16px;
      padding: 25px;
      position: relative;
    }


    .modal-content.large {
      max-width: 1050px;
    }


    .modal-content h2 {
      margin: 0 0 20px;
      color: #2b2050;
    }


    .close-btn {
      position: absolute;
      top: 15px;
      right: 17px;
      width: 32px;
      height: 32px;
      border: none;
      border-radius: 50%;
      background: #f0edf5;
      color: #555;
      cursor: pointer;
      font-size: 18px;
    }


    .form-group {
      margin-bottom: 15px;
    }


    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 7px;
      color: #514761;
    }


    .form-control {
      width: 100%;
      padding: 11px 13px;
      border: 1px solid #ddd;
      border-radius: 8px;
      outline: none;
      background: #fafafa;
    }


    .form-control:focus {
      border-color: #5a34ae;
      background: #fff;
    }


    textarea.form-control {
      min-height: 100px;
      resize: vertical;
    }


    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }


    .cancel-btn {
      border: none;
      background: #eee;
      padding: 11px 18px;
      border-radius: 8px;
      cursor: pointer;
    }


    /* =========================
           NEW CHAT POPUP
        ========================= */

    .chat-popup-notification {

      position: fixed;

      right: 25px;
      bottom: 25px;

      width: 340px;

      background: #ffffff;

      border-radius: 16px;

      padding: 16px 18px;

      display: none;

      align-items: center;

      gap: 14px;

      box-shadow:
        0 10px 35px rgba(0, 0, 0, 0.20);

      border-left:
        5px solid #5a34ae;

      z-index: 99999;

      animation:
        chatPopupIn .35s ease;
    }


    .chat-popup-icon {

      width: 45px;
      height: 45px;

      background: #eee8ff;

      color: #5a34ae;

      border-radius: 50%;

      display: flex;

      align-items: center;
      justify-content: center;

      font-size: 21px;

      flex-shrink: 0;
    }


    .chat-popup-content {
      flex: 1;
    }


    .chat-popup-title {

      font-size: 15px;

      font-weight: 700;

      color: #222;

      margin-bottom: 4px;
    }


    .chat-popup-text {

      font-size: 13px;

      color: #666;
    }


    .chat-popup-close {

      border: none;

      background: transparent;

      font-size: 20px;

      color: #888;

      cursor: pointer;

      padding: 3px;
    }


    .chat-popup-close:hover {
      color: #333;
    }


    @keyframes chatPopupIn {

      from {

        transform:
          translateY(30px);

        opacity: 0;

      }

      to {

        transform:
          translateY(0);

        opacity: 1;

      }
    }


    /* =========================
           MOBILE
        ========================= */

    .mobile-menu {
      display: none;
    }


    @media (max-width: 1000px) {

      .sidebar {
        width: 210px;
      }

      .main {
        margin-left: 210px;
        padding: 20px;
      }

      .stats {
        grid-template-columns:
          repeat(2, 1fr);
      }
    }


    @media (max-width: 750px) {

      .sidebar {
        display: none;
      }


      .main {
        margin-left: 0;
        padding: 15px;
        padding-top: 70px;
      }


      .mobile-menu {

        display: flex;

        position: fixed;

        top: 0;
        left: 0;
        right: 0;

        height: 58px;

        background: #5a34ae;

        color: #fff;

        z-index: 1500;

        align-items: center;

        justify-content:
          space-between;

        padding: 0 16px;
      }


      .mobile-menu strong {
        font-size: 17px;
      }


      .mobile-menu button {

        border: none;

        background: transparent;

        color: #fff;

        font-size: 24px;

        cursor: pointer;
      }


      .topbar {

        flex-direction: column;

        align-items:
          flex-start;
      }


      .stats {

        grid-template-columns:
          1fr 1fr;
      }


      .chat-popup-notification {

        left: 15px;
        right: 15px;

        bottom: 15px;

        width: auto;
      }
    }


    @media (max-width: 500px) {

      .stats {
        grid-template-columns: 1fr;
      }


      .section {
        padding: 15px;
      }


      .modal {
        padding: 10px;
      }


      .modal-content {
        padding: 20px 15px;
      }
    }
  </style>

</head>


<body>


  <!-- =========================
     MOBILE TOP BAR
========================= -->

  <div class="mobile-menu">

    <strong>
      Happy Tails
    </strong>

    <button
      onclick="toggleMobileSidebar()">
      ☰
    </button>

  </div>


  <!-- =========================
     SIDEBAR
========================= -->

  <aside
    class="sidebar"
    id="sidebar">

    <div class="brand">

      🐾 Happy Tails

      <span>
        Admin Dashboard
      </span>

    </div>


    <a
      href="#dashboard"
      class="nav-link active">

      🏠 Dashboard

    </a>


    <a
      href="#dogs"
      class="nav-link">

      🐕 Dogs

    </a>


    <a
      href="#applications"
      class="nav-link">

      📋 Applications

    </a>


    <!-- CHAT SUPPORT -->

    <a
      href="admin_chat.php"
      class="nav-link chat-nav-link">

      <span>
        💬 Chat Support
      </span>


      <?php if ($unreadChatCount > 0): ?>

        <span
          class="chat-notification">

          <?php
          echo $unreadChatCount;
          ?>

        </span>

      <?php endif; ?>

    </a>


    <div class="sidebar-bottom">

      <a
        href="../index.php"
        class="nav-link">

        ← Back to Website

      </a>

    </div>

  </aside>


  <!-- =========================
     MAIN
========================= -->

  <main class="main">


    <!-- TOPBAR -->

    <div
      class="topbar"
      id="dashboard">

      <div>

        <h1>
          Admin Dashboard
        </h1>

        <p>
          Manage dogs and adoption applications
        </p>

      </div>


      <div
        class="datetime"
        id="datetime">

        Loading...

      </div>

    </div>


    <!-- =========================
         STATISTICS
    ========================= -->

    <div class="stats">


      <div class="stat-card">

        <div class="stat-label">
          Total Dogs
        </div>

        <div class="stat-number">

          <?php
          echo $totalDogs;
          ?>

        </div>

      </div>


      <div class="stat-card">

        <div class="stat-label">
          Pending Applications
        </div>

        <div class="stat-number">

          <?php
          echo $pendingCount;
          ?>

        </div>

      </div>


      <div class="stat-card">

        <div class="stat-label">
          Accepted Applications
        </div>

        <div class="stat-number">

          <?php
          echo $acceptedCount;
          ?>

        </div>

      </div>


      <div class="stat-card">

        <div class="stat-label">
          Declined Applications
        </div>

        <div class="stat-number">

          <?php
          echo $declinedCount;
          ?>

        </div>

      </div>

    </div>


    <!-- =========================
         DOGS SECTION
    ========================= -->

    <section
      class="section"
      id="dogs">

      <div class="section-header">

        <h2>
          Dogs
        </h2>

        <button
          class="primary-btn"
          onclick="openModal('addDogModal')">

          + Add Dog

        </button>

      </div>


      <div class="table-wrap">

        <table>

          <thead>

            <tr>

              <th>
                Image
              </th>

              <th>
                Breed
              </th>

              <th>
                Age
              </th>

              <th>
                Description
              </th>

              <th>
                Added Date
              </th>

              <th>
                Action
              </th>

            </tr>

          </thead>


          <tbody>


            <?php if (empty($dogs)): ?>

              <tr>

                <td
                  colspan="6"
                  style="text-align:center;">

                  No dogs found.

                </td>

              </tr>


            <?php else: ?>


              <?php foreach ($dogs as $dog): ?>

                <tr>

                  <td>

                    <img
                      class="dog-thumb"
                      src="<?php
                            echo htmlspecialchars(
                              getValidImagePath(
                                $dog['dog_image']
                              )
                            );
                            ?>"
                      alt="<?php
                            echo htmlspecialchars(
                              $dog['dog_breed']
                            );
                            ?>">

                  </td>


                  <td>

                    <strong>

                      <?php
                      echo htmlspecialchars(
                        $dog['dog_breed']
                      );
                      ?>

                    </strong>

                  </td>


                  <td>

                    <?php
                    echo htmlspecialchars(
                      $dog['age']
                    );
                    ?>

                    yrs

                  </td>


                  <td>

                    <?php
                    echo htmlspecialchars(
                      $dog['description'] ?? ''
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo htmlspecialchars(
                      $dog['added_date']
                    );
                    ?>

                  </td>


                  <td>

                    <div class="action-buttons">


                      <!-- EDIT -->

                      <button
                        class="edit-btn"
                        onclick='openEditDog(<?php
                                              echo json_encode([
                                                "dog_id" =>
                                                $dog["dog_id"],

                                                "dog_breed" =>
                                                $dog["dog_breed"],

                                                "age" =>
                                                $dog["age"],

                                                "description" =>
                                                $dog["description"]
                                                  ?? "",

                                                "dog_image" =>
                                                $dog["dog_image"]
                                                  ?? ""
                                              ]);
                                              ?>)'>

                        Edit

                      </button>


                      <!-- DELETE -->

                      <form
                        method="POST"
                        style="display:inline;"
                        onsubmit="
                                            return confirm(
                                                'Are you sure you want to delete this dog?'
                                            );
                                        ">

                        <input
                          type="hidden"
                          name="dog_id"
                          value="<?php
                                  echo (int)
                                  $dog['dog_id'];
                                  ?>">

                        <button
                          type="submit"
                          name="delete_dog"
                          class="delete-btn">

                          Delete

                        </button>

                      </form>


                    </div>

                  </td>

                </tr>

              <?php endforeach; ?>


            <?php endif; ?>


          </tbody>

        </table>

      </div>

    </section>


    <!-- =========================
         APPLICATIONS
    ========================= -->

    <section
      class="section"
      id="applications">

      <div class="section-header">

        <h2>
          Adoption Applications
        </h2>

      </div>


      <div class="table-wrap">

        <table>

          <thead>

            <tr>

              <th>
                Applicant
              </th>

              <th>
                Email
              </th>

              <th>
                Dog
              </th>

              <th>
                Phone
              </th>

              <th>
                Address
              </th>

              <th>
                Reason
              </th>

              <th>
                Status
              </th>

              <th>
                Action
              </th>

            </tr>

          </thead>


          <tbody>


            <?php if (empty($applications)): ?>

              <tr>

                <td
                  colspan="8"
                  style="text-align:center;">

                  No applications found.

                </td>

              </tr>


            <?php else: ?>


              <?php foreach (
                $applications
                as $application
              ): ?>


                <tr>


                  <!-- APPLICANT -->

                  <td>

                    <strong>

                      <?php
                      echo htmlspecialchars(
                        $application['owner_name']
                      );
                      ?>

                    </strong>

                  </td>


                  <!-- EMAIL -->

                  <td>

                    <span
                      class="email-text">

                      <?php

                      echo htmlspecialchars(
                        $application['applicant_email']
                          ??
                          'No email found'
                      );

                      ?>

                    </span>

                  </td>


                  <!-- DOG -->

                  <td>

                    <?php
                    echo htmlspecialchars(
                      $application['dog_breed']
                    );
                    ?>

                  </td>


                  <!-- PHONE -->

                  <td>

                    <?php
                    echo htmlspecialchars(
                      $application['phone']
                    );
                    ?>

                  </td>


                  <!-- ADDRESS -->

                  <td>

                    <?php
                    echo htmlspecialchars(
                      $application['address']
                    );
                    ?>

                  </td>


                  <!-- REASON -->

                  <td>

                    <?php
                    echo htmlspecialchars(
                      $application['reason'] ?? ''
                    );
                    ?>


                    <?php

                    if (
                      $application['status'] === 'declined' &&
                      !empty($application['decline_reason'])
                    ):

                    ?>

                      <div
                        class="reason-box">

                        <strong>
                          Decline Reason:
                        </strong>

                        <br>

                        <?php
                        echo nl2br(
                          htmlspecialchars(
                            $application['decline_reason']
                          )
                        );
                        ?>

                      </div>

                    <?php endif; ?>

                  </td>


                  <!-- STATUS -->

                  <td>

                    <?php

                    $status =
                      $application['status']
                      ?: 'pending';

                    ?>

                    <span
                      class="status <?php
                                    echo htmlspecialchars(
                                      $status
                                    );
                                    ?>">

                      <?php
                      echo htmlspecialchars(
                        $status
                      );
                      ?>

                    </span>

                  </td>


                  <!-- ACTION -->

                  <td>

                    <?php
                    if (
                      $status === 'pending'
                    ):
                    ?>

                      <div
                        class="action-buttons">


                        <!-- ACCEPT -->

                        <form
                          method="POST"
                          style="display:inline;"
                          onsubmit="
                                            return confirm(
                                                'Accept this application?'
                                            );
                                        ">

                          <input
                            type="hidden"
                            name="application_id"
                            value="<?php
                                    echo (int)
                                    $application['application_id'];
                                    ?>">

                          <button
                            type="submit"
                            name="accept_application"
                            class="accept-btn">

                            Accept

                          </button>

                        </form>


                        <!-- DECLINE -->

                        <button
                          type="button"
                          class="decline-btn"
                          onclick="
                                            openDeclineModal(
                                                <?php
                                                echo (int)
                                                $application['application_id'];
                                                ?>,
                                                '<?php
                                                  echo htmlspecialchars(
                                                    $application['owner_name'],
                                                    ENT_QUOTES
                                                  );
                                                  ?>'
                                            )
                                        ">

                          Decline

                        </button>


                      </div>


                    <?php else: ?>


                      <span
                        style="
                                        color:#999;
                                        font-size:12px;
                                    ">

                        No action

                      </span>


                    <?php endif; ?>

                  </td>

                </tr>


              <?php endforeach; ?>


            <?php endif; ?>


          </tbody>

        </table>

      </div>

    </section>


  </main>


  <!-- =========================================================
     ADD DOG MODAL
========================================================= -->

  <div
    class="modal"
    id="addDogModal">

    <div class="modal-content">

      <button
        class="close-btn"
        onclick="closeModal('addDogModal')">

        ×

      </button>


      <h2>
        Add New Dog
      </h2>


      <form
        method="POST"
        action="adddog.php"
        enctype="multipart/form-data">


        <div class="form-group">

          <label>
            Dog Breed
          </label>

          <input
            type="text"
            name="dog_breed"
            class="form-control"
            placeholder="Enter dog breed"
            required>

        </div>


        <div class="form-group">

          <label>
            Age
          </label>

          <input
            type="text"
            name="age"
            class="form-control"
            placeholder="Enter age"
            required>

        </div>


        <div class="form-group">

          <label>
            Description
          </label>

          <textarea
            name="description"
            class="form-control"
            placeholder="Enter dog description"></textarea>

        </div>


        <div class="form-group">

          <label>
            Dog Image
          </label>

          <input
            type="file"
            name="dog_image"
            class="form-control"
            accept=".jpg,.jpeg,.png,.gif,.webp,.jfif">

        </div>


        <div class="modal-footer">

          <button
            type="button"
            class="cancel-btn"
            onclick="closeModal('addDogModal')">

            Cancel

          </button>


          <button
            type="submit"
            class="primary-btn"
            name="add_dog">

            Save Dog

          </button>

        </div>

      </form>

    </div>

  </div>


  <!-- =========================================================
     EDIT DOG MODAL
========================================================= -->

  <div
    class="modal"
    id="editDogModal">

    <div class="modal-content">

      <button
        class="close-btn"
        onclick="closeModal('editDogModal')">

        ×

      </button>


      <h2>
        Edit Dog
      </h2>


      <form
        method="POST"
        action=""
        enctype="multipart/form-data">


        <input
          type="hidden"
          name="dog_id"
          id="editDogId">


        <div class="form-group">

          <label>
            Dog Breed
          </label>

          <input
            type="text"
            name="breed"
            id="editDogBreed"
            class="form-control"
            required>

        </div>


        <div class="form-group">

          <label>
            Age
          </label>

          <input
            type="text"
            name="age"
            id="editDogAge"
            class="form-control"
            required>

        </div>


        <div class="form-group">

          <label>
            Description
          </label>

          <textarea
            name="description"
            id="editDogDescription"
            class="form-control"></textarea>

        </div>


        <div class="form-group">

          <label>
            Replace Image
          </label>

          <input
            type="file"
            name="dog_image"
            class="form-control"
            accept=".jpg,.jpeg,.png,.gif,.webp,.jfif">

        </div>


        <div class="modal-footer">

          <button
            type="button"
            class="cancel-btn"
            onclick="closeModal('editDogModal')">

            Cancel

          </button>


          <button
            type="submit"
            name="update_dog"
            class="primary-btn">

            Save Changes

          </button>

        </div>

      </form>

    </div>

  </div>


  <!-- =========================================================
     DECLINE APPLICATION MODAL
========================================================= -->

  <div
    class="modal"
    id="declineModal">

    <div class="modal-content">

      <button
        class="close-btn"
        onclick="closeModal('declineModal')">

        ×

      </button>


      <h2>
        Decline Application
      </h2>


      <p
        style="
                color:#666;
                font-size:14px;
                margin-top:-8px;
                margin-bottom:18px;
            ">

        Please enter the reason for declining
        <strong id="declineApplicantName"></strong>'s
        application.

      </p>


      <form
        method="POST"
        action="">


        <input
          type="hidden"
          name="application_id"
          id="declineApplicationId">


        <div class="form-group">

          <label>
            Decline Reason
          </label>

          <textarea
            name="decline_reason"
            class="form-control"
            placeholder="Enter the reason for declining this application..."
            required
            id="declineReason"></textarea>

        </div>


        <div class="modal-footer">

          <button
            type="button"
            class="cancel-btn"
            onclick="closeModal('declineModal')">

            Cancel

          </button>


          <button
            type="submit"
            name="decline_application"
            class="decline-btn"
            style="
                        padding:11px 18px;
                        font-size:13px;
                    ">

            Decline Application

          </button>

        </div>

      </form>

    </div>

  </div>


  <!-- =========================================================
     NEW CHAT POPUP
========================================================= -->

  <div
    id="chatPopupNotification"
    class="chat-popup-notification">

    <div class="chat-popup-icon">
      💬
    </div>


    <div class="chat-popup-content">

      <div class="chat-popup-title">
        New Chat Message
      </div>

      <div class="chat-popup-text">
        A user has sent you a new message.
      </div>

    </div>


    <button
      type="button"
      class="chat-popup-close"
      onclick="closeChatPopup()">

      ×

    </button>

  </div>


  <script>
    /*
|--------------------------------------------------------------------------
| MODAL FUNCTIONS
|--------------------------------------------------------------------------
*/

    function openModal(id) {

      const modal =
        document.getElementById(id);

      if (modal) {

        modal.classList.add('show');

      }
    }


    function closeModal(id) {

      const modal =
        document.getElementById(id);

      if (modal) {

        modal.classList.remove('show');

      }
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT DOG
    |--------------------------------------------------------------------------
    */

    function openEditDog(dog) {

      document.getElementById(
          'editDogId'
        ).value =
        dog.dog_id;


      document.getElementById(
          'editDogBreed'
        ).value =
        dog.dog_breed;


      document.getElementById(
          'editDogAge'
        ).value =
        dog.age;


      document.getElementById(
          'editDogDescription'
        ).value =
        dog.description || '';


      openModal(
        'editDogModal'
      );
    }


    /*
    |--------------------------------------------------------------------------
    | DECLINE MODAL
    |--------------------------------------------------------------------------
    */

    function openDeclineModal(
      applicationId,
      applicantName
    ) {

      document.getElementById(
          'declineApplicationId'
        ).value =
        applicationId;


      document.getElementById(
          'declineApplicantName'
        ).textContent =
        applicantName;


      document.getElementById(
          'declineReason'
        ).value =
        '';


      openModal(
        'declineModal'
      );
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL OUTSIDE CLICK
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
      'click',
      function(event) {

        if (
          event.target.classList.contains(
            'modal'
          )
        ) {

          event.target.classList.remove(
            'show'
          );

        }

      }
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
      'keydown',
      function(event) {

        if (
          event.key === 'Escape'
        ) {

          document
            .querySelectorAll(
              '.modal.show'
            )
            .forEach(
              modal =>
              modal.classList.remove(
                'show'
              )
            );

        }

      }
    );


    /*
    |--------------------------------------------------------------------------
    | LIVE DATE AND TIME
    |--------------------------------------------------------------------------
    */

    function updateDateTime() {

      const now =
        new Date();


      const options = {

        weekday: 'short',

        year: 'numeric',

        month: 'short',

        day: 'numeric',

        hour: '2-digit',

        minute: '2-digit',

        second: '2-digit'

      };


      const element =
        document.getElementById(
          'datetime'
        );


      if (element) {

        element.textContent =
          now.toLocaleString(
            'en-US',
            options
          );

      }
    }


    updateDateTime();


    setInterval(
      updateDateTime,
      1000
    );


    /*
    |--------------------------------------------------------------------------
    | MOBILE SIDEBAR
    |--------------------------------------------------------------------------
    */

    function toggleMobileSidebar() {

      const sidebar =
        document.getElementById(
          'sidebar'
        );


      if (!sidebar) {

        return;

      }


      if (
        sidebar.style.display ===
        'block'
      ) {

        sidebar.style.display =
          'none';

      } else {

        sidebar.style.display =
          'block';

        sidebar.style.width =
          '245px';

        sidebar.style.zIndex =
          '2000';

      }
    }


    /*
    |--------------------------------------------------------------------------
    | NAVIGATION
    |--------------------------------------------------------------------------
    */

    document
      .querySelectorAll(
        '.nav-link'
      )
      .forEach(
        link => {

          link.addEventListener(
            'click',
            function() {

              document
                .querySelectorAll(
                  '.nav-link'
                )
                .forEach(
                  item =>
                  item.classList.remove(
                    'active'
                  )
                );


              if (
                this.getAttribute(
                  'href'
                ) &&
                this.getAttribute(
                  'href'
                ).startsWith('#')
              ) {

                this.classList.add(
                  'active'
                );

              }

            }
          );

        }
      );


    /*
    |--------------------------------------------------------------------------
    | CHAT NOTIFICATION SYSTEM
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | The initial unread count comes from PHP.
    |
    | If current count = 2
    | and new count = 2
    | -> NO SOUND
    | -> NO POPUP
    |
    | If user sends another message:
    | current count = 2
    | new count = 3
    | -> SOUND
    | -> POPUP
    | -> BADGE = 3
    |
    |--------------------------------------------------------------------------
    */

    let currentUnreadChatCount =
      <?php echo $unreadChatCount; ?>;


    let chatPopupTimer = null;


    /*
    |--------------------------------------------------------------------------
    | PLAY CHAT NOTIFICATION SOUND
    |--------------------------------------------------------------------------
    */

    function playChatNotificationSound() {

      try {

        const AudioContext =
          window.AudioContext ||
          window.webkitAudioContext;


        if (!AudioContext) {

          return;

        }


        const audioContext =
          new AudioContext();


        const oscillator =
          audioContext.createOscillator();


        const gainNode =
          audioContext.createGain();


        oscillator.type =
          'sine';


        /*
        | First tone
        */

        oscillator.frequency.setValueAtTime(
          880,
          audioContext.currentTime
        );


        /*
        | Second tone
        */

        oscillator.frequency.setValueAtTime(
          660,
          audioContext.currentTime + 0.12
        );


        /*
        | Volume
        */

        gainNode.gain.setValueAtTime(
          0.001,
          audioContext.currentTime
        );


        gainNode.gain.exponentialRampToValueAtTime(
          0.25,
          audioContext.currentTime + 0.02
        );


        gainNode.gain.exponentialRampToValueAtTime(
          0.001,
          audioContext.currentTime + 0.35
        );


        oscillator.connect(
          gainNode
        );


        gainNode.connect(
          audioContext.destination
        );


        oscillator.start();


        oscillator.stop(
          audioContext.currentTime + 0.35
        );


      } catch (error) {

        console.log(
          'Chat sound error:',
          error
        );

      }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW CHAT POPUP
    |--------------------------------------------------------------------------
    */

    function showChatPopup() {

      const popup =
        document.getElementById(
          'chatPopupNotification'
        );


      if (!popup) {

        return;

      }


      popup.style.display =
        'flex';


      /*
      | Play sound ONLY for a new message
      */

      playChatNotificationSound();


      /*
      | Remove previous timer
      */

      clearTimeout(
        chatPopupTimer
      );


      /*
      | Hide popup after 6 seconds
      */

      chatPopupTimer =
        setTimeout(
          function() {

            closeChatPopup();

          },
          6000
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE CHAT POPUP
    |--------------------------------------------------------------------------
    */

    function closeChatPopup() {

      const popup =
        document.getElementById(
          'chatPopupNotification'
        );


      if (popup) {

        popup.style.display =
          'none';

      }
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK NEW CHAT MESSAGES
    |--------------------------------------------------------------------------
    */

    function checkNewChatMessages() {

      fetch(
          'chat_notification.php', {
            cache: 'no-store'
          }
        )

        .then(
          function(response) {

            if (!response.ok) {

              throw new Error(
                'Notification request failed'
              );

            }


            return response.json();

          }
        )

        .then(
          function(data) {

            if (!data.success) {

              return;

            }


            const newCount =
              parseInt(
                data.unread_count || 0
              );


            /*
            |--------------------------------------------------------------------------
            | UPDATE SIDEBAR BADGE
            |--------------------------------------------------------------------------
            */

            const chatLink =
              document.querySelector(
                '.chat-nav-link'
              );


            if (chatLink) {

              let badge =
                chatLink.querySelector(
                  '.chat-notification'
                );


              /*
              | If unread messages exist
              */

              if (newCount > 0) {

                /*
                | Create badge if it does not exist
                */

                if (!badge) {

                  badge =
                    document.createElement(
                      'span'
                    );


                  badge.className =
                    'chat-notification';


                  chatLink.appendChild(
                    badge
                  );

                }


                badge.textContent =
                  newCount;

              }


              /*
              | If there are no unread messages
              */
              else {

                if (badge) {

                  badge.remove();

                }

              }

            }


            /*
            |--------------------------------------------------------------------------
            | NEW MESSAGE DETECTION
            |--------------------------------------------------------------------------
            |
            | This is the important part.
            |
            | Sound + popup only happen when
            | unread count INCREASES.
            |
            */

            if (
              newCount >
              currentUnreadChatCount
            ) {

              showChatPopup();

            }


            /*
            | Save latest count
            */

            currentUnreadChatCount =
              newCount;

          }
        )

        .catch(
          function(error) {

            console.log(
              'Chat notification error:',
              error
            );

          }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EVERY 5 SECONDS
    |--------------------------------------------------------------------------
    */

    setInterval(
      checkNewChatMessages,
      5000
    );
  </script>


</body>

</html>