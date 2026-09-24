<?php
require __DIR__ . '/includes/header.php';

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dataconnection.php';


/* =========================
   FETCH PUBLISHED STORIES
========================= */

$stories = [];

$result = $conn->query("
    SELECT
        story_id,
        title,
        story_image,
        description,
        added_date
    FROM stories
    WHERE status = 'published'
    ORDER BY story_id DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
}


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
   Example: 5th Sep 2026
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
   GET FIRST 2 SENTENCES
========================= */

function getStoryPreview($description)
{
    $description = trim((string)$description);

    if ($description === '') {
        return '';
    }

    /*
     * Split after ., ! or ?
     */
    $sentences = preg_split(
        '/(?<=[.!?])\s+/',
        $description,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if (count($sentences) <= 2) {
        return $description;
    }

    return $sentences[0] . ' ' . $sentences[1];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Our Adoption Stories | Happy Tails</title>

    <!-- Font Awesome -->
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
           PAGE HEADING
        ========================= */

        .stories-heading {
            text-align: center;
            padding: 55px 20px 30px;
        }

        .stories-heading h1 {
            font-size: 38px;
            color: #5a34ae;
            margin-bottom: 10px;
        }

        .stories-heading h1 i {
            margin-right: 8px;
        }

        .stories-heading p {
            max-width: 650px;
            margin: 0 auto;
            color: #666;
            font-size: 16px;
            line-height: 1.7;
        }


        /* =========================
           CONTAINER
        ========================= */

        .stories-container {
            width: 92%;
            max-width: 1200px;
            margin: 20px auto 60px;
        }


        /* =========================
           STORY GRID
        ========================= */

        .stories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }


        /* =========================
           STORY CARD
        ========================= */

        .story-card {
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.08);

            transition: all 0.3s ease;

            display: flex;
            flex-direction: column;
        }

        .story-card:hover {
            transform: translateY(-7px);

            box-shadow:
                0 14px 35px rgba(0, 0, 0, 0.13);
        }


        /* =========================
           IMAGE
        ========================= */

        .story-image {
            width: 100%;
            height: 240px;
            overflow: hidden;
            background: #eee;
        }

        .story-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;

            transition: transform 0.4s ease;
        }

        .story-card:hover .story-image img {
            transform: scale(1.05);
        }


        /* =========================
           CONTENT
        ========================= */

        .story-content {
            padding: 22px;

            display: flex;
            flex-direction: column;

            flex: 1;
        }


        /* =========================
           TITLE
        ========================= */

        .story-title {
            font-size: 23px;
            color: #5a34ae;

            line-height: 1.35;

            margin-bottom: 10px;
        }


        /* =========================
           DATE
        ========================= */

        .story-date {
            color: #7d55c7;

            font-size: 13px;
            font-weight: 600;

            margin-bottom: 14px;
        }

        .story-date i {
            margin-right: 5px;
        }


        /* =========================
           DESCRIPTION
        ========================= */

        .story-description {
            font-size: 15px;

            line-height: 1.7;

            color: #555;

            margin-bottom: 18px;
        }


        /* =========================
           READ FULL STORY
        ========================= */

        .read-story {
            margin-top: auto;

            display: inline-flex;
            align-items: center;
            gap: 7px;

            width: fit-content;

            padding: 10px 17px;

            background: #5a34ae;
            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;
            font-weight: 600;

            transition: 0.3s ease;
        }

        .read-story:hover {
            background: #47268f;
            transform: translateX(3px);
        }


        /* =========================
           NO STORIES
        ========================= */

        .no-stories {
            text-align: center;

            padding: 70px 20px;

            background: white;

            border-radius: 18px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .no-stories i {
            font-size: 55px;

            color: #7d55c7;

            margin-bottom: 20px;
        }

        .no-stories h2 {
            font-size: 25px;

            margin-bottom: 10px;
        }

        .no-stories p {
            color: #777;
        }


        /* =========================
           TABLET
        ========================= */

        @media (max-width: 900px) {

            .stories-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stories-heading h1 {
                font-size: 34px;
            }
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 600px) {

            .stories-heading {
                padding: 40px 18px 20px;
            }

            .stories-heading h1 {
                font-size: 29px;
            }

            .stories-heading p {
                font-size: 15px;
            }

            .stories-grid {
                grid-template-columns: 1fr;
                gap: 22px;
            }

            .story-image {
                height: 240px;
            }
        }

    </style>

</head>


<body>


    <!-- =========================
         SIMPLE PAGE HEADING
    ========================= -->

    <section class="stories-heading">

        <h1>
            <i class="fa-solid fa-heart"></i>
            Our Adoption Stories
        </h1>

        <p>
            Read heartwarming stories of rescued dogs
            finding loving families and their forever homes.
        </p>

    </section>



    <!-- =========================
         STORIES
    ========================= -->

    <main class="stories-container">

        <?php if (!empty($stories)): ?>

            <div class="stories-grid">

                <?php foreach ($stories as $story): ?>

                    <article class="story-card">


                        <!-- IMAGE -->

                        <div class="story-image">

                            <img
                                src="<?php echo e(
                                    getStoryImage(
                                        $story['story_image']
                                    )
                                ); ?>"
                                alt="<?php echo e($story['title']); ?>"
                                loading="lazy"
                                onerror="this.src='assets/images/default-dog.jpg';">

                        </div>



                        <!-- CONTENT -->

                        <div class="story-content">


                            <!-- TITLE -->

                            <h2 class="story-title">

                                <?php
                                echo e($story['title']);
                                ?>

                            </h2>



                            <!-- DATE -->

                            <div class="story-date">

                                <i class="fa-regular fa-calendar"></i>

                                <?php
                                echo e(
                                    formatStoryDate(
                                        $story['added_date']
                                    )
                                );
                                ?>

                            </div>



                            <!-- 2 SENTENCE PREVIEW -->

                            <p class="story-description">

                                <?php
                                echo e(
                                    getStoryPreview(
                                        $story['description']
                                    )
                                );
                                ?>

                                <?php
                                if (
                                    strlen(
                                        trim(
                                            $story['description']
                                        )
                                    ) >
                                    strlen(
                                        trim(
                                            getStoryPreview(
                                                $story['description']
                                            )
                                        )
                                    )
                                ):
                                ?>
                                    ...
                                <?php endif; ?>

                            </p>



                            <!-- READ FULL STORY -->

                            <a
                                href="singlestories.php?id=<?php echo (int)$story['story_id']; ?>"
                                class="read-story">

                                Read Full Story

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


        <?php else: ?>


            <div class="no-stories">

                <i class="fa-solid fa-book-open"></i>

                <h2>
                    No Stories Yet
                </h2>

                <p>
                    Our happy adoption stories
                    will appear here soon.
                </p>

            </div>


        <?php endif; ?>

    </main>


</body>

</html>


<?php require __DIR__ . '/includes/footer.php'; ?>