<?php require __DIR__ . '/includes/header.php'; ?>
<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';


$self = basename(__FILE__);

/*
 * STEP 1: Check whether the entered username exists in the `users`
 * table (database: dogadoption).
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['check_user'])) {

    $username = trim($_POST['username'] ?? '');
    $errors = [];

    if ($username === '') {

        $errors['username'] = "Username is required.";
    } else {

        $stmt = $conn->prepare("SELECT name FROM users WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            // Verified — remember it for the adoption step (kept in the
            // session itself, not flash, so it survives future refreshes).
            $_SESSION['adoption_user'] = $username;
        } else {

            $errors['username'] = "This user doesn't exist.";
        }

        $stmt->close();
    }

    $_SESSION['flash'] = [
        'errors'    => $errors,
        'form_data' => ['username' => $username],
    ];

    header("Location: $self");
    exit();
}

/*
 * STEP 2: Handle the adoption form submission itself. Only allowed if
 * a username has already been verified in this session.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_adoption'])) {

    $errors = [];

    if (empty($_SESSION['adoption_user'])) {

        $errors['general'] = "Please verify your username before submitting the form.";
        $_SESSION['flash'] = ['errors' => $errors, 'form_data' => []];
        header("Location: $self");
        exit();
    }

    $ownerName = $_SESSION['adoption_user'];
    $dogName   = trim($_POST['dog_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $reason    = trim($_POST['reason'] ?? '');

    if ($dogName === '') {
        $errors['dog_name'] = "Please enter the dog's name.";
    }

    if ($phone === '') {
        $errors['phone'] = "Phone number is required.";
    }

    if ($address === '') {
        $errors['address'] = "Address is required.";
    }

    if (empty($errors)) {

        $stmt = $conn->prepare(
            "INSERT INTO adoption_applications
                (owner_name, dog_name, phone, address, reason)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssss",
            $ownerName,
            $dogName,
            $phone,
            $address,
            $reason
        );

        if ($stmt->execute()) {

            // Done with this session's verified user — a refresh after
            // this point should land back on the plain verify screen,
            // not resubmit or re-show the thank-you message forever.
            unset($_SESSION['adoption_user']);

            $_SESSION['flash'] = [
                'success' => true,
                'name'    => $ownerName,
            ];
        } else {

            $errors['general'] = "Unable to submit application. Please try again.";
        }

        $stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = [
            'errors'    => $errors,
            'form_data' => $_POST,
        ];
    }

    header("Location: $self");
    exit();
}

/*
 * GET: read (and immediately clear) whatever the last POST left behind.
 */
$flash    = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

$errors     = $flash['errors'] ?? [];
$formData   = $flash['form_data'] ?? [];
$adoptionSuccess = !empty($flash['success']);
$successName     = $flash['name'] ?? '';

$userVerified = !empty($_SESSION['adoption_user']);
$verifiedName = $_SESSION['adoption_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <title>Dog Adoption</title>

    <!--
        Scoped styling for this page only. Every class below is prefixed
        with "adopt-" so it can never collide with .login-* / .main-form-
        container rules used elsewhere in assets/style.css (that's what
        was causing the overlap with the site header).
    -->
    <style>
        .adopt-page {
            box-sizing: border-box;
            min-height: calc(100vh - 90px);
            /* leaves room for the fixed/sticky navbar above */
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

            <!-- SUCCESS MESSAGE (shown once, right after submission) -->
            <div class="adopt-panel">
                <h1 class="adopt-title">Application Submitted!</h1>
                <p class="adopt-text">
                    Thank you, <?php echo htmlspecialchars($successName); ?>.
                    Your adoption application has been received.
                </p>
            </div>

        <?php elseif ($userVerified): ?>

            <!-- ADOPTION FORM (shown only after username is verified) -->
            <div class="adopt-panel">
                <form class="adopt-form" action="" method="POST">

                    <h1 class="adopt-title">Dog Adoption Application</h1>
                    <span class="adopt-subtext">
                        Welcome, <?php echo htmlspecialchars($verifiedName); ?>!
                    </span>

                    <?php if (!empty($errors['general'])): ?>
                        <span class="adopt-error">
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </span>
                    <?php endif; ?>

                    <input type="text"
                        name="dog_name"
                        class="adopt-input"
                        placeholder="Dog's Name"
                        value="<?php echo htmlspecialchars($formData['dog_name'] ?? ''); ?>">

                    <?php if (!empty($errors['dog_name'])): ?>
                        <span class="adopt-error">
                            <?php echo htmlspecialchars($errors['dog_name']); ?>
                        </span>
                    <?php endif; ?>

                    <input type="text"
                        name="phone"
                        class="adopt-input"
                        placeholder="Phone Number"
                        value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">

                    <?php if (!empty($errors['phone'])): ?>
                        <span class="adopt-error">
                            <?php echo htmlspecialchars($errors['phone']); ?>
                        </span>
                    <?php endif; ?>

                    <input type="text"
                        name="address"
                        class="adopt-input"
                        placeholder="Address"
                        value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>">

                    <?php if (!empty($errors['address'])): ?>
                        <span class="adopt-error">
                            <?php echo htmlspecialchars($errors['address']); ?>
                        </span>
                    <?php endif; ?>

                    <textarea name="reason"
                        class="adopt-input"
                        placeholder="Why do you want to adopt this dog?"><?php echo htmlspecialchars($formData['reason'] ?? ''); ?></textarea>

                    <button type="submit" name="submit_adoption" class="adopt-button">
                        Submit Application
                    </button>

                </form>
            </div>

        <?php else: ?>

            <!-- USERNAME CHECK FORM (default view) -->
            <div class="adopt-panel">
                <form class="adopt-form" action="" method="POST">

                    <h1 class="adopt-title">Verify Your Account</h1>
                    <span class="adopt-subtext">
                        Enter your registered username to continue to the adoption form
                    </span>

                    <input type="text"
                        name="username"
                        class="adopt-input"
                        placeholder="Username"
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">

                    <?php if (!empty($errors['username'])): ?>
                        <span class="adopt-error">
                            <?php echo htmlspecialchars($errors['username']); ?>
                        </span>
                    <?php endif; ?>

                    <button type="submit" name="check_user" class="adopt-button">
                        Check
                    </button>

                    <?php if (!empty($errors['username']) && $errors['username'] === "This user doesn't exist."): ?>
                        <p class="adopt-text">Not registered yet?</p>
                        <a href="index.php" style="text-decoration:none; display:block;">
                            <button type="button" class="adopt-button-secondary">
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