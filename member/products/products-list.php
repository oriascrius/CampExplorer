<?php
require_once '../../camping_db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/navbar.php';

// 初始化變數
$error_message = null;
$products = [];

try {
    // 獲取分類資料
    $stmt = $db->prepare("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取商品列表
    $sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            WHERE p.status = 1 AND c.status = 1 AND s.status = 1
            ORDER BY p.sort_order ASC, p.created_at DESC";
            
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>露營趣 | 商品列表</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .product-card {
            height: 100%;
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .product-price {
            color: #dc3545;
            font-weight: bold;
        }
        .product-stock {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h2>商品列表</h2>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info">目前沒有商品</div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card product-card shadow-sm">
                            <img src="<?= !empty($product['main_image']) ? '/CampExplorer/uploads/products/main/' . $product['main_image'] : '/CampExplorer/assets/images/no-image.png' ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="card-body">
                                <div class="mb-2">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                                    <span class="badge bg-info"><?= htmlspecialchars($product['subcategory_name']) ?></span>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars(mb_substr($product['description'], 0, 50)) ?>...
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="product-price">NT$ <?= number_format($product['price']) ?></span>
                                    <span class="product-stock">庫存: <?= $product['stock'] ?></span>
                                </div>
                                <button onclick="addToCart(<?= $product['id'] ?>)" 
                                        class="btn btn-primary w-100 mt-3" 
                                        <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-cart-plus"></i> 
                                    <?= $product['stock'] <= 0 ? '暫無庫存' : '加入購物車' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function addToCart(productId) {
            try {
                const response = await axios.post('../api/cart/add.php', {
                    product_id: productId,
                    quantity: 1
                });
                
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '已加入購物車',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '加入購物車失敗',
                    text: error.response?.data?.message || '請稍後再試'
                });
            }
        }
    </script>
</body>
</html>