<div class="modal fade" id="imageEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="uploadImageForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">圖片編輯 - <span id="productImageEditName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="productImagesContainer" class="d-flex flex-wrap"></div>
                    <div class="mt-3">
                        <label for="newImageInput" class="form-label">新增圖片</label>
                        <div class="d-flex">
                            <input type="file" id="newImageInput" name="new_image" class="form-control">
                            <button type="button" id="addImageBtn" class="btn btn-primary ms-2">
                                <i class="fa-solid fa-plus fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">確認</button>
                </div>
            </form>
        </div>
    </div>
</div>