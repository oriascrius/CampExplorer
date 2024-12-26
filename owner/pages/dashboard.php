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

    /* 提示訊息樣式 */
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

    /* 營位卡基本樣式 */
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

    /* 營位狀態標籤 */
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
            <div class="col-md-6 text-md-end">
                <div class="btn-group">
                    <button class="btn btn-refresh" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-2"></i>更新數據
                    </button>
                    <button class="btn btn-outline-secondary" onclick="exportDashboardData()">
                        <i class="fas fa-download"></i> 匯出報表
                    </button>
                </div>
            </div>
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
                        <h5 class="mb-0"><span id="occupancyRate">0</span>%</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 主要統計區 -->
    <div class="row g-4 mb-4">
        <!-- 訂單統計 -->
        <div class="col-md-3">
            <div class="stat-card orders">
                <i class="fas fa-shopping-cart stat-icon"></i>
                <div class="stat-content">
                    <h6 class="text-muted mb-2">單概況</h6>
                    <div class="stat-data">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h3 class="mb-0" id="totalBookings">0</h3>
                                <small class="text-muted">訂單數</small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0" id="todayBookings">0</h5>
                                <small class="text-muted">今日新增</small>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress mb-2">
                                <div class="progress-bar status-confirmed" id="confirmedBar" style="width: 0%"></div>
                                <div class="progress-bar status-pending" id="pendingBar" style="width: 0%"></div>
                                <div class="progress-bar status-cancelled" id="cancelledBar" style="width: 0%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small id="confirmedOrders" class="status-confirmed status-text">0 已確認</small>
                                <small id="pendingOrders" class="status-pending status-text">0 待處理</small>
                                <small id="cancelledOrders" class="status-cancelled status-text">0 已取消</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 營收統計 -->
        <div class="col-md-3">
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
                            <span class="badge" id="revenueGrowth">0%</span>
                            <small class="text-muted d-block">環比增長</small>
                        </div>
                    </div>
                    <i class="fas fa-dollar-sign stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- 客戶統計 -->
        <div class="col-md-3">
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
                        <small id="customerRetentionRate">0%</small>
                    </div>
                    <i class="fas fa-users stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- 營位統計 -->
        <div class="col-md-3">
            <div class="stat-card spots">
                <div class="stat-content p-3">
                    <h6 class="text-muted mb-2">營位概況</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h3 class="mb-0" id="totalSpots">0</h3>
                            <small class="text-muted">總營位</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0" id="operatingSpots">0</h5>
                            <small class="text-muted">營運中</small>
                        </div>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar" id="spotOccupancyBar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>使用</small>
                        <small id="spotOccupancyRate">0%</small>
                    </div>
                    <i class="fas fa-campground stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 訂單趨勢圖卡 -->
    <div class="row mt-4">
        <!-- 訂單趨勢分析 -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">訂單趨勢分析</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm active" data-days="7">最近7天</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-days="30">最近30天</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="orderTrendChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- 熱門營位排行 -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">熱門營位排行</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm active" data-period="week">本週</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-period="month">本月</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="popularSpotsTable"></div>
                </div>
            </div>
        </div>
    </div>


    <!-- 營位管理區塊 -->
    <div class="spots-management" id="spotsContainer">
        <!-- 動態生成的營位卡片範例 -->
        <div class="spot-card">
            <i class="fas fa-campground spot-icon"></i>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>營位名稱</h6>
                <span class="spot-status status-available">可預訂</span>
            </div>
            <div class="spot-info">
                <p>
                    <span>容納人數</span>
                    <span>4人</span>
                </p>
                <p class="price-info">
                    <span>價格</span>
                    <span>NT$ 2,000</span>
                </p>
                <p>
                    <span>本月預訂</span>
                    <span>15次</span>
                </p>
                <p class="revenue-info">
                    <span>本月營收</span>
                    <span>NT$ 30,000</span>
                </p>
                <p class="maintenance-date">
                    <span>維護結束日期</span>
                    <span>2024-03-15</span>
                </p>
            </div>
        </div>
    </div>

    <!-- 圖表區域 -->
    <!-- <div class="chart-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5>營位使用趨勢</h5>
            <select id="chartPeriod" class="form-select" style="width: auto;">
                <option value="7">最近 7 天</option>
                <option value="30">最近 30 天</option>
                <option value="90">最近 90 天</option>
            </select>
        </div>
        <canvas id="spotsChart"></canvas>
    </div> -->
</div>    <!-- 新增的詳細分析區域 -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 在 script 區塊中設置 axios 默配置
axios.defaults.withCredentials = true;

