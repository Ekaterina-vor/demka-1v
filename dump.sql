-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Мар 27 2025 г., 05:09
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `dump`
--

-- --------------------------------------------------------

--
-- Структура таблицы `analytics`
--

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `occupancy_rate` decimal(5,2) NOT NULL,
  `ADR` decimal(10,2) NOT NULL,
  `RevPAR` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cleaning`
--

CREATE TABLE `cleaning` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `cleaning_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Назначен к уборке','Чистый') NOT NULL DEFAULT 'Назначен к уборке'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `cleaning`
--

INSERT INTO `cleaning` (`id`, `room_id`, `cleaning_date`, `status`) VALUES
(2, 14, '2025-03-01 10:00:00', 'Назначен к уборке'),
(3, 21, '2025-03-02 09:30:00', 'Чистый'),
(4, 22, '2025-03-02 10:15:00', 'Чистый');

-- --------------------------------------------------------

--
-- Структура таблицы `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `passport_data` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `guests`
--

INSERT INTO `guests` (`id`, `first_name`, `last_name`, `middle_name`, `phone`, `email`, `passport_data`) VALUES
(1, 'Ольга', 'Шевченко', 'Викторовна', '+79991234567', 'shevchenko@example.com', '1234 567890'),
(2, 'Ирина', 'Мазалова', 'Львовна', '+79991234568', 'mazalova@example.com', '1234 567891'),
(3, 'Юрий', 'Семеняка', 'Геннадьевич', '+79991234569', 'semenyaka@example.com', '1234 567892'),
(4, 'Олег', 'Савельев', 'Иванович', '+79991234570', 'saveliev@example.com', '1234 567893'),
(5, 'Эдуард', 'Бунин', 'Михайлович', '+79991234571', 'bunin@example.com', '1234 567894'),
(6, 'Павел', 'Бахшиев', 'Иннокентьевич', '+79991234572', 'bakhshiev@example.com', '1234 567895'),
(7, 'Наталья', 'Тюренкова', 'Сергеевна', '+79991234573', 'tyurenkova@example.com', '1234 567896'),
(8, 'Галина', 'Любяшева', 'Аркадьевна', '+79991234574', 'lyubyasheva@example.com', '1234 567897'),
(9, 'Петр', 'Александров', 'Константинович', '+79991234575', 'alexandrov@example.com', '1234 567898'),
(10, 'Ольга', 'Мазалова', 'Николаевна', '+79991234576', 'mazalova_olga@example.com', '1234 567899');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Карта','Наличные','Онлайн') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `payments`
--

INSERT INTO `payments` (`id`, `reservation_id`, `payment_date`, `amount`, `payment_method`) VALUES
(1, 1, '2025-03-24 09:26:34', 50000.00, 'Карта');

-- --------------------------------------------------------

--
-- Структура таблицы `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` enum('Ожидает','Подтверждено','Отменено') NOT NULL DEFAULT 'Ожидает',
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `reservations`
--

INSERT INTO `reservations` (`id`, `guest_id`, `room_id`, `status`, `check_in_date`, `check_out_date`, `total_price`) VALUES
(1, 1, 1, 'Подтверждено', '2025-02-14 14:00:00', '2025-03-02 12:00:00', 50000.00);

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `floor` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `category` varchar(100) NOT NULL,
  `status` enum('Свободен','Занят','Грязный','Назначен к уборке','Чистый') NOT NULL DEFAULT 'Свободен'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `floor`, `room_number`, `category`, `status`) VALUES
(1, 1, '101', 'Одноместный стандарт', 'Чистый'),
(2, 1, '102', 'Одноместный стандарт', 'Занят'),
(3, 1, '103', 'Одноместный эконом', 'Чистый'),
(4, 1, '104', 'Одноместный эконом', 'Занят'),
(5, 1, '105', 'Стандарт двухместный с 2 раздельными кроватями', 'Занят'),
(6, 1, '106', 'Стандарт двухместный с 2 раздельными кроватями', 'Чистый'),
(7, 1, '107', 'Эконом двухместный с 2 раздельными кроватями', 'Занят'),
(8, 1, '108', 'Эконом двухместный с 2 раздельными кроватями', 'Занят'),
(9, 1, '109', '3-местный бюджет', 'Занят'),
(10, 1, '110', '3-местный бюджет', 'Занят'),
(11, 2, '201', 'Бизнес с 1 или 2 кроватями', 'Занят'),
(12, 2, '202', 'Бизнес с 1 или 2 кроватями', 'Чистый'),
(13, 2, '203', 'Бизнес с 1 или 2 кроватями', 'Занят'),
(14, 2, '204', 'Двухкомнатный двухместный стандарт с 1 или 2 кроватями', 'Назначен к уборке'),
(15, 2, '205', 'Двухкомнатный двухместный стандарт с 1 или 2 кроватями', 'Занят'),
(16, 2, '206', 'Двухкомнатный двухместный стандарт с 1 или 2 кроватями', 'Занят'),
(17, 2, '207', 'Одноместный стандарт', 'Занят'),
(18, 2, '208', 'Одноместный стандарт', 'Занят'),
(19, 2, '209', 'Одноместный стандарт', 'Занят'),
(20, 3, '301', 'Студия', 'Грязный'),
(21, 3, '302', 'Студия', 'Грязный'),
(22, 3, '303', 'Студия', 'Чистый'),
(23, 3, '304', 'Люкс с 2 двуспальными кроватями', 'Занят'),
(24, 3, '305', 'Люкс с 2 двуспальными кроватями', 'Чистый'),
(25, 3, '306', 'Люкс с 2 двуспальными кроватями', 'Занят');

-- --------------------------------------------------------

--
-- Структура таблицы `room_status_reports`
--

CREATE TABLE `room_status_reports` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` enum('Свободен','Занят','Грязный','Назначен к уборке','Чистый') NOT NULL,
  `check_out_date` datetime DEFAULT NULL,
  `report_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `room_status_reports`
--

INSERT INTO `room_status_reports` (`id`, `room_id`, `status`, `check_out_date`, `report_date`) VALUES
(1, 1, 'Чистый', '2025-03-24 09:26:58', '2025-03-24 02:27:20'),
(2, 2, 'Занят', NULL, '2025-03-24 02:30:58'),
(3, 3, 'Чистый', NULL, '2025-03-24 02:30:58'),
(4, 4, 'Занят', '2025-02-02 12:00:00', '2025-03-24 02:30:58'),
(5, 5, 'Занят', '2025-03-07 12:00:00', '2025-03-24 02:30:58'),
(6, 6, 'Чистый', NULL, '2025-03-24 02:30:58'),
(7, 7, 'Занят', '2025-03-17 12:00:00', '2025-03-24 02:30:58'),
(8, 8, 'Занят', '2025-03-20 12:00:00', '2025-03-24 02:30:58'),
(9, 9, 'Занят', '2025-03-12 12:00:00', '2025-03-24 02:30:58'),
(10, 10, 'Занят', '2025-02-02 12:00:00', '2025-03-24 02:30:58');

-- --------------------------------------------------------

--
-- Структура таблицы `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('Администратор','Горничная','Руководитель') NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `stays`
--

