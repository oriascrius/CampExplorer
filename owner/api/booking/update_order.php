<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../camping_db.php';
global $db;

header('Content-Type: application/json');

try {
    session_start();
    
    // 驗證登入狀態
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('尚未登入，請先登入系統');
    }

    // 獲取 POST 數據
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 驗證輸入數據
    if (!isset($input['order']) || !is_array($input['order'])) {
        throw new Exception('無效的排序數據');
    }

    $owner_id = $_SESSION['owner_id'];
    
    // 記錄操作開始
    error_log('開始更新排序，owner_id: ' . $owner_id);
    error_log('排序數據: ' . print_r($input['order'], true));

    // 開始事務
    $db->beginTransaction();

    try {
        // 驗證所有訂單是否屬於當前商家
        $verify_sql = "SELECT COUNT(*) as count 
                      FROM bookings b
                      JOIN activity_spot_options aso ON b.option_id = aso.option_id
                      JOIN spot_activities sa ON aso.activity_id = sa.activity_id
                      WHERE b.booking_id = :booking_id 
                      AND sa.owner_id = :owner_id";
        
        $verify_stmt = $db->prepare($verify_sql);
        
        foreach ($input['order'] as $booking_id) {
            $verify_stmt->execute([
                'booking_id' => $booking_id,
                'owner_id' => $owner_id
            ]);
            
            if ($verify_stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
                throw new Exception('存在無效的訂單ID: ' . $booking_id);
            }
        }

        // 更新排序
        $update_sql = "UPDATE bookings b
                      JOIN activity_spot_options aso ON b.option_id = aso.option_id
                      JOIN spot_activities sa ON aso.activity_id = sa.activity_id
                      SET b.display_order = :display_order
                      WHERE b.booking_id = :booking_id 
                      AND sa.owner_id = :owner_id";
        
        $update_stmt = $db->prepare($update_sql);

        foreach ($input['order'] as $index => $booking_id) {
            $update_stmt->execute([
                'display_order' => $index + 1,
                'booking_id' => $booking_id,
                'owner_id' => $owner_id
            ]);
        }

        // 提交事務
        $db->commit();
        
        error_log('排序更新成功');

        echo json_encode([
            'success' => true,
            'message' => '排序已更新',
            'updated_count' => count($input['order'])
        ]);

    } catch (Exception $e) {
        // 如果出現錯誤，回滾事務
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('更新排序時發生錯誤: ' . $e->getMessage());
    error_log('錯誤追蹤: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'time' => date('Y-m-d H:i:s'),
            'session_status' => session_status(),
            'session_id' => session_id(),
            'owner_id' => $_SESSION['owner_id'] ?? 'not set'
        ]
    ]);
}