<?php
/**
 * DemkaAuth - Система авторизации пользователей
 * Файл вспомогательных функций
 */

/**
 * Получение информации о пользователе по ID
 * 
 * @param int $userId ID пользователя
 * @return array|bool Данные пользователя или false в случае ошибки
 */
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Добавляем поле login для совместимости, если оно отсутствует
        if ($user && isset($user['username']) && !isset($user['login'])) {
            $user['login'] = $user['username'];
        }
        
        return $user ? $user : false;
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при получении пользователя по ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Обновление времени последней активности пользователя
 * 
 * @param int $userId ID пользователя
 * @return bool Результат операции
 */
function updateLastActivity($userId) {
    global $pdo;
    
    try {
        $currentTime = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("UPDATE users SET last_activity = :last_activity WHERE id = :id");
        $stmt->bindParam(':last_activity', $currentTime);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при обновлении времени активности: " . $e->getMessage());
        return false;
    }
}

/**
 * Изменение пароля пользователя
 * 
 * @param int $userId ID пользователя
 * @param string $currentPassword Текущий пароль
 * @param string $newPassword Новый пароль
 * @return array Результат операции
 */
function updateUserPassword($userId, $currentPassword, $newPassword) {
    global $pdo;
    
    try {
        // Получаем текущий хеш пароля
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Пользователь не найден'
            ];
        }
        
        // Проверяем текущий пароль
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Текущий пароль указан неверно'
            ];
        }
        
        // Хешируем новый пароль
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Обновляем пароль и сбрасываем флаг первого входа
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password, first_login = 0 WHERE id = :id");
        $stmt->bindParam(':password', $newPasswordHash);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Не удалось изменить пароль'
            ];
        }
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при изменении пароля: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Произошла ошибка при изменении пароля'
        ];
    }
}

/**
 * Получение списка всех пользователей
 * 
 * @param array $filters Фильтры для выборки
 * @return array Список пользователей
 */
function getUsersList($filters = []) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM users";
        $whereConditions = [];
        $params = [];
        
        // Применяем фильтры, если они есть
        if (!empty($filters)) {
            if (isset($filters['blocked']) && $filters['blocked'] !== null) {
                // Проверяем как по полю blocked, так и по is_blocked для совместимости
                $whereConditions[] = "(blocked = :blocked OR is_blocked = :blocked)";
                $params[':blocked'] = $filters['blocked'];
            }
            
            if (isset($filters['role']) && !empty($filters['role'])) {
                $whereConditions[] = "role = :role";
                $params[':role'] = $filters['role'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereConditions[] = "username LIKE :search";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " ORDER BY id ASC";
        
        $stmt = $pdo->prepare($sql);
        
        // Привязываем параметры
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Добавляем поле login для совместимости и синхронизируем статус блокировки
        foreach ($users as &$user) {
            // Синхронизация полей login и username
            if (isset($user['username']) && !isset($user['login'])) {
                $user['login'] = $user['username'];
            }
            
            // Синхронизация полей is_blocked и blocked
            if (isset($user['is_blocked']) && !isset($user['blocked'])) {
                $user['blocked'] = $user['is_blocked'];
            } elseif (isset($user['blocked']) && !isset($user['is_blocked'])) {
                $user['is_blocked'] = $user['blocked'];
            } elseif (isset($user['blocked']) && isset($user['is_blocked']) && $user['blocked'] != $user['is_blocked']) {
                // Если оба поля есть, но они различаются, считаем пользователя заблокированным
                // если хотя бы одно поле указывает на блокировку
                $isBlocked = ($user['blocked'] || $user['is_blocked']) ? 1 : 0;
                $user['blocked'] = $isBlocked;
                $user['is_blocked'] = $isBlocked;
                
                // Обновляем в базе данных для согласованности
                try {
                    $updateStmt = $pdo->prepare("UPDATE users SET blocked = :blocked, is_blocked = :blocked WHERE id = :id");
                    $updateStmt->bindParam(':blocked', $isBlocked, PDO::PARAM_INT);
                    $updateStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                    $updateStmt->execute();
                } catch (PDOException $e) {
                    error_log("Ошибка при синхронизации статуса блокировки: " . $e->getMessage());
                }
            }
        }
        
        return $users;
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при получении списка пользователей: " . $e->getMessage());
        return [];
    }
}

/**
 * Блокировка/разблокировка пользователя
 * 
 * @param int $userId ID пользователя
 * @param int $blocked Статус блокировки (1 - заблокирован, 0 - разблокирован)
 * @param string $reason Причина блокировки
 * @return bool Результат операции
 */
function toggleUserBlock($userId, $blocked, $reason = null) {
    global $pdo;
    
    try {
        // Используем параметризованный запрос с массивом параметров
        $sql = "UPDATE users SET blocked = ?, is_blocked = ?, failed_attempts = 0, block_reason = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$blocked, $blocked, $reason, $userId]);
        
        return $result;
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при изменении статуса блокировки: " . $e->getMessage());
        return false;
    }
}

/**
 * Изменение роли пользователя
 * 
 * @param int $userId ID пользователя
 * @param string $newRole Новая роль пользователя
 * @return bool Результат операции
 */
function updateUserRole($userId, $newRole) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->bindParam(':role', $newRole);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при изменении роли пользователя: " . $e->getMessage());
        return false;
    }
}

