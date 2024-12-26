<?php
require_once __DIR__ . '/../../../camping_db.php';
try {
    // 獲取主類別與次類別資料
    $categories_sql = "
        SELECT 
            categories.*,
            subcategories.name AS sub_name,
            subcategories.id AS sub_id,
            subcategories.category_id AS sub_category_id,
            subcategories.status AS sub_status
        FROM categories 
        LEFT JOIN subcategories ON categories.id = subcategories.category_id
        ORDER BY categories.id ASC
    ";

    $categories_stmt = $db->prepare($categories_sql);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die("資料載入失敗，請稍後再試");
}

$uniqueCategories = [];
foreach ($categories as $category) {
    if (!isset($uniqueCategories[$category['id']])) {
        $uniqueCategories[$category['id']] = $category;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>類別編輯頁面</title>
    <style>
        .card.my-4{
            padding: 0;
            background-color: #f8f9fa;
            border-radius: 30px;
            box-shadow: 0px 18px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-body{
            padding: 0;
        }
        .haeder-style{
            background-image: linear-gradient(to top, #0ba360 0%, #3cba92 100%);
            border-radius: 30px 30px 0 0;
            color:#fff;
            padding: 15px;
        }
        .haeder-style span.badge{
            background-color: transparent !important;
            border: 1px solid #ffffff;
            color: #ffffff !important;
        }
        .mb-3.border-bottom .d-flex{
            padding: 0 15px;
            padding-right: 10px;
            color:#767676!important
        }
        .container{
            padding: 4rem;
            max-width: 100%;    
        }
        .d-flex.justify-content-end{
            padding: 15px;
        } 
        .badge.bg-success{
            background-color: transparent !important;
            border: 1px solid #0080005c;
            color: #008000 !important;
            padding: 7px 23px;
        }
        .badge.bg-danger{
            background-color: transparent !important;
            border: 1px solid #ff000040;
            color: #db0000 !important;
            padding: 7px 23px;
        }
        .btn.btn-outline-primary{
            color: #8b6a09;
            background-color: #ffc1076e;
            border: 0;
        }
    </style>

</head>

<body>
    <!-- modal入 -->
    <?php include("edit_modal.php") ?>
    <!-- modal入 -->

    <div class="container">
        <h1 class="mb-4">類別編輯</h1>
        <div>
            <!-- 新增主類別按鈕 -->
            <button class="btn btn-primary " id="addCategoryBtn">新增主類別</button>
        </div>

        <div class="row">
            <?php foreach ($uniqueCategories as $category): ?>
                <div class="col-sm-12 col-md-6 col-lg-4  mb-4">
                    <div class="card my-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between haeder-style">
                                <!-- 左側主類別名稱 -->
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-0"><?= $category['name'] ?></h4>
                                </div>

                                <!-- 右側狀態與按鈕 -->
                                <div class="d-flex align-items-center">
                                    <!-- 狀態標籤 -->
                                    <div class="me-2">
                                        <span class="badge <?= $category['status'] ? 'bg-success' : 'bg-danger' ?> p-2 status-size">
                                            <?= $category['status'] ? '啟用中' : '停用中' ?>
                                        </span>
                                    </div>
                                    <!-- 編輯按鈕 -->
                                    <div id="category-container">
                                        <button class="btn btn-primary btn-sm edit-category-btn" data-category-id="<?= $category['id'] ?>" data-category-name="<?= $category['name'] ?>" data-category-status="<?= $category['status'] ?>">
                                            編輯
                                        </button>
                                    </div>

                                </div>
                            </div>



                            <ul class="list-unstyled">
                                <?php foreach ($categories as $subCategory): ?>
                                    <?php if ($subCategory['sub_category_id'] == $category['id']): ?>
                                        <li class="mb-3 border-bottom">
                                            <div class="d-flex align-items-center justify-content-between py-2 ">
                                                <!-- 左側次類別名稱 -->
                                                <div class="flex-grow-1">
                                                    <h5 class="card-title mb-0"><?= $subCategory['sub_name'] ?></h5>
                                                </div>

                                                <!-- 右側狀態與按鈕 -->
                                                <div class="d-flex align-items-center">
                                                    <!-- 狀態標籤 -->
                                                    <div class="me-2">
                                                        <span class="badge <?= $subCategory['sub_status'] ? 'bg-success' : 'bg-danger' ?> p-2">
                                                            <?= $subCategory['sub_status'] ? '啟用中' : '停用中' ?>
                                                        </span>
                                                    </div>

                                                    <!-- 編輯按鈕 -->
                                                    <div>
                                                        <button class="btn btn-outline-primary btn-sm edit-subcategory-btn" data-category-id="<?= $subCategory['sub_category_id'] ?>" data-subcategory-id="<?= $subCategory['sub_id'] ?>" data-subcategory-name="<?= $subCategory['sub_name'] ?>" data-subcategory-status="<?= $subCategory['sub_status'] ?>">
                                                            編輯
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <div class="d-flex justify-content-end ">
                                <button class="btn btn-outline-primary btn-sm add-subcategory-btn"
                                    data-category-id="<?= $category['id'] ?>">新增次類別</button>
                            </div>

                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>




        <script src="pages/categories/edit_ajax_modal_JS.js"></script>





</body>

</html>