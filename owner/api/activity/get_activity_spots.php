<?php
// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    // 檢查登入狀態
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    // 檢查活動ID
    if (!isset($_GET['activity_id'])) {
        throw new Exception('缺少活動ID');
    }

    $activityId = intval($_GET['activity_id']);
    if ($activityId <= 0) {
        throw new Exception('無效的活動ID');
    }

    require_once __DIR__ . '/../../../camping_db.php';

    // 檢查資料庫連線
    if (!$db) {
        throw new Exception('資料庫連線失敗');
    }

    // 1. 先獲取活動基本資料
    $activityQuery = "
        SELECT 
            sa.activity_id,
            sa.activity_name,
            sa.title,
            sa.subtitle,
            sa.description,
            sa.notice,
            sa.start_date,
            sa.end_date,
            sa.is_active,
            sa.main_image,
            ca.name as camp_name
        FROM spot_activities sa
        JOIN camp_applications ca ON sa.application_id = ca.application_id
        WHERE sa.activity_id = :activity_id 
        AND sa.owner_id = :owner_id";

    $stmt = $db->prepare($activityQuery);
    $stmt->execute([
        'activity_id' => $activityId,
        'owner_id' => $_SESSION['owner_id']
    ]);
    
    $activityData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 記錄活動資料
    error_log('Activity Data: ' . print_r($activityData, true));

    if (!$activityData) {
        throw new Exception('找不到該活動或無權限查看');
    }

    // 添加活動狀態
    $activityData['activity_status'] = '';
    $today = date('Y-m-d');
    $activityData['activity_status'] = '下架中';  // 預設狀態

    if ($activityData['is_active'] == 1) {
        if ($activityData['start_date'] > $today) {
            $activityData['activity_status'] = '即將開始';
        } elseif ($activityData['end_date'] < $today) {
            $activityData['activity_status'] = '已結束';
        } else {
            $activityData['activity_status'] = '進行中';
        }
    }

    // 2. 獲取營位資料
    $spotsQuery = "
        SELECT 
            aso.option_id,
            aso.price,
            aso.max_quantity,
            csa.name as spot_name,
            0 as booked_quantity,
            aso.max_quantity as remaining_quantity
        FROM activity_spot_options aso
        JOIN camp_spot_applications csa ON aso.spot_id = csa.spot_id
        WHERE aso.activity_id = :activity_id
        ORDER BY aso.sort_order";

    $stmt = $db->prepare($spotsQuery);
    $stmt->execute(['activity_id' => $activityId]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 記錄營位資料
    error_log('Spots Data: ' . print_r($spots, true));

    // 3. 計算統計資料
    $stats = [
        'total_spots' => count($spots),
        'min_price' => !empty($spots) ? min(array_column($spots, 'price')) : 0,
        'max_price' => !empty($spots) ? max(array_column($spots, 'price')) : 0,
        'total_quantity' => array_sum(array_column($spots, 'max_quantity')),
        'total_booked' => 0 // 暫時設為 0
    ];

    // 記錄統計資料
    error_log('Stats Data: ' . print_r($stats, true));

    // 回傳資料
    echo json_encode([
        'success' => true,
        'data' => [
            'activity' => $activityData,
            'spots' => $spots,
            'stats' => $stats
        ]
    ]);

} catch (Exception $e) {
    error_log("Get Activity Spots Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}