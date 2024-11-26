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

    // 商品分類統計
    $sql_categories = "SELECT 
        c.name as category_name,
        COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY product_count DESC";
    $result_categories = $db->query($sql_categories);

    // 新增最近活動查詢
    $sql_recent_activities = "SELECT 
        'camp_review' as type,
        cr.reviewed_at as time,
        CONCAT('審核了營地申請 #', cr.application_id) as description,
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
        SUM(CASE WHEN payment_status = 1 THEN total_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 1 AND DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue,
        COUNT(CASE WHEN payment_status = 1 THEN 1 END) as total_orders,
        COUNT(CASE WHEN payment_status = 0 THEN 1 END) as pending_orders,
        (
            SUM(CASE WHEN payment_status = 1 AND MONTH(created_at) = MONTH(CURDATE()) THEN total_amount ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN payment_status = 1 AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN total_amount ELSE 0 END), 0) * 100 - 100
        ) as growth_rate
    FROM product_orders";

    $result_revenue = $db->query($sql_revenue);
    $revenue_stats = $result_revenue->fetch(PDO::FETCH_ASSOC);

    // 訂單狀態統計
    $sql_order_stats = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN order_status = 0 THEN 1 END) as pending_orders,
        COUNT(CASE WHEN order_status = 1 THEN 1 END) as processing_orders,
        COUNT(CASE WHEN order_status = 2 THEN 1 END) as completed_orders,
        COUNT(CASE WHEN order_status = 3 THEN 1 END) as cancelled_orders,
        COUNT(CASE WHEN payment_status = 0 THEN 1 END) as unpaid_orders,
        COUNT(CASE WHEN payment_status = 1 THEN 1 END) as paid_orders,
        COUNT(CASE WHEN payment_status = 2 THEN 1 END) as refunded_orders
    FROM product_orders";

    $result_order_stats = $db->query($sql_order_stats);
    $order_stats = $result_order_stats->fetch(PDO::FETCH_ASSOC);

    // 訂單轉換率統計
    $sql_conversion = "SELECT 
        COUNT(DISTINCT po.user_id) as total_buyers,
        (COUNT(DISTINCT po.user_id) * 100.0 / (SELECT COUNT(*) FROM users)) as conversion_rate,
        AVG(po.total_amount) as avg_order_amount,
        COUNT(CASE WHEN DATE(po.created_at) = CURDATE() THEN 1 END) as orders_today
    FROM product_orders po
    WHERE po.payment_status = 1";

    $result_conversion = $db->query($sql_conversion);
    $conversion_stats = $result_conversion->fetch(PDO::FETCH_ASSOC);

    // 會員活躍度統計
    $sql_activity = "SELECT 
        COUNT(DISTINCT ua.user_id) as active_users_month,
        (COUNT(DISTINCT ua.user_id) * 100.0 / (SELECT COUNT(*) FROM users)) as active_rate,
        COUNT(DISTINCT CASE WHEN DATE(ua.created_at) = CURDATE() THEN ua.user_id END) as active_users_today,
        COUNT(DISTINCT CASE WHEN DATE(ua.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN ua.user_id END) as active_users_week
    FROM (
        SELECT user_id, created_at FROM product_orders
        UNION ALL
        SELECT user_id, created_at FROM discussions
        UNION ALL
        SELECT user_id, created_at FROM favorites
    ) as ua
    WHERE ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

    $result_activity = $db->query($sql_activity);
    $activity_stats = $result_activity->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 添加錯誤處理，設置預設值
    $conversion_stats = [
        'total_buyers' => 0,
        'conversion_rate' => 0,
        'avg_order_amount' => 0,
        'orders_today' => 0
    ];
    
    $activity_stats = [
        'active_users_month' => 0,
        'active_rate' => 0,
        'active_users_today' => 0,
        'active_users_week' => 0
    ];
    
    error_log("Dashboard Stats Error: " . $e->getMessage());
}
?>

