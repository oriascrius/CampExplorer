<?php
$servername = "localhost";
$username = "admin";
$password = "12345";
$dbname = "camp_explorer_db";

try {
    // 建立 PDO 連線並設置錯誤模式為異常
    $pdo = new PDO(
        "mysql:host={$servername};dbname={$dbname};charset=utf8;",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 可選：設置 PDO 的預設提取模式（例如提取關聯數組）
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 記錄錯誤日誌（建議）或顯示自定義錯誤訊息
    error_log($e->getMessage(), 3, "db_errors.log"); // 將錯誤記錄到日誌檔案
    echo "無法連接到資料庫，請稍後再試。";
}
