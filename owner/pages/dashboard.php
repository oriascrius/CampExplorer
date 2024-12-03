<?php
require_once __DIR__ . '/../../camping_db.php';

// 檢查登入狀態
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../owner-login.php");
    exit;
}

// 記錄當前的 session 狀態
error_log('Dashboard Session ID: ' . session_id());
error_log('Dashboard Session Data: ' . print_r($_SESSION, true));

$owner_id = $_SESSION['owner_id'];

// 初始化變數
$pending_stats = [
    'pending_bookings' => 0,
    'today_bookings' => 0
];

try {
    // 獲取待處理事項統計
    $pending_sql = "SELECT 
        COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN DATE(b.booking_date) = CURDATE() THEN 1 END) as today_bookings
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        WHERE aso.application_id IN (SELECT application_id FROM camp_applications WHERE owner_id = :owner_id)";

    $stmt = $db->prepare($pending_sql);
    $stmt->execute([':owner_id' => $owner_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $pending_stats = $result;
    }
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

// 在頁面開始處添加數據初始化
try {
    // 訂單概況統計
    $sql_orders = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN DATE(created_at) = CURRENT_DATE() THEN 1 END) as today_orders
    FROM bookings b
    JOIN activity_spot_options aso ON b.option_id = aso.option_id
    WHERE aso.application_id IN (
        SELECT application_id 
        FROM camp_applications 
        WHERE owner_id = :owner_id
    )";
    
    $stmt = $db->prepare($sql_orders);
    $stmt->bindParam(':owner_id', $_SESSION['owner_id'], PDO::PARAM_INT);
    $stmt->execute();
    $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 營收概況統計
    $sql_revenue = "SELECT 
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURRENT_DATE() THEN total_price ELSE 0 END), 0) as today_revenue,
        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) THEN total_price ELSE 0 END), 0) as monthly_revenue,
        COALESCE(SUM(total_price), 0) as total_revenue,
        COALESCE(((SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) THEN total_price ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
            THEN total_price ELSE 0 END), 0)) - 1) * 100, 0) as growth_rate
    FROM bookings b
    JOIN activity_spot_options aso ON b.option_id = aso.option_id
    WHERE aso.application_id IN (
        SELECT application_id 
        FROM camp_applications 
        WHERE owner_id = :owner_id
    )
    AND b.status != 'cancelled'";

    $stmt = $db->prepare($sql_revenue);
    $stmt->bindParam(':owner_id', $_SESSION['owner_id'], PDO::PARAM_INT);
    $stmt->execute();
    $revenue_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 客戶統計
    $sql_customers = "SELECT 
        COUNT(DISTINCT user_id) as total_customers,
        COUNT(DISTINCT CASE WHEN DATE(created_at) = CURRENT_DATE() THEN user_id END) as new_customers,
        ROUND((COUNT(DISTINCT CASE WHEN status != 'cancelled' THEN user_id END) * 100.0 / 
            NULLIF(COUNT(DISTINCT user_id), 0)), 1) as retention_rate
    FROM bookings b
    JOIN activity_spot_options aso ON b.option_id = aso.option_id
    WHERE aso.application_id IN (
        SELECT application_id 
        FROM camp_applications 
        WHERE owner_id = :owner_id
    )";

    $stmt = $db->prepare($sql_customers);
    $stmt->bindParam(':owner_id', $_SESSION['owner_id'], PDO::PARAM_INT);
    $stmt->execute();
    $customer_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 營位統計
    $sql_spots = "SELECT 
        COUNT(DISTINCT cs.spot_id) as total_spots,
        COUNT(DISTINCT CASE WHEN cs.status = 1 THEN cs.spot_id END) as active_spots,
        ROUND((COUNT(DISTINCT CASE WHEN cs.status = 1 THEN cs.spot_id END) * 100.0 / 
            NULLIF(COUNT(DISTINCT cs.spot_id), 0)), 1) as usage_rate
    FROM camp_spot_applications cs
    JOIN camp_applications ca ON cs.application_id = ca.application_id
    WHERE ca.owner_id = :owner_id";

    $stmt = $db->prepare($sql_spots);
    $stmt->bindParam(':owner_id', $_SESSION['owner_id'], PDO::PARAM_INT);
    $stmt->execute();
    $spot_stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    // 設置預設值
    $order_stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'completed_orders' => 0,
        'today_orders' => 0
    ];
    $revenue_stats = [
        'today_revenue' => 0,
        'monthly_revenue' => 0,
        'total_revenue' => 0,
        'growth_rate' => 0
    ];
    $customer_stats = [
        'total_customers' => 0,
        'new_customers' => 0,
        'retention_rate' => 0
    ];
    $spot_stats = [
        'total_spots' => 0,
        'active_spots' => 0,
        'usage_rate' => 0
    ];
}
?>
<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>

