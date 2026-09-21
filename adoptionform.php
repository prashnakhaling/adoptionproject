<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';

/*
|--------------------------------------------------------------------------
| PHPMailer - Manual Installation
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
| IMPORTANT:
| Use the Gmail account that will SEND the emails.
| Do NOT put the applicant's email here.
|--------------------------------------------------------------------------
*/

$mailUsername = "YOUR_GMAIL@gmail.com";
$mailPassword = "YOUR_16_CHARACTER_APP_PASSWORD";
$mailFromName = "Happy Tails";


/*
|--------------------------------------------------------------------------
| SEND APPLICATION SUBMITTED EMAIL
|--------------------------------------------------------------------------
*/
function sendApplicationSubmittedEmail(
    $toEmail,
    $applicantName,
    $dogBreed
) {
    global $mailUsername, $mailPassword, $mailFromName;

    if (
        empty($toEmail) ||
        !filter_var($toEmail, FILTER_VALIDATE_EMAIL)
    ) {
        return false;
    }

    try {

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUsername;
        $mail->Password   = $mailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        /*
         * Sender = your Happy Tails Gmail account
         * Recipient = automatically fetched from users.email
         */
        $mail->setFrom($mailUsername, $mailFromName);
        $mail->addAddress($toEmail, $applicantName);

        $mail->isHTML(true);

        $mail->Subject = "Dog Adoption Application Submitted";

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;line-height:1.6;'>
                <h2>Application Submitted</h2>

                <p>Hi <strong>" . htmlspecialchars($applicantName) . "</strong>,</p>

                <p>
                    Your application to adopt
                    <strong>" . htmlspecialchars($dogBreed) . "</strong>
                    has been submitted successfully.
                </p>

                <p>
                    We will review your application and update you
                    about the application status by email.
                </p>

                <p>Thank you for choosing Happy Tails.</p>

                <p>
                    Regards,<br>
                    <strong>Happy Tails</strong>
                </p>
            </div>
        ";

        $mail->AltBody =
            "Hi {$applicantName},\n\n" .
            "Your application to adopt {$dogBreed} has been submitted successfully.\n\n" .
            "We will review your application and update you about the application status by email.\n\n" .
            "Thank you for choosing Happy Tails.\n\n" .
            "Regards,\nHappy Tails";

        $mail->send();

        return true;
    } catch (Exception $e) {

        error_log("PHPMailer Submission Email Error: " . $e->getMessage());

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD DOGS
|--------------------------------------------------------------------------
*/

$self = basename(__FILE__);

$dogs = [];

$dogsResult = $conn->query(
    "SELECT dog_id, dog_breed, dog_image, age, description
     FROM dogs
     ORDER BY dog_breed ASC"
);

if ($dogsResult) {

    while ($row = $dogsResult->fetch_assoc()) {
        $dogs[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| DOG LOOKUP BY ID
|--------------------------------------------------------------------------
*/

$dogsById = [];

foreach ($dogs as $d) {

    $dogsById[(string)$d['dog_id']] = $d;
}


/*
|--------------------------------------------------------------------------
| STEP 1: VERIFY USERNAME
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['check_user'])
) {

    $username = trim($_POST['username'] ?? '');

    $errors = [];

    if ($username === '') {

        $errors['username'] = "Username is required.";
    } else {

        /*
         * users table:
         * name = username
         * email = user's email
         */

        $stmt = $conn->prepare(
            "SELECT name, email
             FROM users
             WHERE name = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $user = $result->fetch_assoc();

            /*
             * Store username and email in session.
             * Email comes automatically from users table.
             */

            $_SESSION['adoption_user'] = $user['name'];
            $_SESSION['adoption_email'] = $user['email'];
        } else {

            $errors['username'] = "This user doesn't exist.";
        }

        $stmt->close();
    }

    $_SESSION['flash'] = [
        'errors' => $errors,
        'form_data' => [
            'username' => $username
        ]
    ];

    header("Location: $self");
    exit();
}


/*
|--------------------------------------------------------------------------
| STEP 2: SUBMIT ADOPTION APPLICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['submit_adoption'])
) {

    $errors = [];

    /*
     * Username must already be verified.
     */

    if (empty($_SESSION['adoption_user'])) {

        $errors['general'] =
            "Please verify your username before submitting the form.";

        $_SESSION['flash'] = [
            'errors' => $errors,
            'form_data' => []
        ];

        header("Location: $self");
        exit();
    }


    /*
     * Get verified user information.
     */

    $ownerName = $_SESSION['adoption_user'];

    /*
     * Get email directly from users table again.
     * This makes sure the current email is used.
     */

    $emailStmt = $conn->prepare(
        "SELECT email
         FROM users
         WHERE name = ?
         LIMIT 1"
    );

    $emailStmt->bind_param("s", $ownerName);

    $emailStmt->execute();

    $emailResult = $emailStmt->get_result();

    $userRow = $emailResult->fetch_assoc();

    $emailStmt->close();

    $applicantEmail = $userRow['email'] ?? '';


    /*
     * Form values
     */

    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $reason  = trim($_POST['reason'] ?? '');
    $dogId   = trim($_POST['dog_id'] ?? '');


    /*
     * Validation
     */

    if (
        $dogId === '' ||
        !isset($dogsById[$dogId])
    ) {

        $errors['dog_id'] =
            "Please select a dog from the list.";
    }

    if ($phone === '') {

        $errors['phone'] =
            "Phone number is required.";
    }

    if ($address === '') {

        $errors['address'] =
            "Address is required.";
    }


    /*
     * Submit application
     */

    if (empty($errors)) {

        $dogBreed = $dogsById[$dogId]['dog_breed'];

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

            /*
             * Database submission succeeded.
             */

            /*
             * Send email to the email belonging
             * to the verified username.
             */

            if (
                !empty($applicantEmail) &&
                filter_var(
                    $applicantEmail,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                sendApplicationSubmittedEmail(
                    $applicantEmail,
                    $ownerName,
                    $dogBreed
                );
            }


            /*
             * Clear verified user after submission.
             */

            unset($_SESSION['adoption_user']);
            unset($_SESSION['adoption_email']);


            /*
             * Show success message.
             */

            $_SESSION['flash'] = [
                'success' => true,
                'name' => $ownerName
            ];
        } else {

            $errors['general'] =
                "Unable to submit application. Please try again.";
        }

        $stmt->close();
    }


    /*
     * Validation errors
     */

    if (!empty($errors)) {

        $_SESSION['flash'] = [
            'errors' => $errors,
            'form_data' => $_POST
        ];
    }

    header("Location: $self");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
*/

$flash = $_SESSION['flash'] ?? [];

unset($_SESSION['flash']);

$errors = $flash['errors'] ?? [];

$formData = $flash['form_data'] ?? [];

$adoptionSuccess = !empty($flash['success']);

$successName = $flash['name'] ?? '';

$userVerified =
    !empty($_SESSION['adoption_user']);

$verifiedName =
    $_SESSION['adoption_user'] ?? '';


$selectedDogId =
    $formData['dog_id'] ?? '';

$selectedDog =
    $dogsById[$selectedDogId] ?? null;

?>

<?php require __DIR__ . '/includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <link
        rel="stylesheet"
        href="assets/style.css">

    <title>Dog Adoption</title>


    <style>
        .adopt-page {
            box-sizing: border-box;
            min-height: calc(100vh - 90px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: #ffffff;
        }

        .adopt-panel {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 36px 32px;
            box-sizing: border-box;
            text-align: center;
        }

        .adopt-title {
            margin: 0 0 8px;
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .adopt-subtext {
            display: block;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #6b6b6b;
        }

        .adopt-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .adopt-input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #f5f5f5;
            font-size: 0.95rem;
            box-sizing: border-box;
            font-family: inherit;
        }

        .adopt-input:focus {
            outline: none;
            border-color: #5b7fdb;
            background: #ffffff;
        }

        textarea.adopt-input {
            min-height: 90px;
            resize: vertical;
            font-family: inherit;
        }

        .adopt-error {
            display: block;
            text-align: left;
            color: #d64545;
            font-size: 0.82rem;
            margin-top: -6px;
        }

        .adopt-dog-search-wrap {
            position: relative;
            text-align: left;
        }

        .adopt-suggestions {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            max-height: 220px;
            overflow-y: auto;
            z-index: 20;
            text-align: left;
        }

        .adopt-suggestion-item {
            padding: 10px 14px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .adopt-suggestion-item:hover {
            background: #f0f3fc;
        }

        .adopt-dog-card {
            display: none;
            align-items: center;
            gap: 12px;
            margin-top: 4px;
            padding: 10px;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            background: #fafbff;
            text-align: left;
        }

        .adopt-dog-card img {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }

        .adopt-dog-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1a1a1a;
        }

        .adopt-dog-meta {
            font-size: 0.8rem;
            color: #666;
        }

        .adopt-button {
            margin-top: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 24px;
            background: #5b7fdb;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.03em;
            cursor: pointer;
            width: 100%;
        }

        .adopt-button:hover {
            background: #4a6bc4;
        }

        .adopt-button-secondary {
            margin-top: 4px;
            padding: 12px 20px;
            border: none;
            border-radius: 24px;
            background: #eef1fb;
            color: #33448e;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            width: 100%;
        }

        .adopt-button-secondary:hover {
            background: #dde3f8;
        }

        .adopt-text {
            margin: 12px 0 4px;
            font-size: 0.88rem;
            color: #4a4a4a;
        }
    </style>

</head>


<body>

    <section class="adopt-page">


        <?php if ($adoptionSuccess): ?>


            <div class="adopt-panel">

                <h1 class="adopt-title">
                    Application Submitted!
                </h1>

                <p class="adopt-text">

                    Thank you,
                    <?php echo htmlspecialchars($successName); ?>.

                    Your application has been submitted.
                    We'll keep you updated about its status via email.

                </p>

            </div>


        <?php elseif ($userVerified): ?>


            <div class="adopt-panel">

                <form
                    class="adopt-form"
                    action=""
                    method="POST">

                    <h1 class="adopt-title">
                        Dog Adoption Application
                    </h1>

                    <span class="adopt-subtext">

                        Welcome,
                        <?php echo htmlspecialchars($verifiedName); ?>!

                    </span>


                    <?php if (!empty($errors['general'])): ?>

                        <span class="adopt-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['general']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- DOG SEARCH -->

                    <div class="adopt-dog-search-wrap">

                        <input
                            type="text"
                            id="dogSearch"
                            class="adopt-input"
                            placeholder="Search dog by breed or description..."
                            autocomplete="off"
                            value="<?php
                                    echo $selectedDog
                                        ? htmlspecialchars(
                                            $selectedDog['dog_breed']
                                        )
                                        : '';
                                    ?>">

                        <div
                            id="dogSuggestions"
                            class="adopt-suggestions"></div>

                    </div>


                    <input
                        type="hidden"
                        name="dog_id"
                        id="dogId"
                        value="<?php
                                echo htmlspecialchars(
                                    $selectedDogId
                                );
                                ?>">


                    <?php if (!empty($errors['dog_id'])): ?>

                        <span class="adopt-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['dog_id']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- SELECTED DOG -->

                    <div
                        id="selectedDogCard"
                        class="adopt-dog-card"
                        style="<?php
                                echo $selectedDog
                                    ? 'display:flex;'
                                    : '';
                                ?>">

                        <img
                            id="selectedDogImg"
                            src="<?php
                                    echo $selectedDog
                                        ? htmlspecialchars(
                                            $selectedDog['dog_image']
                                        )
                                        : '';
                                    ?>"
                            alt="">

                        <div>

                            <div
                                id="selectedDogName"
                                class="adopt-dog-name">

                                <?php
                                echo $selectedDog
                                    ? htmlspecialchars(
                                        $selectedDog['dog_breed']
                                    )
                                    : '';
                                ?>

                            </div>


                            <div
                                id="selectedDogMeta"
                                class="adopt-dog-meta">

                                <?php
                                echo $selectedDog
                                    ? htmlspecialchars(
                                        $selectedDog['age'] .
                                            ' yrs — ' .
                                            $selectedDog['description']
                                    )
                                    : '';
                                ?>

                            </div>

                        </div>

                    </div>


                    <!-- PHONE -->

                    <input
                        type="text"
                        name="phone"
                        class="adopt-input"
                        placeholder="Phone Number"
                        value="<?php
                                echo htmlspecialchars(
                                    $formData['phone'] ?? ''
                                );
                                ?>">


                    <?php if (!empty($errors['phone'])): ?>

                        <span class="adopt-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['phone']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- ADDRESS -->

                    <input
                        type="text"
                        name="address"
                        class="adopt-input"
                        placeholder="Address"
                        value="<?php
                                echo htmlspecialchars(
                                    $formData['address'] ?? ''
                                );
                                ?>">


                    <?php if (!empty($errors['address'])): ?>

                        <span class="adopt-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['address']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <!-- REASON -->

                    <textarea
                        name="reason"
                        class="adopt-input"
                        placeholder="Why do you want to adopt this dog?"><?php
                                                                            echo htmlspecialchars(
                                                                                $formData['reason'] ?? ''
                                                                            );
                                                                            ?></textarea>


                    <button
                        type="submit"
                        name="submit_adoption"
                        class="adopt-button">
                        Submit Application
                    </button>

                </form>

            </div>


            <script>
                const dogsData =
                    <?php
                    echo json_encode(
                        array_values($dogs)
                    );
                    ?>;


                const searchInput =
                    document.getElementById('dogSearch');

                const suggestionsBox =
                    document.getElementById('dogSuggestions');

                const dogIdInput =
                    document.getElementById('dogId');

                const card =
                    document.getElementById('selectedDogCard');

                const cardImg =
                    document.getElementById('selectedDogImg');

                const cardName =
                    document.getElementById('selectedDogName');

                const cardMeta =
                    document.getElementById('selectedDogMeta');


                function renderSuggestions(term) {

                    suggestionsBox.innerHTML = '';

                    if (!term) {

                        suggestionsBox.style.display = 'none';

                        return;
                    }


                    const lower =
                        term.toLowerCase();


                    const matches =
                        dogsData
                        .filter(d =>
                            (d.dog_breed || '')
                            .toLowerCase()
                            .includes(lower) ||
                            (d.description || '')
                            .toLowerCase()
                            .includes(lower)
                        )
                        .slice(0, 8);


                    if (matches.length === 0) {

                        suggestionsBox.style.display = 'none';

                        return;
                    }


                    matches.forEach(d => {

                        const item =
                            document.createElement('div');

                        item.className =
                            'adopt-suggestion-item';

                        item.textContent =
                            d.dog_breed +
                            ' (age ' +
                            d.age +
                            ')';


                        item.addEventListener(
                            'click',
                            () => selectDog(d)
                        );


                        suggestionsBox.appendChild(item);

                    });


                    suggestionsBox.style.display =
                        'block';
                }


                function selectDog(d) {

                    dogIdInput.value =
                        d.dog_id;

                    searchInput.value =
                        d.dog_breed;

                    suggestionsBox.style.display =
                        'none';

                    suggestionsBox.innerHTML = '';

                    cardImg.src =
                        d.dog_image || '';

                    cardName.textContent =
                        d.dog_breed;

                    cardMeta.textContent =
                        d.age +
                        ' yrs — ' +
                        (d.description || '');

                    card.style.display =
                        'flex';
                }


                searchInput.addEventListener(
                    'input',
                    function() {

                        dogIdInput.value = '';

                        card.style.display =
                            'none';

                        renderSuggestions(
                            this.value.trim()
                        );

                    }
                );


                searchInput.addEventListener(
                    'focus',
                    function() {

                        if (this.value.trim()) {

                            renderSuggestions(
                                this.value.trim()
                            );

                        }

                    }
                );


                document.addEventListener(
                    'click',
                    function(e) {

                        if (
                            !e.target.closest(
                                '.adopt-dog-search-wrap'
                            )
                        ) {

                            suggestionsBox.style.display =
                                'none';
                        }

                    }
                );
            </script>


        <?php else: ?>


            <div class="adopt-panel">

                <form
                    class="adopt-form"
                    action=""
                    method="POST">

                    <h1 class="adopt-title">
                        Verify Your Account
                    </h1>

                    <span class="adopt-subtext">
                        Enter your registered username to continue to the adoption form
                    </span>


                    <input
                        type="text"
                        name="username"
                        class="adopt-input"
                        placeholder="Username"
                        autocomplete="username"
                        value="<?php
                                echo htmlspecialchars(
                                    $formData['username'] ?? ''
                                );
                                ?>">


                    <?php if (!empty($errors['username'])): ?>

                        <span class="adopt-error">

                            <?php
                            echo htmlspecialchars(
                                $errors['username']
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <button
                        type="submit"
                        name="check_user"
                        class="adopt-button">
                        Check
                    </button>


                    <?php
                    if (
                        !empty($errors['username']) &&
                        $errors['username'] ===
                        "This user doesn't exist."
                    ):
                    ?>

                        <p class="adopt-text">
                            Not registered yet?
                        </p>


                        <a
                            href="index.php"
                            style="text-decoration:none;display:block;">

                            <button
                                type="button"
                                class="adopt-button-secondary">
                                Go to Sign Up
                            </button>

                        </a>

                    <?php endif; ?>

                </form>

            </div>


        <?php endif; ?>


    </section>

</body>

</html>