<?php
require_once __DIR__ . '/admin/dataconnection.php';
require __DIR__ . '/includes/header.php';

// Check whether dog ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid dog ID.");
}

$dogId = (int) $_GET['id'];

// Fetch selected dog
$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        dog_id,
        dog_breed,
        dog_image,
        age,
        COALESCE(description, '') AS description
     FROM dogs
     WHERE dog_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $dogId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// Check if dog exists
if (mysqli_num_rows($result) === 0) {
    die("Dog not found.");
}

$dog = mysqli_fetch_assoc($result);

$breed = htmlspecialchars($dog['dog_breed'] ?? '');
$image = htmlspecialchars($dog['dog_image'] ?? '');
$age = htmlspecialchars($dog['age'] ?? '');
$description = htmlspecialchars($dog['description'] ?? 'No description available for this dog.');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/style.css">

    <title><?php echo $breed; ?> - Dog Details</title>

    <style>
        :root {
            --primary: #5a34ae;
            --primary-dark: #48258f;
            --text-dark: #2b2b2b;
            --text-light: #6b6b6b;
            --page-bg: #f4f6f8;
            --card-bg: #ffffff;
        }

        .single-dog-container {
            max-width: 1100px;
            margin: 50px auto 80px;
            padding: 0 20px;
        }

        .dog-details-card {
            background: var(--card-bg);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);

            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Image */

        .dog-detail-image {
            min-height: 500px;
            background: #eee;
        }

        .dog-detail-image img {
            width: 100%;
            height: 100%;
            min-height: 500px;
            object-fit: cover;
            display: block;
        }

        /* Information */

        .dog-detail-content {
            padding: 45px;
            display: flex;
            flex-direction: column;
        }

        .dog-detail-content h1 {
            margin: 0 0 25px;
            font-size: 34px;
            color: var(--text-dark);
            text-transform: capitalize;
        }

        .dog-info-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eeeeee;
            padding: 13px 0;
            font-size: 15px;
        }

        .dog-info-row .label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .dog-info-row .value {
            color: var(--text-light);
        }

        .description-title {
            margin: 30px 0 10px;
            font-size: 20px;
            color: var(--text-dark);
        }

        .description {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 15px;
            margin: 0;
        }

        /* Adopt button */

        .adopt-button {
            display: block;
            text-align: center;
            margin-top: 30px;
            padding: 14px 20px;

            background: var(--primary);
            color: white;

            text-decoration: none;
            border-radius: 9px;

            font-size: 16px;
            font-weight: 600;

            transition: background 0.2s ease,
                transform 0.2s ease;
        }

        .adopt-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Back button */

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .back-button:hover {
            text-decoration: underline;
        }

        @media (max-width: 800px) {

            .dog-details-card {
                grid-template-columns: 1fr;
            }

            .dog-detail-image {
                min-height: 350px;
            }

            .dog-detail-image img {
                min-height: 350px;
            }

            .dog-detail-content {
                padding: 30px;
            }

            .dog-detail-content h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 500px) {

            .single-dog-container {
                padding: 0 15px;
                margin-top: 30px;
            }

            .dog-detail-content {
                padding: 22px;
            }

            .dog-detail-image {
                min-height: 280px;
            }

            .dog-detail-image img {
                min-height: 280px;
            }
        }
    </style>
</head>

<body>

    <div class="single-dog-container">

        <a href="availabledogs.php" class="back-button">
            ← Back to Available Dogs
        </a>

        <div class="dog-details-card">

            <!-- Dog Image -->
            <div class="dog-detail-image">

                <img
                    src="<?php echo $image; ?>"
                    alt="<?php echo $breed; ?>"
                    onerror="this.src='https://via.placeholder.com/600x600?text=No+Image';">

            </div>

            <!-- Dog Information -->
            <div class="dog-detail-content">

                <h1>
                    <?php echo $breed; ?>
                </h1>

                <div class="dog-info-row">
                    <span class="label">Dog ID</span>
                    <span class="value">
                        <?php echo $dog['dog_id']; ?>
                    </span>
                </div>

                <div class="dog-info-row">
                    <span class="label">Breed</span>
                    <span class="value">
                        <?php echo $breed; ?>
                    </span>
                </div>

                <div class="dog-info-row">
                    <span class="label">Age</span>
                    <span class="value">
                        <?php echo $age; ?> years
                    </span>
                </div>

                <h2 class="description-title">
                    About This Dog
                </h2>

                <p class="description">
                    <?php echo nl2br($description); ?>
                </p>

                <!-- Adopt Button -->
                <a href="login.php" class="adopt-button">
                    Adopt This Dog
                </a>

            </div>

        </div>

    </div>

</body>

</html>