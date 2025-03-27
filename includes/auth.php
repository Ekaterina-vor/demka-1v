<?php
// Проверяем, не запущена ли уже сессия
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

/**
 * Функция для авторизации пользователя
 * @param string $login Логин пользователя
 * @param string $password Пароль пользователя
 * @return array Статус авторизации и сообщение
 */
function login($login, $password) {
    global $pdo;
    
    // Проверяем, не заблокирован ли пользователь из-за неудачных попыток входа
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :login");
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Проверяем блокировку
        if ($user['is_blocked'] == 1) {
            // Записываем в сессию информацию о блокировке
            $_SESSION['blocked'] = true;
            
            return [
                'success' => false,
                'message' => 'Вы заблокированы. Обратитесь к администратору',
                'redirect' => 'blocked.php?reason=general'
            ];
        }
        
        // Проверяем блокировку из-за неактивности
        if ($user['last_login'] !== null) {
            $lastLoginDate = new DateTime($user['last_login']);
            $currentDate = new DateTime();
            $interval = $lastLoginDate->diff($currentDate);
            
            if ($interval->days > 30) {
                // Блокируем пользователя
                $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = :id");
                $stmt->execute(['id' => $user['id']]);
                
                // Записываем в сессию информацию о блокировке
                $_SESSION['blocked'] = true;
                
                return [
                    'success' => false,
                    'message' => 'Вы заблокированы из-за длительного отсутствия активности. Обратитесь к администратору',
                    'redirect' => 'blocked.php?reason=inactivity'
                ];
            }
        }
        
        // Проверяем пароль - сравниваем с хешем в базе
        if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
            // Успешная авторизация
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_login'] = $user['first_login'] ?? 0; // Добавим это поле, если его нет
            
            // Обновляем дату последнего входа и сбрасываем счетчик неудачных попыток
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), failed_attempts = 0 WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
            
            return [
                'success' => true,
                'message' => 'Вы успешно авторизовались',
                'first_login' => $_SESSION['first_login']
            ];
        } else {
            // Неверный пароль, увеличиваем счетчик неудачных попыток
            $failedAttempts = $user['failed_attempts'] + 1;
            
            if ($failedAttempts >= 3) {
                // Блокируем пользователя
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = :attempts, is_blocked = 1 WHERE id = :id");
                $stmt->execute([
                    'attempts' => $failedAttempts,
                    'id' => $user['id']
                ]);
                
                // Записываем в сессию информацию о блокировке
                $_SESSION['blocked'] = true;
                
                return [
                    'success' => false,
                    'message' => 'Вы заблокированы из-за превышения лимита неудачных попыток входа. Обратитесь к администратору',
                    'redirect' => 'blocked.php?reason=attempts'
                ];
            } else {
                // Обновляем счетчик неудачных попыток
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = :attempts WHERE id = :id");
                $stmt->execute([
                    'attempts' => $failedAttempts,
                    'id' => $user['id']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные'
                ];
            }
        }
    } else {
        // Пользователь не найден
        return [
            'success' => false,
            'message' => 'Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные'
        ];
    }
}

/**
 * Функция для смены пароля пользователя
 * @param int $userId ID пользователя
 * @param string $currentPassword Текущий пароль
 * @param string $newPassword Новый пароль
 * @param string $confirmPassword Подтверждение нового пароля
 * @return array Статус смены пароля и сообщение
 */
function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
    global $pdo;
    
    // Проверяем, что новый пароль и подтверждение совпадают
    if ($newPassword !== $confirmPassword) {
        return [
            'success' => false,
            'message' => 'Новый пароль и подтверждение не совпадают'
        ];
    }
    
    // Получаем информацию о пользователе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Пользователь не найден'
        ];
    }
    
    // Проверяем текущий пароль
    if (!password_verify($currentPassword, $user['password_hash']) && $currentPassword !== $user['password_hash']) {
        return [
            'success' => false,
            'message' => 'Текущий пароль введен неверно'
        ];
    }
    
    // Не хешируем новый пароль
    $plainPassword = $newPassword;
    
    // Проверяем наличие поля first_login
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'first_login'");
    $checkStmt->execute();
    $firstLoginExists = ($checkStmt->rowCount() > 0);
    
    if ($firstLoginExists) {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password, first_login = 0 WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password WHERE id = :id");
    }
    
    $stmt->execute([
        'password' => $plainPassword,
        'id' => $userId
    ]);
    
    // Обновляем сессию
    if ($firstLoginExists) {
        $_SESSION['first_login'] = 0;
    }
    
    return [
        'success' => true,
        'message' => 'Пароль успешно изменен'
    ];
}

