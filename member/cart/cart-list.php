<?php
require_once '../../camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// 獲取購物車內容
try {
    // 檢查資料庫連接
    if (!$db) {
        throw new Exception("資料庫連接失敗");
    }

    // 修改購物車查詢 SQL
    $cart_sql = "SELECT 
        c.id,
        c.user_id,
        c.product_id,
        c.quantity,
        p.name AS product_name,
        p.price,
        p.stock,
        p.main_image,
        (p.price * c.quantity) AS subtotal
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?";

    $stmt = $db->prepare($cart_sql);
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
    // 計算總金額
    $total = array_sum(array_column($cartItems, 'subtotal'));
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>購物車 - Camp Explorer</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .quantity-input {
            width: 80px;
        }

        .bi-cart-x {
            display: block;
            margin-bottom: 1rem;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .text-muted {
            color: #6c757d;
        }

        .btn-primary {
            padding: 8px 24px;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container py-4">
        <?php if (empty($cartItems)): ?>
            <!-- 購物車為空時的顯示 -->
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-cart-x" style="font-size: 4rem; color: #ccc;"></i>
                    <h3 class="mt-3">購物車是空的</h3>
                    <p class="text-muted">快去選購喜歡的商品吧！</p>
                    <a href="/CampExplorer/member/products/products-list.php" class="btn btn-primary mt-3">
                        <i class="bi bi-cart-plus"></i> 去購物
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- 原有的購物車內容顯示 -->
            <div class="card">
                <div class="card-body">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>商品資訊</th>
                                <th>單價</th>
                                <th>數量</th>
                                <th>小計</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <!-- 修改圖片路徑 -->
                                            <img src="/CampExplorer/uploads/products/main/<?= $item['main_image'] ?? 'default-product.jpg' ?>"
                                                alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                class="product-img me-3"
                                                style="width: 80px; height: 80px; object-fit: cover;">
                                            <div>
                                                <h5 class="mb-0"><?= htmlspecialchars($item['product_name']) ?></h5>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?= number_format($item['price']) ?></td>
                                    <td>
                                        <input type="number" class="form-control quantity-input"
                                            value="<?= $item['quantity'] ?>"
                                            min="1"
                                            max="<?= $item['stock'] ?>"
                                            onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                                    </td>
                                    <td>$<?= number_format($item['subtotal']) ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="removeItem(<?= $item['id'] ?>)">
                                            移除
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">總計：</td>
                                <td class="fw-bold">$<?= number_format($total) ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="checkout()">
                                        結帳
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        async function updateQuantity(cartId, quantity) {
            try {
                await axios.post('../api/cart/update.php', {
                    cart_id: cartId,
                    quantity: quantity
                });
                location.reload();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '更新失敗',
                    text: error.response?.data?.message || '請稍後再試'
                });
            }
        }

        async function removeItem(cartId) {
            try {
                const result = await Swal.fire({
                    title: '確定要移除此商品？',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '確定',
                    cancelButtonText: '取消'
                });

                if (result.isConfirmed) {
                    await axios.post('../api/cart/remove.php', {
                        cart_id: cartId
                    });
                    location.reload();
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '移除失敗',
                    text: error.response?.data?.message || '請稍後再試'
                });
            }
        }

        async function checkout() {
            try {
                const result = await Swal.fire({
                    title: '確認結帳',
                    text: '確定要送出訂單嗎？',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '確定',
                    cancelButtonText: '取消'
                });

                if (result.isConfirmed) {
                    const response = await axios.post('../api/orders/create.php');
                    if (response.data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: '訂單建立成功',
                            text: '訂單編號: ' + response.data.order_id,
                            confirmButtonText: '查看訂單'
                        });
                        location.href = '../orders/order-list.php';
                    }
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '結帳失敗',
                    text: error.response?.data?.message || '請稍後再試'
                });
            }
        }
    </script>
</body>

</html>