<?php
require_once 'includes/auth.php';

// Проверяем, авторизован ли пользователь
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Если первый вход, перенаправляем на страницу смены пароля
if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
    header('Location: change_password.php');
    exit;
}

$pageTitle = 'Рабочий стол пользователя';
$error = '';
$success = '';

// Если это администратор, перенаправляем на страницу администратора
if (isAdmin()) {
    header('Location: admin.php');
    exit;
}

// Обработка формы смены пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Заполните все поля формы';
    } else {
        $result = changePassword($_SESSION['user_id'], $currentPassword, $newPassword, $confirmPassword);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Система авторизации'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="navbar">
            <div class="logo">Система авторизации</div>
            <div class="user-info">
                <span class="username">
                    Пользователь: <strong><?php echo htmlspecialchars($_SESSION['login']); ?></strong>
                    (<?php echo $_SESSION['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>)
                </span>
                <a href="logout.php"><button class="secondary">Выйти</button></a>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

<h1>Рабочий стол пользователя</h1>

<div class="message info">
    Добро пожаловать, <?php echo htmlspecialchars($_SESSION['login']); ?>! 
    Вы успешно вошли в систему с правами обычного пользователя.
</div>

<div class="content">
    <h2>Доступные действия</h2>
    <ul>
        <li>Просмотр информации</li>
        <li>Работа с системой</li>
        <li><a href="#change-password" onclick="document.getElementById('change-password-form-container').style.display='block'">Сменить пароль</a></li>
    </ul>
</div>

<!-- Форма смены пароля -->
<div id="change-password-form-container" style="display:none;">
    <h2>Смена пароля</h2>
    <div class="form-container">
        <form id="change-password-form" method="POST" action="">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password">Текущий пароль:</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div class="form-group">
                <label for="new_password">Новый пароль:</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Подтверждение нового пароля:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            
            <div class="btn-container">
                <button type="submit">Изменить пароль</button>
                <button type="button" class="secondary" onclick="document.getElementById('change-password-form-container').style.display='none'">Отмена</button>
            </div>
        </form>
    </div>
</div>

    </div>
    <script src="js/validation.js"></script>
</body>
</html> 