<?php
require_once __DIR__ . '/admin/dataconnection.php';
require __DIR__ . '/includes/header.php';

// Fetch dogs from database
$sql = "SELECT dog_id, dog_breed, dog_image FROM dogs ORDER BY added_date DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/style.css">

    <title>Available Dogs</title>

    <style>
        :root {
            --card-bg: #ffffff;
            --page-bg: #f4f6f8;
            --text-dark: #2b2b2b;
            --text-light: #6b6b6b;
            --border: #e6e6e6;
            --primary: #5a34ae;
            --primary-dark: #48258f;
            --radius: 12px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-header h1 {
            margin: 0 0 6px;
            font-size: 30px;
            color: var(--text-dark);
            padding-top: 25px;
        }

        .dog-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }

        .dog-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .dog-image-wrap {
            width: 100%;
            height: 240px;
            background: #eee;
            overflow: hidden;
        }

        .dog-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .dog-info {
            padding: 18px;
            text-align: center;
        }

        .dog-breed {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 14px;
            text-transform: capitalize;
            color: var(--text-dark);
        }

        .view-details {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 11px 15px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .view-details:hover {
            background: var(--primary-dark);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            color: var(--text-light);
            margin-top: 60px;
            font-size: 16px;
        }

        @media (max-width: 900px) {
            .dog-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .dog-grid {
                grid-template-columns: 1fr;
                padding: 0 15px;
            }

            .dog-image-wrap {
                height: 220px;
            }
        }
    </style>
</head>

<body>

    <div class="page-header">
        <h1>Available Dogs</h1>
    </div>

    <div class="dog-grid">

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($dog = mysqli_fetch_assoc($result)): ?>

                <?php
                $dogId = htmlspecialchars($dog['dog_id']);
                $breed = htmlspecialchars($dog['dog_breed']);
                $image = htmlspecialchars($dog['dog_image']);
                ?>

                <div class="dog-card">

                    <div class="dog-image-wrap">
                        <img
                            src="<?php echo $image; ?>"
                            alt="<?php echo $breed; ?>"
                            loading="lazy"
                            onerror="this.src='https://via.placeholder.com/400x300?text=No+Image';">
                    </div>

                    <div class="dog-info">

                        <h3 class="dog-breed">
                            <?php echo $breed; ?>
                        </h3>

                        <a
                            href="single.php?id=<?php echo $dogId; ?>"
                            class="view-details">
                            View Details
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="empty-state">
                No dogs are currently available for adoption.
            </p>

        <?php endif; ?>

    </div>

</body>

</html>