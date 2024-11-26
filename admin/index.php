<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 處理 AJAX 請求
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $page = $_GET['page'] ?? 'dashboard';
    $allowed_pages = [
        'dashboard' => 'pages/dashboard.php',
        'camps_review' => 'pages/reviews/camp-review.php',
        'spots_review' => 'pages/reviews/spot-review.php',
        'products_list' => 'pages/products/product-list.php',
        'product_category' => 'pages/categories/product-category.php',
        'spot_category' => 'pages/categories/spot-category.php',
        'coupons_list' => 'pages/coupons/coupon.php',
        'members_list' => 'pages/users/member/member-list.php',
        'owners_list' => 'pages/users/owner/owner-list.php',
        'articles_list' => 'pages/articles/article-list.php',
        'orders_list' => 'pages/orders/order-list.php',
        'approved_camps' => 'pages/campsites/approved-camps.php',
        'approved_spots' => 'pages/campsites/approved-spots.php',
    ];

    if (isset($allowed_pages[$page])) {
        ob_start();
        include $allowed_pages[$page];
        $content = ob_get_clean();
        echo $content;
    } else {
        http_response_code(404);
        echo '<div class="alert alert-danger">頁面不存在</div>';
    }
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>後台管理系統</title>
    <!-- 先載入所有必要的 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-wrapper {
            margin-left: 250px;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .main-content {
            padding: 2rem;
        }

        /* 分類管理樣式 */
        .category-item {
            border: none;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .category-item:hover {
            background-color: #f8f9fa;
        }

        .category-icon {
            transition: transform 0.3s ease;
        }

        .category-item .collapsed .category-icon {
            transform: rotate(-90deg);
        }

        .subcategory-list {
            background-color: #f8f9fa;
        }

        .subcategory-item {
            transition: all 0.3s ease;
        }

        .subcategory-item:hover {
            background-color: #fff;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .badge {
            font-weight: normal;
            padding: 0.35em 0.65em;
        }

        .required:after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/components/sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="main-content">
            <div class="container-fluid">
                <?php
                $page = $_GET['page'] ?? 'dashboard';
                $allowed_pages = [
                    // 數據首頁
                    'dashboard' => 'pages/dashboard.php',

                    // 審核管理
                    // 營地審核
                    'camps_review' => 'pages/reviews/camp-review.php',
                    // 營位審核
                    'spots_review' => 'pages/reviews/spot-review.php',



                    // 類別管理
                    // 商品分類
                    'product_category' => 'pages/categories/product-category.php',
                    // 營位分類
                    'spot_category' => 'pages/categories/spot-category.php',


                    // 營區管理
                    // 全部營地列表
                    'approved_camps' => 'pages/campsites/approved-camps.php',
                    // 全部營位列表
                    'approved_spots' => 'pages/campsites/approved-spots.php',

                    // 商品管理
                    'products_list' => 'pages/products/product-list.php',


                    // 訂單管理
                    // 商品訂單管理
                    'orders_list' => 'pages/orders/order-list.php',
                    // 營位訂單管理


                    // 使用者管理
                    // 會員管理
                    'members_list' => 'pages/users/member/member-list.php',
                    // 營主管理
                    'owners_list' => 'pages/users/owner/owner-list.php',

                    // 優惠券
                    'coupons_list' => 'pages/coupons/coupon.php',

                    // 官方文章管理
                    'articles_list' => 'pages/articles/article-list.php',
                ];

                if (isset($allowed_pages[$page])) {
                    // 先載入共用的 scripts
                    include_once __DIR__ . '/components/scripts.php';
                    // 再載入頁面內容
                    include __DIR__ . '/' . $allowed_pages[$page];
                } else {
                    echo "<div class='alert alert-danger'>頁面不存在</div>";
                }
                ?>
            </div>
        </div>
    </div>
</body>

</html>