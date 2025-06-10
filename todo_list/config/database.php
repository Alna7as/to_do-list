<?php
define('DB_HOST', 'sql304.infinityfree.com');
define('DB_NAME', 'if0_39175585_todo_list_f');
define('DB_USER', 'if0_39175585');
define('DB_PASS', 'NmGNKsbxl50Ir');

function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }
} 