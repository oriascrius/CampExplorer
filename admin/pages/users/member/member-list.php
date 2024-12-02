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
    $perPage = 7; // 每頁顯示的會員數量
    $offset = max(0, ($p - 1) * $perPage);
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    // 修改 SQL 查詢，處理不同欄位的排序
    $orderBy = in_array($sort_field, $allowed_fields) ? "u.{$sort_field} {$sort_order}" : "u.created_at ASC";

    // 獲取會員列表，包含所有狀態的會員，並進行分頁
    $sql = "SELECT u.*
            FROM users u
            WHERE 1=1";
    
    if (!empty($search)) {
        $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    }
    
    if (isset($_GET['hideStatusZero']) && $_GET['hideStatusZero'] == '1') {
        $sql .= " AND u.status != 0";
    }

    $sql .= " ORDER BY {$orderBy}
              LIMIT :perPage OFFSET :offset";

    $stmt = $db->prepare($sql);
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取會員總數
    $totalSql = "SELECT COUNT(*) FROM users WHERE 1=1";
    if (isset($_GET['hideStatusZero']) && $_GET['hideStatusZero'] == '1') {
        $totalSql .= " AND status != 0";
    }
    $totalStmt = $db->query($totalSql);
    $totalUsers = $totalStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);

    // 獲取停用會員數量
    $statusZeroCountSql = "SELECT COUNT(*) FROM users WHERE status = 0";
    $statusZeroCountStmt = $db->query($statusZeroCountSql);
    $statusZeroCount = $statusZeroCountStmt->fetchColumn();
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
function translateGender($gender)
{
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
<div class="container pagedata ">
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- 頁面標題和新增按鈕 -->
    <div class="d-flex justify-content-start align-items-center mb-4">
        <h1 class=" mt-4 fw-bold me-5">會員管理</h1>
    </div>
    <div class="d-flex justify-content-between align-items-end flex-wrap">
        
        <button type="button" class="btn btn-success " data-action="add">
            <i class="bi bi-plus-lg"></i> 新增會員
        </button>

     

        <div class="d-flex flex-column align-items-start">
            
            <!-- 勾選框 -->
            <div class="d-flex align-items-center mb-2">
    
    <label for="fieldSelect" class="fw-bold mb-0">排序方式：</label><div class="form-check me-3">
        <input class="form-check-input" type="checkbox" value="" id="hideStatusZero" <?= isset($_GET['hideStatusZero']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="hideStatusZero">
            隱藏 停用 <?= $statusZeroCount ?>名會員
        </label>
    </div>
    
</div>
<div class="d-flex flex-wrap">
    <div class="mb-2 mb-md-0">
        <select id="fieldSelect" class="form-select fixed-width">
            <option value="">--未選擇--</option>
            <option value="id">編號</option>
            <option value="name">姓名</option>
            <option value="birthday">生日</option>
            <option value="gender">性別</option>
            <option value="status">狀態</option>
            <option value="created_at">建立時間</option>
        </select>
    </div>
    <div class="mb-2 mb-md-0">
        <select id="orderSelect" class="form-select fixed-width">
            <option value="">--未選擇--</option>
            <option value="asc">升序</option>
            <option value="desc">降序</option>
        </select>
    </div>
    <div class="mb-2 mb-md-0">
        <form class="d-flex" method="GET" action="http://localhost/CampExplorer/admin/index.php">
            <input type="hidden" name="page" value="members_list">
            <input type="text" class="form-control fixed-width me-1" name="search" placeholder="搜尋會員" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
</div>

        
    </div>
</div>

    <!-- 會員列表 -->
    <div class="card">
        <div class="" id="userTableContainer">
            <div class="table-responsive ">
                <table class="table table-bordered ">
                    <thead class="">
                        <tr class="text-center">
                            <?php
                            $headers = [
                                'id' => '編號',
                                'email' => 'Email',
                                'name' => '姓名',
                                'phone' => '電話',
                                'birthday' => '生日',
                                'gender' => '性別',
                                'address' => '地址',                              
                                'status' => '狀態',
                                'created_at' => '建立時間',
                                
                            ];
                            foreach ($headers as $field => $label): ?>
                                <th class="<?= $field ?>">
                                    <h6 class="m-0 p-0 fw-bold <?= $field === 'gender' || $field === 'status' || $field === 'actions' ? 'text-center' : '' ?>" data-sort="<?= $field ?>">
                                        <?= $label ?>
                                        <?php if ($field !== 'email'): ?>
                                        <!-- <i class="bi bi-arrow-<?= $sort_field === $field ? ($sort_order === 'ASC' ? 'up' : 'down') : 'down-up' ?> sort-icon"></i> -->
                                        <?php endif; ?>
                                    </h6>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row" data-id="<?= $user['id'] ?>">
                            <td class="id px-2 text-center"><?= htmlspecialchars($user['id']) ?></td>
                            <td class="email px-2 text-center"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($user['name']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($user['phone']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($user['birthday']) ?></td>
                            <td class="gender text-center"><?= htmlspecialchars(translateGender($user['gender'])) ?></td>
                            <td><?= htmlspecialchars($user['address']) ?></td>
                           
                            <td class="status text-center">
                                <span class="badge <?= $user['status'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $user['status'] ? '啟用' : '停用' ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                        
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
            if (isset($_GET['hideStatusZero']) && $_GET['hideStatusZero'] == '1') {
                $queryArray['hideStatusZero'] = 1;
            }
            $queryString = http_build_query($queryArray);

            // 上一頁按鈕
            $prevPage = max(1, $p - 1);
            $endPage = min($totalPages, $p + 1);
            ?>
            <li class="page-item <?php if ($p == 1) echo "disabled"; ?>">
                <a class="page-link" href="?<?= $queryString ?>&p=<?= 1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php
            for ($i = $prevPage; $i <= $endPage; $i++): ?>
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

        document.getElementById('hideStatusZero').addEventListener('change', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (this.checked) {
                urlParams.set('hideStatusZero', '1');
            } else {
                urlParams.delete('hideStatusZero');
            }
            window.location.search = urlParams.toString();
        });

        document.querySelectorAll('.email').forEach(function (element) {
            element.innerHTML = element.innerHTML.replace('@', '<wbr>@');
        });

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
                            <div class="form-floating mb-4">
                                <input type="text" class="form-control" name="name" placeholder="name" value="${user.name}" required>
                                <label for="floatingInput"><i class="bi bi-person"></i> </i> <span style="color: red;">*</span>  會員名稱</label>
                                <div class="invalid-feedback" id="nameError"></div>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="email" class="form-control" name="email" placeholder="email" value="${user.email}" required>
                                <label for="floatingInput"><i class="bi bi-envelope"></i> </i> <span style="color: red;">*</span> Email</label>
                                <div class="invalid-feedback" id="emailError"></div>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="text" class="form-control" name="phone" placeholder="phone" value="${user.phone}">
                                <label for="floatingInput"><i class="bi bi-telephone"></i> </i> <span style="color: red;">*</span> 電話</label>
                                <div class="invalid-feedback" id="phoneError"></div>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="text" class="form-control" name="address" placeholder="address" value="${user.address}">
                                <label for="floatingInput"><i class="bi bi-geo-alt"></i> 地址</label>
                            </div>
                            <div class="form-floating mb-4 pt-1">
                                <input type="date" class="form-control" name="birthday" placeholder="birthday" value="${user.birthday}">
                                <label for="floatingInput"><i class="bi bi-calendar"></i> 生日</label>
                            </div>
                            <div class="form-floating mb-4 pt-1">
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
                            const name = formData.get('name');
                            const email = formData.get('email');
                            const nameError = document.getElementById('nameError');
                            const emailError = document.getElementById('emailError');
                            const nameRegex = /^[\p{L}\s]+$/u;

                            if (!name || !nameRegex.test(name)) {
                                nameError.textContent = '姓名不得為空且必須為有效的字符';
                                nameError.classList.add('show');
                                return false;
                            } else {
                                nameError.classList.remove('show');
                            }

                            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                                emailError.textContent = '請輸入有效的電子郵件地址';
                                emailError.classList.add('show');
                                return false;
                            } else {
                                emailError.classList.remove('show');
                            }

                            const phone = formData.get('phone');
                            const phoneError = document.getElementById('phoneError');
                            if (!phone || !/^[0-9]{10}$/.test(phone)) {
                                phoneError.textContent = '請輸入有效的電話號碼';
                                phoneError.classList.add('show'); // 確保這行存在
                                return false;
                            } else {
                                phoneError.classList.remove('show'); // 確保這行存在
                            }

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
                    <div class="text-start mt-2">
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
                                body: JSON.stringify({
                                    id: userId,
                                    status: newStatus
                                })
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
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control " id="floatingName" name="name" placeholder="name" required>
                            <label for="floatingName"><i class="bi bi-person"></i> <span style="color: red;">*</span> 姓名</label>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                            <label for="floatingPassword"><i class="bi bi-lock"></i> <span style="color: red;">*</span> 密碼(8個字以上英數混合)</label>
                        
                         <div class="invalid-feedback" id="passwordError"></div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="email" required>
                            <label for="floatingEmail"><i class="bi bi-envelope"></i> <span style="color: red;">*</span> Email</label>
                            <div class="invalid-feedback" id="emailError"></div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="floatingPhone" name="phone" placeholder="phone">
                            <label for="floatingPhone"><i class="bi bi-telephone"></i> <span style="color: red;">*</span> 電話</label>
                            <div class="invalid-feedback" id="phoneError"></div> <!-- 添加這行 -->
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="floatingAddress" name="address" placeholder="address">
                            <label for="floatingAddress"><i class="bi bi-geo-alt"></i> 地址</label>
                        </div>
                        <div class="form-floating mb-4 pt-1">
                            <input type="date" class="form-control" id="floatingBirthday" name="birthday" placeholder="birthday">
                            <label for="floatingBirthday"><i class="bi bi-calendar"></i> 生日</label>
                        </div>
                        <div class="form-floating mb-4 pt-1">
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
                        const name = formData.get('name');
                        const email = formData.get('email');
                        const phone = formData.get('phone');
                        const nameError = document.getElementById('nameError');
                        const emailError = document.getElementById('emailError');
                        const phoneError = document.getElementById('phoneError');
                        const nameRegex = /^[\p{L}\s]+$/u;

                        if (!name || !nameRegex.test(name)) {
                            nameError.textContent = '姓名不得為空且必須為有效的字符';
                            nameError.classList.add('show');
                            return false;
                        } else {
                            nameError.classList.remove('show');
                        }

                        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            emailError.textContent = '請輸入有效的電子郵件地址';
                            emailError.classList.add('show');
                            return false;
                        } else {
                            emailError.classList.remove('show');
                        }
                        if (!phone || !/^[0-9]{10}$/.test(phone)) {
                            phoneError.textContent = '請輸入有效的電話號碼';
                            phoneError.classList.add('show'); // 確保這行存在
                            return false;
                        } else {
                            phoneError.classList.remove('show'); // 確保這行存在
                        }

                        const password = formData.get('password');
                        const passwordError = document.getElementById('passwordError');
                        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;

                        if (!passwordRegex.test(password)) {
                            passwordError.textContent = '密碼至少8個字，且包含至少一個字母和一個數字';
                            passwordError.classList.add('show');
                            // passwordError.style.display = 'block';
                            return false;
                        } else {
                            passwordError.classList.remove('show');
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

        document.querySelectorAll('.user-row').forEach(row => {
            row.addEventListener('click', async function(event) {
                if (event.target.closest('button')) {
                    return; // 如果點擊的是按鈕，則不處理
                }
                const userId = this.getAttribute('data-id');
                try {
                    const response = await fetch(`/CampExplorer/admin/api/users/member/read.php?id=${userId}`);
                    const result = await response.json();

                    if (!result.success) {
                        throw new Error(result.message);
                    }

                    const user = result.data;
                    Swal.fire({
                        title: '會員資料',
                        html: `
                            <div class="text-start mt-2">
                                <p><i class="bi bi-person"></i> 會員名稱: ${user.name}</p>
                                <p><i class="bi bi-envelope"></i> Email: ${user.email}</p>
                                <p><i class="bi bi-telephone"></i> 電話: ${user.phone}</p>
                                <p><i class="bi bi-geo-alt"></i> 地址: ${user.address}</p>
                                <p><i class="bi bi-calendar"></i> 生日: ${user.birthday}</p>
                                <p><i class="bi bi-gender-ambiguous"></i> 性別: ${translateGender(user.gender)}</p>
                                <p><i class="bi bi-clock"></i> 最後登入: ${user.last_login ? user.last_login : '尚未登入'}</p>
                                <p><i class="bi bi-calendar-plus"></i> 建立時間: ${user.created_at}</p>
                                <p><i class="bi bi-calendar-check"></i> 更新時間: ${user.updated_at}</p>
                                <p><i class="bi ${user.status ? 'bi-toggle-on' : 'bi-toggle-off'}"></i> 狀態: ${user.status ? '啟用' : '停用'}</p>
                            </div>
                        `,
                        showCloseButton: true,
                        focusConfirm: false,
                        confirmButtonText: '關閉'
                    });
                } catch (error) {
                    Swal.fire('錯誤', error.message, 'error');
                }
            });
        });
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
document.querySelectorAll('.email').forEach(function (element) {
    element.innerHTML = element.innerHTML.replace('@', '<wbr>@');
});
</script>

<style>
    .table td.email {
    word-break: break-all;
}
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

    .table th {
      
        
        height: 2.54rem;
        vertical-align: middle;
        word-wrap: break-word;
        /* 自動換行 */
        word-break: break-all;
        /* 允許在任何字符處換行 */
    }
    .table td{
        height: 4.54rem;
        vertical-align: middle;
        word-break: break-all;
      
    }
    .table th.id,
    .table th.gender {
        width: 5rem;
        /* 調整寬度 */
    }
    .table th.created_at{
        width: 7.4rem;
        /* 調整寬度 */
    }

    .table th.address {
        width: 17.4rem;
        /* 調整寬度 */
        word-wrap: break-word;

    }
    .table th.email {
        width: 12rem;
        /* 調整寬度 */
    }
    .table th.phone {
        width: 7rem;
        /* 調整寬度 */
    }

    .table th.name {
        width: 5rem;
        /* 調整寬度以容納4個中文字 */
    }

    .table .status .badge {
        user-select: none;
        /* 禁止選取 */
    }

    .table .birthday{
        width: 7.1rem;
        /* 調整寬度 */
    }

    .form-floating {
        position: relative;
    }

    .invalid-feedback {
        position: absolute;
        bottom: -20px;
        /* 根據需要調整 */
        left: 0;
        width: 100%;
        display: none;
       
    }

    .invalid-feedback.show {
        display: block;
        /* 顯示錯誤訊息 */
    }
    .table {
        border-radius: 10px; /* 調整圓角半徑 */
        overflow: hidden; /* 確保內容不會超出圓角邊界 */
    }
    .fixed-width {
    width: 9.1rem; /* 設置寬度為 5 個字元大小 */
}
    .pagedata{
        margin: 54px 131px;
    }
    .container{
        padding: 4rem;
        padding-top: 1rem;
        max-width: 100%;
        margin: 0;
        padding-bottom: 1rem;
    }
    .btn.btn-success{
        background-color: #ecba82;
        border: 0;
    }
    .d-flex.justify-content-between{
        background-color: #fff;
        padding: 15px;
        border-radius: 30px 30px 0 0;
        box-shadow: 0px 18px 10px rgba(0, 0, 0, 0.1);
    }
    .card{
        padding: 0 15px;
        border: 0;
        border-radius: 0px 0px 30px 30px;
        box-shadow: 0px 10px 10px rgba(0, 0, 0, 0.1);
    }
    .table{
        border-radius: 0;
        color: #fff;
    }
    .btn-outline-primary.btn-sm{
        color: #8b6a09;
        background-color: #ffc1076e;
        border: 0;
    }
    .btn-sm.btn-outline-success{
        background-color: #0080003b !important;
        color: green !important;
        border: 0;
    }
    .btn-sm.btn-outline-danger{
        background-color: #f5000029 !important;
        color: #db0000 !important;
        border: 0;
    }
    .badge.bg-success{
        background-color: transparent !important;
        border: 1px solid #0080005c;
        color: #008000 !important;
        padding: 7px 23px;
    }
    .badge.bg-danger{
        background-color: transparent !important;
        border: 1px solid #ff000040;
        color: #db0000 !important;
        padding: 7px 23px;
    }
    .flex-wrap .mb-2.mb-md-0{
        margin-right: 15px;
    }
    tbody tr:hover{
        background: rgb(155 254 144 / 10%);
        transition: all 0.2s ease-in-out;
        box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 10%);
        --bs-table-accent-bg: none!important;
    }
</style>