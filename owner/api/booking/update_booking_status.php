<?php
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

try {
    // 檢查登入狀態
    session_start();
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('請先登入');
    }

    // 獲取並驗證輸入
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('未收到任何數據');
    }
    
    // 解析 JSON 數據
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('無效的 JSON 格式');
    }

    // 驗證必要參數
    if (empty($data['booking_id'])) {
        throw new Exception('訂單編號不能為空');
    }
    if (empty($data['status'])) {
        throw new Exception('狀態不能為空');
    }

    // 驗證狀態值
    $allowed_statuses = ['pending', 'confirmed', 'cancelled'];
    if (!in_array($data['status'], $allowed_statuses)) {
        throw new Exception('無效的狀態值');
    }

    $booking_id = intval($data['booking_id']);
    $status = $data['status'];
    $owner_id = $_SESSION['owner_id'];

    // 檢查訂單是否存在且屬於該營主
    $check_sql = "SELECT b.status, b.booking_id
                 FROM bookings b
                 JOIN activity_spot_options aso ON b.option_id = aso.option_id
                 JOIN spot_activities sa ON aso.activity_id = sa.activity_id
                 WHERE b.booking_id = ? AND sa.owner_id = ?";
    
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$booking_id, $owner_id]);
    $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('找不到此訂單或無權限更新');
    }

    // 更新訂單狀態 (移除已取消狀態的限制)
    $update_sql = "UPDATE bookings 
                  SET status = ?, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE booking_id = ? AND booking_id IN (
                      SELECT b.booking_id
                      FROM bookings b
                      JOIN activity_spot_options aso ON b.option_id = aso.option_id
                      JOIN spot_activities sa ON aso.activity_id = sa.activity_id
                      WHERE sa.owner_id = ?
                  )";
    
    $update_stmt = $db->prepare($update_sql);
    $result = $update_stmt->execute([$status, $booking_id, $owner_id]);

    if (!$result) {
        throw new Exception('更新失敗：' . implode(', ', $update_stmt->errorInfo()));
    }

    // 檢查是否有任何行被更新
    if ($update_stmt->rowCount() === 0) {
        throw new Exception('無權更新此訂單或訂單不存在');
    }

    echo json_encode([
        'success' => true,
        'message' => '訂單狀態已更新',
        'data' => [
            'booking_id' => $booking_id,
            'new_status' => $status
        ]
    ]);

} catch (Exception $e) {
    error_log('Update booking status error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}