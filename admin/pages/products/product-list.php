<?php
// 引入資料庫連線檔案
require_once __DIR__ . '/../../../camping_db.php';


// 排序邏輯處理
$orderClause = "";
if (isset($_GET["order"])) {
    $order = $_GET["order"];
    switch ($order) {
        case "0":
            $orderClause = "ORDER BY id ASC";
            break;
        case "1":
            $orderClause = "ORDER BY price ASC";
            break;
        case "2":
            $orderClause = "ORDER BY price DESC";
            break;
        case "3":
            $orderClause = "ORDER BY created_at ASC";
            break;
        case "4":
            $orderClause = "ORDER BY created_at DESC";
            break;
    }
}

// 類別選擇器 若有GET到類別就篩選資料庫資料
$categoryFilter = "";
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $categoryFilter = "WHERE products.category_id = " . intval($_GET['category']);
} else {
    // 確保沒有選擇類別時，也不會造成錯誤
    $categoryFilter = "";
}

//GET Search內容
$searchFilter = "";
if (isset($_GET["search"])) {
    $search = $_GET["search"];
    if (empty($categoryFilter)) {
        $searchFilter = "WHERE products.name LIKE '%$search%'";
    } else {
        $searchFilter = "AND products.name LIKE '%$search%'";
    }
} else {
    $searchFilter = "";
}

