<?php
global $db;
require_once __DIR__ . '/../../camping_db.php';

try {
    // 基本統計
    $sql_users = "SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_users_today,
        COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN 1 END) as new_users_month
    FROM users";
    $result_users = $db->query($sql_users);
    $users_stats = $result_users->fetch(PDO::FETCH_ASSOC);

    // 營地統計（包含營運狀態）
    $sql_camps = "SELECT 
        COUNT(*) as total_camps,
        COUNT(CASE WHEN status = 0 THEN 1 END) as pending_camps,
        COUNT(CASE WHEN status = 1 THEN 1 END) as approved_camps,
        COUNT(CASE WHEN operation_status = 1 THEN 1 END) as operating_camps,
        COUNT(CASE WHEN operation_status = 2 THEN 1 END) as maintenance_camps,
        COUNT(CASE WHEN operation_status = 3 THEN 1 END) as closed_camps
    FROM camp_applications";
    $result_camps = $db->query($sql_camps);
    $camps_stats = $result_camps->fetch(PDO::FETCH_ASSOC);

    // 文章統計（含月度數據）
    $sql_articles = "SELECT 
        COUNT(*) as total_articles, 
        SUM(views) as total_views,
        COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN 1 END) as new_articles_month,
        AVG(views) as avg_views
    FROM articles";
    $result_articles = $db->query($sql_articles);
    $articles_stats = $result_articles->fetch(PDO::FETCH_ASSOC);

    // 商品統計（含分類統計）
    $sql_products = "SELECT 
        COUNT(*) as total_products,
        COUNT(CASE WHEN stock <= 10 THEN 1 END) as low_stock,
        COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
        AVG(price) as avg_price
    FROM products";
    $result_products = $db->query($sql_products);
    $products_stats = $result_products->fetch(PDO::FETCH_ASSOC);

    // 熱門文章
    $sql_popular_articles = "SELECT title, views, created_at 
    FROM articles 
    ORDER BY views DESC 
    LIMIT 5";
    $result_popular_articles = $db->query($sql_popular_articles);

    // 商品分類統計 - 優化查詢
    $sql_categories = "SELECT 
        c.id,
        c.name as category_name,
        COUNT(DISTINCT p.id) as total_products,
        COUNT(CASE WHEN p.status = 1 THEN p.id END) as active_products,
        SUM(CASE WHEN p.status = 1 AND p.stock <= 10 THEN 1 ELSE 0 END) as low_stock_count,
        SUM(CASE WHEN p.status = 1 AND p.stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
        ROUND(AVG(CASE WHEN p.status = 1 THEN p.price END), 0) as avg_price
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    WHERE c.status = 1
    GROUP BY c.id, c.name
    HAVING active_products > 0
    ORDER BY active_products DESC";
    $result_categories = $db->query($sql_categories);

    // 新增最近活動查詢
    $sql_recent_activities = "SELECT 
        'camp_review' as type,
        cr.reviewed_at as time,
        CONCAT('審核營地申請 #', cr.application_id) as description,
        ca.name as detail,
        a.name as admin_name
    FROM campsite_reviews cr
    JOIN camp_applications ca ON cr.application_id = ca.application_id
    JOIN admins a ON cr.admin_id = a.id
    UNION ALL
    SELECT 
        'article' as type,
        created_at as time,
        '新增了文章' as description,
        title as detail,
        'system' as admin_name
    FROM articles
    ORDER BY time DESC
    LIMIT 10";
    $result_activities = $db->query($sql_recent_activities);

    // 新增待處理事項統計
    $sql_pending = "SELECT 
        (SELECT COUNT(*) FROM camp_applications WHERE status = 0) as pending_camps,
        (SELECT COUNT(*) FROM products WHERE stock <= 10) as low_stock_products,
        (SELECT COUNT(*) FROM user_discussions WHERE status = 'pending') as pending_discussions
    ";
    $result_pending = $db->query($sql_pending);
    $pending_stats = $result_pending->fetch(PDO::FETCH_ASSOC);

    // 營收統計
    $sql_revenue = "SELECT 
        COALESCE(SUM(CASE WHEN payment_status = 1 THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN payment_status = 1 AND DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END), 0) as today_revenue,
        COUNT(*) as total_orders,
        COUNT(CASE WHEN order_status = 0 THEN 1 END) as pending_orders,
        COALESCE(
            ((SUM(CASE WHEN payment_status = 1 AND MONTH(created_at) = MONTH(CURDATE()) THEN total_amount ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN payment_status = 1 AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
            THEN total_amount ELSE 0 END), 0)) * 100) - 100, 
        0) as growth_rate
    FROM product_orders";

    $result_revenue = $db->query($sql_revenue);
    $revenue_stats = $result_revenue->fetch(PDO::FETCH_ASSOC);

    // 訂單狀態統計
    $sql_order_stats = "SELECT 
        COUNT(*) as total_orders,
        COALESCE(COUNT(CASE WHEN order_status = 0 THEN 1 END), 0) as pending_orders,
        COALESCE(COUNT(CASE WHEN order_status = 1 THEN 1 END), 0) as processing_orders,
        COALESCE(COUNT(CASE WHEN order_status = 2 THEN 1 END), 0) as completed_orders,
        COALESCE(COUNT(CASE WHEN order_status = 3 THEN 1 END), 0) as cancelled_orders,
        COALESCE(COUNT(CASE WHEN payment_status = 0 THEN 1 END), 0) as unpaid_orders,
        COALESCE(COUNT(CASE WHEN payment_status = 1 THEN 1 END), 0) as paid_orders,
        COALESCE(COUNT(CASE WHEN payment_status = 2 THEN 1 END), 0) as refunded_orders
    FROM product_orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";

    $result_order_stats = $db->query($sql_order_stats);
    $order_stats = $result_order_stats->fetch(PDO::FETCH_ASSOC);

    // 訂單轉換率統計
    $sql_conversion = "SELECT 
        COUNT(DISTINCT member_id) as total_buyers,
        ROUND((COUNT(DISTINCT member_id) * 100.0 / (SELECT COUNT(*) FROM users)), 2) as conversion_rate,
        COALESCE(AVG(CASE WHEN payment_status = 1 THEN total_amount END), 0) as avg_order_amount,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as orders_today
    FROM product_orders";

    $result_conversion = $db->query($sql_conversion);
    $conversion_stats = $result_conversion->fetch(PDO::FETCH_ASSOC);

    // 會員活躍度統計
    $sql_activity = "SELECT 
        COUNT(DISTINCT CASE WHEN DATE(last_login) = CURDATE() THEN id END) as active_users_today,
        COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN id END) as active_users_week,
        COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN id END) as active_users_month,
        ROUND(
            (COUNT(DISTINCT CASE WHEN last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN id END) * 100.0 / 
            COUNT(*)), 
        2) as active_rate
    FROM users
    WHERE status = 1";  // 只統計啟用的帳號

    $result_activity = $db->query($sql_activity);
    $activity_stats = $result_activity->fetch(PDO::FETCH_ASSOC);

    // 取得今年每月營收趨勢
    $sql_monthly_revenue = "SELECT 
        MONTH(created_at) as month,
        COALESCE(SUM(CASE WHEN payment_status = 1 THEN total_amount ELSE 0 END), 0) as revenue
    FROM product_orders 
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY month ASC";

    $result_monthly_revenue = $db->query($sql_monthly_revenue);
    $monthly_revenue = $result_monthly_revenue->fetchAll(PDO::FETCH_ASSOC);

    // 準備圖表數據
    $revenue_months = array_fill(1, 12, 0); // 初始化12個月的數據為0
    foreach ($monthly_revenue as $data) {
        $revenue_months[$data['month']] = (float)$data['revenue'];
    }
} catch (PDOException $e) {
    // 添加錯誤處理，設置預設值
    $activity_stats = [
        'active_users_today' => 0,
        'active_users_week' => 0,
        'active_users_month' => 0,
        'active_rate' => 0
    ];

    error_log("Dashboard Stats Error: " . $e->getMessage());
}
?>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>