/**
 * Сброс пароля пользователя
 * 
 * @param int $userId ID пользователя
 * @return array Результат операции и новый пароль
 */
function generateNewPassword($userId) {
    global $pdo;
    
    try {
        // Генерируем новый пароль
        $newPassword = generateRandomPassword();
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Устанавливаем новый пароль и флаг первого входа
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password, first_login = 1 WHERE id = :id");
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Пароль успешно сброшен',
                'password' => $newPassword
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Не удалось сбросить пароль'
            ];
        }
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при сбросе пароля: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Произошла ошибка при сбросе пароля'
        ];
    }
}

/**
 * Добавление нового пользователя
 * 
 * @param string $login Логин пользователя
 * @param string $password Пароль пользователя
 * @param string $role Роль пользователя
 * @return array Результат операции
 */
function createNewUser($login, $password, $role) {
    global $pdo;
    
    try {
        // Проверяем, существует ли пользователь с таким логином
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :login LIMIT 1");
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Пользователь с таким логином уже существует'
            ];
        }
        
        // Хешируем пароль
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $currentTime = date('Y-m-d H:i:s');
        
        // Адаптируем роль для формата базы данных
        $roleValue = ($role === 'admin') ? 'Администратор' : 'Пользователь';
        
        // Добавляем нового пользователя
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, created_at, last_login) VALUES (:login, :password, :role, :created_at, :last_login)");
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':role', $roleValue);
        $stmt->bindParam(':created_at', $currentTime);
        $stmt->bindParam(':last_login', $currentTime);
        $result = $stmt->execute();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Пользователь успешно добавлен',
                'user_id' => $pdo->lastInsertId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Не удалось добавить пользователя'
            ];
        }
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при добавлении пользователя: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Произошла ошибка при добавлении пользователя: ' . $e->getMessage()
        ];
    }
}

/**
 * Получение общего количества пользователей
 * 
 * @return int Количество пользователей
 */
function getTotalUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при подсчете пользователей: " . $e->getMessage());
        return 0;
    }
}

/**
 * Получение количества активных пользователей
 * 
 * @return int Количество активных пользователей
 */
function getActiveUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_blocked = 0");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при подсчете активных пользователей: " . $e->getMessage());
        return 0;
    }
}

/**
 * Получение количества заблокированных пользователей
 * 
 * @return int Количество заблокированных пользователей
 */
function getBlockedUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_blocked = 1");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при подсчете заблокированных пользователей: " . $e->getMessage());
        return 0;
    }
}