// 初始化所有事件監聽器和圖表
document.addEventListener('DOMContentLoaded', function() {
    // 只初始化一次圖表
    const charts = initializeCharts();
    
    // 初始化事件監聽器
    initializeEventListeners();
    
    // 初始加載數據
    updateOrderTrend(7);
    updatePopularSpots('week');
});

// 初始化所有事件監聽器
function initializeEventListeners() {
    // 時間範圍選擇器
    const dateRangeSelector = document.querySelector('.btn-group [data-period]');
    if (dateRangeSelector) {
        const periodButtons = document.querySelectorAll('.btn-group [data-period]');
        periodButtons.forEach(button => {
            button.addEventListener('click', function() {
                // 移除其他按鈕的 active 類
                periodButtons.forEach(btn => btn.classList.remove('active'));
                // 添加當前按鈕的 active 類
                this.classList.add('active');
                // 更新圖表數據
                // updateAllCharts(this.dataset.period);
            });
        });
    }

    // 刷新按鈕
    const refreshButton = document.querySelector('button[onclick="refreshDashboard()"]');
    if (refreshButton) {
        refreshButton.addEventListener('click', refreshDashboard);
    }

    // 匯出按鈕
    const exportButton = document.querySelector('button[onclick="exportDashboardData()"]');
    if (exportButton) {
        exportButton.addEventListener('click', exportDashboardData);
    }
}



// 刷新儀表板
async function refreshDashboard() {
    try {
        showLoading();
        const response = await axios.get('/CampExplorer/owner/api/dashboard/analytics.php', {
            withCredentials: true,
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                'Expires': '0',
            }
        });
        
        if (response.data.success) {
            updateDashboardData(response.data.data);
            showSuccess('數據已更新');
        } else {
            throw new Error(response.data.message);
        }
    } catch (error) {
        console.error('刷新失敗:', error);
        if (error.response && error.response.status === 401) {
            window.location.href = '../owner-login.php';
        } else {
            showError('刷新數據失敗');
        }
    } finally {
        hideLoading();
    }
}

// 匯出報表
async function exportDashboardData() {
    try {
        showLoading();
        
        // 準備 CSV 內容
        const rows = [];
        
        // 添加標題
        rows.push(['營運數據報表']);
        rows.push(['報表生成時間：' + new Date().toLocaleString('zh-TW')]);
        rows.push([]);  // 空行
        
        // 訂單統計
        rows.push(['訂單統計']);
        rows.push(['總訂單數', document.getElementById('totalBookings').textContent]);
        rows.push(['已確認訂單', document.getElementById('confirmedOrders').textContent]);
        rows.push(['待處理訂單', document.getElementById('pendingOrders').textContent]);
        rows.push(['已取消訂單', document.getElementById('cancelledOrders').textContent]);
        rows.push([]);
        
        // 營收統計
        rows.push(['營收統計']);
        rows.push(['總營收', document.getElementById('totalRevenue').textContent]);
        rows.push(['本月營收', document.getElementById('monthlyRevenue').textContent]);
        rows.push(['今日營收', document.getElementById('todayRevenue').textContent]);
        rows.push([]);
        
        // 客戶統計
        rows.push(['客戶統計']);
        rows.push(['總客戶數', document.getElementById('totalCustomers').textContent]);
        rows.push(['回訪客戶', document.getElementById('returningCustomers').textContent]);
        rows.push([]);
        
        // 營位統計
        rows.push(['營位統計']);
        rows.push(['總營位數', document.getElementById('totalSpots').textContent]);
        rows.push(['營運中營位', document.getElementById('operatingSpots').textContent]);
        
        // 轉換為 CSV 字串
        const csvContent = rows.map(row => 
            row.map(cell => 
                typeof cell === 'string' && cell.includes(',') ? 
                `"${cell}"` : cell
            ).join(',')
        ).join('\n');
        
        // 創建 Blob
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // 創建下載連結
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `營運報表_${formatDate(new Date())}.csv`;
        
        // 觸發下載
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showSuccess('報表匯出成功');
    } catch (error) {
        console.error('匯出失敗:', error);
        showError('匯出報表失敗');
    } finally {
        hideLoading();
    }
}

// 格式化日期
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    const second = String(date.getSeconds()).padStart(2, '0');
    
    return `${year}${month}${day}_${hour}${minute}${second}`;
}

// 載入狀態管理
function showLoading() {
    // 添加載入動畫
    document.querySelector('.dashboard-container').classList.add('loading');
}

function hideLoading() {
    // 移除載入動畫
    document.querySelector('.dashboard-container').classList.remove('loading');
}

