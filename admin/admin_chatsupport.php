<?php
session_start();
include 'dataconnection.php';

/* ================= SEND MESSAGE ================= */
if (isset($_POST['send'])) {

    $user = mysqli_real_escape_string($conn, $_POST['user']);
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (!empty($message)) {

        mysqli_query($conn, "
            INSERT INTO chat_messages(sender, receiver, message)
            VALUES('Admin', '$user', '$message')
        ");

        header("Location: admin_chatsupport.php?user=" . urlencode($user));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Chat Support</title>

    <style>
        /* ================= RESET ================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
            overflow: hidden;
        }

        /* ================= MAIN LAYOUT ================= */

        .container {
            width: 100%;
            height: 100vh;

            display: flex;

            background: #ffffff;
        }

        /* ================= LEFT SIDEBAR ================= */

        .left {
            width: 290px;
            height: 100vh;

            background: #ffffff;

            border-right: 1px solid #e5e7eb;

            display: flex;
            flex-direction: column;

            flex-shrink: 0;
        }

        /* ================= SIDEBAR HEADER ================= */

        .sidebar-header {
            height: 82px;

            padding: 0 22px;

            display: flex;
            align-items: center;

            border-bottom: 1px solid #e5e7eb;

            background: #ffffff;
        }

        .sidebar-icon {
            width: 44px;
            height: 44px;

            border-radius: 14px;

            background: #a3aef1;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 21px;

            margin-right: 12px;
        }

        .sidebar-title h2 {
            font-size: 17px;
            font-weight: 650;

            color: #1f2937;
        }

        .sidebar-title p {
            font-size: 11px;
            color: #9ca3af;

            margin-top: 3px;
        }

        /* ================= USER LIST ================= */

        .user-list {
            flex: 1;

            overflow-y: auto;

            padding: 14px 10px;
        }

        .user-list::-webkit-scrollbar {
            width: 6px;
        }

        .user-list::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 20px;
        }

        .user-item {
            display: flex;

            align-items: center;

            padding: 12px 12px;

            margin-bottom: 5px;

            text-decoration: none;

            color: #374151;

            border-radius: 12px;

            transition: all 0.2s ease;
        }

        .user-item:hover {
            background: #f3f4ff;
            transform: translateX(2px);
        }

        .user-item.active {
            background: #eef0ff;
        }

        /* ================= USER AVATAR ================= */

        .user-avatar {
            width: 42px;
            height: 42px;

            border-radius: 50%;

            background: #a3aef1;

            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
            font-weight: 600;

            margin-right: 12px;

            flex-shrink: 0;
        }

        .user-info {
            min-width: 0;
        }

        .user-info strong {
            display: block;

            font-size: 14px;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-info span {
            display: block;

            font-size: 11px;

            color: #9ca3af;

            margin-top: 3px;
        }

        /* ================= EMPTY USERS ================= */

        .no-users {
            padding: 35px 20px;

            text-align: center;

            color: #9ca3af;

            font-size: 13px;
        }

        /* ================= RIGHT CHAT ================= */

        .right {
            flex: 1;

            height: 100vh;

            min-width: 0;

            display: flex;
            flex-direction: column;

            background: #f8fafc;
        }

        /* ================= CHAT HEADER ================= */

        .chat-header {
            height: 82px;

            flex-shrink: 0;

            display: flex;
            align-items: center;

            padding: 0 28px;

            background: linear-gradient(135deg,
                    #a3aef1,
                    #a3aef1);

            color: white;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .chat-avatar {
            width: 48px;
            height: 48px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.2);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;

            border: 2px solid rgba(255, 255, 255, 0.45);

            margin-right: 14px;
        }

        .chat-user-info h2 {
            font-size: 18px;

            font-weight: 600;
        }

        .chat-user-info p {
            font-size: 11px;

            margin-top: 4px;

            opacity: 0.85;
        }

        /* ================= CHAT AREA ================= */

        .chatbox {
            flex: 1;

            overflow-y: auto;

            padding: 30px 7%;

            background:
                radial-gradient(circle at top left,
                    rgba(163, 174, 241, 0.08),
                    transparent 30%),
                #f8fafc;
        }

        .chatbox::-webkit-scrollbar {
            width: 7px;
        }

        .chatbox::-webkit-scrollbar-track {
            background: transparent;
        }

        .chatbox::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 20px;
        }

        /* ================= MESSAGE ROW ================= */

        .message {
            display: flex;

            width: 100%;

            margin-bottom: 18px;
        }

        .message.admin {
            justify-content: flex-end;
        }

        .message.user {
            justify-content: flex-start;
        }

        /* ================= MESSAGE BUBBLE ================= */

        .bubble {
            max-width: 65%;

            padding: 12px 17px;

            border-radius: 18px;

            font-size: 14px;

            line-height: 1.5;

            word-wrap: break-word;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .message.admin .bubble {
            background: #a3aef1;

            color: white;

            border-bottom-right-radius: 5px;
        }

        .message.user .bubble {
            background: white;

            color: #374151;

            border: 1px solid #e5e7eb;

            border-bottom-left-radius: 5px;
        }

        .sender-name {
            font-size: 10px;

            font-weight: 600;

            margin-bottom: 4px;

            opacity: 0.75;
        }

        /* ================= EMPTY CHAT ================= */

        .empty-chat {
            flex: 1;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            text-align: center;

            color: #9ca3af;
        }

        .empty-icon {
            width: 80px;
            height: 80px;

            border-radius: 50%;

            background: #eef0ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 34px;

            margin-bottom: 18px;
        }

        .empty-chat h2 {
            color: #4b5563;

            font-size: 20px;

            margin-bottom: 7px;
        }

        .empty-chat p {
            font-size: 13px;
        }

        /* ================= INPUT AREA ================= */

        .message-form {
            min-height: 76px;

            flex-shrink: 0;

            padding: 14px 7%;

            display: flex;

            align-items: center;

            gap: 12px;

            background: white;

            border-top: 1px solid #e5e7eb;
        }

        .message-input {
            flex: 1;

            height: 48px;

            padding: 0 19px;

            border: 1px solid #dbe1ea;

            border-radius: 25px;

            outline: none;

            background: #f8fafc;

            color: #1f2937;

            font-size: 14px;

            transition: 0.25s ease;
        }

        .message-input:focus {
            background: white;

            border-color: #a3aef1;

            box-shadow:
                0 0 0 4px rgba(163, 174, 241, 0.15);
        }

        .message-input::placeholder {
            color: #9ca3af;
        }

        /* ================= SEND BUTTON ================= */

        .send-btn {
            height: 48px;

            padding: 0 22px;

            border: none;

            border-radius: 25px;

            background: #a3aef1;

            color: white;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 5px 14px rgba(163, 174, 241, 0.3);

            transition: all 0.25s ease;
        }

        .send-btn:hover {
            background: #919ce3;

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px rgba(163, 174, 241, 0.35);
        }

        .send-btn:active {
            transform: scale(0.97);
        }

        /* ================= BACK BUTTON ================= */

        .back-home {
            position: absolute;

            right: 22px;
            top: 18px;

            padding: 9px 15px;

            border-radius: 22px;

            background: rgba(255, 255, 255, 0.16);

            color: white;

            text-decoration: none;

            font-size: 12px;

            border: 1px solid rgba(255, 255, 255, 0.25);

            transition: 0.2s;
        }

        .back-home:hover {
            background: white;

            color: #a3aef1;
        }

        /* ================= MOBILE ================= */

        @media (max-width: 700px) {

            .left {
                width: 220px;
            }

            .sidebar-title h2 {
                font-size: 15px;
            }

            .chatbox {
                padding: 20px 15px;
            }

            .message-form {
                padding: 12px;
            }

            .bubble {
                max-width: 80%;
                font-size: 13px;
            }

            .chat-header {
                padding: 0 15px;
            }

            .chat-user-info h2 {
                font-size: 15px;
            }

            .back-home {
                right: 10px;
                top: 14px;
                padding: 7px 10px;
                font-size: 10px;
            }

            .send-btn {
                padding: 0 16px;
            }
        }

        @media (max-width: 520px) {

            .left {
                width: 75px;
            }

            .sidebar-header {
                justify-content: center;
                padding: 0;
            }

            .sidebar-title {
                display: none;
            }

            .sidebar-icon {
                margin: 0;
            }

            .user-item {
                justify-content: center;
                padding: 10px;
            }

            .user-avatar {
                margin: 0;
            }

            .user-info {
                display: none;
            }

            .chatbox {
                padding: 15px 10px;
            }

            .bubble {
                max-width: 88%;
            }
        }
    </style>

</head>

<body>

    <div class="container">

        <!-- ================================================= -->
        <!-- LEFT USER SIDEBAR -->
        <!-- ================================================= -->

        <div class="left">

            <div class="sidebar-header">

                <div class="sidebar-icon">
                    💬
                </div>

                <div class="sidebar-title">

                    <h2>Messages</h2>

                    <p>Customer Support</p>

                </div>

            </div>


            <div class="user-list">

                <?php

                $users = mysqli_query($conn, "
                SELECT DISTINCT sender
                FROM chat_messages
                WHERE receiver='Admin'
                ORDER BY sender
            ");

                if (mysqli_num_rows($users) == 0) {

                    echo '<div class="no-users">
                        No messages yet.
                      </div>';
                }

                while ($row = mysqli_fetch_assoc($users)) {

                    $username = $row['sender'];

                    $active = '';

                    if (
                        isset($_GET['user']) &&
                        $_GET['user'] == $username
                    ) {

                        $active = 'active';
                    }

                    $firstLetter = strtoupper(substr($username, 0, 1));

                ?>

                    <a
                        href="admin_chatsupport.php?user=<?php echo urlencode($username); ?>"
                        class="user-item <?php echo $active; ?>">

                        <div class="user-avatar">
                            <?php echo htmlspecialchars($firstLetter); ?>
                        </div>

                        <div class="user-info">

                            <strong>
                                <?php echo htmlspecialchars($username); ?>
                            </strong>

                            <span>
                                Customer
                            </span>

                        </div>

                    </a>

                <?php } ?>

            </div>

        </div>


        <!-- ================================================= -->
        <!-- RIGHT CHAT PANEL -->
        <!-- ================================================= -->

        <div class="right">

            <?php

            if (isset($_GET['user'])) {

                $user = mysqli_real_escape_string(
                    $conn,
                    $_GET['user']
                );

                $displayUser = htmlspecialchars($_GET['user']);

            ?>

                <!-- ================= CHAT HEADER ================= -->

                <div class="chat-header">

                    <div class="chat-avatar">
                        👤
                    </div>

                    <div class="chat-user-info">

                        <h2>
                            <?php echo $displayUser; ?>
                        </h2>

                        <p>
                            Customer Support
                        </p>

                    </div>

                    <a
                        href="userdashboard.php"
                        class="back-home">
                        ← Back
                    </a>

                </div>


                <!-- ================= CHAT MESSAGES ================= -->

                <div class="chatbox">

                    <?php

                    $chat = mysqli_query($conn, "
                    SELECT *
                    FROM chat_messages
                    WHERE
                        (sender='$user' AND receiver='Admin')
                        OR
                        (sender='Admin' AND receiver='$user')
                    ORDER BY sent_at ASC
                ");

                    while ($msg = mysqli_fetch_assoc($chat)) {

                        $isAdmin = ($msg['sender'] == 'Admin');

                        $class = $isAdmin
                            ? 'admin'
                            : 'user';

                    ?>

                        <div class="message <?php echo $class; ?>">

                            <div class="bubble">

                                <div class="sender-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $msg['sender']
                                    );
                                    ?>

                                </div>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $msg['message']
                                    )
                                );
                                ?>

                            </div>

                        </div>

                    <?php } ?>

                </div>


                <!-- ================= MESSAGE INPUT ================= -->

                <form
                    method="POST"
                    class="message-form">

                    <input
                        type="hidden"
                        name="user"
                        value="<?php echo htmlspecialchars($user); ?>">

                    <input
                        type="text"
                        name="message"
                        class="message-input"
                        placeholder="Type your reply..."
                        autocomplete="off"
                        required>

                    <button
                        type="submit"
                        name="send"
                        class="send-btn">
                        Send ➤
                    </button>

                </form>


            <?php

            } else {

            ?>

                <!-- ================= NO USER SELECTED ================= -->

                <div class="empty-chat">

                    <div class="empty-icon">
                        💬
                    </div>

                    <h2>
                        Welcome to Chat Support
                    </h2>

                    <p>
                        Select a customer from the left to start chatting.
                    </p>

                </div>

            <?php } ?>

        </div>

    </div>

</body>

</html>