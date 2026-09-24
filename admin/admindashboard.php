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


/* Ensure dog size and gender columns exist */
$sizeColumnCheck = $conn->query("SHOW COLUMNS FROM dogs LIKE 'size'");
if ($sizeColumnCheck && $sizeColumnCheck->num_rows === 0) {
  $conn->query("ALTER TABLE dogs ADD COLUMN size VARCHAR(50) NULL AFTER age");
}
$genderColumnCheck = $conn->query("SHOW COLUMNS FROM dogs LIKE 'gender'");
if ($genderColumnCheck && $genderColumnCheck->num_rows === 0) {
  $conn->query("ALTER TABLE dogs ADD COLUMN gender VARCHAR(20) NULL AFTER size");
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
  return htmlspecialchars(
    (string)($value ?? ''),
    ENT_QUOTES,
    'UTF-8'
  );
}


/*
|--------------------------------------------------------------------------
| IMAGE DIRECTORY
|--------------------------------------------------------------------------
*/

$imageDirectory = __DIR__ . '/../assets/images/';

$imageExtensions = [
  'jpg',
  'jpeg',
  'png',
  'gif',
  'webp',
  'jfif'
];

$availableImages = [];

if (is_dir($imageDirectory)) {

  $files = scandir($imageDirectory);

  foreach ($files as $file) {

    if ($file === '.' || $file === '..') {
      continue;
    }

    $extension = strtolower(
      pathinfo($file, PATHINFO_EXTENSION)
    );

    if (
      in_array(
        $extension,
        $imageExtensions,
        true
      )
    ) {
      $availableImages[] = $file;
    }
  }
}


/*
|--------------------------------------------------------------------------
| IMAGE URL
|--------------------------------------------------------------------------
*/

function getImageUrl($image)
{
  $image = trim((string)$image);

  if ($image === '') {
    return '../assets/images/default-dog.jpg';
  }

  if (
    strpos($image, 'assets/images/') === 0 ||
    strpos($image, 'dogpic/') === 0
  ) {
    return '../' . $image;
  }

  return '../assets/images/' .
    rawurlencode($image);
}


/*
|--------------------------------------------------------------------------
| DATABASE IMAGE PATH
|--------------------------------------------------------------------------
*/

function getDatabaseImagePath($filename)
{
  $filename = basename((string)$filename);

  return 'assets/images/' . $filename;
}


/*
|--------------------------------------------------------------------------
| UNREAD CHAT COUNT
|--------------------------------------------------------------------------
*/

$unreadChatCount = 0;

$unreadChatResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM chat_messages
    WHERE sender_type = 'user'
      AND is_read = 0
");