// 提示訊息
function showSuccess(message) {
    // 可以使用 Toast 或其他提示元件
    alert(message);
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
        <strong>錯誤！</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.dashboard-container');
    container.insertBefore(errorDiv, container.firstChild);
    
    // 5秒後自動消失
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// 初始載入數據
// updateAllCharts();

// 更新儀表板數據函數
async function updateDashboardData(data) {
    // 更新訂單統計卡
    document.getElementById('totalBookings').textContent = data.bookings.total;
    document.getElementById('todayBookings').textContent = data.bookings.today;
    document.getElementById('confirmedOrders').textContent = `${data.bookings.confirmed} 已確認`;
    document.getElementById('pendingOrders').textContent = `${data.bookings.pending} 待處理`;
    document.getElementById('cancelledOrders').textContent = `${data.bookings.cancelled} 已取消`;

    // 更新進度條
    const totalOrders = data.bookings.total || 1; // 避免除以零
    document.getElementById('confirmedBar').style.width = `${(data.bookings.confirmed / totalOrders) * 100}%`;
    document.getElementById('pendingBar').style.width = `${(data.bookings.pending / totalOrders) * 100}%`;
    document.getElementById('cancelledBar').style.width = `${(data.bookings.cancelled / totalOrders) * 100}%`;

    // 更新營收統計卡
    document.getElementById('totalRevenue').textContent = `NT$ ${formatNumber(data.revenue.total)}`;
    document.getElementById('todayRevenue').textContent = `NT$ ${formatNumber(data.revenue.today)}`;
    document.getElementById('monthlyRevenue').textContent = `NT$ ${formatNumber(data.revenue.monthly)}`;
    
    // 計算環比增長
    const growth = data.revenue.monthly > 0 ? 
        ((data.revenue.monthly - data.revenue.last_month) / data.revenue.last_month * 100).toFixed(1) : 0;
    document.getElementById('revenueGrowth').textContent = `${growth}%`;
    document.getElementById('revenueGrowth').className = `badge ${growth >= 0 ? 'bg-success' : 'bg-danger'}`;

    // 更新客戶統計卡
    document.getElementById('totalCustomers').textContent = data.customers.total;
    document.getElementById('returningCustomers').textContent = data.customers.returning;
    
    // 更新客戶留存率進度條
    const retentionRate = data.customers.total > 0 ? 
        (data.customers.returning / data.customers.total * 100).toFixed(1) : 0;
    document.getElementById('customerRetentionBar').style.width = `${retentionRate}%`;
    document.getElementById('customerRetentionRate').textContent = `${retentionRate}%`;

    // 更新營位統計卡
    document.getElementById('totalSpots').textContent = data.spots.total;
    document.getElementById('operatingSpots').textContent = data.spots.operating;
    
    // 更新營位使用率進度
    const occupancyRate = data.spots.total > 0 ? 
        (data.spots.operating / data.spots.total * 100).toFixed(1) : 0;
    document.getElementById('spotOccupancyBar').style.width = `${occupancyRate}%`;
    document.getElementById('spotOccupancyRate').textContent = `${occupancyRate}%`;
}

// 格式化數字的輔助函數
function formatNumber(number) {
    return new Intl.NumberFormat('zh-TW').format(number);
}

// 初始化儀表板
async function initializeDashboard() {
    try {
        showLoading();
        const response = await axios.get('/CampExplorer/owner/api/dashboard/analytics.php', {
            withCredentials: true,
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                'Expires': '0',
            }
        });
        
        if (response.data.success) {
            updateDashboardData(response.data.data);
        }
    } catch (error) {
        console.error('初始化儀表板失敗:', error);
        if (error.response && error.response.status === 401) {
            window.location.href = '../owner-login.php';
        } else {
            showError('載入數據失敗: ' + error.message);
        }
    } finally {
        hideLoading();
    }
}

// 在文檔加載完成後只初始化儀表板
document.addEventListener('DOMContentLoaded', initializeDashboard);