<!-- Dashboard UI -->
<div class="container-fluid px-4">
    <!-- 頁面標題與工具列 -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="h3 mb-0">營運分析中心</h1>
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
            <span class="badge bg-danger"><?= $pending_stats['pending_camps'] ?></span> 個待審核營地、
            <span class="badge bg-warning"><?= $pending_stats['low_stock_products'] ?></span> 個庫存不足商品、
            <span class="badge bg-info"><?= $pending_stats['pending_discussions'] ?></span> 則待回覆評論
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
                            <i class="fas fa-ticket-alt me-1"></i> 優券
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
    <div class="row g-4 mb-4">
        <!-- 會員統計卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-blue-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3">
                                <i class="fas fa-users me-2"></i>會員統計
                            </h6>
                            <h2 class="mb-0"><?= number_format($users_stats['total_users']) ?></h2>
                            <small>總會員數</small>
                        </div>
                        <div class="icon-circle">
                            <i class="fas fa-user-friends fa-2x"></i>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $users_stats['new_users_today'] ?></h4>
                            <small>今日新增</small>
                        </div>
                        <div>
                            <h4 class="mb-0"><?= $users_stats['new_users_month'] ?></h4>
                            <small>本月新增</small>
                        </div>
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
                            <h6 class="card-title mb-3 text-morandi-sage">
                                <i class="fas fa-campground me-2"></i>營地統計
                            </h6>
                            <h2 class="mb-0 text-dark"><?= number_format($camps_stats['total_camps']) ?></h2>
                            <small class="text-muted">營地數</small>
                        </div>
                        <div class="icon-circle bg-morandi-sage">
                            <i class="fas fa-mountain text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-sage"><?= $camps_stats['pending_camps'] ?></h4>
                            <small class="text-muted">待審核</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-sage"><?= $camps_stats['operating_camps'] ?></h4>
                            <small class="text-muted">營業中</small>
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
                            <h2 class="mb-0 text-dark"><?= number_format($articles_stats['total_articles']) ?></h2>
                            <small class="text-muted">總文章數</small>
                        </div>
                        <div class="icon-circle bg-morandi-rose">
                            <i class="fas fa-newspaper text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-rose"><?= number_format($articles_stats['total_views']) ?></h4>
                            <small class="text-muted">總瀏覽</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-rose"><?= $articles_stats['new_articles_month'] ?></h4>
                            <small class="text-muted">本月新增</small>
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
                            <h2 class="mb-0 text-dark"><?= number_format($products_stats['total_products']) ?></h2>
                            <small class="text-muted">總商品數</small>
                        </div>
                        <div class="icon-circle bg-morandi-mauve">
                            <i class="fas fa-box text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-mauve"><?= $products_stats['low_stock'] ?></h4>
                            <small class="text-muted">庫存不足</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-mauve">$<?= number_format($products_stats['avg_price']) ?></h4>
                            <small class="text-muted">平均單價</small>
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
                            <h2 class="mb-0 text-dark">$<?= number_format($revenue_stats['total_revenue'] ?? 0) ?></h2>
                            <small class="text-muted">總營收</small>
                        </div>
                        <div class="icon-circle bg-morandi-mint">
                            <i class="fas fa-dollar-sign text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-mint">$<?= number_format($revenue_stats['today_revenue'] ?? 0) ?></h4>
                            <small class="text-muted">今日營</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-mint"><?= number_format($revenue_stats['growth_rate'] ?? 0, 1) ?>%</h4>
                            <small class="text-muted"><?= ($revenue_stats['growth_rate'] ?? 0) >= 0 ? '月增長' : '月下降' ?></small>
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
                            <h2 class="mb-0 text-dark"><?= number_format($order_stats['total_orders']) ?></h2>
                            <small class="text-muted">總訂單數</small>
                        </div>
                        <div class="icon-circle bg-morandi-sand">
                            <i class="fas fa-shopping-cart text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-sand"><?= number_format($order_stats['pending_orders']) ?></h4>
                            <small class="text-muted">待處理</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-sand">$<?= number_format($revenue_stats['today_revenue'], 0) ?></h4>
                            <small class="text-muted">今日營收</small>
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
                            <h2 class="mb-0 text-dark"><?= number_format($conversion_stats['conversion_rate'], 1) ?>%</h2>
                            <small class="text-muted">購買轉換率</small>
                        </div>
                        <div class="icon-circle bg-morandi-purple">
                            <i class="fas fa-percentage text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-purple"><?= number_format($conversion_stats['total_buyers']) ?></h4>
                            <small class="text-muted">總購買人數</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-purple">$<?= number_format($conversion_stats['avg_order_amount']) ?></h4>
                            <small class="text-muted">平均訂單金額</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 會員活躍度卡片 -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 bg-morandi-gray-gradient">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-3 text-morandi-gray">
                                <i class="fas fa-user-check me-2"></i>活躍度
                            </h6>
                            <h2 class="mb-0 text-dark"><?= number_format($activity_stats['active_rate'], 1) ?>%</h2>
                            <small class="text-muted">月活躍率</small>
                        </div>
                        <div class="icon-circle bg-morandi-gray">
                            <i class="fas fa-chart-bar text-white fa-2x"></i>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0 text-morandi-gray"><?= number_format($activity_stats['active_users_today']) ?></h4>
                            <small class="text-muted">今日活動</small>
                        </div>
                        <div>
                            <h4 class="mb-0 text-morandi-gray"><?= number_format($activity_stats['active_users_week']) ?></h4>
                            <small class="text-muted">週活躍</small>
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
                        <button class="btn btn-sm btn-outline-secondary active" data-period="week">週</button>
                        <button class="btn btn-sm btn-outline-secondary" data-period="month">月</button>
                        <button class="btn btn-sm btn-outline-secondary" data-period="year">年</button>
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
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">近期活動紀錄</h6>
                    <div class="d-flex gap-2">
                        <button class="timeline-collapse-btn" id="collapseAllBtn">
                            <i class="fas fa-compress-alt"></i> 全部折疊
                        </button>
                        <select class="form-select form-select-sm" style="width: auto;" id="activityFilter">
                            <option value="all">全部活動</option>
                            <option value="camp_review">營地審核</option>
                            <option value="article">文章管理</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="timeline" id="activityTimeline">
                        <?php while ($activity = $result_activities->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="timeline-item" data-type="<?= $activity['type'] ?>">
                                <div class="timeline-icon bg-<?= $activity['type'] === 'camp_review' ? 'primary' : 'success' ?>">
                                    <i class="fas fa-<?= $activity['type'] === 'camp_review' ? 'check' : 'pen' ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="activity-badge <?= $activity['type'] ?>">
                                                <?= $activity['type'] === 'camp_review' ? '營地審核' : '文章管理' ?>
                                            </span>
                                            <h6><?= htmlspecialchars($activity['description']) ?></h6>
                                        </div>
                                        <button class="timeline-collapse-btn" onclick="toggleTimelineItem(this)">
                                            <i class="fas fa-chevron-up"></i>
                                        </button>
                                    </div>
                                    <div class="timeline-content-body">
                                        <p><?= htmlspecialchars($activity['detail']) ?></p>
                                        <small>
                                            <i class="far fa-clock"></i>
                                            <time datetime="<?= date('Y-m-d\TH:i:s', strtotime($activity['time'])) ?>">
                                                <?= date('Y-m-d H:i', strtotime($activity['time'])) ?>
                                            </time>
                                            <i class="far fa-user"></i>
                                            <?= htmlspecialchars($activity['admin_name']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">商品分類統計</h6>
                </div>
                <div class="card-body">
                    <?php while ($category = $result_categories->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?= htmlspecialchars($category['category_name']) ?></span>
                                <span class="text-primary"><?= $category['product_count'] ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?= ($category['product_count'] / $products_stats['total_products']) * 100 ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 新增快速篩選器 -->
    <div class="mb-4">
        <div class="btn-group">
            <button class="btn btn-outline-primary active" data-filter="all">全部</button>
            <button class="btn btn-outline-primary" data-filter="today">今日</button>
            <button class="btn btn-outline-primary" data-filter="week">本週</button>
            <button class="btn btn-outline-primary" data-filter="month">本月</button>
        </div>
    </div>

    <!-- 新增互動式提 -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-bell me-2"></i>
                <strong class="me-auto">系統通知</strong>
                <small>剛剛</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                新的系統通知...
            </div>
        </div>
    </div>
</div>

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
        max-height: 600px; /* 增加最大高度 */
        min-height: 400px; /* 設定最小高度 */
        overflow-y: auto;
        transition: all 0.3s ease;
        padding: 1rem 1.5rem;
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

    /* 活動圖標樣式 */
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
        --monofondi-sage: #9CAF88;      /* 鼠尾草綠 */
        --monofondi-blue: #8E9EAB;      /* 莫蘭迪藍 */
        --monofondi-gray: #A2A9B0;      /* 莫蘭迪灰 */
        --monofondi-green: #A5B5A3;     /* 莫蘭迪綠 */
        --monofondi-sand: #B5A898;      /* 莫蘭迪沙 */
        --monofondi-rose: #B5A3A1;      /* 莫蘭迪玫瑰 */
        --monofondi-purple: #A499B3;    /* 莫蘭迪紫 */
        --monofondi-blue-gray: #8B97A5; /* 莫蘭迪藍灰 */
    }

    /* 按鈕基本樣式 */
    .btn-sm {
        padding: 0.5rem 1rem;
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
    .card h2, .card h4 {
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
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
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
        --morandi-blue: #7A90A8;      /* 更深的莫蘭迪藍 */
        --morandi-sage: #8FA977;      /* 更鮮豔的鼠尾草綠 */
        --morandi-rose: #C69B97;      /* 更溫暖的莫蘭迪玫瑰 */
        --morandi-purple: #9B8AA6;    /* 更深的莫蘭迪紫 */
        --morandi-sand: #C4A687;      /* 更溫暖的莫蘭迪沙 */
        --morandi-mint: #89B0A3;      /* 更清新的莫蘭迪薄荷 */
        --morandi-mauve: #A68E9B;     /* 更深的莫蘭迪紫灰 */
        --morandi-gray: #8E9CAA;      /* 更深的莫蘭迪灰 */
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

    /* 卡片內容布局 */
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
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
        transform: translate(30%, -30%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* 動畫效果 */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
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
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


<!-- 自定義 JavaScript -->
<script>
    // 匯出 CSV 功能
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
        const now = new Date();
        const dateStr = now.toLocaleString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
        document.querySelector('.date-display').firstChild.textContent = dateStr;
    }, 1000);

    // 新增出儀表板數據功能
    function exportDashboardData() {
        // 實作匯出功能
    }

    // 修改圖表初始化代碼
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
                        labels: ['一月', '二月', '三月', '四月', '五月', '六月'],
                        datasets: [{
                            label: '營收金額',
                            data: [12000, 19000, 15000, 25000, 22000, 30000],
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 6,
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `營收: $${context.parsed.y.toLocaleString()}`;
                                    }
                                }
                            },
                            // 修改：datalabels 配置
                            datalabels: {
                                align: 'top',
                                anchor: 'end',
                                offset: 10,
                                backgroundColor: 'rgba(255, 255, 255, 0.85)',
                                borderRadius: 4,
                                color: '#4e73df',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                padding: {
                                    top: 4,
                                    right: 6,
                                    bottom: 4,
                                    left: 6
                                },
                                formatter: function(value) {
                                    return '$' + value.toLocaleString();
                                },
                                // 添加陰影效果
                                shadowOffsetX: 1,
                                shadowOffsetY: 1,
                                shadowBlur: 3,
                                shadowColor: 'rgba(0,0,0,0.2)',
                                // 確保標籤永遠顯示
                                display: true
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                },
                                grid: {
                                    drawBorder: false,
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        // 修改：增加上方間距以容納標籤
                        layout: {
                            padding: {
                                top: 30,
                                right: 20,
                                bottom: 10,
                                left: 20
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeOutQuart'
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }

            // 營地分布圖
            const distributionCtx = document.getElementById('campDistributionChart');
            if (distributionCtx) {
                charts.distribution = new Chart(distributionCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['北部', '中部', '南部', '東部', '離島'],
                        datasets: [{
                            data: [30, 25, 20, 15, 10],
                            backgroundColor: [
                                '#4e73df',
                                '#1cc88a',
                                '#36b9cc',
                                '#f6c23e',
                                '#e74a3b'
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
                                        return `${context.label}: ${value} (${percentage}%)`;
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

    // 增即時數更新
    setInterval(async () => {
        const response = await fetch('api/dashboard/stats');
        const stats = await response.json();
        updateDashboardStats(stats);
    }, 60000);

    // 新增 SPA 路由處理
    document.addEventListener('DOMContentLoaded', function() {
        // 移除原有的 preventDefault 和 AJAX 載入
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
        const toggleBtn = document.getElementById('collapseAllBtn');  // 更正ID名称
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

    // 修改：移除重复的事件监听器
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
            message.innerHTML = '此时间范围内无活动记录';
            container.appendChild(message);
        }
    }

    function removeNoDataMessage(container) {
        const message = container.querySelector('.no-data-message');
        if (message) {
            message.remove();
        }
    }
</script>