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

// Обработка действий с пользователями
$message = '';
$messageType = '';

// Блокировка/разблокировка пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_block') {
    $userId = (int)$_POST['user_id'];
    $blocked = (int)$_POST['blocked'];
    $reason = $_POST['reason'] ?? null;
    
    if (toggleUserBlock($userId, $blocked, $reason)) {
        $message = $blocked ? 'Пользователь успешно заблокирован' : 'Пользователь успешно разблокирован';
        $messageType = 'success';
    } else {
        $message = 'Ошибка при изменении статуса блокировки';
        $messageType = 'error';
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

// Получаем список пользователей с фильтрами
$blocked = isset($_GET['blocked']) ? (int)$_GET['blocked'] : null;
$role = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$filters = [
    'blocked' => $blocked !== '' ? $blocked : null,
    'role' => $role,
    'search' => $search
];

// Используем getUsersList вместо getAllUsers для совместимости имен полей
$users = getUsersList($filters);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DemkaAuth - Управление пользователями</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">DemkaAuth</a>
            <ul class="navbar-nav">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Панель</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link active">Пользователи</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link">Выход</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1>Управление пользователями</h1>
                
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
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Список пользователей</h2>
                        <a href="#" class="btn btn-primary btn-sm" onclick="ModalHandler.openModal('add-user-modal')">Добавить пользователя</a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="mb-4">
                            <div class="row">
                                <div class="col-12 col-md-3">
                                    <div class="form-group">
                                        <label for="blocked-filter" class="form-label">Статус</label>
                                        <select id="blocked-filter" name="blocked" class="form-control">
                                            <option value="">Все</option>
                                            <option value="0" <?php echo isset($_GET['blocked']) && $_GET['blocked'] === '0' ? 'selected' : ''; ?>>Активные</option>
                                            <option value="1" <?php echo isset($_GET['blocked']) && $_GET['blocked'] === '1' ? 'selected' : ''; ?>>Заблокированные</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="form-group">
                                        <label for="role-filter" class="form-label">Роль</label>
                                        <select id="role-filter" name="role" class="form-control">
                                            <option value="">Все</option>
                                            <option value="admin" <?php echo isset($_GET['role']) && $_GET['role'] === 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                            <option value="user" <?php echo isset($_GET['role']) && $_GET['role'] === 'user' ? 'selected' : ''; ?>>Пользователь</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="form-group">
                                        <label for="search-filter" class="form-label">Поиск</label>
                                        <input type="text" id="search-filter" name="search" class="form-control" placeholder="Поиск по логину" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <div class="form-group">
                                        <label class="form-label visually-hidden">Действие</label>
                                        <button type="submit" class="btn btn-primary btn-block">Применить</button>
                                    </div>
                                </div>
                            </div>
                        </form>

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
                                            <th>Последняя активность</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars(isset($user['login']) ? $user['login'] : $user['username']); ?></td>
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
                                                    <?php 
                                                    if (isset($user['last_activity'])) {
                                                        echo date('d.m.Y H:i', strtotime($user['last_activity']));
                                                    } else {
                                                        echo 'Нет данных';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="user-actions">
                                                        <button class="btn btn-sm btn-info" onclick="ModalHandler.openModal('change-role-modal-<?php echo $user['id']; ?>')">Роль</button>
                                                        <button class="btn btn-sm btn-warning" onclick="ModalHandler.openModal('reset-password-modal-<?php echo $user['id']; ?>')">Пароль</button>
                                                        <button class="btn btn-sm <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'btn-success' : 'btn-danger') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'btn-success' : 'btn-danger') : 'btn-danger'); ?>" onclick="ModalHandler.openModal('block-modal-<?php echo $user['id']; ?>')">
                                                            <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'Разблокировать' : 'Блокировать') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'Разблокировать' : 'Блокировать') : 'Блокировать'); ?>
                                                        </button>
                                                    </div>

                                                    <!-- Модальное окно изменения роли -->
                                                    <div id="change-role-modal-<?php echo $user['id']; ?>" class="modal">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h3 class="modal-title">Изменение роли пользователя</h3>
                                                                <span class="modal-close" onclick="ModalHandler.closeModal('change-role-modal-<?php echo $user['id']; ?>')">&times;</span>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Изменение роли пользователя: <strong><?php echo htmlspecialchars(isset($user['login']) ? $user['login'] : $user['username']); ?></strong></p>
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
                                                                <p>Вы уверены, что хотите сбросить пароль пользователя: <strong><?php echo htmlspecialchars(isset($user['login']) ? $user['login'] : $user['username']); ?></strong>?</p>
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

                                                    <!-- Модальное окно блокировки/разблокировки -->
                                                    <div id="block-modal-<?php echo $user['id']; ?>" class="modal">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h3 class="modal-title">
                                                                    <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'Разблокировка' : 'Блокировка') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'Разблокировка' : 'Блокировка') : 'Блокировка'); ?> пользователя
                                                                </h3>
                                                                <span class="modal-close" onclick="ModalHandler.closeModal('block-modal-<?php echo $user['id']; ?>')">&times;</span>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>
                                                                    Вы уверены, что хотите <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'разблокировать' : 'заблокировать') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'разблокировать' : 'заблокировать') : 'заблокировать'); ?> пользователя: 
                                                                    <strong><?php echo htmlspecialchars(isset($user['login']) ? $user['login'] : $user['username']); ?></strong>?
                                                                </p>
                                                                
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="action" value="toggle_block">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <input type="hidden" name="blocked" value="<?php echo isset($user['blocked']) ? ($user['blocked'] ? 0 : 1) : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 0 : 1) : 1); ?>">
                                                                    
                                                                    <?php if (!(isset($user['blocked']) ? $user['blocked'] : (isset($user['is_blocked']) ? $user['is_blocked'] : false))): ?>
                                                                        <div class="form-group">
                                                                            <label for="block-reason-<?php echo $user['id']; ?>" class="form-label">Причина блокировки</label>
                                                                            <select id="block-reason-<?php echo $user['id']; ?>" name="reason" class="form-control">
                                                                                <option value="manual">Решение администратора</option>
                                                                                <option value="login_attempts">Превышено количество попыток входа</option>
                                                                                <option value="inactivity">Длительная неактивность</option>
                                                                                <option value="other">Другая причина</option>
                                                                            </select>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="form-group">
                                                                        <button type="submit" class="btn <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'btn-success' : 'btn-danger') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'btn-success' : 'btn-danger') : 'btn-danger'); ?>">
                                                                            <?php echo isset($user['blocked']) ? ($user['blocked'] ? 'Разблокировать' : 'Заблокировать') : (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'Разблокировать' : 'Заблокировать') : 'Заблокировать'); ?>
                                                                        </button>
                                                                        <button type="button" class="btn btn-secondary" onclick="ModalHandler.closeModal('block-modal-<?php echo $user['id']; ?>')">Отмена</button>
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
            
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h2>Статистика</h2>
                    </div>
                    <div class="card-body">
                        <p>Всего пользователей: <strong><?php echo getTotalUsers(); ?></strong></p>
                        <p>Активных пользователей: <strong><?php echo getActiveUsers(); ?></strong></p>
                        <p>Заблокированных пользователей: <strong><?php echo getBlockedUsers(); ?></strong></p>
                        <p>Пользователей онлайн: <strong><?php echo getOnlineUsers(); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления пользователя -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Добавление нового пользователя</h3>
                <span class="modal-close" onclick="ModalHandler.closeModal('add-user-modal')">&times;</span>
            </div>
            <div class="modal-body">
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
                            <option value="">Выберите роль</option>
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Добавить пользователя</button>
                        <button type="button" class="btn btn-secondary" onclick="ModalHandler.closeModal('add-user-modal')">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/validation.js"></script>
</body>
</html>