CREATE TABLE `stays` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `stays`
--

INSERT INTO `stays` (`id`, `guest_id`, `room_id`, `check_in_date`, `check_out_date`) VALUES
(1, 1, 1, '2025-02-14 14:00:00', '2025-03-24 09:26:58'),
(2, 2, 2, '2025-02-28 14:00:00', NULL),
(3, 3, 4, '2025-02-23 14:00:00', '2025-02-02 12:00:00'),
(4, 4, 5, '2025-03-01 14:00:00', '2025-03-07 12:00:00'),
(5, 5, 7, '2025-02-27 14:00:00', '2025-04-22 12:00:00'),
(6, 6, 7, '2025-02-24 14:00:00', '2025-03-17 12:00:00'),
(7, 7, 8, '2025-02-15 14:00:00', '2025-03-20 12:00:00'),
(8, 8, 9, '2025-02-27 14:00:00', '2025-03-12 12:00:00'),
(9, 9, 10, '2025-02-14 14:00:00', '2025-02-02 12:00:00'),
(10, 10, 11, '2025-02-24 14:00:00', '2025-03-17 12:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Администратор','Пользователь') NOT NULL DEFAULT 'Пользователь',
  `failed_attempts` int(11) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked` tinyint(1) NOT NULL DEFAULT 0,
  `block_reason` varchar(255) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `failed_attempts`, `last_login`, `is_blocked`, `created_at`, `blocked`, `block_reason`, `last_activity`, `first_login`) VALUES
(1, 'admin', '123', 'Администратор', 0, '2025-03-27 04:07:02', 0, '2025-03-27 02:53:14', 0, NULL, '2025-03-27 05:07:07', 0),
(2, 'user1', '123456', 'Пользователь', 0, '2025-03-27 04:07:18', 0, '2025-03-27 02:53:14', 0, NULL, '2025-03-27 05:09:18', 0),
(3, 'user2', '111', 'Пользователь', 0, '2025-03-27 03:01:53', 0, '2025-03-27 03:01:53', 0, NULL, '2025-03-27 05:00:55', 0),
(4, 'user3', '222', 'Пользователь', 0, '2025-03-27 03:04:11', 0, '2025-03-27 03:03:59', 0, NULL, '2025-03-27 05:00:55', 0);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cleaning`
--
ALTER TABLE `cleaning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `passport_data` (`passport_data`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Индексы таблицы `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Индексы таблицы `room_status_reports`
--
ALTER TABLE `room_status_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Индексы таблицы `stays`
--
ALTER TABLE `stays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cleaning`
--
ALTER TABLE `cleaning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `room_status_reports`
--
ALTER TABLE `room_status_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `stays`
--
ALTER TABLE `stays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cleaning`
--
ALTER TABLE `cleaning`
  ADD CONSTRAINT `cleaning_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `room_status_reports`
--
ALTER TABLE `room_status_reports`
  ADD CONSTRAINT `room_status_reports_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `stays`
--
ALTER TABLE `stays`
  ADD CONSTRAINT `stays_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stays_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