<!-- 在 Font Awesome 之後添加 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<!-- 自定義 CSS -->
<style>
    :root {
        /* 較亮的莫蘭迪色系 */
        --morandiLightGreen: #C5D5C3;   /* 亮灰綠 */
        --morandiLightPink: #E6D7D6;    /* 亮灰粉 */
        --morandiLightBlue: #C3CED7;    /* 亮灰藍 */
        
        /* 原有的莫蘭迪色系保留 */
        --morandiPrimary: #9C9B7A;    
        --morandiSuccess: #A1B0AB;    
        --morandiInfo: #B5C4C9;       
        --morandiWarning: #DCD3D0;    
        --morandiDark: #767B91;       
        
        /* 背景色系 */
        --bgPrimary: rgba(156, 155, 122, 0.1);
        --bgSuccess: rgba(161, 176, 171, 0.1);
        --bgInfo: rgba(181, 196, 201, 0.1);
        --bgWarning: rgba(220, 211, 208, 0.1);
    }

    /* 儀表板容器 */
    .dashboard-container {
        margin-left: 280px;
        padding: 2rem;
        background-color: var(--bg-primary);
        min-height: 100vh;
    }

    /* 統計卡片樣式 */
    .stat-card {
        height: 100%;
        min-height: 180px;
        border: none;
        border-radius: 1rem;
        transition: all 0.3s ease;
        overflow: visible;
        position: relative;
        color: #fff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* 不同類型的統計卡片背景 */
    .stat-card.orders {
        background: linear-gradient(135deg, #A1B5C1, #8A9EAD);
    }

    .stat-card.revenue {
        background: linear-gradient(135deg, #B4C4AE, #98A98F);
    }

    .stat-card.spots {
        background: linear-gradient(135deg, #B1A89F, #968C84);
    }

    .stat-card.customers {
        background: linear-gradient(135deg, #E6D1D0, #C9ABAA);
    }

    /* 統計卡片內容 */
    .stat-content {
        position: relative;
        z-index: 1;
        padding: 1.5rem;
        padding-top: 1rem;
    }

    .stat-content h6 {
        color: rgba(255, 255, 255, 0.8);
        font-weight: 500;
    }

    .stat-content h3 {
        color: #fff;
        font-weight: 600;
        font-size: 2rem;
    }

    .stat-content small {
        color: rgba(255, 255, 255, 0.7);
    }

    .stat-icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 2rem;
        opacity: 0.15;
        color: #fff;
        transition: all 0.3s ease;
        z-index: 2;
    }

    .stat-card:hover .stat-icon {
        transform: translateY(-3px) rotate(10deg);
        opacity: 1;
    }

    /* 圖表卡片 */
    .chart-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        height: 100%;
        padding: 1.5rem;
    }

    /* 表格樣式優化 */
    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .table th {
        background: var(--morandi-blue-100);
        border: none;
        padding: 1rem;
        font-weight: 600;
    }

    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--morandi-blue-100);
    }

    /* 進度條樣式 */
    .progress {
        height: 6px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-bar {
        background: linear-gradient(to right, var(--morandi-blue-300), var(--morandi-blue-400));
    }

    .hover-effect {
        transition: all 0.3s ease;
    }

    .hover-effect:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
    }

    .border-left-primary {
        border-left: 4px solid var(--morandiPrimary);
        background: linear-gradient(to right, var(--bgPrimary), #FFFFFF);
    }

    .border-left-success {
        border-left: 4px solid var(--morandiSuccess);
        background: linear-gradient(to right, var(--bgSuccess), #FFFFFF);
    }

    .border-left-info {
        border-left: 4px solid var(--morandiInfo);
        background: linear-gradient(to right, var(--bgInfo), #FFFFFF);
    }

    .border-left-warning {
        border-left: 4px solid var(--morandiWarning);
        background: linear-gradient(to right, var(--bgWarning), #FFFFFF);
    }

    /* 漸層背景效果 */
    .card {
        background: linear-gradient(to right, rgba(78,115,223,0.05) 0%, rgba(255,255,255,1) 100%);
    }

    /* .chart-container {
        position: relative;
        height: 350px;
        margin: 20px 0;
    } */

    /* 圖表標題樣式 */
    .chart-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #5a5c69;
        margin-bottom: 1rem;
        padding-left: 0.5rem;
        border-left: 4px solid #4e73df;
    }

    /* 空白資料處理 */
    .no-data {
        color: #999;
        font-style: italic;
        font-size: 0.9rem;
    }

    .card-value {
        color: var(--morandiDark);
    }

    /* 統計卡片懸停效果 */
    .stat-card {
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    /* 進度條樣式優化 */
    .progress {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .stat-card .progress-bar {
        background-color: rgba(255, 255, 255, 0.8);
    }

    /* 圖標樣式優化 */
    .stat-icon {
        opacity: 0.15;
        font-size: 4rem;
        transition: all 0.3s ease;
    }

    .stat-card:hover .stat-icon {
        opacity: 0.25;
        transform: scale(1.1);
    }

    /* 添加數據更新動畫 */
    @keyframes numberChange {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .number-animate {
        animation: numberChange 0.5s ease;
    }

    /* 數據區塊樣式優化 */
    .stat-data {
        margin-top: 0.5rem;
    }

    /* 確保數據文字清晰可見 */
    .stat-content h3,
    .stat-content h5,
    .stat-content small {
        position: relative;
        z-index: 3;
    }

    /* 進度條容器調整 */
    .progress-container {
        margin-top: 1rem;
        position: relative;
        z-index: 3;
    }

    /* 今日營運概況區塊樣式優化 */
    .alert-info {
        background: var(--morandiDark);  /* 使用深色背景 */
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        color: white !important;  /* 文字改為白色 */
        padding: 1.5rem;
    }

    .alert-info i {
        color: rgba(255, 255, 255, 0.8);  /* 圖標顏色改為半透明白色 */
    }

    .alert-info .alert-heading {
        color: white !important;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .alert-info small.text-muted {
        color: rgba(255, 255, 255, 0.7) !important;  /* 次要文字改為半透明白色 */
    }

    .alert-info h5 {
        color: white !important;
        font-weight: 500;
    }

    /* 徽章樣式調整 */
    .alert-info .badge {
        padding: 0.5em 1em;
        border-radius: 50rem;
        font-weight: 500;
    }

    .alert-info .badge.bg-warning {
        background-color: rgba(255, 255, 255, 0.2) !important;  /* 半透明白色背景 */
        color: white;
    }

    .alert-info .badge.bg-info {
        background-color: rgba(255, 255, 255, 0.15) !important;  /* 稍微淺一點的半透明白色背景 */
        color: white;
    }

    /* 數字顯示樣式 */
    .alert-info .mb-0 {
        color: white !important;
        font-weight: 600;
    }

    /* 區塊間距調整 */
    .alert-info .row > div {
        margin-bottom: 0.5rem;
    }

    /* hover 效果 */
    .alert-info:hover {
        transform: translateY(-2px);
        transition: transform 0.3s ease;
    }

    /* 進度條樣式優化 */
    .progress {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .stat-card .progress-bar {
        background-color: rgba(255, 255, 255, 0.8);
    }

    /* 卡片內文字間距優化 */
    .stat-content {
        padding: 1.5rem;
    }

    .stat-data {
        margin-top: 1rem;
    }

    /* 確保所有文字清晰可見 */
    .stat-card .text-muted {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    /* 訂單狀態文字顏色 */
    .stat-card .status-confirmed {
        color: var(--morandiLightGreen);
    }

    .stat-card .status-pending {
        color: var(--morandiLightPink);
    }

    .stat-card .status-cancelled {
        color: var(--morandiLightBlue);
    }

    /* 進度條顏色 */
    .stat-card .progress {
        background-color: rgba(255, 255, 255, 0.2);
        height: 4px;
    }

    .stat-card .progress-bar.status-confirmed {
        background-color: var(--morandiLightGreen) !important;
        opacity: 0.9;
    }

    .stat-card .progress-bar.status-pending {
        background-color: var(--morandiLightPink) !important;
        opacity: 0.9;
    }

    .stat-card .progress-bar.status-cancelled {
        background-color: var(--morandiLightBlue) !important;
        opacity: 0.9;
    }

    /* 懸停效果 */
    .stat-card .status-confirmed:hover {
        color: #D4E1D2;
    }

    .stat-card .status-pending:hover {
        color: #F0E4E3;
    }

    .stat-card .status-cancelled:hover {
        color: #D1DAE2;
    }

    /* 訂單狀態文字樣式 */
    .stat-card .status-text {
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .stat-card .status-confirmed {
        color: #9ED5A5;  /* 較深 */
    }

    .stat-card .status-pending {
        color: #FFB5A6;  /* 較深的珊瑚粉 */
    }

    .stat-card .status-cancelled {
        color: #A5C3E5;  /* 較深的天藍 */
    }

    /* 進度條顏色 */
    .stat-card .progress-bar.status-confirmed {
        background-color: #9ED5A5 !important;
    }

    .stat-card .progress-bar.status-pending {
        background-color: #FFB5A6 !important;
    }

    .stat-card .progress-bar.status-cancelled {
        background-color: #A5C3E5 !important;
    }

    /* 新增的分析區域樣式 */
    .analysis-section {
        margin-top: 2rem;
    }

    /* 按鈕組樣式 */
    .btn-group .btn-outline-secondary {
        color: var(--morandiDark);
        border-color: var(--morandiLightBlue);
        font-size: 0.9rem;
        padding: 0.3rem 0.8rem;
    }

    .btn-group .btn-outline-secondary.active {
        background-color: var(--morandiLightBlue);
        color: white;
        border-color: var(--morandiLightBlue);
    }

    /* 統計數據標籤樣式 */
    .stats-label {
        font-size: 0.85rem;
        color: var(--morandiDark);
        margin-bottom: 0.3rem;
    }

    /* 表格內進度條樣式 */
    .table .progress {
        height: 6px;
        width: 100px;
        margin: 0;
        background-color: var(--bgPrimary);
    }

    /* 評分星星樣式 */
    .rating-stars {
        color: var(--morandiWarning);
        font-size: 0.9rem;
    }

    /* 小標籤樣式 */
    .stat-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        background: var(--bgPrimary);
        color: var(--morandiDark);
    }

    /* 圖表提示框自定義樣式 */
    .custom-tooltip {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--morandiLightBlue);
    }

    .loading {
        position: relative;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    /* 圖表卡片樣式 */
    .chart-card {
        background: #fff;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.05);
        height: 100%;
        min-height: 800px; /* 增加最小高度 */
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .chart-header h5 {
        margin: 0;
        color: var(--morandiDark);
        font-weight: 600;
    }

    .chart-filters {
        display: flex;
        gap: 0.5rem;
    }

    .chart-filters .btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        color: var(--morandiDark);
        border-color: var(--morandiDark);
    }

    .chart-filters .btn.active {
        background-color: var(--morandiDark);
        color: #fff;
    }

    .chart-body {
        height: 300px;
        position: relative;
    }

    /* 表格樣式 */
    .table {
        margin-bottom: 0;
    }

    .table th {
        border-top: none;
        color: var(--morandiDark);
        font-weight: 600;
    }

    .table td {
        vertical-align: middle;
        color: #666;
    }

    /* 提訊息樣式 */
    .alert-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem;
        border-radius: 0.5rem;
        color: #fff;
        font-weight: 500;
        z-index: 1000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    }

    .alert-message.show {
        opacity: 1;
        transform: translateX(0);
    }

    .alert-message.error {
        background-color: #dc3545;
    }

    .alert-message.success {
        background-color: var(--morandiSuccess);
    }

    /* 圖表區域樣式 */
    /* .chart-container {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    } */

    /* 營位管理區塊樣式 */
    .spots-management {
        margin-top: 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    /* 營位卡式 */
    .spot-card {
        background: var(--morandiLightBlue);  /* 使用較淡的灰藍色作為背景 */
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .spot-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    }

    /* 營位卡片標題 */
    .spot-card h6 {
        color: var(--morandiDark);
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }

    /* 營位狀態籤 */
    .spot-status {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--morandiDark);
    }

    .status-available {
        background: var(--morandiLightGreen);
    }

    .status-maintenance {
        background: var(--morandiLightPink);
    }

    .status-occupied {
        background: var(--morandiWarning);
    }

    /* 營位資訊樣式 */
    .spot-info {
        margin-top: 1rem;
    }

    .spot-info p {
        color: var(--morandiDark);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* 營位資訊標籤 */
    .spot-info p span:first-child {
        color: rgba(118, 123, 145, 0.8);  /* 使用半透明的 morandiDark */
    }

    /* 價格和營收特殊樣式 */
    .spot-info p.price-info,
    .spot-info p.revenue-info {
        font-weight: 500;
        color: var(--morandiDark);
    }

    /* 維護日期警告樣式 */
    .maintenance-date {
        color: var(--morandiLightPink) !important;
        font-style: italic;
    }

    /* 新增圖標樣式 */
    .spot-icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 2rem;
        opacity: 0.15;
        color: var(--morandiDark);
    }

    /* 更新按鈕樣式 */
    .btn-refresh {
        background: linear-gradient(135deg, var(--morandiLightBlue), var(--morandiInfo));
        border: none;
        color: white;
        padding: 8px 20px;
        transition: all 0.3s ease;
    }

    .btn-refresh:hover {
        background: linear-gradient(135deg, var(--morandiInfo), var(--morandiLightBlue));
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-refresh i {
        transition: transform 0.5s ease;
    }

    .btn-refresh.loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* 數字動畫效果 */
    .animate-number {
        display: inline-block;
        position: relative;
        transition: all 0.3s ease;
    }

    .animate-number.updating {
        animation: numberUpdate 0.5s ease;
    }

    @keyframes numberUpdate {
        0% {
            transform: translateY(0);
            opacity: 1;
        }
        50% {
            transform: translateY(-10px);
            opacity: 0;
        }
        51% {
            transform: translateY(10px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* 數字跳動動畫 */
    @keyframes countAnimation {
        0% { opacity: 0.3; }
        50% { opacity: 1; }
        100% { opacity: 0.3; }
    }

    .counting {
        animation: countAnimation 0.1s ease;
    }

    /* 共用按鈕基本樣式 */
    .custom-period-btn {
        padding: 8px 15px;
        font-size: 14px;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.3s ease;
        margin: 0 4px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* 訂單趨勢分析按鈕樣式 */
    .trend-period-selector .custom-period-btn {
        background-color: #2C3E50;
        color: #ECF0F1;
    }
    
    .trend-period-selector .custom-period-btn:hover {
        background-color: #34495E;
        color: #FFF;
        transform: translateY(-1px);
    }
    
    .trend-period-selector .custom-period-btn.active {
        background-color: #2980B9;
        color: #FFF;
        border-color: #3498DB;
        box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
    }

    /* 熱門營位排行按鈕樣式 */
    .spots-period-selector .custom-period-btn {
        background-color: #2C3E50;
        color: #ECF0F1;
    }
    
    .spots-period-selector .custom-period-btn:hover {
        background-color: #34495E;
        color: #FFF;
        transform: translateY(-1px);
    }
    
    .spots-period-selector .custom-period-btn.active {
        background-color: #16A085;
        color: #FFF;
        border-color: #1ABC9C;
        box-shadow: 0 0 10px rgba(22, 160, 133, 0.3);
    }

    /* 按鈕文字樣式 */
    .btn-text {
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    
    /* 按鈕圖標樣式 */
    .btn-icon {
        margin-right: 6px;
        font-size: 12px;
    }

    /* 圖卡片樣式更新 */
    .chart-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        padding: 1.5rem;
        height: 100%;
        min-height: 700px;  /* 增加最小高度 */
    }

    /* 圖表容器樣式 */
    .order-trend-chart-container {
        width: 100%;
        height: 800px !important;  /* 增加圖表高度 */
        position: relative;
    }

    /* 趨勢按鈕容器樣式 */
    .order-trend-header {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding: 1rem 0;      /* 增加上下內邊距 */
    }

    /* 趨勢按鈕樣式 */
    .custom-period-btn {
        padding: 0.5rem 1rem;
        border: 1px solid var(--morandiPrimary);
        background: transparent;
        color: var(--morandiPrimary);
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .custom-period-btn:hover,
    .custom-period-btn.active {
        background: var(--morandiPrimary);
        color: #fff;
    }

    .custom-period-btn .btn-icon {
        margin-right: 0.5rem;
    }

    /* 莫蘭迪色系變量 */
    :root {
        --morandi-blue: #A8C0D3;      /* 柔和藍 */
        --morandi-sage: #B8C4B8;      /* 灰綠色 */
        --morandi-mint: #B5C7C0;      /* 薄荷綠 */
        --morandi-teal: #A3C5C9;      /* 藍綠色 */
        --morandi-gray: #B8C0C8;      /* 灰藍色 */
        --morandi-sand: #D3C1B1;      /* 沙色 */
        --morandi-rose: #D4B9B9;      /* 玫瑰色 */
        --morandi-purple: #C5B8CC;    /* 紫色 */
    }

    /* 統計卡片基本樣式 */
    .stats-card {
        background: #FFFFFF;
        border-radius: 15px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        padding: 1.5rem;
        transition: all 0.3s ease;
        border: none;
    }

    /* 背景漸層 */
    .bg-morandi-blue-gradient {
        background: linear-gradient(135deg, #A8C0D3 0%, #7A9BB7 100%);
        color: white;
    }

    .bg-morandi-sage-gradient {
        background: linear-gradient(135deg, #B8C4B8 0%, #8FA898 100%);
        color: white;
    }

    .bg-morandi-mint-gradient {
        background: linear-gradient(135deg, #B5C7C0 0%, #89A69B 100%);
        color: white;
    }

    .bg-morandi-rose-gradient {
        background: linear-gradient(135deg, #D4B9B9 0%, #B79292 100%);
        color: white;
    }

    /* 卡片內容樣式 */
    .stats-card .card-title {
        color: rgba(255, 255, 255, 0.95);
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .stats-card .main-number {
        color: white;
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stats-card .stats-label {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.875rem;
    }

    /* 圖標樣式 */
    .icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    /* 分隔線樣式 */
    .stats-card hr {
        border-color: rgba(255, 255, 255, 0.2);
        margin: 1rem 0;
    }

    /* 卡片懸停效果 */
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    /* 管理區塊卡片樣式 */
    .management-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .management-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    /* 更新更亮的莫蘭迪色系 */
    :root {
        --morandi-peach: #FFDFD3;     /* 蜜桃色 */
        --morandi-sage: #E0EEE0;      /* 淺灰綠 */
        --morandi-sky: #D6E9F3;       /* 天空藍 */
        --morandi-lavender: #E6E6FA;  /* 薰衣草 */
        --morandi-mint: #D0F0C0;      /* 薄荷綠 */
        --morandi-cream: #FFF5EE;     /* 奶油色 */
        --morandi-rose: #FFE4E1;      /* 玫瑰粉 */
        --morandi-lilac: #E6E6FA;     /* 丁香紫 */
    }

    /* 更新漸層背景 */
    .bg-morandi-peach-gradient {
        background: linear-gradient(135deg, #FFDFD3 0%, #FFB6A3 100%);
        color: #7B5B52;
    }

    .bg-morandi-sage-gradient {
        background: linear-gradient(135deg, #E0EEE0 0%, #C1D9C1 100%);
        color: #5B715B;
    }

    .bg-morandi-sky-gradient {
        background: linear-gradient(135deg, #D6E9F3 0%, #B0D3E8 100%);
        color: #4A6B8A;
    }

    .bg-morandi-lavender-gradient {
        background: linear-gradient(135deg, #E6E6FA 0%, #C9C9F0 100%);
        color: #5D5D8A;
    }

    /* 更新文字顏色為深色 */
    .stats-card .card-title {
        color: inherit;
        opacity: 0.8;
    }

    .stats-card .main-number {
        color: inherit;
        text-shadow: none;
    }

    .stats-card .stats-label {
        color: inherit;
        opacity: 0.7;
    }

    .icon-circle {
        background: rgba(0, 0, 0, 0.1);
        color: inherit;
    }

    .order-status-container {
        margin: 15px 0;
    }

    .order-status-bar {
        height: 8px;
        width: 100%;
        border-radius: 4px;
        background: #eee;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .order-status-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        font-size: 0.9em;
        color: #666;
    }

    .status-label {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .dot.confirmed {
        background-color: #28a745;
    }

    .dot.pending {
        background-color: #ffc107;
    }

    .dot.cancelled {
        background-color: #dc3545;
    }

    .order-status-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .total-orders, .today-orders {
        text-align: center;
    }

    .total-orders span, .today-orders span {
        display: block;
        font-size: 24px;
        font-weight: bold;
    }

    .order-status-numbers {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .status-number {
        flex: 1;
        text-align: center;
        padding: 10px;
        border-radius: 8px;
    }

    .status-number span {
        display: block;
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .status-number small {
        color: #6c757d;
    }

    /* 狀態顏色 */
    .status-number.confirmed {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .status-number.pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .status-number.cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    /* 訂單狀態樣式 */
    .order-status-numbers {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 15px;
    }

    .status-number {
        flex: 1;
        text-align: center;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .status-number span {
        display: block;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .status-number small {
        font-size: 12px;
        opacity: 0.8;
    }

    /* 狀態顏色 */
    .status-number.confirmed {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.2);
    }

    .status-number.pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .status-number.cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.2);
    }

    /* 懸停效果 */
    .status-number:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* 統計卡片基本樣式 */
    .stat-card {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .stat-header {
        margin-bottom: 15px;
    }

    /* 訂單狀態進度條 */
    .order-status-bar {
        height: 4px;
        width: 100%;
        background: linear-gradient(to right, 
            #28a745 0%, 
            #28a745 var(--confirmed-percent, 33%), 
            #ffc107 var(--confirmed-percent, 33%), 
            #ffc107 var(--pending-percent, 66%), 
            #dc3545 var(--pending-percent, 66%), 
            #dc3545 100%
        );
        border-radius: 2px;
        margin: 15px 0;
    }

    /* 訂單狀態說明文字 */
    .order-status-info {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 8px;
    }

    .status-text {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .status-text span {
        font-weight: 600;
    }

    .status-text.confirmed span {
        color: #28a745;
    }

    .status-text.pending span {
        color: #ffc107;
    }

    .status-text.cancelled span {
        color: #dc3545;
    }

    /* 在現有的 style 標籤中添加 */
    .stats-card {
        height: 100%;
        padding: 1.5rem;
        border-radius: 1rem;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .stats-card .card-title {
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 1rem;
    }

    .stats-card .stat-data h3 {
        font-size: 2rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.5rem;
    }

    .stats-card .stat-data small {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.875rem;
    }

    .stats-card i {
        opacity: 0.8;
    }

    /* 更新營位管理概況卡片樣式 */
    .stats-card {
        height: 100%;
        padding: 1.5rem;
        border-radius: 1rem;
        transition: all 0.3s ease;
    }

    /* 更新卡片標題樣式 */
    .stats-card .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.95) !important; /* 提高不透明度 */
        margin-bottom: 1.2rem;
        letter-spacing: 0.5px;
    }

    /* 更新數字樣式 */
    .stats-card .stat-data h3 {
        font-size: 2.2rem;
        font-weight: 700;
        color: #FFFFFF !important; /* 純白色 */
        margin-bottom: 0.5rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); /* 添加文字陰影 */
    }

    /* 更新說明文字樣式 */
    .stats-card .stat-data small {
        color: rgba(255, 255, 255, 0.9) !important; /* 提高不透明度 */
        font-size: 0.95rem;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* 更新圖標樣式 */
    .stats-card i {
        opacity: 0.9; /* 提高圖標不透明度 */
        color: #FFFFFF;
    }

    /* 更新背景漸層，使文字更容易閱讀 */
    .bg-morandi-blue-gradient {
        background: linear-gradient(135deg, #A8C0D3 0%, #7A9BB7 100%);
    }

    .bg-morandi-sage-gradient {
        background: linear-gradient(135deg, #B8C4B8 0%, #8FA898 100%);
    }

    .bg-morandi-mint-gradient {
        background: linear-gradient(135deg, #B5C7C0 0%, #89A69B 100%);
    }

    .bg-morandi-rose-gradient {
        background: linear-gradient(135deg, #D4B9B9 0%, #B79292 100%);
    }

    /* 添加卡片陰影效果 */
    .stats-card {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
</style>

<div class="dashboard-container">
    <!-- 儀表板頂部區域 -->
    <div class="dashboard-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">營運分析中心</h1>
                <p class="text-muted mb-0">
                    <i class="far fa-clock me-1"></i>
                    最後更<span id="lastUpdateTime"><?= date('Y-m-d H:i') ?></span>
                </p>
            </div>
            <!-- <div class="col-md-6 text-md-end">
                <div class="btn-group">
                    <button class="btn btn-refresh" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-2"></i>更新數據
                    </button>
                    <button class="btn btn-outline-secondary" onclick="exportDashboardData()">
                        <i class="fas fa-download"></i> 匯出報表
                    </button>
                </div>
            </div> -->
        </div>
    </div>

    <!-- 快速概覽區 -->
    <div class="alert alert-info mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-bell fa-2x"></i>
            </div>
            <div class="col">
                <h4 class="alert-heading mb-1">今日營運概況</h4>
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">待確認訂單</small>
                        <h5 class="mb-0">
                            <span class="badge bg-warning"><?= $pending_stats['pending_bookings'] ?></span>
                        </h5>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">今日新訂單</small>
                        <h5 class="mb-0">
                            <span class="badge bg-info"><?= $pending_stats['today_bookings'] ?></span>
                        </h5>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">今日營收</small>
                        <h5 class="mb-0">NT$ <span id="todayRevenueQuick"><?= number_format($stats['revenue']['today'] ?? 0) ?></span></h5>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">營位使用率</small>
                        <h5 class="mb-0"><span id="occupancyRate">0</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 主要統計區 -->
    <div class="row g-4 mb-4" id="statsCardContainer">
        <!-- 訂單統計 -->
        <div class="col-md-3" data-card-type="orders">
            <div class="stat-card orders">
                <div class="stat-content">
                    <div class="stat-header">
                        <h6 class="text-muted mb-2">訂單概況</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0" id="totalOrders">0</h3>
                                <small class="text-muted">訂單數</small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0" id="todayOrders">0</h5>
                                <small class="text-muted">今日新增</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 訂單狀態進度條 -->
                    <div class="order-status-bar mt-3"></div>
                    
                    <!-- 訂單狀態說明 -->
                    <div class="order-status-info">
                        <div class="status-text confirmed">
                            <span id="confirmedOrders">14</span> 已確認
                        </div>
                        <div class="status-text pending">
                            <span id="pendingOrders">10</span> 待處理
                        </div>
                        <div class="status-text cancelled">
                            <span id="cancelledOrders">6</span> 已取消
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 營收統計 -->
        <div class="col-md-3" data-card-type="revenue">
            <div class="stat-card revenue">
                <div class="stat-content p-3">
                    <h6 class="text-muted mb-2">營收概況</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="mb-0" id="totalRevenue">NT$ 0</h4>
                            <small class="text-muted">總營收</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0" id="todayRevenue">NT$ 0</h5>
                            <small class="text-muted">今日營收</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0" id="monthlyRevenue">NT$ 0</h5>
                            <small class="text-muted">本月營收</small>
                        </div>
                        <div class="text-end">
                            <span class="badge" id="growthRate">0%</span>
                            <small class="text-muted d-block">環比增長</small>
                        </div>
                    </div>
                    <i class="fas fa-dollar-sign stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- 客戶統計 -->
        <div class="col-md-3" data-card-type="customers">
            <div class="stat-card customers">
                <div class="stat-content p-3">
                    <h6 class="text-muted mb-2">客戶概況</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-0" id="totalCustomers">0</h3>
                            <small class="text-muted">總客戶數</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0" id="returningCustomers">0</h5>
                            <small class="text-muted">回頭客數</small>
                        </div>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar" id="customerRetentionBar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>戶留存率</small>
                        <small id="retentionRate">0%</small>
                    </div>
                    <i class="fas fa-users stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- 營位計 -->
        <div class="col-md-3" data-card-type="spots">
            <div class="stat-card spots">
                <div class="stat-content p-3">
                    <h6 class="text-muted mb-2">營位概況</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-0" id="totalSpots">0</h3>
                            <small class="text-muted">總營位</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0" id="activeSpots">0</h5>
                            <small class="text-muted">營運中</small>
                        </div>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar" id="spotOccupancyBar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>使用</small>
                        <small id="occupancyRate">0%</small>
                    </div>
                    <i class="fas fa-campground stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 訂單趨勢圖卡 -->
    <div class="row mt-4">
        <!-- 訂單趨勢分析 -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">訂單趨勢分析</h5>
                        <div class="order-trend-header">
                            <!-- 這裡會由 JavaScript 插入按鈕 -->
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="orderTrendChart" style="height: 400px !important;"></canvas>
                </div>
            </div>
        </div>
        
        <!-- 熱門營位排行 -->
        <!-- <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">熱門營位排行</h5>
                        <div class="popular-spots-header">
                           
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="popularSpotsTable"></div>
                </div>
            </div>
        </div> -->
    </div>


    <!-- 營位管理區塊 -->
    <div class="spots-management mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">營位管理概況</h5>
            </div>
            <div class="card-body">
                <div class="row g-4" id="spotsContainer">
                    <!-- 總營位數 -->
                    <div class="col-md-3">
                        <div class="stats-card bg-morandi-blue-gradient">
                            <div class="card-title">
                                <i class="fas fa-campground me-2"></i>總營位數
                            </div>
                            <div class="stat-data">
                                <h3><?= number_format($spot_stats['total_spots']) ?></h3>
                                <small>個營位</small>
                            </div>
                        </div>
                    </div>

                    <!-- 可用營位數 -->
                    <div class="col-md-3">
                        <div class="stats-card bg-morandi-sage-gradient">
                            <div class="card-title">
                                <i class="fas fa-check-circle me-2"></i>可用營位
                            </div>
                            <div class="stat-data">
                                <h3><?= number_format($spot_stats['active_spots']) ?></h3>
                                <small>個營位可預訂</small>
                            </div>
                        </div>
                    </div>

                    <!-- 使用率 -->
                    <div class="col-md-3">
                        <div class="stats-card bg-morandi-mint-gradient">
                            <div class="card-title">
                                <i class="fas fa-chart-line me-2"></i>營位使用率
                            </div>
                            <div class="stat-data">
                                <h3><?= number_format($spot_stats['usage_rate'], 1) ?>%</h3>
                                <small>平均使用率</small>
                            </div>
                        </div>
                    </div>

                    <!-- 本月預訂 -->
                    <div class="col-md-3">
                        <div class="stats-card bg-morandi-rose-gradient">
                            <div class="card-title">
                                <i class="fas fa-calendar-check me-2"></i>本月預訂
                            </div>
                            <div class="stat-data">
                                <h3><?= number_format($order_stats['today_orders']) ?></h3>
                                <small>筆預訂</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>    <!-- 新增的詳細分析區域 -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// 在 script 區塊中設置 axios 默配置
axios.defaults.withCredentials = true;

// 定義趨勢按鈕 HTML
const trendButtons = `
    <div class="trend-period-selector d-flex gap-2">
        <button type="button" class="btn custom-period-btn" data-days="7">
            <i class="fas fa-calendar-week"></i> 近7天
        </button>
        <button type="button" class="btn custom-period-btn" data-days="30">
            <i class="fas fa-calendar-alt"></i> 近30天
        </button>
        <button type="button" class="btn custom-period-btn" data-days="90">
            <i class="fas fa-calendar"></i> 近90天
        </button>
    </div>
`;

// 更新訂單趨勢圖表
async function updateOrderTrend(period = 7) {
    const ctx = document.getElementById('orderTrendChart').getContext('2d');
    try {
        // 顯示載入中狀態
        ctx.canvas.style.opacity = '0.5';
        
        // 添加錯誤處理和超時設置
        const response = await axios.get(`api/dashboard/order-trend.php?period=${period}`, {
            timeout: 5000,
            validateStatus: function (status) {
                return status >= 200 && status < 500; // 只接受狀態碼在此範圍內的響應
            }
        });

        console.log('API Response:', response.data); // 添加日誌

        if (!response.data.success) {
            throw new Error(response.data.message || '無法載入訂單趨勢數據');
        }

        const data = response.data.data;
        
        // 驗證數據格式
        if (!data || !Array.isArray(data.dates) || !Array.isArray(data.orders) || !Array.isArray(data.revenue)) {
            throw new Error('數據格式不正確');
        }

        ctx.canvas.style.opacity = '1';

        // 如果已存在圖表，先銷毀
        if (window.orderTrendChart instanceof Chart) {
            window.orderTrendChart.destroy();
        }

        // 創建新圖表
        window.orderTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.dates,
                datasets: [
                    {
                        label: '訂單數量',
                        data: data.orders,
                        borderColor: '#2980B9',
                        backgroundColor: 'rgba(41, 128, 185, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'orders'
                    },
                    {
                        label: '營收金額',
                        data: data.revenue,
                        borderColor: '#16A085',
                        backgroundColor: 'rgba(22, 160, 133, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'revenue'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 1) {
                                    label += '$' + Number(context.raw).toLocaleString();
                                } else {
                                    label += context.raw;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    orders: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    revenue: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + Number(value).toLocaleString();
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        right: 20,
                        bottom: 20,
                        left: 20
                    }
                }
            }
        });

    } catch (error) {
        console.error('更新訂單趨勢失敗:', error);
        console.log('完整錯誤信息:', error.response?.data || error.message);
        
        // 清除畫布並顯示錯誤信息
        ctx.canvas.style.opacity = '1';
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#dc3545';
        ctx.font = '14px Arial';
        ctx.fillText('載入數據時發生錯誤', ctx.canvas.width / 2, ctx.canvas.height / 2);
    }
}

// 初始化趨勢按鈕
function initializeTrendButtons() {
    const trendContainer = document.querySelector('.order-trend-header');
    if (!trendContainer) {
        console.error('找不到趨勢按鈕容器');
        return;
    }

    trendContainer.innerHTML = trendButtons;
    
    // 添加點擊事件
    trendContainer.querySelectorAll('.custom-period-btn').forEach(button => {
        button.addEventListener('click', function() {
            trendContainer.querySelectorAll('.custom-period-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            updateOrderTrend(parseInt(this.dataset.days));
        });
    });

    // 預設選中第一個按鈕
    trendContainer.querySelector('.custom-period-btn').classList.add('active');
}

// 確保在頁面載入時初始化
document.addEventListener('DOMContentLoaded', () => {
    initializeTrendButtons();
    updateOrderTrend(7); // 預設顯示7天數據
});

document.addEventListener('DOMContentLoaded', function() {
    // 初始化儀表板
    initializeDashboard();
    
    // 每5分鐘更新一次數據
    setInterval(initializeDashboard, 300000);
});

async function initializeDashboard() {
    try {
        const response = await axios.get('api/dashboard/analytics.php');
        if (!response.data.success) {
            throw new Error(response.data.message || '獲取數據失敗');
        }

        // 更新概況數據
        updateOverview(response.data.data.overview);
        
        // 更新最後更新時���
        const lastUpdateElement = document.getElementById('lastUpdateTime');
        if (lastUpdateElement) {
            lastUpdateElement.textContent = new Date().toLocaleString('zh-TW');
        }

    } catch (error) {
        console.error('儀表板���始化失敗:', error);
    }
}

function updateOverview(data) {
    // 檢查數據是否存在
    if (!data) {
        console.error('No data received');
        return;
    }

    // 訂單概況
    const orderElements = {
        'totalOrders': data.orders?.total || 0,
        'todayOrders': data.orders?.today || 0,
        'confirmedOrders': data.orders?.confirmed || 0,
        'pendingOrders': data.orders?.pending || 0,
        'cancelledOrders': data.orders?.cancelled || 0
    };

    // 營收概況
    const revenueElements = {
        'totalRevenue': data.revenue?.total || 0,
        'todayRevenue': data.revenue?.today || 0,
        'monthlyRevenue': data.revenue?.monthly || 0,
        'growthRate': data.revenue?.growth_rate || 0
    };

    // 客戶概況
    const customerElements = {
        'totalCustomers': data.customers?.total || 0,
        'returningCustomers': data.customers?.returning || 0,
        'retentionRate': data.customers?.retention_rate || 0
    };

    // 營位概況
    const spotElements = {
        'totalSpots': data.spots?.total || 0,
        'activeSpots': data.spots?.active || 0,
        'occupancyRate': data.spots?.occupancy_rate || 0
    };

    // 更��訂單概況
    Object.entries(orderElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value.toString();
        }
    });

    // 更新營收概況
    Object.entries(revenueElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            if (id.includes('Revenue')) {
                // 營收金額格式化：移除小數點，加上千分位
                element.textContent = `NT$ ${Math.round(value).toLocaleString('zh-TW')}`;
            } else {
                // 增長率保留一位小數
                element.textContent = `${value.toFixed(1)}%`;
            }
        }
    });

    // 更新客戶概況
    Object.entries(customerElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            if (id.includes('Rate')) {
                element.textContent = `${value.toFixed(1)}%`;
            } else {
                element.textContent = value.toString();
            }
        }
    });

    // 更新營位概況
    Object.entries(spotElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            if (id.includes('Rate')) {
                element.textContent = `${value.toFixed(1)}%`;
            } else {
                element.textContent = value.toString();
            }
        }
    });

    // 計算訂單狀態比例
    const totalOrders = data.orders.total || 0;
    const confirmedOrders = data.orders.confirmed || 0;
    const pendingOrders = data.orders.pending || 0;
    const cancelledOrders = data.orders.cancelled || 0;

    // 計算百分比
    const confirmedPercent = totalOrders ? (confirmedOrders / totalOrders * 100) : 0;
    const pendingPercent = totalOrders ? (pendingOrders / totalOrders * 100) : 0;
    const cancelledPercent = totalOrders ? (cancelledOrders / totalOrders * 100) : 0;

    // 更新進度條
    const progressBar = document.querySelector('.order-status-bar');
    if (progressBar) {
        progressBar.style.background = `linear-gradient(to right, 
            #28a745 0%, 
            #28a745 ${confirmedPercent}%, 
            #ffc107 ${confirmedPercent}%, 
            #ffc107 ${confirmedPercent + pendingPercent}%, 
            #dc3545 ${confirmedPercent + pendingPercent}%, 
            #dc3545 100%
        )`;
    }

    // 更新比例標籤
    const statusLabels = document.querySelector('.order-status-labels');
    if (statusLabels) {
        statusLabels.innerHTML = `
            <div class="status-label">
                <span class="dot confirmed"></span>
                已確認 ${confirmedPercent.toFixed(1)}%
            </div>
            <div class="status-label">
                <span class="dot pending"></span>
                待處理 ${pendingPercent.toFixed(1)}%
            </div>
            <div class="status-label">
                <span class="dot cancelled"></span>
                已取消 ${cancelledPercent.toFixed(1)}%
            </div>
        `;
    }
}

function updateOrderStatusBar(data) {
    const totalOrders = data.orders.total || 0;
    if (totalOrders === 0) return;

    const confirmedPercent = (data.orders.confirmed / totalOrders * 100);
    const pendingPercent = confirmedPercent + (data.orders.pending / totalOrders * 100);

    const statusBar = document.querySelector('.order-status-bar');
    if (statusBar) {
        statusBar.style.setProperty('--confirmed-percent', `${confirmedPercent}%`);
        statusBar.style.setProperty('--pending-percent', `${pendingPercent}%`);
    }
}

// 初始化拖放功能
function initializeDraggableCards() {
    const container = document.getElementById('statsCardContainer');
    if (!container) return;

    // 初始化 Sortable
    new Sortable(container, {
        animation: 150,
        draggable: '.col-md-3',
        handle: '.stat-card', // 使用卡片本身作為拖動把手
        ghostClass: 'sortable-ghost', // 拖動時的樣式
        chosenClass: 'sortable-chosen', // 被選中時的樣式
        dragClass: 'sortable-drag', // 拖動中的樣式
        
        // 保存順序到 localStorage
        store: {
            set: function(sortable) {
                const order = sortable.toArray();
                localStorage.setItem('statsCardOrder', JSON.stringify(order));
            },
            get: function() {
                const order = localStorage.getItem('statsCardOrder');
                return order ? JSON.parse(order) : ['orders', 'revenue', 'customers', 'spots'];
            }
        },

        onEnd: function(evt) {
            // 可以在這裡添加拖放完成後的回調
            console.log('Card order updated');
        }
    });
}

// 在頁面載入時初始化
document.addEventListener('DOMContentLoaded', () => {
    initializeDraggableCards();
    initializeDashboard();
});
</script>
