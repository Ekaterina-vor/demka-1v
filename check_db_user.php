<?php
// Скрипт для проверки данных пользователя и разблокировки
try {
    // Подключение к базе данных
    $pdo = new PDO('mysql:host=localhost;dbname=dump', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Подключение к базе данных успешно\n";
    
    // Проверяем данные пользователя с ID=2
    $userId = 2;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Информация о пользователе ID=2:\n";
        echo "Логин: " . ($user['username'] ?? 'не указан') . "\n";
        echo "Роль: " . ($user['role'] ?? 'не указана') . "\n";
        echo "Статус is_blocked: " . (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'Заблокирован' : 'Не заблокирован') : 'не указан') . "\n";
        echo "Статус blocked: " . (isset($user['blocked']) ? ($user['blocked'] ? 'Заблокирован' : 'Не заблокирован') : 'не указан') . "\n";
        
        // Попытка разблокировать
        echo "\nПытаемся разблокировать пользователя...\n";
        try {
            $stmt = $pdo->prepare("UPDATE users SET blocked = 0, is_blocked = 0, block_reason = NULL, failed_attempts = 0 WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                echo "Пользователь успешно разблокирован\n";
                
                // Проверяем результат разблокировки
                $stmt = $pdo->prepare("SELECT is_blocked, blocked FROM users WHERE id = :id");
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "Новый статус is_blocked: " . (isset($updatedUser['is_blocked']) ? ($updatedUser['is_blocked'] ? 'Заблокирован' : 'Не заблокирован') : 'не указан') . "\n";
                echo "Новый статус blocked: " . (isset($updatedUser['blocked']) ? ($updatedUser['blocked'] ? 'Заблокирован' : 'Не заблокирован') : 'не указан') . "\n";
            } else {
                echo "Ошибка при разблокировке пользователя\n";
            }
        } catch (PDOException $e) {
            echo "Ошибка при разблокировке: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Пользователь с ID=2 не найден\n";
    }
    
} catch (PDOException $e) {
    echo "Ошибка базы данных: " . $e->getMessage() . "\n";
}
?> 