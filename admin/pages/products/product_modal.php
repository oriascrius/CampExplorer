    <!--edit modalStrat -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">編輯商品</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="productId">
                        <div class="mb-3">
                            <label for="productName" class="form-label">名稱</label>
                            <input type="text" class="form-control" id="productName" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="productPrice" class="form-label">價格</label>
                            <input type="number" step="0.01" class="form-control" id="productPrice" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="productStock" class="form-label">庫存</label>
                            <input type="number" class="form-control" id="productStock" name="stock" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategoryId" class="form-label">類別</label>
                            <select class="form-select" id="editCategoryId" name="category_id" required>
                                <option value="" disabled selected>請選擇類別</option>
                                <?php foreach ($categories as $category): ?>
                                    <?php if ($category['status']  === 1): ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>">
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>" disabled>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSubcategoryId" class="form-label">次類別</label>
                            <select class="form-select" id="editSubcategoryId" name="subcategory_id" required>
                                <option value="" disabled selected>請選擇次類別</option>
                            </select>
                        </div>
                        <script>
                            const allSubcategories = <?= json_encode($subcategories); ?>; // PHP 傳遞的次類別資料
                            console.log(allSubcategories);
                        </script>


                        <div class="mb-3">
                            <label for="productStatus" class="form-label">狀態</label>
                            <select class="form-select" id="productStatus" name="status">
                                <option value="1">上架</option>
                                <option value="0">下架</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">確認</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!--edit Modal End -->




    <!-- add Modal Start -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">新增商品</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" method="POST" enctype="multipart/form-data" action="api/products/add_product.php">
                        <div class="mb-3">
                            <label for="productName" class="form-label">產品名稱</label>
                            <input type="text" class="form-control" name="name" id="productName" required>
                        </div>
                        <!-- 類別選項 使用category表中資料 跑回圈 -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">類別</label>
                            <select class="form-select" id="addcategory_id" name="category_id" required>
                                <option value="" disabled selected>請選擇類別</option>
                                <?php foreach ($categories as $category): ?>
                                    <?php if ($category['status']  === 1): ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>">
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>" disabled>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_id" class="form-label">次類別</label>
                            <select class="form-select" id="addsubcategory_id" name="subcategory_id" required>
                                <option value="" disabled selected>請選擇次類別</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?= htmlspecialchars($subcategory['id']) ?>">
                                        <?= htmlspecialchars($subcategory['name']) ?>
                                    </option>

                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="productDescription" class="form-label">商品描述</label>
                            <textarea class="form-control" name="description" id="productDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="productPrice" class="form-label">價格</label>
                            <input type="number" class="form-control" name="price" id="productPrice" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="productStock" class="form-label">庫存數量</label>
                            <input type="number" class="form-control" name="stock" id="productStock" required>
                        </div>
                        <div class="mb-3">
                            <label for="mainImage" class="form-label">主圖片 (只能選擇一張)</label>
                            <input type="file" class="form-control" name="main_image" id="mainImage" accept="image/*" required>
                        </div>

                        <div class="mb-3">
                            <label for="additionalImages" class="form-label">附加圖片 (可多選)</label>
                            <input type="file" class="form-control" name="additional_images[]" id="additionalImages" multiple accept="image/*">
                            <small class="form-text text-muted">可上傳多張圖片，最大檔案大小 5MB</small>
                        </div>

                        <div class="mb-3">
                            <label for="productStatus" class="form-label">上架狀態</label>
                            <select class="form-control" name="status" id="productStatus">
                                <option value="1">上架</option>
                                <option value="0">下架</option>
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">儲存商品</button>
                </div>
                </form>
            </div>
        </div>
    </div>
    <!-- add Modal End -->