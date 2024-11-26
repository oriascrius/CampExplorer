<?php
require_once '../../camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

try {
    // 獲取會員的所有訂單
    $sql = "SELECT 
        po.order_id,
        po.total_amount,
        po.payment_status,
        po.order_status,
        po.created_at,
        COUNT(pod.id) as item_count
    FROM product_orders po
    LEFT JOIN product_order_details pod ON po.order_id = pod.order_id
    WHERE po.member_id = ?
    GROUP BY po.order_id
    ORDER BY po.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的訂單 - CampExplorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .order-card {
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.3rem 0.6rem;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4">我的訂單</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-receipt" style="font-size: 4rem; color: #ccc;"></i>
                    <h3 class="mt-3">尚無訂單記錄</h3>
                    <p class="text-muted">去購物建立您的第一筆訂單吧！</p>
                    <a href="/CampExplorer/products/product-list.php" class="btn btn-primary">
                        <i class="bi bi-cart-plus"></i> 去購物
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card order-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <h5 class="mb-1">訂單編號</h5>
                                <p class="mb-0">#<?= str_pad($order['order_id'], 8, '0', STR_PAD_LEFT) ?></p>
                            </div>
                            <div class="col-md-2">
                                <h5 class="mb-1">訂購日期</h5>
                                <p class="mb-0"><?= date('Y/m/d', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="col-md-2">
                                <h5 class="mb-1">商品數量</h5>
                                <p class="mb-0"><?= $order['item_count'] ?> 件</p>
                            </div>
                            <div class="col-md-2">
                                <h5 class="mb-1">訂單金額</h5>
                                <p class="mb-0">$<?= number_format($order['total_amount']) ?></p>
                            </div>
                            <div class="col-md-2">
                                <h5 class="mb-1">訂單狀態</h5>
                                <?php
                                $orderStatusClass = match($order['order_status']) {
                                    0 => 'bg-warning',    // 待處理
                                    1 => 'bg-info',       // 處理中
                                    2 => 'bg-success',    // 已完成
                                    3 => 'bg-danger',     // 已取消
                                    default => 'bg-secondary'
                                };
                                $orderStatusText = match($order['order_status']) {
                                    0 => '待處理',
                                    1 => '處理中',
                                    2 => '已完成',
                                    3 => '已取消',
                                    default => '未知'
                                };
                                ?>
                                <span class="badge <?= $orderStatusClass ?> status-badge">
                                    <?= $orderStatusText ?>
                                </span>
                                <?php if ($order['payment_status'] == 1): ?>
                                    <span class="badge bg-success status-badge">已付款</span>
                                <?php elseif ($order['payment_status'] == 2): ?>
                                    <span class="badge bg-info status-badge">已退款</span>
                                <?php else: ?>
                                    <span class="badge bg-warning status-badge">未付款</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="order-detail.php?id=<?= $order['order_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    查看詳情
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>