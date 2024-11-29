<?php
require_once __DIR__ . '/../../../camping_db.php';

try {
    // 設定排序條件
    $allowed_sort_columns = ['views', 'like_count', 'articles.updated_at'];
    $sort_column = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns)
        ? $_GET['sort_by']
        : 'articles.updated_at'; // 預設排序欄位
    $sort_order = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), ['ASC', 'DESC'])
        ? strtoupper($_GET['sort_order'])
        : 'DESC'; // 預設排序方式

    // 每頁顯示的資料數
    $items_per_page = 6;

    // 獲取當前頁數
    $current_page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
    $current_page = max($current_page, 1);

    // 過濾條件
    $search_query = isset($_GET['search']) ? $_GET['search'] : '';
    $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $status_filter = isset($_GET['status']) ? (int)$_GET['status'] : -1;
    $author_filter = isset($_GET['author']) ? (int)$_GET['author'] : 0;

    // 計算起始位置
    $offset = ($current_page - 1) * $items_per_page;

    // 建立查詢 SQL
    $articles_sql = "SELECT 
        articles.id,
        articles.image_name,
        articles.title,
        articles.status,
        articles.views,
        articles.created_at,
        articles.updated_at,
        admins.name AS admin_name,
        article_categories.name AS category_name,
        COUNT(article_like.article_id) AS like_count
    FROM articles
    LEFT JOIN admins ON articles.created_by = admins.id
    LEFT JOIN article_categories ON articles.article_category = article_categories.id
    LEFT JOIN article_like ON articles.id = article_like.article_id
    WHERE 1=1";

    // 加入篩選條件
    if ($search_query) {
        $articles_sql .= " AND (articles.title LIKE :search OR articles.content LIKE :search)";
    }
    if ($category_filter > 0) {
        $articles_sql .= " AND articles.article_category = :category";
    }
    if ($status_filter >= 0) {
        $articles_sql .= " AND articles.status = :status";
    }
    if ($author_filter > 0) {
        $articles_sql .= " AND articles.created_by = :author";
    }

    // 加入排序條件與分頁
    $articles_sql .= " GROUP BY articles.id ORDER BY $sort_column $sort_order LIMIT :limit OFFSET :offset";

    // 準備查詢語句
    $articles_stmt = $db->prepare($articles_sql);

    // 綁定過濾條件
    if ($search_query) {
        $articles_stmt->bindValue(':search', '%' . $search_query . '%', PDO::PARAM_STR);
    }
    if ($category_filter > 0) {
        $articles_stmt->bindValue(':category', $category_filter, PDO::PARAM_INT);
    }
    if ($status_filter >= 0) {
        $articles_stmt->bindValue(':status', $status_filter, PDO::PARAM_INT);
    }
    if ($author_filter > 0) {
        $articles_stmt->bindValue(':author', $author_filter, PDO::PARAM_INT);
    }

    // 綁定分頁參數
    $articles_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $articles_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $articles_stmt->execute();
    $articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 計算總筆數
    $count_sql = "SELECT COUNT(DISTINCT articles.id) FROM articles
        LEFT JOIN article_like ON articles.id = article_like.article_id WHERE 1=1";
    if ($search_query) {
        $count_sql .= " AND (articles.title LIKE :search OR articles.content LIKE :search)";
    }
    if ($category_filter > 0) {
        $count_sql .= " AND articles.article_category = :category";
    }
    if ($status_filter >= 0) {
        $count_sql .= " AND articles.status = :status";
    }
    if ($author_filter > 0) {
        $count_sql .= " AND articles.created_by = :author";
    }

    // 準備計算總筆數的查詢語句
    $count_stmt = $db->prepare($count_sql);
    if ($search_query) {
        $count_stmt->bindValue(':search', '%' . $search_query . '%', PDO::PARAM_STR);
    }
    if ($category_filter > 0) {
        $count_stmt->bindValue(':category', $category_filter, PDO::PARAM_INT);
    }
    if ($status_filter >= 0) {
        $count_stmt->bindValue(':status', $status_filter, PDO::PARAM_INT);
    }
    if ($author_filter > 0) {
        $count_stmt->bindValue(':author', $author_filter, PDO::PARAM_INT);
    }
    $count_stmt->execute();
    $total_items = $count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // 取得分類與作者資料
    $categories_stmt = $db->prepare("SELECT id, name FROM article_categories");
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    $authors_stmt = $db->prepare("SELECT id, name FROM admins");
    $authors_stmt->execute();
    $authors = $authors_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}

