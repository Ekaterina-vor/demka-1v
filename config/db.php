<?php
// Параметры подключения к базе данных
$host = 'localhost';
$dbname = 'dump';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}
?> 