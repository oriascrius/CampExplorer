<script>
    const imageEditModal = new bootstrap.Modal(document.getElementById('imageEditModal'));
    const productImagesContainer = document.getElementById('productImagesContainer');
    const productImageEditName = document.getElementById('productImageEditName');
    const addImageBtn = document.getElementById('addImageBtn');
    let newImages = []; // 儲存新增的圖片
    let productId = null;
    // 顯示商品圖片並進行操作
    // 顯示商品圖片並進行操作
    document.querySelectorAll('.edit-images-btn').forEach(button => {
        button.addEventListener('click', () => {
            productId = button.dataset.id;
            const productName = button.dataset.name;

            productImageEditName.textContent = productName;
            refreshImages(productId);
            imageEditModal.show();
        });
    });

    // 新增圖片按鈕的事件處理
    document.getElementById('addImageBtn').addEventListener('click', (event) => {
        event.preventDefault();
        const newImageInput = document.getElementById('newImageInput');
        const file = newImageInput.files[0];

        if (!file) {
            alert('請選擇一張圖片');
            return;
        }

        if (!productId) {
            alert('無法取得當前產品 ID');
            return;
        }

        const formData = new FormData();
        formData.append('product_id', productId); // 傳遞當前產品 ID
        formData.append('new_image', file);

        fetch('api/products/upload_image.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                console.log(data); // 檢查伺服器的回應內容
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '修改成功',
                        text: '商品已成功修改！',
                        showConfirmButton: false,
                        timer: 1000 // 自動關閉提示
                    }).then(() => {
                        refreshImages(productId);
                    })


                } else {
                    alert(data.message || '圖片新增失敗');
                }
            })
            .catch(error => console.error('Error:', error));;
    });




    function refreshImages(productId) {
        fetch(`api/products/fetch_product_img.php?product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    productImagesContainer.innerHTML = ''; // 清空當前圖片
                    data.images.forEach(image => {
                        const imgElement = document.createElement('div');
                        imgElement.classList.add('position-relative');
                        if (image.is_main == 0) {
                            imgElement.innerHTML = `
                            <img style="width:150px" src="../uploads/products/img/${image.image_path}" alt="" class="img-thumbnail" width="100">
                            <button class="btn btn-danger btn-sm position-absolute top-0 end-0 delete-image-btn" data-id="${image.id}">X</button>
                        `;
                        } else if (image.is_main == 1) {
                            imgElement.innerHTML = `
                            <img style="width:150px" src="../uploads/products/img/${image.image_path}" alt="" class="img-thumbnail" width="100">
                        `;
                        }
                        productImagesContainer.appendChild(imgElement);
                        productImagesContainer.offsetHeight;
                    });

                    // 重新綁定刪除按鈕的事件
                    bindDeleteButtons();
                } else {
                    alert(data.message || '刷新圖片失敗');
                }
            })
            .catch(error => console.error('刷新圖片錯誤:', error));
    }

    function bindDeleteButtons() {
        document.querySelectorAll('.delete-image-btn').forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault(); // 阻止默認行為
                const imageId = button.dataset.id;

                // 確認刪除圖片的對話框
                Swal.fire({
                    title: '確認刪除',
                    text: '您確定要永久刪除此圖片嗎？',
                    icon: 'warning',
                    showCancelButton: true, // 顯示取消按鈕
                    confirmButtonText: '確認刪除',
                    cancelButtonText: '取消',
                    reverseButtons: true // 確保取消按鈕在左邊
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 使用 console.log 來調試，確認刪除按鈕是否觸發
                        console.log("刪除圖片，圖片 ID:", imageId);

                        fetch('api/products/delete_image.php', {
                                method: 'POST',
                                body: JSON.stringify({
                                    image_id: imageId
                                }),
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => response.json()) // 確保返回的數據是 JSON 格式
                            .then(data => {
                                if (data.success) {
                                    button.closest('div').remove(); // 刪除圖片元素
                                    Swal.fire({
                                        icon: 'success',
                                        title: '修改成功',
                                        text: '圖片已成功刪除！',
                                        showConfirmButton: false,
                                        timer: 1000 // 自動關閉提示
                                    });
                                } else {
                                    alert(data.message || '刪除圖片失敗');
                                }
                            })
                            .catch(error => console.error('刪除圖片錯誤:', error));
                    }
                });
            });
        });
    }
</script>