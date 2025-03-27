<?php
/**
 * DemkaAuth - Система авторизации пользователей
 * Подключение к базе данных
 */

// Конфигурация базы данных
$db_config = [
    'host'     => 'localhost',
    'dbname'   => 'dump',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4'
];

try {
    // Создаем PDO соединение
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
} catch (PDOException $e) {
    // Логирование ошибки
    error_log("Ошибка подключения к базе данных: " . $e->getMessage());
    die("Ошибка подключения к базе данных. Пожалуйста, обратитесь к администратору.");
} 