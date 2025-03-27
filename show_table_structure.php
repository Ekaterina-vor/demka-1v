<?php
require_once 'config/db.php';

// Получаем структуру таблицы
$stmt = $db->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Структура таблицы users</h1>";
echo "<table border='1'>";
echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th><th>Дополнительно</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column['Field']}</td>";
    echo "<td>{$column['Type']}</td>";
    echo "<td>{$column['Null']}</td>";
    echo "<td>{$column['Key']}</td>";
    echo "<td>{$column['Default']}</td>";
    echo "<td>{$column['Extra']}</td>";
    echo "</tr>";
}

echo "</table>";

// Проверяем есть ли поле failed_attempts
$failedAttemptsExists = false;
$firstLoginExists = false;

foreach ($columns as $column) {
    if ($column['Field'] === 'failed_attempts') {
        $failedAttemptsExists = true;
    }
    if ($column['Field'] === 'first_login') {
        $firstLoginExists = true;
    }
}

echo "<h2>Проверка полей</h2>";
echo "Поле failed_attempts: " . ($failedAttemptsExists ? "Существует" : "Отсутствует") . "<br>";
echo "Поле first_login: " . ($firstLoginExists ? "Существует" : "Отсутствует") . "<br>";

// Если нужных полей нет, добавляем их
if (!$failedAttemptsExists) {
    try {
        $db->exec("ALTER TABLE users ADD failed_attempts INT NOT NULL DEFAULT 0");
        echo "Поле failed_attempts успешно добавлено<br>";
    } catch (PDOException $e) {
        echo "Ошибка добавления поля failed_attempts: " . $e->getMessage() . "<br>";
    }
}

if (!$firstLoginExists) {
    try {
        $db->exec("ALTER TABLE users ADD first_login TINYINT(1) NOT NULL DEFAULT 1");
        echo "Поле first_login успешно добавлено<br>";
    } catch (PDOException $e) {
        echo "Ошибка добавления поля first_login: " . $e->getMessage() . "<br>";
    }
}
?> 