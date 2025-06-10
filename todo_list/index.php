<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'مستخدم';
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// رسائل
$success = '';
$error = '';

function check_csrf() {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die('<div style="color:red;text-align:center;margin:2em;">خطأ أمني: رمز الأمان غير صحيح!</div>');
    }
}

// إضافة مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    check_csrf();
    $title = sanitize($_POST['title']);
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)");
        $stmt->execute([$user_id, $title]);
        $success = 'تمت إضافة المهمة بنجاح!';
    } else {
        $error = 'يرجى إدخال عنوان المهمة.';
    }
}
// معالجة حذف مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['task_id'])) {
    check_csrf();
    $task_id = (int)$_POST['task_id'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    $success = 'تم حذف المهمة بنجاح!';
    // إعادة تحميل الصفحة لتحديث القائمة
    echo '<meta http-equiv="refresh" content="0">';
    exit;
}
// معالجة إكمال مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete' && isset($_POST['task_id'])) {
    check_csrf();
    $task_id = (int)$_POST['task_id'];
    $stmt = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    $success = 'تم إكمال المهمة بنجاح!';
    echo '<meta http-equiv=\'refresh\' content=\'0\'>';
    exit;
}
// معالجة تعديل مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['task_id']) && isset($_POST['new_title'])) {
    check_csrf();
    $task_id = (int)$_POST['task_id'];
    $new_title = sanitize($_POST['new_title']);
    if (!empty($new_title)) {
        $stmt = $conn->prepare("UPDATE tasks SET title = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_title, $task_id, $user_id]);
        $success = 'تم تعديل المهمة بنجاح!';
    } else {
        $error = 'لا يمكن ترك عنوان المهمة فارغ.';
    }
    echo '<meta http-equiv=\'refresh\' content=\'0\'>';
    exit;
}
// معالجة تحديث موقع مهمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_position' && isset($_POST['task_id'], $_POST['position'])) {
    check_csrf();
    $task_id = (int)$_POST['task_id'];
    $position = (int)$_POST['position'];
    $stmt = $conn->prepare("UPDATE tasks SET position = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$position, $task_id, $user_id]);
    exit;
}

// جلب المهام
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

