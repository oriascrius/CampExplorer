<?php
require_once __DIR__ . '/../../../camping_db.php';
require_once __DIR__ . '/order_status.php';

// 獲取排序參數
$sort_field = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'desc';

// 獲取訂單數據
try {
    // 先測試基本查詢
    $orders_sql = "SELECT * FROM product_orders";
    $orders_stmt = $db->prepare($orders_sql);
    $orders_stmt->execute();
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 如果基本查詢成功，再使用完整查詢
    if (!empty($orders)) {
        $orders_sql = "SELECT 
            po.*, 
            u.name as username,
            COALESCE(COUNT(pod.id), 0) as items_count
        FROM product_orders po
        LEFT JOIN users u ON po.member_id = u.id
        LEFT JOIN product_order_details pod ON po.order_id = pod.order_id
        GROUP BY po.order_id, po.member_id, po.total_amount, po.payment_status, 
                 po.order_status, po.created_at, po.updated_at, u.name
        ORDER BY po.$sort_field $sort_order";

        $orders_stmt = $db->prepare($orders_sql);
        $orders_stmt->execute();
        $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // 只有在真的沒有取得資料時才顯示錯誤
    if (empty($orders)) {
        $error_message = "資料載入失敗，請稍後再試";
    }
    error_log("SQL Query: " . $orders_sql);
    error_log("Error Details: " . $e->getMessage());
}

// 在頁面上顯示錯誤信息（如果有的話）
if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">商品訂單管理</h2>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="order_id">
                                訂單編號 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="username">
                                會員名稱 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>商品數量</th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="total_amount">
                                總金額 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="payment_status">
                                付款狀態 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="order_status">
                                訂單狀態 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>
                            <button class="btn btn-link text-dark p-0" data-sort="created_at">
                                建立時間 <i class="bi bi-arrow-down-up"></i>
                            </button>
                        </th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">目前沒有訂單資料</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($order['username'] ?? '未知會員') ?></td>
                                <td><?= $order['items_count'] ?? 0 ?></td>
                                <td>NT$ <?= number_format($order['total_amount'] ?? 0) ?></td>
                                <td><?= getPaymentStatusBadge($order['payment_status'] ?? 0) ?></td>
                                <td><?= getOrderStatusBadge($order['order_status'] ?? 0) ?></td>
                                <td><?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="OrderList.viewOrderDetails(<?= $order['order_id'] ?>)">
                                            查看
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary"
                                            onclick="OrderList.updateOrderStatus(<?= $order['order_id'] ?>)">
                                            更新狀態
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // 將 OrderList 定義為全域物件
    window.OrderList = {
        async viewOrderDetails(orderId) {
            try {
                const response = await fetch(`/CampExplorer/admin/api/orders/product_orders/get_order_details.php?order_id=${orderId}`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message);
                }

                const order = result.data;
                const items = order.items.map(item => `
                <tr>
                    <td>
                        <img src="${item.product_image.startsWith('/') ? '' : '/CampExplorer/uploads/products/main/'}${item.product_image}" 
                             alt="${item.product_name}" 
                             style="width: 50px; height: 50px; object-fit: cover;">
                    </td>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td>NT$ ${Number(item.price).toLocaleString()}</td>
                    <td>NT$ ${(item.quantity * item.price).toLocaleString()}</td>
                </tr>
            `).join('');

                await Swal.fire({
                    title: `訂單詳情 #${String(order.order_id).padStart(5, '0')}`,
                    html: `
                    <div class="text-start">
                        <h6>會員資訊</h6>
                        <p>姓名：${order.username || '未知'}</p>
                        <p>信箱：${order.email || '無'}</p>
                        <p>電話：${order.phone || '無'}</p>
                        
                        <h6 class="mt-4">訂單資訊</h6>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>商品圖</th>
                                    <th>商品名稱</th>
                                    <th>數量</th>
                                    <th>單價</th>
                                    <th>小計</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items}
                            </tbody>
                        </table>
                        <div class="text-end">
                            <h5>總金額：NT$ ${Number(order.total_amount).toLocaleString()}</h5>
                        </div>
                    </div>
                `,
                    width: '800px'
                });
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('錯誤', error.message, 'error');
            }
        },

        async updateOrderStatus(orderId) {
            try {
                const {
                    value: formValues
                } = await Swal.fire({
                    title: '更新訂單狀態',
                    html: `
                    <div class="mb-3">
                        <label class="form-label">付款狀態</label>
                        <select class="form-select" id="payment_status">
                            <option value="0">未付款</option>
                            <option value="1">已付款</option>
                            <option value="2">已退款</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">訂單狀態</label>
                        <select class="form-select" id="order_status">
                            <option value="0">待處理</option>
                            <option value="1">處理中</option>
                            <option value="2">已完成</option>
                            <option value="3">已取消</option>
                        </select>
                    </div>
                `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: '確認更新',
                    cancelButtonText: '取消',
                    preConfirm: () => {
                        return {
                            payment_status: document.getElementById('payment_status').value,
                            order_status: document.getElementById('order_status').value
                        }
                    }
                });

                if (formValues) {
                    const responses = await Promise.all([
                        this.updateStatus(orderId, 'payment_status', formValues.payment_status),
                        this.updateStatus(orderId, 'order_status', formValues.order_status)
                    ]);

                    if (responses.every(r => r.success)) {
                        await Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: '訂單狀態已更新',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        // 更新成功後重新載入頁面
                        setTimeout(() => {
                            window.location.reload();
                        }, 0);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('錯誤', error.message, 'error');
            }
        },

        async updateStatus(orderId, statusType, statusValue) {
            const response = await fetch('/CampExplorer/admin/api/orders/product_orders/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status_type: statusType,
                    status_value: statusValue
                })
            });
            return await response.json();
        }
    };

    // 添加初始化函數
    function initializeOrderList() {
        // 確保 OrderList 已經被定義
        if (typeof window.OrderList === 'undefined') {
            console.error('OrderList not initialized');
            return;
        }
    }

    // 同時監聽 DOMContentLoaded 和 pageLoaded 事件
    document.addEventListener('DOMContentLoaded', initializeOrderList);
    document.addEventListener('pageLoaded', initializeOrderList);
</script>