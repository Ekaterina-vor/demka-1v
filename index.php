<?php
// Инициализируем сессию
session_start();

// Подключаем необходимые файлы
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Если пользователь уже авторизован, перенаправляем на защищенную страницу
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Обработка формы авторизации
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    
    // Проверка введенных данных
    if (empty($login) || empty($password)) {
        $error = "Необходимо заполнить все поля";
    } else {
        $result = login($login, $password);
        
        if (isset($result['success']) && $result['success']) {
            // Если есть перенаправление для заблокированного пользователя
            if (isset($result['redirect'])) {
                header("Location: " . $result['redirect']);
                exit;
            }
            
            // Перенаправляем на защищенную страницу
            header("Location: dashboard.php");
            exit;
        } elseif (isset($result['message'])) {
            $error = $result['message'];
        } else {
            $error = "Произошла ошибка при авторизации";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DemkaAuth - Авторизация</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-logo">
                <h1>DemkaAuth</h1>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2 class="auth-heading">Вход в систему</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="message message-error">
                            <span class="message-icon">✕</span>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="auth-warning">
                        <strong>Внимание!</strong> После 3-х неудачных попыток входа ваша учетная запись будет заблокирована. 
                        Учетная запись также блокируется при отсутствии активности более 1 месяца.
                    </div>
                    
                    <form id="login-form" method="POST" action="">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" id="login" name="login" class="form-control" placeholder="Введите логин" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Введите пароль" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">Войти</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="text-muted">© <?php echo date('Y'); ?> DemkaAuth. Все права защищены.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/validation.js"></script>
</body>
</html> 