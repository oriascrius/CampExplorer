<?php
// 載入資料庫連線設定
require_once __DIR__ . '/../../../camping_db.php';

try {
    // 使用 PDO 建立資料庫連線
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_pass);

    // 設定 PDO 錯誤模式為異常
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查詢分類資料和每個分類的文章數量
    $sql = "
    SELECT 
        ac.id, 
        ac.name, 
        ac.sort_order, 
        ac.status, 
        COUNT(a.id) AS article_count 
    FROM 
        article_categories ac
    LEFT JOIN 
        articles a 
    ON 
        ac.id = a.article_category
    GROUP BY 
        ac.id
    ";

    $stmt = $conn->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 連線失敗或查詢錯誤，捕捉錯誤訊息
    echo "資料庫連線錯誤: " . $e->getMessage();
}
?>

<div class="category-edit-wrapper">
    <form id="addCategoryForm">
        <!-- 新增分類表格 -->
        <table class="category-table table table-bordered">
            <thead>
                <tr>
                    <th>分類名稱</th>
                    <th>排列順序</th>
                    <th>狀態</th>
                </tr>
            </thead>
            <tbody>
                <!-- 動態生成新增欄位 -->
                <tr>
                    <!-- 分類名稱：文字輸入框 -->
                    <td>
                        <input type="text" name="categories[0][name]" value="" class="category-name-input form-control" required>
                    </td>
                    <!-- 排列順序：文字輸入框 -->
                    <td>
                        <input type="number" name="categories[0][sort_order]" value="" class="category-order-input form-control" required>
                    </td>
                    <!-- 狀態：選項按鈕 -->
                    <td class="align-middle text-center">
                        <label>
                            <input type="radio" name="categories[0][status]" value="1"> 啟用
                        </label>
                        <label>
                            <input type="radio" name="categories[0][status]" value="0"> 停用
                        </label>
                    </td>
                </tr>
                <tr>
                    <!-- 分類名稱：文字輸入框 -->
                    <td>
                        <input type="text" name="categories[1][name]" value="" class="category-name-input form-control" required>
                    </td>
                    <!-- 排列順序：文字輸入框 -->
                    <td>
                        <input type="number" name="categories[1][sort_order]" value="" class="category-order-input form-control" required>
                    </td>
                    <!-- 狀態：選項按鈕 -->
                    <td class="align-middle text-center">
                        <label>
                            <input type="radio" name="categories[1][status]" value="1"> 啟用
                        </label>
                        <label>
                            <input type="radio" name="categories[1][status]" value="0"> 停用
                        </label>
                    </td>
                </tr>
                <tr>
                    <!-- 分類名稱：文字輸入框 -->
                    <td>
                        <input type="text" name="categories[2][name]" value="" class="category-name-input form-control" required>
                    </td>
                    <!-- 排列順序：文字輸入框 -->
                    <td>
                        <input type="number" name="categories[2][sort_order]" value="" class="category-order-input form-control" required>
                    </td>
                    <!-- 狀態：選項按鈕 -->
                    <td class="align-middle text-center">
                        <label>
                            <input type="radio" name="categories[2][status]" value="1"> 啟用
                        </label>
                        <label>
                            <input type="radio" name="categories[2][status]" value="0"> 停用
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

<script>
    // 新增分類
    document.getElementById('addCategoryForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(event.target);

        try {
            const response = await axios.post('/CampExplorer/admin/api/articles/add_category.php', formData);

            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '新增成功'
                });
                // 重新載入分類列表
                loadCategoryEditForm();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '新增失敗',
                    text: response.data.message
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: '無法新增分類'
            });
        }
    });

    // 重新載入分類表單
    async function loadCategoryEditForm() {
        const response = await axios.get('/CampExplorer/admin/api/articles/category_edit_form.php');
        document.getElementById('edit-category-container').innerHTML = response.data;
    }
</script>
