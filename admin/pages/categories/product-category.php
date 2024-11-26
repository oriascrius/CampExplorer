<?php
require_once __DIR__ . '/../../../camping_db.php';

// 獲取分類數據
try {
    // 獲取所有主分類
    $main_categories_sql = "SELECT id, name, sort_order, status 
                           FROM categories 
                           ORDER BY sort_order";
    $main_categories_stmt = $db->prepare($main_categories_sql);
    $main_categories_stmt->execute();
    $main_categories = $main_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取所有子分類，不論狀態
    $sub_categories_sql = "SELECT s.*, c.name as parent_name 
FROM subcategories s 
JOIN categories c ON s.category_id = c.id 
WHERE c.status = 1 
ORDER BY c.sort_order, s.sort_order";
    $sub_categories_stmt = $db->prepare($sub_categories_sql);
    $sub_categories_stmt->execute();
    $sub_categories = [];
    while ($row = $sub_categories_stmt->fetch(PDO::FETCH_ASSOC)) {
        $sub_categories[$row['category_id']][] = $row;
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}
?>

<!-- 主要內容 -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0">商品分類管理</h5>
                        <small class="text-muted">管理商品的主分類與子分類</small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="handleAddCategory()">
                        <i class="bi bi-plus-lg me-1"></i>新增主分類
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($main_categories as $category): ?>
                            <div class="list-group-item category-item">
                                <div class="d-flex align-items-center p-3" role="button" onclick="handleCategoryClick(<?= $category['id'] ?>)">
                                    <i class="bi bi-chevron-right category-icon me-3"></i>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><?= htmlspecialchars($category['name']) ?></span>
                                            <span class="badge <?= $category['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $category['status'] ? '啟用中' : '停用中' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="handleEditCategory(<?= $category['id'] ?>)"
                                            data-sort-order="<?= $category['sort_order'] ?>">
                                            編輯
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm <?= $category['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            onclick="handleToggleStatus(<?= $category['id'] ?>, <?= $category['status'] ?>)">
                                            <?= $category['status'] ? '停用' : '啟用' ?>
                                        </button>
                                    </div>
                                </div>

                                <!-- 子分類列表 -->
                                <div class="subcategory-list collapse" id="category<?= $category['id'] ?>">
                                    <?php if (isset($sub_categories[$category['id']])): ?>
                                        <?php foreach ($sub_categories[$category['id']] as $subcategory): ?>
                                            <div class="subcategory-item p-3 ps-5">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <span class="me-2"><?= htmlspecialchars($subcategory['name']) ?></span>
                                                        <span class="badge <?= $subcategory['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $subcategory['status'] ? '啟用中' : '停用中' ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex gap-2 action-buttons">
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="handleEditSubcategory(<?= $subcategory['id'] ?>)">
                                                            編輯
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm <?= $subcategory['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                            onclick="handleToggleSubcategoryStatus(<?= $subcategory['id'] ?>, <?= $subcategory['status'] ?>)">
                                                            <?= $subcategory['status'] ? '停用' : '啟用' ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-3 ps-5 text-muted">
                                            <small>暫無子分類</small>
                                        </div>
                                    <?php endif; ?>
                                    <!-- 新增子分類按鈕 -->
                                    <div class="p-3 ps-5">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="handleAddSubcategory(<?= $category['id'] ?>)">
                                            <i class="bi bi-plus-lg me-1"></i>新增子分類
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .category-item {
        transition: all 0.2s;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .category-item:hover {
        background-color: #f8f9fa;
    }

    .category-icon {
        transition: transform 0.3s ease;
    }

    .category-icon.rotate {
        transform: rotate(90deg);
    }

    .category-item .d-flex[role="button"] {
        cursor: pointer;
    }

    .category-item .d-flex[role="button"]:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .subcategory-item {
        transition: all 0.2s;
        background-color: white;
    }

    .subcategory-item:hover {
        background-color: #f8f9fa;
    }

    .action-buttons {
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .category-item:hover .action-buttons,
    .subcategory-item:hover .action-buttons {
        opacity: 1;
    }

    .subcategory-list {
        background-color: rgba(0, 0, 0, .03);
    }

    .required:after {
        content: " *";
        color: red;
    }

    .swal2-popup {
        font-size: 0.9rem;
    }

    .swal2-popup .form-label {
        text-align: left;
        display: block;
        margin-bottom: 0.5rem;
    }

    .swal2-popup .form-text {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .swal2-popup .form-control,
    .swal2-popup .form-select {
        font-size: 0.9rem;
    }
</style>

<script>
    // 通用工具函數
    function showLoading(title = '處理中...') {
        Swal.fire({
            title,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    // Toast 通配
    // 通用提示函
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1000,
        timerProgressBar: true,
        didOpen: (toast) => {
            // 當滑鼠移入時，暫停計時器
            toast.addEventListener('mouseenter', Swal.stopTimer)
            // 當滑鼠移出時，恢復計時器
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    const showAlert = (title, text, icon) => {
        return Swal.fire({
            title,
            text,
            icon,
            confirmButtonText: '確定'
        });
    };

    // API 工具函數
    const API = {
        categories: {
            get: async (id) => {
                const response = await axios.get(`/CampExplorer/admin/api/categories/read.php?action=category&id=${id}`);
                if (!response.data.success) {
                    throw new Error(response.data.message);
                }
                return response.data;
            },
            update: async (data) => {
                const response = await axios.post('/CampExplorer/admin/api/categories/update.php?action=category', data);
                if (!response.data.success) {
                    throw new Error(response.data.message);
                }
                return response.data;
            },
            delete: async (id) => {
                const response = await axios.get(`/CampExplorer/admin/api/categories/delete.php?action=category&id=${id}`);
                if (!response.data.success) {
                    throw new Error(response.data.message);
                }
                return response.data;
            }
        },
        subcategories: {
            get: (id) => axios.get(`/CampExplorer/admin/api/categories/read.php?action=subcategory&id=${id}`),
            create: (data) => axios.post('/CampExplorer/admin/api/categories/create.php?action=subcategory', data),
            update: (data) => axios.post('/CampExplorer/admin/api/categories/update.php?action=subcategory', data),
            delete: (id) => axios.get(`/CampExplorer/admin/api/categories/delete.php?action=subcategory&id=${id}`)
        }
    };

    // 主分類處理函數
    async function handleAddCategory() {
        try {
            const {
                value: formData
            } = await Swal.fire({
                title: '新增主分類',
                html: `
                    <form id="addCategoryForm" class="text-start">
                        <div class="mb-3">
                            <label class="form-label required">分類名稱</label>
                            <input type="text" class="form-control" name="name" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">狀態</label>
                            <select class="form-select" name="status">
                                <option value="1">啟用</option>
                                <option value="0">停用</option>
                            </select>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '確定新增',
                cancelButtonText: '取消',
                preConfirm: async () => {
                    try {
                        const form = document.getElementById('addCategoryForm');
                        const formData = new FormData(form);
                        const data = Object.fromEntries(formData);

                        console.log('Sending category data:', data);

                        const response = await axios.post('/CampExplorer/admin/api/categories/create.php?action=category', data);
                        return response.data;
                    } catch (error) {
                        console.error('Add category error:', error);
                        Swal.showValidationMessage(error.response?.data?.message || '新增失敗');
                        return false;
                    }
                }
            });

            if (formData?.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '新增成功',
                    timer: 1500
                });
                location.reload();
            }
        } catch (error) {
            console.error('Handle add category error:', error);
            Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: error.response?.data?.message || '新增失敗'
            });
        }
    }

    async function handleEditCategory(id) {
        try {
            const categoryElement = document.querySelector(`button[onclick*="handleEditCategory(${id})"]`)
                .closest('.category-item');
            const categoryName = categoryElement.querySelector('.flex-grow-1 span:first-child').textContent.trim();

            const result = await Swal.fire({
                title: '編輯主分類',
                html: `
                    <form id="editCategoryForm" class="text-start">
                        <input type="hidden" name="id" value="${id}">
                        <div class="mb-3">
                            <label class="form-label">分類名稱</label>
                            <input type="text" class="form-control" name="name" 
                                value="${categoryName}" maxlength="50" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '確定修改',
                cancelButtonText: '取消',
                preConfirm: async () => {
                    try {
                        const form = document.getElementById('editCategoryForm');
                        const data = {
                            id: form.querySelector('[name="id"]').value,
                            name: form.querySelector('[name="name"]').value.trim()
                        };
                        const response = await axios.post('/CampExplorer/admin/api/categories/update.php?action=category', data);
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
        } catch (error) {
            console.error('Edit category error:', error);
            await Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: error.response?.data?.message || '操作失敗'
            });
        }
    }

    async function handleDeleteCategory(id) {
        const result = await Swal.fire({
            title: '確定要刪除嗎？',
            text: '刪除後該分類將被隱藏，但資料會保留在資料庫中',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '確定除',
            cancelButtonText: '取消',
            confirmButtonColor: '#dc3545'
        });

        if (result.isConfirmed) {
            try {
                const response = await API.categories.delete(id);
                await Toast.fire({
                    icon: 'success',
                    title: response.message || '刪除成功'
                });
                location.reload();
            } catch (error) {
                console.error('Delete error:', error);
                showAlert('錯誤', error.response?.data?.message || '刪除失敗', 'error');
            }
        }
    }

    // 子分類相關函數
    async function handleAddSubcategory(categoryId) {
        try {
            const {
                value: formData
            } = await Swal.fire({
                title: '新增子分類',
                html: `
                    <form id="addSubcategoryForm" class="text-start">
                        <div class="mb-3">
                            <label class="form-label required">子分類名稱</label>
                            <input type="text" class="form-control" name="name" required maxlength="50">
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '確定新增',
                cancelButtonText: '取消',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const form = document.getElementById('addSubcategoryForm');
                        const formData = new FormData(form);
                        const data = {
                            category_id: categoryId,
                            name: formData.get('name').trim()
                        };

                        const response = await axios.post('/CampExplorer/admin/api/categories/create.php?action=subcategory', data);
                        return response.data;
                    } catch (error) {
                        Swal.showValidationMessage(error.response?.data?.message || '新增失敗');
                    }
                }
            });

            if (formData && formData.success) {
                // 顯示成功提示
                await Swal.fire({
                    icon: 'success',
                    title: formData.message || '新增成功',
                    timer: 1500,
                    showConfirmButton: false
                });

                // 使用 AdminUI 的 loadPage 方法重新載入當前頁面
                await AdminUI.loadPage(window.location.href);
            }
        } catch (error) {
            console.error('Add subcategory error:', error);
            Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: error.response?.data?.message || '新增失敗'
            });
        }
    }

    async function reloadPageContent() {
        try {
            const response = await axios.get(window.location.pathname + window.location.search);
            const parser = new DOMParser();
            const doc = parser.parseFromString(response.data, 'text/html');
            const newContent = doc.querySelector('#main-content');

            if (newContent) {
                const mainContent = document.querySelector('#main-content');
                mainContent.innerHTML = newContent.innerHTML;

                // 重新綁定事件監聽器
                initEventListeners();
            }
        } catch (error) {
            console.error('重新載入頁面內容失敗:', error);
            // 如果 AJAX 更新失敗，則重新整理頁面
            window.location.reload();
        }
    }

    async function handleEditSubcategory(id) {
        try {
            const subcategoryElement = document.querySelector(`button[onclick*="handleEditSubcategory(${id})"]`)
                .closest('.subcategory-item');
            const subcategoryName = subcategoryElement.querySelector('.flex-grow-1 span:first-child').textContent.trim();

            const result = await Swal.fire({
                title: '編輯子分類',
                html: `
                    <form id="editSubcategoryForm" class="text-start">
                        <input type="hidden" name="id" value="${id}">
                        <div class="mb-3">
                            <label class="form-label">分類名稱</label>
                            <input type="text" class="form-control" name="name" 
                                value="${subcategoryName}" maxlength="50" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '確定修改',
                cancelButtonText: '取消',
                preConfirm: async () => {
                    try {
                        const form = document.getElementById('editSubcategoryForm');
                        const data = {
                            id: form.querySelector('[name="id"]').value,
                            name: form.querySelector('[name="name"]').value.trim()
                        };
                        const response = await axios.post('/CampExplorer/admin/api/categories/update.php?action=subcategory', data);
                        return response.data;
                    } catch (error) {
                        Swal.showValidationMessage(error.response?.data?.message || '修改失敗');
                        return false;
                    }
                }
            });

            if (result.value?.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '修改成功',
                    text: '子分類名稱已更新',
                    showConfirmButton: false,
                    timer: 1500
                });
                location.reload();
            }
        } catch (error) {
            console.error('Edit subcategory error:', error);
            await Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: error.response?.data?.message || '操作失敗'
            });
        }
    }

    async function handleToggleSubcategoryStatus(id, currentStatus) {
        const action = currentStatus ? '停用' : '啟用';
        const result = await Swal.fire({
            title: `確定要${action}此子分類嗎？`,
            text: currentStatus ? '停用後，此子分類將被標記為停用狀態' : '啟用後，此子分類將恢復為啟用狀態',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `確定${action}`,
            cancelButtonText: '取消',
            confirmButtonColor: currentStatus ? '#dc3545' : '#198754'
        });

        if (result.isConfirmed) {
            try {
                const response = await axios.post('/CampExplorer/admin/api/categories/update.php?action=subcategory', {
                    id: id,
                    status: currentStatus ? 0 : 1
                });

                if (response.data.success) {
                    await Toast.fire({
                        icon: 'success',
                        title: response.data.message
                    });
                    await AdminUI.loadPage(window.location.href);
                }
            } catch (error) {
                console.error('Toggle status error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.response?.data?.message || `${action}失敗`
                });
            }
        }
    }

    // 添加通用的 UI 處理函數
    const UI = {
        toggleCategory(categoryId) {
            const icon = document.querySelector(`#category${categoryId} .category-icon`);
            if (icon) {
                icon.classList.toggle('rotate');
            }
        }
    };

    // 修改分類項的點擊處理
    function handleCategoryClick(categoryId) {
        const categoryElement = document.getElementById(`category${categoryId}`);
        if (categoryElement) {
            $(categoryElement).collapse('toggle');
            UI.toggleCategory(categoryId);
        }
    }

    async function handleToggleStatus(id, currentStatus) {
        const action = currentStatus ? '停用' : '啟用';
        const result = await Swal.fire({
            title: `確定要${action}此分類嗎？`,
            text: currentStatus ? '停用後，此分類及其子分類將被標記為停用狀態' : '啟用後，此分類將恢復為啟用狀態',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `確定${action}`,
            cancelButtonText: '取消',
            confirmButtonColor: currentStatus ? '#dc3545' : '#198754'
        });

        if (result.isConfirmed) {
            try {
                const response = await API.categories.update({
                    id: id,
                    status: currentStatus ? 0 : 1
                });

                if (response.success) {
                    await Toast.fire({
                        icon: 'success',
                        title: response.message
                    });
                    location.reload();
                }
            } catch (error) {
                console.error('Toggle status error:', error);
                showAlert('錯誤', error.response?.data?.message || `${action}失敗`, 'error');
            }
        }
    }
</script>