/**
 * Функция для проверки, авторизован ли пользователь
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Функция для проверки, является ли пользователь администратором
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'Администратор');
}

/**
 * Функция для выхода из системы
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Функция для добавления нового пользователя (доступна только администратору)
 * @param string $login Логин пользователя
 * @param string $password Пароль пользователя
 * @param string $role Роль пользователя (admin или user)
 * @return array Статус добавления и сообщение
 */
function addUser($login, $password, $role) {
    global $pdo;
    
    // Проверяем, существует ли пользователь с таким логином
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :login");
    $stmt->execute(['login' => $login]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        return [
            'success' => false,
            'message' => 'Пользователь с таким логином уже существует'
        ];
    }
    
    // Подготовим роль в правильном формате
    $roleValue = ($role === 'admin') ? 'Администратор' : 'Пользователь';
    
    // Больше не хешируем пароль, сохраняем его в чистом виде
    $plainPassword = $password;
    
    // Проверяем наличие поля first_login
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'first_login'");
    $checkStmt->execute();
    $firstLoginExists = ($checkStmt->rowCount() > 0);
    
    if ($firstLoginExists) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, first_login, last_login, created_at) 
                          VALUES (:login, :password, :role, 1, NOW(), NOW())");
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, last_login, created_at) 
                          VALUES (:login, :password, :role, NOW(), NOW())");
    }
    
    $stmt->execute([
        'login' => $login,
        'password' => $plainPassword,
        'role' => $roleValue
    ]);
    
    return [
        'success' => true,
        'message' => 'Пользователь успешно добавлен'
    ];
}

/**
 * Функция для получения списка всех пользователей (доступна только администратору)
 * @return array Список пользователей
 */
function getAllUsers() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT id, username, role, last_login, is_blocked, created_at FROM users ORDER BY id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Функция для разблокировки пользователя (доступна только администратору)
 * @param int $userId ID пользователя
 * @return array Статус разблокировки и сообщение
 */
function unblockUser($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0, failed_attempts = 0 WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    
    return [
        'success' => true,
        'message' => 'Пользователь успешно разблокирован'
    ];
}

/**
 * Функция для изменения роли пользователя (доступна только администратору)
 * @param int $userId ID пользователя
 * @param string $newRole Новая роль (admin или user)
 * @return array Статус изменения и сообщение
 */
function changeUserRole($userId, $newRole) {
    global $pdo;
    
    // Подготовим роль в правильном формате
    $roleValue = ($newRole === 'admin') ? 'Администратор' : 'Пользователь';
    
    $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
    $stmt->execute([
        'role' => $roleValue,
        'id' => $userId
    ]);
    
    return [
        'success' => true,
        'message' => 'Роль пользователя успешно изменена'
    ];
}

/**
 * Функция для сброса пароля пользователя (доступна только администратору)
 * @param int $userId ID пользователя
 * @param string $newPassword Новый пароль
 * @return array Статус сброса и сообщение
 */
function resetUserPassword($userId, $newPassword) {
    global $pdo;
    
    // Больше не хешируем пароль
    $plainPassword = $newPassword;
    
    // Проверяем наличие поля first_login
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'first_login'");
    $checkStmt->execute();
    $firstLoginExists = ($checkStmt->rowCount() > 0);
    
    if ($firstLoginExists) {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password, first_login = 1 WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password WHERE id = :id");
    }
    
    $stmt->execute([
        'password' => $plainPassword,
        'id' => $userId
    ]);
    
    return [
        'success' => true,
        'message' => 'Пароль пользователя успешно сброшен'
    ];
}
?> 