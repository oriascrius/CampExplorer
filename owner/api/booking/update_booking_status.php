<?php
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    // 檢查登入狀態
    session_start();
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('請先登入');
    }

    // 檢查必要參數
    $booking_id = $_POST['booking_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$booking_id || !$status) {
        throw new Exception('缺少必要參數');
    }

    // 檢查狀態是否有效
    $valid_statuses = ['confirmed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('無效的狀態值');
    }

    // 更新訂單狀態
    $sql = "UPDATE bookings 
            SET status = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE booking_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $booking_id]);

    echo json_encode([
        'success' => true,
        'message' => '訂單狀態已更新'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}