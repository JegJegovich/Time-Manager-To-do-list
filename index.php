<?php

// Laden der Aufgaben aus der Datei oder Initialisierung eines leeren Arrays
$tasks = [];
if (!file_exists('tasks.json')) {
    file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$json = file_get_contents('tasks.json');
$decoded = json_decode($json, true);
if (is_array($decoded)) {
    $tasks = $decoded;
}

// Gruppierung der Aufgaben nach Datum
$tasksByDate = [];
foreach ($tasks as $task) {
    $date = $task['date'] ?? 'no_date';
    $tasksByDate[$date][] = [
        'title' => $task['title'],
    ];
}

// Aufgabe hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_title'])) {
    $tasks[] = [
        'title' => $_POST['task_title'],
        'subtasks' => [],
        'timer' => 0,
        'done' => false,
        'subdone' => false,
        'date' => $_POST['task_date']
    ];
    file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Wechsel des Aufgabenstatus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_done'])) {
    $index = (int)$_POST['toggle_done'];
    if (isset($tasks[$index])) {
        $tasks[$index]['done'] = !$tasks[$index]['done'];
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Aufgabe bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_index'], $_POST['new_title'])) {
    $index = (int)$_POST['edit_index'];
    $newTitle = trim($_POST['new_title']);

    if ($newTitle !== '' && isset($tasks[$index])) {
        $tasks[$index]['title'] = $newTitle;
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Aufgabe löschen
if (isset($_POST['delete_index'])) {
    $index = (int)$_POST['delete_index'];
    if (isset($tasks[$index])) {
        array_splice($tasks, $index, 1);
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Unteraufgabe hinzufügen
if (isset($_POST['subtask_index']) && isset($_POST['subtask_title'])) {
    $i = (int)$_POST['subtask_index'];
    if (isset($tasks[$i])) {
        $tasks[$i]['subtasks'][] = $_POST['subtask_title'];
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Wechsel des Status der Unteraufgabe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subdone'])) {
    $taskIndex = (int)$_POST['task_index'];
    $subIndex = (int)$_POST['toggle_subdone'];

    if (isset($tasks[$taskIndex]['subtasks'][$subIndex])) {
        // Initialisierung des Status, falls er noch nicht gesetzt wurde
        if (!isset($tasks[$taskIndex]['subdone'][$subIndex])) {
            $tasks[$taskIndex]['subdone'][$subIndex] = false;
        }

        // Status umschalten
        $tasks[$taskIndex]['subdone'][$subIndex] = !$tasks[$taskIndex]['subdone'][$subIndex];

        // Speichern
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Unteraufgabe löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subtask_index'], $_POST['task_index'])) {
    $taskIndex = (int)$_POST['task_index'];
    $subIndex = (int)$_POST['delete_subtask_index'];

    // Überprüfen, ob die Unteraufgabe existiert
    if (isset($tasks[$taskIndex]['subtasks']) && isset($tasks[$taskIndex]['subtasks'][$subIndex])) {
        // Entfernen der Unteraufgabe
        array_splice($tasks[$taskIndex]['subtasks'], $subIndex, 1);
        if (isset($tasks[$taskIndex]['subdone'][$subIndex])) {
            array_splice($tasks[$taskIndex]['subdone'], $subIndex, 1);
        }
        // Speichern der aktualisierten Daten in der Datei
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // Weiterleitung zur aktuellen Seite
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Unteraufgabe bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subtask_index'], $_POST['task_index'], $_POST['new_subtask'])) {
    $taskIndex = (int)$_POST['task_index'];
    $subIndex = (int)$_POST['edit_subtask_index'];
    $newSubtask = trim($_POST['new_subtask']);

    if ($newSubtask !== '' && isset($tasks[$taskIndex]['subtasks'][$subIndex])) {
        $tasks[$taskIndex]['subtasks'][$subIndex] = $newSubtask;
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Timer der Aufgabe aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_timer'], $_POST['task_index'])) {
    $taskIndex = (int)$_POST['task_index'];
    $hours = (int)$_POST['timer_hours'];
    $minutes = (int)$_POST['timer_minutes'];
    $seconds = (int)$_POST['timer_seconds'];

    $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds;

    if (isset($tasks[$taskIndex])) {
        $tasks[$taskIndex]['timer'] = $totalSeconds;
        file_put_contents('tasks.json', json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>To-Do Taskmanager</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Sofia">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
    <!-- Flatpickr Styles und Skripte -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
</head>
<body>
<h1>📝 Taskliste</h1>

<form class="form" method="POST">
    <input type="text" name="task_title" placeholder="Write new task" required>    
    <input type="text" class="datepicker" name="task_date" value="" readonly />
    <button class="add_btn" type="submit">Add</button>
</form>

<!-- Position des zweiten Eingabefelds mit Button zum Öffnen des Kalenders -->
<form method="POST">
    <input type="text" class="datepicker" name="selected_date" id="selected_date" value="<?= htmlspecialchars($selectedDate) ?>" placeholder="Choose date" readonly />
    <button type="submit" id="openTasksForDay">Show tasks for selected day</button>
    <button type="submit" id="showAllTasks">Show all tasks</button>
</form>

<hr>

<?php
// Проверка, если была выбрана дата, и фильтрация по ней
$selectedDate = $_POST['selected_date'] ?? null;  // сохраняем выбранную дату
if (isset($_POST['showAllTasks'])) {
    $selectedDate = null;  // если выбрана кнопка "Показать все задачи", очищаем выбранную дату
}

// Фильтрация задач по выбранной дате
if ($selectedDate) {
    $filteredTasks = array_filter($tasks, function ($task) use ($selectedDate) {
        return $task['date'] === $selectedDate;
    });
} else {
    $filteredTasks = $tasks;  // показываем все задачи, если дата не выбрана
}

foreach ($filteredTasks as $index => $task): ?>
    <div class="task" data-index="<?= $index ?>">
         <!-- Datum der Aufgabe -->
        <div class="task-date">
            📅 <?= @date('d.m.Y', strtotime($task['date'])) ?>
        </div>
        <div class="task-content">
            <!-- Titel + Bearbeitung -->
            <div class="title_wrap">
                <div class="task-title-block">
                    <strong class="task-title"><?= htmlspecialchars($task['title']) ?></strong>
                    <button class="edit-btn icon">✏</button>
                    <form method="POST" class="edit-form">
                        <input type="hidden" name="edit_index" value="<?= $index ?>">
                        <input type="text" name="new_title" class="edit-input" value="<?= htmlspecialchars($task['title']) ?>" required>
                        <button class="icon" type="submit">✔️</button>
                    </form>
                </div>
            </div>

            <!-- Unteraufgaben + Bearbeitung -->
            <div class="task-body">
                <div class="task-subtasks">
                    <?php if (!empty($task['subtasks'])): ?>
                        <ul>
                            <?php foreach ($task['subtasks'] as $subIndex => $sub): ?>
                                <li class="subtask">
                                    <span class="subtask-text"> ✧ <?= htmlspecialchars($sub) ?></span>

                                    <form method="POST" class="edit-subtask-form" style="display:none;">
                                        <input type="hidden" name="edit_subtask_index" value="<?= $subIndex ?>">
                                        <input type="hidden" name="task_index" value="<?= $index ?>">
                                        <input type="text" name="new_subtask" value="<?= htmlspecialchars($sub) ?>" required>
                                        <button class="icon" type="submit">✔️</button>
                                    </form>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_subtask_index" value="<?= $subIndex ?>">
                                        <input type="hidden" name="task_index" value="<?= $index ?>">
                                        <button class="icon" type="submit">🗑</button>
                                    </form>

                                    <button class="edit-subtask-btn icon">✏</button>
                                    <div class="subtask_status_button">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="task_index" value="<?= $index ?>">
                                            <input type="hidden" name="toggle_subdone" value="<?= $subIndex ?>">
                                            <button class="icon" type="submit" style="background:none; border:none; cursor:pointer;">
                                                <?= isset($task['subdone'][$subIndex]) && $task['subdone'][$subIndex] ? "✅" : "❌" ?>
                                            </button>
                                        </form>
                                    </div>

                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    
                    <div class="subtasks_wrap">
                        <form class="form" method="POST">
                            <input type="hidden" name="subtask_index" value="<?= $index ?>">
                            <input type="text" name="subtask_title" placeholder="New subtask" required>
                            <button class="icon" type="submit">➕</button>
                        </form>
                    </div>
                </div>
            
            <!-- Timer -->
                <div class="timer-wrap" id="timer-display">
                    <form method="POST">
                        <input type="hidden" name="task_index" value="<?= $index ?>">
                        <input type="number" name="timer_hours" min="0" max="99" placeholder="h" style="width:50px;">
                        <input type="number" name="timer_minutes" min="0" max="59" placeholder="min" style="width:60px;">
                        <input type="number" name="timer_seconds" min="0" max="59" placeholder="sec" style="width:60px;">
                        <button type="submit" name="set_timer">⏱Timer</button>
                    </form>

                    <?php if ($task['timer'] > 0): ?>
                        <div id="timer-<?= $index ?>" class="timer-output"></div>
                        <div>
                            <button onclick="startTimer(this, 'timer-<?= $index ?>')">▶</button>
                            <button onclick="pauseTimer('timer-<?= $index ?>')">⏸</button>
                            <button onclick="resetTimer(this, 'timer-<?= $index ?>')">🔁</button>
                        </div>
                        <script>
                            window.addEventListener('DOMContentLoaded', () => {
                                initTimer(<?= $task['timer'] ?>, 'timer-<?= $index ?>');
                            });
                        </script>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Löschen der Aufgabe -->
            <form method="POST">
                <input type="hidden" name="delete_index" value="<?= $index ?>">
                <button class="delete_btn" type="submit">🗑 Delete</button>
            </form>
        </div>

        <!-- Aufgabe erledigt oder nicht-->
        <div class="task-status">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="toggle_done" value="<?= $index ?>">
                <button type="submit">
                    <?= $task['done'] ? "✅ Done!" : "❌ Not done" ?>
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<script>
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const taskDiv = button.closest('.task');
            const title = taskDiv.querySelector('.task-title');
            const form = taskDiv.querySelector('.edit-form');

            title.style.display = 'none';
            form.style.display = 'inline-block';
            button.style.display = 'none';
        });
    });

    document.querySelectorAll('.edit-subtask-btn').forEach(button=> {
        button.addEventListener('click', () => {
            const subtaskDiv = button.closest('li');
            const subtaskText = subtaskDiv.querySelector('.subtask-text');
            const editForm = subtaskDiv.querySelector('.edit-subtask-form');

            subtaskText.style.display = 'none';
            editForm.style.display = 'inline-block';
            button.style.display = 'none';
        });
    });
</script>

<script>
    const timers = {};

    // Funktion zur Zeitformatierung
    function formatTime(totalSeconds) {
        const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const s = String(totalSeconds % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    // Timer initialisieren
    function initTimer(initialSeconds, elementId) {
        // Erstellen eines Objekts für jeden Timer
        if (timers[elementId]) return;

        // Setzen der Anfangszeit
        timers[elementId] = {
            original: initialSeconds,
            remaining: initialSeconds,
            interval: null,
            running: false
        };

        // Setzen der Anfangszeit
        const el = document.getElementById(elementId);
        if (el) {
            el.innerText = formatTime(initialSeconds);
        }
    }

    // Timer starten
    function startTimer(_, elementId) {
        const t = timers[elementId];
        if (!t || t.running || t.remaining <= 0) return;

        t.running = true;
        t.interval = setInterval(() => {
            if (t.remaining <= 0) {
                clearInterval(t.interval);
                t.running = false;
                const el = document.getElementById(elementId);
                if (el) {
                    el.textContent = "⏲ Time is over!";
                    el.style.color = "red";
                }
                return;
            }
            t.remaining--;
            const el = document.getElementById(elementId);
            if (el) {
                el.textContent = formatTime(t.remaining);
            }
        }, 1000);
    }

    // Timer pausieren
    function pauseTimer(elementId) {
        const t = timers[elementId];
        if (t && t.running) {
            clearInterval(t.interval);
            t.running = false;
        }
    }

    // Timer zurücksetzen
    function resetTimer(_, elementId) {
        const t = timers[elementId];
        if (!t) return;

        clearInterval(t.interval);
        t.remaining = t.original;
        t.running = false;
        const el = document.getElementById(elementId);
        if (el) {
            el.textContent = formatTime(t.original);
            el.style.color = "";
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Initialisierung des Kalenders für das Erstellungsdatum der Aufgabe
        const taskDateInput = document.querySelector('input[name="task_date"]');
        
        flatpickr(taskDateInput, {
            dateFormat: "Y-m-d",  // Datumsformat
            defaultDate: "selected_date",  // Setzt das gewä Datum
            static: true,          // Kalender wird immer angezeigt
            disableMobile: true    // Deaktiviert den mobilen Kalender
        });

        // 2. Initialisierung des zweiten Datumsfeldes
        const viewDateInput = document.querySelector('input[name="selected_date"]');
        
        flatpickr(viewDateInput, {
            dateFormat: "Y-m-d",
            disableMobile: true   // Deaktiviert den mobilen Kalender
        });

        // 3. Öffnen des Kalenders im modalen Fenster bei Klick auf die Schaltfläche
        const calendarModal = document.getElementById("calendarModal");
        // const openCalendarButton = document.getElementById("openCalendarButton");

        openCalendarButton.addEventListener("click", function () {
            calendarModal.style.display = "block";  // Zeigt das modale Fenster an
        });

        // 4. Schließen des modalen Fensters
        document.querySelector(".close").addEventListener("click", function () {
            calendarModal.style.display = "none";  // Schließt das modale Fenster
        });
    });
</script>

</body>
</html>