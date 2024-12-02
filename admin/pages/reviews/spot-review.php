<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<style>
    :root {
        --morandi-primary: #7A90A8;
        /* 主色調：莫蘭迪藍 */
        --morandi-secondary: #A68E9B;
        /* 次要色：莫蘭迪紫灰 */
        --morandi-success: #8FA977;
        /* 成功狀態：鼠尾草綠 */
        --morandi-warning: #C4A687;
        /* 警告狀態：莫蘭迪沙 */
        --morandi-danger: #C69B97;
        /* 危險狀態：莫蘭迪玫瑰 */
        --morandi-info: #89B0A3;
        /* 信息狀態：莫蘭迪薄荷 */
        --morandi-light: #F3F1ED;
        /* 背景色：米白色 */
        --morandi-border: #D8D0C5;
        /* 邊框色：淺棕色 */
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
        border-color: var(--morandi-border);
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

    /* 卡片樣式 */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, var(--morandi-primary) 0%, var(--morandi-secondary) 100%);
        color: white;
        border: none;
        padding: 1.5rem;
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

    #rejectReason {
        border-color: var(--morandi-border);
        resize: vertical;
        min-height: 100px;
    }

    /* 響應式調整 */
    @media (max-width: 768px) {
        .table td {
            white-space: nowrap;
        }

        .badge {
            font-size: 0.8rem;
        }

        .card-body {
            padding: 1rem;
        }
    }

    /* 表格互動效果 */
    .table tbody tr {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .table td {
        position: relative;
    }

    .table td span {  /* 針對文字內容 */
        position: relative;
        display: inline-block;
    }

    .table td span::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--morandi-primary);
        transition: width 0.3s ease;
    }

    /* .table td span:hover::after {
        width: 100%;
    } */

    /* 狀態標籤動畫 */
    .badge {
        transition: all 0.3s ease;
    }

    /* .badge:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    } */

    /* 按鈕優化 */
    .btn {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: -100%;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transition: all 0.6s ease;
    }

    .btn:hover::after {
        left: 100%;
    }

    /* Modal 優化 */
    .modal-content {
        transform: scale(0.95);
        opacity: 0;
        transition: all 0.3s ease;
    }

    .modal.show .modal-content {
        transform: scale(1);
        opacity: 1;
    }

    /* 表單元素優化 */
    .form-check-input {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .form-check-label {
        cursor: pointer;
        transition: all 0.3s ease;
        padding-left: 0.5rem;
    }

    .form-check-input:hover {
        transform: scale(1.1);
    }

    /* 文字區域優化 */
    textarea.form-control {
        transition: all 0.3s ease;
        border: 1px solid var(--morandi-border);
    }

    textarea.form-control:focus {
        border-color: var(--morandi-primary);
        box-shadow: 0 0 0 0.2rem rgba(122, 144, 168, 0.25);
    }

    /* 頁面標題樣式 */
    .page-title {

        
        position: relative;
        padding-left: 1rem;
        display: flex;
        align-items: center;
        margin: 0 75px;
        margin-bottom: 1.5rem;
        color: #767676;
    }

    /* .page-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 70%;
        background: linear-gradient(to bottom, var(--morandi-primary), var(--morandi-secondary));
        border-radius: 2px;
    } */

    /* 卡片標題優化 */
    .card-header {
        background: linear-gradient(135deg, var(--morandi-primary) 0%, var(--morandi-secondary) 100%);
        padding: 1.25rem 1.5rem;
    }

    .card-header h6 {
        color: white;
        font-size: 1.1rem;
        font-weight: 500;
        margin: 0;
        letter-spacing: 0.5px;
    }


    /* 狀態標籤優化 */
    .badge {
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    /* 按鈕組優化 */
    .modal-footer {
        border-top: 1px solid var(--morandi-border);
        padding: 1.5rem 2rem;
        gap: 1rem;
    }

    .btn-group-sm > .btn {
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
    }

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
    font-size: 14px;
}
.badge.bg-info{
    background-color: transparent !important;
    border: 1px solid #ffc107;
    color: #efb300 !important;
    padding: 7px 23px;
    font-size: 14px;
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
.badge.bg-warning{
    background-color: transparent !important;
    border: 1px solid #0dcaf0;
    color: #0dcaf0 !important;
    padding: 7px 23px;
    border-radius: .25rem;
}
.badge.bg-success{
    background-color: transparent !important;
    border: 1px solid #0080005c;
    color: #008000 !important;
    padding: 7px 23px;
    border-radius: .25rem;
}
.btn.btn-primary{
    color: #8b6a09;
    background-color: #ffc1076e;
    border: 0;
}
tbody tr:hover{
            background: rgb(155 254 144 / 10%);
            transition: all 0.2s ease-in-out;
            box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 10%);
            --bs-table-accent-bg: none!important;
        }
        .modal-header{
    border-radius: 10px 10px 0 0;
    background-image: linear-gradient(to top, #0ba360 0%, #3cba92 100%)!important;
}
.modal-footer .btn.btn-primary{
    background-color: #ffc1076e;
}
.left-thead{
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}
.right-thead{
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
}
.table-container{
    border-radius: 12px;
}
</style>
<div class="container-fluid" style="margin-top: 40px;">
    <h1 class="page-title">待審核營位管理</h1>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-container">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center left-thead">申請編號</th>
                            <th class="text-center">營地名稱</th>
                            <th class="text-center">營主名稱</th>
                            <th class="text-center">營位名稱</th>
                            <th class="text-center">容納人數</th>
                            <th class="text-center">價格</th>
                            <th class="text-center">申請時間</th>
                            <th class="text-center">狀態</th>
                            <th class="text-center">營地狀態</th>
                            <th class="text-center right-thead">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            global $db;
                            $sql = "SELECT 
                                    csa.spot_id,
                                    csa.application_id,
                                    csa.name AS spot_name,
                                    csa.capacity,
                                    csa.price,
                                    csa.description,
                                    CASE 
                                        WHEN ca.status = 2 THEN 2  -- 如果營地未通過，營位狀態也是未通過
                                        WHEN ca.status = 0 THEN 0  -- 如果營地待審核，營位狀態也是待審核
                                        ELSE csa.status            -- 其他情況（營地通過）才使用營位本身的狀態
                                    END AS status,
                                    csa.created_at,
                                    ca.name AS camp_name,
                                    ca.owner_name,
                                    ca.status AS camp_status,
                                    ca.description AS camp_description
                                FROM camp_spot_applications csa
                                JOIN camp_applications ca 
                                    ON csa.application_id = ca.application_id
                                ORDER BY csa.created_at DESC";

                            $stmt = $db->prepare($sql);
                            $stmt->execute();
                            $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($spots)) {
                                echo '<tr><td colspan="9" class="text-center">目前沒有待審核的營位申請</td></tr>';
                            } else {
                                foreach ($spots as $spot) {
                                    $statusBadge = match ($spot['status']) {
                                        0 => '<span class="badge bg-warning">待審核</span>',
                                        1 => '<span class="badge bg-success">已通過</span>',
                                        2 => '<span class="badge bg-danger">已退回</span>',
                                        default => '<span class="badge bg-secondary">未知</span>'
                                    };
                                    $campStatusBadge = match ($spot['camp_status']) {
                                        0 => '<span class="badge bg-warning">營地待審核</span>',
                                        1 => '<span class="badge bg-success">營地已通過</span>',
                                        2 => '<span class="badge bg-danger">營地未通過</span>',
                                        default => '<span class="badge bg-secondary">未知</span>'
                                    };
                        ?>
                                    <tr>
                                        <td class="text-center"><span><?= htmlspecialchars($spot['application_id']) ?></span></td>
                                        <td class="text-center"><span><?= htmlspecialchars($spot['camp_name']) ?></span></td>
                                        <td class="text-center"><span><?= htmlspecialchars($spot['owner_name']) ?></span></td>
                                        <td class="text-center"><span><?= htmlspecialchars($spot['spot_name']) ?></span></td>
                                        <td class="text-center"><span><?= htmlspecialchars($spot['capacity']) ?></span></td>
                                        <td class="text-center"><span>NT$ <?= number_format($spot['price']) ?></span></td>
                                        <td class="text-center"><span><?= date('Y-m-d H:i', strtotime($spot['created_at'])) ?></span></td>
                                        <td class="text-center"><?= $statusBadge ?></td>
                                        <td class="text-center"><?= $campStatusBadge ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary"
                                                onclick="viewSpotDetails(<?= $spot['spot_id'] ?>)">
                                                編輯
                                            </button>
                                        </td>
                                    </tr>
                        <?php
                                }
                            }
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="9" class="text-danger">資料載入失敗：' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 修改 Modal 結構 -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">編輯審核狀態</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentSpotId">
                <div class="mb-3">
                    <label class="form-label">審核狀態</label>
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status0" value="0">
                            <label class="form-check-label" for="status0">待審核</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status1" value="1">
                            <label class="form-check-label" for="status1">通過</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status2" value="2">
                            <label class="form-check-label" for="status2">不通過</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="rejectReason" class="form-label">審核意見</label>
                    <textarea class="form-control" id="rejectReason" rows="3"></textarea>
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
    let currentSpotId;

    document.addEventListener('DOMContentLoaded', function() {
        statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    });

    function viewSpotDetails(spotId) {
        currentSpotId = spotId;
        // 獲取當前狀態並設置
        document.querySelector('input[name="status"][value="0"]').checked = true;
        // 清空審核意見
        document.getElementById('rejectReason').value = '';
        statusModal.show();
    }

    async function updateStatus() {
        const status = document.querySelector('input[name="status"]:checked').value;
        const rejectReason = document.getElementById('rejectReason').value;

        // 如果是退回狀態但沒有填寫原因
        if (status === '2' && !rejectReason.trim()) {
            await Swal.fire({
                title: '請填寫審核意見',
                text: '退回申請時需寫審核意見',
                icon: 'warning'
            });
            return;
        }

        try {
            const response = await fetch('/CampExplorer/admin/api/reviews/update_spot_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    spot_id: currentSpotId,
                    status: parseInt(status),
                    reject_reason: rejectReason
                })
            });

            const data = await response.json();

            if (data.success) {
                statusModal.hide();
                await Swal.fire({
                    title: '更新成功',
                    text: '營位申請狀態已更新',
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