// عند جلب المهام، أضف أولوية عشوائية مؤقتاً (حتى يتم دعمها من الفورم لاحقاً)
foreach ($tasks as &$task) {
    if (!isset($task['priority'])) {
        $task['priority'] = ['low','medium','high'][array_rand(['low','medium','high'])];
    }
    if (!isset($task['icon'])) {
        $task['icon'] = '<i class="bi bi-list-task"></i>';
    }
}
unset($task);

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filtered_tasks = array_filter($tasks, function($task) use ($filter) {
    if ($filter === 'completed') return $task['status'] === 'completed';
    if ($filter === 'pending') return $task['status'] !== 'completed';
    return true;
});

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة مهامي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;400&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header class="header-bar shadow-sm">
        <div class="container d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-2">
                <span class="logo-circle"></span>
                <span class="site-title">قائمة مهامي</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="toggle_theme">
                    <button type="button" id="toggleThemeBtn" class="btn btn-sm btn-light rounded-circle" title="تغيير الوضع">
                        <i class="bi bi-moon"></i>
                    </button>
                </form>
                <a href="logout.php" class="btn btn-sm btn-danger px-3">تسجيل الخروج</a>
            </div>
        </div>
    </header>
    <main class="main-content py-4">
        <div class="container">
            <div class="add-task-section mx-auto mb-4">
                <form class="row g-2 align-items-center" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="col-12 col-md-9">
                        <input type="text" name="title" class="form-control form-control-lg" placeholder="أضف مهمة جديدة..." required autocomplete="off">
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-plus-circle"></i> إضافة</button>
                    </div>
                </form>
                <?php if ($success): ?>
                    <div class="alert alert-success mt-3"> <?php echo $success; ?> </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3"> <?php echo $error; ?> </div>
                <?php endif; ?>
            </div>
            <div class="mb-3 d-flex gap-2 justify-content-center">
                <a href="?filter=all" class="btn btn-sm <?php echo ($filter==='all')?'btn-primary':'btn-outline-primary'; ?>">الكل</a>
                <a href="?filter=pending" class="btn btn-sm <?php echo ($filter==='pending')?'btn-primary':'btn-outline-primary'; ?>">غير المكتملة</a>
                <a href="?filter=completed" class="btn btn-sm <?php echo ($filter==='completed')?'btn-primary':'btn-outline-primary'; ?>">المكتملة</a>
            </div>
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن مهمة...">
            </div>
            <div class="tasks-section mx-auto">
                <?php if (empty($filtered_tasks)): ?>
                    <p class="text-center text-muted">لا توجد مهام بعد.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($filtered_tasks as $task): ?>
                            <div class="col-12">
                                <div class="card task-card priority-<?php echo $task['priority']; ?> animated fadeIn shadow-sm">
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                        <?php if (isset($_POST['action'], $_POST['task_id']) && $_POST['action'] === 'show_edit' && $_POST['task_id'] == $task['id']): ?>
                                            <form method="POST" class="d-flex w-100 gap-2 align-items-center">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="text" name="new_title" class="form-control" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                                                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="task-icon"><?php echo $task['icon']; ?></span>
                                            <span class="task-title<?php echo ($task['status'] === 'completed') ? ' text-decoration-line-through text-muted' : ''; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </span>
                                            <div class="d-flex gap-2">
                                                <?php if ($task['status'] !== 'completed'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="complete">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-circle" title="إكمال المهمة"><i class="bi bi-check2-circle"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="show_edit">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary rounded-circle" title="تعديل المهمة"><i class="bi bi-pencil"></i></button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-task-id="<?php echo $task['id']; ?>" title="حذف المهمة"><i class="bi bi-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer class="footer-bar text-center py-3 mt-5">
        <div class="container">
            <span>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> | alna7as</span>
         
        </div>
    </footer>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteModalLabel">تأكيد حذف المهمة</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
          </div>
          <div class="modal-body">
            هل أنت متأكد أنك تريد حذف هذه المهمة؟ لا يمكن التراجع عن هذا الإجراء.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <form id="confirmDeleteForm" method="POST" class="d-inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="task_id" id="deleteTaskId">
              <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
              <button type="submit" class="btn btn-danger">حذف</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        // زر تغيير الوضع الليلي
        document.getElementById('toggleThemeBtn').onclick = function() {
            let theme = document.documentElement.getAttribute('data-theme');
            theme = (theme === 'dark') ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            document.cookie = 'theme=' + theme + ';path=/';
            this.innerHTML = theme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
        };
        // كود حذف المهمة (event delegation)
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                if (e.target.closest('.delete-btn')) {
                    const btn = e.target.closest('.delete-btn');
                    const taskId = btn.getAttribute('data-task-id');
                    document.getElementById('deleteTaskId').value = taskId;
                    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    modal.show();
                }
            });
        });
        document.getElementById('searchInput').addEventListener('input', function() {
            const val = this.value.trim();
            document.querySelectorAll('.task-card').forEach(card => {
                const title = card.querySelector('.task-title').textContent;
                card.style.display = title.includes(val) ? '' : 'none';
            });
        });
        const tasksList = document.querySelector('.tasks-section .row');
        if (tasksList) {
            new Sortable(tasksList, {
                animation: 150,
                onEnd: function (evt) {
                    const taskCards = tasksList.querySelectorAll('.task-card');
                    taskCards.forEach((card, idx) => {
                        const taskId = card.querySelector('input[name="task_id"]').value;
                        fetch('index.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `action=update_position&task_id=${taskId}&position=${idx}`
                        });
                    });
                }
            });
        }
    </script>
</body>
</html> 