<?php
// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'todo_list_f');
define('DB_USER', 'root');
define('DB_PASS', '');


// إعدادات التطبيق
define('SITE_NAME', 'قائمة المهام');
define('SITE_URL', 'http://localhost/todo_list');

// دالة الاتصال بقاعدة البيانات
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }
}

// دالة لتنظيف المدخلات
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// بدء الجلسة
session_start();
?> 
