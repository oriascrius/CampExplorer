<?php
require_once __DIR__ . '/../../../camping_db.php';

// 獲取文章列表
try {
    $articles_sql = "SELECT id, title, content, cover_image, status, views, created_at, updated_at 
                    FROM articles 
                    ORDER BY sort_order, created_at DESC";
    $articles_stmt = $db->prepare($articles_sql);
    $articles_stmt->execute();
    $articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "資料載入失敗，請稍後再試";
}
?>

<!-- 在頁面頂部添加，約在第 2-3 行之間 -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-zh-TW.min.js"></script>

<!-- 主要內容 -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0">文章管理</h5>
                        <small class="text-muted">管理官方文章內容</small>
                    </div>
                    <button type="button" class="btn btn-primary" data-action="add">
                        <i class="bi bi-plus-lg me-1"></i>新增文章
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>封面圖片</th>
                                    <th>
                                        <button type="button" class="btn btn-link text-dark p-0" data-sort="title">
                                            標題 <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="btn btn-link text-dark p-0" data-sort="status">
                                            狀態 <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="btn btn-link text-dark p-0" data-sort="views">
                                            瀏覽次數 <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="btn btn-link text-dark p-0" data-sort="created_at">
                                            建立時間 <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </th>
                                    <th>
                                        <button type="button" class="btn btn-link text-dark p-0" data-sort="updated_at">
                                            更新時間 <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td class="article-image-cell">
                                        <?php if ($article['cover_image']): ?>
                                            <div class="article-image-wrapper">
                                                <img src="<?= htmlspecialchars($article['cover_image']) ?>" class="article-image">
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">無圖片</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($article['title']) ?></td>
                                    <td>
                                        <span class="badge <?= $article['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $article['status'] ? '啟用中' : '停用中' ?>
                                        </span>
                                    </td>
                                    <td><?= $article['views'] ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($article['created_at'])) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($article['updated_at'])) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-action="edit"
                                                data-id="<?= $article['id'] ?>">
                                                <i class="bi bi-pencil-square me-1"></i>編輯
                                            </button>
                                            <button type="button" 
                                                class="btn btn-sm <?= $article['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
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
                </div>
            </div>
        </div>
    </div>
</div>

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
        this.initSortButtons();
    },

    initEventListeners() {
        const table = document.querySelector('table');
        if (table) {
            table.addEventListener('click', async (e) => {
                const button = e.target.closest('button');
                if (!button) return;

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
            });
        }

        // 新增按鈕事件監聽
        const addButton = document.querySelector('button[data-action="add"]');
        if (addButton) {
            addButton.addEventListener('click', () => this.handleAddArticle());
        }
    },

    async handleAddArticle() {
        try {
            const result = await Swal.fire({
                title: '新增文章',
                html: `
                    <form id="addArticleForm" class="text-start">
                        <div class="mb-3">
                            <label class="form-label required">文章標題</label>
                            <input type="text" class="form-control" name="title" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">文章內容</label>
                            <textarea class="form-control summernote" name="content" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">封面圖片</label>
                            <input type="file" class="form-control" name="cover_image" 
                                accept="image/jpeg,image/jpg,image/png,image/avif"
                                data-max-size="5242880">
                            <small class="text-muted">支援 JPG、PNG 或 AVIF 格式，檔案大小不超過 5MB</small>
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
            const response = await axios.get(`/CampExplorer/admin/api/articles/read.php?id=${id}`);
            if (response.data.success) {
                const article = response.data.data;
                const result = await Swal.fire({
                    title: '編輯文章',
                    html: `
                        <form id="editArticleForm" class="text-start">
                            <input type="hidden" name="id" value="${article.id}">
                            <div class="mb-3">
                                <label class="form-label required">文標題</label>
                                <input type="text" class="form-control" name="title" 
                                    value="${article.title}" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">文章內容</label>
                                <textarea class="form-control summernote" name="content" 
                                    required rows="10">${article.content}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">目前封面圖片</label>
                                ${article.cover_image ? 
                                    `<img src="${article.cover_image}" class="img-thumbnail d-block mb-2" style="max-height: 200px">` 
                                    : '<p class="text-muted">無封面圖片</p>'}
                                <input type="file" class="form-control" name="cover_image" accept="image/*">
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
                            const form = document.getElementById('editArticleForm');
                            const formData = new FormData(form);
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

    initSortButtons() {
        const thead = document.querySelector('thead');
        if (!thead) return;

        thead.addEventListener('click', async (e) => {
            const sortBtn = e.target.closest('button[data-sort]');
            if (!sortBtn) return;

            const field = sortBtn.dataset.sort;
            
            // 更新排序狀態
            this.currentSort.order = this.currentSort.field === field && 
                this.currentSort.order === 'asc' ? 'desc' : 'asc';
            this.currentSort.field = field;

            // 更新 URL 參數
            const url = new URL(window.location.href);
            url.searchParams.set('sort', field);
            url.searchParams.set('order', this.currentSort.order);
            window.history.pushState({}, '', url);

            // 更新排序圖示
            this.updateSortIcons();

            // 重新載入文章列表
            await this.loadArticles();
        });
    },

    async loadArticles() {
        try {
            const response = await fetch(
                `/CampExplorer/admin/api/articles/read.php?sort=${this.currentSort.field}&order=${this.currentSort.order}`
            );
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            // 更新表格內容
            const tbody = document.querySelector('tbody');
            tbody.innerHTML = this.renderArticles(result.data);
        } catch (error) {
            console.error('Error:', error);
            await Swal.fire('錯誤', error.message, 'error');
        }
    },

    updateSortIcons() {
        document.querySelectorAll('thead button[data-sort]').forEach(btn => {
            const field = btn.dataset.sort;
            const icon = btn.querySelector('i');
            if (field === this.currentSort.field) {
                icon.className = `bi bi-arrow-${this.currentSort.order === 'asc' ? 'up' : 'down'}`;
            } else {
                icon.className = 'bi bi-arrow-down-up';
            }
        });
    },

    renderArticles(articles) {
        if (!articles.length) {
            return '<tr><td colspan="7" class="text-center">目前沒有文章資料</td></tr>';
        }

        return articles.map(article => `
            <tr>
                <td class="article-image-cell">
                    ${article.cover_image ? 
                        `<div class="article-image-wrapper">
                            <img src="${article.cover_image}" class="article-image" alt="文章封面">
                         </div>` : 
                        '<span class="text-muted">無圖片</span>'}
                </td>
                <td>${this.escapeHtml(article.title)}</td>
                <td>
                    <span class="badge ${article.status ? 'bg-success' : 'bg-secondary'}">
                        ${article.status ? '啟用中' : '停用中'}
                    </span>
                </td>
                <td>${article.views}</td>
                <td>${this.formatDate(article.created_at)}</td>
                <td>${this.formatDate(article.updated_at)}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                            data-action="edit"
                            data-id="${article.id}">
                            <i class="bi bi-pencil-square me-1"></i>編輯
                        </button>
                        <button type="button" 
                            class="btn btn-sm ${article.status ? 'btn-outline-danger' : 'btn-outline-success'}"
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

document.addEventListener('DOMContentLoaded', function() {
    $('.summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough']],
            ['para', ['ul', 'ol']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview']]
        ],
        lang: 'zh-TW',
        placeholder: '請輸入內容...',
        callbacks: {
            onChange: function(contents) {
                // 更新原始 textarea 的值，確保表單驗證正常運作
                this.value = contents;
                validateField(this);
                updateProgress();
            }
        }
    });
});
</script>

<style>
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
</style>