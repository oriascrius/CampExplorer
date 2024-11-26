<?php
$db_host = 'localhost';
$db_name = 'camp_explorer_db';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

try {
    // 建立 PDO 連線
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $db = new PDO($dsn, $db_user, $db_pass);
    
    // 設定錯誤模式為例外
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 設定預設的提取模式為關聯陣列
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 確保所有字串都使用 utf8mb4 編碼
    $db->query("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // 資料庫連線失敗時的錯誤處理
    error_log("Database Connection Error: " . $e->getMessage());
    die("資料庫連線失敗，請聯繫系統管理員。");
}

// 設定時區為台北
date_default_timezone_set('Asia/Taipei');