if ($unreadChatResult) {

  $unreadRow =
    $unreadChatResult->fetch_assoc();

  $unreadChatCount =
    (int)($unreadRow['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| EMAIL CONFIGURATION
|--------------------------------------------------------------------------
*/

$mailUsername = "happytailsnepal@gmail.com";

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| Put your Gmail App Password here.
| Do NOT use your normal Gmail password.
|--------------------------------------------------------------------------
*/

$mailPassword = "avovnrqcuhnkcvkd";

$mailFromName = "Happy Tails";

$shelterLocation =
  "Happy Tails Shelter, Kathmandu — Mon to Sat, 10am to 5pm";

$shelterPhone =
  "+977-98XXXXXXXX";


/*
|--------------------------------------------------------------------------
| SEND APPLICATION STATUS EMAIL
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
    $shelterLocation,
    $shelterPhone;


  if (
    empty($toEmail) ||
    !filter_var(
      $toEmail,
      FILTER_VALIDATE_EMAIL
    )
  ) {

    error_log(
      "Invalid applicant email: " .
        $toEmail
    );

    return false;
  }


  if (empty($mailPassword)) {

    error_log(
      "Gmail App Password is not configured."
    );

    return false;
  }


  try {

    $mail = new PHPMailer(true);


    /*
        | SMTP
        */

    $mail->isSMTP();

    $mail->Host =
      'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username =
      $mailUsername;

    $mail->Password =
      $mailPassword;

    $mail->SMTPSecure =
      PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;


    /*
        | FROM
        */

    $mail->setFrom(
      $mailUsername,
      $mailFromName
    );


    /*
        | TO
        */

    $mail->addAddress(
      $toEmail,
      $applicantName
    );


    /*
        | HTML
        */

    $mail->isHTML(true);


    /*
        |--------------------------------------------------------------------------
        | ACCEPTED EMAIL
        |--------------------------------------------------------------------------
        */

    if ($status === 'accepted') {

      $mail->Subject =
        "Your Dog Adoption Application Has Been Accepted!";


      $safeApplicantName =
        e($applicantName);

      $safeDogBreed =
        e($dogBreed);

      $safePhone =
        e($shelterPhone);

      $safeLocation =
        e($shelterLocation);


      $mail->Body = "

                <div style='
                    font-family: Arial, sans-serif;
                    max-width: 650px;
                    margin: auto;
                    padding: 25px;
                    border: 1px solid #ddd;
                    border-radius: 12px;
                    background: #ffffff;
                '>

                    <h2 style='
                        color: #5a34ae;
                    '>
                        Happy Tails Shelter
                    </h2>

                    <p>
                        Dear <strong>
                            {$safeApplicantName}
                        </strong>,
                    </p>

                    <p>
                        Great news! 🎉
                    </p>

                    <p>
                        Your application to adopt
                        <strong>
                            {$safeDogBreed}
                        </strong>
                        has been
                        <strong style='color: green;'>
                            accepted
                        </strong>.
                    </p>

                    <p>
                        Please contact us at the number below
                        or visit our shelter for the further
                        adoption process.
                    </p>

                    <div style='
                        background: #f3f0fa;
                        padding: 18px;
                        border-radius: 10px;
                        margin: 20px 0;
                    '>

                        <p>
                            <strong>
                                Contact Number:
                            </strong>
                            {$safePhone}
                        </p>

                        <p>
                            <strong>
                                Shelter Location:
                            </strong>
                            {$safeLocation}
                        </p>

                    </div>

                    <p>
                        Please contact us or visit the shelter
                        during the available hours so that we
                        can proceed with the remaining adoption
                        formalities.
                    </p>

                    <p>
                        Thank you for choosing to adopt and
                        giving a dog a loving home. ❤️
                    </p>

                    <br>

                    <p>
                        Regards,<br>
                        <strong>
                            Happy Tails Shelter
                        </strong>
                    </p>

                </div>
            ";


      $mail->AltBody =
        "Dear {$applicantName},\n\n" .

        "Great news! Your application to adopt " .
        "{$dogBreed} has been accepted.\n\n" .

        "Please contact us at {$shelterPhone} " .
        "or visit our shelter for the further " .
        "adoption process.\n\n" .

        "Contact Number: {$shelterPhone}\n" .

        "Shelter Location: {$shelterLocation}\n\n" .

        "Thank you for choosing to adopt and " .
        "giving a dog a loving home.\n\n" .

        "Regards,\n" .
        "Happy Tails Shelter";
    }


    /*
        |--------------------------------------------------------------------------
        | DECLINED EMAIL
        |--------------------------------------------------------------------------
        */ elseif ($status === 'declined') {

      $mail->Subject =
        "Update on Your Dog Adoption Application";


      $safeApplicantName =
        e($applicantName);

      $safeDogBreed =
        e($dogBreed);

      $safeReason =
        htmlspecialchars(
          $reason,
          ENT_QUOTES,
          'UTF-8'
        );


      $mail->Body = "

                <div style='
                    font-family: Arial, sans-serif;
                    max-width: 650px;
                    margin: auto;
                    padding: 25px;
                    border: 1px solid #ddd;
                    border-radius: 12px;
                    background: #ffffff;
                '>

                    <h2 style='
                        color: #5a34ae;
                    '>
                        Happy Tails Shelter
                    </h2>

                    <p>
                        Dear <strong>
                            {$safeApplicantName}
                        </strong>,
                    </p>

                    <p>
                        Thank you for submitting your adoption
                        application for
                        <strong>
                            {$safeDogBreed}
                        </strong>.
                    </p>

                    <p>
                        After reviewing your application,
                        we regret to inform you that your
                        application has been
                        <strong style='color: #c0392b;'>
                            declined
                        </strong>
                        at this time.
                    </p>

                    <div style='
                        background: #fff3f3;
                        border-left: 5px solid #c0392b;
                        padding: 15px;
                        margin: 20px 0;
                    '>

                        <p>
                            <strong>
                                Reason for Decline:
                            </strong>
                        </p>

                        <p>
                            " . nl2br($safeReason) . "
                        </p>

                    </div>

                    <p>
                        We appreciate your interest in giving
                        a dog a loving home.
                    </p>

                    <p>
                        You may consider applying for another
                        available dog in the future.
                    </p>

                    <br>

                    <p>
                        Regards,<br>
                        <strong>
                            Happy Tails Shelter
                        </strong>
                    </p>

                </div>
            ";


      $mail->AltBody =
        "Dear {$applicantName},\n\n" .

        "Thank you for submitting your adoption " .
        "application for {$dogBreed}.\n\n" .

        "After reviewing your application, we regret " .
        "to inform you that your application has been " .
        "declined at this time.\n\n" .

        "Reason for Decline:\n" .
        "{$reason}\n\n" .

        "We appreciate your interest in giving a dog " .
        "a loving home.\n\n" .

        "Regards,\n" .
        "Happy Tails Shelter";
    }


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


    if ($stmt) {

      $stmt->bind_param(
        "i",
        $applicationId
      );

      $stmt->execute();

      $result =
        $stmt->get_result();

      $app =
        $result->fetch_assoc();

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


        if ($updateStmt) {

          $updateStmt->bind_param(
            "i",
            $applicationId
          );

          $updateStmt->execute();

          $updateStmt->close();
        }


        sendApplicationEmail(

          $app['email'] ?? '',

          $app['owner_name'] ?? '',

          $app['dog_breed'] ?? '',

          'accepted'

        );
      }
    }
  }


  header(
    "Location: admindashboard.php#applications"
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


    if ($stmt) {

      $stmt->bind_param(
        "i",
        $applicationId
      );

      $stmt->execute();

      $result =
        $stmt->get_result();

      $app =
        $result->fetch_assoc();

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


        if ($updateStmt) {

          $updateStmt->bind_param(
            "si",
            $declineReason,
            $applicationId
          );

          $updateStmt->execute();

          $updateStmt->close();
        }


        sendApplicationEmail(

          $app['email'] ?? '',

          $app['owner_name'] ?? '',

          $app['dog_breed'] ?? '',

          'declined',

          $declineReason

        );
      }
    }
  }


  header(
    "Location: admindashboard.php#applications"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| STORY ACTIONS
|--------------------------------------------------------------------------
| All story operations are handled in THIS FILE.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ADD STORY
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['add_story'])
) {

  $title =
    trim(
      $_POST['title'] ?? ''
    );

  $description =
    trim(
      $_POST['description'] ?? ''
    );

  $storyImage =
    trim(
      $_POST['story_image'] ?? ''
    );


  $storyImage =
    basename($storyImage);


  if (
    $title !== '' &&
    $description !== '' &&
    $storyImage !== ''
  ) {

    $stmt = $conn->prepare("
            INSERT INTO stories (
                title,
                story_image,
                description,
                status
            )
            VALUES (?, ?, ?, 'draft')
        ");


    if ($stmt) {

      $stmt->bind_param(
        "sss",
        $title,
        $storyImage,
        $description
      );

      $stmt->execute();

      $stmt->close();
    }
  }


  header(
    "Location: admindashboard.php#stories"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| EDIT STORY
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['edit_story'])
) {

  $storyId =
    (int)(
      $_POST['story_id'] ?? 0
    );

  $title =
    trim(
      $_POST['title'] ?? ''
    );

  $description =
    trim(
      $_POST['description'] ?? ''
    );

  $storyImage =
    trim(
      $_POST['story_image'] ?? ''
    );


  $storyImage =
    basename($storyImage);


  if (
    $storyId > 0 &&
    $title !== '' &&
    $description !== '' &&
    $storyImage !== ''
  ) {

    $stmt = $conn->prepare("
            UPDATE stories
            SET
                title = ?,
                story_image = ?,
                description = ?
            WHERE story_id = ?
        ");


    if ($stmt) {

      $stmt->bind_param(
        "sssi",
        $title,
        $storyImage,
        $description,
        $storyId
      );

      $stmt->execute();

      $stmt->close();
    }
  }


  header(
    "Location: admindashboard.php#stories"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| PUBLISH STORY
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['publish_story'])
) {

  $storyId =
    (int)(
      $_POST['story_id'] ?? 0
    );


  if ($storyId > 0) {

    $stmt = $conn->prepare("
            UPDATE stories
            SET status = 'published'
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


  header(
    "Location: admindashboard.php#stories"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| ARCHIVE STORY
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['archive_story'])
) {

  $storyId =
    (int)(
      $_POST['story_id'] ?? 0
    );


  if ($storyId > 0) {

    $stmt = $conn->prepare("
            UPDATE stories
            SET status = 'archived'
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


  header(
    "Location: admindashboard.php#stories"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| DELETE STORY
|--------------------------------------------------------------------------
*/

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['delete_story'])
) {

  $storyId =
    (int)(
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


  header(
    "Location: admindashboard.php#stories"
  );

  exit;
}


/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalDogs = 0;
$pendingApplications = 0;
$acceptedApplications = 0;
$declinedApplications = 0;


/*
|--------------------------------------------------------------------------
| TOTAL DOGS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM dogs
");

if ($result) {

  $row =
    $result->fetch_assoc();

  $totalDogs =
    (int)($row['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| PENDING
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM adoption_applications
    WHERE status = 'pending'
");

if ($result) {

  $row =
    $result->fetch_assoc();

  $pendingApplications =
    (int)($row['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| ACCEPTED
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM adoption_applications
    WHERE status = 'accepted'
");

if ($result) {

  $row =
    $result->fetch_assoc();

  $acceptedApplications =
    (int)($row['total'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| DECLINED
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM adoption_applications
    WHERE status = 'declined'
");

if ($result) {

  $row =
    $result->fetch_assoc();

  $declinedApplications =
    (int)($row['total'] ?? 0);
}



/*
|--------------------------------------------------------------------------
| ADD DOG
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dog'])) {
  $dogBreed = trim($_POST['dog_breed'] ?? '');
  $age = trim($_POST['age'] ?? '');
  $size = trim($_POST['size'] ?? '');
  $gender = trim($_POST['gender'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $dogImage = basename(trim($_POST['dog_image'] ?? ''));

  $allowedSizes = ['Small', 'Medium', 'Large'];
  $allowedGenders = ['Male', 'Female'];

  if ($dogBreed === '' || $age === '' || $size === '' || $gender === '' || $description === '' || $dogImage === '') {
    die('Please fill all dog fields.');
  }
  if (!in_array($size, $allowedSizes, true)) die('Invalid dog size.');
  if (!in_array($gender, $allowedGenders, true)) die('Invalid dog gender.');

  $imagePath = __DIR__ . '/../assets/images/' . $dogImage;
  if (!file_exists($imagePath)) die('Selected dog image was not found.');

  $stmt = $conn->prepare("INSERT INTO dogs (dog_breed, age, size, gender, dog_image, description) VALUES (?, ?, ?, ?, ?, ?)");
  if (!$stmt) die('Database error: ' . $conn->error);
  $stmt->bind_param('ssssss', $dogBreed, $age, $size, $gender, $dogImage, $description);

  if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    die('Unable to add dog: ' . $error);
  }
  $stmt->close();
  header('Location: admindashboard.php#dogs');
  exit;
}

/*
|--------------------------------------------------------------------------
| EDIT DOG
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dog'])) {
  $dogId = (int)($_POST['dog_id'] ?? 0);
  $dogBreed = trim($_POST['dog_breed'] ?? '');
  $age = trim($_POST['age'] ?? '');
  $size = trim($_POST['size'] ?? '');
  $gender = trim($_POST['gender'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $dogImage = basename(trim($_POST['dog_image'] ?? ''));

  $allowedSizes = ['Small', 'Medium', 'Large'];
  $allowedGenders = ['Male', 'Female'];

  if ($dogId <= 0) die('Invalid dog ID.');
  if ($dogBreed === '' || $age === '' || $size === '' || $gender === '' || $description === '' || $dogImage === '') {
    die('Please fill all dog fields.');
  }
  if (!in_array($size, $allowedSizes, true)) die('Invalid dog size.');
  if (!in_array($gender, $allowedGenders, true)) die('Invalid dog gender.');

  $imagePath = __DIR__ . '/../assets/images/' . $dogImage;
  if (!file_exists($imagePath)) die('Selected dog image was not found.');

  $stmt = $conn->prepare("UPDATE dogs SET dog_breed = ?, age = ?, size = ?, gender = ?, dog_image = ?, description = ? WHERE dog_id = ?");
  if (!$stmt) die('Database error: ' . $conn->error);
  $stmt->bind_param('ssssssi', $dogBreed, $age, $size, $gender, $dogImage, $description, $dogId);

  if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    die('Unable to update dog: ' . $error);
  }
  $stmt->close();
  header('Location: admindashboard.php#dogs');
  exit;
}

/*
|--------------------------------------------------------------------------
| GET DOGS
|--------------------------------------------------------------------------
*/

$dogs = [];

$dogResult = $conn->query("
    SELECT
        dog_id,
        dog_breed,
        age,
        size,
        gender,
        dog_image,
        added_date,
        description
    FROM dogs
    ORDER BY dog_id DESC
");

if ($dogResult) {

  while (
    $row =
    $dogResult->fetch_assoc()
  ) {

    $dogs[] = $row;
  }
}


/*
|--------------------------------------------------------------------------
| GET APPLICATIONS
|--------------------------------------------------------------------------
*/

$applications = [];

$applicationResult = $conn->query("
    SELECT
        aa.id,
        aa.owner_name,
        u.email,
        aa.dog_id,
        aa.dog_breed,
        aa.phone,
        aa.address,
        aa.reason,
        aa.status,
        aa.decline_reason,
        aa.created_at
    FROM adoption_applications aa
    LEFT JOIN users u
        ON u.name = aa.owner_name
    ORDER BY aa.id DESC
");

if ($applicationResult) {

  while (
    $row =
    $applicationResult->fetch_assoc()
  ) {

    $applications[] = $row;
  }
}


/*
|--------------------------------------------------------------------------
| GET STORIES
|--------------------------------------------------------------------------
*/

$stories = [];

$storyResult = $conn->query("
    SELECT
        story_id,
        title,
        story_image,
        description,
        added_date,
        status
    FROM stories
    ORDER BY story_id DESC
");

if ($storyResult) {

  while (
    $row =
    $storyResult->fetch_assoc()
  ) {

    $stories[] = $row;
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
    Happy Tails - Admin Dashboard
  </title>


  <style>
    * {
      box-sizing: border-box;
    }


    body {

      margin: 0;

      font-family:
        Arial,
        Helvetica,
        sans-serif;

      background: #f5f3fa;

      color: #333;
    }


    /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

    .sidebar {

      position: fixed;

      left: 0;
      top: 0;

      width: 250px;

      height: 100vh;

      background: #ffffff;

      border-right: 1px solid #ddd;

      padding: 25px 18px;

      z-index: 1000;
    }


    .logo {

      text-align: center;

      margin-bottom: 30px;
    }


    .logo img {

      width: 150px;

      max-width: 100%;

      height: auto;
    }


    .nav-link {

      display: flex;

      align-items: center;

      justify-content: space-between;

      text-decoration: none;

      color: #444;

      padding: 14px 15px;

      border-radius: 10px;

      margin-bottom: 8px;

      transition: 0.3s;
    }


    .nav-link:hover {

      background: #eee9f7;

      color: #5a34ae;
    }


    .nav-link.active {

      background: #e8e0f2;

      color: #5a34ae;

      font-weight: bold;
    }


    .chat-nav-link {

      position: relative;
    }


    .chat-notification {

      background: #e74c3c;

      color: white;

      min-width: 22px;

      height: 22px;

      border-radius: 50%;

      display: inline-flex;

      align-items: center;

      justify-content: center;

      font-size: 12px;

      font-weight: bold;
    }


    /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

    .main {

      margin-left: 250px;

      padding: 30px;

      min-height: 100vh;
    }


    .topbar {

      display: flex;

      justify-content: space-between;

      align-items: center;

      margin-bottom: 30px;
    }


    .topbar h1 {

      margin: 0;

      color: #5a34ae;
    }


    /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */

    .stats {

      display: grid;

      grid-template-columns:
        repeat(4, 1fr);

      gap: 20px;

      margin-bottom: 35px;
    }


    .stat-card {

      background: white;

      padding: 25px;

      border-radius: 15px;

      box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.06);
    }


    .stat-card h3 {

      margin: 0 0 10px;

      color: #777;

      font-size: 15px;
    }


    .stat-number {

      font-size: 32px;

      font-weight: bold;

      color: #5a34ae;
    }


    /*
        |--------------------------------------------------------------------------
        | SECTION
        |--------------------------------------------------------------------------
        */

    .section {

      background: white;

      border-radius: 15px;

      padding: 25px;

      margin-bottom: 30px;

      box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.06);
    }


    .section-header {

      display: flex;

      justify-content: space-between;

      align-items: center;

      margin-bottom: 20px;
    }


    .section-header h2 {

      margin: 0;

      color: #5a34ae;
    }


    /*
        |--------------------------------------------------------------------------
        | BUTTONS
        |--------------------------------------------------------------------------
        */

    .btn {

      border: none;

      border-radius: 8px;

      padding: 10px 16px;

      cursor: pointer;

      font-size: 14px;

      transition: 0.3s;
    }


    .btn-primary {

      background: #5a34ae;

      color: white;
    }


    .btn-primary:hover {

      background: #48258f;
    }


    .btn-success {

      background: #2e9d55;

      color: white;
    }


    .btn-success:hover {

      background: #247d43;
    }


    .btn-danger {

      background: #d9534f;

      color: white;
    }


    .btn-danger:hover {

      background: #b93d39;
    }


    .btn-secondary {

      background: #777;

      color: white;
    }


    .btn-secondary:hover {

      background: #666;
    }


    /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

    .table-container {

      width: 100%;

      overflow-x: auto;
    }


    table {

      width: 100%;

      border-collapse: collapse;

      min-width: 900px;
    }


    th {

      background: #f1edf8;

      color: #5a34ae;

      text-align: left;

      padding: 13px;

      font-size: 14px;
    }


    td {

      padding: 13px;

      border-bottom: 1px solid #eee;

      vertical-align: top;

      font-size: 14px;
    }


    td img {

      width: 70px;

      height: 70px;

      object-fit: cover;

      border-radius: 10px;
    }


    /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

    .status {

      display: inline-block;

      padding: 6px 10px;

      border-radius: 20px;

      font-size: 12px;

      font-weight: bold;
    }


    .status-pending {

      background: #fff2cc;

      color: #9a7100;
    }


    .status-accepted {

      background: #dff4e5;

      color: #218838;
    }


    .status-declined {

      background: #fbe1e1;

      color: #c0392b;
    }


    .status-draft {

      background: #eeeeee;

      color: #555;
    }


    .status-published {

      background: #dff4e5;

      color: #218838;
    }


    .status-archived {

      background: #e9ecef;

      color: #6c757d;
    }


    /*
        |--------------------------------------------------------------------------
        | ACTION BUTTONS
        |--------------------------------------------------------------------------
        */

    .action-buttons {

      display: flex;

      gap: 7px;

      flex-wrap: wrap;
    }


    .action-buttons form {

      margin: 0;
    }


    /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

    .modal {

      display: none;

      position: fixed;

      z-index: 2000;

      left: 0;

      top: 0;

      width: 100%;

      height: 100%;

      background:
        rgba(0, 0, 0, 0.5);

      align-items: center;

      justify-content: center;

      padding: 20px;
    }


    .modal-content {

      background: white;

      width: 100%;

      max-width: 600px;

      max-height: 90vh;

      overflow-y: auto;

      border-radius: 15px;

      padding: 25px;

      position: relative;
    }


    .modal-content h2 {

      color: #5a34ae;

      margin-top: 0;
    }


    .close {

      position: absolute;

      right: 20px;

      top: 15px;

      font-size: 28px;

      cursor: pointer;

      color: #777;
    }


    /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

    .form-group {

      margin-bottom: 17px;
    }


    .form-group label {

      display: block;

      margin-bottom: 7px;

      font-weight: bold;

      color: #444;
    }


    .form-group input,
    .form-group textarea,
    .form-group select {

      width: 100%;

      padding: 11px;

      border: 1px solid #ccc;

      border-radius: 8px;

      font-family: inherit;
    }


    .form-group textarea {

      resize: vertical;

      min-height: 100px;
    }


    /*
        |--------------------------------------------------------------------------
        | IMAGE PICKER
        |--------------------------------------------------------------------------
        */

    .image-picker {

      display: grid;

      grid-template-columns:
        repeat(5, 1fr);

      gap: 10px;

      margin-top: 10px;
    }


    .image-option {

      border: 2px solid transparent;

      border-radius: 8px;

      cursor: pointer;

      overflow: hidden;
    }


    .image-option img {

      width: 100%;

      height: 80px;

      object-fit: cover;

      display: block;
    }


    .image-option.selected {

      border-color: #5a34ae;
    }


    /*
        |--------------------------------------------------------------------------
        | STORY ACTION AREA
        |--------------------------------------------------------------------------
        */

    .story-action-buttons {

      display: flex;

      gap: 7px;

      flex-wrap: wrap;

      min-width: 260px;
    }


    .story-action-buttons form {

      margin: 0;
    }


    /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

    .mobile-menu {

      display: none;

      border: none;

      background: #5a34ae;

      color: white;

      padding: 10px 14px;

      border-radius: 8px;

      cursor: pointer;
    }


    @media (max-width: 1000px) {

      .stats {

        grid-template-columns:
          repeat(2, 1fr);
      }

    }


    @media (max-width: 768px) {

      .sidebar {

        transform:
          translateX(-100%);

        transition: 0.3s;
      }


      .sidebar.show {

        transform:
          translateX(0);
      }


      .main {

        margin-left: 0;

        padding: 20px;
      }


      .mobile-menu {

        display: block;
      }


      .topbar {

        gap: 15px;
      }


      .stats {

        grid-template-columns: 1fr;
      }

    }
  </style>

</head>


<body>


  <!-- =========================================================
     SIDEBAR
========================================================= -->

  <div
    class="sidebar"
    id="sidebar">


    <div class="logo">

      <img
        src="../assets/images/happy-tails.png"
        alt="Happy Tails">

    </div>


    <a
      href="#dashboard"
      class="nav-link active">

      <span>
        🏠 Dashboard
      </span>

    </a>


    <a
      href="#dogs"
      class="nav-link">

      <span>
        🐶 Dogs
      </span>

    </a>


    <a
      href="#applications"
      class="nav-link">

      <span>
        📋 Applications
      </span>

    </a>


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


    <a
      href="#stories"
      class="nav-link">

      <span>
        📖 Add Stories
      </span>

    </a>


    <a
      href="admin-logout.php"
      class="nav-link">

      <span>
        🚪 Logout
      </span>

    </a>

  </div>


  <!-- =========================================================
     MAIN
========================================================= -->

  <div class="main">


    <div class="topbar">

      <button
        class="mobile-menu"
        onclick="toggleSidebar()">

        ☰ Menu

      </button>


      <h1>
        Happy Tails Admin Dashboard
      </h1>

    </div>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div
      class="stats"
      id="dashboard">


      <div class="stat-card">

        <h3>
          Total Dogs
        </h3>

        <div class="stat-number">

          <?php
          echo $totalDogs;
          ?>

        </div>

      </div>


      <div class="stat-card">

        <h3>
          Pending Applications
        </h3>

        <div class="stat-number">

          <?php
          echo $pendingApplications;
          ?>

        </div>

      </div>


      <div class="stat-card">

        <h3>
          Accepted Applications
        </h3>

        <div class="stat-number">

          <?php
          echo $acceptedApplications;
          ?>

        </div>

      </div>


      <div class="stat-card">

        <h3>
          Declined Applications
        </h3>

        <div class="stat-number">

          <?php
          echo $declinedApplications;
          ?>

        </div>

      </div>

    </div>


    <!-- =====================================================
         DOGS
    ====================================================== -->

    <div
      class="section"
      id="dogs">


      <div class="section-header">

        <h2>
          Available Dogs
        </h2>


        <button
          class="btn btn-primary"
          onclick="openAddDogModal()">

          + Add Dog

        </button>

      </div>


      <div class="table-container">

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
                Size
              </th>

              <th>
                Gender
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
                  colspan="8"
                  style="text-align:center;">

                  No dogs found.

                </td>

              </tr>

            <?php else: ?>

              <?php foreach ($dogs as $dog): ?>

                <tr>


                  <td>

                    <img
                      src="<?php
                            echo e(
                              getImageUrl(
                                $dog['dog_image']
                              )
                            );
                            ?>"
                      alt="Dog">

                  </td>


                  <td>

                    <?php
                    echo e(
                      $dog['dog_breed']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $dog['age']
                    );
                    ?>

                  </td>




                  <td>
                    <?php echo e($dog['size'] ?? 'Not specified'); ?>
                  </td>

                  <td>
                    <?php echo e($dog['gender'] ?? 'Not specified'); ?>
                  </td>

                  <td>

                    <?php
                    echo e(
                      $dog['description']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $dog['added_date']
                    );
                    ?>

                  </td>


                  <td>

                    <div
                      class="action-buttons">


                      <button
                        type="button"
                        class="btn btn-primary"
                        onclick='openEditDogModal(
                                                <?php
                                                echo json_encode(
                                                  $dog,
                                                  JSON_HEX_TAG |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT |
                                                    JSON_HEX_AMP
                                                );
                                                ?>
                                            )'>

                        Edit

                      </button>


                      <form
                        method="POST"
                        action="delete-dog.php"
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
                          class="btn btn-danger">

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

    </div>


    <!-- =====================================================
         APPLICATIONS
    ====================================================== -->

    <div
      class="section"
      id="applications">


      <div class="section-header">

        <h2>
          Adoption Applications
        </h2>

      </div>


      <div class="table-container">

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
                Date
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
                  colspan="9"
                  style="text-align:center;">

                  No applications found.

                </td>

              </tr>

            <?php else: ?>


              <?php foreach ($applications as $application): ?>

                <tr>


                  <td>

                    <?php
                    echo e(
                      $application['owner_name']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $application['email']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $application['dog_breed']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $application['phone']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $application['address']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $application['reason']
                    );
                    ?>

                  </td>


                  <td>

                    <span
                      class="status status-<?php
                                            echo e(
                                              $application['status']
                                            );
                                            ?>">

                      <?php
                      echo ucfirst(
                        e(
                          $application['status']
                        )
                      );
                      ?>

                    </span>


                    <?php if (
                      $application['status'] ===
                      'declined' &&
                      !empty($application['decline_reason'])
                    ): ?>

                      <div
                        style="
                                                margin-top:8px;
                                                color:#c0392b;
                                                font-size:12px;
                                            ">

                        <strong>
                          Reason:
                        </strong>

                        <?php
                        echo e(
                          $application['decline_reason']
                        );
                        ?>

                      </div>

                    <?php endif; ?>

                  </td>


                  <td>

                    <?php
                    echo e(
                      $application['created_at']
                    );
                    ?>

                  </td>


                  <td>

                    <?php
                    if (
                      $application['status'] ===
                      'pending'
                    ):
                    ?>


                      <div
                        class="action-buttons">


                        <form
                          method="POST"
                          action="">

                          <input
                            type="hidden"
                            name="application_id"
                            value="<?php
                                    echo (int)
                                    $application['id'];
                                    ?>">


                          <button
                            type="submit"
                            name="accept_application"
                            class="btn btn-success"
                            onclick="
                                                        return confirm(
                                                            'Accept this adoption application?'
                                                        );
                                                    ">

                            Accept

                          </button>

                        </form>


                        <button
                          type="button"
                          class="btn btn-danger"
                          onclick="
                                                    openDeclineModal(
                                                        <?php
                                                        echo (int)
                                                        $application['id'];
                                                        ?>
                                                    );
                                                ">

                          Decline

                        </button>

                      </div>


                    <?php else: ?>

                      <span
                        style="
                                                color:#777;
                                                font-size:13px;
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

    </div>


    <!-- =====================================================
         STORIES
    ====================================================== -->

    <div
      class="section"
      id="stories">


      <div class="section-header">

        <h2>
          Stories
        </h2>

      </div>


      <!-- =================================================
             ADD STORY
        ================================================== -->

      <form
        method="POST"
        action="">


        <input
          type="hidden"
          name="add_story"
          value="1">


        <!-- STORY TITLE -->

        <div class="form-group">

          <label>
            Story Title
          </label>


          <input
            type="text"
            name="title"
            required
            maxlength="255"
            placeholder="Enter story title...">

        </div>


        <!-- STORY DESCRIPTION -->

        <div class="form-group">

          <label>
            Story Description
          </label>


          <textarea
            name="description"
            required
            placeholder="Write the story here..."></textarea>

        </div>


        <!-- STORY IMAGE -->

        <div class="form-group">

          <label>
            Choose Story Image
          </label>


          <input
            type="hidden"
            name="story_image"
            id="storyImage"
            required>


          <div class="form-group">

            <label>
              Story Image
            </label>

            <input
              type="file"
              name="story_image"
              accept="image/jpeg,image/png,image/gif,image/webp"
              required>

            <small style="color:#777; display:block; margin-top:6px;">
              <!-- JPG, JPEG, PNG, GIF or WEBP -->
            </small>

          </div>

        </div>


        <button
          type="submit"
          class="btn btn-primary">

          + Add Story

        </button>


      </form>


      <hr
        style="
                margin:30px 0;
                border:none;
                border-top:1px solid #eee;
            ">


      <!-- =================================================
             EXISTING STORIES
        ================================================== -->

      <h3
        style="
                color:#5a34ae;
                margin-bottom:20px;
            ">

        Added Stories

      </h3>


      <div class="table-container">

        <table>

          <thead>

            <tr>

              <th>
                Image
              </th>

              <th>
                Title
              </th>

              <th>
                Description
              </th>

              <th>
                Status
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


            <?php if (empty($stories)): ?>

              <tr>

                <td
                  colspan="6"
                  style="text-align:center;">

                  No stories found.

                </td>

              </tr>


            <?php else: ?>


              <?php foreach ($stories as $story): ?>

                <tr>


                  <!-- IMAGE -->

                  <td>

                    <img
                      src="<?php
                            echo e(
                              getImageUrl(
                                $story['story_image']
                              )
                            );
                            ?>"
                      alt="Story">

                  </td>


                  <!-- TITLE -->

                  <td>

                    <strong
                      style="
                                            color:#5a34ae;
                                            display:block;
                                            min-width:180px;
                                        ">

                      <?php
                      echo e(
                        $story['title']
                      );
                      ?>

                    </strong>

                  </td>


                  <!-- DESCRIPTION -->

                  <td
                    style="
                                        max-width:500px;
                                        line-height:1.6;
                                    ">

                    <?php
                    echo nl2br(
                      e(
                        $story['description']
                      )
                    );
                    ?>

                  </td>


                  <!-- STATUS -->

                  <td>

                    <span
                      class="status status-<?php
                                            echo e(
                                              $story['status']
                                            );
                                            ?>">

                      <?php
                      echo ucfirst(
                        e(
                          $story['status']
                        )
                      );
                      ?>

                    </span>

                  </td>


                  <!-- DATE -->

                  <td>

                    <?php
                    echo e(
                      $story['added_date']
                    );
                    ?>

                  </td>


                  <!-- ACTIONS -->

                  <td>

                    <div
                      class="story-action-buttons">


                      <!-- EDIT -->

                      <button
                        type="button"
                        class="btn btn-primary"
                        onclick='openEditStoryModal(
                                                <?php
                                                echo json_encode(
                                                  $story,
                                                  JSON_HEX_TAG |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT |
                                                    JSON_HEX_AMP
                                                );
                                                ?>
                                            )'>

                        Edit

                      </button>


                      <!-- PUBLISH -->

                      <?php
                      if (
                        $story['status'] !==
                        'published'
                      ):
                      ?>

                        <form
                          method="POST"
                          action=""
                          onsubmit="
                                                    return confirm(
                                                        'Publish this story?'
                                                    );
                                                ">

                          <input
                            type="hidden"
                            name="story_id"
                            value="<?php
                                    echo (int)
                                    $story['story_id'];
                                    ?>">


                          <button
                            type="submit"
                            name="publish_story"
                            class="btn btn-success">

                            Publish

                          </button>

                        </form>

                      <?php endif; ?>


                      <!-- ARCHIVE -->

                      <?php
                      if (
                        $story['status'] !==
                        'archived'
                      ):
                      ?>

                        <form
                          method="POST"
                          action=""
                          onsubmit="
                                                    return confirm(
                                                        'Archive this story?'
                                                    );
                                                ">

                          <input
                            type="hidden"
                            name="story_id"
                            value="<?php
                                    echo (int)
                                    $story['story_id'];
                                    ?>">


                          <button
                            type="submit"
                            name="archive_story"
                            class="btn btn-secondary">

                            Archive

                          </button>

                        </form>

                      <?php endif; ?>


                      <!-- DELETE -->

                      <form
                        method="POST"
                        action=""
                        onsubmit="
                                                return confirm(
                                                    'Are you sure you want to permanently delete this story?'
                                                );
                                            ">

                        <input
                          type="hidden"
                          name="story_id"
                          value="<?php
                                  echo (int)
                                  $story['story_id'];
                                  ?>">


                        <button
                          type="submit"
                          name="delete_story"
                          class="btn btn-danger">

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

    </div>


    <!-- =========================================================
         ADD DOG MODAL
    ========================================================= -->

    <div
      class="modal"
      id="addDogModal">


      <div class="modal-content">


        <span
          class="close"
          onclick="closeAddDogModal()">

          &times;

        </span>


        <h2>
          Add New Dog
        </h2>


        <form
          method="POST"
          action="admindashboard.php#dogs">


          <div class="form-group">

            <label>
              Dog Breed
            </label>


            <input
              type="text"
              name="dog_breed"
              required>

          </div>


          <div class="form-group">

            <label>
              Age
            </label>


            <input
              type="text"
              name="age"
              required>

          </div>


          <div class="form-group">

            <label>
              Size
            </label>

            <select
              name="size"
              required>

              <option value="">Select Size</option>
              <option value="Small">Small</option>
              <option value="Medium">Medium</option>
              <option value="Large">Large</option>

            </select>

          </div>


          <div class="form-group">

            <label>
              Gender
            </label>

            <select
              name="gender"
              required>

              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>

            </select>

          </div>


          <div class="form-group">

            <label>
              Description
            </label>


            <textarea
              name="description"
              required></textarea>

          </div>


          <div class="form-group">

            <label>
              Choose Dog Image
            </label>


            <input
              type="hidden"
              name="dog_image"
              id="addDogImage"
              required>


            <div class="image-picker">


              <?php foreach (
                $availableImages
                as $image
              ): ?>


                <div
                  class="image-option"
                  data-image="<?php
                              echo e($image);
                              ?>"
                  onclick="
                                    selectAddImage(
                                        this,
                                        this.dataset.image
                                    )
                                ">


                  <img
                    src="<?php
                          echo e(
                            '../assets/images/' .
                              rawurlencode($image)
                          );
                          ?>"
                    alt="">

                </div>


              <?php endforeach; ?>


            </div>

          </div>


          <button
            type="submit"
            name="add_dog"
            class="btn btn-primary">

            Add Dog

          </button>

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


        <span
          class="close"
          onclick="closeEditDogModal()">

          &times;

        </span>


        <h2>
          Edit Dog
        </h2>


        <form
          method="POST"
          action="admindashboard.php#dogs">


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
              name="dog_breed"
              id="editDogBreed"
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
              required>

          </div>


          <div class="form-group">

            <label>
              Size
            </label>

            <select
              name="size"
              id="editDogSize"
              required>

              <option value="">Select Size</option>
              <option value="Small">Small</option>
              <option value="Medium">Medium</option>
              <option value="Large">Large</option>

            </select>

          </div>


          <div class="form-group">

            <label>
              Gender
            </label>

            <select
              name="gender"
              id="editDogGender"
              required>

              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>

            </select>

          </div>


          <div class="form-group">

            <label>
              Description
            </label>


            <textarea
              name="description"
              id="editDogDescription"
              required></textarea>

          </div>


          <div class="form-group">

            <label>
              Choose Dog Image
            </label>


            <input
              type="hidden"
              name="dog_image"
              id="editDogImage"
              required>


            <div class="image-picker">


              <?php foreach (
                $availableImages
                as $image
              ): ?>


                <div
                  class="image-option edit-image-option"
                  data-image="<?php
                              echo e($image);
                              ?>"
                  onclick="
                                    selectEditImage(
                                        this,
                                        this.dataset.image
                                    )
                                ">


                  <img
                    src="<?php
                          echo e(
                            '../assets/images/' .
                              rawurlencode($image)
                          );
                          ?>"
                    alt="">

                </div>


              <?php endforeach; ?>


            </div>

          </div>


          <button
            type="submit"
            name="edit_dog"
            class="btn btn-primary">

            Update Dog

          </button>

        </form>

      </div>

    </div>


    <!-- =========================================================
         EDIT STORY MODAL
    ========================================================= -->

    <div
      class="modal"
      id="editStoryModal">


      <div class="modal-content">


        <span
          class="close"
          onclick="closeEditStoryModal()">

          &times;

        </span>


        <h2>
          Edit Story
        </h2>


        <form
          method="POST"
          action="">


          <input
            type="hidden"
            name="edit_story"
            value="1">


          <input
            type="hidden"
            name="story_id"
            id="editStoryId">


          <!-- TITLE -->

          <div class="form-group">

            <label>
              Story Title
            </label>


            <input
              type="text"
              name="title"
              id="editStoryTitle"
              maxlength="255"
              required>

          </div>


          <!-- DESCRIPTION -->

          <div class="form-group">

            <label>
              Story Description
            </label>


            <textarea
              name="description"
              id="editStoryDescription"
              required></textarea>

          </div>


          <!-- IMAGE -->

          <div class="form-group">

            <label>
              Choose Story Image
            </label>


            <input
              type="hidden"
              name="story_image"
              id="editStoryImage"
              required>


            <div class="image-picker">


              <?php foreach (
                $availableImages
                as $image
              ): ?>


                <div
                  class="
                                    image-option
                                    edit-story-image-option
                                "
                  data-image="<?php
                              echo e($image);
                              ?>"
                  onclick="
                                    selectEditStoryImage(
                                        this,
                                        this.dataset.image
                                    )
                                ">


                  <img
                    src="<?php
                          echo e(
                            '../assets/images/' .
                              rawurlencode($image)
                          );
                          ?>"
                    alt="Story Image">

                </div>


              <?php endforeach; ?>


            </div>

          </div>


          <button
            type="submit"
            class="btn btn-primary">

            Update Story

          </button>


          <button
            type="button"
            class="btn btn-secondary"
            onclick="closeEditStoryModal()">

            Cancel

          </button>

        </form>

      </div>

    </div>


    <!-- =========================================================
         DECLINE MODAL
    ========================================================= -->

    <div
      class="modal"
      id="declineModal">


      <div class="modal-content">


        <span
          class="close"
          onclick="closeDeclineModal()">

          &times;

        </span>


        <h2>
          Decline Application
        </h2>


        <p>

          Please enter the reason for declining
          this application.

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
              Reason for Decline
            </label>


            <textarea
              name="decline_reason"
              required
              placeholder="Enter the reason..."></textarea>

          </div>


          <button
            type="submit"
            name="decline_application"
            class="btn btn-danger">

            Decline & Send Email

          </button>


          <button
            type="button"
            class="btn btn-secondary"
            onclick="closeDeclineModal()">

            Cancel

          </button>

        </form>

      </div>

    </div>


    <script>
      /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

      function toggleSidebar() {

        document
          .getElementById('sidebar')
          .classList.toggle('show');

      }


      /*
      |--------------------------------------------------------------------------
      | ADD DOG MODAL
      |--------------------------------------------------------------------------
      */

      function openAddDogModal() {

        document
          .getElementById('addDogModal')
          .style.display = 'flex';

      }


      function closeAddDogModal() {

        document
          .getElementById('addDogModal')
          .style.display = 'none';

      }


      /*
      |--------------------------------------------------------------------------
      | SELECT ADD DOG IMAGE
      |--------------------------------------------------------------------------
      */

      function selectAddImage(
        element,
        image
      ) {

        document
          .querySelectorAll(
            '#addDogModal .image-option'
          )
          .forEach(
            function(item) {

              item.classList.remove(
                'selected'
              );

            }
          );


        element.classList.add(
          'selected'
        );


        document
          .getElementById('addDogImage')
          .value = image;

      }


      /*
      |--------------------------------------------------------------------------
      | EDIT DOG MODAL
      |--------------------------------------------------------------------------
      */

      function openEditDogModal(dog) {


        document
          .getElementById('editDogModal')
          .style.display = 'flex';


        document
          .getElementById('editDogId')
          .value =
          dog.dog_id || '';


        document
          .getElementById('editDogBreed')
          .value =
          dog.dog_breed || '';


        document
          .getElementById('editDogAge')
          .value =
          dog.age || '';


        document
          .getElementById('editDogSize')
          .value =
          dog.size || '';


        document
          .getElementById('editDogGender')
          .value =
          dog.gender || '';


        document
          .getElementById('editDogDescription')
          .value =
          dog.description || '';


        let imageName =
          dog.dog_image || '';


        imageName =
          imageName
          .split('/')
          .pop();


        document
          .getElementById('editDogImage')
          .value =
          imageName;


        document
          .querySelectorAll(
            '#editDogModal .edit-image-option'
          )
          .forEach(
            function(item) {

              item.classList.remove(
                'selected'
              );


              if (
                item.dataset.image ===
                imageName
              ) {

                item.classList.add(
                  'selected'
                );

              }

            }
          );

      }


      function closeEditDogModal() {

        document
          .getElementById('editDogModal')
          .style.display = 'none';

      }


      /*
      |--------------------------------------------------------------------------
      | SELECT EDIT DOG IMAGE
      |--------------------------------------------------------------------------
      */

      function selectEditImage(
        element,
        image
      ) {

        document
          .querySelectorAll(
            '#editDogModal .image-option'
          )
          .forEach(
            function(item) {

              item.classList.remove(
                'selected'
              );

            }
          );


        element.classList.add(
          'selected'
        );


        document
          .getElementById('editDogImage')
          .value =
          image;

      }


      /*
      |--------------------------------------------------------------------------
      | EDIT STORY MODAL
      |--------------------------------------------------------------------------
      */

      function openEditStoryModal(story) {


        document
          .getElementById('editStoryModal')
          .style.display = 'flex';


        document
          .getElementById('editStoryId')
          .value =
          story.story_id || '';


        document
          .getElementById('editStoryTitle')
          .value =
          story.title || '';


        document
          .getElementById('editStoryDescription')
          .value =
          story.description || '';


        let imageName =
          story.story_image || '';


        imageName =
          imageName
          .split('/')
          .pop();


        document
          .getElementById('editStoryImage')
          .value =
          imageName;


        document
          .querySelectorAll(
            '#editStoryModal .edit-story-image-option'
          )
          .forEach(
            function(item) {

              item.classList.remove(
                'selected'
              );


              if (
                item.dataset.image ===
                imageName
              ) {

                item.classList.add(
                  'selected'
                );

              }

            }
          );

      }


      function closeEditStoryModal() {

        document
          .getElementById('editStoryModal')
          .style.display = 'none';

      }


      /*
      |--------------------------------------------------------------------------
      | SELECT EDIT STORY IMAGE
      |--------------------------------------------------------------------------
      */

      function selectEditStoryImage(
        element,
        image
      ) {

        document
          .querySelectorAll(
            '#editStoryModal .edit-story-image-option'
          )
          .forEach(
            function(item) {

              item.classList.remove(
                'selected'
              );

            }
          );


        element.classList.add(
          'selected'
        );


        document
          .getElementById('editStoryImage')
          .value =
          image;

      }


      /*
      |--------------------------------------------------------------------------
      | DECLINE MODAL
      |--------------------------------------------------------------------------
      */

      function openDeclineModal(
        applicationId
      ) {

        document
          .getElementById(
            'declineApplicationId'
          )
          .value =
          applicationId;


        document
          .getElementById(
            'declineModal'
          )
          .style.display =
          'flex';

      }


      function closeDeclineModal() {

        document
          .getElementById(
            'declineModal'
          )
          .style.display =
          'none';

      }


      /*
      |--------------------------------------------------------------------------
      | STORY IMAGE
      |--------------------------------------------------------------------------
      */

      function selectStoryImage(
        element,
        image
      ) {

        document
          .querySelectorAll(
            '#stories .story-image-option'
          )
          .forEach(
            function(item) {

              item.classList.remove(
                'selected'
              );

            }
          );


        element.classList.add(
          'selected'
        );


        document
          .getElementById(
            'storyImage'
          )
          .value =
          image;

      }


      /*
      |--------------------------------------------------------------------------
      | CLOSE MODALS OUTSIDE CLICK
      |--------------------------------------------------------------------------
      */

      window.onclick =
        function(event) {


          const addModal =
            document.getElementById(
              'addDogModal'
            );


          const editModal =
            document.getElementById(
              'editDogModal'
            );


          const editStoryModal =
            document.getElementById(
              'editStoryModal'
            );


          const declineModal =
            document.getElementById(
              'declineModal'
            );


          if (
            event.target ===
            addModal
          ) {

            closeAddDogModal();

          }


          if (
            event.target ===
            editModal
          ) {

            closeEditDogModal();

          }


          if (
            event.target ===
            editStoryModal
          ) {

            closeEditStoryModal();

          }


          if (
            event.target ===
            declineModal
          ) {

            closeDeclineModal();

          }

        };


      /*
      |--------------------------------------------------------------------------
      | ACTIVE NAVIGATION
      |--------------------------------------------------------------------------
      */

      document
        .querySelectorAll(
          '.nav-link'
        )
        .forEach(
          function(link) {


            link.addEventListener(
              'click',
              function() {


                document
                  .querySelectorAll(
                    '.nav-link'
                  )
                  .forEach(
                    function(item) {

                      item.classList.remove(
                        'active'
                      );

                    }
                  );


                if (
                  this
                  .getAttribute(
                    'href'
                  )
                  .startsWith('#')
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
      | CHAT NOTIFICATION POLLING
      |--------------------------------------------------------------------------
      */

      function checkChatNotifications() {


        fetch(
            'chat_notification.php', {
              cache: 'no-store'
            }
          )


          .then(
            function(response) {

              return response.json();

            }
          )


          .then(
            function(data) {


              const chatLink =
                document.querySelector(
                  '.chat-nav-link'
                );


              if (!chatLink) {

                return;

              }


              let badge =
                chatLink.querySelector(
                  '.chat-notification'
                );


              const count =
                parseInt(
                  data.count || 0
                );


              if (count > 0) {


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
                  count;

              } else {


                if (badge) {

                  badge.remove();

                }

              }

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
      | CHECK CHAT EVERY 5 SECONDS
      |--------------------------------------------------------------------------
      */

      setInterval(
        checkChatNotifications,
        5000
      );
    </script>


</body>

</html>