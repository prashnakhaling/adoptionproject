<?php

require __DIR__ . '/includes/header.php';

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';


/* =========================
   GET STORY ID
========================= */

$storyId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


/* =========================
   ESCAPE OUTPUT
========================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================
   IMAGE PATH
========================= */

function getStoryImage($image)
{
    $image = trim((string)$image);

    if ($image === '') {
        return 'assets/images/default-dog.jpg';
    }

    if (
        strpos($image, 'assets/images/') === 0 ||
        strpos($image, 'dogpic/') === 0
    ) {
        return $image;
    }

    return 'assets/images/' . rawurlencode($image);
}


/* =========================
   DATE FORMAT
========================= */

function formatStoryDate($date)
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return '';
    }

    $day = (int)date('j', $timestamp);

    if ($day >= 11 && $day <= 13) {
        $suffix = 'th';
    } else {
        switch ($day % 10) {

            case 1:
                $suffix = 'st';
                break;

            case 2:
                $suffix = 'nd';
                break;

            case 3:
                $suffix = 'rd';
                break;

            default:
                $suffix = 'th';
                break;
        }
    }

    return $day . $suffix . ' ' . date('M Y', $timestamp);
}


/* =========================
   FETCH SINGLE PUBLISHED STORY
========================= */

$story = null;

if ($storyId > 0) {

    $stmt = $conn->prepare("
        SELECT
            story_id,
            title,
            story_image,
            description,
            added_date
        FROM stories
        WHERE story_id = ?
          AND status = 'published'
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $storyId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result) {
            $story = $result->fetch_assoc();
        }

        $stmt->close();
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
        <?php
        echo $story
            ? e($story['title']) . ' | Happy Tails'
            : 'Story Not Found | Happy Tails';
        ?>
    </title>


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="assets/style.css">


    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #faf8ff;
            color: #333;
        }


        /* =========================
           SINGLE STORY CONTAINER
        ========================= */

        .single-story-container {

            width: 92%;
            max-width: 900px;

            margin: 55px auto 70px;
        }


        /* =========================
           BACK BUTTON
        ========================= */

        .back-stories {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            color: #5a34ae;

            text-decoration: none;

            font-size: 15px;

            font-weight: 600;

            margin-bottom: 25px;

            transition: 0.3s ease;
        }

        .back-stories:hover {
            transform: translateX(-3px);
        }


        /* =========================
           STORY ARTICLE
        ========================= */

        .single-story {

            background: white;

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 8px 30px rgba(0, 0, 0, 0.08);
        }


        /* =========================
           STORY IMAGE
        ========================= */

        .single-story-image {

            width: 100%;

            height: 450px;

            background: #eee;

            overflow: hidden;
        }

        .single-story-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;
        }


        /* =========================
           STORY CONTENT
        ========================= */

        .single-story-content {

            padding: 40px;
        }


        /* TITLE */

        .single-story-title {

            color: #5a34ae;

            font-size: 36px;

            line-height: 1.3;

            margin-bottom: 12px;
        }


        /* DATE */

        .single-story-date {

            color: #7d55c7;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 28px;
        }

        .single-story-date i {

            margin-right: 6px;
        }


        /* DESCRIPTION */

        .single-story-description {

            font-size: 17px;

            line-height: 1.9;

            color: #555;

            white-space: pre-line;
        }


        /* =========================
           NOT FOUND
        ========================= */

        .story-not-found {

            text-align: center;

            background: white;

            padding: 70px 25px;

            border-radius: 20px;

            box-shadow:
                0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .story-not-found i {

            font-size: 60px;

            color: #7d55c7;

            margin-bottom: 20px;
        }

        .story-not-found h1 {

            color: #333;

            font-size: 28px;

            margin-bottom: 12px;
        }

        .story-not-found p {

            color: #777;

            margin-bottom: 25px;
        }

        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            background: #5a34ae;

            color: white;

            text-decoration: none;

            padding: 11px 20px;

            border-radius: 8px;

            font-weight: 600;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 600px) {

            .single-story-container {

                width: 92%;

                margin: 35px auto 50px;
            }

            .single-story-image {

                height: 280px;
            }

            .single-story-content {

                padding: 25px 20px;
            }

            .single-story-title {

                font-size: 28px;
            }

            .single-story-description {

                font-size: 16px;

                line-height: 1.8;
            }
        }
    </style>

</head>


<body>


    <main class="single-story-container">


        <?php if ($story): ?>


            <!-- BACK -->

            <a
                href="stories.php"
                class="back-stories">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Stories

            </a>



            <!-- STORY -->

            <article class="single-story">


                <!-- IMAGE -->

                <div class="single-story-image">

                    <img
                        src="<?php echo e(
                                    getStoryImage(
                                        $story['story_image']
                                    )
                                ); ?>"
                        alt="<?php echo e(
                                    $story['title']
                                ); ?>"
                        onerror="this.src='assets/images/default-dog.jpg';">

                </div>



                <!-- CONTENT -->

                <div class="single-story-content">


                    <!-- TITLE -->

                    <h1 class="single-story-title">

                        <?php
                        echo e(
                            $story['title']
                        );
                        ?>

                    </h1>



                    <!-- DATE -->

                    <div class="single-story-date">

                        <i class="fa-regular fa-calendar"></i>

                        <?php
                        echo e(
                            formatStoryDate(
                                $story['added_date']
                            )
                        );
                        ?>

                    </div>



                    <!-- FULL DESCRIPTION -->

                    <div class="single-story-description">

                        <?php
                        echo nl2br(
                            e(
                                $story['description']
                            )
                        );
                        ?>

                    </div>


                </div>

            </article>


        <?php else: ?>


            <!-- STORY NOT FOUND -->

            <div class="story-not-found">

                <i class="fa-solid fa-book-open"></i>

                <h1>
                    Story Not Found
                </h1>

                <p>
                    This story does not exist or is not
                    currently available.
                </p>

                <a
                    href="stories.php"
                    class="back-button">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Stories

                </a>

            </div>


        <?php endif; ?>


    </main>


</body>

</html>


<?php require __DIR__ . '/includes/footer.php'; ?>