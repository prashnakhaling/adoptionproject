<?php

require_once __DIR__ . '/dataconnection.php';

header('Content-Type: application/json');

$unreadCount = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM chat_messages
    WHERE sender_type = 'user'
      AND is_read = 0
");

if ($result) {
    $row = $result->fetch_assoc();
    $unreadCount = (int)($row['total'] ?? 0);
}

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount
]);
