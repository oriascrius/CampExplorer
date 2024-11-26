<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('未提供申請 ID');
    }

    $application_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$application_id) {
        throw new Exception('無效的申請 ID');
    }

    $conn = connectDB();
    
    // 查詢營地申請和審核資料
    $sql = "SELECT 
                ca.*,
                COALESCE(cr.status, 0) as review_status,
                COALESCE(cr.comment, '') as comment,
                COALESCE(cr.reviewed_at, '') as reviewed_at,
                COALESCE(au.username, '') as reviewer_name
            FROM camp_applications ca
            LEFT JOIN campsite_reviews cr ON ca.application_id = cr.campsite_id
            LEFT JOIN admin_users au ON cr.admin_id = au.admin_id
            WHERE ca.application_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$application_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        throw new Exception('找不到此申請記錄');
    }
    
    // 格式化日期
    $review['created_at'] = date('Y-m-d H:i:s', strtotime($review['created_at']));
    if ($review['reviewed_at']) {
        $review['reviewed_at'] = date('Y-m-d H:i:s', strtotime($review['reviewed_at']));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $review
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤'
    ]);
    error_log($e->getMessage());
}