<?php
require_once 'includes/auth.php';

// Проверяем, авторизован ли пользователь
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Если не первый вход, перенаправляем на соответствующую страницу
if (!isset($_SESSION['first_login']) || $_SESSION['first_login'] != 1) {
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: user.php');
    }
    exit;
}

$error = '';
$success = '';
$pageTitle = 'Смена пароля';

// Обработка формы смены пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Проверяем заполнены ли поля
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Заполните все поля формы';
    } else {
        // Пытаемся сменить пароль
        $result = changePassword($_SESSION['user_id'], $currentPassword, $newPassword, $confirmPassword);
        
        if ($result['success']) {
            $success = $result['message'];
            
            // Перенаправляем на рабочий стол в зависимости от роли
            header('refresh:2;url=' . (isAdmin() ? 'admin.php' : 'user.php'));
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

        <h1>Смена пароля при первом входе</h1>
        
        <div class="message info">
            Это ваш первый вход в систему. Пожалуйста, смените пароль для продолжения работы.
        </div>
        
        <div class="form-container">
            <form id="change-password-form" method="POST" action="">
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
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/validation.js"></script>
</body>
</html> 