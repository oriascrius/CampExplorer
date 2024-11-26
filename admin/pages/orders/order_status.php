<?php
function getPaymentStatusBadge($status) {
    $badges = [
        0 => '<span class="badge bg-warning">未付款</span>',
        1 => '<span class="badge bg-success">已付款</span>',
        2 => '<span class="badge bg-secondary">已退款</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-light">未知</span>';
}

function getOrderStatusBadge($status) {
    $badges = [
        0 => '<span class="badge bg-info">待處理</span>',
        1 => '<span class="badge bg-primary">處理中</span>',
        2 => '<span class="badge bg-success">已完成</span>',
        3 => '<span class="badge bg-danger">已取消</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-light">未知</span>';
}