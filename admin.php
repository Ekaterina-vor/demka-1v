<?php
// Инициализируем сессию
session_start();

// Подключаем необходимые файлы
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Проверяем авторизацию и права администратора
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header("Location: dashboard.php");
    exit;
}

// Получаем информацию о пользователе
$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId);

// Получаем время последней активности
$lastActivity = isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] : time();

// Обработка действий с пользователями
$message = '';
$messageType = '';

// Добавление нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    
    if (empty($login) || empty($password) || empty($role)) {
        $message = 'Необходимо заполнить все поля';
        $messageType = 'error';
    } else {
        $result = createNewUser($login, $password, $role);
        
        if ($result['success']) {
            $message = 'Пользователь успешно добавлен';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// Изменение роли пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    
    if (updateUserRole($userId, $newRole)) {
        $message = 'Роль пользователя успешно изменена';
        $messageType = 'success';
    } else {
        $message = 'Ошибка при изменении роли пользователя';
        $messageType = 'error';
    }
}

// Сброс пароля пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = (int)$_POST['user_id'];
    
    $result = generateNewPassword($userId);
    
    if ($result['success']) {
        $message = 'Пароль успешно сброшен. Новый пароль: ' . $result['password'];
        $messageType = 'success';
    } else {
        $message = 'Ошибка при сбросе пароля';
        $messageType = 'error';
    }
}

// Получаем список пользователей
$users = getUsersList();

// Статистика
$stats = [
    'total_users' => getTotalUsers(),
    'active_users' => getActiveUsers(),
    'blocked_users' => getBlockedUsers(),
    'online_users' => getOnlineUsers()
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DemkaAuth - Панель администратора</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">DemkaAuth</a>
            <ul class="navbar-nav">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Панель</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link">Пользователи</a></li>
                <li class="nav-item"><a href="admin.php" class="nav-link active">Админ-панель</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link">Выход</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1>Панель администратора</h1>
                <p>Добро пожаловать, <strong><?php echo htmlspecialchars($userInfo['login']); ?></strong>! Вы авторизованы как администратор и можете управлять пользователями системы.</p>
                
                <?php if (!empty($message)): ?>
                    <div class="message message-<?php echo $messageType; ?>">
                        <span class="message-icon"><?php echo $messageType === 'success' ? '✓' : '✕'; ?></span>
                        <?php echo htmlspecialchars($message); ?>
                        <span class="message-close">&times;</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

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
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Добавить нового пользователя</h3>
                    </div>
                    <div class="card-body">
                        <form id="add-user-form" method="POST" action="">
                            <input type="hidden" name="action" value="add_user">
                            
                            <div class="form-group">
                                <label for="new_login" class="form-label">Логин</label>
                                <input type="text" id="new_login" name="login" class="form-control" placeholder="Введите логин" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">Пароль</label>
                                <input type="password" id="new_password" name="password" class="form-control" placeholder="Введите пароль" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role" class="form-label">Роль</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="">-- Выберите роль --</option>
                                    <option value="user">Пользователь</option>
                                    <option value="admin">Администратор</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">Добавить пользователя</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Список пользователей</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                            <p class="text-center">Пользователи не найдены</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Логин</th>
                                            <th>Роль</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['login']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                                        <?php echo $user['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'badge-danger' : 'badge-success') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'badge-danger' : 'badge-success') : 'badge-success'); ?>">
                                                        <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'Заблокирован' : 'Активен') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'Заблокирован' : 'Активен') : 'Активен'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="user-actions">
                                                        <button class="btn btn-sm btn-info" onclick="ModalHandler.openModal('change-role-modal-<?php echo $user['id']; ?>')">Изменить роль</button>
                                                        <button class="btn btn-sm btn-warning" onclick="ModalHandler.openModal('reset-password-modal-<?php echo $user['id']; ?>')">Сбросить пароль</button>
                                                    </div>

                                                    <!-- Модальное окно изменения роли -->
                                                    <div id="change-role-modal-<?php echo $user['id']; ?>" class="modal">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h3 class="modal-title">Изменение роли пользователя</h3>
                                                                <span class="modal-close" onclick="ModalHandler.closeModal('change-role-modal-<?php echo $user['id']; ?>')">&times;</span>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Изменение роли пользователя: <strong><?php echo htmlspecialchars($user['login']); ?></strong></p>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="action" value="change_role">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="role-<?php echo $user['id']; ?>" class="form-label">Роль</label>
                                                                        <select id="role-<?php echo $user['id']; ?>" name="role" class="form-control">
                                                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Пользователь</option>
                                                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <button type="submit" class="btn btn-primary">Сохранить</button>
                                                                        <button type="button" class="btn btn-secondary" onclick="ModalHandler.closeModal('change-role-modal-<?php echo $user['id']; ?>')">Отмена</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Модальное окно сброса пароля -->
                                                    <div id="reset-password-modal-<?php echo $user['id']; ?>" class="modal">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h3 class="modal-title">Сброс пароля пользователя</h3>
                                                                <span class="modal-close" onclick="ModalHandler.closeModal('reset-password-modal-<?php echo $user['id']; ?>')">&times;</span>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Вы уверены, что хотите сбросить пароль пользователя: <strong><?php echo htmlspecialchars($user['login']); ?></strong>?</p>
                                                                <p>Пользователю будет создан новый пароль и отображен на экране.</p>
                                                                
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="action" value="reset_password">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    
                                                                    <div class="form-group">
                                                                        <button type="submit" class="btn btn-warning">Сбросить пароль</button>
                                                                        <button type="button" class="btn btn-secondary" onclick="ModalHandler.closeModal('reset-password-modal-<?php echo $user['id']; ?>')">Отмена</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/validation.js"></script>
</body>
</html> 