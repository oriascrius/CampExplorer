
<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /CampExplorer/member/index.php');
    exit;
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/CampExplorer/index.php">露營趣</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/CampExplorer/member/dashboard.php">會員中心</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/CampExplorer/member/products/products-list.php">商品列表</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <a href="/CampExplorer/member/cart/cart-list.php" class="btn btn-outline-primary me-3">
                    <i class="bi bi-cart3"></i> 購物車
                </a>
                <a href="/CampExplorer/member/logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> 登出
                </a>
            </div>
        </div>
    </div>
</nav>