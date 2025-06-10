<?php
require_once 'includes/config.php';

try {
    $conn = getDBConnection();
    
    // إضافة عمود category_id إلى جدول tasks إذا لم يكن موجوداً
    $sql = "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS category_id INT, ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
    $conn->exec($sql);
    
    // إضافة عمود position إلى جدول tasks إذا لم يكن موجوداً
    $sql = "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS position INT DEFAULT 0";
    $conn->exec($sql);
    
    // إضافة عمود priority إلى جدول tasks إذا لم يكن موجوداً
    $sql = "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS priority ENUM('low', 'medium', 'high') DEFAULT 'medium'";
    $conn->exec($sql);
    
    // إضافة عمود parent_id إلى جدول tasks إذا لم يكن موجوداً
    $sql = "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS parent_id INT, ADD FOREIGN KEY (parent_id) REFERENCES tasks(id) ON DELETE SET NULL";
    $conn->exec($sql);
    
    // إضافة عمود recurring_type إلى جدول tasks إذا لم يكن موجوداً
    $sql = "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS recurring_type ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none'";
    $conn->exec($sql);
    
    // إضافة عمود recurring_interval إلى جدول tasks إذا لم يكن موجوداً
    $sql = "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS recurring_interval INT DEFAULT 1";
    $conn->exec($sql);
    
    echo "تم تحديث قاعدة البيانات بنجاح";
} catch(PDOException $e) {
    echo "خطأ في تحديث قاعدة البيانات: " . $e->getMessage();
}
?> 