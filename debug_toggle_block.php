<?php
// Отладочный скрипт для блокировки/разблокировки пользователя
require_once 'includes/db.php';

try {
    // Подключаемся к базе данных
    echo "Подключение к базе данных...\n";
    
    // Функция переключения блокировки
    function debugToggleUserBlock($userId, $blocked, $reason = null) {
        global $pdo;
        
        try {
            echo "Вызов функции toggleUserBlock с параметрами:\n";
            echo "userId: $userId\n";
            echo "blocked: $blocked\n";
            echo "reason: " . ($reason ?? 'NULL') . "\n";
            
            // Используем простой запрос без привязки параметров в запросе
            $sql = "UPDATE users SET blocked = ?, is_blocked = ?, failed_attempts = 0, block_reason = ? WHERE id = ?";
            echo "SQL запрос: $sql\n";
            
            $stmt = $pdo->prepare($sql);
            
            // Используем execute с массивом параметров
            echo "Выполнение SQL запроса...\n";
            $result = $stmt->execute([$blocked, $blocked, $reason, $userId]);
            
            echo "Результат выполнения: " . ($result ? 'успешно' : 'ошибка') . "\n";
            echo "Затронуто строк: " . $stmt->rowCount() . "\n";
            
            return $result;
        } catch (PDOException $e) {
            echo "Ошибка в toggleUserBlock: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    // Получаем параметры из GET запроса или используем значения по умолчанию
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 2;
    $blocked = isset($_GET['blocked']) ? (int)$_GET['blocked'] : 0; // 0 - разблокировать, 1 - заблокировать
    $reason = isset($_GET['reason']) ? $_GET['reason'] : null;
    
    // Выводим информацию о пользователе до изменения
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Пользователь до изменения:\n";
        echo "ID: " . $user['id'] . ", Логин: " . $user['username'] . "\n";
        echo "is_blocked: " . ($user['is_blocked'] ?? 'NULL') . ", blocked: " . ($user['blocked'] ?? 'NULL') . "\n";
        
        // Вызываем функцию блокировки/разблокировки
        $result = debugToggleUserBlock($userId, $blocked, $reason);
        
        // Проверяем результат изменения
        if ($result) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "\nПользователь после изменения:\n";
            echo "ID: " . $updatedUser['id'] . ", Логин: " . $updatedUser['username'] . "\n";
            echo "is_blocked: " . ($updatedUser['is_blocked'] ?? 'NULL') . ", blocked: " . ($updatedUser['blocked'] ?? 'NULL') . "\n";
        } else {
            echo "\nОперация не выполнена. Проверьте логи для получения дополнительной информации.\n";
        }
    } else {
        echo "Пользователь с ID $userId не найден.\n";
    }
    
} catch (PDOException $e) {
    echo "Ошибка базы данных: " . $e->getMessage() . "\n";
}
?> 