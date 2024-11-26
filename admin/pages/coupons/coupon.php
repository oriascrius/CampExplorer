<?php

require_once __DIR__ . '/../../../camping_db.php';

// 初始化變數
$per_page = 10;
$sqlAll = "SELECT * FROM coupons";
$resultAll = $db->query($sqlAll);
$couponsCount = $resultAll->rowCount();

// 取得分頁參數
$p = isset($_GET["p"]) ? (int)$_GET["p"] : 1; // 預設為第 1 頁
$order = isset($_GET["order"]) ? (int)$_GET["order"] : 0; // 預設按 ID 升序
$search = isset($_GET["search"]) ? $_GET["search"] : ""; // 搜尋欄位

// 若有搜尋條件，則搜尋優惠券名稱
if ($search) {
    // 搜尋時的 SQL 查詢
    $sql = "SELECT * FROM coupons WHERE name LIKE :search";
} elseif ($p == 1 && !isset($_GET["order"])) {
    // 無搜尋條件，載入所有資料，不進行分頁
    $sql = "SELECT * FROM coupons ORDER BY id ASC"; // 預設排序
    $total_page = 1; // 沒有分頁，總頁數為 1
} else {
    // 計算分頁資料
    $start_item = ($p - 1) * $per_page;
    $total_page = ceil($couponsCount / $per_page);

    // 根據排序條件構建排序語句
    $whereClause = match ($order) {
        1 => "ORDER BY name ASC",
        2 => "ORDER BY name DESC",
        3 => "ORDER BY id ASC",
        4 => "ORDER BY id DESC",
        default => "ORDER BY id ASC"
    };
    // 構建最終查詢，進行分頁
    $sql = "SELECT * FROM coupons $whereClause LIMIT $start_item, $per_page";
}

// 準備 SQL 查詢
$stmt = $db->prepare($sql);

try {
    // 如果有搜尋條件，執行帶參數的查詢
    if ($search) {
        $stmt->execute([':search' => '%' . $search . '%']);
    } else {
        $stmt->execute();
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowCount = $stmt->rowCount();
} catch (PDOException $e) {
    echo "資料庫連線失敗<br>";
    echo "錯誤: " . $e->getMessage() . "<br/>";
    $db = null;
    exit;
}
$db = null;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!-- 主要內容 -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-5 text-center font-weight-bold">優惠券管理</h1>
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <form action="" method="get">
                    <div class="input-group">
                        <input type="search" id="search" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜尋優惠券名稱">
                        <button class="btn btn-primary" onclick="htmlspecialchars()"><i class="fa fa-search" aria-hidden="true"></i></button>
                        <?php if (isset($_GET["search"])): ?><!---//查詢search是否有參數 -->
                            <a class="btn btn-primary me-2" href="index.php?page=coupons_list"><i class="fa-solid fa-left-long fa-fw"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal_2">
                    <i class="bi bi-plus-lg me-1"></i>新增優惠券
                </button>
                <form method="GET" action="index.php?page=coupons_list">
                    <select name="order" class="form-select" onchange="redirectToOrderPage(this)">
                        <option style="display: none;" value="0" <?= $order == 0 ? 'selected' : '' ?>>排序方式</option>
                        <option value="1" <?= $order == 1 ? 'selected' : '' ?>>排序由高到低</option>
                        <option value="2" <?= $order == 2 ? 'selected' : '' ?>>排序由低到高</option>
                        <option value="3" <?= $order == 3 ? 'selected' : '' ?>>排序由A到Z</option>
                        <option value="4" <?= $order == 4 ? 'selected' : '' ?>>排序由Z到A</option>
                    </select>
                </form>
                <div class="">

                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if ($rowCount > 0): ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">優惠券代碼</th>
                                        <th scope="col">優惠券名稱</th>
                                        <th scope="col">折扣類型</th>
                                        <th scope="col">折扣值</th>
                                        <th scope="col">最低消費</th>
                                        <th scope="col">使用期限</th>
                                        <th scope="col">狀態</th>
                                        <th scope="col">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $user): ?>
                                        <tr>
                                            <td><?= $user["code"] ?></td>
                                            <td><?= $user["name"] ?></td>
                                            <td><?= $user['discount_type'] === 'percentage' ? '百分比' : '固定金額' ?></td>
                                            <td><?= $user['discount_type'] === 'percentage' ? $user["discount_value"] . '%' : '$' . $user["discount_value"] ?></td>
                                            <td><?= $user["min_purchase"] ?></td>
                                            <td><?= date('Y/m/d', strtotime($user['start_date'])) ?> - <?= date('Y/m/d', strtotime($user["end_date"])) ?></td>
                                            <td><?= $user["status"] === 1 ? '<div class="p-1 bg-success text-white">啟用</div>' : '<div class="p-1 bg-danger text-white">停用</div>' ?></td>
                                            <td class="d-flex justify-content-center align-items-center">
                                                <button class="btn mx-2 btn-info edit-button" data-bs-toggle="modal" data-bs-target="#exampleModal" data-id="<?= $user['id'] ?>">編輯</button>
                                                <button class="btn mx-2 delete-button text-white <?= $user['status'] === 1 ? 'bg-danger' : 'bg-success' ?>" data-id="<?= $user['id'] ?>" data-bs-toggle="modal" data-bs-target="<?= $user['status'] === 1 ? '#deactivateModal' : '#openModal' ?>"><?= $user['status'] === 1 ? '停用' : '啟用' ?></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if (isset($total_page) && $total_page > 1): ?>
                                <nav aria-label="Page navigation example">
                                    <ul class="pagination">
                                        <?php for ($i = 1; $i <= $total_page; $i++): ?>
                                            <li class="page-item <?= ($i == $p) ? 'active' : '' ?>">
                                                <a class="page-link" href="index.php?page=coupons_list&p=<?= $i ?>&order=<?= $order ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            目前沒有符合條件的優惠券
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 跳窗 -->
<div class="modal fade" id="openModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">確定要啟用優惠卷嗎?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button class="btn btn-primary openButton">啟用</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal  D-->
<div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">確定要停用優惠卷嗎?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button class="btn btn-primary deleteButton">停用</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal U-->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">編輯用戶資料</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="user-id" name="id">
                    <table class="table table-bordered">
                        <tr>
                            <th>優惠卷代碼</th>
                            <td>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" name="code" placeholder="" aria-label="Recipient's username" aria-describedby="button-addon2" id="code-input">
                                    <button class="btn btn-outline-secondary" type="button" id="button-addon2">新增代碼</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>優惠卷名稱</th>
                            <td><input type="text" id="name" class="form-control" name="name" required></td>
                        </tr>
                        <tr>
                            <th>折扣類型</th>
                            <td><select name="discount_type" id="discount_type" class="w-100">
                                    <option value="fixed">折扣金額</option>
                                    <option value="percentage">折扣百分比</option>
                                </select></td>
                        </tr>
                        <tr>
                            <th>折扣值</th>
                            <td><input type="number" id="discount_value" class="form-control" name="discount_value" required></td>
                        </tr>
                        <tr>
                            <th>最低消費金額</th>
                            <td><input type="number" id="min_purchase" class="form-control" name="min_purchase" required></td>
                        </tr>
                        <tr>
                            <th>最高折抵金額</th>
                            <td><input type="number" id="max_discount" class="form-control" name="max_discount" required></td>
                        </tr>
                        <tr>
                            <th>優惠卷期限</th>
                            <td>
                                <input type="date" id="start_date" class="form-control" name="start_date" required>
                                <input type="date" id="end_date" class="form-control" name="end_date" required>
                            </td>
                        </tr>
                    </table>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal  C-->
