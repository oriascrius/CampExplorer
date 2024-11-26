<?php
require_once '../camping_db.php';
session_start();

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 獲取會員資料
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>露營趣 | 會員中心</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <!-- 導航欄 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">露營趣</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="products/products-list.php">商品列表</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart/cart-list.php">購物車</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3">歡迎，<?= htmlspecialchars($user['name']) ?></span>
                    <a href="/CampExplorer/member/logout.php" class="btn btn-outline-danger">登出</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-3">
                <!-- 側邊選單 -->
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person"></i> 會員資料
                    </a>
                    <a href="orders/order-list.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-list-ul"></i> 訂單紀錄
                    </a>
                </div>
            </div>
            <div class="col-md-9">
                <!-- 會員資料卡片 -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">會員資料</h3>
                        <div class="row mb-3">
                            <div class="col-md-3">姓名：</div>
                            <div class="col-md-9"><?= htmlspecialchars($user['name']) ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">信箱：</div>
                            <div class="col-md-9"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">電話：</div>
                            <div class="col-md-9"><?= htmlspecialchars($user['phone'] ?? '尚未設定') ?></div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" onclick="editProfile()">編輯資料</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>