/**
 * Получение количества пользователей онлайн (активность за последние 15 минут)
 * 
 * @return int Количество пользователей онлайн
 */
function getOnlineUsers() {
    global $pdo;
    
    try {
        $timeThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE last_activity > :threshold");
        $stmt->bindParam(':threshold', $timeThreshold);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при подсчете пользователей онлайн: " . $e->getMessage());
        return 0;
    }
}

/**
 * Получение задач пользователя
 * 
 * @param int $userId ID пользователя
 * @return array Список задач пользователя
 */
function getUserTasks($userId) {
    global $pdo;
    
    try {
        // Проверяем, существует ли таблица tasks
        $stmt = $pdo->query("SHOW TABLES LIKE 'tasks'");
        if ($stmt->rowCount() == 0) {
            // Если таблицы нет, создаем некоторые примеры задач
            return [
                [
                    'id' => 1,
                    'title' => 'Изучить документацию системы',
                    'status' => 'В процессе',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
                ],
                [
                    'id' => 2,
                    'title' => 'Ознакомиться с интерфейсом',
                    'status' => 'Выполнено',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
                ],
                [
                    'id' => 3,
                    'title' => 'Обновить данные профиля',
                    'status' => 'Ожидает выполнения',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ];
        }
        
        // Если таблица существует, получаем реальные задачи
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при получении задач пользователя: " . $e->getMessage());
        return [];
    }
}

/**
 * Генерация случайного пароля
 * 
 * @param int $length Длина пароля
 * @return string Сгенерированный пароль
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    $charsLength = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, $charsLength)];
    }
    
    return $password;
}

/**
 * Проверка наличия необходимых полей в таблице users
 * Создание полей, если они отсутствуют
 */
function checkDatabaseFields() {
    global $pdo;
    
    try {
        // Проверяем наличие поля failed_attempts
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'failed_attempts'");
        if ($stmt->rowCount() == 0) {
            // Создаем поле failed_attempts
            $pdo->exec("ALTER TABLE users ADD failed_attempts INT NOT NULL DEFAULT 0");
        }
        
        // Проверяем наличие поля blocked
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'blocked'");
        if ($stmt->rowCount() == 0) {
            // Создаем поле blocked
            $pdo->exec("ALTER TABLE users ADD blocked TINYINT(1) NOT NULL DEFAULT 0");
        }
        
        // Проверяем наличие поля block_reason
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'block_reason'");
        if ($stmt->rowCount() == 0) {
            // Создаем поле block_reason
            $pdo->exec("ALTER TABLE users ADD block_reason VARCHAR(255) NULL");
        }
        
        // Проверяем наличие поля last_activity
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
        if ($stmt->rowCount() == 0) {
            // Создаем поле last_activity
            $pdo->exec("ALTER TABLE users ADD last_activity DATETIME NULL");
            // Устанавливаем текущее время для существующих пользователей
            $currentTime = date('Y-m-d H:i:s');
            $pdo->exec("UPDATE users SET last_activity = '$currentTime' WHERE last_activity IS NULL");
        }
        
        // Проверяем наличие поля first_login
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_login'");
        if ($stmt->rowCount() == 0) {
            // Создаем поле first_login
            $pdo->exec("ALTER TABLE users ADD first_login TINYINT(1) NOT NULL DEFAULT 0");
        }
        
        // Проверяем наличие поля created_at
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
        if ($stmt->rowCount() == 0) {
            // Создаем поле created_at
            $pdo->exec("ALTER TABLE users ADD created_at DATETIME NULL");
            // Устанавливаем текущее время для существующих пользователей
            $currentTime = date('Y-m-d H:i:s');
            $pdo->exec("UPDATE users SET created_at = '$currentTime' WHERE created_at IS NULL");
        }
        
        return true;
    } catch (PDOException $e) {
        // Логирование ошибки
        error_log("Ошибка при проверке полей базы данных: " . $e->getMessage());
        return false;
    }
}

// Проверяем необходимые поля в базе данных при подключении файла
checkDatabaseFields(); 