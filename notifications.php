<?php
require_once 'config.php';
requireLogin();

$userId = getUserId();

// Tek bildirimi okundu işaretle
if (isset($_GET['mark_read'])) {
    $notificationId = $_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);

    echo json_encode(['success' => true]);
    exit;
}

// Tümünü okundu işaretle
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);

    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

redirect('index.php');
