<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/dataconnection.php';


/*
|--------------------------------------------------------------------------
| SEND ADMIN MESSAGE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['send_admin_message'])
) {

    $user_email =
        trim($_POST['user_email'] ?? '');

    $message =
        trim($_POST['message'] ?? '');

    $user_name =
        trim($_POST['user_name'] ?? 'User');


    if (
        $user_email !== '' &&
        $message !== ''
    ) {

        $stmt = $conn->prepare("
            INSERT INTO chat_messages
            (
                user_email,
                user_name,
                sender_type,
                message,
                is_read
            )
            VALUES
            (?, ?, 'admin', ?, 1)
        ");

        $stmt->bind_param(
            "sss",
            $user_email,
            $user_name,
            $message
        );

        $stmt->execute();

        $stmt->close();
    }


    /*
     * Return to the same conversation.
     */

    header(
        "Location: admin_chat.php?user=" .
            urlencode($user_email)
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET SELECTED USER
|--------------------------------------------------------------------------
*/

$selectedEmail =
    trim($_GET['user'] ?? '');


/*
|--------------------------------------------------------------------------
| MARK SELECTED USER'S MESSAGES AS READ
|--------------------------------------------------------------------------
|
| When admin opens a conversation, all unread messages
| from that user are marked as read.
|
*/

if ($selectedEmail !== '') {

    $readStmt = $conn->prepare("
        UPDATE chat_messages
        SET is_read = 1
        WHERE user_email = ?
          AND sender_type = 'user'
          AND is_read = 0
    ");

    if ($readStmt) {

        $readStmt->bind_param(
            "s",
            $selectedEmail
        );

        $readStmt->execute();

        $readStmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| GET ALL USERS WHO HAVE CHATTED
|--------------------------------------------------------------------------
*/

$users = [];

$userResult = $conn->query("
    SELECT
        user_email,
        MAX(user_name) AS user_name,
        COUNT(*) AS message_count,
        MAX(id) AS last_message_id,

        SUM(
            CASE
                WHEN sender_type = 'user'
                 AND is_read = 0
                THEN 1
                ELSE 0
            END
        ) AS unread_count

    FROM chat_messages

    WHERE user_email IS NOT NULL
      AND user_email != ''

    GROUP BY user_email

    ORDER BY last_message_id DESC
");


if ($userResult) {

    while (
        $row =
        $userResult->fetch_assoc()
    ) {

        $users[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| GET SELECTED USER'S MESSAGES
|--------------------------------------------------------------------------
*/

$messages = [];


if ($selectedEmail !== '') {

    $stmt = $conn->prepare("
        SELECT
            id,
            user_email,
            user_name,
            sender_type,
            message,
            is_read
        FROM chat_messages
        WHERE user_email = ?
        ORDER BY id ASC
    ");


    $stmt->bind_param(
        "s",
        $selectedEmail
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row =
        $result->fetch_assoc()
    ) {

        $messages[] = $row;
    }


    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| GET SELECTED USER NAME
|--------------------------------------------------------------------------
*/

$selectedUserName = 'User';


foreach ($users as $user) {

    if (
        $user['user_email'] ===
        $selectedEmail
    ) {

        $selectedUserName =
            $user['user_name'];

        break;
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

    <title>Chat Support - Happy Tails</title>


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

            background: #f6f4fb;

            color: #222;
        }


        .chat-page {

            margin-left: 245px;

            min-height: 100vh;

            padding: 25px;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .page-header {

            margin-bottom: 20px;
        }


        .page-header h1 {

            margin: 0;

            color: #2b2050;

            font-size: 27px;
        }


        .page-header p {

            margin-top: 6px;

            color: #777;
        }


        .back-dashboard {

            display: inline-block;

            margin-top: 12px;

            padding: 10px 16px;

            background: #5a34ae;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s;
        }


        .back-dashboard:hover {

            background: #48258f;

            color: white;
        }


        /* =========================================================
           CHAT LAYOUT
        ========================================================= */

        .chat-layout {

            display: grid;

            grid-template-columns: 280px 1fr;

            height: calc(100vh - 140px);

            min-height: 500px;

            background: white;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, .06);
        }


        /* =========================================================
           USERS
        ========================================================= */

        .users-panel {

            border-right: 1px solid #eee;

            background: #faf9fc;

            overflow-y: auto;
        }


        .users-title {

            padding: 18px;

            font-size: 16px;

            font-weight: bold;

            color: #2b2050;

            border-bottom: 1px solid #eee;
        }


        .user-item {

            display: block;

            padding: 15px;

            text-decoration: none;

            color: #333;

            border-bottom: 1px solid #eee;

            transition: .2s;

            position: relative;
        }


        .user-item:hover {

            background: #f0ecf8;
        }


        .user-item.active {

            background: #e9e2f7;

            border-left: 4px solid #5a34ae;
        }


        .user-name {

            font-weight: bold;

            color: #3d3154;

            margin-bottom: 5px;

            padding-right: 30px;
        }


        .user-email {

            font-size: 12px;

            color: #777;

            word-break: break-word;
        }


        .message-count {

            display: inline-block;

            margin-top: 7px;

            font-size: 11px;

            color: #5a34ae;
        }


        /* =========================================================
           UNREAD BADGE
        ========================================================= */

        .unread-badge {

            position: absolute;

            top: 13px;

            right: 13px;

            min-width: 22px;

            height: 22px;

            padding: 0 6px;

            border-radius: 50px;

            background: #ff4d5a;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 11px;

            font-weight: 700;
        }


        /* =========================================================
           CHAT
        ========================================================= */

        .chat-area {

            display: flex;

            flex-direction: column;

            min-width: 0;
        }


        .chat-header {

            padding: 17px 20px;

            background: #5a34ae;

            color: white;
        }


        .chat-header h2 {

            margin: 0;

            font-size: 18px;
        }


        .chat-header p {

            margin: 5px 0 0;

            font-size: 12px;

            opacity: .85;
        }


        .messages {

            flex: 1;

            overflow-y: auto;

            padding: 25px;

            background: #f8f7fb;
        }


        .message-row {

            display: flex;

            margin-bottom: 14px;
        }


        .message-row.user {

            justify-content: flex-start;
        }


        .message-row.admin {

            justify-content: flex-end;
        }


        .message-box {

            max-width: 70%;

            padding: 11px 15px;

            border-radius: 15px;

            line-height: 1.5;

            word-wrap: break-word;

            overflow-wrap: break-word;
        }


        .message-row.user .message-box {

            background: white;

            color: #333;

            border: 1px solid #e5e2eb;

            border-bottom-left-radius: 4px;
        }


        .message-row.admin .message-box {

            background: #5a34ae;

            color: white;

            border-bottom-right-radius: 4px;
        }


        .sender {

            font-size: 10px;

            font-weight: bold;

            margin-bottom: 4px;

            opacity: .7;
        }


        .message-text {

            font-size: 14px;
        }


        /* =========================================================
           SEND FORM
        ========================================================= */

        .chat-form {

            display: flex;

            gap: 10px;

            padding: 15px;

            border-top: 1px solid #eee;

            background: white;
        }


        .chat-form input {

            flex: 1;

            padding: 13px;

            border: 1px solid #ddd;

            border-radius: 9px;

            outline: none;

            font-size: 14px;
        }


        .chat-form input:focus {

            border-color: #5a34ae;
        }


        .chat-form button {

            border: none;

            background: #5a34ae;

            color: white;

            padding: 0 22px;

            border-radius: 9px;

            cursor: pointer;

            font-weight: bold;
        }


        .chat-form button:hover {

            background: #48258f;
        }


        /* =========================================================
           NO CHAT
        ========================================================= */

        .no-chat {

            display: flex;

            align-items: center;

            justify-content: center;

            height: 100%;

            color: #999;

            text-align: center;

            padding: 30px;
        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media(max-width: 900px) {

            .chat-page {

                margin-left: 0;

                padding: 15px;
            }


            .chat-layout {

                grid-template-columns: 220px 1fr;
            }
        }


        @media(max-width: 650px) {

            .chat-layout {

                grid-template-columns: 1fr;

                height: auto;
            }


            .users-panel {

                max-height: 220px;

                border-right: none;

                border-bottom: 1px solid #eee;
            }


            .chat-area {

                height: 600px;
            }


            .message-box {

                max-width: 85%;
            }
        }
    </style>

</head>


<body>


    <div class="chat-page">


        <!-- HEADER -->

        <div class="page-header">

            <h1>
                💬 Chat Support
            </h1>

            <p>
                Manage conversations with Happy Tails users.
            </p>

            <a
                href="admindashboard.php"
                class="back-dashboard">

                ← Back to Dashboard

            </a>

        </div>


        <!-- CHAT LAYOUT -->

        <div class="chat-layout">


            <!-- USER LIST -->

            <div class="users-panel">

                <div class="users-title">

                    Users

                </div>


                <?php if (empty($users)): ?>

                    <div
                        style="
                            padding:20px;
                            color:#999;
                            font-size:13px;
                        ">

                        No chat conversations yet.

                    </div>

                <?php else: ?>


                    <?php foreach ($users as $user): ?>


                        <a
                            href="admin_chat.php?user=<?php
                                                        echo urlencode(
                                                            $user['user_email']
                                                        );
                                                        ?>"
                            class="user-item
                            <?php
                            echo (
                                $selectedEmail ===
                                $user['user_email']
                            )
                                ? 'active'
                                : '';
                            ?>">

                            <?php

                            $unread =
                                (int)(
                                    $user['unread_count']
                                    ?? 0
                                );

                            ?>


                            <?php if ($unread > 0): ?>

                                <span class="unread-badge">

                                    <?php
                                    echo $unread;
                                    ?>

                                </span>

                            <?php endif; ?>


                            <div class="user-name">

                                <?php

                                echo htmlspecialchars(
                                    $user['user_name']
                                );

                                ?>

                            </div>


                            <div class="user-email">

                                <?php

                                echo htmlspecialchars(
                                    $user['user_email']
                                );

                                ?>

                            </div>


                            <div class="message-count">

                                <?php

                                echo (int)
                                $user['message_count'];

                                ?>

                                messages

                            </div>

                        </a>


                    <?php endforeach; ?>


                <?php endif; ?>

            </div>


            <!-- CHAT AREA -->

            <div class="chat-area">


                <?php if ($selectedEmail === ''): ?>


                    <div class="no-chat">

                        <div>

                            <div
                                style="
                                    font-size:45px;
                                    margin-bottom:10px;
                                ">

                                💬

                            </div>


                            <strong>

                                Select a user

                            </strong>


                            <br>


                            <span>

                                Choose a user from the left
                                to view their conversation.

                            </span>

                        </div>

                    </div>


                <?php else: ?>


                    <!-- CHAT HEADER -->

                    <div class="chat-header">

                        <h2>

                            <?php

                            echo htmlspecialchars(
                                $selectedUserName
                            );

                            ?>

                        </h2>


                        <p>

                            <?php

                            echo htmlspecialchars(
                                $selectedEmail
                            );

                            ?>

                        </p>

                    </div>


                    <!-- MESSAGES -->

                    <div
                        class="messages"
                        id="messages">


                        <?php if (empty($messages)): ?>


                            <div class="no-chat">

                                No messages yet.

                            </div>


                        <?php else: ?>


                            <?php foreach ($messages as $message): ?>


                                <?php

                                $sender =
                                    strtolower(
                                        trim(
                                            $message['sender_type']
                                        )
                                    );

                                ?>


                                <div
                                    class="message-row
                                    <?php
                                    echo (
                                        $sender === 'admin'
                                    )
                                        ? 'admin'
                                        : 'user';
                                    ?>">

                                    <div
                                        class="message-box">

                                        <div
                                            class="sender">

                                            <?php

                                            if (
                                                $sender ===
                                                'admin'
                                            ) {

                                                echo 'You';
                                            } else {

                                                echo htmlspecialchars(
                                                    $message['user_name']
                                                );
                                            }

                                            ?>

                                        </div>


                                        <div
                                            class="message-text">

                                            <?php

                                            echo nl2br(
                                                htmlspecialchars(
                                                    $message['message']
                                                )
                                            );

                                            ?>

                                        </div>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                    <!-- SEND FORM -->

                    <form
                        method="POST"
                        class="chat-form">

                        <input
                            type="hidden"
                            name="user_email"
                            value="<?php
                                    echo htmlspecialchars(
                                        $selectedEmail
                                    );
                                    ?>">


                        <input
                            type="hidden"
                            name="user_name"
                            value="<?php
                                    echo htmlspecialchars(
                                        $selectedUserName
                                    );
                                    ?>">


                        <input
                            type="text"
                            name="message"
                            placeholder="Type your reply..."
                            autocomplete="off"
                            maxlength="1000"
                            required>


                        <button
                            type="submit"
                            name="send_admin_message">

                            Send

                        </button>

                    </form>


                <?php endif; ?>


            </div>

        </div>

    </div>


    <script>
        /*
        |--------------------------------------------------------------------------
        | SCROLL TO LATEST MESSAGE
        |--------------------------------------------------------------------------
        */

        const messages =
            document.getElementById('messages');

        if (messages) {

            messages.scrollTop =
                messages.scrollHeight;
        }
    </script>


</body>

</html>