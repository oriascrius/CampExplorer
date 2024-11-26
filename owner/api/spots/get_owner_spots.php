<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    require_once __DIR__ . '/../../../camping_db.php';

    global $db;
    $sql = "SELECT 
        csa.spot_id,
        csa.name AS spot_name,
        csa.capacity,
        csa.price,
        csa.status,
        ca.name AS camp_name,
        ca.status AS application_status,
        ca.operation_status,
        (SELECT csi.image_path 
         FROM camp_spot_images csi 
         WHERE csi.spot_id = csa.spot_id 
         ORDER BY csi.sort_order ASC 
         LIMIT 1) as image_path
        FROM camp_spot_applications csa
        JOIN camp_applications ca ON csa.application_id = ca.application_id
        WHERE ca.owner_id = :owner_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([':owner_id' => $_SESSION['owner_id']]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_spots = array_map(function($spot) {
        // 處理圖片路徑
        if (!empty($spot['image_path'])) {
            $spot['image_path'] = '/CampExplorer/uploads/spots/' . $spot['image_path'];
        }
        
        // 格式化價格
        $spot['price_formatted'] = 'NT$ ' . number_format($spot['price']);
        
        // 設定營位狀態（啟用/停用）
        $spot['status_text'] = $spot['status'] == 1 ? '使用中' : '已停用';
        $spot['status_class'] = $spot['status'] == 1 ? 'success' : 'secondary';
        
        // 添加 active_status 相關資訊
        $spot['active_status_text'] = $spot['status'] == 1 ? '使用中' : '已停用';
        $spot['active_status_class'] = $spot['status'] == 1 ? 'success' : 'secondary';
        $spot['is_active'] = $spot['status'] == 1;
        
        // 設定是否可以編輯（只有審核通過的才能編輯）
        $spot['can_edit'] = $spot['application_status'] == 1;
        
        // 設定申請狀態
        switch($spot['application_status']) {
            case 0:
                $spot['application_status_text'] = '審核中';
                $spot['application_status_class'] = 'warning';
                break;
            case 1:
                $spot['application_status_text'] = '已通過';
                $spot['application_status_class'] = 'success';
                break;
            case 2:
                $spot['application_status_text'] = '已退回';
                $spot['application_status_class'] = 'danger';
                break;
            default:
                $spot['application_status_text'] = '未知';
                $spot['application_status_class'] = 'secondary';
        }
        
        return $spot;
    }, $spots);

    echo json_encode([
        'success' => true,
        'spots' => $formatted_spots
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
