<?php
session_start();

// Если пользователь не блокирован или не пытался войти, перенаправляем на главную
if (!isset($_GET['reason'])) {
    header('Location: index.php');
    exit;
}

$reason = $_GET['reason'];
$message = '';

if ($reason === 'attempts') {
    $message = 'Ваша учетная запись заблокирована из-за превышения лимита неудачных попыток входа (3 попытки). Пожалуйста, обратитесь к администратору для разблокировки.';
} elseif ($reason === 'inactivity') {
    $message = 'Ваша учетная запись заблокирована из-за длительного отсутствия активности (более 1 месяца). Пожалуйста, обратитесь к администратору для разблокировки.';
} else {
    $message = 'Ваша учетная запись заблокирована. Пожалуйста, обратитесь к администратору для разблокировки.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учетная запись заблокирована</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Учетная запись заблокирована</h1>
        
        <div class="message error">
            <?php echo $message; ?>
        </div>
        
        <div class="message info">
            <h2>Учетная запись блокируется в следующих случаях:</h2>
            <ul>
                <li>После 3-х неудачных попыток входа</li>
                <li>При отсутствии активности более 1 месяца</li>
                <li>По решению администратора системы</li>
            </ul>
        </div>
        
        <div class="btn-container">
            <a href="index.php"><button class="secondary">Вернуться на страницу входа</button></a>
        </div>
    </div>
</body>
</html> 