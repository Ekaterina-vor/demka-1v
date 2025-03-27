<?php
// Скрипт для обновления статуса блокировки пользователя
try {
    // Подключение к базе данных
    $pdo = new PDO('mysql:host=localhost;dbname=dump', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Обновляем статус блокировки пользователя с id=2 (user1)
    $stmt = $pdo->prepare("UPDATE users SET blocked = 1, is_blocked = 1 WHERE id = 2");
    $stmt->execute();
    
    echo "Статус блокировки обновлен для пользователя с id=2 (user1)\n";
    echo "Обновлено строк: " . $stmt->rowCount() . "\n";
    
    // Проверяем, что статус обновился
    $stmt = $pdo->prepare("SELECT username, is_blocked, blocked FROM users WHERE id = 2");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Пользователь: " . $user['username'] . "\n";
    echo "Статус is_blocked: " . ($user['is_blocked'] ? 'Заблокирован' : 'Не заблокирован') . "\n";
    echo "Статус blocked: " . ($user['blocked'] ? 'Заблокирован' : 'Не заблокирован') . "\n";
    
} catch (PDOException $e) {
    echo "Ошибка базы данных: " . $e->getMessage() . "\n";
}
?> 