<div class="modal fade" id="exampleModal_2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">新增優惠卷</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../../../../CampExplorer/admin/api/coupons/doCreateCoupon.php" method="post">
                    <input type="hidden" id="user-id" name="id">
                    <table class="table table-bordered">
                        <tr>
                            <th>優惠卷代碼</th>
                            <td>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" name="code" placeholder="" aria-label="Recipient's username" aria-describedby="button-addon22" id="code-input2">
                                    <button class="btn btn-outline-secondary" type="button" id="button-addon22">新增代碼</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>優惠卷名稱</th>
                            <td><input type="text" id="name" class="form-control" name="name" required></td>
                        </tr>
                        <tr>
                            <th>折扣類型</th>
                            <td><select name="discount_type" id="discount_type" class="w-100">
                                    <option value="fixed">折扣金額</option>
                                    <option value="percentage">折扣百分比</option>
                                </select></td>
                        </tr>
                        <tr>
                            <th>折扣值</th>
                            <td><input type="number" id="discount_value" class="form-control" name="discount_value" required></td>
                        </tr>
                        <tr>
                            <th>最低消費金額</th>
                            <td><input type="number" id="min_purchase" class="form-control" name="min_purchase" required></td>
                        </tr>
                        <tr>
                            <th>最高折抵金額</th>
                            <td><input type="number" id="max_discount" class="form-control" name="max_discount" required></td>
                        </tr>
                        <tr>
                            <th>優惠卷期限</th>
                            <td>
                                <input type="date" id="start_date" class="form-control" name="start_date" required>
                                <input type="date" id="end_date" class="form-control" name="end_date" required>
                                <!-- <input type="date" id="end_date" class="form-control hidden" name="end_date" required> -->
                            </td>
                        </tr>
                    </table>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary " id="createBtn">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!--  -->
