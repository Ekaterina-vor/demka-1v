<?php
// Скрипт для проверки структуры таблицы users и данных
try {
    // Подключение к базе данных
    $pdo = new PDO('mysql:host=localhost;dbname=dump', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Подключение к базе данных успешно\n";
    
    // Проверяем существование таблицы users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "Таблица users не существует!\n";
        
        // Проверяем какие таблицы существуют
        echo "Список доступных таблиц:\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "- $table\n";
        }
        
        exit;
    }
    
    // Получаем структуру таблицы users
    echo "Структура таблицы users:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']} - {$column['Null']}\n";
    }
    
    // Получаем данные пользователей
    echo "\nДанные пользователей:\n";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "Таблица users пуста.\n";
    } else {
        foreach ($users as $user) {
            echo "ID: " . (isset($user['id']) ? $user['id'] : 'н/д');
            echo ", Логин: " . (isset($user['username']) ? $user['username'] : (isset($user['login']) ? $user['login'] : 'н/д'));
            echo ", Роль: " . (isset($user['role']) ? $user['role'] : 'н/д');
            echo ", Заблокирован (is_blocked): " . (isset($user['is_blocked']) ? ($user['is_blocked'] ? 'Да' : 'Нет') : 'н/д');
            echo ", Заблокирован (blocked): " . (isset($user['blocked']) ? ($user['blocked'] ? 'Да' : 'Нет') : 'н/д') . "\n";
            
            // Добавляем полный вывод всех полей пользователя
            echo "Все поля пользователя:\n";
            foreach ($user as $field => $value) {
                echo "  $field: " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Ошибка базы данных: " . $e->getMessage() . "\n";
}
?> 