//獲取category表內有的類別
try {
    $stmt = $db->query("SELECT id, name ,status FROM categories");
    $stmt = $db->query("SELECT id, name ,status FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("無法取得類別資料：" . $e->getMessage());
}

try {
    $stmt = $db->query("SELECT id, name ,category_id AS subcategory_category ,status FROM subcategories");
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("無法取得類別資料：" . $e->getMessage());
}



// 確保 categoryFilter 和 searchFilter 正確組合
$whereClause = $categoryFilter . " " . $searchFilter;
$whereClause = trim($whereClause); // 去除多餘的空格


// 分頁選取資料！！！！
// 每頁顯示 6 筆資料
$itemsPerPage = 6;
// 獲取篩選後資料總數
$countQuery = "SELECT COUNT(*) FROM products 
                LEFT JOIN categories ON products.category_id = categories.id
                LEFT JOIN subcategories ON products.subcategory_id = subcategories.id
                $whereClause"; // 加上篩選條件

try {
    $stmt = $db->query($countQuery);
    $stmt = $db->query($countQuery);
    $totalItems = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("查詢資料總數失敗：" . $e->getMessage());
}

// 計算總頁數
$totalPages = ceil($totalItems / $itemsPerPage);

// 當前頁數，預設為第 1 頁
$currentPage = isset($_GET['innerpage']) ? intval($_GET['innerpage']) : 1;

// 確保頁數在合理範圍內
if ($currentPage < 1) $currentPage = 1;
if ($currentPage > $totalPages) $currentPage = $totalPages;

// 計算 LIMIT 起始位置 並且讓他不小於零否則當資料表沒有值會變成 0-1*10=-10會出事
$offset = ($currentPage - 1) * $itemsPerPage;
if ($offset < 0) $offset = 1;

// 獲取商品資料並關聯類別子類別表

try {
    $stmt = $db->query("SELECT 
        products.id, 
        products.name AS product_name, 
        products.price, 
        products.created_at,
        products.stock, 
        products.status, 
        categories.id AS category_id, 
        categories.name AS category_name,
        subcategories.id AS subcategory_id,
        subcategories.name AS subcategory_name,
        product_images.id AS img_id,
        product_images.image_path AS img_path
    FROM 
        products
    LEFT JOIN 
        categories 
        ON products.category_id = categories.id 
    LEFT JOIN 
        subcategories 
        ON products.subcategory_id = subcategories.id
    LEFT JOIN
        product_images
        ON products.id = product_images.product_id AND product_images.is_main = 1
    
    $whereClause
    $orderClause
    LIMIT $itemsPerPage OFFSET $offset      
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("查詢失敗：" . $e->getMessage());
}


?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品列表</title>
    <?php include("css.php") ?>

    <style>
        /* 美化選擇框 */
        .form-select,
        .form-input {
            width: 150px;
            /* 調整寬度 */
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            transition: border-color 0.3s ease;
        }

        .form-select:focus,
        .form-input:focus {
            border-color: #007bff;
            outline: none;
        }

        /* 美化標籤 */
        .form-label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #555;
        }

        /* 並排顯示選項 */
        .d-flex {
            display: flex;
            gap: 10px;
            /* 增加間距 */
            justify-content: space-between;
            align-items: center;
        }

        .my-3 {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .mx-3 {
            margin-left: 1rem;
            margin-right: 1rem;
        }

        /* 提交按鈕樣式 */
        .search-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* 在小螢幕上調整排版 */
        @media (max-width: 768px) {
            .d-flex {
                flex-direction: column;
                gap: 10px;
                /* 減少元素間的間距 */
            }

            .my-3 {
                width: 100%;
            }

            .mx-3 {
                margin-left: 0;
                margin-right: 0;
            }

            .form-select,
            .form-input {
                width: 100%;
                /* 讓選擇框和輸入框在小螢幕上佔滿整行 */
            }
        }

        .smallImg {
            width: 100px;
            height: 100px;
            object-fit: cover;

            img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
        }
    </style>
</head>

<body>
    <!-- 導入modal -->
    <?php include("product_modal.php") ?>
    <?php include("img_modal.php") ?>
    <!-- 導入modal -->

    <div class="container mt-5">
        <h1 class="text-center mb-4">商品列表</h1>
        <div class="d-flex justify-content-between">
            <div class="my-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    新增商品
                </button>
            </div>
            <div>
                <!-- 搜尋排序與類別選擇器的表單 -->
                <form method="get" action="index.php" class="d-flex justify-content-between align-items-center">
                    <input type="hidden" name="page" value="products_list">


                    <!-- 類別選擇器 -->
                    <div class="my-3">
                        <label for="category" class="form-label">選擇類別:</label>
                        <select name="category" id="category" class="form-select" onchange="this.form.submit()">
                            <option value="">全部類別</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if ($category['status']  === 1): ?>
                                    <option value="<?= $category['id'] ?>"
                                        <?php if (isset($_GET['category']) && $_GET['category'] == $category['id']) echo 'selected'; ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php else: ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>" disabled>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 排序選項 -->
                    <div class="my-3 mx-3">
                        <label for="order" class="form-label">排序方式:</label>
                        <select name="order" id="order" class="form-select" onchange="this.form.submit()">
                            <option value="0" <?php if (!isset($_GET['order']) || $_GET['order'] == '0') echo 'selected'; ?>>預設</option>
                            <option value="1" <?php if (isset($_GET['order']) && $_GET['order'] == '1') echo 'selected'; ?>>價格從低到高</option>
                            <option value="2" <?php if (isset($_GET['order']) && $_GET['order'] == '2') echo 'selected'; ?>>價格從高到低</option>
                            <option value="3" <?php if (isset($_GET['order']) && $_GET['order'] == '3') echo 'selected'; ?>>日期由舊到新</option>
                            <option value="4" <?php if (isset($_GET['order']) && $_GET['order'] == '4') echo 'selected'; ?>>日期由新到舊</option>
                        </select>
                    </div>

                    <!-- 搜尋選擇器 -->
                    <div class="my-3 d-inline-block">
                        <label for="search" class="form-label">搜尋關鍵字：</label>
                        <div class="d-flex">
                            <?php if ((!isset($_GET['search']) || $_GET['search'] == "")): ?>
                                <input type="text" id="search" name="search" placeholder="輸入搜尋內容" class="form-input mx-0">
                                <button type="submit" class="btn btn-primary mx-0 search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
                            <?php else: ?>
                                <input type="text" id="search" name="search" placeholder="搜尋:<?= $_GET['search'] ?>" class="form-input mx-0">
                                <button type="submit" class="btn btn-primary mx-0 search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
                                <a href="index.php?page=products_list" class="btn btn-primary search-btn">X</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

            </div>
        </div>
        <!-- 顯示列表內容 -->
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>名稱</th>
                    <th>主類別</th>
                    <th>次類別</th>
                    <th>圖片</th>
                    <th>價格</th>
                    <th>庫存</th>
                    <th>新增時間</th>
                    <th>狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['id']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td><?= htmlspecialchars($product['subcategory_name']) ?></td>
                            <td class="text-center smallImg">
                                <img src="../uploads/products/img/<?= htmlspecialchars($product['img_path']) ?>" alt="">
                            </td>
                            <td class="text-end">$<?= htmlspecialchars(number_format($product['price'])) ?></td>
                            <td class="text-end"><?= htmlspecialchars($product['stock']) ?></td>
                            <td class="text-end"><?= htmlspecialchars($product['created_at']) ?></td>
                            <td class="text-center">
                                <?= $product['status'] == 1 ? '<span class="badge bg-success">上架</span>' : '<span class="badge bg-secondary">下架</span>' ?>
                            </td>
                            <td class="text-center">
                                <!-- 編輯按鈕 要傳送data-XX參數給JS -->
                                <button
                                    class="btn btn-warning btn-sm edit-btn"
                                    data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['product_name']) ?>"
                                    data-price="<?= $product['price'] ?>"
                                    data-stock="<?= $product['stock'] ?>"
                                    data-status="<?= $product['status'] ?>"
                                    data-category="<?= $product['category_id'] ?>"
                                    data-subcategory="<?= $product['subcategory_id'] ?>">
                                    編輯
                                </button>
                                <button
                                    class="btn btn-secondary btn-sm edit-images-btn"
                                    data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['product_name']) ?>">
                                    圖片編輯
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">目前沒有商品資料。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- 分頁按鈕start -->
        <div class="pagination">

            <?php if ($totalPages > 1 && count($products) > 0): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <!-- 上一頁 -->
                        <li class="page-item <?= ($currentPage == 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=products_list&innerpage=<?= $currentPage - 1 ?>&order=<?= $_GET['order'] ?? '0' ?>&category=<?= $_GET['category'] ?? '' ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <!-- 頁碼 -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=products_list&innerpage=<?= $i ?>&order=<?= $_GET['order'] ?? '0' ?>&category=<?= $_GET['category'] ?? '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- 下一頁 -->
                        <li class="page-item <?= ($currentPage == $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=products_list&innerpage=<?= $currentPage + 1 ?>&order=<?= $_GET['order'] ?? '0' ?>&category=<?= $_GET['category'] ?? '' ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>

                    </ul>
                </nav>
            <?php endif; ?>

        </div>

        <!-- 分頁按鈕End -->
    </div>


    <!-- 新增編輯功能的JS (AJAX PRIMASE) -->
    <?php include("add_edit_ajax_JS.php") ?>
    <!-- 新增編輯功能的JS (AJAX PRIMASE) -->

    <!-- 圖片編輯功能的JS -->
    <?php include("img_js.php") ?>
    <!-- 圖片編輯功能的JS -->
</body>

</html>