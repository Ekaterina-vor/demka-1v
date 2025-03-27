<?php
// Инициализируем сессию
session_start();

// Подключаем необходимые файлы
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Получаем информацию о пользователе
$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId);

// Получаем время последней активности
$lastActivity = isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] : time();

// Обновляем время последней активности
$_SESSION['last_activity'] = time();
updateLastActivity($userId);

// Определяем роль пользователя
$isAdmin = isAdmin();

// Получаем статистику (только для администратора)
$stats = [];
if ($isAdmin) {
    $stats = [
        'total_users' => getTotalUsers(),
        'active_users' => getActiveUsers(),
        'blocked_users' => getBlockedUsers(),
        'online_users' => getOnlineUsers()
    ];
}

// Получаем задачи пользователя
$userTasks = getUserTasks($userId);

// Обработка формы смены пароля
$passwordChangeMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordChangeMessage = [
            'type' => 'error',
            'text' => 'Необходимо заполнить все поля'
        ];
    } elseif ($newPassword !== $confirmPassword) {
        $passwordChangeMessage = [
            'type' => 'error',
            'text' => 'Новый пароль и подтверждение не совпадают'
        ];
    } elseif (strlen($newPassword) < 6) {
        $passwordChangeMessage = [
            'type' => 'error',
            'text' => 'Новый пароль должен быть не менее 6 символов'
        ];
    } else {
        $result = changePassword($userId, $currentPassword, $newPassword, $confirmPassword);
        
        if ($result['success']) {
            $passwordChangeMessage = [
                'type' => 'success',
                'text' => 'Пароль успешно изменен'
            ];
        } else {
            $passwordChangeMessage = [
                'type' => 'error',
                'text' => $result['message']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DemkaAuth - Панель управления</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">DemkaAuth</a>
            <ul class="navbar-nav">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active">Панель</a></li>
                <?php if ($isAdmin): ?>
                <li class="nav-item"><a href="users.php" class="nav-link">Пользователи</a></li>
                <?php endif; ?>
                <li class="nav-item"><a href="logout.php" class="nav-link">Выход</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1>Панель управления</h1>
                <p>Добро пожаловать, <strong><?php echo htmlspecialchars($userInfo['login']); ?></strong>!</p>
                <p>Роль: <span class="badge <?php echo $isAdmin ? 'badge-admin' : 'badge-user'; ?>"><?php echo $isAdmin ? 'Администратор' : 'Пользователь'; ?></span></p>
                <p>Последняя активность: <?php echo date('d.m.Y H:i:s', $lastActivity); ?></p>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="row">
            <div class="col-12">
                <h2>Статистика системы</h2>
            </div>
        </div>
        <div class="admin-dashboard">
            <div class="dashboard-card">
                <div class="dashboard-icon">👤</div>
                <div class="dashboard-value"><?php echo $stats['total_users']; ?></div>
                <div class="dashboard-label">Всего пользователей</div>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-icon">✓</div>
                <div class="dashboard-value"><?php echo $stats['active_users']; ?></div>
                <div class="dashboard-label">Активных пользователей</div>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-icon">🔒</div>
                <div class="dashboard-value"><?php echo $stats['blocked_users']; ?></div>
                <div class="dashboard-label">Заблокированных пользователей</div>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-icon">📱</div>
                <div class="dashboard-value"><?php echo $stats['online_users']; ?></div>
                <div class="dashboard-label">Пользователей онлайн</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h2>Управление пользователями</h2>
                <p>Вы можете управлять пользователями системы на странице <a href="users.php">Пользователи</a>.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <h2>Ваши задачи</h2>
                <?php if (empty($userTasks)): ?>
                <p>У вас нет активных задач.</p>
                <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Статус</th>
                                    <th>Дата создания</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userTasks as $task): ?>
                                <tr>
                                    <td><?php echo $task['id']; ?></td>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($task['status']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($task['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-6">
                <h2>Изменить пароль</h2>
                <?php if (isset($passwordChangeMessage) && !empty($passwordChangeMessage)): ?>
                <div class="message message-<?php echo $passwordChangeMessage['type']; ?>">
                    <span class="message-icon"><?php echo $passwordChangeMessage['type'] === 'success' ? '✓' : '✕'; ?></span>
                    <?php echo htmlspecialchars($passwordChangeMessage['text']); ?>
                </div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-body">
                        <form id="change-password-form" method="POST" action="">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">Текущий пароль</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Введите текущий пароль" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">Новый пароль</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Введите новый пароль" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Подтвердите новый пароль" required>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Изменить пароль</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/validation.js"></script>
</body>
</html> 