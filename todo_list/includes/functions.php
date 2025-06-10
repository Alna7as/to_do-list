<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($path) {
    header("Location: $path");
    exit();
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('خطأ في التحقق من CSRF');
    }
    return true;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function logActivity($conn, $user_id, $action, $description = '') {
    $sql = "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $user_id,
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

function getTaskWithSubtasks($conn, $task_id, $user_id) {
    $stmt = $conn->prepare("
        SELECT t.*, c.name as category_name, c.color as category_color,
        GROUP_CONCAT(DISTINCT tg.name) as tags
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN task_tags tt ON t.id = tt.task_id
        LEFT JOIN tags tg ON tt.tag_id = tg.id
        WHERE t.id = ? AND t.user_id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$task_id, $user_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        $stmt = $conn->prepare("
            SELECT * FROM tasks 
            WHERE parent_id = ? AND user_id = ?
            ORDER BY position ASC
        ");
        $stmt->execute([$task_id, $user_id]);
        $task['subtasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $task;
}

function getNextRecurringDate($recurring_type, $recurring_interval, $current_date) {
    $date = new DateTime($current_date);
    
    switch ($recurring_type) {
        case 'daily':
            $date->modify("+{$recurring_interval} days");
            break;
        case 'weekly':
            $date->modify("+{$recurring_interval} weeks");
            break;
        case 'monthly':
            $date->modify("+{$recurring_interval} months");
            break;
    }
    
    return $date->format('Y-m-d');
}

function exportTasksToPDF($tasks) {
    require_once __DIR__ . '/../fpdf/fpdf.php';
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    
    // العنوان
    $pdf->Cell(0, 10, 'قائمة المهام', 0, 1, 'C');
    $pdf->Ln(10);
    
    // رأس الجدول
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 10, 'العنوان', 1);
    $pdf->Cell(50, 10, 'الوصف', 1);
    $pdf->Cell(30, 10, 'الأولوية', 1);
    $pdf->Cell(30, 10, 'الحالة', 1);
    $pdf->Cell(40, 10, 'تاريخ الاستحقاق', 1);
    $pdf->Ln();
    
    // بيانات المهام
    $pdf->SetFont('Arial', '', 12);
    foreach ($tasks as $task) {
        $pdf->Cell(40, 10, $task['title'], 1);
        $pdf->Cell(50, 10, $task['description'], 1);
        $pdf->Cell(30, 10, $task['priority'], 1);
        $pdf->Cell(30, 10, $task['status'], 1);
        $pdf->Cell(40, 10, $task['due_date'], 1);
        $pdf->Ln();
    }
    
    $pdf->Output('D', 'tasks.pdf');
}

function exportTasksToExcel($tasks) {
    require_once __DIR__ . '/../PhpSpreadsheet/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // تعيين خصائص المستند
    $spreadsheet->getProperties()
        ->setCreator('نظام إدارة المهام')
        ->setLastModifiedBy('نظام إدارة المهام')
        ->setTitle('قائمة المهام')
        ->setSubject('تصدير المهام')
        ->setDescription('تم إنشاء هذا الملف بواسطة نظام إدارة المهام');
    
    // تنسيق رأس الجدول
    $sheet->setCellValue('A1', 'العنوان');
    $sheet->setCellValue('B1', 'الوصف');
    $sheet->setCellValue('C1', 'الأولوية');
    $sheet->setCellValue('D1', 'الحالة');
    $sheet->setCellValue('E1', 'تاريخ الاستحقاق');
    
    // تنسيق الخلايا
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '000000'],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'CCCCCC'],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
    ];
    
    $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
    
    // بيانات المهام
    $row = 2;
    foreach ($tasks as $task) {
        $sheet->setCellValue('A' . $row, $task['title']);
        $sheet->setCellValue('B' . $row, $task['description']);
        $sheet->setCellValue('C' . $row, $task['priority']);
        $sheet->setCellValue('D' . $row, $task['status']);
        $sheet->setCellValue('E' . $row, $task['due_date']);
        $row++;
    }
    
    // ضبط عرض الأعمدة
    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(50);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(20);
    
    // تنسيق الخلايا
    $sheet->getStyle('A2:E' . ($row - 1))->getAlignment()->setWrapText(true);
    
    // إنشاء ملف مؤقت
    $tempFile = tempnam(sys_get_temp_dir(), 'tasks_');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tempFile);
    
    // إرسال الملف
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="tasks.xlsx"');
    header('Cache-Control: max-age=0');
    header('Content-Length: ' . filesize($tempFile));
    
    readfile($tempFile);
    unlink($tempFile);
    exit;
}

function getTaskAnalytics($conn, $user_id) {
    $analytics = [];
    
    // إحصائيات المهام المكتملة في الأسبوع
    $stmt = $conn->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM tasks
        WHERE user_id = ? AND status = 'completed'
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$user_id]);
    $analytics['completed_tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إحصائيات المهام حسب الأولوية
    $stmt = $conn->prepare("
        SELECT priority, COUNT(*) as count
        FROM tasks
        WHERE user_id = ? AND status = 'pending'
        GROUP BY priority
    ");
    $stmt->execute([$user_id]);
    $analytics['priority_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $analytics;
}

function updateTaskPosition($conn, $task_id, $new_position, $user_id) {
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET position = ? 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$new_position, $task_id, $user_id]);
}

function toggleTheme($conn, $user_id) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET theme = CASE WHEN theme = 'light' THEN 'dark' ELSE 'light' END 
        WHERE id = ?
    ");
    return $stmt->execute([$user_id]);
}

function getUserTheme($conn, $user_id) {
    $stmt = $conn->prepare("SELECT theme FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['theme'] : 'light';
}

// دالة تسجيل الدخول
function loginUser($emailOrUsername, $password) {
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active'");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // تحديث آخر تسجيل دخول
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            // تخزين بيانات المستخدم في الجلسة
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'] ?? $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_theme'] = $user['theme'] ?? 'light';
            // تسجيل النشاط
            logActivity($conn, $user['id'], 'login', 'تم تسجيل الدخول بنجاح');
            // توجيه المستخدم للصفحة المناسبة
            if ($user['role'] === 'admin') {
                header('Location: ' . SITE_URL . '/admin/index.php');
            } else {
                header('Location: ' . SITE_URL . '/index.php');
            }
            exit;
        }
        return false;
    } catch(PDOException $e) {
        error_log("خطأ في تسجيل الدخول: " . $e->getMessage());
        return false;
    }
} 