// 用於保留當前篩選條件的函數
function buildQueryString($params = [])
{
    $currentParams = $_GET;
    $mergedParams = array_merge($currentParams, $params);
    return '?' . http_build_query($mergedParams);
}

// 計算分頁範圍
$start_page = max(1, $current_page - 2); // 從當前頁前兩頁開始
$end_page = min($total_pages, $start_page + 4); // 最多顯示5頁
$start_page = max(1, $end_page - 4); // 保證始終有5頁（若可能）

//限制文章標題文字顯示字數 = 10
function truncateText($text, $length = 10) {
    return mb_strlen($text, 'UTF-8') > $length 
        ? mb_substr($text, 0, $length, 'UTF-8') . '...' 
        : $text;
}
?>





<!-- 在頁面頂部添加，約在第 2-3 行之間 -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-zh-TW.min.js"></script>
<!-- Font Awesome -->
<script src="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>


<!-- Lee版本 主要內容開始 -->
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class=" border-0">
                <h1 class="mb-5 text-center font-weight-bold pt-5">官方文章管理</h1>
                <div class="d-flex justify-content-end align-items-center py-3">
                    
                    <div class="d-flex align-items-center">

                        <!-- 搜尋框 -->
                        <form action="index.php" method="get" class="d-flex me-3">
                            <input type="hidden" name="page" value="articles_list"> <!-- 保留原本的頁面參數 -->
                            <input type="text" class="form-control rounded-0 rounded-start" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="搜尋文章" id="searchBar" />
                            <button type="submit" class="btn btn-primary rounded-0 rounded-end text-nowrap">搜尋</button>
                        </form>

                        <!-- 表單 HTML -->
                        <form action="index.php" method="get" class="d-flex me-3" id="filterForm">
                            <input type="hidden" name="page" value="articles_list"> <!-- 確保回到文章列表頁 -->
                            <input type="hidden" name="sort_order" id="sort-order" value="DESC"> <!-- 隱藏排序順序 -->

                            <!-- 文章類型 -->
                            <select name="category" class="form-select rounded-0 rounded-start" onchange="this.form.submit()">
                                <option value="0">文章類型</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $category['id'] == $category_filter ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- 狀態 -->
                            <select name="status" class="form-select rounded-0" onchange="this.form.submit()">
                                <option value="-1">文章狀態</option>
                                <option value="1" <?= $status_filter == '1' ? 'selected' : '' ?>>啟用中</option>
                                <option value="0" <?= $status_filter == '0' ? 'selected' : '' ?>>停用中</option>
                            </select>

                            <!-- 作者 -->
                            <select name="author" class="form-select rounded-0" onchange="this.form.submit()">
                                <option value="0">作者</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['id'] ?>" <?= $author['id'] == $author_filter ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($author['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- 排序選單 -->
                            <select id="sort-select" name="sort_by" class="form-select rounded-0" style="width: 120px;">
                                <option value="" selected>選擇排序</option>
                                <option value="views" <?php echo isset($_GET['sort_by']) && $_GET['sort_by'] == 'views' ? 'selected' : ''; ?>>瀏覽次數</option>
                                <option value="like_count" <?php echo isset($_GET['sort_by']) && $_GET['sort_by'] == 'like_count' ? 'selected' : ''; ?>>按讚次數</option>
                                <option value="articles.updated_at" <?php echo isset($_GET['sort_by']) && $_GET['sort_by'] == 'articles.updated_at' ? 'selected' : ''; ?>>更新時間</option>
                            </select>
                            <!-- 切換排序順序按鈕 -->
                            <button type="submit" id="sort-toggle-btn" class="btn btn-secondary text-nowrap rounded-0 rounded-end">
                                <i class="bi bi-arrow-down-up"></i>
                            </button>

                            <!-- 清除按鈕 -->
                            <button type="button" class="btn btn-warning text-nowrap ms-2" id="clear-filters-btn">清除篩選</button>
                        </form>

                        <button type="button" class="btn btn-secondary rounded-0 rounded-start" data-action="add_category">
                            <i class="fa-solid fa-list me-1"></i>新增分類
                        </button>

                        <button type="button" class="btn btn-secondary rounded-0" data-action="edit_category">
                            <i class="fa-solid fa-pen me-1"></i>編輯分類
                        </button>

                        <button type="button" class="btn btn-primary rounded-0 rounded-end" data-action="add">
                            <i class="bi bi-plus-lg me-1"></i>新增文章
                        </button>
                    </div>

                </div>
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 article-table">
                            <thead class="bg-light">
                                <tr>
                                    <th>封面圖片</th>
                                    <th>文章類型</th>
                                    <th>標題</th>
                                    <th>狀態</th>
                                    <th>瀏覽次數</th>
                                    <th>按讚次數</th>
                                    <th>作者</th>
                                    <th>更新時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td class="article-image-cell">

                                            <?php if ($article['image_name']): ?>
                                                <div class="article-image-wrapper">
                                                    <img src="../uploads/articles/<?= htmlspecialchars($article['image_name']) ?>" class="article-image rounded" data-action="preview" data-id="<?= $article['id'] ?>">
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">無圖片</span>
                                            <?php endif; ?>

                                        </td>
                                        <td><?= htmlspecialchars($article['category_name']) ?></td>
                                        <td><?= htmlspecialchars(truncateText($article['title'])) ?></td>
                                        <td>
                                            <span class="fs-6 badge <?= $article['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $article['status'] ? '啟用中' : '停用中' ?>
                                            </span>
                                        </td>
                                        <td><?= $article['views'] ?></td>
                                        <td><?= $article['like_count'] ?></td>
                                        <td><?= htmlspecialchars($article['admin_name']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($article['updated_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary fs-6"
                                                    data-action="edit"
                                                    data-id="<?= $article['id'] ?>">
                                                    <i class="bi bi-pencil-square me-1"></i>編輯
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm fs-6 <?= $article['status'] ? 'btn-danger' : 'btn-success' ?>"
                                                    data-action="toggle-status"
                                                    data-id="<?= $article['id'] ?>"
                                                    data-status="<?= $article['status'] ?>">
                                                    <i class="bi bi-toggle-<?= $article['status'] ? 'on' : 'off' ?> me-1"></i>
                                                    <?= $article['status'] ? '停用' : '啟用' ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁按鈕 -->
                    <div class="d-flex justify-content-between align-items-center my-3 px-3">
                        <div>
                            <span>共 <?= $total_items ?> 筆資料，分 <?= $total_pages ?> 頁</span>
                        </div>
                        <div>
                            <ul class="pagination mb-0">
                                <!-- 上一頁 -->
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildQueryString(['page_num' => $current_page - 1]) ?>">上一頁</a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <a class="page-link" tabindex="-1">上一頁</a>
                                    </li>
                                <?php endif; ?>

                                <!-- 數字頁碼 -->
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildQueryString(['page_num' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- 下一頁 -->
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildQueryString(['page_num' => $current_page + 1]) ?>">下一頁</a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <a class="page-link" tabindex="-1">下一頁</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<!-- Lee版本 主要內容結束 -->



<script>
    // 在 ArticleUI 物件之前添加 Toast 定義
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1000,
        timerProgressBar: true
    });

    const ArticleUI = {
        currentSort: {
            field: 'created_at',
            order: 'desc'
        },

        init() {
            this.initEventListeners();
        },

        initEventListeners() {
            const table = document.querySelector('table');
            if (table) {
                table.addEventListener('click', async (e) => {
                    const button = e.target.closest('button');
                    const img = e.target.closest('img');
                    // console.log(img);  // 測試圖片點選 = OK
                    // if (!button) return;
                    // const action = button.dataset.action;
                    // const id = button.dataset.id;
                    // const status = button.dataset.status;
                    if (button) {
                        const action = button.dataset.action;
                        const id = button.dataset.id;
                        const status = button.dataset.status;
                        switch (action) {
                            case 'edit':
                                await this.handleEditArticle(id);
                                break;
                            case 'toggle-status':
                                await this.handleToggleStatus(id, parseInt(status));
                                break;
                            case 'add':
                                await this.handleAddArticle();
                                break;
                        }
                    } else if (img) {
                        const img_action = img.dataset.action;
                        const img_id = img.dataset.id;
                        switch (img_action) {
                            case 'preview':
                                await this.handlePreviewArticle(img_id);
                                console.log("圖片被點選");
                                break;
                        }
                    } else {
                        return;
                    }

                    // const img = e.target.closest('img');//
                    // if (!img || !button) return;//
                    // const img_action = img.dataset.action;//
                    // const img_id = img.dataset.id;//

                    // switch (action) {
                    //     case 'edit':
                    //         await this.handleEditArticle(id);
                    //         break;
                    //     case 'toggle-status':
                    //         await this.handleToggleStatus(id, parseInt(status));
                    //         break;
                    //     case 'add':
                    //         await this.handleAddArticle();
                    //         break;
                    // }

                    // switch (img_action) {
                    //     case 'preview':
                    //         await this.handlePreviewArticle(img_id);
                    //         console.log("圖片被點選");
                    //         break;
                    // }
                });
            }



            // 新增按鈕事件監聽
            const addButton = document.querySelector('button[data-action="add"]');
            if (addButton) {
                addButton.addEventListener('click', () => this.handleAddArticle());
            }
            // 新增編輯分類按鈕監聽
            const editButton = document.querySelector('button[data-action="edit_category"]');
            if (editButton) {
                editButton.addEventListener('click', () => this.handleEditCategoryArticle());
            }
            // 新增新增分類按鈕監聽
            const addCategoryButton = document.querySelector('button[data-action="add_category"]');
            if (addCategoryButton) {
                addCategoryButton.addEventListener('click', () => this.handleAddCategoryArticle());
            }
        },


        // add_category 測試
        async handleAddCategoryArticle() {
            try {
                // 顯示 SweetAlert 視窗，並使用 AJAX 載入分類編輯頁面
                const result = await Swal.fire({
                    title: '新增分類',
                    html: `
                        <div id="edit-category-container" class="edit-category-container">
                            <p>載入中...</p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '確定新增',
                    cancelButtonText: '取消',
                    width: '800px',
                    preConfirm: async () => {
                        try {
                            // 提交新增後的分類資料
                            const formData = new FormData(document.getElementById('addCategoryForm')); // 將表單 ID 替換為正確的名稱
                            const response = await axios.post('/CampExplorer/admin/api/articles/add_categories.php', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            });

                            if (response.data.success) {
                                return response.data; // 將成功回應傳遞給 SweetAlert
                            } else {
                                Swal.showValidationMessage(response.data.message || '更新失敗');
                                return false; // 阻止 SweetAlert 繼續執行
                            }
                        } catch (error) {
                            Swal.showValidationMessage(error.response?.data?.message || '更新失敗');
                            return false; // 阻止 SweetAlert 繼續執行
                        }
                    },
                    didOpen: () => {
                        // 在 SweetAlert 視窗打開時，使用 AJAX 載入分類編輯頁面
                        axios.get('/CampExplorer/admin/pages/articles/category_add_form.php')
                            .then((response) => {
                                document.getElementById('edit-category-container').innerHTML = response.data;

                                // 初始化 Summernote 編輯器（如果需要）
                                if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
                                    $('.summernote').summernote({
                                        height: 200,
                                        toolbar: [
                                            ['style', ['bold', 'italic', 'underline', 'clear']],
                                            ['font', ['strikethrough']],
                                            ['para', ['ul', 'ol']],
                                            ['insert', ['link']],
                                            ['view', ['fullscreen', 'codeview']]
                                        ],
                                        lang: 'zh-TW'
                                    });
                                }

                                // 檢查並處理檔案上傳大小限制（如果需要）
                                const fileInput = document.querySelector('input[type="file"]');
                                if (fileInput) {
                                    fileInput.addEventListener('change', function() {
                                        const maxSize = parseInt(this.dataset.maxSize);
                                        if (this.files[0] && this.files[0].size > maxSize) {
                                            Toast.fire({
                                                icon: 'error',
                                                title: '檔案大小不能超過 5MB'
                                            });
                                            this.value = ''; // 清空檔案選擇
                                        }
                                    });
                                }
                            })
                            .catch((error) => {
                                document.getElementById('edit-category-container').innerHTML = '<p>無法載入編輯表單，請稍後再試。</p>';
                            });
                    }
                });

                // 根據 AJAX 請求結果，顯示成功或錯誤訊息
                if (result.value?.success) {
                    await Toast.fire({
                        icon: 'success',
                        title: result.value.message || '編輯成功'
                    });
                    location.reload(); // 更新成功後重新整理頁面
                }
            } catch (error) {
                console.error('編輯分類時發生錯誤:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || '編輯失敗'
                });
            }
        },





        // edit_category 測試成功
        async handleEditCategoryArticle() {
            try {
                // 顯示 SweetAlert 視窗，並使用 AJAX 載入分類編輯頁面
                const result = await Swal.fire({
                    title: '編輯分類',
                    html: `
                        <div id="edit-category-container" class="edit-category-container">
                            <p>載入中...</p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '確定修改',
                    cancelButtonText: '取消',
                    width: '800px',
                    preConfirm: async () => {
                        try {
                            // 提交修改後的分類資料
                            const formData = new FormData(document.getElementById('editCategoryForm')); // 將表單 ID 替換為正確的名稱
                            const response = await axios.post('/CampExplorer/admin/api/articles/update_categories.php', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            });

                            if (response.data.success) {
                                return response.data; // 將成功回應傳遞給 SweetAlert
                            } else {
                                Swal.showValidationMessage(response.data.message || '更新失敗');
                                return false; // 阻止 SweetAlert 繼續執行
                            }
                        } catch (error) {
                            Swal.showValidationMessage(error.response?.data?.message || '更新失敗');
                            return false; // 阻止 SweetAlert 繼續執行
                        }
                    },
                    didOpen: () => {
                        // 在 SweetAlert 視窗打開時，使用 AJAX 載入分類編輯頁面
                        axios.get('/CampExplorer/admin/pages/articles/category_edit_form.php')
                            .then((response) => {
                                document.getElementById('edit-category-container').innerHTML = response.data;

                                // 初始化 Summernote 編輯器（如果需要）
                                if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
                                    $('.summernote').summernote({
                                        height: 200,
                                        toolbar: [
                                            ['style', ['bold', 'italic', 'underline', 'clear']],
                                            ['font', ['strikethrough']],
                                            ['para', ['ul', 'ol']],
                                            ['insert', ['link']],
                                            ['view', ['fullscreen', 'codeview']]
                                        ],
                                        lang: 'zh-TW'
                                    });
                                }

                                // 檢查並處理檔案上傳大小限制（如果需要）
                                const fileInput = document.querySelector('input[type="file"]');
                                if (fileInput) {
                                    fileInput.addEventListener('change', function() {
                                        const maxSize = parseInt(this.dataset.maxSize);
                                        if (this.files[0] && this.files[0].size > maxSize) {
                                            Toast.fire({
                                                icon: 'error',
                                                title: '檔案大小不能超過 5MB'
                                            });
                                            this.value = ''; // 清空檔案選擇
                                        }
                                    });
                                }
                            })
                            .catch((error) => {
                                document.getElementById('edit-category-container').innerHTML = '<p>無法載入編輯表單，請稍後再試。</p>';
                            });
                    }
                });

                // 根據 AJAX 請求結果，顯示成功或錯誤訊息
                if (result.value?.success) {
                    await Toast.fire({
                        icon: 'success',
                        title: result.value.message || '編輯成功'
                    });
                    location.reload(); // 更新成功後重新整理頁面
                }
            } catch (error) {
                console.error('編輯分類時發生錯誤:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || '編輯失敗'
                });
            }
        },



        //preview 測試成功
        async handlePreviewArticle(img_id) {
            try {
                // 獲取文章資料
                const response = await axios.get(`/CampExplorer/admin/api/articles/read.php?id=${img_id}`);

                if (response.data.success) {
                    const article = response.data.data;
                    // console.log(article);  // 確認文章資料是否正確取得
                    const result = await Swal.fire({
                        title: '預覽文章',
                        html: `
                        <div class="preview-container">
                            <div class="preview-box">
                                <img src="../uploads/articles/${article.image_name}" class="preview-pic">
                            </div>
                            <h1 class="mt-4">${article.title}</h1>
                            <h3 class="mb-4">${article.subtitle}</h3>
                            <p class="info text-black-50 mb-5"><i class="fa-solid fa-pen-to-square"></i> ${article.creator_name}　|　${article.category_name}　|　${article.created_date}　<button class="btn btn-sm btn-primary"><i class="fa-solid fa-thumbs-up"></i> 讚 ${article.like_count}</button></p>
                            ${article.content}
                        </div>
                        `,
                        showCancelButton: true,
                        showConfirmButton: false,
                        confirmButtonText: '確認',
                        cancelButtonText: '關閉',
                        width: '800px',

                        preConfirm: async () => {

                        }
                    });

                    if (result.value?.success) {
                        await Toast.fire({
                            icon: 'success',
                            title: '修改成功'
                        });
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Preview article error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || '預覽失敗'
                });
            }
        },






        async handleAddArticle() {
            try {

                // 先獲取類別資料
                const categoryResponse = await axios.get('/CampExplorer/admin/api/articles/categories.php');
                const categories = categoryResponse.data.data;

                // 動態生成選項
                // const categorySelect = document.querySelector(".article_categories");
                // console.log(categorySelect);
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                });

                const optionsHtml = categories
                    .map(category => `<option value="${category.id}">${category.name}</option>`)
                    .join('');

                // console.log(optionsHtml);
                // console.log(categories);


                const result = await Swal.fire({
                    title: '新增文章',
                    html: `
                    <form id="addArticleForm" class="text-start">
                        <div class="mb-3">
                            <label class="form-label required">文章主標題</label>
                            <input type="text" class="form-control" name="title" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">文章副標題</label>
                            <input type="text" class="form-control" name="subtitle" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">文章內容</label>
                            <textarea class="form-control summernote" name="content" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">封面圖片</label>
                            <input type="file" class="form-control" name="cover_image" 
                                accept="image/jpeg,image/jpg,image/png,image/avif"
                                data-max-size="5242880" required>
                            <small class="text-muted">支援 JPG、PNG 或 AVIF 格式，檔案大小不超過 5MB</small>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-6">
                                <label class="form-label">狀態</label>
                                <select class="form-select" name="status">
                                    <option value="1">啟用</option>
                                    <option value="0">停用</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">文章類型</label>
                                <select class="form-select" name="article_category">
                                    ${optionsHtml}
                                </select>
                            </div>
                        </div>
                    </form>
                `,

                    showCancelButton: true,
                    confirmButtonText: '確定新增',
                    cancelButtonText: '取消',
                    width: '800px',
                    didOpen: () => {
                        if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
                            $('.summernote').summernote({
                                height: 200,
                                toolbar: [
                                    ['style', ['bold', 'italic', 'underline', 'clear']],
                                    ['font', ['strikethrough']],
                                    ['para', ['ul', 'ol']],
                                    ['insert', ['link']],
                                    ['view', ['fullscreen', 'codeview']]
                                ],
                                lang: 'zh-TW'
                            });
                        }

                        // 添加檔案大小驗證
                        const fileInput = document.querySelector('input[type="file"]');
                        fileInput.addEventListener('change', function() {
                            const maxSize = parseInt(this.dataset.maxSize);
                            if (this.files[0] && this.files[0].size > maxSize) {
                                Toast.fire({
                                    icon: 'error',
                                    title: '檔案大小不能超過 5MB'
                                });
                                this.value = '';
                            }
                        });
                    },
                    preConfirm: async () => {
                        try {
                            const form = document.getElementById('addArticleForm');
                            const formData = new FormData(form);
                            formData.set('content', $('.summernote').summernote('code'));

                            const response = await axios.post('/CampExplorer/admin/api/articles/create.php', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            });
                            return response.data;
                        } catch (error) {
                            Swal.showValidationMessage(error.response?.data?.message || '新增失敗');
                            return false;
                        }
                    }
                });

                if (result.value?.success) {
                    await Toast.fire({
                        icon: 'success',
                        title: '新增成功'
                    });
                    location.reload();
                }
            } catch (error) {
                console.error('Add article error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || '新增失敗'
                });
            }
        },

        async handleEditArticle(id) {
            try {


                // 先獲取類別資料
                const categoryResponse = await axios.get('/CampExplorer/admin/api/articles/categories.php');
                const categories = categoryResponse.data.data;

                // 獲取文章資料
                const response = await axios.get(`/CampExplorer/admin/api/articles/read.php?id=${id}`);
                // console.log(response.data);  // 檢查 API 回應的資料
                if (response.data.success) {
                    const article = response.data.data;

                    // 動態生成選項並設定 selected
                    const optionsHtml = categories
                        .map(category => `
                            <option value="${category.id}" ${category.id === article.article_category ? 'selected' : ''}>
                                ${category.name}
                            </option>
                        `)
                        .join('');

                    // console.log(article);  // 確認文章資料是否正確取得
                    const result = await Swal.fire({
                        title: '編輯文章',
                        html: `
                        <form id="editArticleForm" class="text-start" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label required">文章主標題</label>
                                <input type="text" class="form-control" name="title" value="${article.title}" required maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">文章副標題</label>
                                <input type="text" class="form-control" name="subtitle" value="${article.subtitle}" required maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">文章內容</label>
                                <textarea class="form-control summernote" name="content" 
                                    required rows="10">${article.content}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">目前封面圖片</label>
                                ${article.image_name ? 
                                    `<img src="../uploads/articles/${article.image_name}" class="img-thumbnail d-block mb-2" style="max-height: 100px">` 
                                    : '<p class="text-muted">無封面圖片</p>'}
                                <input type="file" class="form-control" name="cover_image" accept="image/*">
                            </div>

                            <div class="row">
                                <div class="mb-3 col-6">
                                    <label class="form-label">狀態</label>
                                    <select class="form-select" name="status">
                                        <option value="1" ${article.status === 1 ? 'selected' : ''}>啟用</option>
                                        <option value="0" ${article.status === 0 ? 'selected' : ''}>停用</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">文章類型</label>
                                    <select class="form-select" name="article_category">
                                        ${optionsHtml}
                                    </select>
                                </div>
                            </div>

                        </form>
                    `,
                        showCancelButton: true,
                        confirmButtonText: '確定修改',
                        cancelButtonText: '取消',
                        width: '800px',
                        didOpen: () => {
                            if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
                                $('.summernote').summernote({
                                    height: 200,
                                    toolbar: [
                                        ['style', ['bold', 'italic', 'underline', 'clear']],
                                        ['font', ['strikethrough']],
                                        ['para', ['ul', 'ol']],
                                        ['insert', ['link']],
                                        ['view', ['fullscreen', 'codeview']]
                                    ],
                                    lang: 'zh-TW'
                                });
                            } else {
                                console.error('Summernote is not loaded');
                            }
                        },
                        preConfirm: async () => {
                            try {

                                // 獲取 cookie 的值
                                const getCookie = (name) => {
                                    const decodedCookie = decodeURIComponent(document.cookie);
                                    const cookies = decodedCookie.split(';');
                                    for (let i = 0; i < cookies.length; i++) {
                                        let c = cookies[i].trim();
                                        if (c.indexOf(name + "=") == 0) {
                                            return c.substring(name.length + 1, c.length);
                                        }
                                    }
                                    return "";
                                };
                                // 在表單提交之前附加文章 ID 到 FormData 中
                                const form = document.getElementById('editArticleForm');
                                const formData = new FormData(form);

                                // 取得 cookie 中的文章 ID
                                const articleId = getCookie('articleId');
                                if (articleId) {
                                    formData.append('id', articleId); // 把 ID 加到 FormData 裡
                                }

                                const response = await axios.post('/CampExplorer/admin/api/articles/update.php', formData);

                                return response.data;
                            } catch (error) {
                                Swal.showValidationMessage(error.response?.data?.message || '修改失敗');
                                return false;
                            }
                        }
                    });

                    if (result.value?.success) {
                        await Toast.fire({
                            icon: 'success',
                            title: '修改成功'
                        });
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Edit article error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || '修改失敗'
                });
            }
        },

        async handleToggleStatus(id, currentStatus) {
            try {
                const result = await Swal.fire({
                    title: '確認作',
                    text: `確定要${currentStatus ? '停用' : '啟用'}此文章嗎？`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: currentStatus ? '確定停用' : '確定啟用',
                    cancelButtonText: '取消',
                    confirmButtonColor: currentStatus ? '#dc3545' : '#198754'
                });

                if (result.isConfirmed) {
                    const response = await axios.post('/CampExplorer/admin/api/articles/toggle-status.php', {
                        id: id,
                        status: currentStatus ? 0 : 1
                    });

                    if (response.data.success) {
                        await Toast.fire({
                            icon: 'success',
                            title: response.data.message || `${currentStatus ? '停用' : '啟用'}成功`
                        });
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Toggle status error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || '操作失敗'
                });
            }
        },

        renderArticles(articles) {
            if (!articles.length) {
                return '<tr><td colspan="9" class="text-center">目前沒有文章資料</td></tr>';
            }

            return articles.map(article => `
            <tr>
                <td class="article-image-cell">
                    ${article.image_name ? 
                        `<div class="article-image-wrapper">
                            <img src="../uploads/articles/${article.image_name}" class="article-image" alt="文章封面">
                         </div>` : 
                        '<span class="text-muted">無圖片</span>'}
                </td>
                <td>${this.escapeHtml(article.category_name)}</td>
                <td>${this.escapeHtml(article.title)}</td>
                <td>
                    <span class="fs-6 badge ${article.status ? 'bg-success' : 'bg-secondary'}">
                        ${article.status ? '啟用中' : '停用中'}
                    </span>
                </td>
                <td>${article.views}</td>
                <td>${article.like_count}</td>
                <td>${this.htmlspecialchars(article.admin_name)}</td>
                <td>${this.formatDate(article.updated_at)}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary fs-6" 
                            data-action="edit"
                            data-id="${article.id}">
                            <i class="bi bi-pencil-square me-1"></i>編輯
                        </button>
                        <button type="button" 
                            class="btn btn-sm fs-6 ${article.status ? 'btn-danger' : 'btn-success'}"
                            data-action="toggle-status"
                            data-id="${article.id}"
                            data-status="${article.status}">
                            <i class="bi bi-toggle-${article.status ? 'on' : 'off'} me-1"></i>
                            ${article.status ? '停用' : '啟用'}
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleString('zh-TW', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };

    // 初始化
    document.addEventListener('DOMContentLoaded', () => {
        ArticleUI.init();
    });




    // 排序選單的 JS 相關
    // DOM 元素
    const sortSelect = document.getElementById('sort-select');
    const sortOrderInput = document.getElementById('sort-order');
    const sortToggleBtn = document.getElementById('sort-toggle-btn');

    // 獲取當前 URL 參數
    function getUrlParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    // 更新排序順序並重載頁面
    sortToggleBtn.addEventListener('click', function(event) {
        event.preventDefault(); // 防止表單提交

        // 取得當前排序方式
        const currentSortOrder = getUrlParam('sort_order') || 'DESC'; // 預設為 DESC
        const currentSortBy = getUrlParam('sort_by') || 'articles.updated_at'; // 預設為更新時間

        // 切換排序順序
        const newSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';

        // 更新 URL 上的 sort_order 參數
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('sort_order', newSortOrder); // 切換排序順序
        currentUrl.searchParams.set('sort_by', currentSortBy); // 保留排序欄位

        // 重新載入頁面
        window.location.href = currentUrl.toString(); // 使用新的 URL 重新載入
    });

    // 當選擇排序條件變更時，更新排序順序並重載頁面
    sortSelect.addEventListener('change', function() {
        const selectedSortBy = this.value; // 使用選擇的排序欄位

        if (selectedSortBy) {
            // 更新排序順序為 DESC，並修改 URL 參數
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort_by', selectedSortBy); // 更新排序欄位
            currentUrl.searchParams.set('sort_order', 'DESC'); // 預設排序為 DESC

            // 重新載入頁面
            window.location.href = currentUrl.toString(); // 使用新的 URL 重新載入
        } else {
            // 如果選擇了空白選項（預設的排序選項）
            window.location.href = 'index.php?page=articles_list'; // 重定向到 articles_list 頁面
        }
    });

    // 更新排序按鈕的啟用/禁用狀態
    function updateSortButtonState() {
        if (!sortSelect.value) { // 如果排序選單為預設（未選擇排序）
            sortToggleBtn.disabled = true; // 禁用排序切換按鈕
        } else {
            sortToggleBtn.disabled = false; // 否則啟用排序切換按鈕
        }
    }

    // 頁面加載後，根據當前的排序選單狀態更新按鈕狀態
    window.addEventListener('load', function() {
        updateSortButtonState(); // 初始檢查並更新按鈕狀態
    });

    // 當排序選單變更時，更新按鈕的啟用/禁用狀態
    sortSelect.addEventListener('change', updateSortButtonState);



    //清除按鈕 = 重新導向文章列表頁
    document.getElementById('clear-filters-btn').addEventListener('click', function() {
        // 重新導向到 index.php?page=articles_list，並清除所有篩選參數
        window.location.href = 'index.php?page=articles_list';
    });




    // 處理編輯文章找不到文章 ID 的狀況
    // 設置 cookie 函數
    const setCookie = (name, value, hours) => {
        const d = new Date();
        d.setTime(d.getTime() + (hours * 60 * 60 * 1000)); // 設置有效期為指定小時
        const expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    };

    // 監聽編輯按鈕的點擊事件
    document.querySelectorAll('button[data-action="edit"]').forEach(button => {
        button.addEventListener('click', function() {
            const articleId = this.getAttribute('data-id'); // 取得文章 ID

            if (articleId) {
                // 設置 cookie，ID 會被存儲，並設置有效期為 1 小時
                setCookie('articleId', articleId, 1);
                console.log("文章 ID 已存儲到 cookie:", articleId); // 可選，檢查是否正確設置
            }
        });
    });
</script>

<style>
    #searchBar {
        width: 220px;        
    }
    #filterForm {
        select {
            z-index: 1;
        }

        select:focus {
            z-index: 10;
        }
    }

    .article-image-cell {
        width: 120px;
        height: 80px;
        vertical-align: middle;
    }

    .article-image-wrapper {
        width: 120px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background-color: #f8f9fa;
    }

    .article-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    table.article-table {
        .article-image-wrapper {
            cursor: pointer;
        }

        td {
            line-height: 97px;
        }
    }

    .preview-container {
        text-align: left;

        .preview-box {
            height: 300px;
            position: relative;
            overflow: hidden;
        }

        .preview-pic {
            position: absolute;
            top: 50%;
            left: 50%;
            display: block;
            max-width: 100%;
            transform: translate(-50%, -50%);
        }

        h1 {
            font-weight: 900;
        }

        h3 {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .info {
            font-size: 1rem;
        }

        p {
            line-height: 2rem;
            letter-spacing: .5px;
        }

        p:nth-child(2) {
            background-color: #000;
        }
    }
</style>