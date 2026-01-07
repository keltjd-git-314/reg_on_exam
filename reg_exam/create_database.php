<?php
// Скрипт для создания/проверки базы данных
$host = 'localhost';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Создание базы данных</title>
    <link rel='stylesheet' href='style.css'>
    <style>
        .db-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='db-container'>
        <h1>🔧 Настройка базы данных</h1>";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Создание базы данных
    $pdo->exec("CREATE DATABASE IF NOT EXISTS exam_registration_db
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "<p class='success'>✅ База данных создана/проверена</p>";

    // Использование созданной базы
    $pdo->exec("USE exam_registration_db");

    // Проверяем существующие таблицы
    $tables = $pdo->query("SHOW TABLES LIKE 'exam_registrations'")->fetchAll();

    if (count($tables) > 0) {
        // Таблица существует, проверяем структуру
        $columns = $pdo->query("SHOW COLUMNS FROM exam_registrations")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');

        echo "<p class='warning'>⚠️ Таблица уже существует</p>";
        echo "<p>Столбцы в таблице:</p><pre>";
        foreach ($columns as $col) {
            echo "{$col['Field']} ({$col['Type']})\n";
        }
        echo "</pre>";

        // Проверяем наличие новых полей
        $requiredFields = ['last_name', 'first_name', 'teacher_last_name', 'teacher_first_name'];
        $missingFields = array_diff($requiredFields, $columnNames);

        if (count($missingFields) > 0) {
            echo "<p class='error'>❌ Отсутствуют новые поля: " . implode(', ', $missingFields) . "</p>";
            echo "<p>Запустите миграцию: <a href='migrate_data.php'>migrate_data.php</a></p>";
        } else {
            echo "<p class='success'>✅ Таблица имеет правильную структуру (разделенные ФИО)</p>";
        }

    } else {
        // Создаем новую таблицу с разделенными ФИО
        $pdo->exec("
            CREATE TABLE exam_registrations (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                last_name VARCHAR(100) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100) DEFAULT NULL,
                course VARCHAR(10) NOT NULL,
                faculty VARCHAR(50) NOT NULL,
                student_group VARCHAR(10) NOT NULL,
                teacher_last_name VARCHAR(100) NOT NULL,
                teacher_first_name VARCHAR(100) NOT NULL,
                teacher_middle_name VARCHAR(100) DEFAULT NULL,
                subject VARCHAR(100) NOT NULL,
                exam_day VARCHAR(2) NOT NULL,
                exam_month VARCHAR(20) NOT NULL,
                exam_time TIME NOT NULL,
                exam_type VARCHAR(50) NOT NULL,
                registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        echo "<p class='success'>✅ Новая таблица создана (с разделенными ФИО)</p>";

        // Добавляем тестовые данные
        $testData = [
            [
                'last_name' => 'Иванов',
                'first_name' => 'Иван',
                'middle_name' => 'Иванович',
                'course' => '1',
                'faculty' => 'ИСАУ',
                'student_group' => '2013',
                'teacher_last_name' => 'Петров',
                'teacher_first_name' => 'Петр',
                'teacher_middle_name' => 'Петрович',
                'subject' => 'Математика',
                'exam_day' => '15',
                'exam_month' => 'Июнь',
                'exam_time' => '10:00',
                'exam_type' => 'Очная'
            ],
            [
                'last_name' => 'Сидорова',
                'first_name' => 'Мария',
                'middle_name' => 'Александровна',
                'course' => '2',
                'faculty' => 'ФСГН',
                'student_group' => '2014',
                'teacher_last_name' => 'Кузнецова',
                'teacher_first_name' => 'Елена',
                'teacher_middle_name' => 'Владимировна',
                'subject' => 'Программирование',
                'exam_day' => '20',
                'exam_month' => 'Июнь',
                'exam_time' => '14:30',
                'exam_type' => 'Дистанционная'
            ]
        ];

        $sql = "INSERT INTO exam_registrations
                (last_name, first_name, middle_name, course, faculty, student_group,
                 teacher_last_name, teacher_first_name, teacher_middle_name,
                 subject, exam_day, exam_month, exam_time, exam_type)
                VALUES (:last_name, :first_name, :middle_name, :course, :faculty,
                        :student_group, :teacher_last_name, :teacher_first_name,
                        :teacher_middle_name, :subject, :exam_day, :exam_month,
                        :exam_time, :exam_type)";

        $stmt = $pdo->prepare($sql);
        foreach ($testData as $data) {
            $stmt->execute($data);
        }

        echo "<p class='success'>✅ Добавлены тестовые записи (2 записи)</p>";
    }

    // Проверяем количество записей
    $count = $pdo->query("SELECT COUNT(*) as total FROM exam_registrations")->fetch()['total'];
    echo "<p><strong>Всего записей в базе:</strong> $count</p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ Ошибка подключения к MySQL: " . $e->getMessage() . "</p>";
    echo "<p>Система будет использовать PHP сессии для хранения данных.</p>";
    echo "<p>Для использования MySQL убедитесь, что:</p>
          <ul>
          <li>MySQL сервер запущен</li>
          <li>Пользователь 'root' имеет доступ</li>
          <li>Порт 3306 не занят</li>
          </ul>";
}

echo "
        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
            <a href='index.html' class='back-link' style='display: inline-block; margin: 5px;'>🏠 На главную</a>
            <a href='admin_panel.php' class='back-link' style='display: inline-block; margin: 5px; background: #9b59b6;'>📋 Админ-панель</a>
            <a href='migrate_data.php' class='back-link' style='display: inline-block; margin: 5px; background: #e67e22;'>🔄 Миграция данных</a>
        </div>
    </div>
</body>
</html>";
?>