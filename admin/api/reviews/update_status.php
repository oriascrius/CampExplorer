<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    // 檢查是否為 POST 請求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // 獲取並解析請求數據
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['application_id']) || !isset($data['status'])) {
        throw new Exception('Missing required parameters');
    }

    $applicationId = intval($data['application_id']);
    $status = intval($data['status']);

    // 驗證狀態值
    if (!in_array($status, [0, 1, 2])) {
        throw new Exception('Invalid status value');
    }

    global $db;
    
    // 更新狀態
    $stmt = $db->prepare("
        UPDATE camp_applications 
        SET status = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE application_id = ?
    ");
    
    $stmt->execute([$status, $applicationId]);

    // 如果有更新審核記錄的需求，可以在這裡添加
    if ($status !== 0) {
        $stmt = $db->prepare("
            INSERT INTO campsite_reviews 
            (application_id, admin_id, status, reviewed_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $applicationId,
            $_SESSION['admin_id'],
            $status
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => '狀態更新成功'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}