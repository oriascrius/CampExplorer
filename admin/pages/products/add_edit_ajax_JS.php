<script>
    // 編輯商品
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // 獲取資料
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = this.dataset.price;
            const stock = this.dataset.stock;
            const status = this.dataset.status;
            const categoryId = this.dataset.category; // 主分類 ID
            const subcategoryId = this.dataset.subcategory; // 次分類 ID

            // 填充表單
            document.getElementById('productId').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('productPrice').value = price;
            document.getElementById('productStock').value = stock;
            document.getElementById('productStatus').value = status;
            document.getElementById('editCategoryId').value = categoryId;

            const editCategorySelect = document.getElementById("editCategoryId");
            const editSubcategorySelect = document.getElementById("editSubcategoryId");

            // 初始化次類別選項
            populateSubcategories(categoryId, subcategoryId);

            // 設定主分類改變時的行為
            editCategorySelect.addEventListener("change", function() {
                populateSubcategories(this.value, null);
            });

            // 顯示 Modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });



    // 當主分類改變時，更新次類別選項
    document.getElementById('editCategoryId').addEventListener('change', function() {
        const selectedCategoryId = this.value;
        populateSubcategories(selectedCategoryId, null); // 次分類未預選
    });


    const editForm = document.getElementById('editForm');
    editForm.addEventListener('submit', function(event) {
        event.preventDefault(); // 防止默認表單提交

        // 創建 FormData 物件，將表單資料序列化
        const formData = new FormData(editForm);

        // 發送 AJAX 請求
        updateProduct(formData)
            .then(response => {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '修改成功',
                        text: '商品已成功修改！',
                        showConfirmButton: false,
                        timer: 1000 // 自動關閉提示
                    }).then(() => {
                        window.location.reload();
                    }) // 重新加載頁面

                } else {
                    alert("商品更新失敗：" + response.message);
                }
            })
            .catch(error => {
                console.error("發生錯誤：", error);
                alert("更新失敗，請稍後再試！");
            });
    });

    function updateProduct(data) {
        return new Promise((resolve, reject) => {
            fetch('api/products/edit_product.php', {
                    method: 'POST',
                    body: data
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP 錯誤！狀態碼：${response.status}`);
                    }
                    return response.json(); // 將回應轉為 JSON
                })
                .then(result => resolve(result)) // 成功處理 JSON
                .catch(error => reject(error)); // 處理錯誤
        });
    }




    // 新增商品
    const addProductForm = document.getElementById('addProductForm');

    // 當新增商品 Modal 顯示時，初始化次類別選項
    const addProductModal = document.getElementById('addProductModal');
    addProductModal.addEventListener('shown.bs.modal', function() {
        document.getElementById('addProductForm').reset(); // 清空表單
        const categoryId = document.getElementById('addcategory_id').value;
        addPopulateSubcategories(categoryId, null); // 初始化次類別選項
    });

    // 當主分類選擇變更時，更新次類別選項
    document.getElementById('addcategory_id').addEventListener('change', function() {
        const selectedCategoryId = this.value;
        addPopulateSubcategories(selectedCategoryId, null); // 次類別未預選
    });

    // 處理表單提交
    addProductForm.addEventListener('submit', function(event) {
        event.preventDefault(); // 防止表單默認提交行為

        // 確保主圖片已選擇
        const mainImage = document.getElementById('mainImage').files[0];
        if (!mainImage) {
            alert("請選擇主圖片");
            return; // 不繼續執行下面的程式
        }

        const submitBtn = document.getElementById('submitBtn'); // 假設按鈕ID是submitButton
        submitBtn.disabled = true;





        // 新增圖片
        document.getElementById('additionalImages').addEventListener('change', function() {
            const fileInput = this;
            const selectElement = document.getElementById('mainImageSelection');
            const mainImageFlag = document.getElementById('main_image_flag');
            // 清空選項
            selectElement.innerHTML = '<option value="" disabled selected>請先上傳圖片後選擇</option>';


            // 顯示提示訊息
            if (fileInput.files.length === 0) {
                selectElement.disabled = true;
            } else {
                selectElement.disabled = false;
            }

            // 當主圖片選擇框變更時，更新 hidden 輸入欄位的值
            selectElement.addEventListener('change', function() {
                const selectedIndex = selectElement.value;
                mainImageFlag.value = selectedIndex; // 設置 hidden 欄位的值
            });
        });



        // 創建 FormData 物件，將表單資料序列化
        const formData = new FormData(addProductForm);

        // 打印 FormData 內容進行檢查
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // 發送 AJAX 請求
        addProduct(formData)
            .then(response => {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '新增成功',
                        text: '商品已成功新增！',
                        showConfirmButton: false,
                        timer: 1000 // 自動關閉提示
                    }).then(() => {
                        window.location.reload();
                    }) // 重新加載頁面
                } else {
                    alert("新增商品失敗：" + response.message);
                }
            })
            .catch(error => {
                console.error("發生錯誤：", error);
                alert("新增失敗，請稍後再試！");
            });
    });


    // 新增商品的 AJAX 函式
    function addProduct(data) {
        return new Promise((resolve, reject) => {
            fetch('api/products/add_product.php', {
                    method: 'POST',
                    body: data
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP 錯誤！狀態碼：${response.status}`);
                    }
                    return response.json(); // 將回應轉為 JSON
                })
                .then(result => resolve(result)) // 成功處理 JSON
                .catch(error => reject(error)); // 處理錯誤
        });
    }





    // 
    function populateSubcategories(categoryId, selectedSubcategoryId) {
        const editSubcategorySelect = document.getElementById("editSubcategoryId");

        // 清空次類別選項
        editSubcategorySelect.innerHTML = '<option value="" disabled selected>請選擇次類別</option>';

        // 篩選符合主分類的次類別
        const filteredSubcategories = allSubcategories.filter(
            subcategory => subcategory.subcategory_category == categoryId
        );

        // 新增篩選後的次類別選項
        filteredSubcategories.forEach(subcategory => {
            const option = document.createElement("option");
            option.value = subcategory.id;
            option.textContent = subcategory.name;

            // 如果是當前商品的次分類，則預設選中
            if (subcategory.id == selectedSubcategoryId) {
                option.selected = true;
            }

            editSubcategorySelect.appendChild(option);
        });

        // 如果篩選後無次類別，禁用選單
        editSubcategorySelect.disabled = filteredSubcategories.length === 0;
    }

    function addPopulateSubcategories(categoryId, selectedSubcategoryId) {
        const editSubcategorySelect = document.getElementById("addsubcategory_id");

        // 清空次類別選項
        editSubcategorySelect.innerHTML = '<option value="" disabled selected>請選擇次類別</option>';

        // 篩選符合主分類的次類別
        const filteredSubcategories = allSubcategories.filter(
            subcategory => subcategory.subcategory_category == categoryId
        );

        // 新增篩選後的次類別選項
        filteredSubcategories.forEach(subcategory => {
            const option = document.createElement("option");
            option.value = subcategory.id;
            option.textContent = subcategory.name;

            // 如果是當前商品的次分類，則預設選中
            if (subcategory.id == selectedSubcategoryId) {
                option.selected = true;
            }

            editSubcategorySelect.appendChild(option);
        });

        // 如果篩選後無次類別，禁用選單
        editSubcategorySelect.disabled = filteredSubcategories.length === 0;
    }
</script>