<?php

require_once __DIR__ . "../../../../camping_db.php";

if(!isset($_POST["code"])){
    die("請輸入帳號");
}

$code = $_POST["code"];
$name = $_POST["name"];
$discount_type = $_POST["discount_type"];
$discount_value = $_POST["discount_value"];
$min_purchase = $_POST["min_purchase"];
$max_discount = $_POST["max_discount"];
$start_date = $_POST["start_date"];
$end_date = $_POST["end_date"];
$created_at = date("Y-m-d H:i:s");
$updated_at = date("Y-m-d H:i:s");

$sql = "INSERT INTO coupons (code, name, discount_type, discount_value, min_purchase, max_discount, start_date, end_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);

try{
    $stmt->execute([$code, $name, $discount_type, $discount_value, $min_purchase, $max_discount, $start_date, $end_date, $created_at, $updated_at]);
    echo "新增成功";
}catch(PDOException $e){
    // echo $e->getMessage();
    echo "新增失敗";
    echo "Error:" . $e->getMessage() . "<br/>";
    $db = null;
    exit;
}

header("location:/CampExplorer/admin/index.php?page=coupons_list");
exit;
?>
