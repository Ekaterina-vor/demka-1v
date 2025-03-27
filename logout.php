<?php
/**
 * DemkaAuth - Система авторизации пользователей
 * Выход из системы
 */

// Запускаем сессию
session_start();

// Записываем время последней активности перед выходом, если есть ID пользователя
if (isset($_SESSION['user_id'])) {
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    
    // Обновляем время последней активности
    updateLastActivity($_SESSION['user_id']);
}

// Очищаем все переменные сессии
$_SESSION = array();

// Если используются cookie для хранения сессии, удаляем их
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на страницу входа
header("Location: index.php");
exit;
?> 