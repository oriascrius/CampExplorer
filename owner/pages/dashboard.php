<?php
require_once __DIR__ . '/../../camping_db.php';

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../../owner-login.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];

try {
    // 初始化變量，設置預設值
    $today_orders = 0;
    $month_orders = 0;
    $pending_orders = 0;
    $spots_count = 0;
    $camp_info = [
        'operation_status' => 0,
        'status' => null,
        'created_at' => null,
        'application_id' => null
    ];

    // 查詢營地申請狀態
    $camp_sql = "SELECT 
        application_id,
        status,
        created_at,
        operation_status 
    FROM camp_applications 
    WHERE owner_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1";
    $camp_stmt = $db->prepare($camp_sql);
    $camp_stmt->execute([$owner_id]);
    $camp_result = $camp_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($camp_result) {
        $camp_info = $camp_result;
    }

    // 查詢營位數量
    $spots_sql = "SELECT COUNT(*) as spot_count 
    FROM camp_spot_applications csa 
    JOIN camp_applications ca ON csa.application_id = ca.application_id 
    WHERE ca.owner_id = ?";
    $spots_stmt = $db->prepare($spots_sql);
    $spots_stmt->execute([$owner_id]);
    $spots_count = $spots_stmt->fetch(PDO::FETCH_ASSOC)['spot_count'];

    // 查詢訂單統計
    $today = date('Y-m-d');
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');

    // 今日訂單
    $today_orders_sql = "SELECT COUNT(*) as count FROM bookings 
    WHERE owner_id = ? AND DATE(created_at) = ?";
    $today_stmt = $db->prepare($today_orders_sql);
    $today_stmt->execute([$owner_id, $today]);
    $today_orders = $today_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 本月訂單
    $month_orders_sql = "SELECT COUNT(*) as count FROM bookings 
    WHERE owner_id = ? AND created_at BETWEEN ? AND ?";
    $month_stmt = $db->prepare($month_orders_sql);
    $month_stmt->execute([$owner_id, $month_start, $month_end]);
    $month_orders = $month_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 待處理訂單
    $pending_orders_sql = "SELECT COUNT(*) as count FROM bookings 
    WHERE owner_id = ? AND status = 0";
    $pending_stmt = $db->prepare($pending_orders_sql);
    $pending_stmt->execute([$owner_id]);
    $pending_orders = $pending_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet">
<style>
    /* 基礎變數定義 */
    :root {
        --morandi-blue-dark: #546E7A;
        --morandi-blue: #78909C;
        --morandi-blue-light: #B0BEC5;
        --morandi-gray-blue: #CFD8DC;
        --morandi-light: #ECEFF1;
        --sidebar-width: 250px;
        /* 與側邊欄寬度一致 */
    }

    /* 主要內容區域調整 */
    .dashboard-container {
        padding: 2rem;
        margin-left: var(--sidebar-width);
        /* 配合側邊欄寬度 */
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    /* 歡迎標題 */
    .welcome-title {
        color: var(--morandi-blue-dark);
        font-size: 1.8rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--morandi-gray-blue);
    }

    /* 卡片基礎樣式 */
    .dashboard-card {
        background: white;
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(176, 190, 197, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        /* 確保同一行的卡片高度一致 */
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(176, 190, 197, 0.3);
    }

    /* 響應式調整 */
    @media (max-width: 768px) {
        .dashboard-container {
            margin-left: 0;
            padding: 1rem;
        }

        .welcome-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .order-stats {
            margin-bottom: 1rem;
        }
    }

    /* 其他樣式保持不變 */
    .card-title {
        color: var(--morandi-blue-dark);
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-title i {
        color: var(--morandi-blue);
        font-size: 1.4rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .badge-warning {
        background-color: #FFE0B2;
        color: #F57C00;
    }

    .badge-success {
        background-color: #C8E6C9;
        color: #388E3C;
    }

    .badge-danger {
        background-color: #FFCDD2;
        color: #D32F2F;
    }

    .stats-value {
        font-size: 2rem;
        font-weight: 600;
        color: var(--morandi-blue-dark);
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: var(--morandi-blue);
        font-size: 0.9rem;
    }

    .order-stats {
        text-align: center;
        padding: 1.5rem;
        background: var(--morandi-light);
        border-radius: 10px;
        transition: transform 0.3s ease;
    }

    .order-stats:hover {
        transform: translateY(-3px);
    }

    .text-muted {
        color: var(--morandi-blue) !important;
    }
</style>

<!-- 包裝整個儀表板內容 -->
<div class="dashboard-container">
    <h2 class="welcome-title">
        <i class="bi bi-house-heart me-2"></i>
        歡迎回來，<?= htmlspecialchars($_SESSION['owner_name']) ?>
    </h2>

    <div class="row g-4"> <!-- 使用 g-4 增加卡片間距 -->
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-earmark-text"></i>營地申請狀態
                    </h5>
                    <div class="mt-4">
                        <p class="mb-3">
                            目前狀態：
                            <span class="status-badge <?=
                                                        $camp_info['status'] == 0 ? 'badge-warning' : ($camp_info['status'] == 1 ? 'badge-success' : 'badge-danger')
                                                        ?>">
                                <?=
                                $camp_info['status'] == 0 ? '審核中' : ($camp_info['status'] == 1 ? '已通過' : '已退回')
                                ?>
                            </span>
                        </p>
                        <p class="text-muted">
                            <i class="bi bi-clock me-2"></i>提交時間：
                            <?= date('Y-m-d H:i', strtotime($camp_info['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-geo-alt"></i>營地資訊
                    </h5>
                    <div class="mt-4">
                        <p class="mb-3">
                            營位數量：<span class="stats-value"><?= $spots_count ?></span>
                        </p>
                        <p>
                            上架狀態：
                            <span class="status-badge <?=
                                                        $camp_info['operation_status'] == 1 ? 'badge-success' : 'badge-warning'
                                                        ?>">
                                <?= $camp_info['operation_status'] == 1 ? '營業中' : '未營業' ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-receipt"></i>訂單概況
                    </h5>
                    <div class="row g-4"> <!-- 使用 g-4 增加訂單統計卡片間距 -->
                        <div class="col-md-4">
                            <div class="order-stats">
                                <div class="stats-value"><?= $today_orders ?></div>
                                <div class="stats-label">今日訂單</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="order-stats">
                                <div class="stats-value"><?= $month_orders ?></div>
                                <div class="stats-label">本月訂單</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="order-stats">
                                <div class="stats-value"><?= $pending_orders ?></div>
                                <div class="stats-label">待處理訂單</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 可選：添加頁腳間距 -->
<div class="mb-4"></div>