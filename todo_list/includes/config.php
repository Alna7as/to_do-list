<?php
// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'sql304.infinityfree.com');
define('DB_NAME', 'if0_39175585_todo_list_f');
define('DB_USER', 'if0_39175585');
define('DB_PASS', 'NmGNKsbxl50Ir');


// إعدادات التطبيق
define('SITE_NAME', 'قائمة المهام');
define('SITE_URL', 'https://yo-do.42web.io');

// دالة الاتصال بقاعدة البيانات
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
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