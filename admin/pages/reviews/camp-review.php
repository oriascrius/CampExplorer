<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    global $db;
    $sql = "
        SELECT 
            ca.application_id,
            ca.owner_name,
            ca.name AS camp_name,
            ca.address,
            ca.created_at,
            ca.status,
            o.company_name,
            o.phone,
            COUNT(DISTINCT csa.spot_id) as spot_count,
            COUNT(DISTINCT ci.image_id) as image_count
        FROM camp_applications ca
        LEFT JOIN owners o ON ca.owner_id = o.id
        LEFT JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        LEFT JOIN camp_images ci ON ca.application_id = ci.application_id
        GROUP BY ca.application_id, ca.owner_name, ca.name, ca.address, ca.created_at, ca.status, o.company_name, o.phone
        ORDER BY ca.created_at DESC
    ";

    $stmt = $db->query($sql);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error_message'] = '資料載入失敗，請稍後再試';
    $applications = [];
}
?>
<style>

.card.shadow-sm{
    border-radius:30px;
    padding-top: 15px;
}
.table thead{
    color:#fff;
    
}
.badge.bg-primary{
    background-color: transparent !important;
    border: 1px solid #007bff;
    color: #007bff !important;
    padding: 7px 23px;
}
.badge.bg-info{
    background-color: transparent !important;
    border: 1px solid #ffc107;
    color: #efb300 !important;
    padding: 7px 23px;
}
.d-flex.justify-content-between{
    margin: 0 75px;
}
.card.shadow-sm{
    margin: 0 75px;
}
tr{
    border-bottom-width: 1px;
}
.table thead th{
    
    background-color: transparent!important;
    color: #fff!important;
    padding: .5rem!important;
}
.text-center .badge.bg-primary{
    background-color: transparent !important;
    border-radius: .25rem;
}
.text-center .badge.bg-info{
    background-color: transparent !important;
    border-radius: .25rem;
}
.text-center .badge.bg-warning{
    background-color: transparent !important;
    border: 1px solid #0dcaf0;
    color: #0dcaf0 !important;
    padding: 7px 23px;
    border-radius: .25rem;
}
.text-center .badge.bg-success{
    background-color: transparent !important;
    border: 1px solid #0080005c;
    color: #008000 !important;
    padding: 7px 23px;
    border-radius: .25rem;
}
.text-center .btn.btn-primary{
    color: #8b6a09;
    background-color: #ffc1076e;
    border: 0;
}
</style>
<!-- 只保留內容部分，移除所有 JavaScript -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
    <h1>待審核營地列表</h1>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="">
                    <tr>
                        <th scope="col" class="text-nowrap">申請編號</th>
                        <th scope="col" style="min-width: 200px;">營地資訊</th>
                        <th scope="col" style="min-width: 180px;">營主資訊</th>
                        <th scope="col" class="text-center">申請內容</th>
                        <th scope="col" class="text-nowrap">申請時間</th>
                        <th scope="col" class="text-center">狀態</th>
                        <th scope="col" class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                目前沒有營地申請
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="text-nowrap">
                               
                                        <?= htmlspecialchars($app['application_id']) ?>
                       
                                </td>
                                <td>
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($app['camp_name']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($app['address']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="mb-1"><?= htmlspecialchars($app['owner_name']) ?></div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-building"></i>
                                        <?= htmlspecialchars($app['company_name']) ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-telephone"></i>
                                        <?= htmlspecialchars($app['phone']) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary me-2">
                                        <i class="bi bi-house-door"></i>
                                        <?= $app['spot_count'] ?> 個營位
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="bi bi-images"></i>
                                        <?= $app['image_count'] ?> 張圖片
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <small>
                                        <i class="bi bi-clock"></i>
                                        <?= date('Y/m/d H:i', strtotime($app['created_at'])) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $statusClass = match($app['status']) {
                                        0 => 'bg-warning',
                                        1 => 'bg-success',
                                        2 => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $statusText = match($app['status']) {
                                        0 => '待審核',
                                        1 => '已通過',
                                        2 => '未通過',
                                        default => '未知'
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            onclick="showStatusModal(<?= $app['application_id'] ?>, <?= $app['status'] ?>)">
                                        <i class="bi bi-pencil-square"></i> 編輯
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 新增狀態編輯的 Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯審核狀態</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentApplicationId">
                <div class="mb-3">
                    <label class="form-label">審核狀態</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status0" value="0">
                            <label class="form-check-label" for="status0">
                                待審核
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status1" value="1">
                            <label class="form-check-label" for="status1">
                                通過
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status2" value="2">
                            <label class="form-check-label" for="status2">
                                不通過
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">確認更新</button>
            </div>
        </div>
    </div>
</div>

<script>
let statusModal;
let currentApplicationId;

document.addEventListener('DOMContentLoaded', function() {
    statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
});

function showStatusModal(applicationId, currentStatus) {
    currentApplicationId = applicationId;
    // 設定當前狀態
    document.querySelector(`input[name="status"][value="${currentStatus}"]`).checked = true;
    statusModal.show();
}

async function updateStatus() {
    const status = document.querySelector('input[name="status"]:checked').value;
    
    try {
        const response = await fetch('/CampExplorer/admin/api/reviews/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                application_id: currentApplicationId,
                status: parseInt(status)
            })
        });

        const data = await response.json();

        if (data.success) {
            statusModal.hide();
            await Swal.fire({
                title: '更新成功',
                text: '營地申請狀態已更新',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            location.reload();
        } else {
            throw new Error(data.message || '更新失敗');
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: '更新失敗',
            text: error.message || '請稍後再試',
            icon: 'error'
        });
    }
}
</script>

<style>
/* 使用莫蘭迪色系 */
:root {
    --morandi-primary: #7A90A8;    /* 主色調：莫蘭迪藍 */
    --morandi-secondary: #A68E9B;  /* 次要色：莫蘭迪紫灰 */
    --morandi-success: #8FA977;    /* 成功狀態：鼠尾草綠 */
    --morandi-warning: #C4A687;    /* 警告狀態：莫蘭迪沙 */
    --morandi-danger: #C69B97;     /* 危險狀態：莫蘭迪玫瑰 */
    --morandi-info: #89B0A3;       /* 信息狀態：莫蘭迪薄荷 */
    --morandi-light: #F3F1ED;      /* 背景色：米白色 */
    --morandi-border: #D8D0C5;     /* 邊框色：淺棕色 */
}

/* 表格樣式優化 */
.table {
    color: #495057;
}

.table thead th {
    background-color: var(--morandi-light);
    border-bottom: 2px solid var(--morandi-border);
    color: var(--morandi-primary);
    font-weight: 500;
    padding: 1rem;
}

.table td {
    padding: 1.2rem 1rem;
    vertical-align: middle;
}

/* 狀態標籤樣式 */
.badge {
    padding: 0.5em 1em;
    border-radius: 20px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.badge.bg-warning {
    background-color: var(--morandi-warning) !important;
    color: white;
}

.badge.bg-success {
    background-color: var(--morandi-success) !important;
    color: white;
}

.badge.bg-danger {
    background-color: var(--morandi-danger) !important;
    color: white;
}

.badge.bg-info {
    background-color: var(--morandi-info) !important;
    color: white;
}

.badge.bg-primary {
    background-color: var(--morandi-primary) !important;
    color: white;
}

/* 卡片樣式 */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

/* 按鈕樣式 */
.btn-primary {
    background-color: var(--morandi-primary);
    border-color: var(--morandi-primary);
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--morandi-secondary);
    border-color: var(--morandi-secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Modal 樣式 */
.modal-content {
    border: none;
    border-radius: 15px;
}

.modal-header {
    background: linear-gradient(135deg, var(--morandi-primary) 0%, var(--morandi-secondary) 100%);
    color: white;
    border: none;
    padding: 1.5rem;
}

.modal-body {
    padding: 2rem;
}

/* 表單元素樣式 */
.form-check-input:checked {
    background-color: var(--morandi-primary);
    border-color: var(--morandi-primary);
}

/* 響應式調整 */
@media (max-width: 768px) {
    .table td {
        white-space: nowrap;
    }
    
    .badge {
        font-size: 0.8rem;
    }
}
</style>