// Edit Modal JS 包含 AJax請求

// 監聽編輯按鈕點擊事件

document.querySelectorAll(".edit-category-btn").forEach((button) => {
  button.addEventListener("click", function (event) {
    const categoryId = this.dataset.categoryId;
    const categoryName = this.dataset.categoryName;
    const categoryStatus = this.dataset.categoryStatus;

    // 填充 modal 內容
    document.getElementById("editCategoryName").value = categoryName;
    document.getElementById("editCategoryStatus").value = categoryStatus;
    document.getElementById("editCategoryForm").dataset.categoryId = categoryId;
    console.log("TEST");
    const editCategoryModal = new bootstrap.Modal(
      document.getElementById("editCategoryModal")
    );
    editCategoryModal.show();
  });
});

// 儲存更改按鈕的處理
const editCategoryForm = document.getElementById("editCategoryForm");
editCategoryForm.addEventListener("submit", function (event) {
  event.preventDefault(); // 阻止表單默認提交行為
  console.log("click");
  const formData = new FormData(editCategoryForm);
  formData.append("category_id", editCategoryForm.dataset.categoryId);

  updateCategory(formData)
    .then((response) => {
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "修改成功",
          text: "類別已成功修改！",
          showConfirmButton: false,
          timer: 1000, // 自動關閉提示
        }).then(() => {
          window.location.reload();
        }); // 重新加載頁面
      } else {
        alert("類別更新失敗：" + response.message);
      }
    })
    .catch((error) => {
      console.error("發生錯誤：", error);
      alert("更新失敗，請稍後再試！");
    });
});

function updateCategory(data) {
  return new Promise((resolve, reject) => {
    fetch("api/categories/edit_category.php", {
      method: "POST",
      body: data,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP 錯誤！狀態碼：${response.status}`);
        }
        return response.json(); // 將回應轉為 JSON
      })
      .then((result) => resolve(result)) // 成功處理 JSON
      .catch((error) => reject(error)); // 處理錯誤
  });
}

// <!-- Edit subcategoryModal JS 包含 AJax請求 -->

// 監聽編輯按鈕點擊事件，根據按鈕的 data-* 屬性填充 modal 內容
document.querySelectorAll(".edit-subcategory-btn").forEach((button) => {
  button.addEventListener("click", function () {
    const subcategoryId = this.dataset.subcategoryId;
    const subcategoryName = this.dataset.subcategoryName;
    const subcategoryStatus = this.dataset.subcategoryStatus;

    // 填充 modal 內容
    document.getElementById("editSubcategoryName").value = subcategoryName;
    document.getElementById("editSubcategoryStatus").value = subcategoryStatus;
    document.getElementById("editSubcategoryForm").dataset.subcategoryId =
      subcategoryId;
    console.log("填充了子類別名稱和狀態");
    const editSubcategoryModal = new bootstrap.Modal(
      document.getElementById("editSubcategoryModal")
    );
    editSubcategoryModal.show();
  });
});
// 監聽表單提交事件
document.getElementById("editSubcategoryForm").addEventListener("submit", function (event) {
  event.preventDefault(); // 阻止表單的默認提交行為
  console.log("表單提交");

  const formData = new FormData(this);
  const subcategoryId = this.dataset.subcategoryId;

  if (!subcategoryId) {
    alert("次類別 ID 錯誤，無法提交更新！");
    return;
  }
  formData.append("subcategory_id", subcategoryId);

  updateSubcategory(formData)
    .then((response) => {
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "修改成功",
          text: "類別已成功修改！",
          showConfirmButton: false,
          timer: 1000, // 自動關閉提示
        }).then(() => {
          window.location.reload();
        }); // 重新加載頁面
      } else {
        alert("子類別更新失敗：" + response.message);
      }
    })
    .catch((error) => {
      console.error("發生錯誤：", error);
      alert("更新失敗，請稍後再試！");
    });
});

// 更新子類別的 API 請求
function updateSubcategory(data) {
  return new Promise((resolve, reject) => {
    fetch("api/categories/edit_subcategory.php", {
      method: "POST",
      body: data,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP 錯誤！狀態碼：${response.status}`);
        }
        return response.json(); // 將回應轉為 JSON
      })
      .then((result) => resolve(result)) // 成功處理 JSON
      .catch((error) => reject(error)); // 處理錯誤
  });
}

// 新增子類別

// 監聽新增次類別按鈕點擊事件
document.querySelectorAll(".add-subcategory-btn").forEach((button) => {
  button.addEventListener("click", function () {
    const categoryId = this.dataset.categoryId;
    document.getElementById("categoryId").value = categoryId; // 填充當前主類別的 ID
    const addSubcategoryModal = new bootstrap.Modal(
      document.getElementById("addSubcategoryModal")
    );
    addSubcategoryModal.show(); // 顯示 modal
  });
});

// 監聽新增次類別表單提交
const addSubcategoryForm = document.getElementById("addSubcategoryForm");
addSubcategoryForm.addEventListener("submit", function (event) {
  event.preventDefault(); // 阻止表單默認提交行為

  const formData = new FormData(addSubcategoryForm);
  const categoryId = document.getElementById("categoryId").value;

  if (!categoryId) {
    alert("主類別 ID 錯誤，無法提交新增！");
    return;
  }

  // 發送新增請求
  addSubcategory(formData)
    .then((response) => {
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "新增成功",
          text: "次類別已成功新增！",
          showConfirmButton: false,
          timer: 1000, // 自動關閉提示
        }).then(() => {
          window.location.reload(); // 重新加載頁面
        });
      } else {
        alert("新增次類別失敗：" + response.message);
      }
    })
    .catch((error) => {
      console.error("發生錯誤：", error);
      alert("新增失敗，請稍後再試！");
    });
});

// 發送新增次類別的 API 請求
function addSubcategory(data) {
  return new Promise((resolve, reject) => {
    fetch("api/categories/add_subcategory.php", {
      method: "POST",
      body: data,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP 錯誤！狀態碼：${response.status}`);
        }
        return response.json(); // 將回應轉為 JSON
      })
      .then((result) => resolve(result)) // 成功處理 JSON
      .catch((error) => reject(error)); // 處理錯誤
  });
}

// 新增主類別
// 監聽新增主類別按鈕的點擊事件
document
  .getElementById("addCategoryBtn")
  .addEventListener("click", function () {
    const addCategoryModal = new bootstrap.Modal(
      document.getElementById("addCategoryModal")
    );
    addCategoryModal.show();
  });

// 監聽新增主類別表單的提交事件
document
  .getElementById("addCategoryForm")
  .addEventListener("submit", function (event) {
    event.preventDefault(); // 阻止表單的默認提交行為

    const formData = new FormData(this); // 獲取表單數據
    addCategory(formData)
      .then((response) => {
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "新增成功",
            text: "主類別已成功新增！",
            showConfirmButton: false,
            timer: 1000, // 自動關閉提示
          }).then(() => {
            window.location.reload();
          }); // 重新加載頁面
        } else {
          alert("新增主類別失敗：" + response.message);
        }
      })
      .catch((error) => {
        console.error("發生錯誤：", error);
        alert("新增失敗，請稍後再試！");
      });
  });

// 新增主類別的 API 請求
function addCategory(data) {
  return new Promise((resolve, reject) => {
    fetch("api/categories/add_category.php", {
      method: "POST",
      body: data,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP 錯誤！狀態碼：${response.status}`);
        }
        return response.json(); // 將回應轉為 JSON
      })
      .then((result) => resolve(result)) // 成功處理 JSON
      .catch((error) => reject(error)); // 處理錯誤
  });
}

// =================================================================
