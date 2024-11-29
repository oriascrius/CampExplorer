<!-- Edit Modal Start -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">編輯主類別</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">主類別名稱</label>
                        <input type="text" class="form-control" id="editCategoryName" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryStatus" class="form-label">主類別狀態</label>
                        <select class="form-select" id="editCategoryStatus" name="category_status" required>
                            <option value="1">啟用中</option>
                            <option value="0">停用中</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                        <button type="submit" class="btn btn-primary" id="saveCategoryBtn">儲存更改</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- Edit Modal End -->





<!-- Edit Subcategory Modal Start -->
<div class="modal fade" id="editSubcategoryModal" tabindex="-1" aria-labelledby="editSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubcategoryModalLabel">編輯子類別</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSubcategoryForm">
                    <div class="mb-3">
                        <label for="editSubcategoryName" class="form-label">子類別名稱</label>
                        <input type="text" class="form-control" id="editSubcategoryName" name="subcategory_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSubcategoryStatus" class="form-label">子類別狀態</label>
                        <select class="form-select" id="editSubcategoryStatus" name="subcategory_status" required>
                            <option value="1">啟用中</option>
                            <option value="0">停用中</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                        <button type="submit" class="btn btn-primary" id="saveSubcategoryBtn">儲存更改</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- Edit Subcategory Modal End -->




<!-- 新增次類別的 Modal -->
<div class="modal fade" id="addSubcategoryModal" tabindex="-1" aria-labelledby="addSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubcategoryModalLabel">新增次類別</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSubcategoryForm">
                    <div class="mb-3">
                        <label for="subcategoryName" class="form-label">次類別名稱</label>
                        <input type="text" class="form-control" id="subcategoryName" name="subcategory_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="subcategoryStatus" class="form-label">狀態</label>
                        <select class="form-select" id="subcategoryStatus" name="subcategory_status" required>
                            <option value="1">啟用</option>
                            <option value="0">停用</option>
                        </select>
                    </div>
                    <input type="hidden" id="categoryId" name="category_id">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary mt-3">儲存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- 新增次類別的 Modal END -->



<!-- 新增主類別的 Modal -->
<!-- 新增主類別 Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">新增主類別</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">類別名稱</label>
                        <input type="text" class="form-control" id="categoryName" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryStatus" class="form-label">狀態</label>
                        <select class="form-select" id="categoryStatus" name="category_status" required>
                            <option value="1">啟用</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary mt-3">儲存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- 新增主類別的Modal END -->