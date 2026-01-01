<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$userId = getUserId();

// Son 20 bildirimi getir
$stmt = $pdo->prepare("SELECT n.*, t.ticket_number, t.subject
                       FROM notifications n
                       LEFT JOIN tickets t ON n.ticket_id = t.id
                       WHERE n.user_id = ?
                       ORDER BY n.created_at DESC
                       LIMIT 20");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$result = [];
foreach ($notifications as $n) {
    $result[] = [
        'id' => $n['id'],
        'ticket_id' => $n['ticket_id'],
        'ticket_number' => $n['ticket_number'],
        'type' => $n['type'],
        'message' => $n['message'],
        'is_read' => $n['is_read'],
        'time_ago' => timeAgo($n['created_at']),
        'created_at' => $n['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'notifications' => $result,
    'unread_count' => getUnreadNotificationCount($userId)
]);
