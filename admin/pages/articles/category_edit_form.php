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
    <form id="editCategoryForm">
        <!-- 編輯分類表格 -->
        <table class="category-table table table-bordered">
            <thead>
                <tr>
                    <th>分類名稱</th>
                    <th>數量</th>
                    <th>排列順序</th>
                    <th>狀態</th>
                    <th>刪除</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <!-- 分類名稱 -->
                        <td>
                            <input
                                type="text"
                                name="categories[<?= $category['id']; ?>][name]"
                                value="<?= htmlspecialchars($category['name']); ?>"
                                class="category-name-input form-control">
                        </td>

                        <!-- 文章數量 -->
                        <td class="text-center align-middle">
                            <?= htmlspecialchars($category['article_count']); ?>
                        </td>

                        <!-- 排列順序 -->
                        <td>
                            <input
                                type="text"
                                name="categories[<?= $category['id']; ?>][sort_order]"
                                value="<?= htmlspecialchars($category['sort_order']); ?>"
                                class="category-order-input form-control">
                        </td>

                        <!-- 狀態 -->
                        <td class="align-middle text-center">
                            <label>
                                <input
                                    type="radio"
                                    name="categories[<?= $category['id']; ?>][status]"
                                    value="1"
                                    <?= $category['status'] == 1 ? 'checked' : ''; ?>>
                                啟用
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    name="categories[<?= $category['id']; ?>][status]"
                                    value="0"
                                    <?= $category['status'] == 0 ? 'checked' : ''; ?>>
                                停用
                            </label>
                        </td>

                        <!-- 刪除 -->
                        <td class="align-middle text-center">
                            <input
                                type="checkbox"
                                name="categories[<?= $category['id']; ?>][delete]"
                                value="1"
                                class="delete-checkbox"
                                <?= $category['article_count'] > 0 ? 'disabled' : ''; ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>




</div>

<script>
    // 切換分類狀態
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const categoryId = button.dataset.id;
            const currentStatus = button.dataset.status;

            try {
                const response = await axios.post('/CampExplorer/admin/api/articles/toggle_category_status.php', {
                    id: categoryId,
                    status: currentStatus
                });

                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '狀態更新成功'
                    });
                    // 重新載入分類列表
                    loadCategoryEditForm();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '狀態更新失敗',
                        text: response.data.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '無法更新分類狀態'
                });
            }
        });
    });

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