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
    $use_mysql = false;
    // Инициализируем сессию если MySQL не доступен
    if (!isset($_SESSION['exam_registrations'])) {
        $_SESSION['exam_registrations'] = [];
    }
}

// Обработка удаления
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    if ($use_mysql) {
        try {
            $sql = "DELETE FROM exam_registrations WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $delete_id]);
            $message = "Запись успешно удалена";
        } catch (Exception $e) {
            $message = "Ошибка удаления: " . $e->getMessage();
        }
    } else {
        // Удаление из сессии
        if (isset($_SESSION['exam_registrations'])) {
            $new_registrations = [];
            foreach ($_SESSION['exam_registrations'] as $reg) {
                if ($reg['id'] != $delete_id) {
                    $new_registrations[] = $reg;
                }
            }
            $_SESSION['exam_registrations'] = $new_registrations;
            $message = "Запись успешно удалена";
        }
    }

    if (isset($message)) {
        header("Location: admin_panel.php?message=" . urlencode($message));
        exit;
    }
}

// Получение данных
$registrations = [];
$total_count = 0;

if ($use_mysql) {
    try {
        // Проверяем структуру таблицы
        $checkTable = $pdo->query("SHOW COLUMNS FROM exam_registrations");
        $columns = $checkTable->fetchAll(PDO::FETCH_COLUMN);

        // Если есть old поля, используем их
        if (in_array('name', $columns)) {
            // Старая структура
            $sql = "SELECT * FROM exam_registrations ORDER BY registration_date DESC";
            $stmt = $pdo->query($sql);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Формируем полные ФИО
            foreach ($registrations as &$reg) {
                $reg['full_name'] = $reg['name'] ?? '';

                // Пробуем разбить на компоненты если есть пробелы
                $name_parts = explode(' ', $reg['full_name']);
                $reg['last_name'] = $name_parts[0] ?? '';
                $reg['first_name'] = $name_parts[1] ?? '';
                $reg['middle_name'] = $name_parts[2] ?? '';

                $reg['teacher_full_name'] = $reg['teacher'] ?? '';
                $teacher_parts = explode(' ', $reg['teacher_full_name']);
                $reg['teacher_last_name'] = $teacher_parts[0] ?? '';
                $reg['teacher_first_name'] = $teacher_parts[1] ?? '';
                $reg['teacher_middle_name'] = $teacher_parts[2] ?? '';
            }
        } else {
            // Новая структура с разделенными полями
            $sql = "SELECT * FROM exam_registrations ORDER BY registration_date DESC";
            $stmt = $pdo->query($sql);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Формируем полные ФИО для отображения
            foreach ($registrations as &$reg) {
                $reg['full_name'] = $reg['last_name'] . ' ' . $reg['first_name'];
                if (!empty($reg['middle_name'])) {
                    $reg['full_name'] .= ' ' . $reg['middle_name'];
                }

                $reg['teacher_full_name'] = $reg['teacher_last_name'] . ' ' . $reg['teacher_first_name'];
                if (!empty($reg['teacher_middle_name'])) {
                    $reg['teacher_full_name'] .= ' ' . $reg['teacher_middle_name'];
                }
            }
        }

        $total_count = count($registrations);
        $data_source = "MySQL база данных";

    } catch (Exception $e) {
        $data_source = "Ошибка загрузки из БД: " . $e->getMessage();
        // Пробуем загрузить из сессии при ошибке
        if (isset($_SESSION['exam_registrations'])) {
            $registrations = $_SESSION['exam_registrations'];
            $total_count = count($registrations);
            $data_source = "PHP сессия (ошибка MySQL)";
        }
    }
} elseif (isset($_SESSION['exam_registrations'])) {
    $registrations = $_SESSION['exam_registrations'];
    $total_count = count($registrations);
    $data_source = "PHP сессия";
} else {
    $data_source = "Нет данных";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель - Записи на экзамен</title>
   <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .admin-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .stats-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }

        .registrations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .registrations-table th {
            background: #3498db;
            color: white;
            padding: 12px 8px;
            text-align: left;
            position: sticky;
            top: 0;
            border: 1px solid #2980b9;
        }

        .registrations-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .registrations-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .registrations-table tr:hover {
            background-color: #f5f5f5;
        }

        .name-cell {
            min-width: 180px;
            white-space: nowrap;
        }

        .actions-cell {
            white-space: nowrap;
            text-align: center;
        }

        .delete-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin: 10px 5px;
        }

        .back-link:hover {
            background: #2980b9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            border: 2px dashed #ddd;
            border-radius: 5px;
            margin: 20px 0;
        }

        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background: white;
        }

        @media (max-width: 768px) {
            .registrations-table {
                font-size: 12px;
            }

            .registrations-table th,
            .registrations-table td {
                padding: 8px 5px;
            }

            .name-cell {
                min-width: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📋 Административная панель</h1>
            <p>Управление записями на экзамены</p>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <div class="stats-info">
            <p><strong>Всего записей:</strong> <span style="color: #2c3e50; font-weight: bold;"><?php echo $total_count; ?></span></p>
            <p><strong>Источник данных:</strong> <?php echo $data_source; ?></p>
            <p><strong>Дата:</strong> <?php echo date('d.m.Y H:i:s'); ?></p>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <a href="index.html" class="back-link">← Вернуться к форме записи</a>
            <a href="create_database.php" class="back-link" style="background: #27ae60;">🔄 Проверить БД</a>
        </div>

        <?php if ($total_count > 0): ?>
            <div class="table-container">
                <table class="registrations-table">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th class="name-cell">Студент</th>
                            <th width="60">Курс</th>
                            <th width="80">Факультет</th>
                            <th width="70">Группа</th>
                            <th width="120">Предмет</th>
                            <th class="name-cell">Преподаватель</th>
                            <th width="100">Дата сдачи</th>
                            <th width="80">Время</th>
                            <th width="100">Форма</th>
                            <th width="100">Дата записи</th>
                            <th width="80" class="actions-cell">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['id'] ?? ''); ?></td>
                                <td class="name-cell">
                                    <?php
                                    if (isset($reg['full_name']) && !empty($reg['full_name'])) {
                                        echo htmlspecialchars($reg['full_name']);
                                    } elseif (isset($reg['last_name']) && isset($reg['first_name'])) {
                                        $name = htmlspecialchars($reg['last_name'] . ' ' . $reg['first_name']);
                                        if (!empty($reg['middle_name'])) {
                                            $name .= ' ' . htmlspecialchars($reg['middle_name']);
                                        }
                                        echo $name;
                                    } elseif (isset($reg['name'])) {
                                        echo htmlspecialchars($reg['name']);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($reg['course'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($reg['faculty'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($reg['student_group'] ?? $reg['group'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($reg['subject'] ?? ''); ?></td>
                                <td class="name-cell">
                                    <?php
                                    if (isset($reg['teacher_full_name']) && !empty($reg['teacher_full_name'])) {
                                        echo htmlspecialchars($reg['teacher_full_name']);
                                    } elseif (isset($reg['teacher_last_name']) && isset($reg['teacher_first_name'])) {
                                        $teacher = htmlspecialchars($reg['teacher_last_name'] . ' ' . $reg['teacher_first_name']);
                                        if (!empty($reg['teacher_middle_name'])) {
                                            $teacher .= ' ' . htmlspecialchars($reg['teacher_middle_name']);
                                        }
                                        echo $teacher;
                                    } elseif (isset($reg['teacher'])) {
                                        echo htmlspecialchars($reg['teacher']);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $exam_date = '';
                                    if (isset($reg['exam_day']) && isset($reg['exam_month'])) {
                                        $exam_date = htmlspecialchars($reg['exam_day']) . ' ' . htmlspecialchars($reg['exam_month']);
                                    }
                                    echo $exam_date ?: '—';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($reg['exam_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($reg['exam_type'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    if (isset($reg['registration_date'])) {
                                        echo date('d.m.Y H:i', strtotime($reg['registration_date']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td class="actions-cell">
                                    <a href="?delete_id=<?php echo $reg['id']; ?>"
                                       class="delete-btn"
                                       onclick="return confirm('Удалить запись #<?php echo $reg['id']; ?>?\nСтудент: <?php echo htmlspecialchars(addslashes($reg['full_name'] ?? $reg['name'] ?? '')); ?>')">
                                        Удалить
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px; padding: 10px; background: #e8f4f8; border-radius: 5px;">
                <p><strong>Информация:</strong> Для поиска используйте Ctrl+F в браузере</p>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <h2>📭 Записей пока нет</h2>
                <p>База данных пуста. Создайте первую запись через форму.</p>
                <div style="margin-top: 20px;">
                    <a href="index.html" class="back-link">➕ Создать первую запись</a>
                </div>
                <div style="margin-top: 15px;">
                    <a href="create_database.php" class="back-link" style="background: #95a5a6;">🔄 Проверить/создать БД</a>
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <a href="index.html" class="back-link" style="background: #7f8c8d;">🏠 На главную</a>
            <a href="admin_panel.php" class="back-link">🔄 Обновить список</a>
        </div>

        <div style="margin-top: 20px; font-size: 12px; color: #95a5a6; text-align: center;">
            <p>Система управления записями на экзамены | <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        // Автоматическое скрытие сообщения через 5 секунд
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(msg) {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(function() {
                    msg.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // Подтверждение удаления с улучшенным диалогом
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Вы уверены, что хотите удалить эту запись?\nДействие нельзя отменить.')) {
                        e.preventDefault();
                    }
                });
            });
        });

        // Быстрый поиск по таблице (дополнительная функция)
        function addSearchFunctionality() {
            const table = document.querySelector('.registrations-table');
            if (!table) return;

            const header = document.createElement('div');
            header.innerHTML = `
                <div style="margin: 10px 0;">
                    <input type="text" id="tableSearch" placeholder="🔍 Поиск по таблице..."
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">
                </div>
            `;
            table.parentNode.insertBefore(header, table);

            document.getElementById('tableSearch').addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = table.getElementsByTagName('tr');

                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let found = false;

                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent || cells[j].innerText;
                        if (cellText.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }

                    rows[i].style.display = found ? '' : 'none';
                }
            });
        }

        // Добавляем поиск если таблица есть
        if (document.querySelector('.registrations-table')) {
            addSearchFunctionality();
        }
    </script>
</body>
</html>