<?php
session_start();

// Подключение к базе данных
$host = 'localhost';
$dbname = 'exam_registration_db';
$username = 'root';
$password = '';

$use_mysql = false;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $use_mysql = true;
} catch (PDOException $e) {
    if (!isset($_SESSION['exam_registrations'])) {
        $_SESSION['exam_registrations'] = [];
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $middle_name = isset($_POST['middle_name']) ? htmlspecialchars(trim($_POST['middle_name'])) : null;

    $teacher_last_name = htmlspecialchars(trim($_POST['teacher_last_name']));
    $teacher_first_name = htmlspecialchars(trim($_POST['teacher_first_name']));
    $teacher_middle_name = isset($_POST['teacher_middle_name']) ? htmlspecialchars(trim($_POST['teacher_middle_name'])) : null;

    $course = htmlspecialchars(trim($_POST['course']));
    $faculty = htmlspecialchars(trim($_POST['faculty']));
    $group = htmlspecialchars(trim($_POST['group']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $day = htmlspecialchars(trim($_POST['day']));
    $month = htmlspecialchars(trim($_POST['month']));
    $time = htmlspecialchars(trim($_POST['time']));
    $exam_type = htmlspecialchars(trim($_POST['exam_type']));

    $full_name = $last_name . ' ' . $first_name;
    if (!empty($middle_name)) {
        $full_name .= ' ' . $middle_name;
    }

    $teacher_full_name = $teacher_last_name . ' ' . $teacher_first_name;
    if (!empty($teacher_middle_name)) {
        $teacher_full_name .= ' ' . $teacher_middle_name;
    }

    // Сохранение данных
    if ($use_mysql) {
        try {
            $sql = "INSERT INTO exam_registrations
                    (last_name, first_name, middle_name, course, faculty, student_group,
                     teacher_last_name, teacher_first_name, teacher_middle_name,
                     subject, exam_day, exam_month, exam_time, exam_type)
                    VALUES (:last_name, :first_name, :middle_name, :course, :faculty,
                            :student_group, :teacher_last_name, :teacher_first_name,
                            :teacher_middle_name, :subject, :exam_day, :exam_month,
                            :exam_time, :exam_type)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':last_name' => $last_name,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':course' => $course,
                ':faculty' => $faculty,
                ':student_group' => $group,
                ':teacher_last_name' => $teacher_last_name,
                ':teacher_first_name' => $teacher_first_name,
                ':teacher_middle_name' => $teacher_middle_name,
                ':subject' => $subject,
                ':exam_day' => $day,
                ':exam_month' => $month,
                ':exam_time' => $time,
                ':exam_type' => $exam_type
            ]);

            $saved_in = "базу данных MySQL";
            $success = true;

        } catch (Exception $e) {
            $error = "Ошибка сохранения в БД: " . $e->getMessage();
            $success = false;
        }
    } else {
        $id = count($_SESSION['exam_registrations']) + 1;
        $_SESSION['exam_registrations'][] = [
            'id' => $id,
            'last_name' => $last_name,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'full_name' => $full_name,
            'course' => $course,
            'faculty' => $faculty,
            'student_group' => $group,
            'teacher_last_name' => $teacher_last_name,
            'teacher_first_name' => $teacher_first_name,
            'teacher_middle_name' => $teacher_middle_name,
            'teacher_full_name' => $teacher_full_name,
            'subject' => $subject,
            'exam_day' => $day,
            'exam_month' => $month,
            'exam_time' => $time,
            'exam_type' => $exam_type,
            'registration_date' => date('Y-m-d H:i:s')
        ];
        $saved_in = "сессию PHP";
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результат записи на экзамен</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-left: 4px solid #2ecc71;
            z-index: 1000;
        }
        .notification-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .notification-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }
        .notification-close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <?php if (isset($success) && $success): ?>
        <!-- Уведомление с кнопкой закрытия -->
        <div class="notification success" id="successNotification">
            <div class="notification-content">
                <div style="font-size: 20px;">✅</div>
                <div>
                    <h3 style="margin: 0 0 5px 0;">Запись успешно сохранена!</h3>
                    <p style="margin: 0;">Данные сохранены в: <strong><?php echo $saved_in; ?></strong></p>
                </div>
                <button class="notification-close" onclick="closeNotification()">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="result-container">
        <?php if (isset($success) && $success): ?>
            <div class="success-box">
                <h2>✅ Запись успешно сохранена!</h2>
                <p>Данные сохранены в: <strong><?php echo $saved_in; ?></strong></p>
            </div>

            <div class="data-display">
                <h3>Ваши данные:</h3>
                <div class="data-item"><strong>Студент:</strong> <?php echo htmlspecialchars($full_name); ?></div>
                <div class="data-item"><strong>Преподаватель:</strong> <?php echo htmlspecialchars($teacher_full_name); ?></div>
                <div class="data-item"><strong>Курс:</strong> <?php echo htmlspecialchars($course); ?></div>
                <div class="data-item"><strong>Факультет:</strong> <?php echo htmlspecialchars($faculty); ?></div>
                <div class="data-item"><strong>Группа:</strong> <?php echo htmlspecialchars($group); ?></div>
                <div class="data-item"><strong>Предмет:</strong> <?php echo htmlspecialchars($subject); ?></div>
                <div class="data-item"><strong>Дата сдачи:</strong> <?php echo htmlspecialchars($day) . ' ' . htmlspecialchars($month); ?></div>
                <div class="data-item"><strong>Время:</strong> <?php echo htmlspecialchars($time); ?></div>
                <div class="data-item"><strong>Форма сдачи:</strong> <?php echo htmlspecialchars($exam_type); ?></div>
            </div>

            <div class="actions">
                <a href="index.html" class="btn">Создать новую запись</a>
                <a href="admin_panel.php" class="btn">Посмотреть все записи</a>
            </div>

        <?php elseif (isset($error)): ?>
            <div class="error-box">
                <h2>❌ Ошибка при сохранении</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>

            <div class="actions">
                <a href="index.html" class="btn">Вернуться к форме</a>
                <a href="admin_panel.php" class="btn btn-secondary">Админ панель</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function closeNotification() {
            const notification = document.getElementById('successNotification');
            if (notification) {
                notification.style.display = 'none';
            }
        }

        // Автоматическое закрытие через 5 секунд
        setTimeout(() => {
            closeNotification();
        }, 5000);
    </script>
</body>
</html>