<script>
    function htmlspecialchars(searchtElement) {
        event.preventDefault();
        const searchtxt = document.getElementById("search").value;
        const url = `index.php?page=coupons_list&search=${searchtxt}`;
        window.location.href = url; // 重定向到新的 URL
    }

    function redirectToOrderPage(selectElement) {
        const orderValue = selectElement.value;
        const p = '<?= $p ?>'; // 当前页的值
        const url = `index.php?page=coupons_list&p=${p}&order=${orderValue}`;
        window.location.href = url; // 重定向到新的 URL
    }
    document.addEventListener("DOMContentLoaded", function() {
        //
        // 使用 JavaScript 在选择框变化时执行页面跳转

        // 生成以英文开头的随机代码
        function generateRandomCode() {
            const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
            const randomLetter = letters[Math.floor(Math.random() * letters.length)];
            const randomSuffix = Math.random().toString(36).substring(2, 10); // 生成随机字符串
            return `CAMP${randomLetter.toUpperCase()}${randomSuffix.toUpperCase()}`; // 以英文字符开头的代码
        }
        // 点击按钮时触发
        document.getElementById("button-addon22").addEventListener("click", function() {
            const inputField = document.getElementById("code-input2");
            inputField.value = generateRandomCode();
        });


        document.querySelectorAll(".createBtn").forEach(function(button) {
            button.addEventListener("click", function() {
                setTimeout(() => {
                    Swal.fire({
                        title: "停用成功!",
                        icon: "success",
                        timer: 3000,
                    }).then(() => {
                        location.reload(); // 刷新页面显示最新数据
                    })
                }, 3000);
            });
        });


        // 编辑按钮点击事件
        document.querySelectorAll(".edit-button").forEach(function(button) {
            button.addEventListener("click", function() {
                const id = button.getAttribute("data-id");
                console.log("Fetching user data for ID:", id);

                // AJAX 请求用户数据
                fetch("../../../../CampExplorer/admin/api/coupons/getCoupon.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            id: id
                        }),
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.status === 1) {

                            const formatDate = (dateString) => {
                                const date = new Date(dateString);
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0'); // 月份从 0 开始
                                const day = String(date.getDate()).padStart(2, '0');
                                return `${year}-${month}-${day}`;
                            };

                            const userData = data.data;
                            document.getElementById("user-id").value = userData.id;
                            document.getElementById("code-input").value = userData.code;
                            document.getElementById("name").value = userData.name;
                            document.getElementById("discount_type").value = userData.discount_type;
                            document.getElementById("discount_value").value = userData.discount_value;
                            document.getElementById("min_purchase").value = userData.min_purchase;
                            document.getElementById("max_discount").value = userData.max_discount;
                            document.getElementById("start_date").value = formatDate(userData.start_date);
                            document.getElementById("end_date").value = formatDate(userData.end_date);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch((error) => console.error("Error fetching user data:", error));
            });
        });

        // 保存修改的资料
        document.getElementById("editForm").addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            // 输出查看数据
            for (let pair of formData.entries()) {
                console.log(pair[0] + ": " + pair[1]);
            }

            // AJAX 发送保存请求
            fetch("../../../../CampExplorer/admin/api/coupons/doUpdataCoupon.php", {
                    method: "POST",
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 1) {
                        Swal.fire({
                            title: "资料更新成功!",
                            icon: "success",
                            timer: 1000,
                        }).then(() => {
                            location.reload(); // 刷新页面显示最新数据
                        })
                        // alert("资料更新成功！");

                    } else {
                        alert(data.message);
                    }
                })
                .catch((error) => console.error("Error updating user data:", error));
        });

        // 删除按钮点击事件
        document.querySelectorAll(".delete-button").forEach(function(button) {
            button.addEventListener("click", function() {
                const id = button.getAttribute("data-id");
                const modalButton = document.querySelector("#deactivateModal .deleteButton");
                modalButton.setAttribute("data-id", id); // 设置 ID 到模态框确认按钮
                console.log("删除 ID：" + id);
            });
        });

        // 确认删除
        document.querySelector("#deactivateModal .deleteButton").addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            if (!id) {
                alert("无法获取用户 ID！");
                return;
            }

            fetch("../../../../CampExplorer/admin/api/coupons/doDelete.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        id: id
                    }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 1) {
                        Swal.fire({
                            title: "停用成功!",
                            icon: "success",
                            timer: 1000,
                        }).then(() => {
                            location.reload(); // 刷新页面显示最新数据
                        })
                        // 删除成功后从表格中移除对应行
                        // const row = document.querySelector(`.delete-button[data-id="${id}"]`).closest("tr");
                        // row.remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch((error) => console.error("Error fetching user data:", error));
        });

        // 删除按钮点击事件
        document.querySelectorAll(".delete-button").forEach(function(button) {
            button.addEventListener("click", function() {
                const id = button.getAttribute("data-id");
                const modalButton = document.querySelector("#openModal .openButton");
                modalButton.setAttribute("data-id", id); // 设置 ID 到模态框确认按钮
                console.log("删除 ID：" + id);
            });
        });

        // 确认删除
        document.querySelector("#openModal .openButton").addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            if (!id) {
                alert("无法获取用户 ID！");
                return;
            }

            fetch("../../../../CampExplorer/admin/api/coupons/doOpen.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        id: id
                    }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 1) {
                        Swal.fire({
                            title: "啟用成功!",
                            icon: "success",
                            timer: 1000,
                        }).then(() => {
                            location.reload(); // 刷新页面显示最新数据
                        })
                        // 删除成功后从表格中移除对应行
                        // const row = document.querySelector(`.delete-button[data-id="${id}"]`).closest("tr");
                        // row.remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch((error) => console.error("Error fetching user data:", error));
        });
    });
</script>