// 初始化圖表
function initializeCharts() {
    // 先檢查並銷毀已存在的圖表
    const existingChart = Chart.getChart('orderTrendChart');
    if (existingChart) {
        existingChart.destroy();
    }

    // 訂單趨勢圖
    const orderTrendCtx = document.getElementById('orderTrendChart').getContext('2d');
    const orderTrendChart = new Chart(orderTrendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: '訂單數量',
                data: [],
                borderColor: 'rgba(156, 155, 122, 0.8)',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(156, 155, 122, 0.1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#666',
                    bodyColor: '#666',
                    borderColor: 'rgba(156, 155, 122, 0.2)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `訂單數: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    return {
        orderTrendChart
    };
}

// 更新熱門營位表格
function updatePopularSpotsTable(data) {
    const container = document.getElementById('popularSpotsTable');
    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>營位名稱</th>
                        <th>預訂數</th>
                        <th>營收</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.map(spot => `
                        <tr>
                            <td>${spot.name || '未命名營位'}</td>
                            <td>${spot.bookings}</td>
                            <td>NT$ ${formatNumber(spot.revenue)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// 事件監聽器
document.addEventListener('DOMContentLoaded', function() {
    const charts = initializeCharts();
    
    // 訂單趨勢時間範圍切換按鈕
    const trendButtons = document.querySelectorAll('.card-header .btn-group [data-days]');
    trendButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 移除其他按鈕的 active 類
            trendButtons.forEach(btn => btn.classList.remove('active'));
            // 添加當前按鈕的 active 類
            this.classList.add('active');
            // 更新訂單趨勢圖
            updateOrderTrend(this.dataset.days);
        });
    });
    
    // 初始加載數據
    updateOrderTrend(7); // 預設顯示最近7天
});

// 更新訂單趨勢圖的函數
async function updateOrderTrend(days) {
    try {
        showLoading();
        const response = await axios.get(`/CampExplorer/owner/api/dashboard/order-trend.php?days=${days}`);
        
        if (response.data.success) {
            const chart = Chart.getChart('orderTrendChart');
            if (chart) {
                chart.data.labels = response.data.data.dates;
                chart.data.datasets[0].data = response.data.data.orders;
                chart.update();
            }
        } else {
            throw new Error(response.data.message || '無法載入訂單趨勢數據');
        }
    } catch (error) {
        console.error('更新訂單趨勢失敗:', error);
        showError(error.response?.data?.message || '無法載入訂單趨勢數據');
    } finally {
        hideLoading();
    }
}

async function updatePopularSpots(period) {
    try {
        const response = await axios.get(`/CampExplorer/owner/api/dashboard/popular-spots.php?period=${period}`);
        if (response.data.success) {
            updatePopularSpotsTable(response.data.data);
        } else {
            throw new Error(response.data.message || '無法載入熱門營位數據');
        }
    } catch (error) {
        console.error('更新熱門營位失敗:', error);
        showError(error.response?.data?.message || '無法載入熱門營位數據');
    }
}

// 提示訊息處理
function showMessage(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert-message ${type}`;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);

    // 顯示動畫
    setTimeout(() => alertDiv.classList.add('show'), 10);

    // 自動消失
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

function showError(message) {
    showMessage(message, 'error');
}

function showSuccess(message) {
    showMessage(message, 'success');
}

// 初始化營位數據
async function initializeSpotsData() {
    try {
        const response = await axios.get('/CampExplorer/owner/api/dashboard/spot-status.php');
        if (response.data.success) {
            updateSpotsDisplay(response.data.data);
            // initializeSpotChart(response.data.data.summary);
        }
    } catch (error) {
        console.error('獲取營位數據失敗:', error);
        showError('無法載入營位數據');
    }
}

// 更新營位顯示
function updateSpotsDisplay(data) {
    const container = document.getElementById('spotsContainer');
    container.innerHTML = data.spots.map(spot => `
        <div class="spot-card">
            <i class="fas fa-campground spot-icon"></i>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>${spot.spot_name}</h6>
                <span class="spot-status status-${spot.status.toLowerCase().replace(/\s+/g, '-')}">
                    ${getStatusText(spot.status)}
                </span>
            </div>
            <div class="spot-info">
                <p>
                    <span>容納人數</span>
                    <span>${spot.capacity}人</span>
                </p>
                <p class="price-info">
                    <span>價格</span>
                    <span>NT$ ${formatNumber(spot.price)}</span>
                </p>
                <p>
                    <span>本月預訂</span>
                    <span>${spot.bookings}次</span>
                </p>
                <p class="revenue-info">
                    <span>本月營收</span>
                    <span>NT$ ${formatNumber(spot.monthly_revenue)}</span>
                </p>
                ${spot.maintenance_end_date ? `
                    <p class="maintenance-date">
                        <span>維護結束日期</span>
                        <span>${spot.maintenance_end_date}</span>
                    </p>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// 狀態文字轉換函數
function getStatusText(status) {
    const statusMap = {
        'available': '可預訂',
        'occupied': '已預訂',
        'maintenance': '維護中'
    };
    return statusMap[status.toLowerCase()] || status;
}

// 初始化營位使用趨勢圖表
function initializeSpotChart(data) {
    const ctx = document.getElementById('spotsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.dates,
            datasets: [{
                label: '使用率',
                data: data.occupancy_rates,
                borderColor: '#9C9B7A',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: value => value + '%'
                    }
                }
            }
        }
    });
}

// 頁面載入時初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeSpotsData();
    
    // 圖表期間選擇
    // document.getElementById('chartPeriod').addEventListener('change', function() {
    //     updateSpotChart(this.value);
    // });
});
</script>
