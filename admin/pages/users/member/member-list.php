<?php
require_once __DIR__ . '/../../../../camping_db.php';

// 初始化變數
$error_message = null;
$users = [];



try {
    $allowed_fields = [
        'id',
        'email',
        'name',
        'phone',
        'birthday',
        'gender',
        'address',
        'avatar',
        'last_login',
        'status',
        'created_at',
        'updated_at'
    ];

    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';
    $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $perPage = 10; // 每頁顯示的會員數量
$offset = max(0, ($p - 1) * $perPage);
    // 修改 SQL 查詢，處理不同欄位的排序
    $orderBy = in_array($sort_field, $allowed_fields) ? "u.{$sort_field} {$sort_order}" : "u.created_at ASC";

    // 獲取會員列表，包含所有狀態的會員，並進行分頁
    $sql = "SELECT u.*
            FROM users u
            ORDER BY {$orderBy}
            LIMIT :perPage OFFSET :offset";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取會員總數
    $totalStmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $totalStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
function translateGender($gender) {
    switch ($gender) {
        case 'male':
            return '男';
        case 'female':
            return '女';
        default:
            return '其他';
    }
}

?>
<!-- 頁面主要內容 -->
<div class="container-fluid py-4 px-5 ">
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- 頁面標題和新增按鈕 -->
    <div class="d-flex justify-content-center align-items-center mb-4">
        <h2 class="h1 mb-0 fw-bold">會員管理</h2>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <button type="button" class="btn btn-success mb-2 mb-md-0" data-action="add">
            <i class="bi bi-plus-lg"></i> 新增會員
        </button>
        
       
    <div class="d-flex flex-column align-items-start">
        <label for="fieldSelect" class="mb-2 fw-bold ">排序方式：</label>
        <div class="d-flex">
            <select id="fieldSelect" class="form-select me-2 mb-2 mb-md-0 ">
            
                <option value="">--未選擇--</option>
            <option value="id">編號</option>
                <option value="name">姓名</option>
                <option value="birthday">生日</option>
                <option value="gender">性別</option>
                <option value="last_login">最後登入</option>
                <option value="status">狀態</option>
                <option value="created_at">建立時間</option>
                <option value="updated_at">更新日期</option>
            </select>
            <select id="orderSelect" class="form-select">
            <option value="">--未選擇--</option>
                <option value="asc">升序</option>
                <option value="desc">降序</option>
            </select>
        </div>
    </div>
    </div>

    <!-- 會員列表 -->
    <div class="card">
        <div class="" id="userTableContainer">
            <div class="table-responsive ">
                <table class="table table-striped table-bordered ">
                    <thead class="table-dark ">
                        <tr >
                            <th class="id">
                                <h6 class="m-0 p-0 fw-bold " data-sort="id">
                                    編號 </i>
    </h6>
                            </th>
                            <th>
                                <h6 class="m-0  p-0 fw-bold " data-sort="email">
                                    Email </i>
    </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="name">
                                    姓名 </i>
    </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="phone">
                                    電話 </i>
    </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="birthday">
                                    生日 </i>
    </h6>
                            </th>
                            <th class="gender">
                                <h6 class="m-0 p-0 fw-bold text-center" data-sort="gender">
                                    性別 </i>
    </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="address">
                                    地址 </i>
    </h6>
                            </th>
            
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="last_login">
                                    最後登入 </i>
                                </h6>
                            </th>
                            <th class="status">
                                <h6 class="m-0 p-0 fw-bold text-center" data-sort="status">
                                    狀態 </i>
                                </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="created_at">
                                    建立時間 </i>
                                </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold " data-sort="updated_at">
                                    更新時間 </i>
                                </h6>
                            </th>
                            <th>
                                <h6 class="m-0 p-0 fw-bold text-center" data-sort="avatar">
                                    操作</i>
                                </h6>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
            <td class="id"><?= htmlspecialchars($user['id']) ?></td>
            <td ><?= htmlspecialchars($user['email']) ?></td>
            <td ><?= htmlspecialchars($user['name']) ?></td>
            <td ><?= htmlspecialchars($user['phone']) ?></td>
            <td ><?= htmlspecialchars($user['birthday']) ?></td>
            <td class="gender text-center"><?= htmlspecialchars(translateGender($user['gender'])) ?></td>
            <td ><?= htmlspecialchars($user['address']) ?></td>

            <td><?= htmlspecialchars($user['last_login']) ?></td>
            <td class="status text-center">
                <span class="badge <?= $user['status'] ? 'bg-success' : 'bg-danger' ?>">
                    <?= $user['status'] ? '啟用' : '停用' ?>
                </span>
            </td>
            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
            <td><?= date('Y-m-d', strtotime($user['updated_at'])) ?></td>
            <td>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="<?= $user['id'] ?>">
                        編輯
                    </button>
                    <button type="button" 
                            class="btn btn-sm <?= $user['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                            data-action="toggle-status"
                            data-id="<?= $user['id'] ?>"
                            data-status="<?= $user['status'] ?>">
                        <?= $user['status'] ? '停用' : '啟用' ?>
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

    <!-- 分頁導航 -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation example">
            <ul class="pagination">
                <?php
                $queryString = $_SERVER['QUERY_STRING'];
                parse_str($queryString, $queryArray);
                unset($queryArray['p']);
                $queryString = http_build_query($queryArray);
                for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php if ($i == $p) echo "active"; ?>">
                        <a class="page-link" href="?<?= $queryString ?>&p=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
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
            const userId = this.getAttribute('data-id');
            try {
                const response = await fetch(`/CampExplorer/admin/api/users/member/read.php?id=${userId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const user = result.data;
                const formResult = await Swal.fire({
                    title: '編輯會員',
                    html: `
                        <form id="editUserForm" class="text-start">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="name" placeholder="name" value="${user.name}" required>
                                <label for="floatingInput"><i class="bi bi-person"></i> 會員名稱</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" name="email" placeholder="email" value="${user.email}" required>
                                <label for="floatingInput"><i class="bi bi-envelope"></i> Email</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="phone" placeholder="phone" value="${user.phone}">
                                <label for="floatingInput"><i class="bi bi-telephone"></i> 電話</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="address" placeholder="address" value="${user.address}">
                                <label for="floatingInput"><i class="bi bi-geo-alt"></i> 地址</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" name="birthday" placeholder="birthday" value="${user.birthday}">
                                <label for="floatingInput"><i class="bi bi-calendar"></i> 生日</label>
                            </div>
                            <div class="form-floating mb-3">
                                <select class="form-control" name="gender" required>
                                    <option value="male" ${user.gender === 'male' ? 'selected' : ''}>男</option>
                                    <option value="female" ${user.gender === 'female' ? 'selected' : ''}>女</option>
                                    <option value="other" ${user.gender === 'other' ? 'selected' : ''}>其他</option>
                                </select>
                                <label for="floatingInput"><i class="bi bi-gender-ambiguous"></i> 性別</label>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '保存',
                    preConfirm: () => {
                        const form = document.getElementById('editUserForm');
                        const formData = new FormData(form);
                        formData.append('id', userId); // 添加 id 到 formData
                        return fetch(`/CampExplorer/admin/api/users/member/update.php`, {
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
                        Swal.fire('成功', '會員資料已更新', 'success').then(() => {
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

    function translateGender(gender) {
    switch (gender) {
        case 'male':
            return '男';
        case 'female':
            return '女';
        default:
            return '其他';
    }
}
    document.querySelectorAll('[data-action="toggle-status"]').forEach(button => {
        button.addEventListener('click', async function() {
            const userId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus == 1 ? 0 : 1;
            try {
                const response = await fetch(`/CampExplorer/admin/api/users/member/read.php?id=${userId}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                const user = result.data;
                const formResult = await Swal.fire({
                    title: user.status ? '停用會員' : '啟用會員',
                    html: `
                    <div class="text-start">
                        <p><i class="bi bi-person"></i> 會員名稱: ${user.name}</p>
                        <p><i class="bi bi-envelope"></i> Email: ${user.email}</p>
                        <p><i class="bi bi-telephone"></i> 電話: ${user.phone}</p>
                        <p><i class="bi bi-geo-alt"></i> 地址: ${user.address}</p>
                        <p><i class="bi bi-calendar"></i> 生日: ${user.birthday}</p>
                        <p><i class="bi bi-gender-ambiguous"></i> 性別: ${translateGender(user.gender)}</p>
                      
                        <p><i class="bi ${user.status ? 'bi-toggle-on' : 'bi-toggle-off'}"></i> 狀態: ${user.status ? '啟用' : '停用'}</p>
                        <p>確定要${user.status ? '停用' : '啟用'}此會員嗎？</p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: user.status ? '確定停用' : '確定啟用',
                    cancelButtonText: '取消',
                    preConfirm: () => {
                        return fetch(`/CampExplorer/admin/api/users/member/delete.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id: userId, status: newStatus })
                        }).then(response => response.json());
                    }
                });

                if (formResult.isConfirmed) {
                    const updateResult = formResult.value;
                    if (updateResult.success) {
                        Swal.fire('成功', '會員狀態已更新', 'success').then(() => {
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
                title: '新增會員',
                html: `
                    <form id="addUserForm" class="text-start">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control " id="floatingName" name="name" placeholder="name" required>
                            <label for="floatingName"><i class="bi bi-person"></i> 姓名</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                            <label for="floatingPassword"><i class="bi bi-lock"></i> 密碼</label>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="email" required>
                            <label for="floatingEmail"><i class="bi bi-envelope"></i> Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingPhone" name="phone" placeholder="phone">
                            <label for="floatingPhone"><i class="bi bi-telephone"></i> 電話</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingAddress" name="address" placeholder="address">
                            <label for="floatingAddress"><i class="bi bi-geo-alt"></i> 地址</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="floatingBirthday" name="birthday" placeholder="birthday">
                            <label for="floatingBirthday"><i class="bi bi-calendar"></i> 生日</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-control" id="floatingGender" name="gender" required>
                             <option value="">--未選擇--</option>
                                <option value="male">男</option>
                                <option value="female">女</option>
                                <option value="other">其他</option>
                            </select>
                            <label for="floatingGender"><i class="bi bi-gender-ambiguous"></i> 性別</label>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '新增',
                cancelButtonText: '取消',
                preConfirm: () => {
                    const form = document.getElementById('addUserForm');
                    const formData = new FormData(form);
                    const password = formData.get('password');
                    const passwordError = document.getElementById('passwordError');
                    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;

                    if (!passwordRegex.test(password)) {
                        passwordError.textContent = '密碼至少8個字符，且包含至少一個字母和一個數字';
                        passwordError.style.display = 'block';
                        return false;
                    } else {
                        passwordError.style.display = 'none';
                    } 
                    

                    return Object.fromEntries(formData);
                }
            });

            if (formResult.isConfirmed && formResult.value) {
                const response = await fetch('/CampExplorer/admin/api/users/member/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formResult.value)
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire('成功', '會員新增成功', 'success');
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

</script>

<style>


 

    /* .btn-link {
        text-decoration: none;
    }
    .btn-link:hover {
        text-decoration: none;
    } */
    /* thead button[data-sort] {
        font-weight: bold;
    } */
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
    .table th, .table td {
        width: 150px; /* 固定寬度 */
        word-wrap: break-word; /* 自動換行 */
    }
    .table th.id, .table td.id,
    .table th.gender, .table td.gender,
    .table th.status, .table td.status {
        width: 60px; /* 調整寬度 */
    }
    .table th.name, .table td.name {
        width: 80px; /* 調整寬度以容納4個中文字 */
    }
    .table .status .badge {
        user-select: none; /* 禁止選取 */
    }
</style>