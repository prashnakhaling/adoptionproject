<?php

session_start();

require_once __DIR__ . '/admin/dataconnection.php';


/*
|--------------------------------------------------------------------------
| CACHE CONTROL
|--------------------------------------------------------------------------
*/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


/*
|--------------------------------------------------------------------------
| GET LOGGED-IN USER
|--------------------------------------------------------------------------
|
| Your users table:
| name
| email
| password
|
| We use email to identify the user.
|--------------------------------------------------------------------------
*/

$user_email =
    $_SESSION['email']
    ?? $_SESSION['user_email']
    ?? '';

$user_name =
    $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? 'User';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (empty($user_email)) {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| SEND MESSAGE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['message'])
) {

    $message = trim($_POST['message']);


    if ($message !== '') {

        /*
         * Use prepared statement
         * instead of directly inserting user input.
         */

        $stmt = mysqli_prepare(
            $conn,
            "
            INSERT INTO chat_messages
            (
                user_email,
                user_name,
                sender_type,
                message
            )
            VALUES
            (?, ?, 'user', ?)
            "
        );


        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $user_email,
            $user_name,
            $message
        );


        mysqli_stmt_execute($stmt);


        mysqli_stmt_close($stmt);
    }


    /*
     * Prevent duplicate message when page refreshes.
     */

    header("Location: user_chatsupport.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ONLY THIS USER'S MESSAGES
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        user_email,
        user_name,
        sender_type,
        message
    FROM chat_messages
    WHERE user_email = ?
    ORDER BY id ASC
    "
);


mysqli_stmt_bind_param(
    $stmt,
    "s",
    $user_email
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Chat with Admin - Happy Tails</title>


    <style>
        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: Arial, sans-serif;

            background: #f6f4fb;

        }


        .chat-wrapper {

            width: 100%;

            max-width: 850px;

            height: 90vh;

            margin: 5vh auto;

            background: white;

            border-radius: 18px;

            box-shadow:
                0 8px 30px rgba(0, 0, 0, .10);

            display: flex;

            flex-direction: column;

            overflow: hidden;

        }


        /* ================= HEADER ================= */

        .chat-header {

            background: #5a34ae;

            color: white;

            padding: 18px 22px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .chat-header h2 {

            margin: 0;

            font-size: 20px;

        }


        .chat-header span {

            display: block;

            margin-top: 4px;

            font-size: 13px;

            opacity: .85;

        }


        .back-btn {

            color: white;

            text-decoration: none;

            background:
                rgba(255, 255, 255, .15);

            padding: 8px 12px;

            border-radius: 8px;

        }


        .back-btn:hover {

            background:
                rgba(255, 255, 255, .25);

        }


        /* ================= CHAT BOX ================= */

        .chat-box {

            flex: 1;

            overflow-y: auto;

            padding: 25px;

            background: #f8f7fb;

        }


        .message-row {

            display: flex;

            margin-bottom: 15px;

        }


        .message-row.user {

            justify-content: flex-end;

        }


        .message-row.admin {

            justify-content: flex-start;

        }


        .message {

            max-width: 70%;

            padding: 11px 15px;

            border-radius: 15px;

            line-height: 1.5;

            word-wrap: break-word;

            overflow-wrap: break-word;

        }


        .message-row.user .message {

            background: #5a34ae;

            color: white;

            border-bottom-right-radius: 4px;

        }


        .message-row.admin .message {

            background: white;

            color: #333;

            border: 1px solid #e5e2eb;

            border-bottom-left-radius: 4px;

        }


        .sender-name {

            font-size: 11px;

            font-weight: bold;

            margin-bottom: 4px;

            opacity: .75;

        }


        .message-text {

            font-size: 15px;

        }


        .empty-chat {

            text-align: center;

            color: #999;

            margin-top: 30vh;

        }


        /* ================= FORM ================= */

        .chat-form {

            display: flex;

            gap: 10px;

            padding: 15px;

            background: white;

            border-top: 1px solid #eee;

        }


        .chat-form input {

            flex: 1;

            padding: 13px 15px;

            border: 1px solid #ddd;

            border-radius: 10px;

            outline: none;

            font-size: 15px;

        }


        .chat-form input:focus {

            border-color: #5a34ae;

        }


        .chat-form button {

            border: none;

            background: #5a34ae;

            color: white;

            padding: 0 22px;

            border-radius: 10px;

            cursor: pointer;

            font-weight: bold;

        }


        .chat-form button:hover {

            background: #48258f;

        }


        /* ================= MOBILE ================= */

        @media(max-width: 600px) {

            .chat-wrapper {

                height: 100vh;

                margin: 0;

                border-radius: 0;

            }


            .message {

                max-width: 85%;

            }


            .chat-header {

                padding: 15px;

            }


            .chat-box {

                padding: 15px;

            }

        }
    </style>

</head>


<body>


    <div class="chat-wrapper">


        <!-- ================= HEADER ================= -->

        <div class="chat-header">

            <div>

                <h2>
                    💬 Chat with Admin
                </h2>

                <span>
                    Happy Tails Support
                </span>

            </div>


            <a
                href="userdashboard.php"
                class="back-btn">
                ← Back
            </a>

        </div>



        <!-- ================= CHAT ================= -->

        <div
            class="chat-box"
            id="chatBox">

            <?php if (mysqli_num_rows($result) === 0): ?>

                <div class="empty-chat">

                    Start a conversation with
                    Happy Tails Admin 🐾

                </div>

            <?php endif; ?>


            <?php while ($row = mysqli_fetch_assoc($result)): ?>


                <?php

                $sender =
                    strtolower(
                        trim($row['sender_type'])
                    );

                ?>


                <div
                    class="message-row
                <?php echo htmlspecialchars($sender); ?>">

                    <div class="message">


                        <div class="sender-name">

                            <?php

                            if ($sender === 'admin') {

                                echo 'Happy Tails Admin';
                            } else {

                                echo htmlspecialchars(
                                    $row['user_name']
                                );
                            }

                            ?>

                        </div>


                        <div class="message-text">

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $row['message']
                                )
                            );

                            ?>

                        </div>


                    </div>

                </div>


            <?php endwhile; ?>


        </div>



        <!-- ================= SEND MESSAGE ================= -->

        <form
            method="POST"
            class="chat-form">

            <input
                type="text"
                name="message"
                placeholder="Type your message..."
                autocomplete="off"
                maxlength="1000"
                required>


            <button type="submit">
                Send
            </button>

        </form>


    </div>


    <script>
        /*
|--------------------------------------------------------------------------
| Always scroll to latest message
|--------------------------------------------------------------------------
*/

        const chatBox =
            document.getElementById('chatBox');

        chatBox.scrollTop =
            chatBox.scrollHeight;
    </script>


</body>

</html>