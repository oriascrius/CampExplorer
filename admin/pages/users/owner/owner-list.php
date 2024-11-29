<?php
require_once __DIR__ . '/../../../../camping_db.php';

// 初始化變數
$error_message = null;
$owners = [];

try {
    $allowed_fields = [
        'id', 'name', 'company_name', 'email', 
        'phone', 'address', 'status', 'created_at'
    ];

    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';
    $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $perPage = 7; // 每頁顯示的營主數量
    $offset = max(0, ($p - 1) * $perPage);

    // 防止 SQL 注入
    if (!in_array($sort_field, $allowed_fields)) {
        $sort_field = 'created_at';
    }
    if (!in_array($sort_order, ['ASC', 'DESC'])) {
        $sort_order = 'DESC';
    }

    // 修改 SQL 查詢，處理不同欄位的排序
    $orderBy = "{$sort_field} {$sort_order}";

    // 獲取營主列表，並進行分頁
    $sql = "SELECT * FROM owners WHERE 1=1";
    if (isset($_GET['hideStatusZero']) && $_GET['hideStatusZero'] == '1') {
        $sql .= " AND status != 0";
    }
    $sql .= " ORDER BY {$orderBy} LIMIT :perPage OFFSET :offset";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取營主總數
    $totalSql = "SELECT COUNT(*) FROM owners WHERE 1=1";
    if (isset($_GET['hideStatusZero']) && $_GET['hideStatusZero'] == '1') {
        $totalSql .= " AND status != 0";
    }
    $totalStmt = $db->query($totalSql);
    $totalOwners = $totalStmt->fetchColumn();
    $totalPages = ceil($totalOwners / $perPage);

    // 獲取停用會員數量
    $statusZeroCountSql = "SELECT COUNT(*) FROM owners WHERE status = 0";
    $statusZeroCountStmt = $db->query($statusZeroCountSql);
    $statusZeroCount = $statusZeroCountStmt->fetchColumn();

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

function getFieldLabel($field) {
    $labels = [
        'id' => '編號',
        'name' => '姓名',
        'company_name' => '公司名稱',
        'email' => '信箱',
        'phone' => '電話',
        'address' => '地址',
        'status' => '狀態',
        'created_at' => '註冊時間'
    ];
    return $labels[$field] ?? $field;
}
?>
<!-- 頁面主要內容 -->
<div class="container  pagedata">
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- 頁面標題和新增按鈕 -->
    <div class="d-flex justify-content-center align-items-center mb-4">
        <h1 class=" mt-5 fw-bold text-center">營主管理</h1>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <button type="button" class="btn btn-success mb-2 mb-md-0" data-action="add">
            <i class="bi bi-plus-lg"></i> 新增營主
        </button>
        
        <div class="d-flex flex-column align-items-start">
                     <!-- 勾選框 -->
<div class="form-check">
    <input class="form-check-input" type="checkbox" value="" id="hideStatusZero" <?= isset($_GET['hideStatusZero']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="hideStatusZero">
    隱藏 停用 <?= $statusZeroCount ?>名營主
    </label>
</div>
            <label for="fieldSelect" class="mb-2 fw-bold ">排序方式：</label>
            <div class="d-flex">
                <select id="fieldSelect" class="form-select me-2 mb-2 mb-md-0 ">
                    <option value="">--未選擇--</option>
                    <?php foreach ($allowed_fields as $field): ?>
                        <option value="<?= $field ?>"><?= getFieldLabel($field) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="orderSelect" class="form-select">
                    <option value="">--未選擇--</option>
                    <option value="asc">升序</option>
                    <option value="desc">降序</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 營主列表 -->
    <div class="card">
        <div class="" id="ownerTableContainer">
            <div class="table-responsive ">
                <table class="table table-striped table-bordered ">
                    <thead class="table-dark ">
                        <tr>
                            <?php foreach ($allowed_fields as $field): ?>
                                <th class="sortable" data-field="<?= $field ?>" data-order="<?= $sort_field === $field ? $sort_order : '' ?>">
                                    <?= getFieldLabel($field) ?>
                                    <i class="bi bi-arrow-<?= $sort_field === $field ? ($sort_order === 'ASC' ? 'up' : 'down') : 'down-up' ?> sort-icon"></i>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center edit">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($owners)): ?>
                        <tr><td colspan="9" class="text-center">目前沒有營主資料</td></tr>
                    <?php else: ?>
                        <?php foreach ($owners as $owner): ?>
                            <tr>
                            <td class="id"><?= htmlspecialchars($owner['id']) ?></td>
                <td class="name"><?= htmlspecialchars($owner['name']) ?></td>
                <td class="company_name"><?= htmlspecialchars($owner['company_name']) ?></td>
                <td class="email"><?= htmlspecialchars($owner['email']) ?></td>
                <td class="phone"><?= htmlspecialchars($owner['phone'] ?? '-') ?></td>
                <td class="address"><?= htmlspecialchars($owner['address'] ?? '-') ?></td>
                                <td class="status text-center">
                                    <span class="badge bg-<?= $owner['status'] ? 'success' : 'danger' ?>">
                                        <?= $owner['status'] ? '啟用' : '停用' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($owner['created_at']) ?></td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="<?= $owner['id'] ?>">
                                            編輯
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm <?= $owner['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                data-action="toggle-status"
                                                data-id="<?= $owner['id'] ?>"
                                                data-status="<?= $owner['status'] ?>">
                                            <?= $owner['status'] ? '停用' : '啟用' ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

   
   <!-- 分頁導航 -->
   <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <?php
            $queryString = $_SERVER['QUERY_STRING'];
            parse_str($queryString, $queryArray);
            unset($queryArray['p']);
            if (isset($_GET['hideStatusZero']) && $_GET['hideStatusZero'] == '1') {
                $queryArray['hideStatusZero'] = 1;
            }
            $queryString = http_build_query($queryArray);

            // 上一頁按鈕
            $prevPage = max(1, $p - 1);
            $endPage = min($totalPages, $p + 1);
            ?>
            <li class="page-item <?php if ($p == 1) echo "disabled"; ?>">
                <a class="page-link" href="?<?= $queryString ?>&p=<?= $prevPage ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php
            for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php if ($i == $p) echo "active"; ?>">
                    <a class="page-link" href="?<?= $queryString ?>&p=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php
            // 下一頁按鈕
            $nextPage = min($totalPages, $p + 1);
            ?>
            <li class="page-item <?php if ($p == $totalPages) echo "disabled"; ?>">
                <a class="page-link" href="?<?= $queryString ?>&p=<?= $totalPages ?>" aria-label="End">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const sortField = urlParams.get('sort') || '';
    const sortOrder = urlParams.get('order') || '';

    document.getElementById('fieldSelect').value = sortField;
    document.getElementById('orderSelect').value = sortOrder.toLowerCase();

    document.getElementById('fieldSelect').addEventListener('change', updateSort);
    document.getElementById('orderSelect').addEventListener('change', updateSort);

    function updateSort() {
        const field = document.getElementById('fieldSelect').value;
        const direction = document.getElementById('orderSelect').value.toUpperCase();
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', field);
        urlParams.set('order', direction);
        window.location.search = urlParams.toString();
    }

    document.querySelectorAll('[data-action="edit"]').forEach(button => {
        button.addEventListener('click', async function() {
            const ownerId = this.getAttribute('data-id');
            try {
                const response = await fetch(`/CampExplorer/admin/api/users/owner/read.php?id=${ownerId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const owner = result.data;
                const formResult = await Swal.fire({
                    title: '編輯營主',
                    html: `
                        <form id="editOwnerForm" class="text-start">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="name" placeholder="name" value="${owner.name}" required>
                                <label for="floatingInput"><i class="bi bi-person"></i> </i> <span style="color: red;">*</span> 營主名稱</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" name="email" placeholder="email" value="${owner.email}" required>
                                <label for="floatingInput"><i class="bi bi-envelope"></i> </i> <span style="color: red;">*</span>   Email</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="phone" placeholder="phone" value="${owner.phone}">
                                <label for="floatingInput"><i class="bi bi-telephone"></i> 電話</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="address" placeholder="address" value="${owner.address}">
                                <label for="floatingInput"><i class="bi bi-geo-alt"></i> 地址</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="company_name" placeholder="company_name" value="${owner.company_name}">
                                <label for="floatingInput"><i class="bi bi-building"></i> </i> <span style="color: red;">*</span> 公司名稱</label>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '保存',
                    preConfirm: () => {
                        const form = document.getElementById('editOwnerForm');
                        const formData = new FormData(form);
                        const name = formData.get('name');
                        const email = formData.get('email');
                        const companyName = formData.get('company_name');
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        if (!name) {
                            Swal.showValidationMessage('營主名稱不能為空');
                            return false;
                        }
                        if (!emailRegex.test(email)) {
                            Swal.showValidationMessage('Email格式不正確');
                            return false;
                        }
                        if (!companyName) {
                            Swal.showValidationMessage('公司名稱不能為空');
                            return false;
                        }

                        formData.append('id', ownerId); // 添加 id 到 formData
                        return fetch(`/CampExplorer/admin/api/users/owner/update.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(Object.fromEntries(formData))
                        }).then(response => response.json());
                    }
                });

                if (formResult.isConfirmed) {
                    const updateResult = formResult.value;
                    if (updateResult.success) {
                        Swal.fire('成功', '營主資料已更新', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(updateResult.message);
                    }
                }
            } catch (error) {
                Swal.fire('錯誤', error.message, 'error');
            }
        });
    });

    document.querySelectorAll('[data-action="toggle-status"]').forEach(button => {
        button.addEventListener('click', async function() {
            const ownerId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus == 1 ? 0 : 1;
            try {
                const response = await fetch(`/CampExplorer/admin/api/users/owner/read.php?id=${ownerId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const owner = result.data;
                const confirmResult = await Swal.fire({
                    title: '確認停用',
                    html: `
                    <div class="text-start mt-2">
                        <p><i class="bi bi-person"></i> 姓名: ${owner.name}</p>
                        <p><i class="bi bi-envelope"></i> Email: ${owner.email}</p>
                        <p><i class="bi bi-telephone"></i> 電話: ${owner.phone}</p>
                        <p><i class="bi bi-geo-alt"></i> 地址: ${owner.address}</p>
                        <p><i class="bi bi-building"></i> 公司名稱: ${owner.company_name}</p>
                        <p><i class="bi ${owner.status ? 'bi-toggle-on' : 'bi-toggle-off'}"></i> 狀態: ${owner.status ? '啟用' : '停用'}</p>
                        <p><i class="bi bi-calendar"></i> 註冊時間: ${owner.created_at}</p>
                        <p>您確定要${newStatus ? '啟用' : '停用'}此營主嗎？</p>
                    </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: newStatus? '確定啟用' : '確定停用',
                    cancelButtonText: '取消'
                });

                if (confirmResult.isConfirmed) {
                    const updateResponse = await fetch(`/CampExplorer/admin/api/users/owner/delete.php?id=${ownerId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ status: newStatus })
                    });
                    const updateResult = await updateResponse.json();

                    if (updateResult.success) {
                        Swal.fire('成功', '營主狀態已更新', 'success').then(() => {
                            // 更新狀態欄
                            const statusBadge = button.closest('tr').querySelector('.badge');
                            statusBadge.classList.toggle('bg-success', newStatus === 1);
                            statusBadge.classList.toggle('bg-danger', newStatus === 0);
                            statusBadge.textContent = newStatus === 1 ? '啟用' : '停用';
                            button.classList.toggle('btn-outline-danger', newStatus === 1);
                            button.classList.toggle('btn-outline-success', newStatus === 0);
                            button.textContent = newStatus === 1 ? '停用' : '啟用';
                            button.setAttribute('data-status', newStatus);
                        });
                    } else {
                        throw new Error(updateResult.message);
                    }
                }
            } catch (error) {
                Swal.fire('錯誤', error.message, 'error');
            }
        });
    });

    document.querySelector('button[data-action="add"]').addEventListener('click', async function() {
        try {
            const formResult = await Swal.fire({
                title: '新增營主',
                html: `
                    <form id="addOwnerForm" class="text-start">
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control " id="floatingName" name="name" placeholder="name" required>
                            <label for="floatingName"><i class="bi bi-person"></i> </i> <span style="color: red;">*</span> 姓名</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                            <label for="floatingPassword"><i class="bi bi-lock"></i> </i> <span style="color: red;">*</span> 密碼</label>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="email" required>
                            <label for="floatingEmail"><i class="bi bi-envelope"></i> </i> <span style="color: red;">*</span> Email</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="floatingPhone" name="phone" placeholder="phone">
                            <label for="floatingPhone"><i class="bi bi-telephone"></i> 電話</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="floatingAddress" name="address" placeholder="address">
                            <label for="floatingAddress"><i class="bi bi-geo-alt"></i> 地址</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="floatingCompanyName" name="company_name" placeholder="company_name">
                            <label for="floatingCompanyName"><i class="bi bi-building"></i> </i> <span style="color: red;">*</span> 公司名稱</label>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '新增',
                cancelButtonText: '取消',
                preConfirm: () => {
                    const form = document.getElementById('addOwnerForm');
                    const formData = new FormData(form);
                    const password = formData.get('password');
                    const passwordError = document.getElementById('passwordError');
                    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;

                    if (!passwordRegex.test(password)) {
                        passwordError.textContent = '密碼至少8個字，且包含至少一個字母和一個數字';
                        passwordError.style.display = 'block';
                        return false;
                    } else {
                        passwordError.style.display = 'none';
                    } 

                    return Object.fromEntries(formData);
                }
            });

            if (formResult.isConfirmed && formResult.value) {
                const response = await fetch('/CampExplorer/admin/api/users/owner/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formResult.value)
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire('成功', '營主新增成功', 'success');
                    location.reload();
                } else {
                    throw new Error(result.message);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('錯誤', error.message, 'error');
        }
    });

    document.getElementById('fieldSelect').addEventListener('change', updateSort);
    document.getElementById('orderSelect').addEventListener('change', updateSort);

    function updateSort() {
        const field = document.getElementById('fieldSelect').value;
        const direction = document.getElementById('orderSelect').value.toUpperCase();
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', field);
        urlParams.set('order', direction);
        window.location.search = urlParams.toString();
    }
});
document.getElementById('hideStatusZero').addEventListener('change', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (this.checked) {
        urlParams.set('hideStatusZero', '1');
    } else {
        urlParams.delete('hideStatusZero');
    }
    window.location.search = urlParams.toString();
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.email').forEach(function (element) {
        element.innerHTML = element.innerHTML.replace('@', '<wbr>@');
    });
});
</script>
<style>


    .pagination {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;

        }
        .table thead {
            display: none;
        }
        .table tbody tr {
            display: block;
            margin-bottom: 10px;
        }
        .table tbody td {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        .table tbody td::before {
            content: attr(data-label);
            font-weight: bold;
        }
    }
    .table th,
    .table td {
        /* width: 100px; */
        height: 2.54rem;
        vertical-align: middle;
        word-wrap: break-word;
        /* 自動換行 */
        word-break: break-all;
        /* 允許在任何字符處換行 */
    }
    .th, .td {
        width: 150px; /* 固定寬度 */
        word-wrap: break-word; /* 自動換行 */
    }
    .table td{
        height: 4.54rem;
    }

th.id, td.id {
    width: 5em; 
}

th.status, td.status {
    width: 5em; 
}

th.name, td.name {
    width: 5em; 
}
.table .status .badge {
        user-select: none; /* 禁止選取 */
    }
.table .address,
.table .email,
.table .created_at,
.table .company_name
{
    width: 12em; 
    
}
.table .phone{
    width: 9em; 
}
.invalid-feedback {
        position: absolute;
        bottom: -20px;
        /* 根據需要調整 */
        left: 0;
        width: 100%;
        display: none;
        /* 默認隱藏 */
    }

    .invalid-feedback.show {
        display: block;
        /* 顯示錯誤訊息 */
    }
    .table {
        border-radius: 10px; /* 調整圓角半徑 */
        overflow: hidden; /* 確保內容不會超出圓角邊界 */
    }

    .pagedata{
        margin: 20px 131px;
    }
</style>
