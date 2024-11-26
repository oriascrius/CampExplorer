<?php
require_once '../../camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// 檢查訂單ID
if (!isset($_GET['id'])) {
    header('Location: order-list.php');
    exit;
}

try {
    // 獲取訂單基本資訊
    $order_sql = "SELECT 
        po.*,
        COUNT(pod.id) as item_count
    FROM product_orders po
    LEFT JOIN product_order_details pod ON po.order_id = pod.order_id
    WHERE po.order_id = ? AND po.member_id = ?
    GROUP BY po.order_id";

    $stmt = $db->prepare($order_sql);
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('找不到訂單資訊');
    }

    // 獲取訂單商品明細
    $details_sql = "SELECT 
        pod.*,
        p.name as product_name,
        p.main_image
    FROM product_order_details pod
    JOIN products p ON pod.product_id = p.id
    WHERE pod.order_id = ?";

    $stmt = $db->prepare($details_sql);
    $stmt->execute([$_GET['id']]);
    $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單詳情 - CampExplorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .status-timeline {
            position: relative;
            padding: 20px 0;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        .status-step {
            position: relative;
            z-index: 2;
            background: white;
            padding: 0 10px;
        }
        .status-active {
            color: #0d6efd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/navbar.php'; ?>

    <div class="container py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>訂單詳情</h2>
                <a href="order-list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 返回訂單列表
                </a>
            </div>

            <!-- 訂單狀態 -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row text-center status-timeline">
                        <div class="col status-step <?= $order['order_status'] >= 0 ? 'status-active' : '' ?>">
                            <i class="bi bi-1-circle"></i><br>待處理
                        </div>
                        <div class="col status-step <?= $order['order_status'] >= 1 ? 'status-active' : '' ?>">
                            <i class="bi bi-2-circle"></i><br>處理中
                        </div>
                        <div class="col status-step <?= $order['order_status'] >= 2 ? 'status-active' : '' ?>">
                            <i class="bi bi-3-circle"></i><br>已完成
                        </div>
                        <?php if ($order['order_status'] == 3): ?>
                            <div class="col status-step status-active text-danger">
                                <i class="bi bi-x-circle"></i><br>已取消
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 訂單資訊 -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">訂單資訊</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <p class="mb-1 text-muted">訂單編號</p>
                            <p class="fw-bold">#<?= str_pad($order['order_id'], 8, '0', STR_PAD_LEFT) ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1 text-muted">訂購日期</p>
                            <p class="fw-bold"><?= date('Y/m/d H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1 text-muted">付款狀態</p>
                            <p>
                                <?php if ($order['payment_status'] == 1): ?>
                                    <span class="badge bg-success">已付款</span>
                                <?php elseif ($order['payment_status'] == 2): ?>
                                    <span class="badge bg-info">已退款</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">未付款</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1 text-muted">訂單狀態</p>
                            <p>
                                <?php
                                $orderStatusClass = match($order['order_status']) {
                                    0 => 'bg-warning',
                                    1 => 'bg-info',
                                    2 => 'bg-success',
                                    3 => 'bg-danger',
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
                                <span class="badge <?= $orderStatusClass ?>"><?= $orderStatusText ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 商品明細 -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">商品明細</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>商品資訊</th>
                                    <th>單價</th>
                                    <th>數量</th>
                                    <th>小計</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderDetails as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="/CampExplorer/uploads/products/main/<?= $item['main_image'] ?? 'default-product.jpg' ?>"
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                     class="product-img me-3">
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>$<?= number_format($item['price']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>$<?= number_format($item['price'] * $item['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">總計：</td>
                                    <td class="fw-bold">$<?= number_format($order['total_amount']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
