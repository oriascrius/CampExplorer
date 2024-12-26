<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 檢查是否已登入
if (!isset($_SESSION['owner_id'])) {
    header('Location: /CampExplorer/owner/login.php');
    exit;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>


<style>
    :root {
        --camp-primary: #4C6B74;
        --camp-primary-dark: #3A545C;
        --camp-secondary: #94A7AE;
        --camp-light: #F5F7F8;
        --camp-border: #E3E8EA;
        --camp-text: #2A4146;
        --camp-warning: #B4A197;
        --camp-warning-dark: #9B8A81;
        --camp-danger: #B47B84;
    }

    .char-count.text-danger {
        color: var(--camp-danger) !important;
    }

    .btn-outline-danger {
        color: var(--camp-danger);
        border-color: var(--camp-danger);
    }

    .btn-outline-danger:hover {
        background-color: var(--camp-danger);
        color: white;
    }

    /* SweetAlert2 按鈕樣式 */
    .btn-morandy-confirm {
        background-color: var(--camp-primary) !important;
        color: white;
    }

    .btn-morandy-cancel {
        background-color: var(--camp-secondary) !important;
        color: white;
    }

    .btn-morandy-deny {
        background-color: var(--camp-primary-dark) !important;
        color: white;
    }

    /* SweetAlert2 按樣式 */
    .swal2-popup {
        border-radius: var(--border-radius-lg) !important;
    }

    .swal2-actions button {
        border-radius: var(--border-radius-md) !important;
    }

    /* 圖片預覽的刪除按鈕 */
    .image-preview-container .btn-danger {
        border-radius: 50% !important;
        /* 持圓形 */
    }

    /* 其他按鈕和入框 */
    .btn,
    .form-control,
    .input-group,
    .alert {
        border-radius: var(--border-radius-md) !important;
    }

    /* 卡片內容區域 */
    .card-body {
        border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
    }

    /* 單區塊樣式 */
    .form-section {
        transition: opacity 0.3s ease;
        opacity: 1;
    }

    /* 步驟按鈕樣式 */
    .prev-step,
    .next-step {
        min-width: 120px;
    }

    .prev-step {
        border: 2px solid var(--camp-primary) !important;
        color: var(--camp-primary) !important;
        background-color: transparent !important;
    }

    .prev-step:hover {
        background-color: var(--camp-primary) !important;
        color: white !important;
    }

    /* 驗證提示樣式 */
    .is-invalid {
        border-color: var(--camp-danger) !important;
    }

    .invalid-feedback {
        color: var(--camp-danger);
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    /* 表單驗證樣式 */
    .form-control.is-invalid {
        border-color: var(--camp-danger);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(.375em + .1875rem) center;
        background-size: calc(.75em + .375rem) calc(.75em + .375rem);
    }

    .form-control.is-valid {
        border-color: var(--morandi-success);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(.375em + .1875rem) center;
        background-size: calc(.75em + .375rem) calc(.75em + .375rem);
    }

    .invalid-feedback {
        display: block;
        color: var(--camp-danger);
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .step.completed {
        background-color: var(--camp-light);
        color: var(--camp-text);
    }

    .step.completed::after {
        content: '✓';
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--camp-text);
    }

    /* 步驟指示器樣式優化 */
    .steps {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 2rem 0 3rem;
        position: relative;
        padding: 0 2rem;
    }


    .step {
        position: relative;
        z-index: 2;
        background: white;
        padding: 1.5rem 2.5rem;
        border-radius: 12px;
        min-width: 220px;
        text-align: center;
        border: 3px solid var(--camp-secondary);
        /* 使用次要灰藍色 */
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.15);
    }

    .step.active {
        border-color: var(--camp-primary-dark);
        /* 使用深藍綠色 */
        background: linear-gradient(135deg,
                rgba(76, 107, 116, 0.3) 0%,
                rgba(255, 255, 255, 0.95) 100%);
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(76, 107, 116, 0.25);
    }

    .step-title {
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--camp-primary-dark);
        /* 使用深藍綠色 */
        margin-bottom: 0.5rem;
    }

    .step small {
        color: var(--camp-primary);
        /* 使用主要藍綠色 */
        font-size: 1rem;
        display: block;
    }

    /* 完成步驟的樣式 */
    .step.completed {
        border-color: var(--camp-primary-dark);
        /* 使用深藍綠色 */
        background: rgba(76, 107, 116, 0.15);
    }

    .step.completed::after {
        content: '✓';
        position: absolute;
        right: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--camp-primary-dark);
        /* 使用深藍綠色 */
        font-size: 1.5rem;
        font-weight: bold;
    }

    /* 未完成步驟的樣式 */
    .step:not(.active):not(.completed) {
        background: rgba(255, 255, 255, 0.95);
        border-color: var(--camp-border);
        opacity: 0.9;
    }

    .step:not(.active):not(.completed) .step-title {
        color: var(--camp-secondary);
        /* 使用次要灰藍色 */
        opacity: 0.8;
    }

    /* 響應式調整 */
    @media (max-width: 768px) {
        .steps {
            flex-direction: column;
            gap: 1rem;
            padding: 0;
        }

        .steps::before {
            width: 2px;
            height: 100%;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
        }

        .step {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
    }

    /* 主容器 RWD 優化 */
    .container-fluid {
        margin-left: 280px;
        /* 配合側邊欄寬度 */
        padding: 2rem;
        transition: all 0.3s ease;
    }

    /* 卡片容器優化 */
    .card {
        margin: 0 auto;
        max-width: 100%;
    }

    /* RWD 調整 */
    @media (max-width: 1200px) {
        .container-fluid {
            padding: 1.5rem;
        }

        .row>.col-12 {
            padding: 0 1rem;
        }
    }

    @media (max-width: 991px) {
        .container-fluid {
            margin-left: 0;
            padding: 1rem;
        }

        .card {
            margin: 0;
            border-radius: 0;
        }

        /* 步驟指示器調整 */
        .steps {
            padding: 0 1rem;
        }

        .step {
            min-width: auto;
            padding: 1rem;
        }

        .step-title {
            font-size: 1rem;
        }

        .step small {
            font-size: 0.875rem;
        }
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding: 0.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        /* 表單元素調整 */
        .form-control,
        .form-select {
            font-size: 0.9rem;
        }

        .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }

    .page-container {
        max-width: 1600px;
        margin: 60px 100px 100px;
        padding: 2rem;
        background-color: white;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    /* RWD 調整 */
    @media (max-width: 991px) {
        .page-container {
            margin: 4rem 1rem;
            padding: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .page-container {
            margin: 3rem 0.5rem;
            padding: 1rem;
        }
    }

    body {
        background-color: var(--camp-light);
        color: var(--camp-text);
        min-height: 100vh;
        padding: 1rem 1rem 1rem 260px;
        /* 左側padding配合導覽列寬度 */
    }

    /* 上傳區塊樣式優化 */
    .upload-container {
        width: 100%;
        margin: 1rem 0;
    }

    .upload-box {
        width: 100%;
        min-height: 200px;
        border: 2px dashed var(--camp-border);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--camp-light);
    }

    .upload-box:hover {
        border-color: var(--camp-primary);
        background: rgba(76, 107, 116, 0.05);
    }

    .upload-box i {
        font-size: 2.5rem;
        color: var(--camp-primary);
        margin-bottom: 1rem;
    }

    .upload-text {
        text-align: center;
    }

    .upload-text span {
        font-size: 1.2rem;
        color: var(--camp-primary);
        display: block;
        margin-bottom: 0.5rem;
    }

    .upload-text small {
        color: var(--camp-secondary);
    }

    /* 圖片預覽容器 */
    .image-preview-container {
        width: 100%;
        margin-top: 1rem;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
    }

    .image-preview {
        width: 100%;
        height: auto;
        max-height: 300px;
        object-fit: cover;
    }

    .upload-box.dragover {
        border-color: var(--camp-primary);
        background-color: rgba(76, 107, 116, 0.05);
    }

    .upload-box .spinner-border {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .image-preview-container {
        position: relative;
        width: 100%;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-remove-image {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-remove-image:hover {
        background-color: var(--camp-primary-dark);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    /* 狀態標籤樣式 */
    .status-badge {
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-block;
    }

    /* 使用營地狀態的顏色變數 */
    .status-0 {
        /* 審核中 */
        background-color: var(--camp-light);
        color: var(--camp-secondary);
        border: 1px solid var(--camp-secondary);
    }

    .status-1 {
        /* 已通過 */
        background-color: var(--camp-light);
        color: var(--camp-primary);
        border: 1px solid var(--camp-primary);
    }

    .status-2 {
        /* 已退回 */
        background-color: #FFF5F6;
        color: var(--camp-danger);
        border: 1px solid var(--camp-danger);
    }

    /* 按鈕基本樣式 */
    .btn {
        transition: all 0.3s ease;
        background-color: var(--camp-primary);
        color: white;
        border: none;
        /* padding: 0.5rem 1.5rem; */
    }

    .btn:focus {
        background-color: var(--camp-primary);
    }

    /* 主要按鈕 hover 效果 */
    .btn:hover {
        background-color: var(--camp-primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    /* 次要按鈕（上一步）樣式 */
    .btn.prev-step {
        background-color: transparent;
        border: 2px solid var(--camp-primary);
        color: var(--camp-primary);
    }

    /* 次要按鈕 hover 效果 */
    .btn.prev-step:hover {
        background-color: var(--camp-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    /* 按鈕點擊效果 */
    .btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(76, 107, 116, 0.15);
    }

    /* 新增營位類型按鈕 */
    #addSpotType {
        background-color: var(--camp-light);
        color: var(--camp-primary);
        border: 2px solid var(--camp-primary);
    }

    #addSpotType:hover {
        background-color: var(--camp-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    .btn-danger {
        padding: 0.2rem 0.5rem;
    }

    .btn-primary:hover {
        color: white;
    }
</style>

<div class="page-container p-0">
    <div class="card shadow-lg border-0">
        <div class="card-header">
            <h3 class="mb-0">營地申請</h3>
        </div>
        <div class="card-body p-4">
            <!-- 步驟指示器 -->
            <div class="steps mb-4">
                <div class="step active" data-step="1">
                    <div class="step-title">基本資訊</div>
                    <small>營地基本資料填寫</small>
                </div>
                <div class="step" data-step="2">
                    <div class="step-title">詳細說明</div>
                    <small>規則與注意事項</small>
                </div>
                <div class="step" data-step="3">
                    <div class="step-title">營位設定</div>
                    <small>營位類型與價格</small>
                </div>
            </div>

            <form id="campApplicationForm" novalidate>
                <!-- 第一步：基本資訊 -->
                <div class="form-section active" data-step="1">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label required">營地名稱</label>
                            <input type="text" class="form-control" name="name" required
                                maxlength="100" placeholder="請輸入營地名稱">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">營主名稱</label>
                            <input type="text" class="form-control" name="owner_name" required
                                maxlength="100" placeholder="請輸入營主名稱">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">營地地址</label>
                            <input type="text" class="form-control" name="address" required
                                placeholder="請輸入完整地址">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">營地描述</label>
                            <textarea class="form-control" name="description" rows="4" required maxlength="500"
                                placeholder="請詳細描述您的營地特色"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">營地主要圖片</label>
                            <div class="upload-container">
                                <input type="file" class="form-control d-none" name="camp_main_image"
                                    accept="image/jpeg,png,gif" required id="campMainImage">
                                <div class="upload-box" data-target="campMainImage">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="upload-text">
                                        <span>點擊或拖曳圖片至此</span>
                                        <small class="d-block text-muted">支援 JPG、PNG、GIF 格式，檔案大小不超過 5MB</small>
                                    </div>
                                </div>
                                <div class="image-preview-container d-none">
                                    <img src="" alt="預覽圖" class="image-preview">
                                    <button type="button" class="btn-remove-image">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-text">請上傳一張能代表營地特色的主要圖片</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn prev-step" style="visibility: hidden">上一步</button>
                        <button type="button" class="btn next-step">下一步</button>
                    </div>
                </div>

                <!-- 第二步則與注意事項 -->
                <div class="form-section" data-step="2" style="display: none;">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">營地規則</label>
                            <textarea class="form-control" name="rules" rows="4"
                                placeholder="請輸入營地使用規則，如：禁止吸菸、寵物規定等"></textarea>
                            <div class="char-count mt-1 text-end"><small>0/500</small></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">注意事項</label>
                            <textarea class="form-control" name="notice" rows="4"
                                placeholder="請輸入住宿意事項，如：攜帶物品建議、天候提醒等"></textarea>
                            <div class="char-count mt-1 text-end"><small>0/500</small></div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn prev-step">上一步</button>
                        <button type="button" class="btn next-step">下一步</button>
                    </div>
                </div>

                <!-- 第三步：營位設定 -->
                <div class="form-section" data-step="3" style="display: none;">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">營位類型設定</h5>
                                <button type="button" class="btn" id="addSpotType">
                                    <i class="bi bi-plus-lg"></i>
                                    新增營位類型
                                </button>
                            </div>
                            <div id="spotTypesContainer">
                                <!-- 營位類型表單模 -->
                                <div class="spot-type-form border rounded p-3 mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label required">營位名稱</label>
                                            <input type="text" class="form-control" name="spot_types[0][name]" required
                                                maxlength="100" placeholder="例：A區雙人營位">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">容納人數</label>
                                            <input type="number" class="form-control" name="spot_types[0][capacity]" required
                                                min="1" placeholder="請輸入人數">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">價格</label>
                                            <input type="number" class="form-control" name="spot_types[0][price]" required
                                                min="0" step="0.01" placeholder="請輸入價格">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">營位描述</label>
                                            <textarea class="form-control" name="spot_types[0][description]" rows="2"
                                                placeholder="請描述營位特色與設備"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label required">營位圖片</label>
                                            <div class="upload-container">
                                                <input type="file" class="form-control d-none" name="spot_images_0"
                                                    accept="image/jpeg,image/png,image/gif" required id="spotImage_0">
                                                <div class="upload-box cursor-pointer" data-target="spotImage_0">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <div class="upload-text">
                                                        <span>點擊或拖曳圖片至此</span>
                                                        <small class="d-block text-muted">支援 JPG、PNG、GIF 格式，檔案大小不超過 5MB</small>
                                                    </div>
                                                </div>
                                                <div class="image-preview-container d-none">
                                                    <img src="" alt="預覽圖" class="image-preview">
                                                    <button type="button" class="btn-remove-image">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn prev-step">上一步</button>
                        <button type="submit" class="btn btn-primary">送出申請</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    class FormValidator {
        constructor(form) {
            this.form = form;
            this.errors = new Map();
        }

        validateImage(file, maxSize = 5) {
            if (!file) {
                throw new Error('請選擇圖片');
            }

            // 驗證圖片格式
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                throw new Error('請上傳 JPG、PNG 或 GIF 格式的圖片');
            }

            // 驗證圖片大小
            if (file.size > maxSize * 1024 * 1024) {
                throw new Error(`圖片大小不能超過 ${maxSize}MB`);
            }

            return true;
        }

        validateRequired(value, fieldName) {
            if (!value || value.trim() === '') {
                throw new Error(`${fieldName}不能為空`);
            }
            return true;
        }

        validateNumber(value, fieldName, min = 0) {
            const num = Number(value);
            if (isNaN(num) || num < min) {
                throw new Error(`${fieldName}必須是大於${min}的數字`);
            }
            return true;
        }

        showError(element, message) {
            // 移除舊的錯提示
            const existingFeedback = element.parentNode.querySelector('.invalid-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }

            // 添加新的錯誤提示
            const feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'invalid-feedback';
            feedbackDiv.textContent = message;
            element.classList.add('is-invalid');
            element.parentNode.appendChild(feedbackDiv);
        }

        clearError(element) {
            element.classList.remove('is-invalid');
            const feedbackDiv = element.parentNode.querySelector('.invalid-feedback');
            if (feedbackDiv) {
                feedbackDiv.remove();
            }
        }
    }

    const FormHandler = {
        currentStep: 1,

        init() {
            this.form = document.getElementById('campApplicationForm');
            this.steps = document.querySelectorAll('.step');
            this.sections = document.querySelectorAll('.form-section');
            this.nextButtons = document.querySelectorAll('.next-step');
            this.prevButtons = document.querySelectorAll('.prev-step');
            this.submitButton = this.form.querySelector('button[type="submit"]');

            if (!this.form) return;

            this.setupEvents();
            this.setupValidation();
        },

        setupEvents() {
            // 表單提交事件
            this.form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (this.validateCurrentStep()) {
                    await this.submitForm();
                }
            });

            // 提交按鈕點擊事件
            this.submitButton.addEventListener('click', async (e) => {
                e.preventDefault();
                if (this.validateCurrentStep()) {
                    await this.submitForm();
                }
            });

            // 步驟切換按鈕
            this.nextButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (this.validateCurrentStep()) {
                        this.nextStep();
                    }
                });
            });

            this.prevButtons.forEach(button => {
                button.addEventListener('click', () => this.prevStep());
            });
        },

        nextStep() {
            if (this.currentStep >= 3) return;

            // 淡出前步驟
            this.sections[this.currentStep - 1].style.opacity = '0';

            setTimeout(() => {
                // 隱藏當前
                this.sections[this.currentStep - 1].style.display = 'none';
                this.steps[this.currentStep - 1].classList.remove('active');
                this.steps[this.currentStep - 1].classList.add('completed');

                // 顯示下一步
                this.currentStep++;
                this.sections[this.currentStep - 1].style.display = 'block';
                this.sections[this.currentStep - 1].style.opacity = '0';

                // 強制重繪
                void this.sections[this.currentStep - 1].offsetWidth;

                // 淡入新驟
                this.sections[this.currentStep - 1].style.opacity = '1';
                this.steps[this.currentStep - 1].classList.add('active');
            }, 300);
        },

        prevStep() {
            if (this.currentStep <= 1) return;

            // 淡出當前步驟
            this.sections[this.currentStep - 1].style.opacity = '0';

            setTimeout(() => {
                // 前步驟
                this.sections[this.currentStep - 1].style.display = 'none';
                this.steps[this.currentStep - 1].classList.remove('active');

                // 顯示上一步
                this.currentStep--;
                this.sections[this.currentStep - 1].style.display = 'block';
                this.sections[this.currentStep - 1].style.opacity = '0';

                // 強制重繪
                void this.sections[this.currentStep - 1].offsetWidth;

                // 淡入新步驟
                this.sections[this.currentStep - 1].style.opacity = '1';
                this.steps[this.currentStep - 1].classList.add('active');
                this.steps[this.currentStep].classList.remove('completed');
            }, 300);
        },

        setupValidation() {
            // 所有輸入欄位的本驗證
            const inputs = this.form.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    this.validateInput(input);
                });

                input.addEventListener('blur', () => {
                    this.validateInput(input);
                });
            });

            // 特別處理第二步的驗證
            const step2Fields = document.querySelectorAll('.form-section[data-step="2"] textarea');
            step2Fields.forEach(field => {
                field.addEventListener('input', () => this.validateStep2Fields());
                field.addEventListener('blur', () => this.validateStep2Fields());
            });
        },

        validateInput(input) {
            const value = input.value.trim();
            let isValid = true;
            let errorMessage = '';

            // 必填欄位驗證
            if (input.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = '此欄位為必填';
            }
            // 大長度驗證
            else if (input.hasAttribute('maxlength')) {
                const maxLength = parseInt(input.getAttribute('maxlength'));
                if (value.length > maxLength) {
                    isValid = false;
                    errorMessage = `不可超過 ${maxLength} 個字`;
                }
            }
            // 數字欄位驗證
            else if (input.type === 'number') {
                const num = parseFloat(value);
                if (isNaN(num) || num <= 0) {
                    isValid = false;
                    errorMessage = '請輸入大於0的數字';
                }
            }

            this.showValidationResult(input, isValid, errorMessage);
            return isValid;
        },

        showValidationResult(input, isValid, errorMessage = '') {
            input.classList.remove('is-valid', 'is-invalid');
            const existingFeedback = input.nextElementSibling;
            if (existingFeedback?.classList.contains('invalid-feedback')) {
                existingFeedback.remove();
            }

            if (!isValid) {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = errorMessage;
                input.parentNode.insertBefore(errorDiv, input.nextSibling);
            } else if (input.value.trim() !== '') {
                input.classList.add('is-valid');
            }
        },

        validateCurrentStep() {
            console.log('驗證當前步驟');
            const currentSection = document.querySelector(`.form-section[data-step="${this.currentStep}"]`);
            const requiredFields = currentSection.querySelectorAll('[required]');

            let isValid = true;
            requiredFields.forEach(field => {
                console.log(`檢查欄位：${field.name}`);
                if (!field.value) {
                    console.log(`欄位 ${field.name} 為空`);
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            return isValid;
        },

        validateStep2Fields() {
            const currentSection = document.querySelector('.form-section[data-step="2"]');
            const rulesTextarea = currentSection.querySelector('textarea[name="rules"]');
            const noticeTextarea = currentSection.querySelector('textarea[name="notice"]');
            const nextButton = currentSection.querySelector('.next-step');
            let isValid = true;

            // 驗證營地規則
            if (rulesTextarea) {
                const rulesValue = rulesTextarea.value.trim();
                const rulesLength = rulesValue.length;
                const rulesCounter = rulesTextarea.nextElementSibling.querySelector('small');

                rulesCounter.textContent = `${rulesLength}/500`;

                if (rulesLength > 500) {
                    this.showValidationResult(rulesTextarea, false, '營地規則不可超過500字');
                    isValid = false;
                } else {
                    this.showValidationResult(rulesTextarea, true);
                }
            }

            // 驗證注意事項
            if (noticeTextarea) {
                const noticeValue = noticeTextarea.value.trim();
                const noticeLength = noticeValue.length;
                const noticeCounter = noticeTextarea.nextElementSibling.querySelector('small');

                noticeCounter.textContent = `${noticeLength}/500`;

                if (noticeLength > 500) {
                    this.showValidationResult(noticeTextarea, false, '注意事項不可超過500字');
                    isValid = false;
                } else {
                    this.showValidationResult(noticeTextarea, true);
                }
            }

            // 更新下一步按鈕狀態
            if (nextButton) {
                nextButton.disabled = !isValid;
            }

            return isValid;
        },

        async submitForm() {
            try {
                const formData = new FormData(this.form);

                // 驗證所有必填欄位
                const requiredInputs = this.form.querySelectorAll('[required]');
                let isValid = true;

                requiredInputs.forEach(input => {
                    if (!input.value) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    }
                });

                if (!isValid) {
                    throw new Error('請填寫所有必填欄位');
                }

                // 驗證營位資料
                const spotForms = document.querySelectorAll('.spot-type-form');
                if (spotForms.length === 0) {
                    throw new Error('請少新增一個營位型');
                }

                // 驗並添加營位資料
                spotForms.forEach((form, index) => {
                    const imageInput = form.querySelector(`input[name="spot_images_${index}"]`);
                    if (!imageInput || !imageInput.files[0]) {
                        throw new Error(`請上傳第 ${index + 1} 個營位的圖片`);
                    }

                    // 添加營位資料
                    const nameInput = form.querySelector('input[name^="spot_types"][name$="[name]"]');
                    const capacityInput = form.querySelector('input[name^="spot_types"][name$="[capacity]"]');
                    const priceInput = form.querySelector('input[name^="spot_types"][name$="[price]"]');
                    const descriptionInput = form.querySelector('textarea[name^="spot_types"][name$="[description]"]');

                    if (!nameInput || !capacityInput || !priceInput) {
                        throw new Error(`第 ${index + 1} 個營位的資料不完整`);
                    }

                    formData.append(`spot_types[${index}][name]`, nameInput.value);
                    formData.append(`spot_types[${index}][capacity]`, capacityInput.value);
                    formData.append(`spot_types[${index}][price]`, priceInput.value);
                    formData.append(`spot_types[${index}][description]`, descriptionInput ? descriptionInput.value : '');

                    // 添加營位圖片
                    formData.append(`spot_images_${index}`, imageInput.files[0]);
                });

                // 確保基本資料都添加
                if (!formData.get('name') || !formData.get('owner_name') || !formData.get('address')) {
                    throw new Error('請填寫營地基本資料');
                }

                // 禁用提交按鈕
                this.submitButton.disabled = true;

                // 發送請求前先打印 FormData 內容以檢查
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                const response = await axios({
                    method: 'post',
                    url: '/CampExplorer/owner/api/camp/submit_application.php',
                    data: formData,
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    // 添加錯誤處理配置
                    validateStatus: function(status) {
                        return status >= 200 && status < 500;
                    }
                });

                if (response.data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '申請成功',
                        timer: 1500,
                        text: '您的營地申請已提交成功！',
                        iconColor: '#4C6B74', // 使用莫蘭迪藍綠色
                        confirmButtonColor: '#4C6B74' // 確認按鈕也使用相同顏色
                    });
                    window.location.href = '/CampExplorer/owner/index.php?page=camp_status';
                } else {
                    throw new Error(response.data.message || '申請失敗');
                }

            } catch (error) {
                console.error('提交錯誤完整信息：', {
                    name: error.name,
                    message: error.message,
                    response: error.response,
                    request: error.request
                });

                let errorMessage = '申請失敗';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }

                await Swal.fire({
                    icon: 'error',
                    title: '申請失敗',
                    text: errorMessage
                });
            } finally {
                this.submitButton.disabled = false;
            }
        },

        // 添加營位類型按鈕件處理
        setupSpotTypeEvents() {
            const addSpotTypeBtn = document.getElementById('addSpotType');
            const spotTypesContainer = document.getElementById('spotTypesContainer');

            if (addSpotTypeBtn && spotTypesContainer) {
                let spotIndex = document.querySelectorAll('.spot-type-form').length;

                addSpotTypeBtn.addEventListener('click', () => {
                    spotIndex = document.querySelectorAll('.spot-type-form').length;
                    const newSpotHtml = `
                        <div class="spot-type-form border rounded p-3 mb-3">
                            <div class="row g-3">
                                <!-- 基本欄位 -->
                                <div class="col-md-6">
                                    <label class="form-label required">營位名稱</label>
                                    <input type="text" class="form-control" name="spot_types[${spotIndex}][name]" required
                                        maxlength="100" placeholder="例：A區雙人營位">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required">容納人數</label>
                                    <input type="number" class="form-control" name="spot_types[${spotIndex}][capacity]" required
                                        min="1" placeholder="請輸入人數">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required">價格</label>
                                    <input type="number" class="form-control" name="spot_types[${spotIndex}][price]" required
                                        min="0" step="0.01" placeholder="請輸入價格">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">營位描述</label>
                                    <textarea class="form-control" name="spot_types[${spotIndex}][description]" rows="2"
                                        placeholder="請描述營位特色與設備"></textarea>
                                </div>
                                <!-- 圖片上傳區域 -->
                                <div class="col-12">
                                    <label class="form-label required">營位圖片</label>
                                    <div class="upload-container">
                                        <input type="file" class="form-control d-none" name="spot_images_${spotIndex}"
                                            accept="image/jpeg,image/png,image/gif" required id="spotImage_${spotIndex}">
                                        <div class="upload-box cursor-pointer" data-target="spotImage_${spotIndex}">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <div class="upload-text">
                                                <span>點擊或拖曳圖片至此</span>
                                                <small class="d-block text-muted">支援 JPG、PNG、GIF 格式，檔案大小不超過 5MB</small>
                                            </div>
                                        </div>
                                        <div class="image-preview-container d-none">
                                            <img src="" alt="預覽圖" class="image-preview">
                                            <button type="button" class="btn-remove-image">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    spotTypesContainer.insertAdjacentHTML('beforeend', newSpotHtml);

                    // 為新增的表單設置圖片上傳能
                    const newForm = spotTypesContainer.lastElementChild;
                    this.setupImageUpload(newForm);

                    // 添加刪除按鈕
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'btn btn-danger delete-spot-btn position-absolute top-0 end-0 m-2';
                    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                    deleteBtn.onclick = () => newForm.remove();

                    newForm.style.position = 'relative';
                    newForm.appendChild(deleteBtn);
                });
            }
        },

        // 添加刪除按鈕
        addDeleteButton(spotForm) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-danger delete-spot-btn position-absolute top-0 end-0 m-2';
            deleteBtn.innerHTML = '<i class="fas fa-times"></i>';

            deleteBtn.addEventListener('click', () => {
                // 檢查是否為最後一個營位表單
                const spotForms = document.querySelectorAll('.spot-type-form');
                if (spotForms.length > 1) {
                    spotForm.remove();
                    this.updateSpotIndexes();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: '無法刪除',
                        text: '至少需要保留一個營位類型'
                    });
                }
            });

            // 設置相對定位以便放置刪按鈕
            spotForm.style.position = 'relative';
            spotForm.appendChild(deleteBtn);
        },

        // 更新所有營位表單的索引
        updateSpotIndexes() {
            const spotForms = document.querySelectorAll('.spot-type-form');
            spotForms.forEach((form, index) => {
                form.querySelectorAll('input, textarea').forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                    }
                });
            });
        },

        validateStep(step) {
            let isValid = true;
            const currentSection = document.querySelector(`.form-section[data-step="${step}"]`);

            if (step === 3) {
                const spotTypes = currentSection.querySelectorAll('.spot-type-form');

                if (spotTypes.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: '驗證失敗',
                        text: '請至少新增一個營位類型'
                    });
                    return false;
                }

                spotTypes.forEach((spot, index) => {
                    const requiredInputs = spot.querySelectorAll('input[required], textarea[required]');
                    requiredInputs.forEach(input => {
                        if (input.type === 'file') {
                            const hasFile = input.files && input.files.length > 0;
                            if (!hasFile) {
                                this.showValidationResult(input, false, `請上傳第 ${index + 1} 個營位的圖片`);
                                isValid = false;
                            }
                        } else if (!input.value.trim()) {
                            this.showValidationResult(input, false, '此欄位為必填');
                            isValid = false;
                        }
                    });
                });
            }

            // 更新提交按鈕狀態
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = !isValid;
            }

            return isValid;
        },

        showValidationResult(element, isValid, message = '') {
            element.classList.remove('is-valid', 'is-invalid');
            element.classList.add(isValid ? 'is-valid' : 'is-invalid');

            // 移除現有的回饋元素
            const feedback = element.nextElementSibling;
            if (feedback && (feedback.classList.contains('valid-feedback') ||
                    feedback.classList.contains('invalid-feedback'))) {
                feedback.remove();
            }

            // 添加新的回饋元素
            if (!isValid && message) {
                const feedbackDiv = document.createElement('div');
                feedbackDiv.className = 'invalid-feedback';
                feedbackDiv.textContent = message;
                element.parentNode.insertBefore(feedbackDiv, element.nextSibling);
            }
        },

        handleImagePreview(file, uploadBox, previewContainer) {
            if (!file || !file.type.startsWith('image/')) {
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '請上傳圖片檔案'
                });
                return;
            }

            // 驗證圖片大小
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '圖片大小不能超過 5MB'
                });
                return;
            }

            // 驗證圖片格式
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '請上傳 JPG、PNG 或 GIF 格式的圖片'
                });
                return;
            }

            // 添加載入中效果
            const loadingSpinner = document.createElement('div');
            loadingSpinner.className = 'spinner-border text-primary';
            uploadBox.appendChild(loadingSpinner);

            const reader = new FileReader();
            reader.onload = e => {
                const previewImg = previewContainer.querySelector('img');
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                    uploadBox.classList.add('d-none');
                }
                uploadBox.removeChild(loadingSpinner);
            };

            reader.onerror = () => {
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '圖片讀取失敗，請重試'
                });
                uploadBox.removeChild(loadingSpinner);
            };

            reader.readAsDataURL(file);
        },

        setupImageUpload(container = document) {
            const uploadBoxes = container.querySelectorAll('.upload-box');

            uploadBoxes.forEach(box => {
                const targetInputId = box.dataset.target;
                const targetInput = document.getElementById(targetInputId);
                const previewContainer = box.closest('.upload-container').querySelector('.image-preview-container');

                if (!targetInput || !previewContainer) return;

                // 點擊上傳框觸發文件選擇
                box.addEventListener('click', () => targetInput.click());

                // 拖放處理
                box.addEventListener('dragover', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    box.classList.add('dragover');
                });

                box.addEventListener('dragleave', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    box.classList.remove('dragover');
                });

                box.addEventListener('drop', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    box.classList.remove('dragover');

                    if (e.dataTransfer.files.length) {
                        targetInput.files = e.dataTransfer.files;
                        this.handleImagePreview(targetInput.files[0], box, previewContainer);
                    }
                });

                // 文件選擇處理
                targetInput.addEventListener('change', (e) => {
                    e.stopPropagation();
                    if (targetInput.files.length) {
                        this.handleImagePreview(targetInput.files[0], box, previewContainer);
                    }
                });

                // 刪除圖片按鈕處理
                const removeButton = previewContainer.querySelector('.btn-remove-image');
                if (removeButton) {
                    removeButton.addEventListener('click', () => {
                        targetInput.value = '';
                        previewContainer.classList.add('d-none');
                        box.classList.remove('d-none');
                    });
                }
            });
        },

        setupCharacterCounter() {
            const textareas = document.querySelectorAll('textarea[maxlength]');
            textareas.forEach(textarea => {
                const container = document.createElement('div');
                container.className = 'char-counter text-end';
                const counter = document.createElement('small');
                counter.className = 'text-muted';
                counter.textContent = `0/${textarea.maxLength}`;
                container.appendChild(counter);
                textarea.parentNode.insertBefore(container, textarea.nextSibling);

                textarea.addEventListener('input', () => {
                    const length = textarea.value.length;
                    counter.textContent = `${length}/${textarea.maxLength}`;

                    if (length > textarea.maxLength) {
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                    }
                });
            });
        }
    };

    // 初始化時添加營位類型按鈕事件
    document.addEventListener('DOMContentLoaded', () => {
        FormHandler.init();
        FormHandler.setupSpotTypeEvents();
        FormHandler.setupImageUpload();
        FormHandler.setupCharacterCounter();
    });

    // 過濾營地列表
    function filterCamps(status) {
        const rows = document.querySelectorAll('.spot-list tbody tr');
        rows.forEach(row => {
            const statusBadge = row.querySelector('.status-badge');
            if (!statusBadge) return;

            const statusText = statusBadge.textContent.trim();

            switch (status) {
                case 'pending':
                    row.style.display = statusText === '審核中' ? '' : 'none';
                    break;
                case 'approved':
                    row.style.display = statusText === '已通過' ? '' : 'none';
                    break;
                case 'rejected':
                    row.style.display = statusText === '已退回' ? '' : 'none';
                    break;
                default:
                    row.style.display = '';
            }
        });
    }

    // 初始化 Modal 事件
    document.addEventListener('DOMContentLoaded', function() {
        const campDetailModal = document.getElementById('campDetailModal');
        if (campDetailModal) {
            campDetailModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                // 更新 Modal 內容
                document.getElementById('modalCampName').textContent = button.dataset.campName;
                document.getElementById('modalCampAddress').textContent = button.dataset.campAddress;
                document.getElementById('modalCampStatus').textContent = button.dataset.campStatus;
                document.getElementById('modalCampCreated').textContent = button.dataset.campCreated;
                document.getElementById('modalCampUpdated').textContent = button.dataset.campUpdated;
                document.getElementById('modalCampDescription').textContent = button.dataset.campDescription;
                document.getElementById('modalCampRules').textContent = button.dataset.campRules;
                document.getElementById('modalCampNotice').textContent = button.dataset.campNotice;

                const adminComment = button.dataset.campComment;
                const commentElement = document.getElementById('modalAdminComment');
                if (commentElement) {
                    commentElement.textContent = adminComment || '無審核意見';
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('campApplicationForm');

        // 步驟切換處理
        const steps = document.querySelectorAll('.step');
        const sections = document.querySelectorAll('.form-section');
        const nextButtons = document.querySelectorAll('.next-step');
        const prevButtons = document.querySelectorAll('.prev-step');
        let currentStep = 1;

        // 下一步按鈕處理
        nextButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (validateCurrentStep(currentStep)) {
                    currentStep++;
                    updateSteps();
                    updateSections();
                }
            });
        });

        // 上一步按鈕處理
        prevButtons.forEach(button => {
            button.addEventListener('click', () => {
                currentStep--;
                updateSteps();
                updateSections();
            });
        });

        // 驗證當前步驟
        function validateCurrentStep(step) {
            const currentSection = document.querySelector(`.form-section[data-step="${step}"]`);
            const requiredFields = currentSection.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (field.type === 'file') {
                    if (!field.files[0]) {
                        showError(field, '請選擇圖片');
                        isValid = false;
                    }
                } else if (!field.value.trim()) {
                    showError(field, '此欄位為必填');
                    isValid = false;
                }
            });

            return isValid;
        }

        // 顯示錯誤訊息
        function showError(element, message) {
            const feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'invalid-feedback';
            feedbackDiv.style.display = 'block';
            feedbackDiv.textContent = message;

            // 移除舊的錯誤訊息
            const existingFeedback = element.parentNode.querySelector('.invalid-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }

            element.classList.add('is-invalid');
            element.parentNode.appendChild(feedbackDiv);
        }

        // 更新步驟指示器
        function updateSteps() {
            steps.forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.remove('active', 'completed');
                if (stepNum === currentStep) {
                    step.classList.add('active');
                } else if (stepNum < currentStep) {
                    step.classList.add('completed');
                }
            });
        }

        // 更新表單區塊顯示
        function updateSections() {
            sections.forEach(section => {
                section.style.display = parseInt(section.dataset.step) === currentStep ? 'block' : 'none';
            });
        }
    });
</script>