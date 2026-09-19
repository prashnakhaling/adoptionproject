<?php
session_start();
include 'dataconnection.php';

$user = $_SESSION['username'] ?? '';

if ($user == '') {
    header("Location: login.php");
    exit();
}

/* ================= SEND MESSAGE ================= */
if (isset($_POST['message']) && !empty(trim($_POST['message']))) {

    $message = mysqli_real_escape_string($conn, $_POST['message']);

    mysqli_query($conn, "
        INSERT INTO chat_messages(sender, receiver, message)
        VALUES('$user', 'Admin', '$message')
    ");

    header("Location: user_chatsupport.php");
    exit();
}

/* ================= DELETE MESSAGE ================= */
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    mysqli_query($conn, "
        DELETE FROM chat_messages 
        WHERE id=$id AND sender='$user'
    ");

    header("Location: user_chatsupport.php");
    exit();
}

/* ================= LOAD EDIT ================= */
$editData = null;

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $res = mysqli_query($conn, "
        SELECT * FROM chat_messages 
        WHERE id=$id AND sender='$user'
    ");

    $editData = mysqli_fetch_assoc($res);
}

/* ================= UPDATE MESSAGE ================= */
if (isset($_POST['edit_id']) && isset($_POST['edit_message'])) {

    $id = (int) $_POST['edit_id'];
    $msg = mysqli_real_escape_string($conn, $_POST['edit_message']);

    mysqli_query($conn, "
        UPDATE chat_messages 
        SET message='$msg'
        WHERE id=$id AND sender='$user'
    ");

    header("Location: user_chatsupport.php");
    exit();
}

/* ================= FETCH CHAT ================= */
$result = mysqli_query($conn, "
    SELECT *
    FROM chat_messages
    WHERE
        (sender='$user' AND receiver='Admin')
        OR
        (sender='Admin' AND receiver='$user')
    ORDER BY sent_at ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chat With Admin</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                linear-gradient(135deg, #eef4ff, #f8faff);
            min-height: 100vh;
            min-width: 100vh;
            padding: 0%;
            margin: 0%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #1f2937;
        }

        /* ================= MAIN CONTAINER ================= */

        .chat-container {
            width: 100%;
            max-width: 100vhpx;
            height: 100vh;
            min-height: 600px;

            background: rgba(255, 255, 255, 0.95);



            box-shadow:
                0 20px 60px rgba(31, 41, 55, 0.15);

            display: flex;
            flex-direction: column;

            overflow: hidden;

            border: 1px solid rgba(255, 255, 255, 0.8);

            position: relative;
        }

        /* ================= HEADER ================= */

        .chat-header {
            height: 82px;

            display: flex;
            align-items: center;

            padding: 0 28px;

            background: linear-gradient(135deg,
                    #a3aef1,
                    #a3aef1);

            color: white;

            position: relative;
        }

        .admin-avatar {
            width: 48px;
            height: 48px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.2);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 23px;

            margin-right: 14px;

            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .header-info h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .header-info p {
            font-size: 12px;
            opacity: 0.85;
        }

        /* ================= BACK BUTTON ================= */

        .back-home {
            position: absolute;

            right: 22px;
            top: 50%;

            transform: translateY(-50%);

            background: rgba(255, 255, 255, 0.16);

            color: white;

            padding: 10px 16px;

            border-radius: 30px;

            text-decoration: none;

            font-size: 13px;

            border: 1px solid rgba(255, 255, 255, 0.25);

            transition: all 0.25s ease;

            backdrop-filter: blur(8px);
        }

        .back-home:hover {
            background: white;
            color: #a3aef1;
            transform: translateY(-50%) translateY(-2px);
        }

        /* ================= CHAT BOX ================= */

        .chat-box {
            flex: 1;

            overflow-y: auto;

            padding: 28px 35px;

            background:
                radial-gradient(circle at top left,
                    rgba(79, 70, 229, 0.04),
                    transparent 30%),
                #f8fafc;

            scroll-behavior: smooth;
        }

        /* Scrollbar */

        .chat-box::-webkit-scrollbar {
            width: 7px;
        }

        .chat-box::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 20px;
        }

        .chat-box::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ================= MESSAGE ================= */

        .message-box {
            display: flex;

            position: relative;

            margin-bottom: 18px;

            animation: messageAppear 0.25s ease;
        }

        @keyframes messageAppear {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* User message */

        .message-box.user {
            justify-content: flex-end;
        }

        /* Admin message */

        .message-box.admin {
            justify-content: flex-start;
        }

        /* ================= MESSAGE BUBBLE ================= */

        .msg-text {
            position: relative;

            max-width: 65%;

            padding: 12px 18px;

            border-radius: 18px;

            font-size: 14px;

            line-height: 1.5;

            word-wrap: break-word;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.06);
        }

        .msg-text strong {
            display: block;

            font-size: 11px;

            margin-bottom: 4px;

            opacity: 0.75;

            font-weight: 600;
        }

        /* USER BUBBLE */

        .user .msg-text {
            background: linear-gradient(135deg,
                    #a3aef1,
                    #a3aef1);

            color: white;

            border-bottom-right-radius: 5px;
        }

        .user .msg-text strong {
            color: rgba(255, 255, 255, 0.8);
        }

        /* ADMIN BUBBLE */

        .admin .msg-text {
            background: white;

            color: #1f2937;

            border: 1px solid #e5e7eb;

            border-bottom-left-radius: 5px;
        }

        .admin .msg-text strong {
            color: #a3aef1;
        }

        /* ================= THREE DOT MENU ================= */

        .menu-container {
            position: absolute;

            top: 8px;
            right: 8px;

            z-index: 20;
        }

        .dots {
            width: 27px;
            height: 27px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;

            cursor: pointer;

            color: rgba(255, 255, 255, 0.8);

            transition: 0.2s;
        }

        .dots:hover {
            background: rgba(255, 255, 255, 0.18);
            color: white;
        }

        /* Dropdown */

        .dropdown {
            display: none;

            position: absolute;

            right: 0;
            top: 30px;

            width: 125px;

            background: white;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 12px 30px rgba(0, 0, 0, 0.15);

            border: 1px solid #e5e7eb;
        }

        .menu-container:hover .dropdown {
            display: block;
        }

        .dropdown a {
            display: block;

            padding: 11px 14px;

            text-decoration: none;

            color: #374151;

            font-size: 13px;

            transition: 0.2s;
        }

        .dropdown a:hover {
            background: #f3f4f6;
        }

        .dropdown .delete {
            color: #ef4444;
        }

        .dropdown .delete:hover {
            background: #fef2f2;
        }

        /* ================= INPUT AREA ================= */

        form {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 16px 22px;

            background: white;

            border-top: 1px solid #e5e7eb;
        }

        input[type=text] {
            flex: 1;

            height: 48px;

            padding: 0 18px;

            border-radius: 25px;

            border: 1px solid #dbe1ea;

            background: #f8fafc;

            outline: none;

            font-size: 14px;

            color: #1f2937;

            transition: all 0.25s ease;
        }

        input[type=text]:focus {
            background: white;

            border-color: #a3aef1;

            box-shadow:
                0 0 0 4px rgba(99, 102, 241, 0.10);
        }

        input[type=text]::placeholder {
            color: #9ca3af;
        }

        /* ================= SEND BUTTON ================= */

        button {
            height: 48px;

            padding: 0 22px;

            border: none;

            border-radius: 25px;

            background: linear-gradient(135deg,
                    #a3aef1,
                    #a3aef1);

            color: white;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 6px 15px rgba(37, 99, 235, 0.25);

            transition: all 0.25s ease;
        }

        button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 9px 20px rgba(37, 99, 235, 0.3);
        }

        button:active {
            transform: scale(0.97);
        }

        /* ================= CANCEL BUTTON ================= */

        .cancel-btn {
            height: 48px;

            display: flex;

            align-items: center;

            padding: 0 18px;

            border-radius: 25px;

            background: #f1f5f9;

            color: #475569;

            text-decoration: none;

            font-size: 13px;

            transition: 0.2s;
        }

        .cancel-btn:hover {
            background: #e2e8f0;
        }

        /* ================= EMPTY CHAT ================= */

        .empty-chat {
            height: 100%;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            color: #94a3b8;

            text-align: center;
        }

        .empty-chat .icon {
            width: 70px;
            height: 70px;

            border-radius: 50%;

            background: #eef2ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;

            margin-bottom: 15px;
        }

        .empty-chat h3 {
            color: #475569;
            margin-bottom: 5px;
        }

        .empty-chat p {
            font-size: 13px;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 700px) {

            body {
                align-items: stretch;
            }

            .chat-container {
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                border-radius: 0;
            }

            .chat-header {
                padding: 0 18px;
            }

            .back-home {
                right: 12px;
                padding: 8px 11px;
                font-size: 11px;
            }

            .chat-box {
                padding: 20px 15px;
            }

            .msg-text {
                max-width: 80%;
                font-size: 13px;
            }

            form {
                padding: 12px;
            }

            input[type=text] {
                height: 44px;
            }

            button {
                height: 44px;
                padding: 0 16px;
            }
        }
    </style>

</head>

<body>

    <div class="chat-container">

        <!-- ================= HEADER ================= -->

        <div class="chat-header">

            <div class="admin-avatar">
                👨‍💼
            </div>

            <div class="header-info">
                <h2>Chat With Admin</h2>
                <p>Usually replies within a few minutes</p>
            </div>

            <a href="userdashboard.php" class="back-home">
                ← Back to Home
            </a>

        </div>


        <!-- ================= CHAT ================= -->

        <div class="chat-box">

            <?php if (mysqli_num_rows($result) == 0) { ?>

                <div class="empty-chat">

                    <div class="icon">
                        💬
                    </div>

                    <h3>Start a conversation</h3>

                    <p>
                        Send a message to the admin for help or support.
                    </p>

                </div>

            <?php } ?>


            <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                <div class="message-box <?php echo ($row['sender'] == $user) ? 'user' : 'admin'; ?>">

                    <div class="msg-text">

                        <strong>
                            <?php echo htmlspecialchars($row['sender']); ?>
                        </strong>

                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>

                    </div>


                    <?php if ($row['sender'] == $user) { ?>

                        <div class="menu-container">

                            <span class="dots">
                                ⋮
                            </span>

                            <div class="dropdown">

                                <a href="?edit=<?php echo $row['id']; ?>">
                                    ✏️ Edit
                                </a>

                                <a href="?delete=<?php echo $row['id']; ?>"
                                    class="delete"
                                    onclick="return confirm('Delete this message?');">

                                    🗑 Delete

                                </a>

                            </div>

                        </div>

                    <?php } ?>

                </div>

            <?php } ?>

        </div>


        <!-- ================= INPUT ================= -->

        <form method="POST">

            <?php if ($editData) { ?>

                <input
                    type="hidden"
                    name="edit_id"
                    value="<?php echo $editData['id']; ?>">

                <input
                    type="text"
                    name="edit_message"
                    value="<?php echo htmlspecialchars($editData['message']); ?>"
                    required
                    autofocus>

                <button type="submit">
                    Update
                </button>

                <a href="user_chatsupport.php" class="cancel-btn">
                    Cancel
                </a>

            <?php } else { ?>

                <input
                    type="text"
                    name="message"
                    placeholder="Type a message..."
                    autocomplete="off"
                    required>

                <button type="submit">
                    Send ➤
                </button>

            <?php } ?>

        </form>

    </div>

</body>

</html>