<!-- 在 Font Awesome CDN 後面添加 -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<!-- 自定義 CSS -->
<style>
    /* 卡片樣式化 */
    .card {
        background: #ffffff;
        /* 改為純白背景 */
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    /* 移除 backdrop-filter */
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    /* 統計卡片背景色系 - 使用純色漸層 */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #6B7A8F 0%, #8299B5 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #7FA18C 0%, #8DAB9B 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #8299B5 0%, #95A7C1 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #C4A99D 0%, #D3B8AC 100%);
    }

    .bg-gradient-purple {
        background: linear-gradient(135deg, #9D91A9 0%, #AEA3B9 100%);
    }

    /* 圖標圓圈樣式優化 */
    .icon-circle {
        height: 60px;
        width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: transform 0.3s ease;
    }

    .icon-circle:hover {
        transform: scale(1.05);
    }

    /* 移除其他遮罩相關樣式 */
    .chart-tooltip {
        background: #ffffff !important;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* 數字顯示優化 */
    .card h2 {
        font-size: 2.2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
    }

    /* 小標籤樣式化 */
    .card small {
        font-size: 0.85rem;
        opacity: 0.9;
        letter-spacing: 0.5px;
    }

    /* 圖標圓圈優化 */
    .icon-circle {
        height: 60px;
        width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }

    .icon-circle:hover {
        transform: scale(1.1);
        background: rgba(255, 255, 255, 0.3);
    }

    /* 新增載入動畫 */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card {
        animation: fadeInUp 0.5s ease-out;
    }

    /* 新增滑鼠懸停效果 */
    .btn {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn:after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
    }

    .btn:hover:after {
        animation: ripple 1s ease-out;
    }

    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }

        100% {
            transform: scale(40, 40);
            opacity: 0;
        }
    }

    /* 新增應式文字大小 */
    @media (max-width: 768px) {
        .card h2 {
            font-size: 1.8rem;
        }

        .card h4 {
            font-size: 1.2rem;
        }
    }


    /* 新增載入中狀態 */
    .loading {
        position: relative;
    }

    .loading:after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    /* 新增工具提示樣式 */
    .tooltip {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    /* 時間軸樣式 */
    .timeline {
        position: relative;
        padding: 1.5rem;
        max-height: 600px;
        overflow-y: auto;
    }

    /* 時間軸項目 */
    .timeline-item {
        position: relative;
        padding-left: 3rem;
        margin-bottom: 1.5rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* 時間軸標記 */
    .timeline-marker {
        position: absolute;
        left: -20px;
        top: 0;
    }

    .timeline-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* 活動內容 */
    .timeline-content {
        border-radius: 8px;
        overflow: hidden;
    }

    .timeline-header {
        background-color: rgba(248, 249, 250, 0.5);
    }

    .timeline-body {
        background-color: white;
    }

    .activity-detail {
        font-size: 0.9rem;
        color: #666;
        background-color: #f8f9fa;
        border-left: 3px solid #dee2e6;
    }

    /* 滾動條美化 */
    .timeline::-webkit-scrollbar {
        width: 6px;
    }

    .timeline::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 3px;
    }

    /* 活動標籤樣式 */
    .activity-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .activity-badge.camp_review {
        background-color: #E8F4F8;
        color: #4A90A0;
    }

    .activity-badge.article {
        background-color: #E8F6E8;
        color: #5B8A5B;
    }

    /* 活動內容樣式 */
    .timeline-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-left: 1rem;
        transition: all 0.3s ease;
    }

    /* 活動詳情樣式 */
    .activity-detail {
        padding: 0.5rem 1rem;
        background: #f8f9fa;
        border-radius: 4px;
        font-size: 0.9rem;
        color: #6c757d;
    }

    /* 莫蘭迪配色 */
    .bg-morandi-blue {
        background: linear-gradient(135deg, #6B7A8F 0%, #8299B5 100%);
    }

    .bg-morandi-sage {
        background: linear-gradient(135deg, #7FA18C 0%, #8DAB9B 100%);
    }

    /* 收合狀態的高度 */
    .timeline.collapsed {
        max-height: 300px;
        min-height: 300px;
    }

    /* 時間軸項目樣式優化 */
    .timeline-item {
        position: relative;
        padding: 1.25rem 0;
        padding-left: 2rem;
        border-left: 2px solid var(--morandi-blue-light);
        margin-left: 0.5rem;
        transition: all 0.3s ease;
    }

    /* 時間軸圓點樣式 */
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -0.5rem;
        top: 1.5rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: var(--morandi-blue);
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px var(--morandi-blue-light);
    }

    /* 活動內容容器 */
    .timeline-content {
        background: rgba(255, 255, 255, 0.5);
        border-radius: 8px;
        padding: 1rem;
        margin-left: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    /* 活動內容懸停效果 */
    .timeline-content:hover {
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transform: translateX(5px);
    }

    /* 分隔線樣式 */
    .timeline-item:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* 自定義滾動條 */
    .timeline::-webkit-scrollbar {
        width: 8px;
    }

    .timeline::-webkit-scrollbar-track {
        background: #f5f5f5;
        border-radius: 4px;
    }

    .timeline::-webkit-scrollbar-thumb {
        background: var(--morandi-blue-light);
        border-radius: 4px;
        border: 2px solid #f5f5f5;
    }

    .timeline::-webkit-scrollbar-thumb:hover {
        background: var(--morandi-blue);
    }

    /* 活動標題樣式 */
    .activity-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--morandi-blue-light);
        color: var(--morandi-blue);
        margin-right: 1rem;
    }

    /* 時間標籤樣式 */
    .timeline-time {
        font-size: 0.85rem;
        color: #6c757d;
        margin-left: auto;
        padding-left: 1rem;
        white-space: nowrap;
    }

    /* 無數據提示樣式 */
    .no-data-message {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
        font-size: 1rem;
        background: rgba(0, 0, 0, 0.02);
        border-radius: 8px;
        margin: 1rem 0;
    }

    /* 按鈕組樣式 */
    .btn-group .btn-outline-secondary {
        border-color: var(--morandi-blue-light);
        color: var(--morandi-blue);
    }

    .btn-group .btn-outline-secondary:hover,
    .btn-group .btn-outline-secondary.active {
        background-color: var(--morandi-blue);
        border-color: var(--morandi-blue);
        color: white;
    }

    /* 活動類型標籤 */
    .activity-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-right: 8px;
    }

    .activity-badge.review {
        background-color: #e3f2fd;
        color: #1976d2;
    }

    .activity-badge.article {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    /* 折疊按鈕樣式 */
    .timeline-collapse-btn {
        padding: 4px 8px;
        font-size: 0.75rem;
        color: #6c757d;
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .timeline-collapse-btn:hover {
        color: #495057;
    }

    /* 篩選按鈕樣式 */
    .btn-group .btn {
        padding: 0.375rem 1rem;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .btn-group .btn.active {
        background-color: #6B7A8F;
        color: #ffffff;
        border-color: #6B7A8F;
    }

    /* 動畫效果 */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 無數據提示樣式 */
    .no-data-message {
        padding: 2rem;
        color: #6c757d;
        background: rgba(108, 117, 125, 0.05);
        border-radius: 8px;
        font-size: 0.9rem;
    }



    /* 趨勢指標樣式 */
    .trend-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        margin-left: 8px;
    }

    .trend-badge.up {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .trend-badge.down {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    /* 詳情區域樣式 */
    .card-details {
        animation: slideDown 0.3s ease-out;
    }

    .stat-item {
        padding: 8px;
        background: rgba(0, 0, 0, 0.03);
        border-radius: 8px;
        margin-bottom: 8px;
    }

    .mini-chart {
        height: 60px;
        margin-top: 4px;
    }

    /* 動畫效果 */
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }


    /* 新增顏色變量 */
    :root {
        --indigo: #6610f2;
        --indigo-light: rgba(102, 16, 242, 0.1);
        --teal: #20c997;
        --teal-light: rgba(32, 201, 151, 0.1);
    }

    /* 新增顏色類 */
    .text-indigo {
        color: var(--indigo) !important;
    }

    .text-teal {
        color: var(--teal) !important;
    }

    .bg-indigo-light {
        background-color: var(--indigo-light) !important;
    }

    .bg-teal-light {
        background-color: var(--teal-light) !important;
    }

    /* 莫蘭迪色系按鈕 */
    :root {
        /* 莫蘭迪主色系 */
        --monofondi-sage: #9CAF88;
        /* 鼠尾草綠 */
        --monofondi-blue: #8E9EAB;
        /* 莫蘭迪藍 */
        --monofondi-gray: #A2A9B0;
        /* 莫蘭迪灰 */
        --monofondi-green: #A5B5A3;
        /* 莫蘭迪綠 */
        --monofondi-sand: #B5A898;
        /* 莫蘭迪沙 */
        --monofondi-rose: #B5A3A1;
        /* 莫蘭迪玫瑰 */
        --monofondi-purple: #A499B3;
        /* 莫蘭迪紫 */
        --monofondi-blue-gray: #8B97A5;
        /* 莫蘭迪藍灰 */
    }

    /* 按鈕基本樣式 */
    .btn-sm {
        /* padding: 0.5rem 1rem; */
        padding: 0.5rem 1.2rem 0.5rem 1rem;
        font-size: 0.875rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* 莫蘭迪按鈕樣式 */
    .btn-monofondi-sage {
        background-color: var(--monofondi-sage);
        color: white;
    }

    .btn-monofondi-blue {
        background-color: var(--monofondi-blue);
        color: white;
    }

    .btn-monofondi-gray {
        background-color: var(--monofondi-gray);
        color: white;
    }

    .btn-monofondi-green {
        background-color: var(--monofondi-green);
        color: white;
    }

    .btn-monofondi-sand {
        background-color: var(--monofondi-sand);
        color: white;
    }

    .btn-monofondi-rose {
        background-color: var(--monofondi-rose);
        color: white;
    }

    .btn-monofondi-purple {
        background-color: var(--monofondi-purple);
        color: white;
    }

    .btn-monofondi-blue-gray {
        background-color: var(--monofondi-blue-gray);
        color: white;
    }

    /* 按鈕懸停效果 */
    [class*="btn-monofondi-"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        color: white;
        filter: brightness(1.1);
    }

    /* 按鈕點擊效果 */
    [class*="btn-monofondi-"]:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* 卡片標題顏色 */
    .text-monofondi {
        color: var(--monofondi-blue-gray);
        font-weight: 500;
    }

    /* 快速操作卡片樣式 */
    .card {
        border: none;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        background: #ffffff;
    }

    /* 按鈕間距優化 */
    .gap-2 {
        gap: 0.75rem !important;
    }

    /* 統計卡片的優化樣式 */
    .card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    /* 卡片背景漸層效果 */
    .card.bg-morandi-blue-gradient {
        background: linear-gradient(135deg, rgba(142, 158, 171, 0.15) 0%, rgba(142, 158, 171, 0.05) 100%);
    }

    .card.bg-morandi-sage-gradient {
        background: linear-gradient(135deg, rgba(156, 175, 136, 0.15) 0%, rgba(156, 175, 136, 0.05) 100%);
    }

    .card.bg-morandi-rose-gradient {
        background: linear-gradient(135deg, rgba(181, 163, 161, 0.15) 0%, rgba(181, 163, 161, 0.05) 100%);
    }

    .card.bg-morandi-mauve-gradient {
        background: linear-gradient(135deg, rgba(162, 148, 166, 0.15) 0%, rgba(162, 148, 166, 0.05) 100%);
    }

    .card.bg-morandi-mint-gradient {
        background: linear-gradient(135deg, rgba(165, 181, 163, 0.15) 0%, rgba(165, 181, 163, 0.05) 100%);
    }

    .card.bg-morandi-sand-gradient {
        background: linear-gradient(135deg, rgba(181, 168, 152, 0.15) 0%, rgba(181, 168, 152, 0.05) 100%);
    }

    .card.bg-morandi-purple-gradient {
        background: linear-gradient(135deg, rgba(164, 153, 179, 0.15) 0%, rgba(164, 153, 179, 0.05) 100%);
    }

    .card.bg-morandi-gray-gradient {
        background: linear-gradient(135deg, rgba(162, 169, 176, 0.15) 0%, rgba(162, 169, 176, 0.05) 100%);
    }

    /* 卡片懸停效果 */
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    /* 圖標圓圈效果 */
    .icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .icon-circle::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        transform: scale(0);
        border-radius: 50%;
        transition: transform 0.3s ease;
    }

    .card:hover .icon-circle::after {
        transform: scale(1);
    }

    .icon-circle i {
        transition: all 0.3s ease;
    }

    .card:hover .icon-circle i {
        transform: scale(1.1);
    }

    /* 數字動畫效果 */
    .card h2,
    .card h4 {
        transition: all 0.3s ease;
    }

    .card:hover h2 {
        transform: scale(1.05);
        color: var(--morandi-blue);
    }

    /* 分隔線效果 */
    .card hr {
        border-color: currentColor;
        opacity: 0.1;
        margin: 1rem 0;
        transition: all 0.3s ease;
    }

    .card:hover hr {
        opacity: 0.2;
        width: 95%;
        margin-left: auto;
        margin-right: auto;
    }

    /* 卡片內容布局優化 */
    .card-body {
        padding: 1.5rem;
        position: relative;
        z-index: 1;
    }

    /* 響應式調整 */
    @media (max-width: 768px) {
        .card {
            margin-bottom: 1rem;
        }
    }

    /* 添加裝飾元素 */
    .card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
        transform: translate(50%, -50%);
        border-radius: 50%;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .card:hover::before {
        opacity: 1;
    }

    /* 莫蘭迪進階色系 - 更鮮明的配色 */
    :root {
        /* 主色調 */
        --morandi-blue: #7A90A8;
        /* 更深的莫蘭迪藍 */
        --morandi-sage: #8FA977;
        /* 更鮮豔的鼠尾草綠 */
        --morandi-rose: #C69B97;
        /* 更溫暖的莫蘭迪玫瑰 */
        --morandi-purple: #9B8AA6;
        /* 更深的莫蘭迪紫 */
        --morandi-sand: #C4A687;
        /* 更溫暖的莫蘭迪沙 */
        --morandi-mint: #89B0A3;
        /* 更清新的莫蘭迪薄荷 */
        --morandi-mauve: #A68E9B;
        /* 更深的莫蘭迪紫灰 */
        --morandi-gray: #8E9CAA;
        /* 更深的莫蘭迪灰 */
    }

    /* 卡片漸層背景 - 更強的視覺效果 */
    .card.bg-morandi-blue-gradient {
        background: linear-gradient(135deg, #7A90A8 0%, #A8B9CC 100%);
        color: white;
    }

    .card.bg-morandi-sage-gradient {
        background: linear-gradient(135deg, #8FA977 0%, #B3C7A1 100%);
        color: white;
    }

    .card.bg-morandi-rose-gradient {
        background: linear-gradient(135deg, #C69B97 0%, #E0BDB9 100%);
        color: white;
    }

    .card.bg-morandi-purple-gradient {
        background: linear-gradient(135deg, #9B8AA6 0%, #B8ABC0 100%);
        color: white;
    }

    .card.bg-morandi-sand-gradient {
        background: linear-gradient(135deg, #C4A687 0%, #E0CCBA 100%);
        color: white;
    }

    .card.bg-morandi-mint-gradient {
        background: linear-gradient(135deg, #89B0A3 0%, #B1CCC3 100%);
        color: white;
    }

    .card.bg-morandi-mauve-gradient {
        background: linear-gradient(135deg, #A68E9B 0%, #C7B9C1 100%);
        color: white;
    }

    .card.bg-morandi-gray-gradient {
        background: linear-gradient(135deg, #8E9CAA 0%, #B5BFC9 100%);
        color: white;
    }

    /* 卡片內容樣式優化 */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    /* 懸停效果增強 */
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    /* 文字顏色適配 */
    .card[class*="-gradient"] h2,
    .card[class*="-gradient"] h4,
    .card[class*="-gradient"] h6,
    .card[class*="-gradient"] small {
        color: white;
    }

    .card[class*="-gradient"] small {
        opacity: 0.9;
    }

    /* 圖標圓圈樣式 */
    .icon-circle {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .icon-circle i {
        color: white;
    }

    /* 分隔線樣式 */
    .card[class*="-gradient"] hr {
        border-color: rgba(255, 255, 255, 0.2);
    }

    /* 數據高亮效果 */
    .card h2 {
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* ��片內容布局 */
    .card-body {
        padding: 1.75rem;
        position: relative;
        z-index: 1;
    }

    /* 裝飾效果 */
    .card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
        transform: translate(30%, -30%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* 動畫效果 */
    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    .card:hover .icon-circle {
        animation: pulse 2s infinite;
    }

    .timeline-content-body {
        transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
        overflow: hidden;
        opacity: 1;
    }

    .timeline-collapse-btn {
        transition: transform 0.3s ease;
    }

    .timeline-collapse-btn i {
        transition: transform 0.3s ease;
    }

    .timeline-collapse-btn:hover {
        transform: scale(1.1);
    }

    .timeline-content-body {
        transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
        overflow: hidden;
        opacity: 1;
        padding: 0.5rem 0;
    }

    .timeline-content-body.collapsed {
        max-height: 0;
        opacity: 0;
        padding: 0;
    }

    .timeline-collapse-btn {
        background: none;
        border: none;
        padding: 0.25rem;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .timeline-collapse-btn:hover {
        transform: scale(1.1);
    }

    .timeline-collapse-btn i {
        transition: transform 0.3s ease;
    }

    .timeline-container .timeline-item {
        opacity: 1;
        transition: none;
    }

    .timeline-item .timeline-content {
        max-height: 1000px;
        opacity: 1;
        overflow: hidden;
        transition: all 0.3s ease-in-out;
    }

    .timeline-item.collapsed .timeline-content {
        max-height: 0;
        opacity: 0;
        padding: 0;
    }

    #collapseAllBtn {
        transition: all 0.3s ease;
    }

    #collapseAllBtn i {
        transition: transform 0.3s ease;
    }

    /* 莫蘭迪色系 */
    .bg-morandi-sand {
        background-color: #E6D5C1;
    }

    .bg-morandi-sage {
        background-color: #C8D1C2;
    }

    .bg-morandi-rose {
        background-color: #E6D0D0;
    }

    .bg-morandi-blue {
        background-color: #D0DDE6;
    }

    .bg-morandi-mauve {
        background-color: #E1D0E6;
    }

    .bg-morandi-gray-light {
        background-color: #F5F5F5;
    }

    .text-morandi-gray {
        color: #8E8E8E;
    }

    /* 表格樣式優化 */
    .table {
        margin-bottom: 0;
    }

    .table th {
        font-weight: 500;
        border-bottom-width: 1px;
    }

    .table td {
        vertical-align: middle;
        padding: 0.75rem;
    }

    .category-name {
        font-weight: 500;
        color: #555;
    }

    .badge {
        font-weight: 500;
        padding: 0.5em 0.75em;
    }

    /* 統計卡片文字顏色變量 */
    :root {
        --stats-title-color: #4A5568;
        /* 標題文字顏色 */
        --stats-number-color: #2D3748;
        /* 主要數字顏色 */
        --stats-label-color: #718096;
        /* 標籤文字顏色 */
        --stats-secondary-color: #A0AEC0;
        /* 次要數字顏色 */
    }

    /* 統計卡片樣式 */
    .stats-card {
        padding: 1.5rem;
        height: 100%;
        transition: all 0.3s ease;
    }

    .stats-card .card-title {
        color: var(--stats-title-color);
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .stats-card .stats-number {
        color: var(--stats-number-color);
        font-size: 1.75rem;
        font-weight: 600;
        line-height: 1.2;
        margin-bottom: 0.5rem;
    }

    .stats-card .stats-label {
        color: var(--stats-label-color);
        font-size: 0.875rem;
        font-weight: 400;
    }

    .stats-card .stats-secondary {
        color: var(--stats-secondary-color);
        font-size: 0.875rem;
        font-weight: 500;
    }

    .stats-card .stats-icon {
        color: var(--stats-title-color);
        opacity: 0.8;
    }

    /* 統一顏色變量 */
    :root {
        --stats-primary: #2D3748;
        /* 主要數字顏色 */
        --stats-secondary: #718096;
        /* 次要文字顏色 */
        --stats-label: #A0AEC0;
        /* 標籤文字顏色 */
        --stats-title: #4A5568;
        /* 標題文字顏色 */

        /* 莫蘭迪色系 */
        --morandi-blue: #A8C0D3;
        --morandi-green: #B8C4B8;
        --morandi-rose: #D4B9B9;
        --morandi-teal: #A3C5C9;
        /* 新增藍綠色 */
        --morandi-sand: #D3C1B1;
        --morandi-mint: #B5C7C0;
    }

    /* 統一卡片樣式 */
    .stats-card {
        padding: 1.5rem;
        border-radius: 0.5rem;
    }

    .stats-card .main-number {
        color: var(--stats-primary);
        font-size: 2rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .stats-card .sub-number {
        color: var(--stats-primary);
        font-size: 1.25rem;
        font-weight: 500;
    }

    .stats-card .stats-label {
        color: var(--stats-label);
        font-size: 0.875rem;
        font-weight: 400;
    }

    .stats-card .stats-title {
        color: var(--stats-title);
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    /* 統一顏色變量 */
    :root {
        --stats-text-white: rgba(255, 255, 255, 0.95);
        /* 主要文字白色 */
        --stats-text-light: rgba(255, 255, 255, 0.85);
        /* 次要文字白色 */
        --stats-text-muted: rgba(255, 255, 255, 0.7);
        /* 標籤文字白色 */
    }

    /* 統計卡片基本樣式 */
    .stats-card {
        padding: 1.5rem;
        border-radius: 0.75rem;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        backdrop-filter: blur(10px);
    }

    .stats-card .card-title {
        color: var(--stats-text-white);
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .stats-card .main-number {
        color: var(--stats-text-white);
        font-size: 2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stats-card .sub-number {
        color: var(--stats-text-light);
        font-size: 1.25rem;
        font-weight: 500;
    }

    .stats-card .stats-label {
        color: var(--stats-text-muted);
        font-size: 0.875rem;
        font-weight: 400;
    }

    .stats-card .icon-circle {
        color: var(--stats-text-white);
        opacity: 0.9;
    }

    /* 分隔線樣式 */
    .stats-card hr {
        border-color: rgba(255, 255, 255, 0.2);
        margin: 1rem 0;
    }

    /* 統一背景漸層 */
    .bg-morandi-blue-gradient {
        background: linear-gradient(135deg, #A8C0D3 0%, #8DA5B8 100%);
    }

    .bg-morandi-sage-gradient {
        background: linear-gradient(135deg, #B8C4B8 0%, #9DAA9D 100%);
    }

    .bg-morandi-rose-gradient {
        background: linear-gradient(135deg, #D4B9B9 0%, #B99E9E 100%);
    }

    .bg-morandi-mauve-gradient {
        background: linear-gradient(135deg, var(--morandi-teal) 0%, #8FADB3 100%);
    }

    .bg-morandi-sand-gradient {
        background: linear-gradient(135deg, #D3C1B1 0%, #B8A696 100%);
    }

    .bg-morandi-mint-gradient {
        background: linear-gradient(135deg, #B5C7C0 0%, #9AACA5 100%);
    }

    .bg-morandi-purple-gradient {
        background: linear-gradient(135deg, #C5B8CC 0%, #AA9DB1 100%);
    }

    .bg-morandi-gray-gradient {
        background: linear-gradient(135deg, #B8C0C8 0%, #9DA5AD 100%);
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
        color: var(--stats-text-white);
    }

    /* 分隔線樣式 */
    .stats-card hr {
        border-color: rgba(255, 255, 255, 0.2);
        margin: 1rem 0;
    }

    /* 卡片內文字間距 */
    .stats-card .d-flex {
        gap: 1rem;
    }

    /* 統一顏色變量 */
    :root {
        /* 文字顏色 */
        --stats-text-white: rgba(255, 255, 255, 0.95);
        /* 主要文字白色 */
        --stats-text-light: rgba(255, 255, 255, 0.85);
        /* 次要文字白色 */
        --stats-text-muted: rgba(255, 255, 255, 0.7);
        /* 標籤文字白色 */

        /* 莫蘭迪色系 */
        --morandi-blue: #A8C0D3;
        --morandi-green: #B8C4B8;
        --morandi-rose: #D4B9B9;
        --morandi-teal: #A3C5C9;
        /* 新增藍綠色 */
        --morandi-sand: #D3C1B1;
        --morandi-mint: #B5C7C0;
        --morandi-purple: #C5B8CC;
        --morandi-gray: #B8C0C8;
    }

    /* 卡片基本樣式 */
    .stats-card {
        padding: 1.75rem;
        border-radius: 0.75rem;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    /* 卡片文字樣式 */
    .stats-card .card-title {
        color: var(--stats-text-white);
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .stats-card .main-number {
        color: var(--stats-text-white);
        font-size: 2.2rem;
        font-weight: 600;
        line-height: 1.2;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stats-card .sub-number {
        color: var(--stats-text-light);
        font-size: 1.25rem;
        font-weight: 500;
    }

    .stats-card .stats-label {
        color: var(--stats-text-muted);
        font-size: 0.875rem;
        font-weight: 400;
        letter-spacing: 0.5px;
    }

    /* 背景漸層 */
    .bg-morandi-blue-gradient {
        background: linear-gradient(135deg, var(--morandi-blue) 0%, #8DA5B8 100%);
    }

    .bg-morandi-sage-gradient {
        background: linear-gradient(135deg, var(--morandi-green) 0%, #9DAA9D 100%);
    }

    .bg-morandi-rose-gradient {
        background: linear-gradient(135deg, var(--morandi-rose) 0%, #B99E9E 100%);
    }

    .bg-morandi-mauve-gradient {
        background: linear-gradient(135deg, var(--morandi-mauve) 0%, #AC9AAB 100%);
    }

    .bg-morandi-sand-gradient {
        background: linear-gradient(135deg, var(--morandi-sand) 0%, #B8A696 100%);
    }

    .bg-morandi-mint-gradient {
        background: linear-gradient(135deg, var(--morandi-mint) 0%, #9AACA5 100%);
    }

    .bg-morandi-purple-gradient {
        background: linear-gradient(135deg, var(--morandi-purple) 0%, #AA9DB1 100%);
    }

    .bg-morandi-gray-gradient {
        background: linear-gradient(135deg, var(--morandi-gray) 0%, #9DA5AD 100%);
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
        color: var(--stats-text-white);
        transition: transform 0.3s ease;
    }

    .icon-circle:hover {
        transform: scale(1.05);
    }

    /* 分隔線樣式 */
    .stats-card hr {
        border-color: rgba(255, 255, 255, 0.2);
        margin: 1rem 0;
    }

    /* 動畫效果 */
    .stats-card {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* 統一顏色變量 */
    :root {
        /* 文字顏色層級 */
        --text-primary: rgba(255, 255, 255, 0.95);
        /* 主標題、大數字 */
        --text-secondary: rgba(255, 255, 255, 0.85);
        /* 次要數字 */
        --text-tertiary: rgba(255, 255, 255, 0.75);
        /* 小標題 */
        --text-quaternary: rgba(255, 255, 255, 0.65);
        /* 說明文字 */
        --text-muted: rgba(255, 255, 255, 0.55);
        /* 最淺層文字 */

        /* 圖標顏色 */
        --icon-primary: rgba(255, 255, 255, 0.9);
        /* 主要圖標 */
        --icon-secondary: rgba(255, 255, 255, 0.7);
        /* 次要圖標 */
    }

    /* 文字層級樣式 */
    .stats-card .card-title {
        color: var(--text-tertiary);
        font-size: 1rem;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .stats-card .main-number {
        color: var(--text-primary);
        font-size: 2.2rem;
        font-weight: 600;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stats-card .sub-number {
        color: var(--text-secondary);
        font-size: 1.25rem;
        font-weight: 500;
    }

    .stats-card .stats-label {
        color: var(--text-quaternary);
        font-size: 0.875rem;
        font-weight: 400;
    }

    .stats-card small {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .stats-card .icon-circle {
        color: var(--icon-primary);
    }

    .stats-card .icon-circle i {
        opacity: 0.9;
    }

    :root {
        /* 莫蘭迪藍綠色系 */
        --morandi-blue: #A8C0D3;
        /* 主色調：柔和藍 */
        --morandi-sage: #B8C4B8;
        /* 灰綠色 */
        --morandi-mint: #B5C7C0;
        /* 薄荷綠 */
        --morandi-teal: #A3C5C9;
        /* 藍綠色 取代紫色 */
        --morandi-gray: #B8C0C8;
        /* 灰藍色 */
        --morandi-sand: #D3C1B1;
        /* 沙色 */
    }

    /* 文字顏色類 */
    .text-morandi-teal {
        color: var(--morandi-teal);
    }

    /* 背景色類 */
    .bg-morandi-teal {
        background-color: var(--morandi-teal);
    }

    /* 背景漸層 */
    .bg-morandi-teal-gradient {
        background: linear-gradient(135deg, var(--morandi-teal) 0%, #8DADB3 100%);
    }

    /* 莫蘭迪按鈕樣式 */
    .btn-outline-morandi-teal {
        color: var(--morandi-teal);
        border-color: var(--morandi-teal);
        background-color: transparent;
        transition: all 0.3s ease;
    }

    .btn-outline-morandi-teal:hover,
    .btn-outline-morandi-teal:active,
    .btn-outline-morandi-teal.active {
        color: #fff;
        background-color: var(--morandi-teal);
        border-color: var(--morandi-teal);
    }

    .btn-outline-morandi-teal:focus {
        box-shadow: 0 0 0 0.2rem rgba(163, 197, 201, 0.25);
    }

    /* Toast 通知樣式 */
    .toast {
        background: rgba(255, 255, 255, 0.98);
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
    }

    .toast-header {
        background: var(--morandi-teal);
        color: white;
        border-radius: 12px 12px 0 0;
    }

    .toast-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .toast-body {
        color: var(--morandi-gray);
        padding: 1rem;
    }

    .sub-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        padding-left: 2rem;
        background: rgba(255, 255, 255, 0.05);
        /* 改為較淺的背景 */
        border-left: 2px solid rgba(52, 152, 219, 0.3);
        /* 調整邊框透明度 */
    }

    .card-header {
        background-color: var(--morandi-teal);
        color: white;
        font-size: 24px;
    }

    .morandiColor {
        background-color: #A8B2B9;
    }

    .toast {
        min-width: 300px;
        backdrop-filter: blur(10px);
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        opacity: 0.95;
    }

    .toast-header {
        border-bottom: none;
    }

    .toast-body {
        color: #6B7A8A;
        padding: 1rem;
        font-size: 0.9rem;
    }

    /* 不同類型通知的顏色 */
    .notification-camp {
        background-color: #A8B2B9 !important;
    }

    /* 莫蘭迪灰藍 */
    .notification-stock {
        background-color: #C4B6B6 !important;
    }

    /* 莫蘭迪粉灰 */
    .notification-discussion {
        background-color: #B9C0BA !important;
    }

    /* 莫蘭迪灰綠 */
    .notification-order {
        background-color: #B6A6A6 !important;
    }

    /* 莫蘭迪玫瑰 */

    /* 待處理事項的莫蘭迪色系 */
    .pending-camps-badge {
        background-color: #A8B2B9 !important;
        /* 莫蘭迪灰藍 - 營地申請 */
    }

    .low-stock-badge {
        background-color: #C4B6B6 !important;
        /* 莫蘭迪粉灰 - 庫存警告 */
    }

    .pending-discussions-badge {
        background-color: #B9C0BA !important;
        /* 莫蘭迪灰綠 - 待回覆評論 */
    }

    /* 共同樣式 */
    .pending-badge {
        color: #fff;
        font-weight: 500;
        padding: 0.2rem 0.5rem 0.2rem 0.8rem;
        border-radius: 6px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        margin-right: 0.2rem;
    }

    .pending-badge:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>


<!-- Dashboard UI -->
<div class="container-fluid px-4">
    <!-- 頁面標題與工具列 -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0"> 營運分析中心</h1>
            <small class="text-muted">CampExplorer 營運數據即時監控</small>
        </div>
        <div class="dashboard-tools">
            <span class="date-display me-3">
                <i class="far fa-clock me-1"></i><?= date('Y-m-d H:i') ?>
            </span>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> 更新數據
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="exportDashboardData()">
                    <i class="fas fa-download"></i> 匯出報表
                </button>
            </div>
        </div>
    </div>

    <!-- 待處理事項提醒 -->
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> 待處理事項提醒</h4>
        <p class="mb-0">
            您有
            <span class="pending-badge pending-camps-badge">
                <?= $pending_stats['pending_camps'] ?>
            </span> 個待審核營地、
            <span class="pending-badge low-stock-badge">
                <?= $pending_stats['low_stock_products'] ?>
            </span> 個庫存不足商品、
            <span class="pending-badge pending-discussions-badge">
                <?= $pending_stats['pending_discussions'] ?>
            </span> 則待回覆評論
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- 快速操作按鈕 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body p-3">
                    <h5 class="card-title mb-3 text-monofondi">快速操作</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="index.php?page=camps_review" class="btn btn-monofondi-sage btn-sm">
                            <i class="fas fa-campground me-1"></i> 審核營地
                        </a>
                        <a href="index.php?page=approved_camps" class="btn btn-monofondi-blue btn-sm">
                            <i class="fas fa-mountain me-1"></i> 營區管理
                        </a>
                        <a href="index.php?page=product_category" class="btn btn-monofondi-gray btn-sm">
                            <i class="fas fa-tags me-1"></i> 商品類別
                        </a>
                        <a href="index.php?page=products_list" class="btn btn-monofondi-green btn-sm">
                            <i class="fas fa-box me-1"></i> 商品管理
                        </a>
                        <a href="index.php?page=orders_list" class="btn btn-monofondi-sand btn-sm">
                            <i class="fas fa-shopping-cart me-1"></i> 訂單管理
                        </a>
                        <a href="index.php?page=members_list" class="btn btn-monofondi-rose btn-sm">
                            <i class="fas fa-users me-1"></i> 會員管理
                        </a>
                        <a href="index.php?page=coupons_list" class="btn btn-monofondi-purple btn-sm">
                            <i class="fas fa-ticket-alt me-1"></i> 優惠券
                        </a>
                        <a href="index.php?page=articles_list" class="btn btn-monofondi-blue-gray btn-sm">
                            <i class="fas fa-newspaper me-1"></i> 文章管理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 統計卡片區 -->
    <div class="row g-4 mb-4" id="statsCards">
        <!-- 會員統計卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-morandi-blue-gradient">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">
                            <i class="fas fa-users me-2"></i>會員統計
                        </h6>
                        <h2 class="main-number"><?= number_format($users_stats['total_users']) ?></h2>
                        <div class="stats-label">總會員數</div>
                    </div>
                    <div class="icon-circle">
                        <i class="fas fa-user-chart fa-2x"></i>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="sub-number"><?= number_format($users_stats['new_users_today']) ?></div>
                        <div class="stats-label">今日新增</div>
                    </div>
                    <div>
                        <div class="sub-number"><?= number_format($users_stats['new_users_month']) ?></div>
                        <div class="stats-label">本月新增</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 營地統計卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-sage-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3">
                                <i class="fas fa-campground me-2"></i>營地統計
                            </h6>
                            <h2 class="m-0" data-stat="total_camps"><?= number_format($camps_stats['total_camps']) ?></h2>
                            <small>總營地數</small>
                        </div>
                        <div class="icon-circle">
                            <i class="fas fa-mountain-sun fa-2x text-morandi-sage"></i>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-sage"><?= $camps_stats['pending_camps'] ?></h4>
                            <small class="">待審核</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-sage"><?= $camps_stats['operating_camps'] ?></h4>
                            <small class="">營業中</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 文章統計片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-rose-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-rose">
                                <i class="fas fa-newspaper me-2"></i>文章統計
                            </h6>
                            <h2 class="mb-0"><?= number_format($articles_stats['total_articles']) ?></h2>
                            <small class="">總文章數</small>
                        </div>
                        <div class="icon-circle bg-morandi-rose">
                            <i class="fas fa-newspaper text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-rose"><?= number_format($articles_stats['total_views']) ?></h4>
                            <small class="">總瀏覽</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-rose"><?= $articles_stats['new_articles_month'] ?></h4>
                            <small class="">本月新增</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 商品統計卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-mauve-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-mauve">
                                <i class="fas fa-box me-2"></i>商品統計
                            </h6>
                            <h2 class="mb-0"><?= number_format($products_stats['total_products']) ?></h2>
                            <small class="">總商品數</small>
                        </div>
                        <div class="icon-circle bg-morandi-mauve">
                            <i class="fas fa-box text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-mauve"><?= $products_stats['low_stock'] ?></h4>
                            <small class="">庫存不足</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-mauve">$<?= number_format($products_stats['avg_price']) ?></h4>
                            <small class="">平均單價</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 營收統計卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-mint-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-mint">
                                <i class="fas fa-dollar-sign me-2"></i>營收統計
                            </h6>
                            <h2 class="mb-0">$<?= number_format($revenue_stats['total_revenue'] ?? 0) ?></h2>
                            <small class="">總營收</small>
                        </div>
                        <div class="icon-circle bg-morandi-mint">
                            <i class="fas fa-dollar-sign text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-mint">$<?= number_format($revenue_stats['today_revenue'] ?? 0) ?></h4>
                            <small class="">今日營收</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-mint"><?= number_format($revenue_stats['growth_rate'] ?? 0, 1) ?>%</h4>
                            <small class=""><?= ($revenue_stats['growth_rate'] ?? 0) >= 0 ? '月增長' : '月下降' ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 訂單統計卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-sand-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-sand">
                                <i class="fas fa-shopping-cart me-2"></i>訂單統計
                            </h6>
                            <h2 class="mb-0"><?= number_format($order_stats['total_orders']) ?></h2>
                            <small class="">總訂單數</small>
                        </div>
                        <div class="icon-circle bg-morandi-sand">
                            <i class="fas fa-shopping-cart text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-sand"><?= number_format($order_stats['pending_orders']) ?></h4>
                            <small class="">待處理</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-sand">$<?= number_format($revenue_stats['today_revenue'], 0) ?></h4>
                            <small class="">今日營收</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 訂單轉換率卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-purple-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-purple">
                                <i class="fas fa-chart-line me-2"></i>轉換率
                            </h6>
                            <h2 class="mb-0"><?= number_format($conversion_stats['conversion_rate'], 1) ?>%</h2>
                            <small class="">購買轉換率</small>
                        </div>
                        <div class="icon-circle bg-morandi-purple">
                            <i class="fas fa-percentage text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-purple"><?= number_format($conversion_stats['total_buyers']) ?></h4>
                            <small class="">總購買人數</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-purple">$<?= number_format($conversion_stats['avg_order_amount']) ?></h4>
                            <small class="">平均訂單金額</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 會員活躍卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-gray-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-gray">
                                <i class="fas fa-user-check me-2"></i>活躍度
                            </h6>
                            <h2 class="mb-0"><?= number_format($activity_stats['active_rate'], 1) ?>%</h2>
                            <small class="">月活躍率</small>
                        </div>
                        <div class="icon-circle bg-morandi-gray">
                            <i class="fas fa-chart-bar text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-gray"><?= number_format($activity_stats['active_users_today']) ?></h4>
                            <small class="">今日活動</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-gray"><?= number_format($activity_stats['active_users_week']) ?></h4>
                            <small class="">週活躍</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 圖表區域 -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">營收趨勢</h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-white active" data-period="week">週</button>
                        <button class="btn btn-sm btn-white" data-period="month">月</button>
                        <button class="btn btn-sm btn-white" data-period="year">年</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">營地分布</h6>
                </div>
                <div class="card-body">
                    <canvas id="campDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 圖表區域 -->
    <div class="row mb-4">
        <div class="col-xl-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">近期活動紀錄</h6>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" style="width: auto;" id="activityFilter">
                            <option value="all">全部活動</option>
                            <option value="camp_review">營地審核</option>
                            <option value="article">文章管理</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="timeline">
                        <?php while ($activity = $result_activities->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="timeline-item" data-type="<?= $activity['type'] ?>">
                                <div class="timeline-marker">
                                    <div class="timeline-icon bg-<?= $activity['type'] === 'camp_review' ? 'morandi-blue' : 'morandi-sage' ?>">
                                        <i class="fas fa-<?= $activity['type'] === 'camp_review' ? 'campground' : 'newspaper' ?>"></i>
                                    </div>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header p-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?= $activity['type'] === 'camp_review' ? 'info' : 'success' ?> rounded-pill">
                                                    <?= $activity['type'] === 'camp_review' ? '營地審核' : '文章管理' ?>
                                                </span>
                                                <small class="text-muted ms-2">
                                                    <time datetime="<?= $activity['time'] ?>"><?= date('F j, Y g:i A', strtotime($activity['time'])) ?></time>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-user-edit me-1"></i><?= htmlspecialchars($activity['admin_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="timeline-body p-3">
                                        <h6 class="mb-2"><?= htmlspecialchars($activity['description']) ?></h6>
                                        <div class="activity-detail p-2 bg-light rounded">
                                            <?= htmlspecialchars($activity['detail']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <!-- 商品分類統計卡片 -->
            <div class="card mb-4">
                <div class="card-header bg-morandi-teal d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">商品分類統計</h6>
                    <span class="badge bg-morandi-teal-light"><?= $result_categories->rowCount() ?> 個分類</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="">
                                <tr class="bg-white">
                                    <th>分類名稱</th>
                                    <th class="text-center">商品數量</th>
                                    <th class="text-end">平均單價</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $result_categories->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>
                                            <span class="category-name"><?= htmlspecialchars($category['category_name']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-morandi-teal"><?= number_format($category['total_products']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-morandi-teal">$<?= number_format($category['avg_price']) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 新增快速篩選器 -->
    <div class="mb-4">
        <div class="btn-group">
            <button class="btn btn-outline-morandi-teal active" data-filter="all">全部</button>
            <button class="btn btn-outline-morandi-teal" data-filter="today">今日</button>
            <button class="btn btn-outline-morandi-teal" data-filter="week">本週</button>
            <button class="btn btn-outline-morandi-teal" data-filter="month">本月</button>
        </div>
    </div>

    <!-- 通知 Toast 容器 -->
    <!-- <div id="notificationContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div class="toast-container">
            <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-bell me-2 text-white"></i>
                    <strong class="me-auto text-white notification-title">系統通知</strong>
                    <small class="text-white opacity-75">剛剛</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body bg-white notification-message">
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<!-- 在適當位置添加 Sortable.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

<!-- 自定義 JavaScript -->
<script>
    // 匯出 CSV
    function exportTableToCSV(tableId) {
        const table = document.getElementById(tableId);
        let csv = [];

        // 取得表頭
        const headers = [];
        const headerCells = table.querySelectorAll('thead th');
        headerCells.forEach(cell => headers.push(cell.textContent.trim()));
        csv.push(headers.join(','));

        // 取得資料
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const data = [];
            const cells = row.querySelectorAll('td');
            cells.forEach(cell => data.push(cell.textContent.trim()));
            csv.push(data.join(','));
        });

        // 下載 CSV
        const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${tableId}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // 定時更新時間顯示
    setInterval(() => {
        const dateDisplay = document.querySelector('.date-display');
        if (dateDisplay) {
            const now = new Date();
            const dateStr = now.toLocaleString('zh-TW', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
            // 使用 i 標籤來顯示圖標
            dateDisplay.innerHTML = `<i class="far fa-clock me-1"></i>${dateStr}`;
        }
    }, 1000);

    // 匯出功能的基本框架
    function exportDashboardData() {
        try {
            // 收集所有統計數據
            const statsData = {
                '用戶統計': {
                    '總用戶數': document.querySelector('.bg-morandi-blue-gradient h2')?.textContent,
                    '本月新增': document.querySelector('.bg-morandi-blue-gradient .text-morandi-blue')?.textContent,
                    '今日新增': document.querySelector('.bg-morandi-blue-gradient .text-morandi-blue:last-child')?.textContent
                },
                '營地統計': {
                    '總營地數': document.querySelector('.bg-morandi-rose-gradient h2')?.textContent,
                    '營運中': document.querySelector('.bg-morandi-rose-gradient .text-morandi-rose')?.textContent,
                    '待審核': document.querySelector('.bg-morandi-rose-gradient .text-morandi-rose:last-child')?.textContent
                },
                '商品統計': {
                    '總商品數': document.querySelector('.bg-morandi-mauve-gradient h2')?.textContent,
                    '庫存不足': document.querySelector('.text-morandi-mauve')?.textContent,
                    '平均單價': document.querySelector('.text-morandi-mauve:last-child')?.textContent
                },
                '營收統計': {
                    '總營收': document.querySelector('.bg-morandi-mint-gradient h2')?.textContent,
                    '今日營收': document.querySelector('.text-morandi-mint')?.textContent,
                    '月增長率': document.querySelector('.text-morandi-mint:last-child')?.textContent
                }
            };

            // 轉換為 CSV 格式
            let csv = '統計類別,項目,數值\n';

            for (const category in statsData) {
                for (const item in statsData[category]) {
                    const value = statsData[category][item]?.trim() || 'N/A';
                    csv += `${category},${item},${value}\n`;
                }
            }

            // 檢查是否有數據
            const hasData = Object.values(statsData).some(category =>
                Object.values(category).some(value => value && value !== 'N/A')
            );

            if (!hasData) {
                throw new Error('無法獲取統計數據');
            }

            // 下載檔案
            const blob = new Blob(['\ufeff' + csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const date = new Date().toLocaleDateString('zh-TW').replace(/\//g, '');
            link.href = URL.createObjectURL(blob);
            link.download = `營運報表_${date}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire({
                icon: 'success',
                title: '匯出成功',
                text: '報表已成功下載',
                timer: 1500
            });
        } catch (error) {
            console.error('匯出失敗:', error);
            Swal.fire({
                icon: 'error',
                title: '匯出失敗',
                text: error.message || '請稍後再試'
            });
        }
    }
    // 修改表格初始化代碼
    document.addEventListener('DOMContentLoaded', function() {
        // 儲存圖表實例
        let charts = {
            revenue: null,
            distribution: null
        };

        // 銷毀現有圖表
        function destroyCharts() {
            Object.values(charts).forEach(chart => {
                if (chart) {
                    chart.destroy();
                }
            });
        }

        // 初始化圖表
        function initCharts() {
            destroyCharts();

            // 營收趨勢圖
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                charts.revenue = new Chart(revenueCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                        datasets: [{
                            label: '營收趨勢',
                            data: <?= json_encode(array_values($revenue_months)) ?>,
                            borderColor: '#7A90A8',
                            backgroundColor: 'rgba(122, 144, 168, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `營收: $${context.raw.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 營地布圖
            const distributionCtx = document.getElementById('campDistributionChart');
            if (distributionCtx) {
                charts.distribution = new Chart(distributionCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['北部', '中部', '南部', '東部', '離島'],
                        datasets: [{
                            data: [30, 25, 20, 15, 10],
                            backgroundColor: [
                                '#7A90A8', // 莫蘭迪藍
                                '#8FA977', // 莫蘭迪灰綠
                                '#C69B97', // 莫蘭迪玫瑰
                                '#C4A687', // 莫蘭迪沙
                                '#A3C5C9' // 莫蘭迪藍綠
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '60%', // 整甜甜圈的寬度
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                enabled: true,
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${context.label}: ${value} (${percentage}%`;
                                    }
                                }
                            },
                            // 新增: 直接在圖表上顯示數據
                            datalabels: {
                                color: '#ffffff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                textAlign: 'center',
                                textStrokeColor: '#000000',
                                textStrokeWidth: 1,
                                formatter: function(value, context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${context.chart.data.labels[context.dataIndex]}\n${percentage}%`;
                                },
                                // 調整標籤位置
                                anchor: 'center',
                                align: 'center',
                                offset: 0,
                                // 添加文字陰影效果
                                textShadowBlur: 5,
                                textShadowColor: 'rgba(0,0,0,0.35)',
                            }
                        },
                        // 新增: 動畫設定
                        animation: {
                            animateScale: true,
                            animateRotate: true,
                            duration: 2000,
                            easing: 'easeInOutQuart'
                        },
                        // 新增: 互動設定
                        interaction: {
                            mode: 'nearest'
                        },
                        // 新增: 佈局設定
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10
                            }
                        }
                    }
                });
            }
        }

        // 初化圖表
        initCharts();

        // 處理時間區間切換
        document.querySelectorAll('[data-period]').forEach(button => {
            button.addEventListener('click', function() {
                // 移除其他按鈕的 active 狀態
                document.querySelectorAll('[data-period]').forEach(btn => {
                    btn.classList.remove('active');
                });
                // 添加當前按鈕的 active 狀態
                this.classList.add('active');

                // 重新初始化圖表
                initCharts();
            });
        });
    });

    // 將原有的setInterval部分修改為:
    function updateDashboardStats(stats) {
        // 更新會員統計
        if (stats.users) {
            const totalUsersEl = document.querySelector('.stats-card .main-number');
            const newUsersTodayEl = document.querySelector('.stats-card .sub-number');

            if (totalUsersEl) totalUsersEl.textContent = stats.users.total.toLocaleString();
            if (newUsersTodayEl) newUsersTodayEl.textContent = stats.users.today.toLocaleString();
        }

        // 更新待處理事項提醒
        if (stats.pending) {
            const pendingCampsEl = document.querySelector('.alert .badge.bg-danger');
            const lowStockEl = document.querySelector('.alert .badge.bg-warning');
            const pendingDiscussionsEl = document.querySelector('.alert .badge.bg-info');

            if (pendingCampsEl) pendingCampsEl.textContent = stats.camps.pending;
            if (lowStockEl) lowStockEl.textContent = stats.products.low_stock;
            if (pendingDiscussionsEl) pendingDiscussionsEl.textContent = stats.discussions.pending;
        }

        // 更新時間顯示
        const timeDisplay = document.querySelector('.date-display');
        if (timeDisplay) {
            const now = new Date();
            timeDisplay.innerHTML = `<i class="far fa-clock me-1"></i>${now.toLocaleString('zh-TW', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            })}`;
        }
    }

    // 定時更新數據
    async function fetchDashboardStats() {
        try {
            const response = await fetch('api/dashboard/stats.php');
            if (!response.ok) throw new Error('Network response was not ok');
            const stats = await response.json();
            updateDashboardStats(stats);
        } catch (error) {
            console.error('更新儀表板數據失敗:', error);
        }
    }

    // 每60秒更新一次數據
    setInterval(fetchDashboardStats, 60000);

    // 頁面載入時先執行一次
    document.addEventListener('DOMContentLoaded', fetchDashboardStats);

    // 新增 SPA 路由處理
    document.addEventListener('DOMContentLoaded', function() {
        // 移原有的 preventDefault AJAX 
        document.querySelectorAll('.quick-action').forEach(button => {
            button.addEventListener('click', function(e) {
                // 不阻止默認行為，讓連結正常跳轉
                const url = this.getAttribute('href');
                window.location.href = url;
            });
        });
    });

    // 初始化頁面功能
    function initPageFunctions() {
        // 重新初始化 Bootstrap 組件
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // 重新初始化其他必要的功能
        if (typeof initDataTable === 'function') {
            initDataTable();
        }
    }

    // 新增數字動畫效果
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // 新增載入狀態管理
    function showLoading(element) {
        element.classList.add('loading');
    }

    function hideLoading(element) {
        element.classList.remove('loading');
    }

    // 新增資料更新通知
    function showUpdateNotification(message) {
        const toast = new bootstrap.Toast(document.getElementById('liveToast'));
        document.querySelector('#liveToast .toast-body').textContent = message;
        toast.show();
    }

    // 新增圖表互動性
    function enhanceChartInteractivity(chart) {
        chart.options.onHover = (event, elements) => {
            if (elements && elements.length) {
                document.body.style.cursor = 'pointer';
            } else {
                document.body.style.cursor = 'default';
            }
        };
    }

    // 初始化所有增強功能
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化所有工具提示
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // 為所有數字添加動畫
        document.querySelectorAll('.card h2').forEach(element => {
            const value = parseInt(element.textContent.replace(/[^0-9]/g, ''));
            element.textContent = '0';
            animateValue(element, 0, value, 1000);
        });

        // 添加卡片載入動畫延遲
        document.querySelectorAll('.card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });

    // 註冊 datalabels 插件
    Chart.register(ChartDataLabels);

    // 活動記錄相關功能
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化活動過濾器
        const activityFilter = document.getElementById('activityFilter');
        if (activityFilter) {
            activityFilter.addEventListener('change', function() {
                filterActivities(this.value);
            });
        }

        // 初始化全部折疊按鈕
        const collapseAllBtn = document.getElementById('collapseAllBtn');
        if (collapseAllBtn) {
            collapseAllBtn.addEventListener('click', toggleAllTimelineItems);
        }

        // 時間軸添加動畫
        animateTimeline();
    });

    // 過濾活動
    function filterActivities(type) {
        const items = document.querySelectorAll('.timeline-item');
        items.forEach(item => {
            if (type === 'all' || item.dataset.type === type) {
                item.style.display = '';
                item.style.animation = 'fadeInUp 0.5s ease-out';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // 切換所有項目的折疊狀態
    function toggleAllTimelineItems() {
        const button = document.getElementById('collapseAllBtn');
        const icon = button.querySelector('i');
        const timelineItems = document.querySelectorAll('.timeline-item');
        const isCollapsed = icon.classList.contains('fa-expand-alt'); // 注意這裡的邏輯改變

        timelineItems.forEach(item => {
            const content = item.querySelector('.timeline-content-body');
            const itemBtn = item.querySelector('.timeline-collapse-btn i');

            if (isCollapsed) {
                // 展開
                content.style.maxHeight = `${content.scrollHeight}px`;
                content.style.opacity = '1';
                if (itemBtn) {
                    itemBtn.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            } else {
                // 折疊
                content.style.maxHeight = '0';
                content.style.opacity = '0';
                if (itemBtn) {
                    itemBtn.classList.replace('fa-chevron-down', 'fa-chevron-up');
                }
            }
        });

        // 更新主按鈕狀態
        if (isCollapsed) {
            icon.classList.replace('fa-expand-alt', 'fa-compress-alt');
            button.title = '全部折疊';
        } else {
            icon.classList.replace('fa-compress-alt', 'fa-expand-alt');
            button.title = '全部展開';
        }
    }

    // 切換單個項目的折疊狀態
    function toggleTimelineItem(button) {
        const content = button.closest('.timeline-content').querySelector('.timeline-content-body');
        const icon = button.querySelector('i');
        const isCollapsed = content.style.maxHeight === '0px' || !content.style.maxHeight;

        if (isCollapsed) {
            // 展開
            content.style.maxHeight = `${content.scrollHeight}px`;
            content.style.opacity = '1';
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
        } else {
            // 折疊
            content.style.maxHeight = '0';
            content.style.opacity = '0';
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        }

        // 檢查所有項目狀態並更新主按鈕
        updateMainButtonState();
    }

    // 新增：更新主按鈕狀態的函數
    function updateMainButtonState() {
        const allContents = document.querySelectorAll('.timeline-content-body');
        const mainButton = document.getElementById('collapseAllBtn');
        const mainIcon = mainButton.querySelector('i');

        const allCollapsed = Array.from(allContents)
            .every(content => content.style.maxHeight === '0px' || !content.style.maxHeight);
        const allExpanded = Array.from(allContents)
            .every(content => content.style.maxHeight && content.style.maxHeight !== '0px');

        if (allCollapsed) {
            mainIcon.classList.replace('fa-compress-alt', 'fa-expand-alt');
            mainButton.title = '全部展開';
        } else if (allExpanded) {
            mainIcon.classList.replace('fa-expand-alt', 'fa-compress-alt');
            mainButton.title = '全部折疊';
        }
    }

    // 添加時間軸動畫
    function animateTimeline() {
        const items = document.querySelectorAll('.timeline-item');
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.style.animation = 'fadeInUp 0.5s ease-out forwards';
        });
    }

    // 格式化時間顯示
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
            if (diffHours === 0) {
                const diffMinutes = Math.floor(diffTime / (1000 * 60));
                return `${diffMinutes} 分鐘前`;
            }
            return `${diffHours} 小時前`;
        } else if (diffDays < 7) {
            return `${diffDays} 天前`;
        } else {
            return date.toLocaleDateString('zh-TW');
        }
    }

    // 時間篩選功能
    document.addEventListener('DOMContentLoaded', function() {
        // 獲取所有篩選按鈕
        const filterButtons = document.querySelectorAll('[data-filter]');

        // 為每個按鈕添加點擊事件
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // 移除所有按鈕的 active 狀態
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // 添加當前按鈕的 active 狀態
                this.classList.add('active');

                // 執行篩選
                filterActivitiesByDate(this.dataset.filter);
            });
        });
    });

    // 篩選活動記錄
    function filterActivitiesByDate(filter) {
        const activities = document.querySelectorAll('.timeline-item');
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - today.getDay()); // 設置到本週日
        const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

        activities.forEach(activity => {
            const activityDate = new Date(activity.querySelector('small time').getAttribute('datetime'));
            let show = false;

            switch (filter) {
                case 'all':
                    show = true;
                    break;
                case 'today':
                    show = activityDate >= today;
                    break;
                case 'week':
                    show = activityDate >= weekStart;
                    break;
                case 'month':
                    show = activityDate >= monthStart;
                    break;
            }

            // 使用動畫效果顯示/隱藏
            if (show) {
                activity.style.display = '';
                activity.style.animation = 'fadeInUp 0.5s ease-out';
            } else {
                activity.style.display = 'none';
            }
        });

        // 如果沒有顯示的項目，顯示提示訊息
        const visibleActivities = document.querySelectorAll('.timeline-item[style="display: none;"]');
        const timelineContainer = document.querySelector('.timeline');
        const noDataMessage = timelineContainer.querySelector('.no-data-message');

        if (visibleActivities.length === activities.length) {
            if (!noDataMessage) {
                const message = document.createElement('div');
                message.className = 'no-data-message text-center py-4 text-muted';
                message.innerHTML = '此時間範圍內無活動記錄';
                timelineContainer.appendChild(message);
            }
        } else if (noDataMessage) {
            noDataMessage.remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // 修改：添加元素存在性检查
        const timeline = document.querySelector('.timeline');
        const toggleBtn = document.getElementById('collapseAllBtn'); // 更正ID名称
        const filterBtns = document.querySelectorAll('[data-filter]');

        // 只在元素存在时执行相关代码
        if (toggleBtn && timeline) {
            let isCollapsed = false;

            // 切换折叠状态
            toggleBtn.addEventListener('click', function() {
                isCollapsed = !isCollapsed;
                timeline.classList.toggle('collapsed', isCollapsed);
                toggleBtn.querySelector('i').classList.toggle('fa-chevron-down', !isCollapsed);
                toggleBtn.querySelector('i').classList.toggle('fa-chevron-up', isCollapsed);
            });
        }

        // 日期筛选功能
        if (filterBtns.length && timeline) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // ... 现有的筛选代码 ...
                });
            });
        }
    });

    // 修正：移除重複的事件监听器
    // 删除或合并重复的 DOMContentLoaded 事件处理程序

    // 将所有辅助函数移到全局作用域
    function isSameDay(d1, d2) {
        return d1.getDate() === d2.getDate() &&
            d1.getMonth() === d2.getMonth() &&
            d1.getFullYear() === d2.getFullYear();
    }

    function isThisWeek(d1, d2) {
        const weekStart = new Date(d2);
        weekStart.setDate(d2.getDate() - d2.getDay());
        weekStart.setHours(0, 0, 0, 0);
        return d1 >= weekStart;
    }

    function isSameMonth(d1, d2) {
        return d1.getMonth() === d2.getMonth() &&
            d1.getFullYear() === d2.getFullYear();
    }

    function showNoDataMessage(container) {
        if (!container.querySelector('.no-data-message')) {
            const message = document.createElement('div');
            message.className = 'no-data-message text-center py-4 text-muted';
            message.innerHTML = '此時間範圍內無活動記錄';
            container.appendChild(message);
        }
    }

    function removeNoDataMessage(container) {
        const message = container.querySelector('.no-data-message');
        if (message) {
            message.remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('[data-filter]');
        const timeline = document.querySelector('.timeline-container');

        // 日期篩選功能
        if (filterBtns.length && timeline) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterActivitiesByDate(this.dataset.filter);
                });
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const timeline = document.querySelector('.timeline');
        const collapseBtn = document.getElementById('collapseAllBtn');

        if (collapseBtn && timeline) {
            let isCollapsed = false;

            collapseBtn.addEventListener('click', function() {
                isCollapsed = !isCollapsed;
                const timelineItems = timeline.querySelectorAll('.timeline-item');
                const icon = this.querySelector('i');

                timelineItems.forEach(item => {
                    const content = item.querySelector('.timeline-content');
                    if (content) {
                        if (isCollapsed) {
                            content.style.maxHeight = '0';
                            content.style.opacity = '0';
                        } else {
                            content.style.maxHeight = content.scrollHeight + 'px';
                            content.style.opacity = '1';
                        }
                    }
                });

                // 更新按鈕圖標和文字
                icon.className = isCollapsed ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
                this.innerHTML = `<i class="${icon.className}"></i> ${isCollapsed ? '展開全部' : '全部折疊'}`;
            });
        }
    });

    // 顯示通知的函數
    // function showNotification(title, message) {
    //     const toast = document.getElementById('liveToast');
    //     const toastTitle = toast.querySelector('strong');
    //     const toastBody = toast.querySelector('.toast-body');
    //     const time = toast.querySelector('small');

    //     // 更新內容
    //     toastTitle.textContent = title;
    //     toastBody.textContent = message;
    //     time.textContent = new Date().toLocaleTimeString();

    //     // 顯示通知
    //     const bsToast = new bootstrap.Toast(toast);
    //     bsToast.show();
    // }

    // // 使用範例
    // showNotification('系統通知', '新訂單已送達！');


    // 檢查待處理事項
    async function checkPendingItems() {
        try {
            const response = await axios.get('/CampExplorer/admin/api/dashboard/stats.php');
            const stats = response.data;

            // 檢查營地審核
            if (stats.pending_camps > 0) {
                showToastNotification(
                    '待審核營地',
                    `有 ${stats.pending_camps} 個營地待審核`
                );
            }

            // 檢查庫存
            if (stats.low_stock_products > 0) {
                showToastNotification(
                    '庫存警告',
                    `有 ${stats.low_stock_products} 個商品庫存不足`
                );
            }

            // 檢查評論
            if (stats.pending_discussions > 0) {
                showToastNotification(
                    '待回覆評論',
                    `有 ${stats.pending_discussions} 則評論待回覆`
                );
            }
        } catch (error) {
            console.error('檢查待處理事項失敗:', error);
        }
    }

    // 使用 SweetAlert2 顯示通知
    function showToastNotification(title, message, type) {
        const toastEl = document.getElementById('notificationToast');
        if (!toastEl) {
            console.error('找不到通知元素');
            return;
        }

        const titleEl = toastEl.querySelector('.notification-title');
        const messageEl = toastEl.querySelector('.notification-message');
        const headerEl = toastEl.querySelector('.toast-header');

        if (titleEl && messageEl && headerEl) {
            // 移除所有之前的顏色類別
            headerEl.className = 'toast-header';
            // 添加新的顏色類別
            headerEl.classList.add(`notification-${type}`);
            
            // 更新內容
            titleEl.textContent = title;
            messageEl.textContent = message;

            // 顯示通知
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    }

    // 等待 DOM 完全載入
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化 Toast 元素
        const toastEl = document.getElementById('notificationToast');
        if (toastEl) {
            window.notificationToast = new bootstrap.Toast(toastEl, {
                delay: 5000,
                autohide: true
            });
        }

        // 初始檢查通知
        checkNotifications();
        
        // 設置定期檢查
        setInterval(checkNotifications, 300000); // 每5分鐘檢查一次
    });

    // 顯示通知
    // function showNotification(title, message) {
    //     const toastEl = document.getElementById('notificationToast');
    //     const titleEl = document.getElementById('notificationTitle');
    //     const messageEl = document.getElementById('notificationMessage');
        
    //     if (!toastEl || !titleEl || !messageEl) {
    //         console.error('通知元素未找到');
    //         return;
    //     }
        
    //     titleEl.textContent = title;
    //     messageEl.textContent = message;
        
    //     if (window.notificationToast) {
    //         window.notificationToast.show();
    //     }
    // }

    // 檢查通知
    async function checkNotifications() {
        try {
            const response = await fetch('/CampExplorer/admin/api/dashboard/stats.php');
            const data = await response.json();
            const notifications = data.notifications;

            Object.entries(notifications).forEach(([type, count]) => {
                if (count > 0) {
                    const messages = {
                        new_camps: `有 ${count} 個新的營地申請待審核`,
                        low_stock: `有 ${count} 個商品庫存不足`,
                        new_discussions: `有 ${count} 則新討論待回覆`,
                        new_orders: `有 ${count} 筆新訂單待處理`,
                        new_users: `今日有 ${count} 位新用戶註冊`
                    };
                    
                    if (messages[type]) {
                        showNotification('系統通知', messages[type]);
                    }
                }
            });
        } catch (error) {
            console.error('檢查通知失敗:', error);
        }
    }

    // 初始化先前狀態
    let previousStats = {
        pending_camps: 0,
        low_stock_products: 0,
        pending_discussions: 0
    };

    // 檢查更新的函數
    async function checkPendingChanges() {
        try {
            const response = await fetch('/CampExplorer/admin/api/dashboard/check-pending.php');
            const currentStats = await response.json();

            // 比較並顯示通知
            if (currentStats.pending_camps > previousStats.pending_camps) {
                showNotification(
                    '新營地申請',
                    `新增 ${currentStats.pending_camps - previousStats.pending_camps} 個營地申請待審核`
                );
            }

            if (currentStats.low_stock_products > previousStats.low_stock_products) {
                showNotification(
                    '庫存警告',
                    `新增 ${currentStats.low_stock_products - previousStats.low_stock_products} 個商品庫存不足`
                );
            }

            if (currentStats.pending_discussions > previousStats.pending_discussions) {
                showNotification(
                    '新討論待回覆',
                    `新增 ${currentStats.pending_discussions - previousStats.pending_discussions} 則評論待回覆`
                );
            }

            // 更新先前狀態
            previousStats = currentStats;

        } catch (error) {
            console.error('檢查更新失敗:', error);
        }
    }

    // 初始化通知系統
    document.addEventListener('DOMContentLoaded', function() {
        // 創建通知容器
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1050';
        toastContainer.innerHTML = `
            <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header morandiColor">
                    <i class="fas fa-bell me-2 text-white"></i>
                    <strong id="notificationTitle" class="me-auto text-white">系統通知</strong>
                    <small class="text-white">剛剛</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div id="notificationMessage" class="toast-body"></div>
            </div>
        `;
        document.body.appendChild(toastContainer);

        // 初始化 Bootstrap Toast
        const toast = new bootstrap.Toast(document.getElementById('notificationToast'), {
            delay: 5000,
            autohide: true
        });

        // 定義全局通知函數
        window.showNotification = function(title, message) {
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            
            if (titleEl && messageEl) {
                titleEl.textContent = title;
                messageEl.textContent = message;
                toast.show();
            }
        };

        // 開始檢查通知
        checkPendingChanges();
        setInterval(checkPendingChanges, 30000);
    });

    // 等待 DOM 和 Bootstrap 完全載入
    window.addEventListener('load', function() {
        // 檢查並創建通知容器
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1050';
            toastContainer.innerHTML = `
                <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header morandiColor">
                        <i class="fas fa-bell me-2 text-white"></i>
                        <strong id="notificationTitle" class="me-auto text-white">系統通知</strong>
                        <small class="text-white">剛剛</small>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div id="notificationMessage" class="toast-body"></div>
                </div>
            `;
            document.body.appendChild(toastContainer);
        }

        // 初始化 Toast
        const toastEl = document.getElementById('notificationToast');
        if (toastEl) {
            window.notificationToast = new bootstrap.Toast(toastEl);
            // 開始檢查通知
            checkPendingChanges();
            setInterval(checkPendingChanges, 30000);
        }
    });

    // 初始化拖曳功能
    document.addEventListener('DOMContentLoaded', function() {
        const statsCards = document.getElementById('statsCards');
        if (statsCards) {
            new Sortable(statsCards, {
                animation: 150,  // 動畫時間(毫秒)
                ghostClass: 'sortable-ghost',  // 拖曳時的樣式類
                dragClass: 'sortable-drag',    // 拖曳中的樣式類
                handle: '.card',  // 指定可拖曳的區域
                onEnd: function(evt) {
                    // 儲存新的排序順序到 localStorage
                    const cards = Array.from(statsCards.children).map(card => {
                        return card.querySelector('.card-title')?.textContent.trim() || '';
                    });
                    localStorage.setItem('statsCardsOrder', JSON.stringify(cards));
                }
            });

            // 載入儲存的排序順序
            const savedOrder = localStorage.getItem('statsCardsOrder');
            if (savedOrder) {
                try {
                    const order = JSON.parse(savedOrder);
                    const cardsArray = Array.from(statsCards.children);
                    const orderedCards = [];
                    
                    order.forEach(title => {
                        const card = cardsArray.find(card => 
                            card.querySelector('.card-title')?.textContent.trim() === title
                        );
                        if (card) {
                            orderedCards.push(card);
                        }
                    });

                    // 重新排序卡片
                    orderedCards.forEach(card => statsCards.appendChild(card));
                } catch (e) {
                    console.error('Error restoring cards order:', e);
                }
            }
        }
    });

    // 添加拖曳時的視覺效果
    const style = document.createElement('style');
    style.textContent = `
        .sortable-ghost {
            opacity: 0.4;
            background: #F0F0F0;
        }

        .sortable-drag {
            opacity: 0.9;
            transform: scale(1.05);
            cursor: grabbing !important;
        }

        .card {
            cursor: grab;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card:active {
            cursor: grabbing;
        }
    `;
    document.head.appendChild(style);
</script>

<!-- 通知容器 -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-bell me-2 text-white"></i>
            <strong id="notificationTitle" class="me-auto text-white">系統通知</strong>
            <small class="text-white">剛剛</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div id="notificationMessage" class="toast-body"></div>
    </div>
</div>