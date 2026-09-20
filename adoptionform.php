<?php require __DIR__ . '/includes/header.php'; ?>

<?php
session_start();

require_once __DIR__ . '/admin/dataconnection.php';

$usernameVerified = false;
$userData = null;
$message = "";
$messageType = "";

/* -----------------------------
   CHECK USERNAME
------------------------------ */
if (isset($_POST['check_username'])) {

    $username = trim($_POST['username']);

    if ($username === "") {

        $message = "Please enter your username.";
        $messageType = "error";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email 
             FROM user 
             WHERE name = ? 
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $userData = $result->fetch_assoc();

            $usernameVerified = true;

            $_SESSION['adoption_user_id'] = $userData['id'];

            $message = "Username verified successfully.";
            $messageType = "success";
        } else {

            $message = "Username does not exist. Please create an account first.";
            $messageType = "error";
        }

        $stmt->close();
    }
}


/* -----------------------------
   SUBMIT ADOPTION APPLICATION
------------------------------ */
if (isset($_POST['submit_application'])) {

    if (!isset($_SESSION['adoption_user_id'])) {

        $message = "Please verify your username first.";
        $messageType = "error";
    } else {

        $user_id = $_SESSION['adoption_user_id'];

        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $dog_id = intval($_POST['dog_id']);
        $reason = trim($_POST['reason']);

        /* Get user information */
        $stmt = $conn->prepare(
            "SELECT id, name, email 
             FROM user 
             WHERE id = ? 
             LIMIT 1"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $userResult = $stmt->get_result();

        if ($userResult->num_rows !== 1) {

            $message = "User account could not be found.";
            $messageType = "error";
        } else {

            $user = $userResult->fetch_assoc();

            $fullname = $user['name'];
            $email = $user['email'];

            /* Check whether dog exists and is available */
            $dogStmt = $conn->prepare(
                "SELECT dog_id 
                 FROM dogs 
                 WHERE dog_id = ? 
                 AND status = 'Available'
                 LIMIT 1"
            );

            $dogStmt->bind_param("i", $dog_id);
            $dogStmt->execute();

            $dogResult = $dogStmt->get_result();

            if ($dogResult->num_rows !== 1) {

                $message = "This dog is no longer available.";
                $messageType = "error";
            } elseif ($phone === "" || $address === "" || $reason === "") {

                $message = "Please fill in all required fields.";
                $messageType = "error";
            } else {

                /* Check if user already has a pending application for this dog */
                $checkStmt = $conn->prepare(
                    "SELECT id 
                     FROM adoption
                     WHERE user_id = ?
                     AND dog_id = ?
                     AND status = 'Pending'
                     LIMIT 1"
                );

                $checkStmt->bind_param("ii", $user_id, $dog_id);
                $checkStmt->execute();

                $existingResult = $checkStmt->get_result();

                if ($existingResult->num_rows > 0) {

                    $message = "You already have a pending application for this dog.";
                    $messageType = "error";
                } else {

                    /* Insert application */
                    $insertStmt = $conn->prepare(
                        "INSERT INTO adoption
                        (user_id, dog_id, fullname, email, phone, address, reason, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
                    );

                    $insertStmt->bind_param(
                        "iisssss",
                        $user_id,
                        $dog_id,
                        $fullname,
                        $email,
                        $phone,
                        $address,
                        $reason
                    );

                    if ($insertStmt->execute()) {

                        $message = "Your adoption application has been submitted successfully.";
                        $messageType = "success";

                        /* Remove verification after successful submission */
                        unset($_SESSION['adoption_user_id']);
                    } else {

                        $message = "Something went wrong. Please try again.";
                        $messageType = "error";
                    }

                    $insertStmt->close();
                }

                $checkStmt->close();
            }

            $dogStmt->close();
        }

        $stmt->close();
    }
}


/* -----------------------------
   GET AVAILABLE DOGS
------------------------------ */
$dogs = [];

$dogQuery = $conn->query(
    "SELECT dog_id, dog_breed, age, dog_image
     FROM dogs
     WHERE status = 'Available'
     ORDER BY added_date DESC"
);

if ($dogQuery) {

    while ($row = $dogQuery->fetch_assoc()) {
        $dogs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Adopt a Dog - Happy Tails</title>

    <link rel="stylesheet" href="assets/style.css">


    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>
<style>
    /* Main container */

    .adoption-container {
        width: 90%;
        max-width: 1000px;
        margin: 50px auto;
    }


    /* Header */

    .adoption-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .paw-icon {
        width: 55px;
        height: 55px;

        margin: 0 auto 12px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #b5baff;
        color: #4c3b8f;

        border-radius: 50%;

        font-size: 25px;
    }

    .adoption-header h1 {
        color: #3d356b;
        font-size: 32px;
        margin-bottom: 8px;
    }

    .adoption-header p {
        color: #666;
        font-size: 16px;
    }


    /* Messages */

    .message {
        padding: 14px 18px;

        border-radius: 10px;

        margin-bottom: 20px;

        font-weight: 600;
    }

    .message.success {
        background: #e6f7ed;
        color: #237a45;
        border: 1px solid #b8e5ca;
    }

    .message.error {
        background: #fff0f0;
        color: #b33434;
        border: 1px solid #f0bcbc;
    }


    /* Username verification */

    .username-section,
    .adoption-form {
        background: white;

        padding: 35px;

        border-radius: 20px;

        box-shadow: 0 8px 25px rgba(60, 50, 100, 0.10);

        border: 1px solid #e3e0ef;
    }

    .username-section {
        max-width: 600px;
        margin: 0 auto;
    }

    .username-section h2,
    .adoption-form h2 {
        color: #4b3b83;

        margin-bottom: 10px;

        font-size: 23px;
    }

    .username-section h2 i,
    .adoption-form h2 i {
        color: #5a34ae;
        margin-right: 8px;
    }

    .username-section p {
        color: #777;
        margin-bottom: 25px;
        line-height: 1.6;
    }


    /* Form */

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;

        margin-bottom: 8px;

        color: #3c3659;

        font-weight: 600;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;

        padding: 13px 15px;

        border: 1px solid #d8d5e5;

        border-radius: 10px;

        outline: none;

        font-size: 15px;

        font-family: Arial, sans-serif;

        background: #faf9fd;

        transition: 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #8c7bd9;

        box-shadow: 0 0 0 3px rgba(181, 186, 255, 0.35);

        background: white;
    }

    .form-group input[readonly] {
        background: #eeeef5;
        color: #555;
        cursor: not-allowed;
    }

    .form-group textarea {
        resize: vertical;
    }


    /* Two columns */

    .form-row {
        display: grid;

        grid-template-columns: 1fr 1fr;

        gap: 20px;
    }


    /* Verify button */

    .verify-btn,
    .submit-btn {
        border: none;

        background: #b5baff;

        color: #27213e;

        font-weight: 700;

        font-size: 15px;

        padding: 13px 22px;

        border-radius: 10px;

        cursor: pointer;

        transition: 0.3s;
    }

    .verify-btn:hover,
    .submit-btn:hover {
        background: #9da4f5;

        transform: translateY(-1px);
    }

    .verify-btn i,
    .submit-btn i {
        margin-right: 7px;
    }


    /* Verified user */

    .verified-user {
        display: flex;

        align-items: center;

        gap: 15px;

        padding: 18px 20px;

        background: #eeedff;

        border: 1px solid #d5d0f3;

        border-radius: 15px;

        margin-bottom: 20px;
    }

    .verified-icon {
        font-size: 28px;

        color: #5a34ae;
    }

    .verified-user strong {
        color: #40346d;

        font-size: 17px;
    }

    .verified-user p {
        color: #666;

        margin-top: 4px;
    }


    /* Adoption form */

    .adoption-form h2 {
        margin-bottom: 28px;

        padding-bottom: 15px;

        border-bottom: 1px solid #e5e2ed;
    }

    .submit-btn {
        width: 100%;

        padding: 15px;

        margin-top: 5px;

        background: #b5baff;

        font-size: 16px;
    }


    /* Mobile */

    @media (max-width: 700px) {

        .adoption-container {
            width: 94%;

            margin: 30px auto;
        }

        .adoption-header h1 {
            font-size: 25px;
        }

        .username-section,
        .adoption-form {
            padding: 22px;
        }

        .form-row {
            grid-template-columns: 1fr;

            gap: 0;
        }
    }
</style>

<body>

    <div class="adoption-container">

        <div class="adoption-header">

            <div class="paw-icon">
                <i class="fa-solid fa-paw"></i>
            </div>

            <h1>Give a Dog a Forever Home</h1>

            <p>
                Start your adoption journey with Happy Tails.
            </p>

        </div>


        <?php if ($message !== ""): ?>

            <div class="message <?php echo $messageType; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- USERNAME VERIFICATION -->

        <?php if (!$usernameVerified && !isset($_SESSION['adoption_user_id'])): ?>

            <div class="username-section">

                <h2>
                    <i class="fa-solid fa-user-check"></i>
                    Verify Your Username
                </h2>

                <p>
                    Please enter your registered username before
                    filling out the adoption application.
                </p>

                <form method="POST">

                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required>

                    </div>

                    <button
                        type="submit"
                        name="check_username"
                        class="verify-btn">

                        <i class="fa-solid fa-check"></i>
                        Check Username

                    </button>

                </form>

            </div>

        <?php else: ?>


            <?php

            /* Get user if session exists */
            if (!$userData && isset($_SESSION['adoption_user_id'])) {

                $sessionUserId = $_SESSION['adoption_user_id'];

                $stmt = $conn->prepare(
                    "SELECT id, name, email
                 FROM user
                 WHERE id = ?
                 LIMIT 1"
                );

                $stmt->bind_param("i", $sessionUserId);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $userData = $result->fetch_assoc();
                }

                $stmt->close();
            }

            ?>


            <!-- USER INFORMATION -->

            <?php if ($userData): ?>

                <div class="verified-user">

                    <div class="verified-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>

                        <strong>
                            Username Verified
                        </strong>

                        <p>
                            Welcome,
                            <?php echo htmlspecialchars($userData['name']); ?>
                        </p>

                    </div>

                </div>


                <!-- ADOPTION FORM -->

                <form method="POST"
                    class="adoption-form">

                    <h2>
                        <i class="fa-solid fa-heart"></i>
                        Adoption Application
                    </h2>


                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Full Name
                            </label>

                            <input
                                type="text"
                                value="<?php echo htmlspecialchars($userData['name']); ?>"
                                readonly>

                        </div>


                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                value="<?php echo htmlspecialchars($userData['email']); ?>"
                                readonly>

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="Enter your phone number"
                                required>

                        </div>


                        <div class="form-group">

                            <label for="dog_id">
                                Select Dog
                            </label>

                            <select
                                name="dog_id"
                                id="dog_id"
                                required>

                                <option value="">
                                    -- Select a Dog --
                                </option>

                                <?php foreach ($dogs as $dog): ?>

                                    <option value="<?php echo $dog['dog_id']; ?>">

                                        <?php
                                        echo htmlspecialchars($dog['dog_breed']);
                                        ?>

                                        -
                                        <?php
                                        echo htmlspecialchars($dog['age']);
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            placeholder="Enter your full address"
                            required></textarea>

                    </div>


                    <div class="form-group">

                        <label for="reason">
                            Why do you want to adopt this dog?
                        </label>

                        <textarea
                            id="reason"
                            name="reason"
                            rows="5"
                            placeholder="Tell us why you would like to adopt this dog..."
                            required></textarea>

                    </div>


                    <button
                        type="submit"
                        name="submit_application"
                        class="submit-btn">

                        <i class="fa-solid fa-paw"></i>
                        Submit Adoption Application

                    </button>

                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</body>

</html>